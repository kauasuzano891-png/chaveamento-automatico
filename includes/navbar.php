<?php
/**
 * Barra de Navegação Responsiva
 * Mobile-First com Tailwind CSS
 */
$urlAtual = basename($_SERVER['PHP_SELF']);
?>
<header class="sticky top-0 z-40 bg-slate-900/90 backdrop-blur-md border-b border-slate-800/80 transition-all">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo e Título -->
            <div class="flex items-center gap-3">
                <a href="index.php" class="flex items-center gap-2.5 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-white shadow-lg shadow-emerald-500/20 group-hover:scale-105 transition-transform">
                        <i class="fas fa-sitemap text-lg"></i>
                    </div>
                    <div>
                        <span class="font-display font-extrabold text-lg tracking-tight bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-transparent">
                            Chaveamento<span class="text-emerald-400">Pro</span>
                        </span>
                        <span class="block text-[10px] text-slate-400 font-medium tracking-wider uppercase -mt-1">
                            Torneios & Campeonatos
                        </span>
                    </div>
                </a>
            </div>

            <!-- Links de Navegação Desktop -->
            <nav class="hidden md:flex items-center gap-1">
                <a href="index.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition <?php echo $urlAtual === 'index.php' ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800/50'; ?>">
                    <i class="fas fa-home text-xs mr-1.5"></i> Início
                </a>
                <a href="novo_torneio.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition <?php echo $urlAtual === 'novo_torneio.php' ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800/50'; ?>">
                    <i class="fas fa-plus-circle text-xs mr-1.5"></i> Novo Torneio
                </a>
                <a href="historico.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition <?php echo $urlAtual === 'historico.php' ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800/50'; ?>">
                    <i class="fas fa-trophy text-xs mr-1.5"></i> Histórico
                </a>
                <a href="auditoria.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition <?php echo $urlAtual === 'auditoria.php' ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800/50'; ?>">
                    <i class="fas fa-shield-alt text-xs mr-1.5"></i> Auditoria & Logs
                </a>
            </nav>

            <!-- Ações de Usuário / Autenticação -->
            <div class="hidden md:flex items-center gap-3">
                <?php if ($usuarioLogado): ?>
                    <div class="flex items-center gap-3 pl-3 border-l border-slate-800">
                        <div class="text-right">
                            <span class="block text-xs font-bold text-white"><?php echo e($usuarioLogado['nome']); ?></span>
                            <span class="block text-[10px] text-emerald-400 font-medium">Administrador</span>
                        </div>
                        <a href="actions/auth.php?acao=logout" title="Encerrar sessão"
                           class="w-9 h-9 rounded-lg bg-slate-800 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 flex items-center justify-center transition border border-slate-700/60">
                            <i class="fas fa-sign-out-alt text-sm"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="flex items-center gap-2">
                        <a href="login.php" class="px-3.5 py-1.5 text-xs font-semibold text-slate-300 hover:text-white transition">
                            Entrar
                        </a>
                        <a href="cadastro.php" class="px-3.5 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-lg shadow-md shadow-emerald-600/20 transition">
                            Cadastrar
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Botão Mobile Menu Hamburger -->
            <div class="flex md:hidden">
                <button type="button" onclick="toggleMenuMobile()" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none">
                    <i id="icone-menu-mobile" class="fas fa-bars text-lg"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Menu Dropdown Mobile -->
    <div id="menu-mobile" class="hidden md:hidden border-t border-slate-800 bg-slate-900 px-4 pt-3 pb-4 space-y-2">
        <a href="index.php" class="block px-3 py-2 rounded-lg text-sm font-medium <?php echo $urlAtual === 'index.php' ? 'bg-slate-800 text-emerald-400' : 'text-slate-300 hover:bg-slate-800'; ?>">
            <i class="fas fa-home mr-2 text-xs"></i> Início
        </a>
        <a href="novo_torneio.php" class="block px-3 py-2 rounded-lg text-sm font-medium <?php echo $urlAtual === 'novo_torneio.php' ? 'bg-slate-800 text-emerald-400' : 'text-slate-300 hover:bg-slate-800'; ?>">
            <i class="fas fa-plus-circle mr-2 text-xs"></i> Novo Torneio
        </a>
        <a href="historico.php" class="block px-3 py-2 rounded-lg text-sm font-medium <?php echo $urlAtual === 'historico.php' ? 'bg-slate-800 text-emerald-400' : 'text-slate-300 hover:bg-slate-800'; ?>">
            <i class="fas fa-trophy mr-2 text-xs"></i> Histórico
        </a>
        <a href="auditoria.php" class="block px-3 py-2 rounded-lg text-sm font-medium <?php echo $urlAtual === 'auditoria.php' ? 'bg-slate-800 text-emerald-400' : 'text-slate-300 hover:bg-slate-800'; ?>">
            <i class="fas fa-shield-alt mr-2 text-xs"></i> Auditoria & Logs
        </a>

        <div class="pt-3 border-t border-slate-800 flex flex-col gap-2">
            <?php if ($usuarioLogado): ?>
                <div class="px-3 py-1 text-xs text-slate-400">
                    Logado como <strong class="text-white"><?php echo e($usuarioLogado['nome']); ?></strong>
                </div>
                <a href="actions/auth.php?acao=logout" class="block px-3 py-2 rounded-lg text-sm font-medium text-rose-400 hover:bg-rose-500/10">
                    <i class="fas fa-sign-out-alt mr-2"></i> Sair da Conta
                </a>
            <?php else: ?>
                <a href="login.php" class="block text-center px-4 py-2 rounded-lg text-sm font-medium bg-slate-800 text-white">
                    Entrar
                </a>
                <a href="cadastro.php" class="block text-center px-4 py-2 rounded-lg text-sm font-bold bg-emerald-600 text-white">
                    Criar Conta
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<script>
function toggleMenuMobile() {
    const menu = document.getElementById('menu-mobile');
    const icone = document.getElementById('icone-menu-mobile');
    if (menu.classList.contains('hidden')) {
        menu.classList.remove('hidden');
        icone.classList.remove('fa-bars');
        icone.classList.add('fa-times');
    } else {
        menu.classList.add('hidden');
        icone.classList.remove('fa-times');
        icone.classList.add('fa-bars');
    }
}
</script>
