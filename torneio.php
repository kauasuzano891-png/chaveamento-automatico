<?php
/**
 * Visualização Interativa do Chaveamento de Torneio (Estilo BracketMaker)
 * Exibição das Fases, Conectores Visuais, Súmulas e Atualização de Placares
 */
require_once __DIR__ . '/bd.php';

$torneioId = intval($_GET['id'] ?? 0);
if ($torneioId <= 0) {
    definirToast('erro', 'Torneio Não Encontrado', 'O identificador do torneio é inválido.');
    header('Location: index.php');
    exit;
}

$db = getDB();

// Busca dados do torneio
$stmtTorneio = $db->prepare("
    SELECT t.*, u.nome AS autor_nome,
           c.nome AS nome_campeao, v.nome AS nome_vice, ter.nome AS nome_terceiro
    FROM torneios t
    LEFT JOIN usuarios u ON t.usuario_id = u.id
    LEFT JOIN equipes c ON t.campeao_id = c.id
    LEFT JOIN equipes v ON t.vice_id = v.id
    LEFT JOIN equipes ter ON t.terceiro_id = ter.id
    WHERE t.id = ?
");
$stmtTorneio->execute([$torneioId]);
$torneio = $stmtTorneio->fetch();

if (!$torneio) {
    definirToast('erro', 'Torneio Inexistente', 'O campeonato solicitado não existe ou foi removido.');
    header('Location: index.php');
    exit;
}

// Busca todas as partidas do torneio com dados das equipes
$stmtPartidas = $db->prepare("
    SELECT p.*,
           e1.nome AS nome_time1,
           e2.nome AS nome_time2,
           v.nome AS nome_vencedor
    FROM partidas p
    LEFT JOIN equipes e1 ON p.equipe1_id = e1.id
    LEFT JOIN equipes e2 ON p.equipe2_id = e2.id
    LEFT JOIN equipes v ON p.vencedor_id = v.id
    WHERE p.torneio_id = ?
    ORDER BY p.fase ASC, p.numero_jogo ASC
");
$stmtPartidas->execute([$torneioId]);
$todasPartidas = $stmtPartidas->fetchAll();

// Agrupa partidas por fase
$fases = [];
$partidaTerceiroLugar = null;

foreach ($todasPartidas as $p) {
    if ($p['fase'] == 99) {
        $partidaTerceiroLugar = $p;
    } else {
        $fases[$p['fase']][] = $p;
    }
}
ksort($fases);

$paginaTitulo = $torneio['nome'];
include __DIR__ . '/includes/header.php';

// Verifica se a final já tem vencedor para permitir finalização do torneio
$finalPartida = null;
if (!empty($fases)) {
    $ultimaFase = end($fases);
    $finalPartida = $ultimaFase[0] ?? null;
}
$podeFinalizar = ($torneio['status'] === 'em_andamento' && $finalPartida && !empty($finalPartida['vencedor_id']));
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
    <!-- Cabeçalho do Torneio -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 mb-8 shadow-xl">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 border-b border-slate-800">
            <div>
                <div class="flex items-center gap-3 mb-2 flex-wrap">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 uppercase tracking-wide">
                        <?php echo e($torneio['modalidade']); ?>
                    </span>
                    <?php if ($torneio['status'] === 'finalizado'): ?>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-300 border border-amber-500/30 flex items-center gap-1.5">
                            <i class="fas fa-trophy"></i> Torneio Finalizado
                        </span>
                    <?php else: ?>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span> Em Andamento
                        </span>
                    <?php endif; ?>
                    <span class="text-xs text-slate-400">
                        <i class="fas fa-users mr-1"></i><?php echo $torneio['total_equipes']; ?> Equipes
                    </span>
                    <span class="text-xs text-slate-500">
                        <i class="far fa-calendar-alt mr-1"></i>Criado em <?php echo date('d/m/Y', strtotime($torneio['criado_em'])); ?>
                    </span>
                </div>

                <h1 class="text-2xl sm:text-4xl font-extrabold font-display text-white tracking-tight mb-1">
                    <?php echo e($torneio['nome']); ?>
                </h1>
                <?php if (!empty($torneio['categoria'])): ?>
                    <p class="text-xs sm:text-sm text-slate-400"><?php echo e($torneio['categoria']); ?></p>
                <?php endif; ?>
            </div>

            <!-- Botões de Ação do Topo -->
            <div class="flex flex-wrap items-center gap-2.5 no-print">
                <button type="button" onclick="compartilharTorneio()" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700 transition flex items-center gap-1.5 shadow-sm">
                    <i class="fas fa-share-alt"></i> Compartilhar
                </button>
                <button type="button" onclick="window.print()" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700 transition flex items-center gap-1.5 shadow-sm">
                    <i class="fas fa-print"></i> Imprimir Chave
                </button>

                <?php if ($usuarioLogado): ?>
                    <?php if ($podeFinalizar): ?>
                        <button type="button" onclick="confirmarFinalizarTorneio()" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-amber-600/30 transition flex items-center gap-1.5">
                            <i class="fas fa-flag-checkered"></i> Concluir Torneio
                        </button>
                    <?php endif; ?>

                    <button type="button" onclick="confirmarReiniciarTorneio()" class="px-3 py-2 bg-slate-800 hover:bg-rose-900/40 text-slate-300 hover:text-rose-300 text-xs font-semibold rounded-xl border border-slate-700 transition flex items-center gap-1.5" title="Zerar todos os placares mantendo os times">
                        <i class="fas fa-redo"></i> Resetar
                    </button>

                    <button type="button" onclick="confirmarExcluirTorneio()" class="px-3 py-2 bg-slate-800 hover:bg-rose-950 text-slate-400 hover:text-rose-400 text-xs font-semibold rounded-xl border border-slate-700 transition" title="Excluir este campeonato">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                <?php else: ?>
                    <a href="login.php" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-md shadow-emerald-600/20">
                        <i class="fas fa-lock"></i> Entrar p/ Editar
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pódio de Vencedores (Exibido quando houver campeão definido) -->
        <?php if (!empty($torneio['nome_campeao'])): ?>
            <div class="mt-6 pt-6 border-t border-slate-800/80">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i class="fas fa-trophy text-amber-400"></i> Classificação Oficial & Pódio
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Campeão -->
                    <div class="p-4 rounded-2xl bg-gradient-to-b from-amber-500/20 to-amber-900/10 border border-amber-500/30 flex items-center gap-3 shadow-lg">
                        <div class="w-12 h-12 rounded-xl bg-amber-500 text-slate-950 font-black text-2xl flex items-center justify-center shadow-md shadow-amber-500/30">
                            🥇
                        </div>
                        <div>
                            <span class="block text-[11px] font-bold text-amber-400 uppercase tracking-widest">Campeão</span>
                            <span class="block text-base font-extrabold text-white truncate"><?php echo e($torneio['nome_campeao']); ?></span>
                        </div>
                    </div>

                    <!-- Vice-Campeão -->
                    <?php if (!empty($torneio['nome_vice'])): ?>
                        <div class="p-4 rounded-2xl bg-gradient-to-b from-slate-400/15 to-slate-800/10 border border-slate-600/30 flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-slate-300 text-slate-900 font-black text-2xl flex items-center justify-center shadow-md">
                                🥈
                            </div>
                            <div>
                                <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest">Vice-Campeão</span>
                                <span class="block text-base font-extrabold text-white truncate"><?php echo e($torneio['nome_vice']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 3º Colocado -->
                    <?php if (!empty($torneio['nome_terceiro'])): ?>
                        <div class="p-4 rounded-2xl bg-gradient-to-b from-amber-700/15 to-amber-950/10 border border-amber-700/30 flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-amber-700 text-white font-black text-2xl flex items-center justify-center shadow-md">
                                🥉
                            </div>
                            <div>
                                <span class="block text-[11px] font-bold text-amber-600 uppercase tracking-widest">3º Colocado</span>
                                <span class="block text-base font-extrabold text-white truncate"><?php echo e($torneio['nome_terceiro']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- ÁRVORE VISUAL DO CHAVEAMENTO (ESTILO BRACKETMAKER) -->
    <div class="mb-12">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <i class="fas fa-project-diagram text-emerald-400"></i>
                <h2 class="text-lg font-bold text-white">Chaveamento Eliminatório</h2>
            </div>
            <div class="text-xs text-slate-400 flex items-center gap-3">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Classificado</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-slate-700"></span> Pendente</span>
            </div>
        </div>

        <!-- Container com Scroll Horizontal Suave -->
        <div class="bracket-wrapper overflow-x-auto pb-8 pt-2">
            <div class="inline-flex gap-8 min-w-full items-stretch">
                <?php
                    $faseIndex = 0;
                    $totalFasesCount = count($fases);
                    foreach ($fases as $faseNumero => $partidasDaFase):
                        $faseIndex++;
                        $nomeFase = $partidasDaFase[0]['nome_fase'] ?? "Fase {$faseNumero}";
                ?>
                    <!-- Coluna da Fase -->
                    <div class="flex flex-col flex-shrink-0 w-72 sm:w-80">
                        <!-- Título da Coluna da Fase -->
                        <div class="bg-slate-900/90 border border-slate-800 rounded-xl p-3 mb-6 text-center shadow-md">
                            <h3 class="font-bold text-sm text-white tracking-wide"><?php echo e($nomeFase); ?></h3>
                            <span class="text-[11px] text-slate-400">
                                <?php echo count($partidasDaFase); ?> <?php echo count($partidasDaFase) === 1 ? 'partida' : 'partidas'; ?>
                            </span>
                        </div>

                        <!-- Lista de Partidas com espaçamento distribuído verticalmente -->
                        <div class="flex flex-col justify-around flex-grow gap-6">
                            <?php foreach ($partidasDaFase as $partida): ?>
                                <?php
                                    $isBye = ($partida['status'] === 'bye');
                                    $isFinalizada = ($partida['status'] === 'finalizada');
                                    $ambosDefinidos = (!empty($partida['equipe1_id']) && !empty($partida['equipe2_id']));
                                    
                                    $t1Vencedor = ($partida['vencedor_id'] && $partida['vencedor_id'] == $partida['equipe1_id']);
                                    $t2Vencedor = ($partida['vencedor_id'] && $partida['vencedor_id'] == $partida['equipe2_id']);
                                    
                                    $podeClicar = ($usuarioLogado && $ambosDefinidos && $torneio['status'] !== 'finalizado');
                                ?>
                                <div class="match-card relative bg-slate-900 border <?php echo $isFinalizada ? 'border-slate-700/80' : 'border-slate-800'; ?> rounded-2xl p-4 shadow-xl select-none <?php echo $podeClicar ? 'cursor-pointer hover:border-emerald-500/50' : ''; ?>"
                                     <?php if ($podeClicar): ?>
                                         onclick="abrirModalPartida(<?php echo htmlspecialchars(json_encode($partida), ENT_QUOTES, 'UTF-8'); ?>)"
                                         title="Clique para lançar o placar"
                                     <?php endif; ?>>
                                    
                                    <!-- Topo do Card: Número do jogo e Status -->
                                    <div class="flex items-center justify-between text-[11px] pb-2 mb-2 border-b border-slate-800/80">
                                        <span class="font-mono font-bold text-slate-500">Jogo #<?php echo $partida['numero_jogo']; ?></span>
                                        <?php if ($isBye): ?>
                                            <span class="text-amber-400 font-semibold text-[10px] uppercase bg-amber-500/10 px-2 py-0.5 rounded">Isento (Bye)</span>
                                        <?php elseif ($isFinalizada): ?>
                                            <span class="text-emerald-400 font-semibold text-[10px] uppercase bg-emerald-500/10 px-2 py-0.5 rounded flex items-center gap-1">
                                                <i class="fas fa-check text-[9px]"></i> Finalizada
                                            </span>
                                        <?php elseif ($ambosDefinidos): ?>
                                            <span class="text-blue-400 font-semibold text-[10px] uppercase bg-blue-500/10 px-2 py-0.5 rounded">
                                                <?php echo $usuarioLogado ? 'Lançar Placar' : 'Agendada'; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-500 font-semibold text-[10px] uppercase">Aguardando</span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Time 1 -->
                                    <div class="flex items-center justify-between p-2 rounded-lg transition <?php echo $t1Vencedor ? 'bg-emerald-950/40 text-emerald-300 font-bold border border-emerald-500/20' : 'text-slate-300'; ?>">
                                        <div class="flex items-center gap-2 truncate mr-2">
                                            <?php if ($t1Vencedor): ?>
                                                <i class="fas fa-check text-emerald-400 text-xs"></i>
                                            <?php endif; ?>
                                            <span class="text-xs truncate" title="<?php echo e($partida['nome_time1'] ?: 'Aguardando vencedor...'); ?>">
                                                <?php echo e($partida['nome_time1'] ?: 'Aguardando...'); ?>
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1 font-mono">
                                            <?php if ($partida['placar1'] !== null && !$isBye): ?>
                                                <span class="text-base font-extrabold px-1.5 py-0.5 rounded bg-slate-950 <?php echo $t1Vencedor ? 'text-emerald-400' : 'text-slate-400'; ?>">
                                                    <?php echo $partida['placar1']; ?>
                                                </span>
                                                <?php if ($partida['penaltis1'] !== null): ?>
                                                    <span class="text-[10px] text-amber-400">(<?php echo $partida['penaltis1']; ?>)</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-slate-600 text-xs">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Divisor -->
                                    <div class="h-px bg-slate-800/60 my-1"></div>

                                    <!-- Time 2 -->
                                    <div class="flex items-center justify-between p-2 rounded-lg transition <?php echo $t2Vencedor ? 'bg-emerald-950/40 text-emerald-300 font-bold border border-emerald-500/20' : 'text-slate-300'; ?>">
                                        <div class="flex items-center gap-2 truncate mr-2">
                                            <?php if ($t2Vencedor): ?>
                                                <i class="fas fa-check text-emerald-400 text-xs"></i>
                                            <?php endif; ?>
                                            <span class="text-xs truncate" title="<?php echo e($isBye ? 'Folga (Avanço Direto)' : ($partida['nome_time2'] ?: 'Aguardando vencedor...')); ?>">
                                                <?php echo e($isBye ? 'Folga (BYE)' : ($partida['nome_time2'] ?: 'Aguardando...')); ?>
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1 font-mono">
                                            <?php if ($partida['placar2'] !== null && !$isBye): ?>
                                                <span class="text-base font-extrabold px-1.5 py-0.5 rounded bg-slate-950 <?php echo $t2Vencedor ? 'text-emerald-400' : 'text-slate-400'; ?>">
                                                    <?php echo $partida['placar2']; ?>
                                                </span>
                                                <?php if ($partida['penaltis2'] !== null): ?>
                                                    <span class="text-[10px] text-amber-400">(<?php echo $partida['penaltis2']; ?>)</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-slate-600 text-xs">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Observação rápida da partida (se houver) -->
                                    <?php if (!empty($partida['observacoes'])): ?>
                                        <div class="mt-2 pt-1.5 border-t border-slate-800/60 text-[10px] text-slate-500 italic truncate" title="<?php echo e($partida['observacoes']); ?>">
                                            <i class="far fa-comment-alt mr-1"></i><?php echo e($partida['observacoes']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- DISPUTA DE 3º LUGAR (Se houver no torneio) -->
                <?php if ($partidaTerceiroLugar): ?>
                    <?php
                        $p3 = $partidaTerceiroLugar;
                        $t1V3 = ($p3['vencedor_id'] && $p3['vencedor_id'] == $p3['equipe1_id']);
                        $t2V3 = ($p3['vencedor_id'] && $p3['vencedor_id'] == $p3['equipe2_id']);
                        $ambos3 = (!empty($p3['equipe1_id']) && !empty($p3['equipe2_id']));
                        $podeClicar3 = ($usuarioLogado && $ambos3 && $torneio['status'] !== 'finalizado');
                    ?>
                    <div class="flex flex-col flex-shrink-0 w-72 sm:w-80">
                        <div class="bg-gradient-to-r from-amber-950/40 to-slate-900 border border-amber-800/40 rounded-xl p-3 mb-6 text-center shadow-md">
                            <h3 class="font-bold text-sm text-amber-400 tracking-wide">Disputa de 3º Lugar</h3>
                            <span class="text-[11px] text-slate-400">Medalha de Bronze</span>
                        </div>

                        <div class="flex flex-col justify-center flex-grow">
                            <div class="match-card bg-slate-900 border border-amber-800/30 rounded-2xl p-4 shadow-xl <?php echo $podeClicar3 ? 'cursor-pointer hover:border-amber-500' : ''; ?>"
                                 <?php if ($podeClicar3): ?>
                                     onclick="abrirModalPartida(<?php echo htmlspecialchars(json_encode($p3), ENT_QUOTES, 'UTF-8'); ?>)"
                                 <?php endif; ?>>
                                <div class="flex items-center justify-between text-[11px] pb-2 mb-2 border-b border-slate-800/80">
                                    <span class="font-mono font-bold text-amber-400">Bronze</span>
                                    <span class="text-[10px] text-slate-400 uppercase">
                                        <?php echo $p3['status'] === 'finalizada' ? 'Concluída' : 'Aguardando Semis'; ?>
                                    </span>
                                </div>

                                <div class="flex items-center justify-between p-2 rounded-lg <?php echo $t1V3 ? 'bg-amber-950/50 text-amber-300 font-bold border border-amber-500/20' : 'text-slate-300'; ?>">
                                    <span class="text-xs truncate"><?php echo e($p3['nome_time1'] ?: 'Perdedor Semi 1'); ?></span>
                                    <span class="font-mono text-sm font-bold"><?php echo $p3['placar1'] !== null ? $p3['placar1'] : '-'; ?></span>
                                </div>
                                <div class="h-px bg-slate-800/60 my-1"></div>
                                <div class="flex items-center justify-between p-2 rounded-lg <?php echo $t2V3 ? 'bg-amber-950/50 text-amber-300 font-bold border border-amber-500/20' : 'text-slate-300'; ?>">
                                    <span class="text-xs truncate"><?php echo e($p3['nome_time2'] ?: 'Perdedor Semi 2'); ?></span>
                                    <span class="font-mono text-sm font-bold"><?php echo $p3['placar2'] !== null ? $p3['placar2'] : '-'; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- FORMULÁRIOS OCULTOS PARA AÇÕES COM MODAL CUSTOMIZADO -->
    <form id="form-finalizar-torneio" action="actions/torneios.php" method="POST" class="hidden">
        <input type="hidden" name="acao" value="finalizar">
        <input type="hidden" name="torneio_id" value="<?php echo $torneioId; ?>">
    </form>

    <form id="form-reiniciar-torneio" action="actions/torneios.php" method="POST" class="hidden">
        <input type="hidden" name="acao" value="reiniciar">
        <input type="hidden" name="torneio_id" value="<?php echo $torneioId; ?>">
    </form>

    <form id="form-excluir-torneio" action="actions/torneios.php" method="POST" class="hidden">
        <input type="hidden" name="acao" value="excluir">
        <input type="hidden" name="torneio_id" value="<?php echo $torneioId; ?>">
    </form>
</main>

<script>
// Compartilhar link do torneio
function compartilharTorneio() {
    const url = window.location.href;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            mostrarToast('sucesso', 'Link Copiado!', 'O link deste chaveamento foi copiado para sua área de transferência.');
        }).catch(() => {
            mostrarToast('info', 'Link do Torneio', url);
        });
    } else {
        mostrarToast('info', 'Link do Torneio', url);
    }
}

// Confirmação para finalizar o torneio (Usa Modal em vez de confirm)
function confirmarFinalizarTorneio() {
    abrirModalConfirmacao({
        titulo: 'Concluir Torneio',
        subtitulo: 'Homologação dos Resultados',
        mensagem: 'Deseja oficializar os resultados e coroar os vencedores? O torneio será marcado como Finalizado.',
        tipo: 'sucesso',
        btnConfirmarTexto: 'Finalizar Campeonato',
        onConfirm: () => {
            document.getElementById('form-finalizar-torneio').submit();
        }
    });
}

// Confirmação para reiniciar torneio
function confirmarReiniciarTorneio() {
    abrirModalConfirmacao({
        titulo: 'Reiniciar Torneio',
        subtitulo: 'Ação Crítica de Auditoria',
        mensagem: 'Atenção: todos os placares registrados serão zerados e os vencedores de fases posteriores serão apagados. As equipes originais serão mantidas. Deseja continuar?',
        tipo: 'perigo',
        btnConfirmarTexto: 'Sim, Reiniciar Chaves',
        onConfirm: () => {
            document.getElementById('form-reiniciar-torneio').submit();
        }
    });
}

// Confirmação para excluir torneio
function confirmarExcluirTorneio() {
    abrirModalConfirmacao({
        titulo: 'Excluir Torneio',
        subtitulo: 'Ação Irreversível',
        mensagem: 'Deseja realmente apagar este campeonato e todas as suas partidas? Esta ação será registrada no relatório de auditoria.',
        tipo: 'perigo',
        btnConfirmarTexto: 'Excluir Definitivamente',
        onConfirm: () => {
            document.getElementById('form-excluir-torneio').submit();
        }
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
