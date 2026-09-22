<?php
require_once __DIR__ . '/components/public/header.php';
require_once __DIR__ . '/components/public/navbar.php';
require_once __DIR__ . '/models/Producto.php';
require_once __DIR__ . '/models/Categoria.php';

$productosDestacados = array_slice((new Producto())->listar(['solo_activos' => true]), 0, 4);
$categoriasPublicas = array_filter((new Categoria())->listar('', true), static fn($categoria) => (int) $categoria['total_productos'] > 0);
?>

<main class="bg-white">
    <section class="border-b border-slate-200 bg-slate-950">
        <div class="grid min-h-[500px] lg:grid-cols-2">
            <div class="flex items-center bg-slate-950 px-6 py-16 sm:px-12 lg:justify-end lg:px-16">
                <div class="max-w-xl">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-marca-300"><?= e($nombreComercial) ?> · Minimarket</p>
                    <h1 class="mt-5 text-4xl font-extrabold tracking-tight text-white sm:text-5xl">Tu compra diaria, bien resuelta.</h1>
                    <p class="mt-5 max-w-lg text-lg leading-relaxed text-slate-300">Encuentra productos de uso diario, precios visibles y una atención que te ayuda a salir rápido.</p>
                    <div class="mt-8 flex flex-wrap gap-3"><a href="#catalogo" class="rounded-lg bg-marca-500 px-5 py-3 text-sm font-bold text-white transition hover:bg-marca-400">Ver productos</a><a href="<?= BASE_URL ?>contacto" class="rounded-lg border border-slate-600 px-5 py-3 text-sm font-bold text-slate-100 transition hover:border-slate-400 hover:bg-slate-900">Cómo llegar</a></div>
                    <dl class="mt-12 grid max-w-md grid-cols-2 gap-6 border-t border-slate-800 pt-6"><div><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Horario</dt><dd class="mt-2 text-sm font-semibold text-white">Todos los días<br>8:00 a. m. – 10:00 p. m.</dd></div><div><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Teléfono</dt><dd class="mt-2 text-sm font-semibold text-white"><?= e($telefonoComercial) ?></dd></div></dl>
                </div>
            </div>
            <div class="relative min-h-[320px] overflow-hidden"><img src="<?= BASE_URL ?>assets/img/login-hero.jpg" alt="Interior de <?= e($nombreComercial) ?>" class="absolute inset-0 h-full w-full object-cover"><div class="absolute inset-0 bg-marca-900/15"></div><div class="absolute bottom-0 left-0 right-0 bg-slate-950/85 px-6 py-5 text-sm text-white sm:px-8"><span class="font-bold">Cerca de ti.</span> <?= e($direccionComercial) ?></div></div>
        </div>
    </section>

    <?php if (!empty($categoriasPublicas)): ?>
    <section class="border-b border-slate-200 bg-slate-50"><div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8"><div class="public-categories flex gap-3 overflow-x-auto pb-1"><a href="<?= BASE_URL ?>tienda" class="shrink-0 rounded-md bg-slate-900 px-4 py-2 text-sm font-bold text-white">Todo el catálogo</a><?php foreach ($categoriasPublicas as $categoria): ?><a href="<?= BASE_URL ?>tienda?categoria=<?= (int) $categoria['id_categoria'] ?>" class="shrink-0 rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-marca-400 hover:text-marca-700"><?= e($categoria['nombre']) ?> <span class="ml-1 text-slate-400"><?= (int) $categoria['total_productos'] ?></span></a><?php endforeach; ?></div></div></section>
    <?php endif; ?>

    <section id="catalogo" class="py-16 sm:py-20"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-200 pb-6"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-marca-600">Selección de hoy</p><h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Productos disponibles</h2></div><a href="<?= BASE_URL ?>tienda" class="text-sm font-bold text-marca-700 hover:text-marca-900">Buscar en el catálogo →</a></div>
        <?php if (!empty($productosDestacados)): ?><div class="mt-8 grid divide-y divide-slate-200 border-y border-slate-200 sm:grid-cols-2 sm:divide-x sm:divide-y-0 lg:grid-cols-4"><?php foreach ($productosDestacados as $producto): ?><article class="p-5 first:pl-0 sm:first:pl-5 lg:first:pl-0"><p class="text-xs font-bold uppercase tracking-wide text-marca-600"><?= e($producto['categoria']) ?></p><h3 class="mt-3 min-h-11 font-bold leading-snug text-slate-800"><?= e($producto['nombre']) ?></h3><p class="mt-5 text-xl font-extrabold text-slate-900"><?= e($monedaComercial) ?> <?= number_format($producto['precio_venta'], 2) ?></p><p class="mt-1 text-xs text-slate-500">Por <?= strtolower(e($producto['unidad_medida'])) ?></p></article><?php endforeach; ?></div><?php else: ?><p class="py-10 text-slate-500">El catálogo se está actualizando.</p><?php endif; ?>
    </div></section>

    <section class="bg-slate-100 py-16"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid gap-8 lg:grid-cols-[1fr_auto] lg:items-center"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-marca-600">Visítanos</p><h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Todo lo esencial, a pocos pasos.</h2><p class="mt-3 max-w-2xl text-slate-600">Estamos en <?= e($direccionComercial) ?>. Si necesitas confirmar la disponibilidad de un producto, llámanos antes de venir.</p></div><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $telefonoComercial)) ?>" class="inline-flex w-fit items-center justify-center rounded-lg bg-slate-900 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-700">Llamar: <?= e($telefonoComercial) ?></a></div></section>
</main>

<?php require_once __DIR__ . '/components/public/footer.php'; ?>
