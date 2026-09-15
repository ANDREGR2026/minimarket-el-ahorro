<footer class="bg-slate-900 text-white pt-12 pb-8 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-marca-600 text-lg font-bold text-white">A</span>
                    <span class="text-xl font-bold"><?= htmlspecialchars($nombreComercial) ?></span>
                </div>
                <p class="text-slate-400 text-sm">
                    Ofreciendo los mejores productos para ti y tu familia desde nuestra fundación. Calidad y buenos precios cerca de ti.
                </p>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-4 text-white">Enlaces Rápidos</h3>
                <ul class="space-y-2 text-sm text-slate-400">
                    <li><a href="index.php" class="hover:text-marca-400 transition-colors">Inicio</a></li>
                    <li><a href="nosotros.php" class="hover:text-marca-400 transition-colors">Acerca de Nosotros</a></li>
                    <li><a href="productos.php" class="hover:text-marca-400 transition-colors">Catálogo de Productos</a></li>
                    <li><a href="contacto.php" class="hover:text-marca-400 transition-colors">Contacto</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-4 text-white">Contáctanos</h3>
                <ul class="space-y-3 text-sm text-slate-400">
                    <li class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-marca-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Av. Principal 123, Ciudad, País</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-marca-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span>+51 987 654 321</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-marca-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>contacto@<?= strtolower(str_replace(' ', '', htmlspecialchars($nombreComercial))) ?>.com</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="border-t border-slate-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-slate-500 text-sm">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($nombreComercial) ?>. Todos los derechos reservados.
            </p>
            <div class="flex space-x-4">
                <a href="#" class="text-slate-400 hover:text-white transition-colors">
                    <span class="sr-only">Facebook</span>
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd"/></svg>
                </a>
            </div>
        </div>
    </div>
</footer>
</body>
</html>
