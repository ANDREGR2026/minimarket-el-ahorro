<?php 
require_once __DIR__ . '/components/header.php'; 
require_once __DIR__ . '/components/navbar.php'; 

// Datos simulados para presentación
$productos = [
    ['nombre' => 'Arroz Costeño Extra 5kg', 'categoria' => 'Abarrotes', 'precio_venta' => 18.50],
    ['nombre' => 'Aceite Primor Premium 1L', 'categoria' => 'Abarrotes', 'precio_venta' => 12.00],
    ['nombre' => 'Leche Gloria Evaporada 400g', 'categoria' => 'Lácteos', 'precio_venta' => 3.80],
    ['nombre' => 'Yogurt Gloria Fresa 1L', 'categoria' => 'Lácteos', 'precio_venta' => 6.50],
    ['nombre' => 'Galletas Oreo 36g', 'categoria' => 'Snacks', 'precio_venta' => 1.20],
    ['nombre' => 'Inca Kola 1.5L', 'categoria' => 'Bebidas', 'precio_venta' => 7.00],
    ['nombre' => 'Detergente Ariel 1kg', 'categoria' => 'Limpieza', 'precio_venta' => 14.50],
    ['nombre' => 'Papel Higiénico Suave', 'categoria' => 'Limpieza', 'precio_venta' => 15.00],
];
?>

<div class="bg-slate-50 py-12 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-10 text-center">
            <h1 class="text-3xl font-bold text-slate-900 mb-3">Nuestro Catálogo</h1>
            <p class="text-slate-600">Explora todos nuestros productos disponibles al mejor precio.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <?php foreach ($productos as $p): ?>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow flex flex-col">
                    <div class="p-4 bg-slate-100 flex justify-center items-center h-40">
                        <svg class="h-16 w-16 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <div class="p-5 flex flex-col flex-1">
                        <span class="text-xs font-semibold text-marca-600 uppercase tracking-wider mb-1"><?= htmlspecialchars($p['categoria']) ?></span>
                        <h3 class="text-sm font-bold text-slate-800 line-clamp-2 mb-2 flex-1" title="<?= htmlspecialchars($p['nombre']) ?>"><?= htmlspecialchars($p['nombre']) ?></h3>
                        <div class="mt-auto flex items-center justify-between">
                            <span class="text-lg font-extrabold text-slate-900">S/ <?= number_format($p['precio_venta'], 2) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
