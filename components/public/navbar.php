<?php $rutaPublica = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/'); ?>
<nav class="border-b border-marca-500/40 bg-marca-700/95 shadow-sm backdrop-blur sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-[4.5rem]">
            <div class="flex items-center">
                <a href="<?= BASE_URL ?>" class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl bg-white/15 ring-1 ring-white/20" aria-hidden="true"><img src="<?= BASE_URL ?>assets/img/favicon.svg" alt="" class="h-full w-full"></span>
                    <span><span class="block text-lg font-bold leading-tight text-white"><?= e($nombreComercial) ?></span><span class="block text-[10px] font-medium uppercase tracking-[0.16em] text-marca-200">Minimarket</span></span>
                </a>
            </div>
            <div class="ml-auto hidden items-center space-x-1 md:flex">
                <a href="<?= BASE_URL ?>" class="rounded-lg px-3 py-2 text-sm font-semibold <?= $rutaPublica === 'MINIMARKET' || $rutaPublica === '' ? 'bg-white/15 text-white' : 'text-marca-100 hover:bg-white/10 hover:text-white' ?> transition">Inicio</a>
                <a href="<?= BASE_URL ?>nosotros" class="rounded-lg px-3 py-2 text-sm font-semibold <?= str_ends_with($rutaPublica, 'nosotros') ? 'bg-white/15 text-white' : 'text-marca-100 hover:bg-white/10 hover:text-white' ?> transition">Nosotros</a>
                <a href="<?= BASE_URL ?>tienda" class="rounded-lg px-3 py-2 text-sm font-semibold <?= str_ends_with($rutaPublica, 'tienda') ? 'bg-white/15 text-white' : 'text-marca-100 hover:bg-white/10 hover:text-white' ?> transition">Productos</a>
                <a href="<?= BASE_URL ?>galeria" class="rounded-lg px-3 py-2 text-sm font-semibold <?= str_ends_with($rutaPublica, 'galeria') ? 'bg-white/15 text-white' : 'text-marca-100 hover:bg-white/10 hover:text-white' ?> transition">Galería</a>
                <a href="<?= BASE_URL ?>contacto" class="rounded-lg px-3 py-2 text-sm font-semibold <?= str_ends_with($rutaPublica, 'contacto') ? 'bg-white/15 text-white' : 'text-marca-100 hover:bg-white/10 hover:text-white' ?> transition">Contacto</a>
            </div>
            <div class="ml-3 hidden items-center md:flex">
                <a href="<?= BASE_URL ?>login" class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-marca-700 shadow-sm transition hover:bg-marca-50 focus:outline-none focus:ring-2 focus:ring-white/70">
                    Acceder al Sistema
                </a>
            </div>
            
            <!-- Botón menú móvil -->
            <div class="flex items-center md:hidden">
                <button type="button" id="btn-menu-mobile" class="text-white hover:text-slate-200 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Menú móvil (oculto por defecto) -->
    <div id="menu-mobile" class="hidden md:hidden bg-marca-700 pb-4 pt-2 shadow-inner">
        <div class="flex flex-col space-y-2 px-4">
            <a href="<?= BASE_URL ?>" class="text-white font-medium block py-2">Inicio</a>
            <a href="<?= BASE_URL ?>nosotros" class="text-marca-100 hover:text-white font-medium block py-2">Nosotros</a>
            <a href="<?= BASE_URL ?>tienda" class="text-marca-100 hover:text-white font-medium block py-2">Productos</a>
            <a href="<?= BASE_URL ?>galeria" class="text-marca-100 hover:text-white font-medium block py-2">Galería</a>
            <a href="<?= BASE_URL ?>contacto" class="text-marca-100 hover:text-white font-medium block py-2">Contacto</a>
            <div class="mt-1 flex w-full justify-center border-t border-marca-500 pt-4">
                <a href="<?= BASE_URL ?>login" class="inline-flex items-center justify-center font-semibold text-marca-100 transition hover:text-white">
                    Acceder al sistema
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
    document.getElementById('btn-menu-mobile').addEventListener('click', function() {
        document.getElementById('menu-mobile').classList.toggle('hidden');
    });
</script>
