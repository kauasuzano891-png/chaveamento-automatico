<?php
$paginaTitulo = "Painel Principal";
require_once __DIR__ . '/bd.php';
include __DIR__ . '/includes/header.php';

$db = getDB();

// Estatísticas gerais
$totalTorneios = $db->query("SELECT COUNT(*) FROM torneios")->fetchColumn() ?: 0;
$totalEquipes = $db->query("SELECT COUNT(*) FROM equipes")->fetchColumn() ?: 0;
$totalPartidas = $db->query("SELECT COUNT(*) FROM partidas WHERE status = 'finalizada'")->fetchColumn() ?: 0;
$torneiosFinalizados = $db->query("SELECT COUNT(*) FROM torneios WHERE status = 'finalizado'")->fetchColumn() ?: 0;

// Torneios em andamento
$stmtAndamento = $db->query("
    SELECT t.*, u.nome AS autor_nome,
           (SELECT COUNT(*) FROM partidas p WHERE p.torneio_id = t.id AND p.status = 'finalizada') as partidas_concluidas,
           (SELECT COUNT(*) FROM partidas p WHERE p.torneio_id = t.id) as partidas_total
    FROM torneios t
    LEFT JOIN usuarios u ON t.usuario_id = u.id
    WHERE t.status = 'em_andamento'
    ORDER BY t.criado_em DESC
    LIMIT 6
");
$torneiosEmAndamento = $stmtAndamento->fetchAll();

// Torneios recentemente finalizados com campeão
$stmtConcluidos = $db->query("
    SELECT t.*, c.nome AS nome_campeao, v.nome AS nome_vice
    FROM torneios t
    LEFT JOIN equipes c ON t.campeao_id = c.id
    LEFT JOIN equipes v ON t.vice_id = v.id
    WHERE t.status = 'finalizado'
    ORDER BY t.atualizado_em DESC
    LIMIT 4
");
$torneiosConcluidos = $stmtConcluidos->fetchAll();

// Últimos logs de auditoria
$stmtLogs = $db->query("
    SELECT * FROM logs_auditoria ORDER BY criado_em DESC LIMIT 5
");
$ultimosLogs = $stmtLogs->fetchAll();

$driverName = strtoupper($db->getAttribute(PDO::ATTR_DRIVER_NAME));
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
    <!-- Banner de Boas-Vindas & Hero -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-900 to-emerald-950/40 border border-slate-800 p-8 sm:p-10 mb-8 shadow-2xl">
        <div class="relative z-10 max-w-2xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold mb-4">
                <i class="fas fa-check-shield"></i> Chaveamento Esportivo Justo & Auditado
            </div>
            <h1 class="text-3xl sm:text-5xl font-black font-display tracking-tight text-white mb-4 leading-tight">
                Gere e gerencie chaves com <span class="bg-gradient-to-r from-emerald-400 to-teal-300 bg-clip-text text-transparent">precisão total</span>.
            </h1>
            <p class="text-sm sm:text-base text-slate-300 mb-6 leading-relaxed">
                Automatize confrontos, acompanhe o avanço das fases até a grande final, registre placares em tempo real e mantenha histórico seguro e livre de manipulações.
            </p>
            <div class="flex flex-wrap items-center gap-4">
                <a href="novo_torneio.php" class="px-6 py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm rounded-xl shadow-lg shadow-emerald-600/30 transition transform hover:-translate-y-0.5 flex items-center gap-2">
                    <i class="fas fa-plus-circle"></i> Criar Novo Torneio
                </a>
                <a href="historico.php" class="px-6 py-3.5 bg-slate-800 hover:bg-slate-700/80 text-slate-200 font-semibold text-sm rounded-xl border border-slate-700 transition flex items-center gap-2">
                    <i class="fas fa-trophy text-amber-400"></i> Ver Campeonatos
                </a>
            </div>
        </div>

        <!-- Marca d'água de troféu ao fundo -->
        <div class="absolute -right-8 -bottom-10 text-slate-800/20 text-[180px] select-none pointer-events-none hidden md:block">
            <i class="fas fa-sitemap"></i>
        </div>
    </div>

    <!-- Indicador de Status do Banco de Dados -->
    <div class="mb-8 flex items-center justify-between flex-wrap gap-2 text-xs text-slate-400 px-1">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
            <span>Banco de Dados: <strong class="text-white"><?php echo $driverName; ?> (PDO)</strong></span>
            <span class="text-slate-600">|</span>
            <span>Ambiente: <span class="text-emerald-400 font-medium">Operacional</span></span>
        </div>
        <div class="text-slate-400">
            Regulamento ETEC: <span class="text-slate-300 font-semibold">Critérios olímpicos de eliminação simples</span>
        </div>
    </div>

    <!-- Cards de Métricas e KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-10">
        <div class="bg-slate-900/80 border border-slate-800/80 rounded-2xl p-5 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Torneios Criados</span>
                <div class="w-9 h-9 rounded-lg bg-blue-500/10 text-blue-400 flex items-center justify-center text-sm border border-blue-500/20">
                    <i class="fas fa-layer-group"></i>
                </div>
            </div>
            <p class="text-3xl font-extrabold text-white"><?php echo number_format($totalTorneios); ?></p>
            <span class="text-[11px] text-slate-500 mt-1 block">Na plataforma</span>
        </div>

        <div class="bg-slate-900/80 border border-slate-800/80 rounded-2xl p-5 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Partidas Disputadas</span>
                <div class="w-9 h-9 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-sm border border-emerald-500/20">
                    <i class="fas fa-futbol"></i>
                </div>
            </div>
            <p class="text-3xl font-extrabold text-white"><?php echo number_format($totalPartidas); ?></p>
            <span class="text-[11px] text-emerald-400 mt-1 block">Placares auditados</span>
        </div>

        <div class="bg-slate-900/80 border border-slate-800/80 rounded-2xl p-5 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Equipes & Turmas</span>
                <div class="w-9 h-9 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-sm border border-indigo-500/20">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <p class="text-3xl font-extrabold text-white"><?php echo number_format($totalEquipes); ?></p>
            <span class="text-[11px] text-slate-500 mt-1 block">Participantes cadastrados</span>
        </div>

        <div class="bg-slate-900/80 border border-slate-800/80 rounded-2xl p-5 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Finalizados</span>
                <div class="w-9 h-9 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center text-sm border border-amber-500/20">
                    <i class="fas fa-crown"></i>
                </div>
            </div>
            <p class="text-3xl font-extrabold text-white"><?php echo number_format($torneiosFinalizados); ?></p>
            <span class="text-[11px] text-amber-400 mt-1 block">Campeões coroados</span>
        </div>
    </div>

    <!-- Seção de Torneios em Andamento -->
    <div class="mb-12">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></div>
                <h2 class="text-xl font-bold text-white tracking-tight">Torneios em Andamento</h2>
            </div>
            <a href="historico.php?status=em_andamento" class="text-xs text-emerald-400 hover:text-emerald-300 font-semibold flex items-center gap-1 transition">
                Ver todos <i class="fas fa-chevron-right text-[10px]"></i>
            </a>
        </div>

        <?php if (empty($torneiosEmAndamento)): ?>
            <div class="bg-slate-900/50 border border-dashed border-slate-800 rounded-2xl p-10 text-center">
                <div class="w-14 h-14 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 mx-auto mb-3 text-xl">
                    <i class="fas fa-sitemap"></i>
                </div>
                <h3 class="text-base font-bold text-white mb-1">Nenhum torneio em andamento</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto mb-5">
                    Crie um campeonato agora para gerar o chaveamento automático de partidas.
                </p>
                <a href="novo_torneio.php" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg transition">
                    <i class="fas fa-plus"></i> Iniciar Torneio
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($torneiosEmAndamento as $torneio): ?>
                    <?php
                        $pct = $torneio['partidas_total'] > 0 ? round(($torneio['partidas_concluidas'] / $torneio['partidas_total']) * 100) : 0;
                    ?>
                    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-6 shadow-xl hover:border-slate-700 transition flex flex-col justify-between group">
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 uppercase tracking-wide">
                                    <?php echo e($torneio['modalidade']); ?>
                                </span>
                                <span class="text-xs text-slate-500 font-mono">
                                    <i class="fas fa-users text-slate-400 mr-1"></i><?php echo $torneio['total_equipes']; ?> equipes
                                </span>
                            </div>

                            <h3 class="text-lg font-bold text-white mb-1 group-hover:text-emerald-400 transition truncate" title="<?php echo e($torneio['nome']); ?>">
                                <?php echo e($torneio['nome']); ?>
                            </h3>
                            <?php if (!empty($torneio['categoria'])): ?>
                                <p class="text-xs text-slate-400 mb-4 truncate"><?php echo e($torneio['categoria']); ?></p>
                            <?php else: ?>
                                <p class="text-xs text-slate-500 mb-4 italic">Mata-Mata Eliminatório</p>
                            <?php endif; ?>

                            <!-- Barra de Progresso de Partidas -->
                            <div class="mb-4">
                                <div class="flex justify-between text-[11px] font-medium text-slate-400 mb-1.5">
                                    <span>Progresso das Chaves</span>
                                    <span class="text-white font-mono"><?php echo $torneio['partidas_concluidas']; ?>/<?php echo $torneio['partidas_total']; ?> (<?php echo $pct; ?>%)</span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                                    <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-2 rounded-full transition-all duration-500" style="width: <?php echo $pct; ?>%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-800/80 flex items-center justify-between">
                            <span class="text-[11px] text-slate-500">
                                <i class="far fa-clock mr-1"></i><?php echo date('d/m/Y', strtotime($torneio['criado_em'])); ?>
                            </span>
                            <a href="torneio.php?id=<?php echo $torneio['id']; ?>" class="px-4 py-2 bg-slate-800 hover:bg-emerald-600 text-white text-xs font-bold rounded-lg transition flex items-center gap-1.5 shadow-sm">
                                Acessar Chaves <i class="fas fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Grade Dupla: Campeões Recentes & Auditoria em Tempo Real -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Campeões Recentes -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2.5">
                    <i class="fas fa-medal text-amber-400 text-lg"></i>
                    <h3 class="text-base font-bold text-white">Últimos Campeões</h3>
                </div>
                <a href="historico.php?status=finalizado" class="text-xs text-slate-400 hover:text-white transition">Histórico completo &rarr;</a>
            </div>

            <?php if (empty($torneiosConcluidos)): ?>
                <div class="py-8 text-center text-xs text-slate-500">
                    Nenhum campeonato finalizado ainda.
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($torneiosConcluidos as $tc): ?>
                        <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-white"><?php echo e($tc['nome']); ?></h4>
                                <span class="text-[11px] text-slate-400"><?php echo e($tc['modalidade']); ?> &bull; Finalizado</span>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs font-bold">
                                    <i class="fas fa-trophy text-[10px]"></i> <?php echo e($tc['nome_campeao'] ?: 'Campeão Indefinido'); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Auditoria e Logs Recentes (Garantia de Não Manipulação) -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2.5">
                    <i class="fas fa-shield-alt text-emerald-400 text-lg"></i>
                    <h3 class="text-base font-bold text-white">Transparência & Auditoria</h3>
                </div>
                <a href="auditoria.php" class="text-xs text-slate-400 hover:text-white transition">Ver todos os logs &rarr;</a>
            </div>

            <?php if (empty($ultimosLogs)): ?>
                <div class="py-8 text-center text-xs text-slate-500">
                    Nenhum registro de log no momento.
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($ultimosLogs as $l): ?>
                        <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 text-xs">
                            <div class="flex items-center justify-between text-slate-400 text-[10px] mb-1">
                                <span class="font-bold text-emerald-400"><?php echo e($l['usuario_nome']); ?></span>
                                <span><?php echo date('d/m/Y H:i:s', strtotime($l['criado_em'])); ?></span>
                            </div>
                            <p class="text-slate-200 line-clamp-2"><?php echo e($l['descricao']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
