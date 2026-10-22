/**
 * Scripts e Interatividades do Sistema de Chaveamento
 * Gerenciamento de Toasts, Modais e Súmula de Partidas
 */

// Auto-fechar TOASTs após 4 segundos
document.addEventListener('DOMContentLoaded', () => {
    const toasts = document.querySelectorAll('.toast-item');
    toasts.forEach(toast => {
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    });

    // Listener para o empate no formulário de partida
    const p1 = document.getElementById('input-placar-1');
    const p2 = document.getElementById('input-placar-2');
    if (p1 && p2) {
        const verificarEmpate = () => {
            const v1 = parseInt(p1.value, 10);
            const v2 = parseInt(p2.value, 10);
            const secaoPenaltis = document.getElementById('secao-penaltis');
            if (!isNaN(v1) && !isNaN(v2) && v1 === v2) {
                secaoPenaltis.classList.remove('hidden');
            } else {
                secaoPenaltis.classList.add('hidden');
            }
        };
        p1.addEventListener('input', verificarEmpate);
        p2.addEventListener('input', verificarEmpate);
    }

    // Validação no envio do formulário de partida
    const formPartida = document.getElementById('form-salvar-partida');
    if (formPartida) {
        formPartida.addEventListener('submit', (e) => {
            const v1 = parseInt(p1.value, 10);
            const v2 = parseInt(p2.value, 10);
            if (isNaN(v1) || isNaN(v2)) {
                e.preventDefault();
                mostrarToast('erro', 'Placar Inválido', 'Por favor, informe o placar dos dois times.');
                return;
            }
            if (v1 === v2) {
                const pen1 = parseInt(document.getElementById('input-penaltis-1').value, 10);
                const pen2 = parseInt(document.getElementById('input-penaltis-2').value, 10);
                if (isNaN(pen1) || isNaN(pen2) || pen1 === pen2) {
                    e.preventDefault();
                    mostrarToast('aviso', 'Desempate Obrigatório', 'A partida terminou empatada no tempo normal! É necessário definir o vencedor nos pênaltis ou desempate.');
                    return;
                }
            }
        });
    }
});

/**
 * Exibe notificação TOAST dinâmica
 */
function mostrarToast(tipo, titulo, mensagem) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const cores = {
        sucesso: 'bg-emerald-600 text-white border-emerald-500 shadow-emerald-500/20',
        erro: 'bg-rose-600 text-white border-rose-500 shadow-rose-500/20',
        aviso: 'bg-amber-500 text-slate-900 border-amber-400 shadow-amber-500/20',
        info: 'bg-blue-600 text-white border-blue-500 shadow-blue-500/20'
    };
    const icones = {
        sucesso: 'fa-check-circle',
        erro: 'fa-times-circle',
        aviso: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };

    const toast = document.createElement('div');
    toast.className = `toast-item pointer-events-auto flex items-start gap-3 p-4 rounded-xl shadow-xl border ${cores[tipo] || cores.info} transition-all duration-300 transform translate-y-0 opacity-100`;
    toast.innerHTML = `
        <div class="text-xl mt-0.5"><i class="fas ${icones[tipo] || icones.info}"></i></div>
        <div class="flex-1">
            <h4 class="font-bold text-sm tracking-wide">${titulo}</h4>
            <p class="text-xs opacity-95 mt-0.5 leading-relaxed">${mensagem}</p>
        </div>
        <button onclick="this.parentElement.remove()" class="text-white/80 hover:text-white transition text-sm">
            <i class="fas fa-times"></i>
        </button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

/**
 * MODAL DE CONFIRMAÇÃO (Substitui confirm() nativo do navegador)
 */
function abrirModalConfirmacao(opcoes) {
    const modal = document.getElementById('modal-confirmacao');
    const tituloEl = document.getElementById('modal-confirmacao-titulo');
    const subtituloEl = document.getElementById('modal-confirmacao-subtitulo');
    const mensagemEl = document.getElementById('modal-confirmacao-mensagem');
    const btnConfirmar = document.getElementById('modal-confirmacao-btn-confirmar');
    const btnCancelar = document.getElementById('modal-confirmacao-btn-cancelar');

    tituloEl.textContent = opcoes.titulo || 'Confirmação';
    subtituloEl.textContent = opcoes.subtitulo || 'Esta ação requer sua atenção';
    mensagemEl.textContent = opcoes.mensagem || 'Deseja realmente prosseguir?';
    btnConfirmar.textContent = opcoes.btnConfirmarTexto || 'Confirmar';

    // Ajusta classes do botão de confirmar
    if (opcoes.tipo === 'perigo' || !opcoes.tipo) {
        btnConfirmar.className = 'px-5 py-2 text-sm font-bold text-white bg-rose-600 hover:bg-rose-500 rounded-lg shadow-lg shadow-rose-600/30 transition';
    } else {
        btnConfirmar.className = 'px-5 py-2 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-lg shadow-lg shadow-emerald-600/30 transition';
    }

    const fechar = () => {
        modal.classList.add('opacity-0');
        modal.querySelector('.transform').classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 200);
    };

    btnCancelar.onclick = fechar;
    btnConfirmar.onclick = () => {
        fechar();
        if (typeof opcoes.onConfirm === 'function') {
            opcoes.onConfirm();
        }
    };

    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        modal.querySelector('.transform').classList.remove('scale-95');
    }, 10);
}

/**
 * MODAL DE ALERTA (Substitui alert() nativo do navegador)
 */
function abrirModalAlerta(opcoes) {
    const modal = document.getElementById('modal-alerta');
    const tituloEl = document.getElementById('modal-alerta-titulo');
    const mensagemEl = document.getElementById('modal-alerta-mensagem');
    const btnOk = document.getElementById('modal-alerta-btn-ok');

    tituloEl.textContent = opcoes.titulo || 'Aviso';
    mensagemEl.textContent = opcoes.mensagem || '';

    const fechar = () => {
        modal.classList.add('opacity-0');
        modal.querySelector('.transform').classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 200);
    };

    btnOk.onclick = fechar;

    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        modal.querySelector('.transform').classList.remove('scale-95');
    }, 10);
}

/**
 * Abre o Modal da Partida para o Administrador registrar o placar
 */
function abrirModalPartida(partida) {
    if (!partida.equipe1_id || !partida.equipe2_id) {
        mostrarToast('aviso', 'Confronto Indefinido', 'Esta partida ainda aguarda a definição dos times classificados na rodada anterior.');
        return;
    }

    document.getElementById('partida_id').value = partida.id;
    document.getElementById('torneio_id').value = partida.torneio_id;
    document.getElementById('modal-partida-fase').textContent = partida.nome_fase;
    document.getElementById('modal-partida-jogo').textContent = `Jogo #${partida.numero_jogo}`;

    document.getElementById('nome-time-1').textContent = partida.nome_time1 || 'Equipe 1';
    document.getElementById('nome-time-2').textContent = partida.nome_time2 || 'Equipe 2';

    const p1 = document.getElementById('input-placar-1');
    const p2 = document.getElementById('input-placar-2');
    const pen1 = document.getElementById('input-penaltis-1');
    const pen2 = document.getElementById('input-penaltis-2');
    const obs = document.getElementById('input-observacoes');

    p1.value = partida.placar1 !== null ? partida.placar1 : '';
    p2.value = partida.placar2 !== null ? partida.placar2 : '';
    pen1.value = partida.penaltis1 !== null ? partida.penaltis1 : '';
    pen2.value = partida.penaltis2 !== null ? partida.penaltis2 : '';
    obs.value = partida.observacoes || '';

    const secaoPenaltis = document.getElementById('secao-penaltis');
    if (partida.placar1 !== null && partida.placar1 === partida.placar2) {
        secaoPenaltis.classList.remove('hidden');
    } else {
        secaoPenaltis.classList.add('hidden');
    }

    const modal = document.getElementById('modal-partida');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        modal.querySelector('.transform').classList.remove('scale-95');
        p1.focus();
    }, 10);
}

function fecharModalPartida() {
    const modal = document.getElementById('modal-partida');
    if (!modal) return;
    modal.classList.add('opacity-0');
    modal.querySelector('.transform').classList.add('scale-95');
    setTimeout(() => modal.classList.add('hidden'), 200);
}
