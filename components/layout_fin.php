            </main>

            <footer class="border-t border-slate-200 bg-white px-4 py-4 text-center text-xs text-slate-400 sm:px-6 no-imprimir">
                Sistema Web para la Gestión de un Minimarket &middot;
                Proyecto del curso de Desarrollo de Sistemas de Información &middot;
                <?= date('Y') ?>
            </footer>
        </div>
    </div>

    <script>
        // Menú lateral en pantallas pequeñas
        (function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const boton = document.getElementById('btn-menu');

            function abrir() {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            }

            function cerrar() {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }

            if (boton) boton.addEventListener('click', abrir);
            if (overlay) overlay.addEventListener('click', cerrar);
        })();
    </script>

    <!-- Listas desplegables propias del sistema -->
    <script src="<?= BASE_URL ?>assets/js/select.js"></script>

    <?php if (!empty($scripts)): ?>
        <?php foreach ((array) $scripts as $script): ?>
            <script src="<?= BASE_URL . $script ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>

</html>
