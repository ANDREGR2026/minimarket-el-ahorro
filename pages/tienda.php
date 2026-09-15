<?php 
require_once __DIR__ . '/../components/public/header.php'; 
require_once __DIR__ . '/../components/public/navbar.php'; 
require_once __DIR__ . '/../models/Producto.php';

$productoModel = new Producto();
$productos = $productoModel->listar(['estado' => 'ACTIVO']);
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
                        <span class="text-xs font-semibold text-marca-600 uppercase tracking-wider mb-1"><?= e($p['categoria']) ?></span>
                        <h3 class="text-sm font-bold text-slate-800 line-clamp-2 mb-2 flex-1" title="<?= e($p['nombre']) ?>"><?= e($p['nombre']) ?></h3>
                        <div class="mt-auto flex items-center justify-between">
                            <span class="text-lg font-extrabold text-slate-900">S/ <?= number_format($p['precio_venta'], 2) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($productos)): ?>
                <div class="col-span-full py-12 text-center text-slate-500">
                    No hay productos disponibles en este momento.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../components/public/footer.php'; ?>
