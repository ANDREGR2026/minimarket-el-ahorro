/**
 * Toma las capturas de pantalla del sistema para el manual de usuario.
 * Se ejecuta desde la carpeta del proyecto: node capturas.js
 */
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const BASE = 'http://localhost/MINIMARKET/';
const SALIDA = 'docs/capturas';
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

const PANTALLAS_ADMIN = [
    ['login', '', { sinSesion: true }],
    ['dashboard', 'dashboard', {}],
    ['productos', 'productos', {}],
    ['producto_nuevo', 'productos', { clic: 'button[onclick="abrirModal()"]' }],
    ['categorias', 'categorias', {}],
    ['inventario', 'inventario', {}],
    ['kardex', 'kardex/8', {}],
    ['clientes', 'clientes', {}],
    ['cliente_nuevo', 'clientes', { clic: 'button[onclick="abrirModal()"]' }],
    ['ventas', 'ventas', {}],
    ['detalle_venta', 'venta/2', {}],
    ['reportes', 'reportes', {}],
    ['usuarios', 'usuarios', {}],
];

async function iniciarSesion(page, usuario, clave) {
    await page.goto(BASE + 'logout', { waitUntil: 'networkidle0' });
    await page.type('#usuario', usuario);
    await page.type('#password', clave);
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type="submit"]'),
    ]);
}

async function capturar(page, nombre, ruta, opciones = {}) {
    await page.goto(BASE + ruta, { waitUntil: 'networkidle0' });

    if (opciones.clic) {
        await page.evaluate((s) => document.querySelector(s).click(), opciones.clic);
        await new Promise(r => setTimeout(r, 400));
    }

    // Dar tiempo a que Chart.js termine de animar
    await new Promise(r => setTimeout(r, 900));

    await page.screenshot({
        path: path.join(SALIDA, nombre + '.png'),
        fullPage: !opciones.clic,
    });

    console.log('  ' + nombre + '.png');
}

(async () => {
    fs.mkdirSync(SALIDA, { recursive: true });

    const browser = await puppeteer.launch({
        executablePath: CHROME,
        headless: 'new',
        defaultViewport: { width: 1440, height: 900, deviceScaleFactor: 1 },
        args: ['--no-sandbox', '--hide-scrollbars'],
    });

    const page = await browser.newPage();

    console.log('Capturas como Administrador:');
    await page.goto(BASE + 'logout', { waitUntil: 'networkidle0' });
    await capturar(page, 'login', 'login');

    await iniciarSesion(page, 'admin', 'admin123');

    for (const [nombre, ruta, opciones] of PANTALLAS_ADMIN) {
        if (opciones.sinSesion) continue;
        await capturar(page, nombre, ruta, opciones);
    }

    console.log('Capturas como Cajero:');
    await iniciarSesion(page, 'cajero', 'cajero123');
    await capturar(page, 'pos_vacio', 'pos');

    // Punto de venta con productos en el carrito
    await page.goto(BASE + 'pos', { waitUntil: 'networkidle0' });

    async function agregar(termino) {
        await page.evaluate((t) => {
            const campo = document.getElementById('buscador');
            campo.value = t;
            campo.dispatchEvent(new Event('input', { bubbles: true }));
        }, termino);

        await page.waitForSelector('.resultado', { timeout: 5000 });
        // El clic sintético del modo headless no siempre llega: se dispara desde la página
        await page.evaluate(() => document.querySelector('.resultado').click());
        await new Promise(r => setTimeout(r, 400));
    }

    await agregar('inca');
    await agregar('galleta');
    await agregar('arroz');

    await page.evaluate(() => document.getElementById('btn-exacto').click());
    await new Promise(r => setTimeout(r, 300));

    await page.screenshot({ path: path.join(SALIDA, 'pos_carrito.png'), fullPage: true });
    console.log('  pos_carrito.png');

    await capturar(page, 'acceso_denegado', 'usuarios');

    await browser.close();
    console.log('Listo. Capturas en ' + SALIDA);
})().catch(e => {
    console.error('Error:', e.message);
    process.exit(1);
});
