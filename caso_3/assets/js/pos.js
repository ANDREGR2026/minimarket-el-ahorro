/**
 * Punto de venta.
 *
 * El carrito vive solo en el navegador; al cobrar se envía al servidor, que
 * vuelve a leer los precios y el stock de la base de datos antes de grabar.
 */
(function () {
    'use strict';

    const cfg = window.POS;
    const FACTOR_IGV = 1 + cfg.igv / 100;

    /** @type {{id_producto:number, nombre:string, precio:number, cantidad:number, stock:number, unidad:string}[]} */
    let carrito = [];
    let cliente = null;
    let cobrando = false;

    // ------------------------------------------------------------ elementos
    const el = {
        buscador: document.getElementById('buscador'),
        resultados: document.getElementById('resultados'),
        carrito: document.getElementById('carrito'),
        vaciar: document.getElementById('btn-vaciar'),

        buscadorCliente: document.getElementById('buscador-cliente'),
        resultadosCliente: document.getElementById('resultados-cliente'),
        clienteElegido: document.getElementById('cliente-elegido'),
        clienteNombre: document.getElementById('cliente-nombre'),
        clienteDocumento: document.getElementById('cliente-documento'),
        quitarCliente: document.getElementById('btn-quitar-cliente'),

        avisoFactura: document.getElementById('aviso-factura'),
        opcionBoleta: document.getElementById('opcion-boleta'),
        opcionFactura: document.getElementById('opcion-factura'),

        metodoPago: document.getElementById('metodo_pago'),
        bloqueEfectivo: document.getElementById('bloque-efectivo'),
        montoPagado: document.getElementById('monto_pagado'),
        exacto: document.getElementById('btn-exacto'),

        tSubtotal: document.getElementById('t-subtotal'),
        tIgv: document.getElementById('t-igv'),
        tTotal: document.getElementById('t-total'),
        tVuelto: document.getElementById('t-vuelto'),

        cobrar: document.getElementById('btn-cobrar'),
        mensaje: document.getElementById('mensaje-venta'),

        modalExito: document.getElementById('modal-exito'),
        exitoComprobante: document.getElementById('exito-comprobante'),
        exitoTotal: document.getElementById('exito-total'),
        exitoVuelto: document.getElementById('exito-vuelto'),
        exitoImprimir: document.getElementById('exito-imprimir'),
        nuevaVenta: document.getElementById('btn-nueva-venta'),

        btnCamara: document.getElementById('btn-camara'),
        modalCamara: document.getElementById('modal-camara'),
        btnCerrarCamara: document.getElementById('btn-cerrar-camara'),
        videoCamara: document.getElementById('video-camara'),
        camaraMensaje: document.getElementById('camara-mensaje')
    };

    // ------------------------------------------------------------ utilidades
    function soles(monto) {
        return 'S/ ' + Number(monto).toFixed(2);
    }

    function escapar(texto) {
        const div = document.createElement('div');
        div.textContent = texto == null ? '' : texto;
        return div.innerHTML;
    }

    function mostrarMensaje(texto, tipo) {
        el.mensaje.textContent = texto;
        el.mensaje.className = 'mt-2 text-center text-xs font-medium ' +
            (tipo === 'error' ? 'text-red-600' : 'text-emerald-600');
        el.mensaje.classList.remove('hidden');

        if (tipo !== 'error') {
            setTimeout(() => el.mensaje.classList.add('hidden'), 3000);
        }
    }

    function limpiarMensaje() {
        el.mensaje.classList.add('hidden');
    }

    /** Retrasa la ejecución hasta que el usuario deja de escribir. */
    function conRetraso(fn, ms) {
        let temporizador;
        return function (...args) {
            clearTimeout(temporizador);
            temporizador = setTimeout(() => fn.apply(this, args), ms);
        };
    }

    function tipoComprobante() {
        return document.querySelector('input[name="tipo_comprobante"]:checked').value;
    }

    // ------------------------------------------------------------ carrito
    function agregarProducto(producto) {
        const id = Number(producto.id_producto);
        const stock = Number(producto.stock);

        if (stock <= 0) {
            mostrarMensaje('"' + producto.nombre + '" no tiene stock disponible.', 'error');
            return;
        }

        const existente = carrito.find(linea => linea.id_producto === id);

        if (existente) {
            if (existente.cantidad + 1 > stock) {
                mostrarMensaje('Solo hay ' + stock + ' unidades de "' + producto.nombre + '".', 'error');
                return;
            }
            existente.cantidad += 1;
        } else {
            carrito.push({
                id_producto: id,
                nombre: producto.nombre,
                precio: Number(producto.precio_venta),
                cantidad: 1,
                stock: stock,
                unidad: producto.unidad_medida || 'UNIDAD'
            });
        }

        limpiarMensaje();
        pintarCarrito();
        cerrarResultados();
        el.buscador.value = '';
        el.buscador.focus();
    }

    function cambiarCantidad(id, cantidad) {
        const linea = carrito.find(l => l.id_producto === id);
        if (!linea) return;

        cantidad = parseInt(cantidad, 10);

        if (isNaN(cantidad) || cantidad <= 0) {
            quitarLinea(id);
            return;
        }

        if (cantidad > linea.stock) {
            mostrarMensaje('Solo hay ' + linea.stock + ' unidades de "' + linea.nombre + '".', 'error');
            cantidad = linea.stock;
        }

        linea.cantidad = cantidad;
        pintarCarrito();
    }

    function quitarLinea(id) {
        carrito = carrito.filter(l => l.id_producto !== id);
        pintarCarrito();
    }

    function pintarCarrito() {
        if (carrito.length === 0) {
            el.carrito.innerHTML =
                '<tr><td colspan="5" class="px-4 py-12 text-center text-slate-400">' +
                'El carrito está vacío. Busque un producto para empezar.</td></tr>';
        } else {
            el.carrito.innerHTML = carrito.map(linea => `
                <tr>
                    <td>
                        <p class="font-medium text-slate-800">${escapar(linea.nombre)}</p>
                        <p class="text-xs text-slate-400">stock: ${linea.stock} ${escapar(linea.unidad.toLowerCase())}</p>
                    </td>
                    <td class="text-right">${soles(linea.precio)}</td>
                    <td class="text-center">
                        <div class="inline-flex items-center gap-1">
                            <button type="button" class="btn-secundario btn-sm menos" data-id="${linea.id_producto}">−</button>
                            <input type="number" min="1" max="${linea.stock}" value="${linea.cantidad}"
                                class="campo w-16 py-1 text-center cantidad" data-id="${linea.id_producto}">
                            <button type="button" class="btn-secundario btn-sm mas" data-id="${linea.id_producto}">+</button>
                        </div>
                    </td>
                    <td class="text-right font-semibold text-slate-800">
                        ${soles(linea.precio * linea.cantidad)}
                    </td>
                    <td class="text-right">
                        <button type="button" class="btn-peligro btn-sm quitar" data-id="${linea.id_producto}">
                            Quitar
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        calcularTotales();
    }

    // ------------------------------------------------------------ totales
    function calcularTotales() {
        const total = carrito.reduce((suma, l) => suma + l.precio * l.cantidad, 0);

        // El precio ya incluye IGV: se descompone hacia atrás (RN-03)
        const subtotal = total / FACTOR_IGV;
        const igv = total - subtotal;

        el.tSubtotal.textContent = soles(subtotal);
        el.tIgv.textContent = soles(igv);
        el.tTotal.textContent = soles(total);

        calcularVuelto();

        el.cobrar.disabled = carrito.length === 0 || cobrando;

        return total;
    }

    function totalActual() {
        return carrito.reduce((suma, l) => suma + l.precio * l.cantidad, 0);
    }

    function calcularVuelto() {
        const total = totalActual();

        if (el.metodoPago.value !== 'EFECTIVO') {
            el.tVuelto.textContent = soles(0);
            return;
        }

        const pagado = parseFloat(el.montoPagado.value) || 0;
        const vuelto = pagado - total;

        el.tVuelto.textContent = soles(vuelto > 0 ? vuelto : 0);
        el.tVuelto.parentElement.className = vuelto < 0
            ? 'flex justify-between rounded-lg bg-red-50 px-3 py-2 text-sm font-semibold text-red-800'
            : 'flex justify-between rounded-lg bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800';
    }

    // ------------------------------------------------------------ búsqueda
    function cerrarResultados() {
        el.resultados.classList.add('hidden');
        el.resultados.innerHTML = '';
    }

    async function buscarProductos(termino) {
        if (termino.length < 2) {
            cerrarResultados();
            return;
        }

        try {
            const r = await fetch(cfg.baseUrl + 'api/buscar_producto?q=' + encodeURIComponent(termino),
                { credentials: 'same-origin' });
            const datos = await r.json();

            if (!datos.ok || datos.productos.length === 0) {
                el.resultados.innerHTML =
                    '<p class="px-4 py-4 text-center text-sm text-slate-400">Sin coincidencias.</p>';
                el.resultados.classList.remove('hidden');
                return;
            }

            el.resultados.innerHTML = datos.productos.map(p => `
                <button type="button" data-producto='${JSON.stringify(p).replace(/'/g, '&#39;')}'
                    class="resultado flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-2.5 text-left hover:bg-slate-50 last:border-0">
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium text-slate-800">${escapar(p.nombre)}</span>
                        <span class="block text-xs text-slate-400">${escapar(p.categoria)} · ${escapar(p.codigo_barras)}</span>
                    </span>
                    <span class="shrink-0 text-right">
                        <span class="block text-sm font-semibold text-slate-800">${soles(p.precio_venta)}</span>
                        <span class="block text-xs ${Number(p.stock) <= 0 ? 'text-red-500' : 'text-slate-400'}">
                            stock: ${p.stock}
                        </span>
                    </span>
                </button>
            `).join('');

            el.resultados.classList.remove('hidden');
        } catch (e) {
            mostrarMensaje('No se pudo buscar el producto.', 'error');
        }
    }

    /** Búsqueda exacta: es lo que dispara el lector de código de barras. */
    async function buscarPorCodigo(codigo) {
        try {
            const r = await fetch(cfg.baseUrl + 'api/buscar_producto?codigo=' + encodeURIComponent(codigo),
                { credentials: 'same-origin' });
            const datos = await r.json();

            if (!datos.ok) {
                mostrarMensaje(datos.mensaje, 'error');
                return;
            }

            agregarProducto(datos.producto);
        } catch (e) {
            mostrarMensaje('No se pudo leer el código de barras.', 'error');
        }
    }

    // ------------------------------------------------------------ escaneo por cámara
    /**
     * Lector de código de barras/QR por cámara, usando la BarcodeDetector API
     * nativa del navegador (Chrome/Edge en escritorio y Android). No depende
     * de ninguna librería externa. En navegadores sin soporte (Safari,
     * Firefox) se avisa al usuario y queda el lector físico como alternativa.
     */
    let streamCamara = null;
    let detectorActivo = false;

    function soportaEscaneoPorCamara() {
        return 'BarcodeDetector' in window && navigator.mediaDevices && navigator.mediaDevices.getUserMedia;
    }

    async function abrirEscanerCamara() {
        el.modalCamara.classList.replace('hidden', 'flex');
        el.camaraMensaje.textContent = 'Apunte la cámara al código de barras del producto.';

        if (!soportaEscaneoPorCamara()) {
            el.camaraMensaje.textContent =
                'Este navegador no soporta escaneo por cámara. Use un lector físico o actualice a Chrome/Edge.';
            return;
        }

        try {
            streamCamara = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' }
            });
            el.videoCamara.srcObject = streamCamara;
            detectorActivo = true;

            const detector = new window.BarcodeDetector({
                formats: ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'code_39', 'qr_code']
            });

            const leerCuadro = async () => {
                if (!detectorActivo) return;

                try {
                    const codigos = await detector.detect(el.videoCamara);
                    if (codigos.length > 0) {
                        const valor = codigos[0].rawValue.trim();
                        cerrarEscanerCamara();
                        if (valor) buscarPorCodigo(valor);
                        return;
                    }
                } catch (e) {
                    // Cuadro no legible: se reintenta con el siguiente.
                }

                if (detectorActivo) requestAnimationFrame(leerCuadro);
            };

            requestAnimationFrame(leerCuadro);
        } catch (e) {
            el.camaraMensaje.textContent =
                'No se pudo acceder a la cámara. Revise los permisos del navegador.';
        }
    }

    function cerrarEscanerCamara() {
        detectorActivo = false;

        if (streamCamara) {
            streamCamara.getTracks().forEach(track => track.stop());
            streamCamara = null;
        }

        el.videoCamara.srcObject = null;
        el.modalCamara.classList.replace('flex', 'hidden');
    }

    if (el.btnCamara) {
        el.btnCamara.addEventListener('click', abrirEscanerCamara);
        el.btnCerrarCamara.addEventListener('click', cerrarEscanerCamara);
    }

    // ------------------------------------------------------------ clientes
    async function buscarClientes(termino) {
        if (termino.length < 2) {
            el.resultadosCliente.classList.add('hidden');
            return;
        }

        try {
            const r = await fetch(cfg.baseUrl + 'api/buscar_cliente?q=' + encodeURIComponent(termino),
                { credentials: 'same-origin' });
            const datos = await r.json();

            if (!datos.ok || datos.clientes.length === 0) {
                el.resultadosCliente.innerHTML =
                    '<p class="px-4 py-3 text-center text-sm text-slate-400">Sin coincidencias.</p>';
                el.resultadosCliente.classList.remove('hidden');
                return;
            }

            el.resultadosCliente.innerHTML = datos.clientes.map(c => `
                <button type="button" data-cliente='${JSON.stringify(c).replace(/'/g, '&#39;')}'
                    class="resultado-cliente block w-full border-b border-slate-100 px-4 py-2 text-left hover:bg-slate-50 last:border-0">
                    <span class="block truncate text-sm font-medium text-slate-800">${escapar(c.nombre_completo)}</span>
                    <span class="block text-xs text-slate-400">${escapar(c.tipo_documento)} ${escapar(c.numero_documento)}</span>
                </button>
            `).join('');

            el.resultadosCliente.classList.remove('hidden');
        } catch (e) {
            mostrarMensaje('No se pudo buscar el cliente.', 'error');
        }
    }

    function elegirCliente(datos) {
        cliente = datos;

        el.clienteNombre.textContent = datos.nombre_completo;
        el.clienteDocumento.textContent = datos.tipo_documento + ' ' + datos.numero_documento;
        el.clienteElegido.classList.remove('hidden');
        el.buscadorCliente.value = '';
        el.resultadosCliente.classList.add('hidden');

        actualizarAvisoFactura();
    }

    function quitarCliente() {
        cliente = null;
        el.clienteElegido.classList.add('hidden');
        actualizarAvisoFactura();
    }

    function actualizarAvisoFactura() {
        const necesitaRuc = tipoComprobante() === 'FACTURA' &&
            (!cliente || cliente.tipo_documento !== 'RUC');

        el.avisoFactura.classList.toggle('hidden', !necesitaRuc);
    }

    // ------------------------------------------------------------ cobro
    async function cobrar() {
        if (cobrando || carrito.length === 0) return;

        const total = totalActual();
        const metodo = el.metodoPago.value;
        const pagado = metodo === 'EFECTIVO' ? (parseFloat(el.montoPagado.value) || 0) : total;

        if (metodo === 'EFECTIVO' && pagado < total) {
            mostrarMensaje('El monto recibido es menor que el total de la venta.', 'error');
            el.montoPagado.focus();
            return;
        }

        if (tipoComprobante() === 'FACTURA' && (!cliente || cliente.tipo_documento !== 'RUC')) {
            mostrarMensaje('Para emitir una factura seleccione un cliente con RUC.', 'error');
            return;
        }

        cobrando = true;
        el.cobrar.disabled = true;
        el.cobrar.textContent = 'Registrando...';

        try {
            const r = await fetch(cfg.baseUrl + 'api/registrar_venta', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    csrf_token: cfg.csrf,
                    carrito: carrito.map(l => ({ id_producto: l.id_producto, cantidad: l.cantidad })),
                    tipo_comprobante: tipoComprobante(),
                    id_cliente: cliente ? cliente.id_cliente : null,
                    metodo_pago: metodo,
                    monto_pagado: pagado
                })
            });

            const datos = await r.json();

            if (!datos.ok) {
                mostrarMensaje(datos.mensaje, 'error');
                return;
            }

            mostrarExito(datos);
        } catch (e) {
            mostrarMensaje('No se pudo conectar con el servidor. Intente nuevamente.', 'error');
        } finally {
            cobrando = false;
            el.cobrar.disabled = carrito.length === 0;
            el.cobrar.textContent = 'Registrar venta (F9)';
        }
    }

    function mostrarExito(datos) {
        el.exitoComprobante.textContent = datos.comprobante;
        el.exitoTotal.textContent = soles(datos.total);
        el.exitoVuelto.textContent = soles(datos.vuelto);
        el.exitoImprimir.href = cfg.baseUrl + 'comprobante/' + datos.id_venta;
        el.modalExito.classList.replace('hidden', 'flex');
    }

    function nuevaVenta() {
        carrito = [];
        quitarCliente();
        el.montoPagado.value = '0.00';
        el.metodoPago.value = 'EFECTIVO';
        // Avisar del cambio para que vuelva a mostrarse el bloque de efectivo
        el.metodoPago.dispatchEvent(new Event('change', { bubbles: true }));
        document.querySelector('input[value="BOLETA"]').checked = true;
        marcarTipo();
        el.modalExito.classList.replace('flex', 'hidden');
        limpiarMensaje();
        pintarCarrito();
        el.buscador.focus();
    }

    /** Resalta visualmente el tipo de comprobante elegido. */
    function marcarTipo() {
        const esBoleta = tipoComprobante() === 'BOLETA';
        const activo = 'flex cursor-pointer items-center justify-center rounded-lg border border-marca-600 bg-marca-50 px-3 py-2 text-sm font-medium text-marca-700';
        const inactivo = 'flex cursor-pointer items-center justify-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-600';

        el.opcionBoleta.className = esBoleta ? activo : inactivo;
        el.opcionFactura.className = esBoleta ? inactivo : activo;

        actualizarAvisoFactura();
    }

    // ------------------------------------------------------------ eventos
    el.buscador.addEventListener('input', conRetraso(function () {
        buscarProductos(this.value.trim());
    }, 250));

    // Enter con un código completo: el lector de barras termina con Enter
    el.buscador.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;

        e.preventDefault();
        const valor = this.value.trim();

        if (valor === '') return;

        if (/^[0-9]{6,}$/.test(valor)) {
            buscarPorCodigo(valor);
            this.value = '';
        } else {
            const primero = el.resultados.querySelector('.resultado');
            if (primero) primero.click();
        }
    });

    el.resultados.addEventListener('click', function (e) {
        const boton = e.target.closest('.resultado');
        if (boton) agregarProducto(JSON.parse(boton.dataset.producto));
    });

    el.carrito.addEventListener('click', function (e) {
        const id = Number(e.target.dataset.id);
        if (!id) return;

        if (e.target.classList.contains('quitar')) quitarLinea(id);
        if (e.target.classList.contains('mas')) {
            const linea = carrito.find(l => l.id_producto === id);
            cambiarCantidad(id, linea.cantidad + 1);
        }
        if (e.target.classList.contains('menos')) {
            const linea = carrito.find(l => l.id_producto === id);
            cambiarCantidad(id, linea.cantidad - 1);
        }
    });

    el.carrito.addEventListener('change', function (e) {
        if (e.target.classList.contains('cantidad')) {
            cambiarCantidad(Number(e.target.dataset.id), e.target.value);
        }
    });

    el.vaciar.addEventListener('click', function () {
        if (carrito.length === 0) return;
        if (!confirm('¿Vaciar el carrito y descartar la venta actual?')) return;

        carrito = [];
        pintarCarrito();
        el.buscador.focus();
    });

    el.buscadorCliente.addEventListener('input', conRetraso(function () {
        buscarClientes(this.value.trim());
    }, 250));

    el.resultadosCliente.addEventListener('click', function (e) {
        const boton = e.target.closest('.resultado-cliente');
        if (boton) elegirCliente(JSON.parse(boton.dataset.cliente));
    });

    el.quitarCliente.addEventListener('click', quitarCliente);

    document.querySelectorAll('input[name="tipo_comprobante"]').forEach(radio => {
        radio.addEventListener('change', marcarTipo);
    });

    el.metodoPago.addEventListener('change', function () {
        const esEfectivo = this.value === 'EFECTIVO';
        el.bloqueEfectivo.classList.toggle('hidden', !esEfectivo);
        calcularVuelto();
    });

    el.montoPagado.addEventListener('input', calcularVuelto);

    // Los botones de billetes suman al monto recibido
    document.querySelectorAll('.billete').forEach(boton => {
        boton.addEventListener('click', function () {
            const actual = parseFloat(el.montoPagado.value) || 0;
            el.montoPagado.value = (actual + Number(this.dataset.monto)).toFixed(2);
            calcularVuelto();
        });
    });

    el.exacto.addEventListener('click', function () {
        el.montoPagado.value = totalActual().toFixed(2);
        calcularVuelto();
    });

    el.cobrar.addEventListener('click', cobrar);
    el.nuevaVenta.addEventListener('click', nuevaVenta);

    // Atajos de teclado
    document.addEventListener('keydown', function (e) {
        if (e.key === 'F9') {
            e.preventDefault();
            cobrar();
        }

        if (e.key === 'Escape') {
            cerrarResultados();
            el.resultadosCliente.classList.add('hidden');
            if (streamCamara) cerrarEscanerCamara();
        }
    });

    // Cerrar los desplegables al hacer clic fuera
    document.addEventListener('click', function (e) {
        if (!el.resultados.contains(e.target) && e.target !== el.buscador) {
            cerrarResultados();
        }
        if (!el.resultadosCliente.contains(e.target) && e.target !== el.buscadorCliente) {
            el.resultadosCliente.classList.add('hidden');
        }
    });

    // ------------------------------------------------------------ arranque
    pintarCarrito();
    marcarTipo();
})();
