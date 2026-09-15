<?php require_once __DIR__ . '/../components/public/header.php'; ?>
<?php require_once __DIR__ . '/../components/public/navbar.php'; ?>

<div class="bg-slate-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-extrabold text-slate-900 mb-4">Sobre Nosotros</h1>
            <p class="text-lg text-slate-600 max-w-2xl mx-auto">Conoce la historia detrás de <?= e($nombreComercial) ?> y nuestra misión de servir a la comunidad.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div>
                <img src="<?= BASE_URL ?>assets/img/login-hero.jpg" alt="Nuestra tienda" class="rounded-2xl shadow-lg w-full h-96 object-cover">
            </div>
            <div>
                <h2 class="text-2xl font-bold text-marca-700 mb-4">Nuestra Historia</h2>
                <p class="text-slate-600 mb-6 leading-relaxed">
                    Fundado con la convicción de llevar los mejores productos a los hogares de nuestra ciudad, <?= e($nombreComercial) ?> comenzó como un pequeño emprendimiento familiar. Hoy nos enorgullecemos de ser el minimarket líder de la zona, ofreciendo calidad, variedad y un excelente servicio.
                </p>
                <h2 class="text-2xl font-bold text-marca-700 mb-4">Nuestra Misión</h2>
                <p class="text-slate-600 leading-relaxed">
                    Brindar a nuestros clientes una experiencia de compra cómoda, rápida y segura, con productos frescos y precios competitivos todos los días del año.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../components/public/footer.php'; ?>
