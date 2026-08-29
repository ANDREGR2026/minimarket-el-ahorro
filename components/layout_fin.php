            </main>
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
