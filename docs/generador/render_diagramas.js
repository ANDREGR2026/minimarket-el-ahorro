/**
 * Renderiza los diagramas .mmd de docs/diagramas a PNG usando Chrome local.
 */
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const DIR = 'docs/diagramas';
const MERMAID = 'node_modules/mermaid/dist/mermaid.min.js';
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

(async () => {
    const mermaidJs = fs.readFileSync(MERMAID, 'utf8');
    const archivos = fs.readdirSync(DIR).filter(f => f.endsWith('.mmd')).sort();

    const browser = await puppeteer.launch({
        executablePath: CHROME,
        headless: 'new',
        defaultViewport: { width: 1600, height: 1200, deviceScaleFactor: 2 },
        args: ['--no-sandbox'],
    });

    for (const archivo of archivos) {
        const codigo = fs.readFileSync(path.join(DIR, archivo), 'utf8');
        const nombre = archivo.replace('.mmd', '');

        const page = await browser.newPage();
        await page.setViewport({ width: 1600, height: 1200, deviceScaleFactor: 2 });

        await page.setContent(`<!doctype html>
<html><head><meta charset="utf-8">
<style>
  body { margin:0; padding:24px; background:#fff;
         font-family:"Segoe UI", system-ui, sans-serif; }
  #salida { display:inline-block; }
</style>
</head><body><div id="salida"></div></body></html>`, { waitUntil: 'domcontentloaded' });

        await page.addScriptTag({ content: mermaidJs });

        const resultado = await page.evaluate(async (codigo) => {
            mermaid.initialize({
                startOnLoad: false,
                theme: 'base',
                themeVariables: {
                    primaryColor: '#eff8ff',
                    primaryTextColor: '#172c54',
                    primaryBorderColor: '#257aeb',
                    lineColor: '#64748b',
                    secondaryColor: '#f1f5f9',
                    tertiaryColor: '#ffffff',
                    fontFamily: '"Segoe UI", system-ui, sans-serif',
                    fontSize: '15px',
                },
                flowchart: { htmlLabels: true, curve: 'basis', padding: 14 },
                sequence: { useMaxWidth: false, width: 170 },
                er: { useMaxWidth: false },
                class: { useMaxWidth: false },
            });

            try {
                const { svg } = await mermaid.render('grafico', codigo);
                document.getElementById('salida').innerHTML = svg;

                const el = document.querySelector('#salida svg');

                // Fijar el tamaño real a partir del viewBox para que no se
                // quede en el 100% del contenedor
                const vb = el.getAttribute('viewBox').split(/[\s,]+/).map(Number);
                const ancho = vb[2];
                const alto = vb[3];

                el.setAttribute('width', ancho);
                el.setAttribute('height', alto);
                el.style.maxWidth = 'none';
                el.style.width = ancho + 'px';
                el.style.height = alto + 'px';

                return 'ok';
            } catch (e) {
                return 'ERROR: ' + e.message;
            }
        }, codigo);

        if (resultado !== 'ok') {
            console.log('  ' + nombre + ' -> ' + resultado);
            await page.close();
            continue;
        }

        await new Promise(r => setTimeout(r, 400));

        const caja = await page.evaluate(() => {
            const b = document.getElementById('salida').getBoundingClientRect();
            return { width: Math.ceil(b.width), height: Math.ceil(b.height) };
        });

        await page.setViewport({
            width: Math.min(caja.width + 48, 4000),
            height: Math.min(caja.height + 48, 6000),
            deviceScaleFactor: 2,
        });
        await new Promise(r => setTimeout(r, 300));

        await page.screenshot({
            path: path.join(DIR, nombre + '.png'),
            fullPage: true,
            omitBackground: false,
        });

        console.log('  ' + nombre + '.png  (' + caja.width + 'x' + caja.height + ')');
        await page.close();
    }

    await browser.close();
    console.log('Diagramas renderizados en ' + DIR);
})().catch(e => { console.error('Error:', e.message); process.exit(1); });
