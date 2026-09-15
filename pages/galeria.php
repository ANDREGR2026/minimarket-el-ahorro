<?php require_once __DIR__ . '/../components/public/header.php'; ?>
<?php require_once __DIR__ . '/../components/public/navbar.php'; ?>

<div class="bg-slate-50 py-16 min-h-[calc(100vh-200px)]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h1 class="text-3xl font-bold text-slate-900 mb-3">Galería</h1>
            <p class="text-slate-600">Un vistazo a nuestras instalaciones y los productos que ofrecemos.</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <img src="<?= BASE_URL ?>assets/img/login-hero.jpg" class="rounded-xl shadow w-full h-64 object-cover" alt="Nuestra tienda 1">
            <img src="<?= BASE_URL ?>assets/img/login-hero.jpg" class="rounded-xl shadow w-full h-64 object-cover opacity-90" alt="Nuestra tienda 2">
            <img src="<?= BASE_URL ?>assets/img/login-hero.jpg" class="rounded-xl shadow w-full h-64 object-cover opacity-80" alt="Nuestra tienda 3">
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../components/public/footer.php'; ?>
