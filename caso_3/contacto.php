<?php require_once __DIR__ . '/components/header.php'; ?>
<?php require_once __DIR__ . '/components/navbar.php'; ?>

<div class="bg-slate-50 py-16 min-h-[calc(100vh-200px)]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-12">
                <h1 class="text-3xl font-bold text-slate-900 mb-3">Contacto</h1>
                <p class="text-slate-600">¿Tienes dudas o comentarios? Escríbenos y te responderemos lo más pronto posible.</p>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                <form action="#" method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
                            <input type="text" class="w-full rounded-lg border-slate-300 ring-1 ring-slate-300 px-3 py-2 focus:ring-2 focus:ring-marca-500 focus:outline-none" placeholder="Tu nombre">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Correo Electrónico</label>
                            <input type="email" class="w-full rounded-lg border-slate-300 ring-1 ring-slate-300 px-3 py-2 focus:ring-2 focus:ring-marca-500 focus:outline-none" placeholder="tu@correo.com">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Mensaje</label>
                        <textarea rows="4" class="w-full rounded-lg border-slate-300 ring-1 ring-slate-300 px-3 py-2 focus:ring-2 focus:ring-marca-500 focus:outline-none" placeholder="¿En qué te podemos ayudar?"></textarea>
                    </div>
                    <div>
                        <button type="button" class="w-full md:w-auto px-8 py-3 bg-marca-600 text-white font-bold rounded-lg shadow hover:bg-marca-700 transition-colors">
                            Enviar Mensaje
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
