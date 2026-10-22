<?php
/**
 * Tela de Cadastro de Novo Organizador
 * Criptografia Segura com Bcrypt
 */
$paginaTitulo = "Cadastrar Organizador";
require_once __DIR__ . '/bd.php';

if (obterUsuarioLogado()) {
    header('Location: index.php');
    exit;
}

include __DIR__ . '/includes/header.php';
?>

<main class="min-h-[calc(100vh-14rem)] flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-8 sm:p-10 shadow-2xl">
        <!-- Logo e Cabeçalho -->
        <div class="text-center mb-8">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-white mx-auto mb-4 shadow-xl shadow-emerald-500/20 text-2xl">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1 class="text-2xl font-black font-display text-white tracking-tight">Criar Conta de Organizador</h1>
            <p class="text-xs text-slate-400 mt-1">
                Cadastre-se para criar campeonatos e homologar partidas esportivas
            </p>
        </div>

        <!-- Formulário de Cadastro -->
        <form action="actions/auth.php" method="POST" class="space-y-4">
            <input type="hidden" name="acao" value="cadastro">

            <div>
                <label for="nome" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Nome Completo <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 text-sm">
                        <i class="far fa-user"></i>
                    </span>
                    <input type="text" name="nome" id="nome" required placeholder="Prof. Carlos Silva"
                           class="w-full bg-slate-950/80 border border-slate-700/80 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-600 focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
            </div>

            <div>
                <label for="email" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    E-mail <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 text-sm">
                        <i class="far fa-envelope"></i>
                    </span>
                    <input type="email" name="email" id="email" required placeholder="carlos.silva@etec.sp.gov.br"
                           class="w-full bg-slate-950/80 border border-slate-700/80 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-600 focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
            </div>

            <div>
                <label for="senha" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Senha (mínimo 6 caracteres) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 text-sm">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="senha" id="senha" minlength="6" required placeholder="••••••••"
                           class="w-full bg-slate-950/80 border border-slate-700/80 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-600 focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
            </div>

            <div>
                <label for="senha_confirmacao" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Confirmar Senha <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 text-sm">
                        <i class="fas fa-check-double"></i>
                    </span>
                    <input type="password" name="senha_confirmacao" id="senha_confirmacao" minlength="6" required placeholder="••••••••"
                           class="w-full bg-slate-950/80 border border-slate-700/80 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-600 focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
            </div>

            <button type="submit" class="w-full mt-2 py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm rounded-xl shadow-lg shadow-emerald-600/30 transition transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                <i class="fas fa-check"></i> Concluir Cadastro
            </button>
        </form>

        <!-- Link para Login -->
        <div class="mt-6 pt-6 border-t border-slate-800 text-center text-xs text-slate-400">
            Já tem uma conta de organizador? 
            <a href="login.php" class="text-emerald-400 font-bold hover:underline ml-1">Fazer Login</a>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
