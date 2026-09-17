/**
 * Estilos y utilidades compartidas por todos los documentos Word del proyecto.
 */
const fs = require('fs');
const path = require('path');
const {
    Document, Packer, Paragraph, TextRun, HeadingLevel, AlignmentType,
    Table, TableRow, TableCell, WidthType, ShadingType, BorderStyle,
    ImageRun, PageBreak, Header, Footer, PageNumber, TableOfContents,
    convertInchesToTwip, LevelFormat, VerticalAlign, PageOrientation,
} = require('docx');

const MARCA = '1E468A';
const MARCA_CLARO = 'EFF8FF';
const GRIS = '64748B';
const GRIS_CLARO = 'F1F5F9';

const ANCHO_PAGINA = 9026; // ancho útil en DXA con márgenes de 1 pulgada

const RAIZ = path.join(__dirname, '..');

// ---------------------------------------------------------------- utilidades

function leerImagen(rutaRelativa) {
    const ruta = path.join(RAIZ, rutaRelativa);
    return fs.existsSync(ruta) ? fs.readFileSync(ruta) : null;
}

/**
 * Inserta una imagen escalada para que quepa en el ancho útil de la página.
 */
function imagen(rutaRelativa, anchoMaximoPx = 620, pie = null) {
    const datos = leerImagen(rutaRelativa);

    if (!datos) {
        return [texto('[No se encontró la imagen: ' + rutaRelativa + ']', { cursiva: true, color: GRIS })];
    }

    const dimensiones = medirPng(datos);
    const escala = Math.min(1, anchoMaximoPx / dimensiones.ancho);

    const elementos = [
        new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { before: 160, after: pie ? 60 : 200 },
            children: [
                new ImageRun({
                    type: 'png',
                    data: datos,
                    transformation: {
                        width: Math.round(dimensiones.ancho * escala),
                        height: Math.round(dimensiones.alto * escala),
                    },
                }),
            ],
        }),
    ];

    if (pie) {
        elementos.push(new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { after: 240 },
            children: [new TextRun({ text: pie, italics: true, size: 18, color: GRIS })],
        }));
    }

    return elementos;
}

/** Lee el ancho y alto de un PNG desde su cabecera IHDR. */
function medirPng(buffer) {
    return {
        ancho: buffer.readUInt32BE(16),
        alto: buffer.readUInt32BE(20),
    };
}

function texto(contenido, opciones = {}) {
    return new Paragraph({
        alignment: opciones.centrado ? AlignmentType.CENTER : AlignmentType.JUSTIFIED,
        spacing: { after: opciones.espacio ?? 120, line: 276 },
        children: [
            new TextRun({
                text: contenido,
                size: opciones.tamano ?? 22,
                bold: opciones.negrita ?? false,
                italics: opciones.cursiva ?? false,
                color: opciones.color ?? '1F2937',
            }),
        ],
    });
}

/** Párrafo con partes de distinto formato: ['normal', {t:'negrita', n:true}] */
function parrafoMixto(partes, opciones = {}) {
    return new Paragraph({
        alignment: AlignmentType.JUSTIFIED,
        spacing: { after: opciones.espacio ?? 120, line: 276 },
        children: partes.map(parte =>
            typeof parte === 'string'
                ? new TextRun({ text: parte, size: 22, color: '1F2937' })
                : new TextRun({
                    text: parte.t,
                    size: 22,
                    bold: !!parte.n,
                    italics: !!parte.c,
                    color: parte.color ?? '1F2937',
                    font: parte.mono ? 'Consolas' : undefined,
                })
        ),
    });
}

function titulo1(contenido) {
    return new Paragraph({
        heading: HeadingLevel.HEADING_1,
        spacing: { before: 360, after: 200 },
        children: [new TextRun({ text: contenido, size: 32, bold: true, color: MARCA })],
    });
}

function titulo2(contenido) {
    return new Paragraph({
        heading: HeadingLevel.HEADING_2,
        spacing: { before: 280, after: 160 },
        children: [new TextRun({ text: contenido, size: 26, bold: true, color: MARCA })],
    });
}

function titulo3(contenido) {
    return new Paragraph({
        heading: HeadingLevel.HEADING_3,
        spacing: { before: 220, after: 120 },
        children: [new TextRun({ text: contenido, size: 23, bold: true, color: '334155' })],
    });
}

function vinieta(contenido, nivel = 0) {
    return new Paragraph({
        numbering: { reference: 'lista-vinetas', level: nivel },
        spacing: { after: 80, line: 276 },
        children: [new TextRun({ text: contenido, size: 22, color: '1F2937' })],
    });
}

function saltoPagina() {
    return new Paragraph({ children: [new PageBreak()] });
}

function espacio(alto = 200) {
    return new Paragraph({ spacing: { after: alto }, children: [] });
}

// ---------------------------------------------------------------- tablas

function celda(contenido, opciones = {}) {
    const contenidos = Array.isArray(contenido) ? contenido : [contenido];

    return new TableCell({
        width: { size: opciones.ancho, type: WidthType.DXA },
        columnSpan: opciones.combinar,
        verticalAlign: VerticalAlign.CENTER,
        shading: opciones.fondo
            ? { type: ShadingType.CLEAR, fill: opciones.fondo, color: 'auto' }
            : undefined,
        margins: { top: 80, bottom: 80, left: 110, right: 110 },
        children: contenidos.map(linea => new Paragraph({
            alignment: opciones.alineacion ?? AlignmentType.LEFT,
            spacing: { after: contenidos.length > 1 ? 60 : 0, line: 260 },
            children: [
                new TextRun({
                    text: linea,
                    size: opciones.tamano ?? 20,
                    bold: opciones.negrita ?? false,
                    color: opciones.color ?? '1F2937',
                    font: opciones.mono ? 'Consolas' : undefined,
                }),
            ],
        })),
    });
}

/**
 * Tabla con cabecera de color y filas alternadas.
 *
 * @param {string[]} cabeceras
 * @param {Array[]}  filas
 * @param {number[]} proporciones porcentaje de ancho de cada columna
 */
function tabla(cabeceras, filas, proporciones, opciones = {}) {
    const anchos = proporciones.map(p => Math.round(ANCHO_PAGINA * p / 100));
    const alineaciones = opciones.alineaciones ?? [];

    const filaCabecera = new TableRow({
        tableHeader: true,
        children: cabeceras.map((titulo, i) => celda(titulo, {
            ancho: anchos[i],
            fondo: MARCA,
            color: 'FFFFFF',
            negrita: true,
            tamano: 19,
            alineacion: alineaciones[i] ?? AlignmentType.LEFT,
        })),
    });

    const filasCuerpo = filas.map((fila, indice) => new TableRow({
        children: fila.map((valor, i) => celda(valor, {
            ancho: anchos[i],
            fondo: indice % 2 === 1 ? GRIS_CLARO : undefined,
            alineacion: alineaciones[i] ?? AlignmentType.LEFT,
            negrita: i === 0 && opciones.primeraColumnaNegrita,
            mono: opciones.mono && i === 0,
        })),
    }));

    return new Table({
        columnWidths: anchos,
        width: { size: ANCHO_PAGINA, type: WidthType.DXA },
        borders: bordes(),
        rows: [filaCabecera, ...filasCuerpo],
    });
}

/**
 * Tabla de dos columnas tipo ficha (etiqueta / valor).
 */
function ficha(filas, proporciones = [26, 74]) {
    const anchos = proporciones.map(p => Math.round(ANCHO_PAGINA * p / 100));

    return new Table({
        columnWidths: anchos,
        width: { size: ANCHO_PAGINA, type: WidthType.DXA },
        borders: bordes(),
        rows: filas.map(([etiqueta, valor]) => new TableRow({
            children: [
                celda(etiqueta, { ancho: anchos[0], fondo: MARCA_CLARO, negrita: true, color: MARCA }),
                celda(valor, { ancho: anchos[1] }),
            ],
        })),
    });
}

function bordes() {
    const linea = { style: BorderStyle.SINGLE, size: 3, color: 'CBD5E1' };
    return { top: linea, bottom: linea, left: linea, right: linea,
             insideHorizontal: linea, insideVertical: linea };
}

// ---------------------------------------------------------------- documento

/**
 * Portada estándar de todos los entregables.
 */
function portada(numero, tituloDocumento, subtitulo) {
    const elementos = [
        espacio(1400),

        new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { after: 80 },
            children: [new TextRun({
                text: 'UNIVERSIDAD · CURSO DE DESARROLLO DE SISTEMAS DE INFORMACIÓN',
                size: 18, bold: true, color: GRIS,
            })],
        }),

        new Paragraph({
            alignment: AlignmentType.CENTER,
            border: { bottom: { style: BorderStyle.SINGLE, size: 12, color: MARCA, space: 10 } },
            spacing: { after: 400 },
            children: [],
        }),

        new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { after: 120 },
            children: [new TextRun({ text: 'PROYECTO FINAL', size: 22, color: GRIS, bold: true })],
        }),

        new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { after: 600 },
            children: [new TextRun({
                text: 'Sistema Web para la Gestión de un Minimarket',
                size: 40, bold: true, color: MARCA,
            })],
        }),

        new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { after: 100 },
            children: [new TextRun({
                text: 'DOCUMENTO ' + numero,
                size: 20, bold: true, color: GRIS,
            })],
        }),

        new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { after: 160 },
            children: [new TextRun({ text: tituloDocumento, size: 32, bold: true, color: '1F2937' })],
        }),

        new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { after: 1200 },
            children: [new TextRun({ text: subtitulo, size: 22, italics: true, color: GRIS })],
        }),
    ];

    elementos.push(ficha([
        ['Proyecto', 'Sistema Web para la Gestión de un Minimarket'],
        ['Curso', 'Desarrollo de Sistemas de Información'],
        ['Versión del documento', '1.0'],
        ['Fecha', fechaLarga()],
        ['Tecnologías', 'PHP 8.3 · MySQL 8.4 · TailwindCSS · JavaScript · Apache (Laragon)'],
    ], [32, 68]));

    elementos.push(saltoPagina());

    return elementos;
}

function indice() {
    return [
        // No usa titulo1 a propósito: así el propio índice no se lista dentro del índice
        new Paragraph({
            spacing: { before: 200, after: 200 },
            children: [new TextRun({ text: 'Índice', size: 32, bold: true, color: MARCA })],
        }),
        texto(
            'Para actualizar el índice en Word: clic derecho sobre él y elegir ' +
            '"Actualizar campos" → "Actualizar toda la tabla".',
            { cursiva: true, color: GRIS, tamano: 18 }
        ),
        espacio(120),
        new TableOfContents('Tabla de contenido', {
            hyperlink: true,
            headingStyleRange: '1-3',
        }),
        saltoPagina(),
    ];
}

function fechaLarga() {
    const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
                   'agosto', 'setiembre', 'octubre', 'noviembre', 'diciembre'];
    const hoy = new Date();
    return hoy.getDate() + ' de ' + meses[hoy.getMonth()] + ' de ' + hoy.getFullYear();
}

/**
 * Arma el documento con encabezado, pie de página y numeración.
 *
 * @param {string} tituloCorto
 * @param {Array}  contenido  arreglo de elementos, o arreglo de secciones
 *                            con la forma { apaisada: bool, hijos: [] }
 */
function documento(tituloCorto, contenido) {
    const esListaDeSecciones = Array.isArray(contenido)
        && contenido.length > 0
        && contenido.every(s => s && typeof s === 'object' && Array.isArray(s.hijos));

    const secciones = esListaDeSecciones
        ? contenido
        : [{ apaisada: false, hijos: contenido, primeraDistinta: true }];

    return new Document({
        creator: 'Sistema Web para la Gestión de un Minimarket',
        title: tituloCorto,
        description: 'Proyecto final del curso de Desarrollo de Sistemas de Información',
        numbering: {
            config: [{
                reference: 'lista-vinetas',
                levels: [
                    {
                        level: 0, format: LevelFormat.BULLET, text: '•',
                        alignment: AlignmentType.LEFT,
                        style: { paragraph: { indent: { left: 460, hanging: 260 } } },
                    },
                    {
                        level: 1, format: LevelFormat.BULLET, text: '◦',
                        alignment: AlignmentType.LEFT,
                        style: { paragraph: { indent: { left: 900, hanging: 260 } } },
                    },
                ],
            }],
        },
        styles: {
            default: {
                document: { run: { font: 'Calibri', size: 22, color: '1F2937' } },
            },
        },
        sections: secciones.map((seccion, indice) => {
            const encabezado = new Header({
                children: [new Paragraph({
                    alignment: AlignmentType.RIGHT,
                    border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: 'CBD5E1', space: 6 } },
                    children: [new TextRun({
                        text: 'Sistema Web para la Gestión de un Minimarket · ' + tituloCorto,
                        size: 16, color: GRIS,
                    })],
                })],
            });

            const pie = new Footer({
                children: [new Paragraph({
                    alignment: AlignmentType.CENTER,
                    children: [new TextRun({
                        children: ['Página ', PageNumber.CURRENT, ' de ', PageNumber.TOTAL_PAGES],
                        size: 16, color: GRIS,
                    })],
                })],
            });

            const vacio = { children: [new Paragraph({ children: [] })] };

            // Solo la primera sección lleva portada sin encabezado ni pie
            const esPortada = indice === 0;

            return {
                properties: {
                    titlePage: esPortada,
                    page: {
                        size: seccion.apaisada
                            ? { width: 11906, height: 16838, orientation: PageOrientation.LANDSCAPE }
                            : undefined,
                        margin: {
                            top: convertInchesToTwip(seccion.apaisada ? 0.7 : 1),
                            bottom: convertInchesToTwip(seccion.apaisada ? 0.7 : 1),
                            left: convertInchesToTwip(seccion.apaisada ? 0.8 : 1),
                            right: convertInchesToTwip(seccion.apaisada ? 0.8 : 1),
                        },
                    },
                },
                headers: esPortada
                    ? { default: encabezado, first: new Header(vacio) }
                    : { default: encabezado },
                footers: esPortada
                    ? { default: pie, first: new Footer(vacio) }
                    : { default: pie },
                children: seccion.hijos,
            };
        }),
    });
}

async function guardar(doc, nombreArchivo) {
    const buffer = await Packer.toBuffer(doc);
    const ruta = path.join(RAIZ, nombreArchivo);
    fs.writeFileSync(ruta, buffer);
    console.log('  ' + nombreArchivo + '  (' + Math.round(buffer.length / 1024) + ' KB)');
}

module.exports = {
    MARCA, MARCA_CLARO, GRIS, GRIS_CLARO, ANCHO_PAGINA,
    texto, parrafoMixto, titulo1, titulo2, titulo3, vinieta,
    saltoPagina, espacio, tabla, ficha, celda, imagen,
    portada, indice, documento, guardar, fechaLarga,
    AlignmentType,
};
