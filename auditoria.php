<?php
/**
 * Página de Auditoria e Logs de Alterações
 * Rastreabilidade completa para garantir campeonato justo e sem manipulação
 */
$paginaTitulo = "Logs de Auditoria";
require_once __DIR__ . '/bd.php';
include __DIR__ . '/includes/header.php';

$db = getDB();

$filtroAcao = trim($_GET['acao_filtro'] ?? '');
$busca = trim($_GET['busca'] ?? '');
$filtroTorneio = !empty($_GET['torneio_id']) ? intval($_GET['torneio_id']) : null;

$sql = "
    SELECT l.*, t.nome AS nome_torneio
    FROM logs_auditoria l
    LEFT JOIN torneios t ON l.torneio_id = t.id
    WHERE 1=1
";
$params = [];

if ($filtroAcao !== '') {
    $sql .= " AND l.acao = ?";
    $params[] = $filtroAcao;
}

if ($filtroTorneio) {
    $sql .= " AND l.torneio_id = ?";
    $params[] = $filtroTorneio;
}

if ($busca !== '') {
    $sql .= " AND (l.descricao LIKE ? OR l.usuario_nome LIKE ? OR l.detalhes LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

$sql .= " ORDER BY l.criado_em DESC LIMIT 200";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Lista de ações distintas para o filtro
$acoesDisponiveis = $db->query("SELECT DISTINCT acao FROM logs_auditoria ORDER BY acao ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
    <!-- Cabeçalho da Auditoria -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold mb-2">
                <i class="fas fa-shield-alt"></i> Módulo de Transparência & Integridade
            </div>
            <h1 class="text-3xl font-extrabold font-display text-white tracking-tight flex items-center gap-3">
                Logs de Auditoria do Sistema
            </h1>
            <p class="text-sm text-slate-400 mt-1 max-w-2xl">
                Registro imutável de todas as ações de organizadores e usuários. Cada alteração de placar, sorteio ou finalização é catalogada com data, autor e IP para auditorias de comissões escolares.
            </p>
        </div>

        <!-- Botão Exportar CSV -->
        <div class="flex items-center gap-3 self-start md:self-auto">
            <a href="actions/logs.php?acao=exportar_csv<?php echo $filtroTorneio ? '&torneio_id=' . $filtroTorneio : ''; ?>"
               class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl border border-slate-700 transition flex items-center gap-2 shadow-sm">
                <i class="fas fa-file-csv text-emerald-400"></i> Exportar Relatório (CSV)
            </a>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 mb-8 shadow-xl">
        <form method="GET" action="auditoria.php" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <!-- Busca por texto -->
            <div class="sm:col-span-2">
                <label for="busca" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                    Buscar por Descrição ou Usuário
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 text-sm">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" name="busca" id="busca" value="<?php echo e($busca); ?>" placeholder="Ex: Placar, Final, Nome do Usuário..."
                           class="w-full bg-slate-950/80 border border-slate-800 rounded-xl pl-10 pr-4 py-2.5 text-sm text-white placeholder-slate-600 focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
            </div>

            <!-- Tipo de Ação -->
            <div>
                <label for="acao_filtro" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                    Evento / Ação
                </label>
                <select name="acao_filtro" id="acao_filtro"
                        class="w-full bg-slate-950/80 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-white focus:ring-2 focus:ring-emerald-500 outline-none">
                    <option value="">Todas as Ações</option>
                    <?php foreach ($acoesDisponiveis as $ac): ?>
                        <option value="<?php echo e($ac); ?>" <?php echo $filtroAcao === $ac ? 'selected' : ''; ?>>
                            <?php echo e($ac); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Botões de Ação -->
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl transition flex items-center justify-center gap-1.5 shadow-md shadow-emerald-600/20">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a href="auditoria.php" class="px-3.5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white rounded-xl transition flex items-center justify-center border border-slate-700" title="Limpar Filtros">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Tabela de Logs de Auditoria -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <?php if (empty($logs)): ?>
            <div class="p-12 text-center text-slate-500 text-xs">
                <div class="w-12 h-12 rounded-full bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-3 text-lg">
                    <i class="fas fa-history"></i>
                </div>
                Nenhum registro de auditoria corresponde aos filtros selecionados.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950 text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="py-3.5 px-4">Data / Hora</th>
                            <th class="py-3.5 px-4">Usuário</th>
                            <th class="py-3.5 px-4">Evento</th>
                            <th class="py-3.5 px-4">Descrição da Operação</th>
                            <th class="py-3.5 px-4">Torneio</th>
                            <th class="py-3.5 px-4">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-sans">
                        <?php foreach ($logs as $l): ?>
                            <?php
                                // Cores de badge por tipo de ação
                                $badgeCores = [
                                    'ATUALIZACAO_PLACAR' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                    'CRIACAO_TORNEIO'    => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                    'FINALIZACAO_TORNEIO'=> 'bg-amber-500/10 text-amber-300 border-amber-500/20',
                                    'REINICIO_TORNEIO'   => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                    'EXCLUSAO_TORNEIO'   => 'bg-red-500/20 text-red-400 border-red-500/30',
                                    'LOGIN'              => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
                                    'CADASTRO_USUARIO'   => 'bg-teal-500/10 text-teal-400 border-teal-500/20'
                                ];
                                $corBadge = $badgeCores[$l['acao']] ?? 'bg-slate-800 text-slate-400 border-slate-700';
                            ?>
                            <tr class="hover:bg-slate-850/50 transition">
                                <!-- Data/Hora -->
                                <td class="py-3.5 px-4 whitespace-nowrap font-mono text-[11px] text-slate-400">
                                    <?php echo date('d/m/Y H:i:s', strtotime($l['criado_em'])); ?>
                                </td>

                                <!-- Usuário -->
                                <td class="py-3.5 px-4 whitespace-nowrap font-bold text-white">
                                    <div class="flex items-center gap-1.5">
                                        <i class="far fa-user text-slate-500 text-[10px]"></i>
                                        <?php echo e($l['usuario_nome']); ?>
                                    </div>
                                </td>

                                <!-- Evento -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border <?php echo $corBadge; ?> font-mono">
                                        <?php echo e($l['acao']); ?>
                                    </span>
                                </td>

                                <!-- Descrição -->
                                <td class="py-3.5 px-4 text-slate-200">
                                    <p class="leading-relaxed"><?php echo e($l['descricao']); ?></p>
                                    <?php if (!empty($l['detalhes'])): ?>
                                        <details class="mt-1">
                                            <summary class="text-[10px] text-emerald-400 hover:underline cursor-pointer select-none">
                                                Ver detalhes técnicos (JSON)
                                            </summary>
                                            <pre class="mt-1 p-2 rounded bg-slate-950 font-mono text-[10px] text-slate-400 overflow-x-auto whitespace-pre-wrap"><?php echo e($l['detalhes']); ?></pre>
                                        </details>
                                    <?php endif; ?>
                                </td>

                                <!-- Torneio -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <?php if (!empty($l['torneio_id'])): ?>
                                        <a href="torneio.php?id=<?php echo $l['torneio_id']; ?>" class="text-emerald-400 hover:underline font-semibold" title="Abrir chave do torneio">
                                            <?php echo e($l['nome_torneio'] ?: "Torneio #{$l['torneio_id']}"); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-slate-600">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- IP -->
                                <td class="py-3.5 px-4 whitespace-nowrap font-mono text-[10px] text-slate-500">
                                    <?php echo e($l['ip'] ?: '127.0.0.1'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
