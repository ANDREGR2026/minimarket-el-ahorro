/**
 * Lista desplegable propia del sistema.
 *
 * Mejora todos los <select class="campo"> de la página: los reemplaza por un
 * control con la apariencia del sistema, pero conserva el select nativo dentro
 * del documento como origen del valor. Gracias a eso:
 *
 *   - El formulario se envía exactamente igual que antes.
 *   - La validación del navegador (required) sigue funcionando.
 *   - Los onchange existentes se siguen disparando.
 *   - Si el JavaScript no carga, queda el select nativo, ya estilizado por CSS.
 *
 * Cuando la lista tiene muchas opciones aparece un buscador automáticamente.
 */
(function () {
    'use strict';

    const MINIMO_PARA_BUSCADOR = 8;

    const FLECHA = '<svg class="select-flecha" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">' +
        '<path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>';

    const CHECK = '<svg class="select-check" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">' +
        '<path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0l-3.5-3.5a1 1 0 1 1 1.4-1.4l2.8 2.79 6.8-6.79a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>';

    let contador = 0;
    let abierto = null;

    // ------------------------------------------------------------ utilidades

    function escapar(texto) {
        const div = document.createElement('div');
        div.textContent = texto == null ? '' : texto;
        return div.innerHTML;
    }

    /** Quita acentos y mayúsculas para que el buscador sea tolerante. */
    function normalizar(texto) {
        return (texto || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    // ------------------------------------------------------------ componente

    function crear(select) {
        if (select.dataset.selectListo === '1') return;
        select.dataset.selectListo = '1';

        contador += 1;
        const id = 'select-' + contador;

        // Las clases de ancho viajan a la envoltura; el resto del aspecto lo
        // toma el botón.
        const clasesSelect = select.className.split(/\s+/).filter(Boolean);
        const clasesEnvoltura = clasesSelect.filter(c => c !== 'campo');

        const envoltura = document.createElement('div');
        envoltura.className = ['select-envoltura'].concat(clasesEnvoltura).join(' ');

        select.parentNode.insertBefore(envoltura, select);
        envoltura.appendChild(select);
        select.setAttribute('tabindex', '-1');
        select.setAttribute('aria-hidden', 'true');

        // ---------------------------------------------------------- botón
        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'select-boton';
        boton.id = id + '-boton';
        boton.setAttribute('aria-haspopup', 'listbox');
        boton.setAttribute('aria-expanded', 'false');
        boton.innerHTML = '<span class="select-valor"></span>' + FLECHA;

        if (select.disabled) {
            boton.disabled = true;
            boton.classList.add('cursor-not-allowed', 'bg-slate-50', 'text-slate-400');
        }

        // Que la etiqueta <label for="..."> enfoque el botón
        const etiqueta = select.id
            ? document.querySelector('label[for="' + CSS.escape(select.id) + '"]')
            : null;

        if (etiqueta) {
            boton.setAttribute('aria-labelledby', (etiqueta.id || (etiqueta.id = id + '-etiqueta')) + ' ' + boton.id);
            etiqueta.addEventListener('click', function (e) {
                e.preventDefault();
                boton.focus();
            });
        }

        // ---------------------------------------------------------- panel
        const panel = document.createElement('div');
        panel.className = 'select-panel';
        panel.hidden = true;

        const opciones = Array.from(select.options);
        const conBuscador = opciones.length >= MINIMO_PARA_BUSCADOR;

        let buscador = null;

        if (conBuscador) {
            const caja = document.createElement('div');
            caja.className = 'select-buscador';
            caja.innerHTML = '<input type="text" placeholder="Buscar..." autocomplete="off" spellcheck="false">';
            panel.appendChild(caja);
            buscador = caja.querySelector('input');
        }

        const lista = document.createElement('div');
        lista.className = 'select-lista';
        lista.setAttribute('role', 'listbox');
        lista.id = id + '-lista';
        panel.appendChild(lista);

        const vacio = document.createElement('p');
        vacio.className = 'select-sin-resultados';
        vacio.textContent = 'Sin coincidencias.';
        vacio.hidden = true;
        panel.appendChild(vacio);

        // Una fila por cada opción del select nativo
        const filas = opciones.map((opcion, indice) => {
            const fila = document.createElement('div');
            fila.className = 'select-opcion';
            fila.setAttribute('role', 'option');
            fila.dataset.indice = String(indice);
            fila.dataset.buscar = normalizar(opcion.text);
            fila.innerHTML = '<span class="select-opcion-texto">' + escapar(opcion.text) + '</span>' + CHECK;

            if (opcion.disabled) {
                fila.setAttribute('aria-disabled', 'true');
            }

            lista.appendChild(fila);
            return fila;
        });

        envoltura.appendChild(boton);
        envoltura.appendChild(panel);

        let activa = -1;

        // ---------------------------------------------------------- pintar

        function pintar() {
            const indice = select.selectedIndex;
            const opcion = indice >= 0 ? opciones[indice] : null;
            const texto = opcion ? opcion.text : '';

            boton.querySelector('.select-valor').textContent = texto;
            // Una opción sin valor funciona como texto de ayuda: se ve apagada
            boton.dataset.vacio = (!opcion || opcion.value === '') ? 'true' : 'false';

            filas.forEach((fila, i) => {
                fila.setAttribute('aria-selected', i === indice ? 'true' : 'false');
            });
        }

        function filasVisibles() {
            return filas.filter(f => !f.hidden);
        }

        function marcarActiva(fila) {
            filas.forEach(f => { f.dataset.activa = 'false'; });

            if (!fila) {
                activa = -1;
                lista.removeAttribute('aria-activedescendant');
                return;
            }

            fila.dataset.activa = 'true';
            activa = Number(fila.dataset.indice);

            if (!fila.id) fila.id = id + '-opcion-' + fila.dataset.indice;
            lista.setAttribute('aria-activedescendant', fila.id);

            // Mantener la opción activa dentro del área visible
            const cajaLista = lista.getBoundingClientRect();
            const cajaFila = fila.getBoundingClientRect();

            if (cajaFila.bottom > cajaLista.bottom) {
                lista.scrollTop += cajaFila.bottom - cajaLista.bottom;
            } else if (cajaFila.top < cajaLista.top) {
                lista.scrollTop -= cajaLista.top - cajaFila.top;
            }
        }

        function filtrar(termino) {
            const buscado = normalizar(termino);
            let visibles = 0;

            filas.forEach(fila => {
                const coincide = buscado === '' || fila.dataset.buscar.indexOf(buscado) !== -1;
                fila.hidden = !coincide;
                if (coincide) visibles += 1;
            });

            vacio.hidden = visibles > 0;
            lista.hidden = visibles === 0;

            const primera = filasVisibles()[0];
            marcarActiva(primera || null);
        }

        // ---------------------------------------------------------- abrir y cerrar

        function ubicar() {
            // Si no hay espacio suficiente abajo, el panel se abre hacia arriba
            const caja = boton.getBoundingClientRect();
            const alto = panel.offsetHeight || 260;
            const espacioAbajo = window.innerHeight - caja.bottom;

            panel.classList.toggle(
                'select-panel-arriba',
                espacioAbajo < alto + 12 && caja.top > espacioAbajo
            );
        }

        function abrir() {
            if (boton.disabled || !panel.hidden) return;

            if (abierto && abierto !== cerrar) abierto();

            panel.hidden = false;
            boton.setAttribute('aria-expanded', 'true');
            abierto = cerrar;

            if (buscador) {
                buscador.value = '';
                filtrar('');
            }

            const seleccionada = filas[select.selectedIndex];
            marcarActiva(seleccionada && !seleccionada.hidden ? seleccionada : filasVisibles()[0] || null);

            ubicar();

            // El foco tiene que estar dentro de la envoltura para que funcionen
            // las flechas, aunque el desplegable se haya abierto por código
            if (buscador) {
                buscador.focus();
            } else {
                boton.focus();
            }
        }

        function cerrar(devolverFoco) {
            if (panel.hidden) return;

            panel.hidden = true;
            boton.setAttribute('aria-expanded', 'false');
            panel.classList.remove('select-panel-arriba');
            marcarActiva(null);

            if (abierto === cerrar) abierto = null;
            if (devolverFoco) boton.focus();
        }

        function elegir(indice) {
            const opcion = opciones[indice];
            if (!opcion || opcion.disabled) return;

            const cambio = select.selectedIndex !== indice;
            select.selectedIndex = indice;
            pintar();
            cerrar(true);

            // Se avisa al resto del sistema como lo haría el select nativo
            if (cambio) {
                select.dispatchEvent(new Event('input', { bubbles: true }));
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // ---------------------------------------------------------- eventos

        boton.addEventListener('click', function () {
            panel.hidden ? abrir() : cerrar(true);
        });

        boton.addEventListener('keydown', function (e) {
            // Con el desplegable abierto manda el manejador de la envoltura
            if (!panel.hidden) return;

            if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                // Evita que el mismo evento mueva además la opción activa
                e.stopPropagation();
                abrir();
                return;
            }

            // Escribir con el desplegable cerrado salta a la opción que empieza así
            if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
                const buscado = normalizar(e.key);
                const encontrada = filas.find(f => f.dataset.buscar.startsWith(buscado));
                if (encontrada) elegir(Number(encontrada.dataset.indice));
            }
        });

        lista.addEventListener('click', function (e) {
            const fila = e.target.closest('.select-opcion');
            if (fila && fila.getAttribute('aria-disabled') !== 'true') {
                elegir(Number(fila.dataset.indice));
            }
        });

        lista.addEventListener('mousemove', function (e) {
            const fila = e.target.closest('.select-opcion');
            if (fila && fila.dataset.activa !== 'true') marcarActiva(fila);
        });

        // Se escucha en la envoltura porque el foco puede estar en el botón
        // (listas cortas) o en el buscador (listas largas)
        envoltura.addEventListener('keydown', manejarTeclado);

        function manejarTeclado(e) {
            if (panel.hidden) return;

            const visibles = filasVisibles();
            if (visibles.length === 0 && e.key !== 'Escape' && e.key !== 'Tab') return;

            const posicion = visibles.findIndex(f => Number(f.dataset.indice) === activa);

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    marcarActiva(visibles[Math.min(posicion + 1, visibles.length - 1)] || visibles[0]);
                    break;

                case 'ArrowUp':
                    e.preventDefault();
                    marcarActiva(visibles[Math.max(posicion - 1, 0)] || visibles[0]);
                    break;

                case 'Home':
                    e.preventDefault();
                    marcarActiva(visibles[0]);
                    break;

                case 'End':
                    e.preventDefault();
                    marcarActiva(visibles[visibles.length - 1]);
                    break;

                case 'Enter':
                    e.preventDefault();
                    if (activa >= 0) elegir(activa);
                    break;

                case 'Escape':
                    e.preventDefault();
                    e.stopPropagation(); // no cerrar también el modal que lo contiene
                    cerrar(true);
                    break;

                case 'Tab':
                    cerrar(false);
                    break;
            }
        }

        if (buscador) {
            buscador.addEventListener('input', function () {
                filtrar(this.value);
            });
        }

        // Cerrar al hacer clic fuera
        document.addEventListener('mousedown', function (e) {
            if (!panel.hidden && !envoltura.contains(e.target)) cerrar(false);
        });

        window.addEventListener('resize', function () {
            if (!panel.hidden) ubicar();
        });

        /**
         * Las pantallas cambian el valor por código (por ejemplo al abrir el
         * modal de edición). Se intercepta la asignación para que el botón
         * refleje siempre lo que tiene el select.
         */
        const descriptorValor = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
        const descriptorIndice = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'selectedIndex');

        Object.defineProperty(select, 'value', {
            configurable: true,
            get() { return descriptorValor.get.call(this); },
            set(nuevo) {
                descriptorValor.set.call(this, nuevo);
                pintar();
            },
        });

        Object.defineProperty(select, 'selectedIndex', {
            configurable: true,
            get() { return descriptorIndice.get.call(this); },
            set(nuevo) {
                descriptorIndice.set.call(this, nuevo);
                pintar();
            },
        });

        // Si alguien dispara el change directamente sobre el select nativo
        select.addEventListener('change', pintar);

        pintar();
    }

    // ------------------------------------------------------------ arranque

    function inicializar(raiz) {
        (raiz || document).querySelectorAll('select.campo').forEach(crear);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => inicializar());
    } else {
        inicializar();
    }

    // Por si alguna pantalla agrega selects después de cargar
    window.SelectPersonalizado = { inicializar };
})();
