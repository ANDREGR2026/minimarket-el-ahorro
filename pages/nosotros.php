<?php require_once __DIR__ . '/../components/public/header.php'; ?>
<?php require_once __DIR__ . '/../components/public/navbar.php'; ?>

<main class="bg-slate-50">
    <section class="border-b border-slate-200 bg-white py-16 sm:py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid gap-10 lg:grid-cols-2 lg:items-center">
            <div>
                <span class="text-sm font-bold uppercase tracking-[0.18em] text-marca-600">Sobre nosotros</span>
                <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">Un negocio local, hecho para su comunidad.</h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-slate-600"><?= e($nombreComercial) ?> nació para hacer más simples las compras de cada día: una tienda práctica, con productos necesarios y atención de confianza.</p>
                <p class="mt-5 max-w-xl leading-relaxed text-slate-600">Cuidamos la variedad, la frescura y el precio para que cada visita sea útil, rápida y agradable.</p>
            </div>
            <div><img src="<?= BASE_URL ?>assets/img/login-hero.jpg" alt="Interior de <?= e($nombreComercial) ?>" class="h-80 w-full rounded-2xl object-cover shadow-xl sm:h-96"><p class="mt-4 text-sm text-slate-500">Un lugar cercano para resolver las compras del hogar.</p></div>
        </div>
    </section>
    <section class="py-20"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid gap-6 md:grid-cols-3">
        <article class="tarjeta p-7"><h2 class="text-xl font-bold text-slate-900">Nuestra historia</h2><p class="mt-3 leading-relaxed text-slate-600">Empezamos con una idea clara: tener cerca una tienda donde encontrar lo necesario sin complicaciones.</p></article>
        <article class="tarjeta p-7"><h2 class="text-xl font-bold text-slate-900">Nuestra misión</h2><p class="mt-3 leading-relaxed text-slate-600">Ofrecer productos frescos, variedad y precios competitivos con una atención ágil y amable.</p></article>
        <article class="tarjeta p-7"><h2 class="text-xl font-bold text-slate-900">Nuestro compromiso</h2><p class="mt-3 leading-relaxed text-slate-600">Escuchar a nuestros clientes y mejorar cada día para seguir siendo una tienda en la que puedes confiar.</p></article>
    </div></section>
</main>
<?php require_once __DIR__ . '/../components/public/footer.php'; ?>
