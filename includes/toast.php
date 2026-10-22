<?php
/**
 * Componente de Notificações TOAST
 * Exibe alertas elegantes na tela com Tailwind CSS
 */
$toast = obterToast();
?>
<!-- Container flutuante dos TOASTs -->
<div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col gap-3 max-w-sm w-full pointer-events-none">
    <?php if ($toast): ?>
        <?php
            $bgColors = [
                'sucesso' => 'bg-emerald-600 text-white border-emerald-500 shadow-emerald-500/20',
                'erro'    => 'bg-rose-600 text-white border-rose-500 shadow-rose-500/20',
                'aviso'   => 'bg-amber-500 text-slate-900 border-amber-400 shadow-amber-500/20',
                'info'    => 'bg-blue-600 text-white border-blue-500 shadow-blue-500/20'
            ];
            $icons = [
                'sucesso' => 'fa-check-circle',
                'erro'    => 'fa-times-circle',
                'aviso'   => 'fa-exclamation-triangle',
                'info'    => 'fa-info-circle'
            ];
            $cor = $bgColors[$toast['tipo']] ?? $bgColors['info'];
            $icone = $icons[$toast['tipo']] ?? $icons['info'];
        ?>
        <div class="toast-item pointer-events-auto flex items-start gap-3 p-4 rounded-xl shadow-xl border <?php echo $cor; ?> transition-all duration-300 transform translate-y-0 opacity-100">
            <div class="text-xl mt-0.5"><i class="fas <?php echo $icone; ?>"></i></div>
            <div class="flex-1">
                <h4 class="font-bold text-sm tracking-wide"><?php echo e($toast['titulo']); ?></h4>
                <p class="text-xs opacity-95 mt-0.5 leading-relaxed"><?php echo e($toast['mensagem']); ?></p>
            </div>
            <button onclick="this.parentElement.remove()" class="text-white/80 hover:text-white transition text-sm">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>
</div>
