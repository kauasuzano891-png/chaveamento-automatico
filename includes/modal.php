<?php
/**
 * Componente de Modais Genéricos em Tailwind CSS
 * Atende à regra do requisitos.md: "sempre trate as mensagens de caixa de mensagens nativas do navegador em um MODAL"
 */
?>

<!-- MODAL DE CONFIRMAÇÃO (Substitui confirm() nativo) -->
<div id="modal-confirmacao" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200">
    <div class="bg-slate-900 border border-slate-800 text-slate-100 rounded-2xl max-w-md w-full p-6 shadow-2xl transform scale-95 transition-transform duration-200">
        <div class="flex items-center gap-4 mb-4">
            <div id="modal-confirmacao-icone-bg" class="w-12 h-12 rounded-xl flex items-center justify-center bg-rose-500/10 text-rose-400 text-xl border border-rose-500/20">
                <i id="modal-confirmacao-icone" class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <h3 id="modal-confirmacao-titulo" class="text-lg font-bold text-white">Confirmação</h3>
                <p id="modal-confirmacao-subtitulo" class="text-xs text-slate-400">Esta ação requer sua atenção</p>
            </div>
        </div>
        <p id="modal-confirmacao-mensagem" class="text-sm text-slate-300 mb-6 leading-relaxed">
            Deseja realmente prosseguir com esta ação?
        </p>
        <div class="flex items-center justify-end gap-3">
            <button id="modal-confirmacao-btn-cancelar" type="button" class="px-4 py-2 text-sm font-medium text-slate-400 hover:text-white bg-slate-800/80 hover:bg-slate-800 rounded-lg transition border border-slate-700/60">
                Cancelar
            </button>
            <button id="modal-confirmacao-btn-confirmar" type="button" class="px-5 py-2 text-sm font-bold text-white bg-rose-600 hover:bg-rose-500 rounded-lg shadow-lg shadow-rose-600/30 transition">
                Confirmar
            </button>
        </div>
    </div>
</div>

<!-- MODAL DE ALERTA / AVISO (Substitui alert() nativo) -->
<div id="modal-alerta" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200">
    <div class="bg-slate-900 border border-slate-800 text-slate-100 rounded-2xl max-w-md w-full p-6 shadow-2xl transform scale-95 transition-transform duration-200">
        <div class="flex items-center gap-4 mb-4">
            <div id="modal-alerta-icone-bg" class="w-12 h-12 rounded-xl flex items-center justify-center bg-blue-500/10 text-blue-400 text-xl border border-blue-500/20">
                <i id="modal-alerta-icone" class="fas fa-info-circle"></i>
            </div>
            <div>
                <h3 id="modal-alerta-titulo" class="text-lg font-bold text-white">Aviso do Sistema</h3>
                <p id="modal-alerta-subtitulo" class="text-xs text-slate-400">Informação importante</p>
            </div>
        </div>
        <p id="modal-alerta-mensagem" class="text-sm text-slate-300 mb-6 leading-relaxed">
            Mensagem
        </p>
        <div class="flex items-center justify-end">
            <button id="modal-alerta-btn-ok" type="button" class="px-5 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-lg shadow-lg shadow-blue-600/30 transition">
                Entendido
            </button>
        </div>
    </div>
</div>

<!-- MODAL DE SÚMULA / INSERIR PLACAR (Para o administrador registrar o resultado da partida) -->
<div id="modal-partida" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200">
    <div class="bg-slate-900 border border-slate-800 text-slate-100 rounded-2xl max-w-lg w-full p-6 shadow-2xl transform scale-95 transition-transform duration-200">
        <!-- Topo com título e botão fechar -->
        <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                    <i class="fas fa-futbol"></i>
                </div>
                <div>
                    <h3 id="modal-partida-fase" class="text-base font-bold text-white">Súmula da Partida</h3>
                    <p id="modal-partida-jogo" class="text-xs text-slate-400">Jogo #1</p>
                </div>
            </div>
            <button onclick="fecharModalPartida()" class="text-slate-400 hover:text-white p-2 text-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Formulário da Partida -->
        <form id="form-salvar-partida" method="POST" action="actions/partidas.php" class="space-y-5">
            <input type="hidden" name="acao" value="salvar_placar">
            <input type="hidden" name="partida_id" id="partida_id" value="">
            <input type="hidden" name="torneio_id" id="torneio_id" value="">

            <!-- Placar dos Times -->
            <div class="bg-slate-950/60 p-4 rounded-xl border border-slate-800/80">
                <div class="grid grid-cols-5 gap-3 items-center text-center">
                    <!-- Time 1 -->
                    <div class="col-span-2 text-left">
                        <label id="nome-time-1" class="block font-bold text-sm text-slate-200 truncate mb-2">Equipe 1</label>
                        <input type="number" min="0" max="999" name="placar1" id="input-placar-1" required
                               class="w-full bg-slate-900 border border-slate-700 text-white text-center text-2xl font-black rounded-xl py-3 focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none">
                    </div>

                    <!-- VS -->
                    <div class="col-span-1 flex flex-col items-center justify-center">
                        <span class="text-xs font-bold uppercase tracking-widest text-slate-500">VS</span>
                        <div class="w-6 h-0.5 bg-slate-800 my-1"></div>
                        <span class="text-[10px] text-slate-500 font-mono">PLACAR</span>
                    </div>

                    <!-- Time 2 -->
                    <div class="col-span-2 text-right">
                        <label id="nome-time-2" class="block font-bold text-sm text-slate-200 truncate mb-2">Equipe 2</label>
                        <input type="number" min="0" max="999" name="placar2" id="input-placar-2" required
                               class="w-full bg-slate-900 border border-slate-700 text-white text-center text-2xl font-black rounded-xl py-3 focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none">
                    </div>
                </div>

                <!-- Seção de Pênaltis / Desempate (visível quando placar for igual) -->
                <div id="secao-penaltis" class="mt-4 pt-4 border-t border-slate-800/80 hidden">
                    <p class="text-xs text-amber-400 font-medium mb-2 flex items-center justify-center gap-1.5">
                        <i class="fas fa-stopwatch"></i> Partida empatada! Informe o desempate (Pênaltis / Pontos Extras):
                    </p>
                    <div class="grid grid-cols-5 gap-3 items-center">
                        <div class="col-span-2">
                            <input type="number" min="0" max="99" name="penaltis1" id="input-penaltis-1" placeholder="Pên. 1"
                                   class="w-full bg-slate-900 border border-amber-500/40 text-amber-300 text-center text-lg font-bold rounded-lg py-2 focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                        <div class="col-span-1 text-center text-xs text-slate-500 font-bold">DESEMPATE</div>
                        <div class="col-span-2">
                            <input type="number" min="0" max="99" name="penaltis2" id="input-penaltis-2" placeholder="Pên. 2"
                                   class="w-full bg-slate-900 border border-amber-500/40 text-amber-300 text-center text-lg font-bold rounded-lg py-2 focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Observações da partida -->
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                    Observações / Súmula (Gols, Cartões, Destaques)
                </label>
                <textarea name="observacoes" id="input-observacoes" rows="2" placeholder="Ex: Gols marcados por João (10') e Pedro (25')..."
                          class="w-full bg-slate-950/60 border border-slate-800 rounded-xl p-3 text-sm text-slate-300 placeholder-slate-600 focus:ring-2 focus:ring-emerald-500 outline-none"></textarea>
            </div>

            <!-- Ações -->
            <div class="flex items-center justify-between pt-3 border-t border-slate-800">
                <button type="button" onclick="fecharModalPartida()" class="px-4 py-2 text-xs font-medium text-slate-400 hover:text-white transition">
                    Fechar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold rounded-xl shadow-lg shadow-emerald-600/30 transition flex items-center gap-2">
                    <i class="fas fa-check"></i> Salvar e Avançar Vencedor
                </button>
            </div>
        </form>
    </div>
</div>
