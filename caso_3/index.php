<?php require_once __DIR__ . '/components/header.php'; ?>
<?php require_once __DIR__ . '/components/navbar.php'; ?>

<!-- Hero Section -->
<section class="relative bg-marca-700 overflow-hidden">
    <!-- Imagen de fondo oscura -->
    <div class="absolute inset-0">
        <img src="assets/img/login-hero.jpg" alt="Minimarket interior" class="w-full h-full object-cover opacity-20">
    </div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32 flex flex-col items-center text-center">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-white tracking-tight mb-6">
            Bienvenido a <span class="text-marca-200"><?= htmlspecialchars($nombreComercial) ?></span>
        </h1>
        <p class="text-xl md:text-2xl text-marca-100 max-w-3xl mb-10 leading-relaxed">
            Los mejores productos para tu hogar. Frescura, calidad y precios increíbles cerca de ti.
        </p>
        <div class="flex flex-col sm:flex-row gap-4">
            <a href="productos.php" class="px-8 py-3 bg-white text-marca-700 font-bold rounded-lg shadow-lg hover:bg-slate-50 transition-all text-lg">
                Ver Catálogo
            </a>
            <a href="nosotros.php" class="px-8 py-3 bg-marca-600 border border-marca-400 text-white font-bold rounded-lg shadow-lg hover:bg-marca-500 transition-all text-lg">
                Conócenos
            </a>
        </div>
    </div>
</section>

<!-- Categorías Destacadas o Sección Informativa -->
<section class="py-20 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-slate-900">¿Por qué elegirnos?</h2>
            <p class="mt-4 text-lg text-slate-600 max-w-2xl mx-auto">Nos esforzamos por brindarte la mejor experiencia de compra todos los días.</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100 text-center hover:shadow-md transition-shadow">
                <div class="w-16 h-16 bg-marca-100 text-marca-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Atención Rápida</h3>
                <p class="text-slate-600">Nuestro personal está siempre dispuesto a ayudarte para que tus compras sean rápidas y eficientes.</p>
            </div>
            
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100 text-center hover:shadow-md transition-shadow">
                <div class="w-16 h-16 bg-marca-100 text-marca-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Calidad Garantizada</h3>
                <p class="text-slate-600">Seleccionamos cuidadosamente cada producto para asegurarnos de llevar lo mejor a tu mesa.</p>
            </div>
            
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100 text-center hover:shadow-md transition-shadow">
                <div class="w-16 h-16 bg-marca-100 text-marca-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Precios Bajos</h3>
                <p class="text-slate-600">Ofrecemos precios competitivos y ofertas exclusivas todos los días de la semana.</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to action -->
<section class="bg-marca-600 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white mb-6">¿Deseas visitar nuestra tienda?</h2>
        <p class="text-marca-100 text-lg mb-8 max-w-2xl mx-auto">Te esperamos todos los días desde las 8:00 AM hasta las 10:00 PM. Encuentra todo lo que necesitas en un solo lugar.</p>
        <a href="contacto.php" class="inline-block px-8 py-3 bg-white text-marca-700 font-bold rounded-lg shadow-lg hover:bg-slate-50 transition-all text-lg">
            Ver Ubicación
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/components/footer.php'; ?>
