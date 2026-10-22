<?php
/**
 * Ações de Atualização de Partidas e Avanço Automático de Chaves
 * Auditoria Completa de Alterações de Placares
 */
require_once __DIR__ . '/../bd.php';

exigirLogin();

$acao = $_POST['acao'] ?? '';

if ($acao === 'salvar_placar') {
    $partidaId = intval($_POST['partida_id'] ?? 0);
    $torneioId = intval($_POST['torneio_id'] ?? 0);
    $placar1 = isset($_POST['placar1']) && $_POST['placar1'] !== '' ? intval($_POST['placar1']) : null;
    $placar2 = isset($_POST['placar2']) && $_POST['placar2'] !== '' ? intval($_POST['placar2']) : null;
    $penaltis1 = isset($_POST['penaltis1']) && $_POST['penaltis1'] !== '' ? intval($_POST['penaltis1']) : null;
    $penaltis2 = isset($_POST['penaltis2']) && $_POST['penaltis2'] !== '' ? intval($_POST['penaltis2']) : null;
    $observacoes = trim($_POST['observacoes'] ?? '');

    if ($partidaId <= 0 || $torneioId <= 0 || $placar1 === null || $placar2 === null) {
        definirToast('erro', 'Dados Inválidos', 'Informe os placares válidos para ambas as equipes.');
        header("Location: ../torneio.php?id={$torneioId}");
        exit;
    }

    try {
        $db = getDB();
        $db->beginTransaction();

        // Busca dados atuais da partida e do torneio
        $stmtPartida = $db->prepare("
            SELECT p.*, e1.nome AS nome_time1, e2.nome AS nome_time2, t.tem_terceiro_lugar
            FROM partidas p
            LEFT JOIN equipes e1 ON p.equipe1_id = e1.id
            LEFT JOIN equipes e2 ON p.equipe2_id = e2.id
            INNER JOIN torneios t ON p.torneio_id = t.id
            WHERE p.id = ? AND p.torneio_id = ?
        ");
        $stmtPartida->execute([$partidaId, $torneioId]);
        $partida = $stmtPartida->fetch();

        if (!$partida) {
            throw new Exception('Partida não encontrada no sistema.');
        }

        if (empty($partida['equipe1_id']) || empty($partida['equipe2_id'])) {
            throw new Exception('Esta partida ainda não possui ambas as equipes definidas.');
        }

        // Determinação do Vencedor e Perdedor
        $vencedorId = null;
        $perdedorId = null;
        $motivoDesempate = '';

        if ($placar1 > $placar2) {
            $vencedorId = $partida['equipe1_id'];
            $perdedorId = $partida['equipe2_id'];
        } elseif ($placar2 > $placar1) {
            $vencedorId = $partida['equipe2_id'];
            $perdedorId = $partida['equipe1_id'];
        } else {
            // Empate no tempo normal: exige desempate por pênaltis
            if ($penaltis1 === null || $penaltis2 === null || $penaltis1 === $penaltis2) {
                definirToast('aviso', 'Desempate Obrigatório', 'A partida terminou empatada! É indispensável registrar o vencedor nos pênaltis ou prorrogação.');
                header("Location: ../torneio.php?id={$torneioId}");
                exit;
            }

            if ($penaltis1 > $penaltis2) {
                $vencedorId = $partida['equipe1_id'];
                $perdedorId = $partida['equipe2_id'];
                $motivoDesempate = " ({$penaltis1} x {$penaltis2} nos pênaltis)";
            } else {
                $vencedorId = $partida['equipe2_id'];
                $perdedorId = $partida['equipe1_id'];
                $motivoDesempate = " ({$penaltis2} x {$penaltis1} nos pênaltis)";
            }
        }

        $nomeVencedor = ($vencedorId == $partida['equipe1_id']) ? $partida['nome_time1'] : $partida['nome_time2'];
        $nomePerdedor = ($perdedorId == $partida['equipe1_id']) ? $partida['nome_time1'] : $partida['nome_time2'];

        // Atualiza a partida atual
        $stmtUpdate = $db->prepare("
            UPDATE partidas 
            SET placar1 = ?, placar2 = ?, penaltis1 = ?, penaltis2 = ?, 
                vencedor_id = ?, perdedor_id = ?, status = 'finalizada', observacoes = ?, atualizado_em = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmtUpdate->execute([$placar1, $placar2, $penaltis1, $penaltis2, $vencedorId, $perdedorId, $observacoes, $partidaId]);

        // Avança o vencedor para a próxima partida (se houver próxima fase)
        if (!empty($partida['proxima_partida_id'])) {
            $slot = intval($partida['proxima_slot']);
            $colunaSlot = ($slot === 1) ? 'equipe1_id' : 'equipe2_id';
            
            $stmtProx = $db->prepare("UPDATE partidas SET {$colunaSlot} = ? WHERE id = ?");
            $stmtProx->execute([$vencedorId, $partida['proxima_partida_id']]);
        }

        // Se for semifinal e o torneio tiver disputa de 3º lugar, avança o perdedor para a partida de 3º lugar
        if ($partida['tem_terceiro_lugar'] && $partida['fase'] < 90) {
            // Verifica se a próxima partida é a Final (ou seja, esta partida é a semifinal)
            $stmtVerificaFinal = $db->prepare("
                SELECT fase FROM partidas WHERE id = ?
            ");
            if (!empty($partida['proxima_partida_id'])) {
                $stmtVerificaFinal->execute([$partida['proxima_partida_id']]);
                $proxPartida = $stmtVerificaFinal->fetch();
                
                // Se a próxima partida for a final do torneio, esta é semifinal
                $stmtMaxFase = $db->prepare("SELECT MAX(fase) as max_fase FROM partidas WHERE torneio_id = ? AND fase < 90");
                $stmtMaxFase->execute([$torneioId]);
                $maxFase = $stmtMaxFase->fetchColumn();

                if ($proxPartida && $proxPartida['fase'] == $maxFase) {
                    // Busca a partida de 3º lugar (fase 99)
                    $stmtTerceiro = $db->prepare("SELECT id FROM partidas WHERE torneio_id = ? AND fase = 99 LIMIT 1");
                    $stmtTerceiro->execute([$torneioId]);
                    $partida3 = $stmtTerceiro->fetch();

                    if ($partida3) {
                        // Se for a semi 1 (slot 1 da final), vai pro slot 1 do 3º lugar. Se semi 2, slot 2.
                        $colTerceiro = ($partida['proxima_slot'] == 1) ? 'equipe1_id' : 'equipe2_id';
                        $db->prepare("UPDATE partidas SET {$colTerceiro} = ? WHERE id = ?")
                           ->execute([$perdedorId, $partida3['id']]);
                    }
                }
            }
        }

        // Se foi a Grande Final, já define campeão e vice no registro do torneio
        $stmtMaxFaseTorneio = $db->prepare("SELECT MAX(fase) as max_fase FROM partidas WHERE torneio_id = ? AND fase < 90");
        $stmtMaxFaseTorneio->execute([$torneioId]);
        if ($partida['fase'] == $stmtMaxFaseTorneio->fetchColumn()) {
            $db->prepare("UPDATE torneios SET campeao_id = ?, vice_id = ? WHERE id = ?")
               ->execute([$vencedorId, $perdedorId, $torneioId]);
        }

        // Monta detalhes de auditoria com estado anterior e novo
        $placarAnterior = ($partida['placar1'] !== null) ? "{$partida['placar1']} x {$partida['placar2']}" : "Sem placar";
        $placarNovo = "{$placar1} x {$placar2}{$motivoDesempate}";
        
        $detalhesLog = json_encode([
            'partida_id' => $partidaId,
            'jogo_numero' => $partida['numero_jogo'],
            'fase' => $partida['nome_fase'],
            'equipe1' => $partida['nome_time1'],
            'equipe2' => $partida['nome_time2'],
            'placar_anterior' => $placarAnterior,
            'placar_novo' => $placarNovo,
            'vencedor' => $nomeVencedor
        ], JSON_UNESCAPED_UNICODE);

        registrarLog(
            $torneioId,
            'ATUALIZACAO_PLACAR',
            "Placar definido no Jogo #{$partida['numero_jogo']} ({$partida['nome_fase']}): {$partida['nome_time1']} {$placarNovo} {$partida['nome_time2']}. Classificado: {$nomeVencedor}.",
            $detalhesLog
        );

        $db->commit();

        definirToast('sucesso', 'Placar Salvo!', "{$nomeVencedor} venceu e avançou para a próxima fase.");
        header("Location: ../torneio.php?id={$torneioId}");
        exit;

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        definirToast('erro', 'Erro ao Atualizar', $e->getMessage());
        header("Location: ../torneio.php?id={$torneioId}");
        exit;
    }
}

header('Location: ../index.php');
exit;
