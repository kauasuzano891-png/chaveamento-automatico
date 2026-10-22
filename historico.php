<?php
/**
 * Página de Histórico de Campeonatos e Torneios
 * Filtros por Modalidade, Status e Busca Textual
 */
$paginaTitulo = "Histórico de Torneios";
require_once __DIR__ . '/bd.php';
include __DIR__ . '/includes/header.php';

$db = getDB();

// Filtros da busca
$busca = trim($_GET['busca'] ?? '');
$filtroModalidade = trim($_GET['modalidade'] ?? '');
$filtroStatus = trim($_GET['status'] ?? '');

$sql = "
    SELECT t.*, u.nome AS autor_nome,
           c.nome AS nome_campeao,
           (SELECT COUNT(*) FROM partidas p WHERE p.torneio_id = t.id AND p.status = 'finalizada') as partidas_concluidas,
           (SELECT COUNT(*) FROM partidas p WHERE p.torneio_id = t.id) as partidas_total
    FROM torneios t
    LEFT JOIN usuarios u ON t.usuario_id = u.id
    LEFT JOIN equipes c ON t.campeao_id = c.id
    WHERE 1=1
";
$params = [];

if ($busca !== '') {
    $sql .= " AND (t.nome LIKE ? OR t.categoria LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

if ($filtroModalidade !== '') {
    $sql .= " AND t.modalidade = ?";
    $params[] = $filtroModalidade;
}

if ($filtroStatus !== '') {
    $sql .= " AND t.status = ?";
    $params[] = $filtroStatus;
}

$sql .= " ORDER BY t.criado_em DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$torneios = $stmt->fetchAll();

// Modalidades distintas para o select
$modalidadesDisponiveis = $db->query("SELECT DISTINCT modalidade FROM torneios ORDER BY modalidade ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
    <!-- Topo da Página -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold font-display text-white tracking-tight flex items-center gap-3">
                <i class="fas fa-trophy text-amber-400"></i> Histórico de Campeonatos
            </h1>
            <p class="text-sm text-slate-400 mt-1">
                Explore todos os torneios realizados, campeões registrados e chaves arquivadas.
            </p>
        </div>
        <a href="novo_torneio.php" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl transition flex items-center gap-2 self-start md:self-auto shadow-lg shadow-emerald-600/20">
            <i class="fas fa-plus"></i> Novo Torneio
        </a>
    </div>

    <!-- Barra de Filtros e Busca -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 sm:p-6 mb-8 shadow-xl">
        <form method="GET" action="historico.php" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <!-- Busca por nome -->
            <div class="sm:col-span-2">
                <label for="busca" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                    Buscar por Nome ou Categoria
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 text-sm">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" name="busca" id="busca" value="<?php echo e($busca); ?>" placeholder="Ex: Interclasses, Futsal Manhã..."
                           class="w-full bg-slate-950/80 border border-slate-800 rounded-xl pl-10 pr-4 py-2.5 text-sm text-white placeholder-slate-600 focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
            </div>

            <!-- Modalidade -->
            <div>
                <label for="modalidade" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                    Modalidade
                </label>
                <select name="modalidade" id="modalidade"
                        class="w-full bg-slate-950/80 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-white focus:ring-2 focus:ring-emerald-500 outline-none">
                    <option value="">Todas as Modalidades</option>
                    <?php foreach ($modalidadesDisponiveis as $mod): ?>
                        <option value="<?php echo e($mod); ?>" <?php echo $filtroModalidade === $mod ? 'selected' : ''; ?>>
                            <?php echo e($mod); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status -->
            <div class="flex gap-2">
                <div class="flex-1">
                    <label for="status" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                        Status
                    </label>
                    <select name="status" id="status"
                            class="w-full bg-slate-950/80 border border-slate-800 rounded-xl px-3 py-2.5 text-sm text-white focus:ring-2 focus:ring-emerald-500 outline-none">
                        <option value="">Todos</option>
                        <option value="em_andamento" <?php echo $filtroStatus === 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                        <option value="finalizado" <?php echo $filtroStatus === 'finalizado' ? 'selected' : ''; ?>>Finalizados</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl transition flex items-center justify-center self-end border border-slate-700">
                    <i class="fas fa-filter"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Lista de Torneios em Cards -->
    <?php if (empty($torneios)): ?>
        <div class="bg-slate-900/40 border border-dashed border-slate-800 rounded-3xl p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 mx-auto mb-4 text-2xl">
                <i class="fas fa-search"></i>
            </div>
            <h3 class="text-base font-bold text-white mb-1">Nenhum campeonato encontrado</h3>
            <p class="text-xs text-slate-400 max-w-md mx-auto mb-6">
                Não foram localizados torneios com os filtros informados. Tente alterar os termos ou crie uma nova chave esportiva.
            </p>
            <a href="historico.php" class="px-4 py-2 bg-slate-800 text-slate-300 hover:text-white text-xs font-semibold rounded-lg transition mr-2">
                Limpar Filtros
            </a>
            <a href="novo_torneio.php" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg transition">
                Criar Torneio
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($torneios as $t): ?>
                <?php
                    $isFinalizado = ($t['status'] === 'finalizado');
                    $pct = $t['partidas_total'] > 0 ? round(($t['partidas_concluidas'] / $t['partidas_total']) * 100) : 0;
                ?>
                <div class="bg-slate-900 border <?php echo $isFinalizado ? 'border-amber-500/20 bg-slate-900/90' : 'border-slate-800/80'; ?> rounded-2xl p-6 shadow-xl flex flex-col justify-between hover:border-slate-700 transition group">
                    <div>
                        <!-- Badges Superiores -->
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-slate-800 text-slate-300 uppercase tracking-wide">
                                <?php echo e($t['modalidade']); ?>
                            </span>

                            <?php if ($isFinalizado): ?>
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-amber-500/10 text-amber-300 border border-amber-500/20 flex items-center gap-1">
                                    <i class="fas fa-crown text-[10px]"></i> Finalizado
                                </span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Em Andamento
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Título e Categoria -->
                        <h3 class="text-lg font-bold text-white mb-1 group-hover:text-emerald-400 transition truncate" title="<?php echo e($t['nome']); ?>">
                            <?php echo e($t['nome']); ?>
                        </h3>
                        <p class="text-xs text-slate-400 mb-4 truncate">
                            <?php echo e($t['categoria'] ?: 'Mata-Mata Eliminatório'); ?>
                        </p>

                        <!-- Campeão em Destaque se finalizado -->
                        <?php if ($isFinalizado && !empty($t['nome_campeao'])): ?>
                            <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center gap-2.5 mb-4">
                                <span class="text-lg">🥇</span>
                                <div class="truncate">
                                    <span class="block text-[10px] text-amber-400 uppercase font-bold tracking-wider">Campeão</span>
                                    <span class="block text-xs font-extrabold text-white truncate"><?php echo e($t['nome_campeao']); ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Progresso de Partidas -->
                            <div class="mb-4">
                                <div class="flex justify-between text-[11px] font-medium text-slate-400 mb-1.5">
                                    <span>Partidas Realizadas</span>
                                    <span class="text-white font-mono"><?php echo $t['partidas_concluidas']; ?>/<?php echo $t['partidas_total']; ?></span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-emerald-500 h-1.5 rounded-full" style="width: <?php echo $pct; ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Rodapé do Card -->
                    <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
                        <div class="text-[11px] text-slate-500">
                            <span><i class="fas fa-users mr-1"></i><?php echo $t['total_equipes']; ?> times</span>
                            <span class="mx-1">&bull;</span>
                            <span><?php echo date('d/m/Y', strtotime($t['criado_em'])); ?></span>
                        </div>
                        <a href="torneio.php?id=<?php echo $t['id']; ?>" class="px-3.5 py-1.5 bg-slate-800 hover:bg-emerald-600 text-white text-xs font-bold rounded-lg transition flex items-center gap-1 shadow-sm">
                            Ver Chaves <i class="fas fa-chevron-right text-[9px]"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
