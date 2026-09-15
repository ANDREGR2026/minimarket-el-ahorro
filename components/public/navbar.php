<nav class="bg-marca-600 shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="<?= BASE_URL ?>" class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-xl font-bold text-white">A</span>
                    <span class="text-xl font-bold text-white"><?= e($nombreComercial) ?></span>
                </a>
            </div>
            <div class="hidden md:flex items-center space-x-8">
                <a href="<?= BASE_URL ?>" class="text-white hover:text-marca-100 font-medium transition-colors">Inicio</a>
                <a href="<?= BASE_URL ?>nosotros" class="text-marca-100 hover:text-white font-medium transition-colors">Nosotros</a>
                <a href="<?= BASE_URL ?>tienda" class="text-marca-100 hover:text-white font-medium transition-colors">Productos</a>
                <a href="<?= BASE_URL ?>galeria" class="text-marca-100 hover:text-white font-medium transition-colors">Galería</a>
                <a href="<?= BASE_URL ?>contacto" class="text-marca-100 hover:text-white font-medium transition-colors">Contacto</a>
            </div>
            <div class="hidden md:flex items-center">
                <a href="<?= BASE_URL ?>login" class="bg-white text-marca-600 hover:bg-slate-50 px-5 py-2 rounded-lg font-bold shadow-sm transition-all">
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
            <hr class="border-marca-500 my-2">
            <a href="<?= BASE_URL ?>login" class="bg-white text-center text-marca-600 hover:bg-slate-50 px-5 py-2 rounded-lg font-bold block mt-2">
                Acceder al Sistema
            </a>
        </div>
    </div>
</nav>

<script>
    document.getElementById('btn-menu-mobile').addEventListener('click', function() {
        document.getElementById('menu-mobile').classList.toggle('hidden');
    });
</script>
