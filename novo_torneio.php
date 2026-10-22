<?php
$paginaTitulo = "Criar Novo Torneio";
require_once __DIR__ . '/bd.php';
exigirLogin(); // Exige autenticação de organizador
include __DIR__ . '/includes/header.php';
?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">
    <!-- Cabeçalho da Página -->
    <div class="mb-8">
        <a href="index.php" class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-emerald-400 mb-3 transition">
            <i class="fas fa-arrow-left text-[10px]"></i> Voltar ao painel
        </a>
        <h1 class="text-3xl font-extrabold font-display text-white tracking-tight flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center border border-emerald-500/20 text-lg">
                <i class="fas fa-sitemap"></i>
            </span>
            Gerador de Chaveamento Automático
        </h1>
        <p class="text-sm text-slate-400 mt-1">
            Cadastre os dados da competição e insira a lista de equipes. O algoritmo organizará confrontos, calculará as fases e definirá os byes automaticamente.
        </p>
    </div>

    <!-- Formulário Principal -->
    <form action="actions/torneios.php" method="POST" id="form-novo-torneio" class="space-y-8">
        <input type="hidden" name="acao" value="criar">

        <!-- 1. DADOS BÁSICOS DA COMPETIÇÃO -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl">
            <div class="flex items-center gap-3 pb-4 mb-6 border-b border-slate-800">
                <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white font-bold text-xs flex items-center justify-center">1</span>
                <h2 class="text-lg font-bold text-white">Informações da Competição</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Nome do Torneio -->
                <div class="sm:col-span-2">
                    <label for="nome" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        Nome do Torneio / Campeonato <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="nome" id="nome" required placeholder="Ex: Torneio Interclasses ETEC 2026"
                           class="w-full bg-slate-950/80 border border-slate-700/80 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none">
                </div>

                <!-- Modalidade Esportiva -->
                <div>
                    <label for="modalidade" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        Modalidade Esportiva <span class="text-rose-500">*</span>
                    </label>
                    <select name="modalidade" id="modalidade" required
                            class="w-full bg-slate-950/80 border border-slate-700/80 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none">
                        <option value="Futsal">⚽ Futsal</option>
                        <option value="Futebol de Campo">⚽ Futebol de Campo</option>
                        <option value="Vôlei">🏐 Vôlei</option>
                        <option value="Basquete">🏀 Basquete</option>
                        <option value="Handebol">🤾 Handebol</option>
                        <option value="Queimada">🎯 Queimada</option>
                        <option value="Tênis de Mesa">🏓 Tênis de Mesa</option>
                        <option value="E-Sports">🎮 E-Sports (Fifa, LoL, CS)</option>
                        <option value="Outro">🏆 Outra Modalidade</option>
                    </select>
                </div>

                <!-- Categoria / Observação -->
                <div>
                    <label for="categoria" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        Categoria / Turma
                    </label>
                    <input type="text" name="categoria" id="categoria" placeholder="Ex: Ensino Médio & Técnico (Tarde)"
                           class="w-full bg-slate-950/80 border border-slate-700/80 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none">
                </div>

                <!-- Critério de Desempate -->
                <div>
                    <label for="criterio_desempate" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        Critério em caso de empate
                    </label>
                    <select name="criterio_desempate" id="criterio_desempate"
                            class="w-full bg-slate-950/80 border border-slate-700/80 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none">
                        <option value="penaltis">Disputa de Pênaltis</option>
                        <option value="prorrogacao">Prorrogação + Pênaltis</option>
                        <option value="tiros_livres">Tiros Livres Alternados</option>
                        <option value="golden_goal">Morte Súbita (Golden Goal)</option>
                    </select>
                </div>

                <!-- Opções Adicionais (Disputa de 3º lugar) -->
                <div class="flex items-center">
                    <label class="relative flex items-center gap-3 cursor-pointer select-none">
                        <input type="checkbox" name="tem_terceiro_lugar" value="1" checked class="w-5 h-5 rounded bg-slate-950 border-slate-700 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm font-medium text-slate-200">
                            Incluir disputa pelo <strong>3º Lugar</strong> (Bronze)
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <!-- 2. CADASTRO DE EQUIPES E PARTICIPANTES -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl">
            <div class="flex items-center justify-between pb-4 mb-6 border-b border-slate-800 flex-wrap gap-2">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white font-bold text-xs flex items-center justify-center">2</span>
                    <div>
                        <h2 class="text-lg font-bold text-white">Equipes / Turmas Participantes</h2>
                        <p class="text-xs text-slate-400">Insira um nome por linha (mínimo de 2)</p>
                    </div>
                </div>

                <!-- Botões para inserir exemplos rápidos -->
                <div class="flex items-center gap-2">
                    <button type="button" onclick="carregarExemplo(4)" class="px-2.5 py-1 text-[11px] font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-md transition">
                        Exemplo (4 times)
                    </button>
                    <button type="button" onclick="carregarExemplo(8)" class="px-2.5 py-1 text-[11px] font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-md transition">
                        Exemplo (8 times)
                    </button>
                </div>
            </div>

            <!-- Textarea de Equipes -->
            <div class="mb-4">
                <textarea name="equipes_texto" id="equipes_texto" rows="9" required
                          placeholder="Digite ou cole as equipes aqui (uma por linha)&#10;Exemplo:&#10;1º DS - Manhã&#10;2º DS - Manhã&#10;3º DS - Manhã&#10;1º ADM - Tarde&#10;2º ADM - Tarde&#10;3º ADM - Tarde"
                          class="w-full font-mono bg-slate-950/80 border border-slate-700/80 rounded-xl p-4 text-sm text-emerald-300 placeholder-slate-600 focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none"></textarea>
            </div>

            <!-- Painel Informativo Dinâmico sobre a Chave -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 mb-6">
                <div>
                    <span class="block text-[11px] text-slate-400 uppercase tracking-wider font-semibold">Equipes Detectadas</span>
                    <span id="contador-equipes" class="text-xl font-black text-white font-mono">0</span>
                </div>
                <div>
                    <span class="block text-[11px] text-slate-400 uppercase tracking-wider font-semibold">Tamanho da Chave</span>
                    <span id="tamanho-chave" class="text-xl font-black text-emerald-400 font-mono">-</span>
                </div>
                <div>
                    <span class="block text-[11px] text-slate-400 uppercase tracking-wider font-semibold">Isenções (Byes)</span>
                    <span id="total-byes" class="text-xl font-black text-amber-400 font-mono">-</span>
                </div>
            </div>

            <!-- Opção de Sorteio Imparcial -->
            <div class="p-4 rounded-xl bg-emerald-500/5 border border-emerald-500/20 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="sortear_equipes" id="sortear_equipes" value="1" checked
                           class="w-5 h-5 rounded bg-slate-950 border-slate-700 text-emerald-600 focus:ring-emerald-500">
                    <div>
                        <label for="sortear_equipes" class="text-sm font-bold text-white cursor-pointer block">
                            Sortear posições aleatoriamente na chave
                        </label>
                        <span class="text-xs text-slate-400">Garante neutralidade total e ausência de manipulação no torneio</span>
                    </div>
                </div>
                <i class="fas fa-dice text-emerald-400 text-2xl"></i>
            </div>
        </div>

        <!-- BOTÕES DE SUBMISSÃO -->
        <div class="flex items-center justify-between pt-4 border-t border-slate-800">
            <a href="index.php" class="px-5 py-2.5 text-xs font-semibold text-slate-400 hover:text-white transition">
                Cancelar
            </a>
            <button type="submit" id="btn-gerar" class="px-8 py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-sm rounded-xl shadow-xl shadow-emerald-600/30 transition transform hover:-translate-y-0.5 flex items-center gap-2">
                <i class="fas fa-magic"></i> Gerar Chaveamento Automático
            </button>
        </div>
    </form>
</main>

<script>
// Contador dinâmico e cálculo de chave e byes
const textareaEquipes = document.getElementById('equipes_texto');
const contadorEl = document.getElementById('contador-equipes');
const tamanhoChaveEl = document.getElementById('tamanho-chave');
const totalByesEl = document.getElementById('total-byes');

function atualizarCalculoChave() {
    const linhas = textareaEquipes.value.split('\n')
        .map(l => l.trim())
        .filter(l => l.length > 0);

    const total = linhas.length;
    contadorEl.textContent = total;

    if (total < 2) {
        tamanhoChaveEl.textContent = '-';
        totalByesEl.textContent = '-';
        return;
    }

    // Calcula potência de 2
    let chave = 2;
    while (chave < total) {
        chave *= 2;
    }

    const byes = chave - total;
    tamanhoChaveEl.textContent = `${chave} equipes`;
    totalByesEl.textContent = byes > 0 ? `${byes} folgas (Byes)` : 'Nenhuma (Chave cheia)';
}

textareaEquipes.addEventListener('input', atualizarCalculoChave);

function carregarExemplo(quantidade) {
    if (quantidade === 4) {
        textareaEquipes.value = "1º Ano DS (Alpha)\n2º Ano DS (Beta)\n3º Ano DS (Gamma)\n1º Ano ADM (Delta)";
    } else if (quantidade === 8) {
        textareaEquipes.value = "1º DS Manhã\n2º DS Manhã\n3º DS Manhã\n1º ADM Manhã\n2º ADM Manhã\n3º ADM Manhã\n1º EDIF Manhã\n2º EDIF Manhã";
    }
    atualizarCalculoChave();
    mostrarToast('info', 'Exemplo Carregado', `Lista pré-definida com ${quantidade} equipes inserida.`);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
