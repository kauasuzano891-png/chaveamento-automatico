<?php
/**
 * Ações de Auditoria e Logs
 * Exportação de Relatórios de Integridade
 */
require_once __DIR__ . '/../bd.php';

exigirLogin();

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

if ($acao === 'exportar_csv') {
    try {
        $db = getDB();
        $torneioId = !empty($_GET['torneio_id']) ? intval($_GET['torneio_id']) : null;

        if ($torneioId) {
            $stmt = $db->prepare("SELECT * FROM logs_auditoria WHERE torneio_id = ? ORDER BY criado_em DESC");
            $stmt->execute([$torneioId]);
            $nomeArquivo = "auditoria_torneio_{$torneioId}_" . date('Ymd_His') . ".csv";
        } else {
            $stmt = $db->query("SELECT * FROM logs_auditoria ORDER BY criado_em DESC");
            $nomeArquivo = "auditoria_geral_" . date('Ymd_His') . ".csv";
        }

        $logs = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');

        $saida = fopen('php://output', 'w');
        // BOM UTF-8 para o Excel abrir com acentuação correta
        fprintf($saida, chr(0xEF).chr(0xBB).chr(0xBF));

        // Cabeçalhos CSV
        fputcsv($saida, ['ID', 'Data/Hora', 'Torneio ID', 'Usuário', 'Ação', 'Descrição', 'Detalhes (JSON)', 'IP'], ';');

        foreach ($logs as $l) {
            fputcsv($saida, [
                $l['id'],
                $l['criado_em'],
                $l['torneio_id'] ?: 'Geral',
                $l['usuario_nome'],
                $l['acao'],
                $l['descricao'],
                $l['detalhes'],
                $l['ip']
            ], ';');
        }

        fclose($saida);
        registrarLog($torneioId, 'EXPORTACAO_LOGS', "Relatório de auditoria exportado via CSV.");
        exit;

    } catch (Exception $e) {
        definirToast('erro', 'Erro ao Exportar', $e->getMessage());
        header('Location: ../auditoria.php');
        exit;
    }
}

header('Location: ../auditoria.php');
exit;
