/**
 * Genera los seis documentos Word del proyecto.
 *
 * Ejecutar desde la carpeta del proyecto:
 *     node docs/generador/generar.js
 */
const C = require('./comun');
const D = require('./datos');
const { AlignmentType } = require('docx');

const CENTRO = AlignmentType.CENTER;

// =====================================================================
// 1. DOCUMENTO DE REQUERIMIENTOS
// =====================================================================
function documentoRequerimientos() {
    const hijos = [
        ...C.portada('01', 'Documento de Requerimientos',
            'Especificación de requerimientos funcionales, no funcionales y reglas de negocio'),
        ...C.indice(),

        C.titulo1('1. Introducción'),

        C.titulo2('1.1. Propósito del documento'),
        C.texto(
            'Este documento especifica los requerimientos del Sistema Web para la Gestión ' +
            'de un Minimarket. Describe qué debe hacer el sistema, bajo qué condiciones de ' +
            'calidad debe hacerlo y qué reglas del negocio debe respetar. Está dirigido al ' +
            'docente del curso, que evalúa el proyecto, y sirve además como referencia para ' +
            'el desarrollo y las pruebas.'),

        C.titulo2('1.2. Alcance'),
        C.texto(
            'El sistema automatiza la operación diaria de un minimarket de barrio: la venta ' +
            'en caja con emisión de boleta o factura, el control del catálogo y del ' +
            'inventario, el registro de clientes y la generación de reportes de gestión. ' +
            'Está pensado para funcionar en la red local del negocio, sobre un servidor ' +
            'Laragon con Apache, PHP y MySQL.'),
        C.texto('Quedan comprendidos dentro del alcance:'),
        C.vinieta('Autenticación de usuarios y control de acceso por rol.'),
        C.vinieta('Punto de venta con lectura de código de barras y cálculo automático del IGV.'),
        C.vinieta('Emisión del comprobante en PDF con serie y correlativo propios.'),
        C.vinieta('Catálogo de productos organizado por categorías.'),
        C.vinieta('Control de inventario con kardex por producto.'),
        C.vinieta('Registro de clientes con DNI o RUC.'),
        C.vinieta('Historial de ventas con anulación y restitución de stock.'),
        C.vinieta('Dashboard de indicadores y reportes exportables a PDF y Excel.'),
        C.espacio(80),
        C.texto('Quedan fuera del alcance de esta versión:'),
        C.vinieta('Envío de comprobantes electrónicos a la SUNAT.'),
        C.vinieta('Módulo de compras y de órdenes a proveedores.'),
        C.vinieta('Ventas al crédito y control de cuentas por cobrar.'),
        C.vinieta('Operación con varias sucursales o varias cajas simultáneas en distintos locales.'),
        C.vinieta('Aplicación móvil o venta por internet.'),

        C.titulo2('1.3. Definiciones y abreviaturas'),
        C.tabla(
            ['Término', 'Significado'],
            [
                ['IGV', 'Impuesto General a las Ventas. En el Perú equivale al 18 % del valor de venta.'],
                ['Operación gravada', 'Importe de la venta sin considerar el IGV. También llamado valor de venta o subtotal.'],
                ['Boleta', 'Comprobante de pago dirigido al consumidor final, identificado con DNI o sin identificación.'],
                ['Factura', 'Comprobante de pago dirigido a empresas, que exige el RUC del cliente.'],
                ['Serie y correlativo', 'Numeración del comprobante. Por ejemplo B001-000045.'],
                ['Kardex', 'Registro histórico de todos los movimientos de stock de un producto.'],
                ['Stock mínimo', 'Cantidad a partir de la cual el sistema alerta que el producto debe reponerse.'],
                ['Baja lógica', 'Desactivar un registro en lugar de borrarlo, para conservar el historial.'],
                ['POS', 'Point of Sale. Pantalla de punto de venta que usa el cajero.'],
                ['CSRF', 'Cross-Site Request Forgery. Ataque que el sistema previene con un token por sesión.'],
                ['MVC', 'Modelo-Vista-Controlador. Patrón de arquitectura empleado en el desarrollo.'],
            ],
            [22, 78],
            { primeraColumnaNegrita: true }
        ),

        C.titulo2('1.4. Descripción general del sistema'),
        C.texto(
            'El sistema es una aplicación web de tres capas. La capa de presentación está ' +
            'construida con HTML, TailwindCSS y JavaScript, y se ejecuta en el navegador del ' +
            'usuario. La capa de negocio está escrita en PHP siguiendo el patrón MVC, con ' +
            'controladores que aplican las reglas del negocio y modelos que encapsulan el ' +
            'acceso a los datos. La capa de datos es una base MySQL con nueve tablas ' +
            'relacionadas, a la que se accede exclusivamente mediante PDO con sentencias ' +
            'preparadas.'),

        C.saltoPagina(),
        C.titulo1('2. Actores del sistema'),
        C.texto(
            'Se identificaron dos actores humanos y un actor no humano que interviene ' +
            'automáticamente en los procesos críticos.'),
        C.tabla(
            ['Actor', 'Descripción y responsabilidades'],
            D.ACTORES,
            [22, 78],
            { primeraColumnaNegrita: true }
        ),

        C.titulo2('2.1. Matriz de permisos por rol'),
        C.tabla(
            ['Módulo', 'Administrador', 'Cajero'],
            [
                ['Punto de venta', 'Sí', 'Sí'],
                ['Historial de ventas', 'Todas las ventas', 'Solo las propias'],
                ['Anular venta', 'Sí', 'No'],
                ['Catálogo de productos', 'Consulta y edición', 'Solo consulta'],
                ['Categorías', 'Sí', 'No'],
                ['Inventario y kardex', 'Sí', 'No'],
                ['Clientes', 'Registro, edición y baja', 'Registro y edición'],
                ['Dashboard', 'Sí', 'No'],
                ['Reportes', 'Sí', 'No'],
                ['Usuarios', 'Sí', 'No'],
            ],
            [40, 30, 30],
            { primeraColumnaNegrita: true, alineaciones: [null, CENTRO, CENTRO] }
        ),

        C.saltoPagina(),
        C.titulo1('3. Requerimientos funcionales'),
        C.texto(
            'Los requerimientos funcionales describen lo que el sistema debe hacer. Se ' +
            'identifican con el prefijo RF y se agrupan por módulo. La prioridad indica qué ' +
            'tan indispensable es el requerimiento para que el sistema cumpla su propósito.'),
    ];

    // Requerimientos funcionales agrupados
    const gruposRF = [
        ['3.1. Seguridad y control de acceso', 'RF-01', 'RF-04'],
        ['3.2. Catálogo de productos', 'RF-05', 'RF-10'],
        ['3.3. Control de inventario', 'RF-11', 'RF-15'],
        ['3.4. Gestión de clientes', 'RF-16', 'RF-19'],
        ['3.5. Punto de venta', 'RF-20', 'RF-29'],
        ['3.6. Historial y anulación de ventas', 'RF-30', 'RF-32'],
        ['3.7. Dashboard y reportes', 'RF-33', 'RF-35'],
        ['3.8. Administración de usuarios', 'RF-36', 'RF-37'],
    ];

    for (const [titulo, desde, hasta] of gruposRF) {
        const filas = D.RF
            .filter(rf => rf[0] >= desde && rf[0] <= hasta)
            .map(rf => [rf[0], rf[1], rf[2], rf[3], rf[4]]);

        hijos.push(C.titulo2(titulo));
        hijos.push(C.tabla(
            ['Código', 'Nombre', 'Descripción', 'Actor', 'Prioridad'],
            filas,
            [9, 19, 46, 15, 11],
            { primeraColumnaNegrita: true, alineaciones: [CENTRO, null, null, null, CENTRO] }
        ));
        hijos.push(C.espacio(160));
    }

    hijos.push(
        C.saltoPagina(),
        C.titulo1('4. Requerimientos no funcionales'),
        C.texto(
            'Los requerimientos no funcionales describen las condiciones de calidad que debe ' +
            'cumplir el sistema: qué tan usable, rápido, seguro y mantenible debe ser. Se ' +
            'identifican con el prefijo RNF.'),
        C.tabla(
            ['Código', 'Categoría', 'Nombre', 'Descripción', 'Prioridad'],
            D.RNF,
            [9, 14, 18, 48, 11],
            { primeraColumnaNegrita: true, alineaciones: [CENTRO, null, null, null, CENTRO] }
        ),

        C.saltoPagina(),
        C.titulo1('5. Reglas de negocio'),
        C.texto(
            'Las reglas de negocio son las condiciones propias del giro del minimarket que el ' +
            'sistema debe hacer cumplir siempre, independientemente de quién lo use o desde ' +
            'dónde. Se identifican con el prefijo RN.'),
        C.tabla(
            ['Código', 'Regla', 'Descripción'],
            D.RN,
            [9, 24, 67],
            { primeraColumnaNegrita: true, alineaciones: [CENTRO, null, null] }
        ),

        C.saltoPagina(),
        C.titulo1('6. Restricciones del proyecto'),
        C.tabla(
            ['Tipo', 'Restricción'],
            [
                ['Tecnológica', 'El sistema debe desarrollarse con PHP y MySQL, sin utilizar frameworks de backend.'],
                ['Tecnológica', 'La interfaz debe construirse con TailwindCSS compilado localmente, sin depender de CDN.'],
                ['Tecnológica', 'El servidor de desarrollo y de demostración es Laragon (Apache 2.4, PHP 8.3, MySQL 8.4).'],
                ['Operativa', 'El sistema opera en la red local del minimarket; no requiere acceso a internet.'],
                ['Normativa', 'Los comprobantes siguen el formato peruano de boleta y factura con IGV del 18 %.'],
                ['Normativa', 'Esta versión no realiza la declaración electrónica ante la SUNAT.'],
                ['Académica', 'El proyecto debe entregarse con su documentación completa antes del cierre del curso.'],
            ],
            [18, 82],
            { primeraColumnaNegrita: true }
        ),

        C.titulo1('7. Supuestos y dependencias'),
        C.vinieta('El equipo donde se instala el sistema cuenta con Windows y Laragon instalado.'),
        C.vinieta('El minimarket dispone de un lector de código de barras que funciona como teclado.'),
        C.vinieta('Los productos del catálogo tienen un código de barras legible o se les asigna uno interno.'),
        C.vinieta('La impresora de tickets acepta papel de 80 mm de ancho.'),
        C.vinieta('El personal de caja recibe una capacitación básica sobre el uso del punto de venta.'),

        C.titulo1('8. Matriz de trazabilidad'),
        C.texto(
            'La siguiente matriz relaciona cada caso de uso con los requerimientos ' +
            'funcionales que lo sustentan y con las reglas de negocio que debe respetar.'),
        C.tabla(
            ['Caso de uso', 'Requerimientos', 'Reglas de negocio'],
            [
                ['CU-01 Iniciar sesión', 'RF-01, RF-02, RF-03', '—'],
                ['CU-02 Registrar venta', 'RF-20 a RF-28', 'RN-01, RN-02, RN-03, RN-04, RN-05, RN-06, RN-12'],
                ['CU-03 Emitir comprobante', 'RF-29', 'RN-03, RN-05'],
                ['CU-04 Consultar historial', 'RF-30, RF-31', 'RN-14'],
                ['CU-05 Anular venta', 'RF-32', 'RN-06, RN-07, RN-08'],
                ['CU-06 Gestionar productos', 'RF-05 a RF-09', 'RN-09'],
                ['CU-07 Gestionar categorías', 'RF-10', 'RN-09, RN-13'],
                ['CU-08 Movimiento de inventario', 'RF-11, RF-12, RF-13', 'RN-02, RN-06'],
                ['CU-09 Consultar kardex', 'RF-14', 'RN-06'],
                ['CU-10 Gestionar clientes', 'RF-16, RF-17, RF-18, RF-19', 'RN-09, RN-11'],
                ['CU-11 Consultar dashboard', 'RF-15, RF-33', '—'],
                ['CU-12 Generar reportes', 'RF-34, RF-35', '—'],
                ['CU-13 Gestionar usuarios', 'RF-36, RF-37', 'RN-09, RN-10'],
            ],
            [28, 30, 42],
            { primeraColumnaNegrita: true }
        )
    );

    return C.documento('Documento de Requerimientos', hijos);
}

// =====================================================================
// 2. HISTORIAS DE USUARIO
// =====================================================================
function documentoHistorias() {
    const hijos = [
        ...C.portada('02', 'Historias de Usuario',
            'Requerimientos expresados desde la perspectiva de quien usa el sistema'),
        ...C.indice(),

        C.titulo1('1. Introducción'),
        C.texto(
            'Las historias de usuario describen lo que cada persona necesita del sistema, ' +
            'contado desde su propio punto de vista y en su propio lenguaje. A diferencia de ' +
            'los requerimientos funcionales, que enumeran funciones, las historias explican ' +
            'para qué sirve cada función a quien la usa.'),
        C.texto('Todas siguen la misma plantilla:'),
        C.parrafoMixto([
            { t: 'Como ', n: true }, '⟨rol⟩ ',
            { t: 'quiero ', n: true }, '⟨acción⟩ ',
            { t: 'para ', n: true }, '⟨beneficio⟩.',
        ]),
        C.texto(
            'Cada historia incluye sus criterios de aceptación en formato Dado / Cuando / ' +
            'Entonces: son las condiciones concretas que deben cumplirse para dar la historia ' +
            'por terminada, y sirven directamente como casos de prueba.'),
        C.texto(
            'La estimación se expresa en puntos de historia, una medida relativa de esfuerzo ' +
            'donde 3 puntos corresponde a una tarea sencilla y 13 puntos a una compleja.'),

        C.titulo2('1.1. Resumen de historias'),
        C.tabla(
            ['Código', 'Historia', 'Rol', 'Prioridad', 'Estimación'],
            D.HU.map(h => [h.codigo, h.titulo, h.rol, h.prioridad, h.estimacion]),
            [10, 38, 22, 15, 15],
            { primeraColumnaNegrita: true, alineaciones: [CENTRO, null, null, CENTRO, CENTRO] }
        ),

        C.saltoPagina(),
        C.titulo1('2. Detalle de las historias de usuario'),
    ];

    for (const h of D.HU) {
        hijos.push(C.titulo2(h.codigo + ' · ' + h.titulo));

        hijos.push(C.ficha([
            ['Como', h.rol],
            ['Quiero', h.accion],
            ['Para', h.beneficio],
            ['Prioridad', h.prioridad],
            ['Estimación', h.estimacion],
        ], [20, 80]));

        hijos.push(C.espacio(160));
        hijos.push(C.titulo3('Criterios de aceptación'));

        h.criterios.forEach((criterio, i) => {
            hijos.push(C.parrafoMixto([
                { t: 'CA-' + (i + 1) + '. ', n: true, color: C.MARCA },
                criterio,
            ]));
        });

        hijos.push(C.espacio(200));
    }

    return C.documento('Historias de Usuario', hijos);
}

// =====================================================================
// 3. CASOS DE USO
// =====================================================================
function documentoCasosDeUso() {
    const hijos = [
        ...C.portada('03', 'Casos de Uso',
            'Especificación detallada de la interacción entre los actores y el sistema'),
        ...C.indice(),

        C.titulo1('1. Introducción'),
        C.texto(
            'Un caso de uso describe, paso a paso, cómo un actor interactúa con el sistema ' +
            'para lograr un objetivo concreto. Cada especificación incluye el flujo principal ' +
            '—el camino en que todo sale bien— y los flujos alternos, que describen qué ocurre ' +
            'cuando algo se sale de lo previsto.'),
        C.texto(
            'Los flujos alternos se numeran según el paso del flujo principal donde se ' +
            'desvían. Por ejemplo, "4a." describe una alternativa que ocurre en el paso 4.'),

        C.titulo2('1.1. Diagrama de casos de uso'),
        C.texto(
            'El siguiente diagrama muestra los trece casos de uso del sistema y qué actor ' +
            'participa en cada uno. Las relaciones «include» indican que un caso de uso ' +
            'siempre ejecuta a otro; «extend» indica que puede hacerlo opcionalmente.'),
        ...C.imagen('diagramas/01_casos_de_uso.png', 430,
            'Figura 1. Diagrama de casos de uso del sistema'),

        C.titulo2('1.2. Resumen de casos de uso'),
        C.tabla(
            ['Código', 'Caso de uso', 'Actor principal', 'Descripción'],
            D.CU.map(c => [c.codigo, c.nombre, c.actor, c.descripcion]),
            [9, 22, 22, 47],
            { primeraColumnaNegrita: true, alineaciones: [CENTRO, null, null, null] }
        ),

        C.saltoPagina(),
        C.titulo1('2. Especificación de los casos de uso'),
    ];

    for (const cu of D.CU) {
        hijos.push(C.titulo2(cu.codigo + ' · ' + cu.nombre));

        hijos.push(C.ficha([
            ['Código', cu.codigo],
            ['Nombre', cu.nombre],
            ['Actor principal', cu.actor],
            ['Descripción', cu.descripcion],
            ['Precondiciones', cu.precondiciones.join('  •  ')],
            ['Postcondiciones', cu.postcondiciones.join('  •  ')],
        ], [22, 78]));

        hijos.push(C.espacio(160));
        hijos.push(C.titulo3('Flujo principal'));
        hijos.push(C.tabla(
            ['N°', 'Acción'],
            cu.principal.map((paso, i) => [String(i + 1), paso]),
            [7, 93],
            { alineaciones: [CENTRO, null] }
        ));

        hijos.push(C.espacio(160));
        hijos.push(C.titulo3('Flujos alternos'));
        hijos.push(C.tabla(
            ['Paso', 'Situación y respuesta del sistema'],
            cu.alternos.map(([paso, respuesta]) => [paso, respuesta]),
            [26, 74],
            { primeraColumnaNegrita: true }
        ));

        hijos.push(C.espacio(240));
    }

    return C.documento('Casos de Uso', hijos);
}

// =====================================================================
// 4. DIAGRAMAS UML
// =====================================================================
function documentoDiagramas() {
    const hijos = [
        ...C.portada('04', 'Diagramas UML',
            'Modelado estructural y de comportamiento del sistema'),
        ...C.indice(),

        C.titulo1('1. Introducción'),
        C.texto(
            'Este documento reúne los diagramas que modelan el sistema desde distintas ' +
            'perspectivas. Los diagramas estructurales —clases, entidad-relación y ' +
            'despliegue— muestran cómo está organizado el sistema. Los diagramas de ' +
            'comportamiento —casos de uso, secuencia y actividades— muestran cómo funciona.'),
        C.tabla(
            ['Diagrama', 'Tipo', 'Qué responde'],
            [
                ['Casos de uso', 'Comportamiento', '¿Quién usa el sistema y para qué?'],
                ['Clases', 'Estructural', '¿Cómo está organizado el código y qué relaciones hay entre sus clases?'],
                ['Entidad-relación', 'Estructural', '¿Cómo se guardan los datos y cómo se relacionan entre sí?'],
                ['Secuencia', 'Comportamiento', '¿En qué orden se comunican los componentes al registrar una venta?'],
                ['Actividades', 'Comportamiento', '¿Cuál es el flujo completo del proceso de venta, con sus decisiones?'],
                ['Despliegue', 'Estructural', '¿Sobre qué equipos y servicios se ejecuta el sistema?'],
            ],
            [22, 20, 58],
            { primeraColumnaNegrita: true }
        ),

    ];

    // Los dos diagramas más anchos van en páginas horizontales para que se lean bien
    const hijosApaisados = [
        C.titulo1('2. Diagrama de casos de uso'),
        C.texto(
            'Muestra los casos de uso implementados y los actores que interactúan con el sistema. ' +
            'El visitante consulta el catálogo público; el cajero opera ventas y clientes; el almacenero ' +
            'gestiona inventario y kardex; el administrador gestiona la operación; y el superadministrador ' +
            'controla las cuentas de mayor privilegio.'),
        C.texto(
            'Registrar una venta incluye la emisión de su comprobante. El sistema aplica ' +
            'permisos por rol antes de cada módulo y conserva los movimientos de stock en el kardex.'),
        ...C.imagen('diagramas/01_casos_de_uso.png', 900,
            'Figura 1. Diagrama de casos de uso'),

        C.saltoPagina(),
        C.titulo1('3. Diagrama de clases'),
        C.texto(
            'Representa las clases del sistema, sus atributos, sus métodos principales y las ' +
            'relaciones entre ellas. Las clases del paquete de modelos encapsulan el acceso a ' +
            'los datos; los controladores aplican las reglas de negocio; Auth y Conexion son ' +
            'clases de infraestructura compartidas por todo el sistema.'),
        C.texto(
            'Destaca la relación de composición entre Venta y DetalleVenta: una línea de ' +
            'detalle no existe sin su venta, y al eliminarse la venta desaparece con ella. ' +
            'Las relaciones punteadas indican dependencia de uso.'),
        ...C.imagen('diagramas/02_clases.png', 900,
            'Figura 2. Diagrama de clases del sistema'),

        C.saltoPagina(),
        C.titulo1('4. Modelo entidad-relación'),
        C.texto(
            'Modela las nueve tablas de la base de datos, sus campos y la cardinalidad de sus ' +
            'relaciones. Todas las tablas usan el motor InnoDB con codificación utf8mb4, lo ' +
            'que garantiza integridad referencial y soporte completo de acentos.'),
        C.texto(
            'La tabla movimientos_inventario es el eje de la trazabilidad: recibe una fila ' +
            'por cada variación de stock, venga de una venta, de un ingreso de mercadería, de ' +
            'una merma o de un ajuste por conteo físico.'),
        ...C.imagen('diagramas/03_modelo_entidad_relacion.png', 860,
            'Figura 3. Modelo entidad-relación de la base de datos'),
    ];

    const hijosFinales = [
        C.titulo1('5. Diagrama de secuencia: registrar venta'),
        C.texto(
            'Muestra el orden exacto en que se comunican el cajero, el navegador, el endpoint ' +
            'del servidor, el controlador, el modelo y la base de datos al registrar una ' +
            'venta. Es el proceso más delicado del sistema, porque toca simultáneamente el ' +
            'comprobante, el stock y el kardex.'),
        C.texto(
            'Dos detalles merecen atención. Primero, el servidor vuelve a leer el precio y el ' +
            'stock de cada producto desde la base de datos con SELECT … FOR UPDATE: nunca ' +
            'confía en los datos que envía el navegador. Segundo, todo ocurre dentro de una ' +
            'transacción, de modo que cualquier fallo deshace la operación completa y no deja ' +
            'registros a medias.'),
        ...C.imagen('diagramas/04_secuencia_registrar_venta.png', 560,
            'Figura 4. Diagrama de secuencia del registro de una venta'),

        C.saltoPagina(),
        C.titulo1('6. Diagrama de actividades: proceso de venta'),
        C.texto(
            'Describe el flujo completo de la atención en caja, incluyendo todas las ' +
            'decisiones y los caminos de error. Permite ver de un vistazo en qué puntos el ' +
            'sistema puede rechazar la operación y qué hace el cajero en cada caso.'),
        ...C.imagen('diagramas/05_actividades_proceso_venta.png', 400,
            'Figura 5. Diagrama de actividades del proceso de venta'),

        C.saltoPagina(),
        C.titulo1('7. Diagrama de despliegue'),
        C.texto(
            'Muestra sobre qué nodos físicos se ejecuta el sistema y qué protocolos los ' +
            'comunican. El equipo de caja solo necesita un navegador; toda la lógica y los ' +
            'datos residen en el servidor Laragon, que puede ser el mismo equipo o cualquier ' +
            'otro dentro de la red local del minimarket.'),
        ...C.imagen('diagramas/06b_despliegue_nuevo.png', 520,
            'Figura 6. Diagrama de despliegue del sistema'),

        C.saltoPagina(),
        C.titulo1('8. Arquitectura del código'),
        C.texto(
            'El sistema sigue el patrón Modelo-Vista-Controlador. La siguiente tabla resume ' +
            'la responsabilidad de cada carpeta del proyecto.'),
        C.tabla(
            ['Carpeta', 'Capa', 'Responsabilidad'],
            [
                ['config.php', 'Configuración', 'Define las rutas base, carga el archivo .env y expone las funciones auxiliares comunes.'],
                ['database/', 'Datos', 'Conexión PDO y scripts SQL de creación y carga inicial de la base de datos.'],
                ['models/', 'Modelo', 'Acceso a datos y transacciones. Una clase por entidad del negocio.'],
                ['controllers/', 'Controlador', 'Validaciones y reglas de negocio. Traducen la petición del usuario en operaciones del modelo.'],
                ['middleware/', 'Seguridad', 'Verificación de sesión y de rol antes de ejecutar cualquier página protegida.'],
                ['pages/', 'Vista', 'Pantallas del sistema. Reciben la petición, invocan al controlador e imprimen el HTML.'],
                ['components/', 'Vista', 'Fragmentos reutilizables: encabezado, menú lateral, pie de página y mensajes.'],
                ['api/', 'Controlador', 'Endpoints que devuelven JSON, consumidos por el punto de venta.'],
                ['assets/', 'Recursos', 'Hoja de estilos compilada, JavaScript del punto de venta, librería de gráficos e imágenes.'],
                ['lib/fpdf/', 'Librería', 'Generación de los archivos PDF de comprobantes y reportes.'],
                ['storage/', 'Almacenamiento', 'Comprobantes emitidos en formato PDF.'],
            ],
            [17, 16, 67],
            { primeraColumnaNegrita: true, mono: true }
        ),
    ];

    return C.documento('Diagramas UML', [
        { apaisada: false, hijos },
        { apaisada: true, hijos: hijosApaisados },
        { apaisada: false, hijos: hijosFinales },
    ]);
}

// =====================================================================
// 5. DICCIONARIO DE DATOS
// =====================================================================
function documentoDiccionario() {
    const hijos = [
        ...C.portada('05', 'Diccionario de Datos',
            'Descripción detallada de la estructura de la base de datos'),
        ...C.indice(),

        C.titulo1('1. Introducción'),
        C.texto(
            'Este documento describe cada tabla de la base de datos minimarket, campo por ' +
            'campo: su tipo, su longitud, si admite valores nulos, si forma parte de alguna ' +
            'llave y qué significa dentro del negocio.'),

        C.titulo2('1.1. Convenciones'),
        C.tabla(
            ['Elemento', 'Convención adoptada'],
            [
                ['Motor de almacenamiento', 'InnoDB, para contar con transacciones e integridad referencial.'],
                ['Codificación', 'utf8mb4 con intercalación utf8mb4_unicode_ci.'],
                ['Nombres de tabla', 'En español, en minúsculas y en plural.'],
                ['Llave primaria', 'Campo id_⟨entidad⟩ de tipo INT autoincremental.'],
                ['Llave foránea', 'Conserva el nombre del campo referenciado.'],
                ['Importes', 'DECIMAL(10,2). Nunca se usan tipos de punto flotante para dinero.'],
                ['Baja lógica', 'Campo estado de tipo TINYINT(1): 1 activo, 0 inactivo.'],
                ['Auditoría', 'Campos created_at y updated_at gestionados por la propia base de datos.'],
            ],
            [26, 74],
            { primeraColumnaNegrita: true }
        ),

        C.titulo2('1.2. Nomenclatura de las llaves'),
        C.tabla(
            ['Sigla', 'Significado'],
            [
                ['PK', 'Primary Key. Llave primaria de la tabla.'],
                ['FK', 'Foreign Key. Llave foránea que referencia a otra tabla.'],
                ['UK', 'Unique Key. Campo o combinación de campos que no admite duplicados.'],
            ],
            [12, 88],
            { primeraColumnaNegrita: true, alineaciones: [CENTRO, null] }
        ),

        C.titulo2('1.3. Resumen de tablas'),
        C.tabla(
            ['N°', 'Tabla', 'Campos', 'Descripción'],
            D.TABLAS.map((t, i) => [String(i + 1), t.nombre, String(t.campos.length), t.descripcion]),
            [7, 22, 10, 61],
            { alineaciones: [CENTRO, null, CENTRO, null], mono: false }
        ),

        C.titulo2('1.4. Modelo entidad-relación'),
        C.texto(
            'El siguiente esquema resume las relaciones entre las nueve tablas. La versión ' +
            'ampliada, en página horizontal, se encuentra en el Documento 04 · Diagramas UML.'),
        ...C.imagen('diagramas/03_modelo_entidad_relacion.png', 620,
            'Figura 1. Modelo entidad-relación de la base de datos'),

        C.saltoPagina(),
        C.titulo1('2. Detalle de las tablas'),
    ];

    D.TABLAS.forEach((t, indice) => {
        hijos.push(C.titulo2('2.' + (indice + 1) + '. Tabla ' + t.nombre));
        hijos.push(C.texto(t.descripcion));
        hijos.push(C.tabla(
            ['Campo', 'Tipo', 'Long.', 'Nulo', 'Llave', 'Descripción'],
            t.campos,
            [19, 11, 8, 7, 8, 47],
            {
                primeraColumnaNegrita: true,
                mono: true,
                alineaciones: [null, CENTRO, CENTRO, CENTRO, CENTRO, null],
            }
        ));
        hijos.push(C.espacio(240));
    });

    hijos.push(
        C.saltoPagina(),
        C.titulo1('3. Relaciones entre tablas'),
        C.tabla(
            ['Tabla origen', 'Tabla destino', 'Cardinalidad', 'Regla de borrado'],
            [
                ['productos.id_categoria', 'categorias.id_categoria', '1 : N', 'RESTRICT'],
                ['ventas.id_usuario', 'usuarios.id_usuario', '1 : N', 'RESTRICT'],
                ['ventas.id_cliente', 'clientes.id_cliente', '0..1 : N', 'SET NULL'],
                ['detalle_venta.id_venta', 'ventas.id_venta', '1 : N', 'CASCADE'],
                ['detalle_venta.id_producto', 'productos.id_producto', '1 : N', 'RESTRICT'],
                ['movimientos_inventario.id_producto', 'productos.id_producto', '1 : N', 'RESTRICT'],
                ['movimientos_inventario.id_venta', 'ventas.id_venta', '0..1 : N', 'SET NULL'],
                ['movimientos_inventario.id_usuario', 'usuarios.id_usuario', '1 : N', 'RESTRICT'],
            ],
            [33, 30, 17, 20],
            { mono: true, alineaciones: [null, null, CENTRO, CENTRO] }
        ),
        C.espacio(200),
        C.texto(
            'La regla RESTRICT impide borrar un registro que todavía tiene dependientes, ' +
            'protegiendo el historial. CASCADE se usa únicamente entre una venta y sus ' +
            'líneas de detalle, que no tienen sentido por separado. SET NULL permite ' +
            'desvincular un cliente sin perder la venta.'),

        C.titulo1('4. Índices definidos'),
        C.tabla(
            ['Tabla', 'Índice', 'Campos', 'Propósito'],
            [
                ['productos', 'idx_productos_nombre', 'nombre', 'Acelerar la búsqueda por nombre en el punto de venta.'],
                ['productos', 'idx_productos_categoria', 'id_categoria', 'Filtrar el catálogo por categoría.'],
                ['productos', 'uq_productos_codigo', 'codigo_barras', 'Garantizar códigos de barras únicos y búsqueda inmediata al escanear.'],
                ['clientes', 'idx_clientes_nombres', 'apellidos, nombres', 'Ordenar y buscar clientes por su nombre.'],
                ['ventas', 'idx_ventas_fecha', 'fecha', 'Filtrar el historial y los reportes por período.'],
                ['ventas', 'idx_ventas_estado', 'estado', 'Separar comprobantes emitidos de anulados.'],
                ['ventas', 'uq_ventas_comprobante', 'tipo_comprobante, serie, correlativo', 'Impedir comprobantes duplicados.'],
                ['detalle_venta', 'idx_detalle_venta', 'id_venta', 'Recuperar el detalle de un comprobante.'],
                ['detalle_venta', 'idx_detalle_producto', 'id_producto', 'Calcular los productos más vendidos.'],
                ['movimientos_inventario', 'idx_movimientos_producto', 'id_producto, fecha', 'Construir el kardex de un producto.'],
                ['movimientos_inventario', 'idx_movimientos_fecha', 'fecha', 'Consultar los movimientos de un período.'],
            ],
            [20, 24, 24, 32],
            { mono: true }
        ),

        C.titulo1('5. Valores iniciales'),
        C.texto(
            'El archivo database/seed.sql carga los datos mínimos para que el sistema pueda ' +
            'operar apenas se instala.'),
        C.tabla(
            ['Tabla', 'Registros', 'Contenido'],
            [
                ['configuracion', '7', 'Razón social, RUC, dirección, teléfono, porcentaje de IGV y moneda.'],
                ['usuarios', '2', 'Una cuenta de administrador y una de cajero para las pruebas.'],
                ['series_comprobante', '2', 'Serie B001 para boletas y F001 para facturas, ambas iniciadas en cero.'],
                ['categorias', '8', 'Abarrotes, bebidas, lácteos, snacks, limpieza, cuidado personal, panadería y embutidos.'],
                ['productos', '41', 'Catálogo de productos representativos de un minimarket peruano.'],
                ['clientes', '10', 'Siete personas naturales con DNI y tres empresas con RUC.'],
            ],
            [22, 14, 64],
            { primeraColumnaNegrita: true, mono: true, alineaciones: [null, CENTRO, null] }
        )
    );

    return C.documento('Diccionario de Datos', hijos);
}

// =====================================================================
// 6. MANUAL DE USUARIO
// =====================================================================
function documentoManual() {
    const hijos = [
        ...C.portada('06', 'Manual de Usuario',
            'Guía paso a paso para operar el sistema'),
        ...C.indice(),

        C.titulo1('1. Introducción'),
        C.texto(
            'Este manual explica cómo usar el Sistema Web para la Gestión de un Minimarket. ' +
            'Está organizado por módulos y señala en cada caso qué rol puede acceder a ellos. ' +
            'Las capturas corresponden al sistema en funcionamiento.'),

        C.titulo2('1.1. Requisitos para usar el sistema'),
        C.vinieta('Un equipo con Windows y Laragon instalado, o acceso por red al equipo que lo tenga.'),
        C.vinieta('Un navegador actualizado: Chrome, Edge o Firefox.'),
        C.vinieta('Una cuenta de usuario creada por el administrador.'),
        C.vinieta('Opcional: un lector de código de barras y una impresora de tickets de 80 mm.'),

        C.titulo2('1.2. Cómo ingresar al sistema'),
        C.parrafoMixto([
            'Abra el navegador y escriba la dirección ',
            { t: 'http://localhost/MINIMARKET/', mono: true, n: true },
            '. Si trabaja desde otro equipo de la red, reemplace ',
            { t: 'localhost', mono: true },
            ' por la dirección IP del equipo donde está instalado el sistema.',
        ]),

        C.titulo2('1.3. Roles y permisos'),
        C.texto(
            'El sistema distingue dos roles. Lo que cada usuario puede hacer depende del rol ' +
            'que el administrador le haya asignado.'),
        C.tabla(
            ['Rol', 'Qué puede hacer'],
            [
                ['Administrador',
                    'Todo: punto de venta, catálogo, categorías, inventario, clientes, historial completo de ventas, anulaciones, dashboard, reportes y usuarios.'],
                ['Cajero',
                    'Punto de venta, consulta del catálogo, registro de clientes y consulta de sus propias ventas.'],
            ],
            [22, 78],
            { primeraColumnaNegrita: true }
        ),

        C.saltoPagina(),
        C.titulo1('2. Iniciar y cerrar sesión'),

        C.titulo2('2.1. Iniciar sesión'),
        C.texto('Para entrar al sistema:'),
        C.vinieta('Escriba su nombre de usuario en el primer campo.'),
        C.vinieta('Escriba su contraseña en el segundo campo. Puede presionar el ícono del ojo para verificar lo que escribió.'),
        C.vinieta('Presione el botón Ingresar.'),
        C.texto(
            'Si es administrador llegará al dashboard; si es cajero llegará directamente al ' +
            'punto de venta, listo para atender.'),
        ...C.imagen('capturas/login.png', 600, 'Figura 1. Pantalla de inicio de sesión'),

        C.parrafoMixto([
            { t: 'Si el sistema muestra "Usuario o contraseña incorrectos": ', n: true },
            'verifique que no tenga activado el bloqueo de mayúsculas. Si el problema persiste, ' +
            'pida al administrador que restablezca su contraseña desde el módulo de Usuarios.',
        ]),

        C.titulo2('2.2. Cerrar sesión'),
        C.texto(
            'Presione "Cerrar sesión" en la parte inferior del menú lateral. Es importante ' +
            'hacerlo al terminar el turno para que nadie más registre ventas con su cuenta.'),

        C.saltoPagina(),
        C.titulo1('3. Punto de venta'),
        C.parrafoMixto([{ t: 'Disponible para: ', n: true }, 'Administrador y Cajero.']),
        C.texto(
            'Es la pantalla donde se registran las ventas. Está dividida en dos partes: a la ' +
            'izquierda se arma el carrito con los productos y a la derecha se completan los ' +
            'datos del comprobante y se cobra.'),
        ...C.imagen('capturas/pos_vacio.png', 600, 'Figura 2. Punto de venta al iniciar'),

        C.titulo2('3.1. Agregar productos a la venta'),
        C.texto('Hay dos formas de agregar un producto:'),
        C.vinieta('Con el lector de código de barras: apunte al código y dispare. El producto se agrega solo.'),
        C.vinieta('Escribiendo: escriba parte del nombre en el buscador y elija el producto de la lista que aparece.'),
        C.texto(
            'Cada línea del carrito muestra el precio unitario, la cantidad y el subtotal. ' +
            'Use los botones − y + para ajustar la cantidad, o escriba el número directamente. ' +
            'El botón Quitar elimina la línea.'),
        ...C.imagen('capturas/pos_carrito.png', 600, 'Figura 3. Punto de venta con productos en el carrito'),

        C.parrafoMixto([
            { t: 'El sistema no permite vender más unidades de las que hay en stock. ', n: true },
            'Si intenta hacerlo, ajustará la cantidad al máximo disponible y se lo avisará.',
        ]),

        C.titulo2('3.2. Elegir el tipo de comprobante'),
        C.texto(
            'Por defecto se emite una boleta. Si el cliente necesita factura, presione ' +
            '"Factura" y seleccione un cliente que tenga RUC registrado. Sin un cliente con ' +
            'RUC el sistema no permitirá emitir la factura.'),

        C.titulo2('3.3. Seleccionar el cliente'),
        C.texto(
            'Escriba el número de documento o el nombre del cliente en el campo Cliente y ' +
            'elíjalo de la lista. Para una boleta este paso es opcional: si no selecciona a ' +
            'nadie, la venta se registra como "Cliente varios". Si el cliente no está ' +
            'registrado, puede darlo de alta desde el módulo de Clientes.'),

        C.titulo2('3.4. Cobrar'),
        C.texto('Elija el método de pago:'),
        C.vinieta('Efectivo: ingrese el monto que le entrega el cliente. El sistema calcula el vuelto automáticamente.'),
        C.vinieta('Tarjeta, Yape o Plin: se cobra el importe exacto y el campo de monto recibido desaparece.'),
        C.texto(
            'Los botones de S/ 10, S/ 20, S/ 50, S/ 100 y S/ 200 suman rápidamente al monto ' +
            'recibido. El botón "Exacto" coloca el importe justo de la venta.'),
        C.texto(
            'Cuando todo esté listo, presione "Registrar venta" o simplemente la tecla F9. ' +
            'El sistema confirmará la operación mostrando el número del comprobante, el total ' +
            'cobrado y el vuelto.'),

        C.titulo2('3.5. Imprimir el comprobante'),
        C.texto(
            'En la ventana de confirmación presione "Imprimir": se abrirá el comprobante en ' +
            'PDF, listo para enviarlo a la impresora de tickets. Presione "Nueva venta" para ' +
            'dejar la pantalla lista para el siguiente cliente.'),

        C.titulo2('3.6. Atajos de teclado'),
        C.tabla(
            ['Tecla', 'Acción'],
            [
                ['F9', 'Registrar la venta.'],
                ['Enter', 'Agregar el primer producto de la lista de resultados.'],
                ['Esc', 'Cerrar las listas de resultados abiertas.'],
            ],
            [16, 84],
            { primeraColumnaNegrita: true, mono: true }
        ),

        C.saltoPagina(),
        C.titulo1('4. Productos'),
        C.parrafoMixto([
            { t: 'Disponible para: ', n: true },
            'Administrador (registro y edición) y Cajero (solo consulta).',
        ]),
        C.texto(
            'Muestra el catálogo completo con su precio, su stock y su estado. Los productos ' +
            'cuyo stock llegó al mínimo aparecen con el número en rojo.'),
        ...C.imagen('capturas/productos.png', 600, 'Figura 4. Catálogo de productos'),

        C.titulo2('4.1. Buscar y filtrar'),
        C.vinieta('Escriba en el campo Buscar el nombre o el código de barras del producto.'),
        C.vinieta('Use el selector de Categoría para ver solo una familia de productos.'),
        C.vinieta('Marque "Solo stock bajo" para ver únicamente los productos que hay que reponer.'),

        C.titulo2('4.2. Registrar un producto nuevo'),
        C.texto('Presione "Nuevo producto" y complete el formulario:'),
        ...C.imagen('capturas/producto_nuevo.png', 560, 'Figura 5. Formulario de registro de producto'),
        C.tabla(
            ['Campo', 'Qué debe ingresar'],
            [
                ['Nombre', 'Nombre comercial del producto, como aparece en el envase.'],
                ['Código de barras', 'Escanee el código del producto o escríbalo. No puede repetirse.'],
                ['Categoría', 'Familia a la que pertenece el producto.'],
                ['Precio de compra', 'Lo que le cuesta el producto al minimarket.'],
                ['Precio de venta', 'Lo que paga el cliente. Este precio ya incluye el IGV.'],
                ['Stock inicial', 'Unidades con las que empieza. Quedan registradas en el kardex.'],
                ['Stock mínimo', 'Cuando el stock llegue a esta cantidad, el sistema le avisará que debe reponer.'],
                ['Unidad de medida', 'Unidad, bolsa, botella, lata, caja, paquete, kilo o litro.'],
                ['Imagen', 'Opcional. Archivo JPG, PNG o WEBP de hasta 2 MB.'],
            ],
            [24, 76],
            { primeraColumnaNegrita: true }
        ),

        C.titulo2('4.3. Editar un producto'),
        C.texto(
            'Presione "Editar" en la fila del producto. Puede cambiar todos sus datos salvo ' +
            'el stock, que aparece bloqueado: el stock solo se modifica desde el módulo de ' +
            'Inventario, para que el kardex siempre explique cada cambio.'),

        C.titulo2('4.4. Desactivar un producto'),
        C.texto(
            'Presione "Desactivar". El producto deja de aparecer en el punto de venta pero ' +
            'conserva todo su historial de ventas y movimientos. Puede reactivarlo cuando ' +
            'quiera.'),

        C.saltoPagina(),
        C.titulo1('5. Categorías'),
        C.parrafoMixto([{ t: 'Disponible para: ', n: true }, 'Administrador.']),
        C.texto(
            'Las categorías agrupan los productos del catálogo y alimentan el reporte de ' +
            'ventas por categoría. La columna "Productos" muestra cuántos productos activos ' +
            'tiene cada una.'),
        ...C.imagen('capturas/categorias.png', 600, 'Figura 6. Módulo de categorías'),
        C.texto(
            'Una categoría que todavía tiene productos activos no puede desactivarse. Primero ' +
            'reasigne o desactive esos productos.'),

        C.saltoPagina(),
        C.titulo1('6. Inventario'),
        C.parrafoMixto([{ t: 'Disponible para: ', n: true }, 'Administrador.']),
        C.texto(
            'Aquí se registra todo cambio de stock que no proviene de una venta. A la ' +
            'izquierda está el formulario de registro y a la derecha el historial de ' +
            'movimientos.'),
        ...C.imagen('capturas/inventario.png', 600, 'Figura 7. Módulo de inventario'),

        C.titulo2('6.1. Tipos de movimiento'),
        C.tabla(
            ['Tipo', 'Cuándo usarlo', 'Efecto en el stock'],
            [
                ['Entrada', 'Llegó mercadería del proveedor.', 'Suma la cantidad indicada.'],
                ['Salida', 'Producto vencido, roto o perdido.', 'Resta la cantidad indicada.'],
                ['Ajuste', 'Después de un conteo físico, el stock real no coincide con el del sistema.', 'Deja el stock en la cantidad contada.'],
            ],
            [14, 51, 35],
            { primeraColumnaNegrita: true }
        ),
        C.texto(
            'En un ajuste, la cantidad que se ingresa es el stock que realmente contó, no la ' +
            'diferencia. El sistema calcula la diferencia y la registra.'),

        C.titulo2('6.2. Registrar un movimiento'),
        C.vinieta('Seleccione el producto. El sistema le mostrará su stock actual.'),
        C.vinieta('Elija el tipo de movimiento.'),
        C.vinieta('Ingrese la cantidad.'),
        C.vinieta('Escriba el motivo. Sea específico: "Compra a Distribuidora San Martín" es mejor que "compra".'),
        C.vinieta('Presione "Registrar movimiento".'),

        C.titulo2('6.3. Consultar el kardex de un producto'),
        C.texto(
            'Desde el catálogo, presione "Kardex" en cualquier producto. Verá su ficha con el ' +
            'stock actual y el historial completo de movimientos, del más reciente al más ' +
            'antiguo. Cada fila muestra el stock antes y después del movimiento, de modo que ' +
            'siempre se puede explicar cómo se llegó al stock actual.'),
        ...C.imagen('capturas/kardex.png', 600, 'Figura 8. Kardex de un producto'),

        C.saltoPagina(),
        C.titulo1('7. Clientes'),
        C.parrafoMixto([{ t: 'Disponible para: ', n: true }, 'Administrador y Cajero.']),
        C.texto(
            'Registra a los clientes del minimarket. La lista muestra cuántas compras hizo ' +
            'cada uno y cuánto ha gastado en total.'),
        ...C.imagen('capturas/clientes.png', 600, 'Figura 9. Módulo de clientes'),

        C.titulo2('7.1. Registrar un cliente'),
        C.texto(
            'Presione "Nuevo cliente" y elija primero el tipo de documento: el formulario se ' +
            'adapta según su elección.'),
        ...C.imagen('capturas/cliente_nuevo.png', 560, 'Figura 10. Formulario de registro de cliente'),
        C.tabla(
            ['Tipo', 'Documento', 'Datos que se piden'],
            [
                ['DNI', '8 dígitos', 'Nombres y apellidos. Es el caso de las personas naturales.'],
                ['RUC', '11 dígitos', 'Razón social. Es el caso de las empresas que necesitan factura.'],
            ],
            [14, 18, 68],
            { primeraColumnaNegrita: true }
        ),
        C.texto(
            'El teléfono, el correo y la dirección son opcionales, aunque conviene registrar ' +
            'la dirección de las empresas porque se imprime en la factura.'),

        C.saltoPagina(),
        C.titulo1('8. Ventas'),
        C.parrafoMixto([
            { t: 'Disponible para: ', n: true },
            'Administrador (todas las ventas) y Cajero (solo las propias).',
        ]),
        C.texto(
            'Muestra el historial de comprobantes emitidos. Al abrirlo verá las ventas del mes ' +
            'en curso, con tres tarjetas de resumen: cantidad de comprobantes, total emitido y ' +
            'total anulado.'),
        ...C.imagen('capturas/ventas.png', 600, 'Figura 11. Historial de ventas'),

        C.titulo2('8.1. Buscar una venta'),
        C.vinieta('Ajuste el rango de fechas con los campos Desde y Hasta.'),
        C.vinieta('Filtre por tipo de comprobante o por estado.'),
        C.vinieta('Escriba el número de comprobante o el documento del cliente en el campo Buscar.'),

        C.titulo2('8.2. Ver el detalle de una venta'),
        C.texto(
            'Presione "Ver" para abrir el comprobante con todos sus productos, los importes y ' +
            'los datos del cliente. Desde ahí también puede imprimirlo.'),
        ...C.imagen('capturas/detalle_venta.png', 600, 'Figura 12. Detalle de una venta'),

        C.titulo2('8.3. Anular una venta'),
        C.parrafoMixto([{ t: 'Solo el administrador puede anular comprobantes.', n: true }]),
        C.texto(
            'Presione "Anular" en la fila de la venta y escriba el motivo (mínimo 5 ' +
            'caracteres). Al confirmar, el sistema devuelve al stock las unidades vendidas y ' +
            'marca el comprobante como anulado. La venta no se borra: queda en el historial ' +
            'con su motivo y su fecha de anulación, y deja de sumar en los reportes.'),

        C.saltoPagina(),
        C.titulo1('9. Dashboard'),
        C.parrafoMixto([{ t: 'Disponible para: ', n: true }, 'Administrador.']),
        C.texto(
            'Es la pantalla inicial del administrador. Resume el estado del negocio en un solo ' +
            'vistazo.'),
        ...C.imagen('capturas/dashboard.png', 600, 'Figura 13. Dashboard de indicadores'),
        C.tabla(
            ['Panel', 'Qué muestra'],
            [
                ['Ventas de hoy', 'Importe cobrado y cantidad de comprobantes emitidos en el día.'],
                ['Ventas del mes', 'Acumulado del mes en curso.'],
                ['Ticket promedio', 'Cuánto gasta en promedio cada cliente, en el mes en curso.'],
                ['Stock bajo mínimo', 'Cuántos productos necesitan reposición.'],
                ['Últimos 7 días', 'Evolución diaria de las ventas.'],
                ['Ventas por categoría', 'Qué familias de productos aportan más ingresos.'],
                ['Productos más vendidos', 'Los ocho productos con más unidades vendidas.'],
                ['Productos por reponer', 'Listado de los productos en alerta, con su stock y su mínimo.'],
                ['Últimas ventas', 'Los ocho comprobantes más recientes.'],
            ],
            [26, 74],
            { primeraColumnaNegrita: true }
        ),
        C.texto('Las ventas anuladas no se consideran en ninguno de estos indicadores.'),

        C.saltoPagina(),
        C.titulo1('10. Reportes'),
        C.parrafoMixto([{ t: 'Disponible para: ', n: true }, 'Administrador.']),
        C.texto(
            'Genera el análisis de ventas de cualquier período. Elija las fechas o use uno de ' +
            'los atajos: Hoy, Últimos 7 días, Este mes o Mes anterior.'),
        ...C.imagen('capturas/reportes.png', 600, 'Figura 14. Módulo de reportes'),

        C.titulo2('10.1. Qué incluye el reporte'),
        C.vinieta('Resumen del período: comprobantes, operación gravada, IGV, total y ticket promedio.'),
        C.vinieta('Evolución diaria de las ventas.'),
        C.vinieta('Distribución por método de pago.'),
        C.vinieta('Ventas por cajero, con su ticket promedio.'),
        C.vinieta('Ventas por categoría.'),
        C.vinieta('Ranking de los quince productos más vendidos.'),
        C.vinieta('Productos por reponer, que no dependen del período.'),

        C.titulo2('10.2. Exportar el reporte'),
        C.tabla(
            ['Botón', 'Resultado'],
            [
                ['PDF', 'Documento A4 con el resumen, los desgloses y el detalle de todos los comprobantes del período. Listo para imprimir o adjuntar.'],
                ['Excel (CSV)', 'Archivo separado por punto y coma que se abre directamente en Excel, con una fila por comprobante y una fila final de totales.'],
            ],
            [18, 82],
            { primeraColumnaNegrita: true }
        ),

        C.saltoPagina(),
        C.titulo1('11. Usuarios'),
        C.parrafoMixto([{ t: 'Disponible para: ', n: true }, 'Administrador.']),
        C.texto('Administra las cuentas de acceso del personal del minimarket.'),
        ...C.imagen('capturas/usuarios.png', 600, 'Figura 15. Módulo de usuarios'),

        C.titulo2('11.1. Crear un usuario'),
        C.texto('Presione "Nuevo usuario" y complete:'),
        C.tabla(
            ['Campo', 'Indicaciones'],
            [
                ['Nombre completo', 'Nombre real de la persona. Aparecerá en los comprobantes que registre.'],
                ['Usuario', 'Entre 4 y 50 caracteres: letras, números, punto o guión bajo.'],
                ['Contraseña', 'Mínimo 6 caracteres.'],
                ['Rol', 'Cajero para el personal de caja; Administrador para quien dirige el negocio.'],
            ],
            [22, 78],
            { primeraColumnaNegrita: true }
        ),

        C.titulo2('11.2. Cambiar la contraseña de un usuario'),
        C.texto(
            'Presione "Editar" y escriba la nueva contraseña. Si deja ese campo vacío, la ' +
            'contraseña actual se mantiene sin cambios.'),

        C.titulo2('11.3. Restricciones de seguridad'),
        C.vinieta('No puede desactivar su propia cuenta.'),
        C.vinieta('No puede desactivar ni degradar al único administrador activo del sistema.'),
        C.vinieta('Los usuarios no se eliminan, se desactivan: así se conserva el historial de sus ventas.'),

        C.saltoPagina(),
        C.titulo1('12. Preguntas frecuentes'),
        C.tabla(
            ['Situación', 'Qué hacer'],
            [
                ['El sistema dice "Acceso denegado".',
                    'Su rol no tiene permiso para esa pantalla. Presione el botón para volver a su pantalla principal.'],
                ['El sistema dice "La sesión expiró".',
                    'Estuvo demasiado tiempo inactivo. Vuelva a iniciar sesión e intente de nuevo.'],
                ['No encuentro un producto en el punto de venta.',
                    'Verifique que esté activo en el catálogo. Los productos desactivados no aparecen en la venta.'],
                ['El lector de código de barras no agrega el producto.',
                    'Haga clic en el campo de búsqueda antes de escanear, para que el lector escriba ahí.'],
                ['Me equivoqué en una venta ya registrada.',
                    'Pida al administrador que la anule indicando el motivo. El stock se restituye automáticamente.'],
                ['El stock del sistema no coincide con el real.',
                    'Haga un conteo físico y registre un Ajuste en el módulo de Inventario con la cantidad contada.'],
                ['Necesito emitir una factura y no puedo.',
                    'Verifique que el cliente esté registrado con RUC. Sin RUC solo puede emitirse una boleta.'],
                ['Olvidé mi contraseña.',
                    'Solicite al administrador que le asigne una nueva desde el módulo de Usuarios.'],
                ['Los gráficos no se ven.',
                    'Actualice la página con Ctrl + F5. El sistema no necesita internet para dibujarlos.'],
            ],
            [34, 66],
            { primeraColumnaNegrita: true }
        ),
    ];

    return C.documento('Manual de Usuario', hijos);
}

// =====================================================================
(async () => {
    console.log('Generando los documentos Word...');

    if (!process.env.SOLO_DIAGRAMAS) {
        await C.guardar(documentoRequerimientos(), '01_Documento_de_Requerimientos.docx');
        await C.guardar(documentoHistorias(), '02_Historias_de_Usuario.docx');
        await C.guardar(documentoCasosDeUso(), '03_Casos_de_Uso.docx');
    }
    await C.guardar(documentoDiagramas(), process.env.ARCHIVO_DIAGRAMAS || '04_Diagramas_UML.docx');
    if (!process.env.SOLO_DIAGRAMAS) {
        await C.guardar(documentoDiccionario(), '05_Diccionario_de_Datos.docx');
        await C.guardar(documentoManual(), '06_Manual_de_Usuario.docx');
    }

    console.log('Listo.');
})().catch(e => {
    console.error('Error:', e);
    process.exit(1);
});
