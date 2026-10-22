<?php
/**
 * Cabeçalho Padrão do Sistema
 * Inclui Tailwind CSS, FontAwesome e Configurações Globais
 */
require_once __DIR__ . '/../bd.php';
$usuarioLogado = obterUsuarioLogado();
$tituloSite = isset($paginaTitulo) ? $paginaTitulo . " - Chaveamento Automático" : "Chaveamento Automático - Gerador de Torneios Esportivos";
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-950 text-slate-100 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($tituloSite); ?></title>

    <!-- Tailwind CSS (CDN Oficial) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        display: ['Outfit', 'sans-serif']
                    }
                }
            }
        }
    </script>

    <!-- Fontes Google (Inter & Outfit) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- FontAwesome Ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Estilos Customizados -->
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="min-h-full flex flex-col bg-slate-950 text-slate-100 selection:bg-emerald-500 selection:text-white">

<?php include __DIR__ . '/navbar.php'; ?>
<?php include __DIR__ . '/toast.php'; ?>
<?php include __DIR__ . '/modal.php'; ?>
