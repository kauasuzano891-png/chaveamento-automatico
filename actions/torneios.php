<?php
/**
 * Ações de Gerenciamento e Chaveamento de Torneios
 * Algoritmo Automático de Chaveamento Eliminatório com Suporte a Potências de 2 e Byes
 */
require_once __DIR__ . '/../bd.php';

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

// Operações de escrita exigem autenticação
if (in_array($acao, ['criar', 'excluir', 'finalizar', 'reiniciar'])) {
    exigirLogin();
}

/**
 * 1. CRIAR NOVO TORNEIO E GERAR CHAVEAMENTO AUTOMÁTICO
 */
if ($acao === 'criar') {
    $nome = trim($_POST['nome'] ?? '');
    $modalidade = trim($_POST['modalidade'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $formato = trim($_POST['formato'] ?? 'mata-mata');
    $criterioDesempate = trim($_POST['criterio_desempate'] ?? 'penaltis');
    $temTerceiroLugar = !empty($_POST['tem_terceiro_lugar']) ? 1 : 0;
    $sortearEquipes = !empty($_POST['sortear_equipes']) ? 1 : 0;

    // Processamento da lista de equipes (podem vir de textarea ou array)
    $equipesRaw = $_POST['equipes_texto'] ?? '';
    $linhas = preg_split('/[\r\n]+/', $equipesRaw);
    $equipesNomes = [];
    foreach ($linhas as $linha) {
        $nomeLimpo = trim($linha);
        if ($nomeLimpo !== '') {
            $equipesNomes[] = $nomeLimpo;
        }
    }

    // Validações
    if (empty($nome) || empty($modalidade)) {
        definirToast('erro', 'Campos Obrigatórios', 'Informe o nome do torneio e a modalidade esportiva.');
        header('Location: ../novo_torneio.php');
        exit;
    }

    $totalEquipes = count($equipesNomes);
    if ($totalEquipes < 2) {
        definirToast('erro', 'Equipes Insuficientes', 'Um torneio precisa de no mínimo 2 equipes para gerar o chaveamento.');
        header('Location: ../novo_torneio.php');
        exit;
    }

    if ($totalEquipes > 64) {
        definirToast('erro', 'Limite Excedido', 'O número máximo permitido por chave é de 64 equipes.');
        header('Location: ../novo_torneio.php');
        exit;
    }

    // Validação de nomes duplicados
    if (count($equipesNomes) !== count(array_unique(array_map('mb_strtolower', $equipesNomes)))) {
        definirToast('aviso', 'Equipes Duplicadas', 'Existem equipes com o mesmo nome na lista. Verifique e corrija.');
        header('Location: ../novo_torneio.php');
        exit;
    }

    // Sorteio aleatório se solicitado
    if ($sortearEquipes) {
        shuffle($equipesNomes);
    }

    try {
        $db = getDB();
        $db->beginTransaction();

        $usuario = obterUsuarioLogado();
        $usuarioId = $usuario ? $usuario['id'] : null;

        // Insere o torneio
        $stmtTorneio = $db->prepare("
            INSERT INTO torneios (usuario_id, nome, modalidade, categoria, formato, total_equipes, tem_terceiro_lugar, criterio_desempate, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'em_andamento')
        ");
        $stmtTorneio->execute([
            $usuarioId, $nome, $modalidade, $categoria, $formato, $totalEquipes, $temTerceiroLugar, $criterioDesempate
        ]);
        $torneioId = $db->lastInsertId();

        // Insere as equipes no banco
        $equipesIds = [];
        $stmtEquipe = $db->prepare("INSERT INTO equipes (torneio_id, nome, posicao_inicial) VALUES (?, ?, ?)");
        foreach ($equipesNomes as $idx => $nomeEq) {
            $stmtEquipe->execute([$torneioId, $nomeEq, $idx + 1]);
            $equipesIds[] = $db->lastInsertId();
        }

        // ========================================================
        // ALGORITMO DE GERAÇÃO DO CHAVEAMENTO (MATA-MATA)
        // ========================================================
        // Calcula a menor potência de 2 maior ou igual ao número de equipes
        $tamanhoChave = 2;
        $totalFases = 1;
        while ($tamanhoChave < $totalEquipes) {
            $tamanhoChave *= 2;
            $totalFases++;
        }

        // Quantidade de equipes isentas (BYEs) na primeira rodada
        $totalByes = $tamanhoChave - $totalEquipes;

        // Nomes das fases conforme distância da final
        $obterNomeFase = function(int $faseNum, int $totalFases) {
            $distancia = $totalFases - $faseNum;
            switch ($distancia) {
                case 0: return 'Grande Final';
                case 1: return 'Semifinais';
                case 2: return 'Quartas de Final';
                case 3: return 'Oitavas de Final';
                case 4: return '16-avos de Final';
                default: return 'Fase Preliminar (Rodada ' . $faseNum . ')';
            }
        };

        // 1º Passo: Criar todas as estruturas de partidas de cada fase
        $partidasFase = []; // [fase][indice] => id
        $numeroJogoGeral = 1;

        $stmtCriaPartida = $db->prepare("
            INSERT INTO partidas (torneio_id, fase, nome_fase, numero_jogo, status)
            VALUES (?, ?, ?, ?, 'pendente')
        ");

        for ($f = 1; $f <= $totalFases; $f++) {
            $jogosNestaFase = $tamanhoChave / pow(2, $f);
            $nomeFase = $obterNomeFase($f, $totalFases);
            $partidasFase[$f] = [];

            for ($j = 0; $j < $jogosNestaFase; $j++) {
                $stmtCriaPartida->execute([$torneioId, $f, $nomeFase, $numeroJogoGeral]);
                $partidasFase[$f][$j] = $db->lastInsertId();
                $numeroJogoGeral++;
            }
        }

        // Se houver disputa de 3º lugar, cria a partida especial (fase 99)
        $partidaTerceiroId = null;
        if ($temTerceiroLugar && $totalFases >= 2) {
            $stmtCriaPartida->execute([$torneioId, 99, 'Disputa de 3º Lugar', $numeroJogoGeral]);
            $partidaTerceiroId = $db->lastInsertId();
            $numeroJogoGeral++;
        }

        // 2º Passo: Interligar as partidas entre as fases (definir proxima_partida_id e proxima_slot)
        $stmtLigaPartida = $db->prepare("
            UPDATE partidas SET proxima_partida_id = ?, proxima_slot = ? WHERE id = ?
        ");

        for ($f = 1; $f < $totalFases; $f++) {
            $jogosNestaFase = count($partidasFase[$f]);
            for ($j = 0; $j < $jogosNestaFase; $j++) {
                $partidaAtualId = $partidasFase[$f][$j];
                $indiceProximoJogo = intdiv($j, 2);
                $proximaPartidaId = $partidasFase[$f + 1][$indiceProximoJogo];
                $proximoSlot = ($j % 2 === 0) ? 1 : 2;

                $stmtLigaPartida->execute([$proximaPartidaId, $proximoSlot, $partidaAtualId]);
            }
        }

        // 3º Passo: Distribuir as equipes na Rodada 1 e aplicar BYEs (isenções)
        // Distribuição balanceada de Byes: top seeds recebem folga
        $jogosRodada1 = count($partidasFase[1]);
        $slots = []; // array de $tamanhoChave posições com id da equipe ou null (bye)

        // Preenche slots: equipes e BYEs
        $ptrEq = 0;
        for ($s = 0; $s < $tamanhoChave; $s += 2) {
            // Time 1 da partida
            $slots[$s] = $equipesIds[$ptrEq++] ?? null;
            // Time 2 da partida: se ainda temos BYEs para alocar nesta rodada, deixa null
            $byesRestantes = $tamanhoChave - count($slots);
            if ($totalByes > 0 && ($s / 2) < $totalByes) {
                $slots[$s + 1] = null; // BYE
            } else {
                $slots[$s + 1] = $equipesIds[$ptrEq++] ?? null;
            }
        }

        $stmtAtualizaRodada1 = $db->prepare("
            UPDATE partidas 
            SET equipe1_id = ?, equipe2_id = ?, status = ?, vencedor_id = ?, placar1 = ?, placar2 = ?
            WHERE id = ?
        ");

        $stmtAvancaProxima = $db->prepare("
            UPDATE partidas SET equipe1_id = CASE WHEN ? = 1 THEN ? ELSE equipe1_id END,
                                equipe2_id = CASE WHEN ? = 2 THEN ? ELSE equipe2_id END
            WHERE id = ?
        ");

        for ($j = 0; $j < $jogosRodada1; $j++) {
            $partidaId = $partidasFase[1][$j];
            $t1 = $slots[$j * 2] ?? null;
            $t2 = $slots[$j * 2 + 1] ?? null;

            if ($t1 && !$t2) {
                // BYE automático: Time 1 avança direto sem jogar
                $stmtAtualizaRodada1->execute([$t1, null, 'bye', $t1, 1, 0, $partidaId]);
                
                // Avança o time diretamente para o próximo jogo na rodada 2
                if ($totalFases > 1) {
                    $proxJogoIdx = intdiv($j, 2);
                    $proxPartidaId = $partidasFase[2][$proxJogoIdx];
                    $slotNum = ($j % 2 === 0) ? 1 : 2;
                    $stmtAvancaProxima->execute([$slotNum, $t1, $slotNum, $t1, $proxPartidaId]);
                }
            } else {
                // Jogo normal
                $stmtAtualizaRodada1->execute([$t1, $t2, 'pendente', null, null, null, $partidaId]);
            }
        }

        // Registra log de auditoria
        registrarLog($torneioId, 'CRIACAO_TORNEIO', "Torneio '{$nome}' ({$modalidade}) criado com {$totalEquipes} equipes e {$totalFases} fases.");

        $db->commit();

        definirToast('sucesso', 'Chaveamento Gerado!', "Torneio '{$nome}' criado com sucesso e chaves definidas automaticamente.");
        header("Location: ../torneio.php?id={$torneioId}");
        exit;

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        definirToast('erro', 'Falha ao Criar Torneio', 'Erro: ' . $e->getMessage());
        header('Location: ../novo_torneio.php');
        exit;
    }
}

/**
 * 2. FINALIZAR TORNEIO (Após a final ter sido disputada)
 */
if ($acao === 'finalizar') {
    $torneioId = intval($_POST['torneio_id'] ?? 0);
    if ($torneioId <= 0) {
        definirToast('erro', 'Identificador Inválido', 'Torneio não encontrado.');
        header('Location: ../index.php');
        exit;
    }

    try {
        $db = getDB();

        // Busca a partida final do torneio
        $stmtFinal = $db->prepare("
            SELECT * FROM partidas 
            WHERE torneio_id = ? AND fase = (SELECT MAX(fase) FROM partidas WHERE torneio_id = ? AND fase < 90)
            LIMIT 1
        ");
        $stmtFinal->execute([$torneioId, $torneioId]);
        $final = $stmtFinal->fetch();

        if (!$final || empty($final['vencedor_id'])) {
            definirToast('aviso', 'Final Incompleta', 'Não é possível finalizar o torneio antes de computar o resultado da Grande Final!');
            header("Location: ../torneio.php?id={$torneioId}");
            exit;
        }

        $campeaoId = $final['vencedor_id'];
        $viceId = ($final['vencedor_id'] == $final['equipe1_id']) ? $final['equipe2_id'] : $final['equipe1_id'];

        // Busca o 3º lugar se existir disputa
        $terceiroId = null;
        $stmtTerceiro = $db->prepare("SELECT vencedor_id FROM partidas WHERE torneio_id = ? AND fase = 99 LIMIT 1");
        $stmtTerceiro->execute([$torneioId]);
        $terceiro = $stmtTerceiro->fetch();
        if ($terceiro && !empty($terceiro['vencedor_id'])) {
            $terceiroId = $terceiro['vencedor_id'];
        }

        $stmtUp = $db->prepare("
            UPDATE torneios 
            SET status = 'finalizado', campeao_id = ?, vice_id = ?, terceiro_id = ?
            WHERE id = ?
        ");
        $stmtUp->execute([$campeaoId, $viceId, $terceiroId, $torneioId]);

        // Busca nomes para o log
        $stmtNomes = $db->prepare("SELECT id, nome FROM equipes WHERE id IN (?, ?, ?)");
        $stmtNomes->execute([$campeaoId, $viceId, $terceiroId ?: 0]);
        $nomes = $stmtNomes->fetchAll(PDO::FETCH_KEY_PAIR);

        $nomeCampeao = $nomes[$campeaoId] ?? 'Campeão';
        $nomeVice = $nomes[$viceId] ?? 'Vice';

        registrarLog(
            $torneioId,
            'FINALIZACAO_TORNEIO',
            "Torneio finalizado com sucesso! Campeão: {$nomeCampeao}, Vice: {$nomeVice}."
        );

        definirToast('sucesso', 'Torneio Finalizado!', "Parabéns à equipe {$nomeCampeao} pelo título!");
        header("Location: ../torneio.php?id={$torneioId}");
        exit;

    } catch (Exception $e) {
        definirToast('erro', 'Erro ao Finalizar', $e->getMessage());
        header("Location: ../torneio.php?id={$torneioId}");
        exit;
    }
}

/**
 * 3. REINICIAR TORNEIO (Mantém equipes e chaveamento, reseta placares e vencedores)
 */
if ($acao === 'reiniciar') {
    $torneioId = intval($_POST['torneio_id'] ?? 0);
    if ($torneioId <= 0) {
        header('Location: ../index.php');
        exit;
    }

    try {
        $db = getDB();
        $db->beginTransaction();

        // Reseta torneio
        $db->prepare("UPDATE torneios SET status = 'em_andamento', campeao_id = NULL, vice_id = NULL, terceiro_id = NULL WHERE id = ?")
           ->execute([$torneioId]);

        // Limpa fases posteriores à fase 1
        $db->prepare("UPDATE partidas SET placar1 = NULL, placar2 = NULL, penaltis1 = NULL, penaltis2 = NULL, vencedor_id = NULL, perdedor_id = NULL, equipe1_id = NULL, equipe2_id = NULL, status = 'pendente', observacoes = NULL WHERE torneio_id = ? AND fase > 1")
           ->execute([$torneioId]);

        // Na fase 1, limpa placares de jogos normais mantendo byes
        $db->prepare("UPDATE partidas SET placar1 = NULL, placar2 = NULL, penaltis1 = NULL, penaltis2 = NULL, vencedor_id = NULL, perdedor_id = NULL, status = 'pendente', observacoes = NULL WHERE torneio_id = ? AND fase = 1 AND status != 'bye'")
           ->execute([$torneioId]);

        // Repassa os vencedores de BYE para a fase 2
        $stmtByes = $db->prepare("SELECT id, equipe1_id, proxima_partida_id, proxima_slot FROM partidas WHERE torneio_id = ? AND fase = 1 AND status = 'bye'");
        $stmtByes->execute([$torneioId]);
        $byes = $stmtByes->fetchAll();

        $stmtAvanca = $db->prepare("
            UPDATE partidas SET equipe1_id = CASE WHEN ? = 1 THEN ? ELSE equipe1_id END,
                                equipe2_id = CASE WHEN ? = 2 THEN ? ELSE equipe2_id END
            WHERE id = ?
        ");
        foreach ($byes as $b) {
            $stmtAvanca->execute([$b['proxima_slot'], $b['equipe1_id'], $b['proxima_slot'], $b['equipe1_id'], $b['proxima_partida_id']]);
        }

        registrarLog($torneioId, 'REINICIO_TORNEIO', 'Todos os placares do torneio foram resetados pelo organizador.');
        $db->commit();

        definirToast('info', 'Torneio Reiniciado', 'Todos os placares foram zerados mantendo a chave original intacta.');
        header("Location: ../torneio.php?id={$torneioId}");
        exit;

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        definirToast('erro', 'Erro ao Reiniciar', $e->getMessage());
        header("Location: ../torneio.php?id={$torneioId}");
        exit;
    }
}

/**
 * 4. EXCLUIR TORNEIO
 */
if ($acao === 'excluir') {
    $torneioId = intval($_POST['torneio_id'] ?? 0);
    if ($torneioId <= 0) {
        header('Location: ../index.php');
        exit;
    }

    try {
        $db = getDB();
        $stmtNome = $db->prepare("SELECT nome FROM torneios WHERE id = ?");
        $stmtNome->execute([$torneioId]);
        $nomeTorneio = $stmtNome->fetchColumn() ?: "Torneio #{$torneioId}";

        $stmt = $db->prepare("DELETE FROM torneios WHERE id = ?");
        $stmt->execute([$torneioId]);

        registrarLog(null, 'EXCLUSAO_TORNEIO', "Torneio '{$nomeTorneio}' (ID: {$torneioId}) foi excluído definitivamente.");

        definirToast('sucesso', 'Torneio Excluído', "O torneio '{$nomeTorneio}' foi removido com sucesso.");
        header('Location: ../index.php');
        exit;

    } catch (Exception $e) {
        definirToast('erro', 'Erro ao Excluir', $e->getMessage());
        header("Location: ../torneio.php?id={$torneioId}");
        exit;
    }
}

header('Location: ../index.php');
exit;
