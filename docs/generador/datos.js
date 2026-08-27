/**
 * Contenido de los documentos: requerimientos, reglas de negocio,
 * historias de usuario, casos de uso y diccionario de datos.
 */

// ---------------------------------------------------------------- actores
const ACTORES = [
    ['Administrador',
        'Dueño o encargado del minimarket. Tiene acceso total al sistema: catálogo, ' +
        'inventario, clientes, ventas, reportes y usuarios. Es el único que puede anular ' +
        'comprobantes y crear cuentas de acceso.'],
    ['Cajero',
        'Personal de atención en caja. Registra las ventas, consulta el catálogo y el ' +
        'historial de sus propias ventas, y registra clientes nuevos. No puede modificar ' +
        'precios, stock ni anular comprobantes.'],
    ['Sistema',
        'Actor no humano. Ejecuta automáticamente el cálculo del IGV, el descuento de ' +
        'stock, la asignación del correlativo y el registro del kardex cada vez que se ' +
        'confirma una venta.'],
];

// ---------------------------------------------------------------- requerimientos funcionales
const RF = [
    // Seguridad y acceso
    ['RF-01', 'Iniciar sesión',
        'El sistema debe permitir el ingreso mediante usuario y contraseña, verificando ' +
        'la contraseña contra su hash almacenado.',
        'Administrador, Cajero', 'Alta'],
    ['RF-02', 'Redirigir según el rol',
        'Al iniciar sesión, el administrador debe llegar al dashboard y el cajero al ' +
        'punto de venta.',
        'Sistema', 'Media'],
    ['RF-03', 'Restringir el acceso por rol',
        'El sistema debe bloquear en el servidor todo intento de acceder a una pantalla ' +
        'o acción que no corresponda al rol del usuario, aunque se escriba la URL a mano.',
        'Sistema', 'Alta'],
    ['RF-04', 'Cerrar sesión',
        'El usuario debe poder cerrar su sesión desde cualquier pantalla, destruyendo la ' +
        'sesión del servidor.',
        'Administrador, Cajero', 'Alta'],

    // Catálogo
    ['RF-05', 'Registrar producto',
        'El administrador debe poder registrar un producto con código de barras, nombre, ' +
        'descripción, categoría, precio de compra, precio de venta, stock inicial, stock ' +
        'mínimo, unidad de medida e imagen.',
        'Administrador', 'Alta'],
    ['RF-06', 'Modificar producto',
        'El administrador debe poder editar los datos de un producto. El stock no se ' +
        'modifica desde esta pantalla.',
        'Administrador', 'Alta'],
    ['RF-07', 'Desactivar producto',
        'El administrador debe poder dar de baja lógica a un producto, que deja de ' +
        'aparecer en el punto de venta pero conserva su historial.',
        'Administrador', 'Media'],
    ['RF-08', 'Buscar y filtrar productos',
        'El sistema debe permitir buscar productos por nombre o código de barras y ' +
        'filtrarlos por categoría o por stock bajo el mínimo.',
        'Administrador, Cajero', 'Alta'],
    ['RF-09', 'Validar código de barras único',
        'El sistema debe impedir registrar dos productos con el mismo código de barras.',
        'Sistema', 'Alta'],
    ['RF-10', 'Gestionar categorías',
        'El administrador debe poder registrar, editar y desactivar categorías de ' +
        'productos, con nombre único.',
        'Administrador', 'Media'],

    // Inventario
    ['RF-11', 'Registrar entrada de mercadería',
        'El administrador debe poder registrar el ingreso de unidades al stock indicando ' +
        'producto, cantidad y motivo.',
        'Administrador', 'Alta'],
    ['RF-12', 'Registrar salida de inventario',
        'El administrador debe poder registrar salidas que no son ventas (merma, rotura, ' +
        'vencimiento), validando que exista stock suficiente.',
        'Administrador', 'Alta'],
    ['RF-13', 'Registrar ajuste por conteo físico',
        'El administrador debe poder dejar el stock en la cantidad realmente contada, ' +
        'quedando registrada la diferencia.',
        'Administrador', 'Media'],
    ['RF-14', 'Consultar el kardex de un producto',
        'El sistema debe mostrar todos los movimientos de un producto con fecha, tipo, ' +
        'cantidad, stock anterior, stock nuevo, motivo y usuario responsable.',
        'Administrador', 'Alta'],
    ['RF-15', 'Alertar productos bajo el mínimo',
        'El sistema debe señalar los productos cuyo stock sea igual o menor al stock ' +
        'mínimo configurado, en el catálogo y en el dashboard.',
        'Sistema', 'Alta'],

    // Clientes
    ['RF-16', 'Registrar cliente',
        'El sistema debe permitir registrar clientes identificados con DNI (persona ' +
        'natural) o con RUC (empresa).',
        'Administrador, Cajero', 'Alta'],
    ['RF-17', 'Validar el documento del cliente',
        'El sistema debe exigir 8 dígitos para el DNI y 11 dígitos para el RUC, y debe ' +
        'impedir documentos duplicados.',
        'Sistema', 'Alta'],
    ['RF-18', 'Modificar y desactivar clientes',
        'El sistema debe permitir editar los datos de un cliente; solo el administrador ' +
        'puede desactivarlo.',
        'Administrador, Cajero', 'Media'],
    ['RF-19', 'Consultar el historial de compras del cliente',
        'El sistema debe mostrar, por cliente, la cantidad de compras realizadas y el ' +
        'monto acumulado.',
        'Administrador, Cajero', 'Baja'],

    // Punto de venta
    ['RF-20', 'Buscar productos en el punto de venta',
        'El cajero debe poder buscar un producto escribiendo parte de su nombre o de su ' +
        'código, viendo precio y stock disponible.',
        'Cajero', 'Alta'],
    ['RF-21', 'Agregar producto por código de barras',
        'El sistema debe agregar el producto al carrito automáticamente cuando se ' +
        'escanea un código de barras completo.',
        'Cajero', 'Alta'],
    ['RF-22', 'Gestionar el carrito de la venta',
        'El cajero debe poder cambiar la cantidad de cada línea, quitar líneas y vaciar ' +
        'el carrito completo.',
        'Cajero', 'Alta'],
    ['RF-23', 'Calcular subtotal, IGV y total',
        'El sistema debe descomponer el IGV del precio de venta y mostrar en todo momento ' +
        'la operación gravada, el IGV y el total.',
        'Sistema', 'Alta'],
    ['RF-24', 'Seleccionar el tipo de comprobante',
        'El cajero debe poder elegir entre boleta y factura. La factura exige un cliente ' +
        'con RUC.',
        'Cajero', 'Alta'],
    ['RF-25', 'Seleccionar el método de pago',
        'El sistema debe permitir registrar el pago en efectivo, con tarjeta, con Yape o ' +
        'con Plin.',
        'Cajero', 'Media'],
    ['RF-26', 'Calcular el vuelto',
        'Para los pagos en efectivo el sistema debe calcular el vuelto a partir del monto ' +
        'recibido, e impedir cerrar la venta si el monto es menor al total.',
        'Sistema', 'Alta'],
    ['RF-27', 'Registrar la venta',
        'El sistema debe grabar la venta con su detalle, descontar el stock y registrar ' +
        'el kardex dentro de una única transacción.',
        'Sistema', 'Alta'],
    ['RF-28', 'Asignar serie y correlativo',
        'El sistema debe asignar automáticamente el siguiente número de comprobante de la ' +
        'serie correspondiente, sin repetir ni saltar números.',
        'Sistema', 'Alta'],
    ['RF-29', 'Emitir el comprobante en PDF',
        'El sistema debe generar el comprobante en formato ticket de 80 mm con los datos ' +
        'del negocio, el detalle, el IGV, el total y el importe en letras.',
        'Sistema', 'Alta'],

    // Historial de ventas
    ['RF-30', 'Consultar el historial de ventas',
        'El sistema debe listar las ventas filtrables por rango de fechas, tipo de ' +
        'comprobante, estado y número. El cajero solo ve sus propias ventas.',
        'Administrador, Cajero', 'Alta'],
    ['RF-31', 'Consultar el detalle de una venta',
        'El sistema debe mostrar los productos vendidos, los importes y los datos del ' +
        'comprobante.',
        'Administrador, Cajero', 'Media'],
    ['RF-32', 'Anular una venta',
        'El administrador debe poder anular un comprobante indicando el motivo. El stock ' +
        'vendido debe volver al inventario.',
        'Administrador', 'Alta'],

    // Reportes
    ['RF-33', 'Consultar el dashboard',
        'El sistema debe mostrar las ventas del día y del mes, el ticket promedio, los ' +
        'productos por reponer, los más vendidos y las últimas ventas.',
        'Administrador', 'Alta'],
    ['RF-34', 'Generar reportes por rango de fechas',
        'El administrador debe poder generar reportes de ventas por período, por cajero, ' +
        'por categoría y por método de pago.',
        'Administrador', 'Alta'],
    ['RF-35', 'Exportar reportes',
        'El sistema debe permitir descargar el reporte del período en PDF y en CSV ' +
        'compatible con Excel.',
        'Administrador', 'Media'],

    // Usuarios
    ['RF-36', 'Gestionar usuarios del sistema',
        'El administrador debe poder registrar, editar, activar y desactivar usuarios, ' +
        'asignándoles el rol de administrador o de cajero.',
        'Administrador', 'Alta'],
    ['RF-37', 'Proteger al último administrador',
        'El sistema debe impedir desactivar o degradar al único administrador activo, y ' +
        'que un usuario se desactive a sí mismo.',
        'Sistema', 'Media'],
];

// ---------------------------------------------------------------- requerimientos no funcionales
const RNF = [
    ['RNF-01', 'Usabilidad', 'Interfaz adaptable',
        'La interfaz debe verse correctamente en pantallas de 1366×768 o superiores y ' +
        'adaptarse a tablets, ocultando el menú lateral en pantallas pequeñas.', 'Alta'],
    ['RNF-02', 'Usabilidad', 'Operación con teclado',
        'El punto de venta debe permitir registrar la venta con la tecla F9 y agregar ' +
        'productos con Enter, para no depender del ratón durante la atención.', 'Media'],
    ['RNF-03', 'Usabilidad', 'Mensajes comprensibles',
        'Todo error debe explicarse en lenguaje del negocio, indicando qué ocurrió y qué ' +
        'debe hacer el usuario.', 'Alta'],
    ['RNF-04', 'Rendimiento', 'Tiempo de respuesta',
        'Ninguna pantalla debe demorar más de 2 segundos en cargar con un catálogo de ' +
        'hasta 5 000 productos.', 'Media'],
    ['RNF-05', 'Rendimiento', 'Búsqueda inmediata',
        'La búsqueda de productos en el punto de venta debe responder en menos de 500 ms, ' +
        'apoyada en índices sobre nombre y código de barras.', 'Alta'],
    ['RNF-06', 'Seguridad', 'Contraseñas cifradas',
        'Las contraseñas deben almacenarse con un hash bcrypt generado por password_hash(). ' +
        'El sistema nunca guarda ni muestra la contraseña en claro.', 'Alta'],
    ['RNF-07', 'Seguridad', 'Autorización en el servidor',
        'El control de acceso por rol debe aplicarse en el servidor, no solo ocultando ' +
        'opciones del menú.', 'Alta'],
    ['RNF-08', 'Seguridad', 'Protección contra CSRF',
        'Todo formulario y todo endpoint que modifique datos debe validar un token CSRF ' +
        'asociado a la sesión.', 'Alta'],
    ['RNF-09', 'Seguridad', 'Protección contra inyección SQL',
        'Todas las consultas deben usar sentencias preparadas con parámetros. No se ' +
        'concatenan datos del usuario dentro del SQL.', 'Alta'],
    ['RNF-10', 'Seguridad', 'Protección contra XSS',
        'Todo dato mostrado en pantalla debe escaparse con htmlspecialchars() antes de ' +
        'imprimirse.', 'Alta'],
    ['RNF-11', 'Seguridad', 'Precios validados en el servidor',
        'El precio de venta se relee siempre de la base de datos: nunca se confía en el ' +
        'precio que envía el navegador.', 'Alta'],
    ['RNF-12', 'Integridad', 'Operaciones atómicas',
        'El registro y la anulación de una venta deben ejecutarse dentro de una ' +
        'transacción: o se graba todo o no se graba nada.', 'Alta'],
    ['RNF-13', 'Integridad', 'Precisión monetaria',
        'Todos los importes deben almacenarse como DECIMAL(10,2). No se admite el uso de ' +
        'tipos de punto flotante para dinero.', 'Alta'],
    ['RNF-14', 'Integridad', 'Comprobantes irrepetibles',
        'La combinación de tipo de comprobante, serie y correlativo debe ser única a nivel ' +
        'de base de datos.', 'Alta'],
    ['RNF-15', 'Trazabilidad', 'Kardex completo',
        'Toda variación de stock debe quedar registrada indicando el usuario responsable, ' +
        'la fecha y el motivo.', 'Alta'],
    ['RNF-16', 'Disponibilidad', 'Operación sin internet',
        'El sistema debe funcionar íntegramente en la red local: las hojas de estilo y las ' +
        'librerías de gráficos se sirven desde el propio servidor.', 'Alta'],
    ['RNF-17', 'Portabilidad', 'Instalación sencilla',
        'El sistema debe instalarse copiando la carpeta al directorio www de Laragon o ' +
        'XAMPP e importando dos archivos SQL.', 'Media'],
    ['RNF-18', 'Compatibilidad', 'Navegadores soportados',
        'Debe funcionar en las versiones actuales de Chrome, Edge y Firefox.', 'Media'],
    ['RNF-19', 'Mantenibilidad', 'Arquitectura por capas',
        'El código debe seguir el patrón MVC, separando modelos, controladores y vistas, ' +
        'con un único punto de conexión a la base de datos.', 'Media'],
    ['RNF-20', 'Mantenibilidad', 'Idioma del código',
        'Los nombres de tablas, campos, clases y métodos deben estar en español, igual que ' +
        'la interfaz y la documentación.', 'Baja'],
];

// ---------------------------------------------------------------- reglas de negocio
const RN = [
    ['RN-01', 'Venta solo de productos activos',
        'Únicamente pueden venderse productos con estado activo y con stock disponible.'],
    ['RN-02', 'El stock nunca es negativo',
        'Ninguna operación puede dejar el stock de un producto por debajo de cero. Si la ' +
        'cantidad solicitada supera el stock, la operación completa se cancela.'],
    ['RN-03', 'El precio de venta incluye el IGV',
        'Siguiendo la práctica del comercio minorista peruano, el precio de góndola ya ' +
        'incluye el IGV. La venta descompone el impuesto hacia atrás: ' +
        'subtotal = total ÷ 1,18 e IGV = total − subtotal. Así el total cobrado nunca ' +
        'sufre descuadres por redondeo.'],
    ['RN-04', 'La factura exige RUC',
        'Solo puede emitirse una factura si se selecciona un cliente identificado con RUC. ' +
        'En cualquier otro caso corresponde una boleta.'],
    ['RN-05', 'Correlativo único y consecutivo',
        'Cada comprobante recibe el siguiente número de su serie. El correlativo se reserva ' +
        'bloqueando la fila de la serie, de modo que dos cajas simultáneas nunca obtengan ' +
        'el mismo número.'],
    ['RN-06', 'Todo movimiento de stock se registra',
        'Cada variación de stock (venta, entrada, salida, ajuste o anulación) genera un ' +
        'registro en el kardex con el stock anterior y el nuevo.'],
    ['RN-07', 'Solo el administrador anula',
        'La anulación de un comprobante está reservada al administrador y exige registrar ' +
        'un motivo de al menos 5 caracteres.'],
    ['RN-08', 'La anulación restituye el stock',
        'Al anular una venta, las unidades vendidas vuelven al inventario como un ' +
        'movimiento de entrada. El comprobante no se elimina: queda marcado como anulado.'],
    ['RN-09', 'Baja lógica',
        'Productos, categorías, clientes y usuarios nunca se eliminan físicamente: se ' +
        'desactivan para conservar la trazabilidad del historial.'],
    ['RN-10', 'Siempre debe haber un administrador',
        'El sistema impide desactivar o cambiar de rol al único administrador activo.'],
    ['RN-11', 'Formato de los documentos de identidad',
        'El DNI tiene exactamente 8 dígitos y el RUC exactamente 11. Ambos son únicos en ' +
        'el sistema.'],
    ['RN-12', 'El pago en efectivo cubre el total',
        'Una venta en efectivo no puede cerrarse si el monto recibido es menor que el ' +
        'total. Con tarjeta o billetera digital se cobra el importe exacto.'],
    ['RN-13', 'Una categoría con productos no se desactiva',
        'No puede desactivarse una categoría que todavía tenga productos activos ' +
        'asociados.'],
    ['RN-14', 'El cajero ve solo sus ventas',
        'En el historial de ventas, el cajero accede únicamente a los comprobantes que él ' +
        'mismo registró.'],
];

// ---------------------------------------------------------------- historias de usuario
const HU = [
    {
        codigo: 'HU-01', titulo: 'Iniciar sesión en el sistema',
        rol: 'usuario del minimarket', accion: 'ingresar con mi usuario y contraseña',
        beneficio: 'acceder solo a las funciones que me corresponden',
        prioridad: 'Alta', estimacion: '3 puntos',
        criterios: [
            'Dado que ingreso credenciales correctas, cuando presiono Ingresar, entonces accedo al sistema y llego a mi pantalla inicial según mi rol.',
            'Dado que ingreso una contraseña incorrecta, cuando presiono Ingresar, entonces veo el mensaje "Usuario o contraseña incorrectos" sin que se revele si el usuario existe.',
            'Dado que mi cuenta está desactivada, cuando intento ingresar, entonces el sistema no me deja entrar.',
        ],
    },
    {
        codigo: 'HU-02', titulo: 'Registrar una venta rápidamente',
        rol: 'cajero', accion: 'registrar los productos escaneando su código de barras',
        beneficio: 'atender al cliente sin hacerlo esperar',
        prioridad: 'Alta', estimacion: '13 puntos',
        criterios: [
            'Dado que escaneo un código de barras válido, cuando el lector envía el código, entonces el producto se agrega al carrito con su precio.',
            'Dado que el producto ya está en el carrito, cuando lo vuelvo a escanear, entonces la cantidad aumenta en uno en lugar de duplicar la línea.',
            'Dado que el producto no tiene stock, cuando intento agregarlo, entonces veo un aviso y el producto no se agrega.',
            'Dado que tengo productos en el carrito, cuando presiono F9, entonces la venta se registra sin necesidad de usar el ratón.',
        ],
    },
    {
        codigo: 'HU-03', titulo: 'Ver el total con IGV desglosado',
        rol: 'cajero', accion: 'ver la operación gravada, el IGV y el total mientras armo la venta',
        beneficio: 'informar correctamente al cliente antes de cobrar',
        prioridad: 'Alta', estimacion: '5 puntos',
        criterios: [
            'Dado que agrego o quito un producto, cuando cambia el carrito, entonces los tres importes se recalculan al instante.',
            'Dado que el total es S/ 118.00, cuando reviso el desglose, entonces la operación gravada es S/ 100.00 y el IGV es S/ 18.00.',
            'Dado cualquier carrito, cuando sumo operación gravada más IGV, entonces el resultado es exactamente el total.',
        ],
    },
    {
        codigo: 'HU-04', titulo: 'Calcular el vuelto',
        rol: 'cajero', accion: 'ingresar el monto que me entrega el cliente',
        beneficio: 'saber el vuelto exacto y no equivocarme',
        prioridad: 'Alta', estimacion: '3 puntos',
        criterios: [
            'Dado que ingreso un monto mayor al total, cuando el sistema recalcula, entonces muestro el vuelto en verde.',
            'Dado que el monto es menor al total, cuando intento cobrar, entonces el sistema no registra la venta y me avisa.',
            'Dado que presiono el botón "Exacto", cuando se aplica, entonces el monto recibido queda igual al total y el vuelto en cero.',
        ],
    },
    {
        codigo: 'HU-05', titulo: 'Emitir el comprobante',
        rol: 'cajero', accion: 'imprimir la boleta o factura al terminar la venta',
        beneficio: 'entregar al cliente su comprobante',
        prioridad: 'Alta', estimacion: '8 puntos',
        criterios: [
            'Dado que registré una venta, cuando presiono Imprimir, entonces se abre el PDF del ticket en formato de 80 mm.',
            'Dado el comprobante impreso, cuando lo reviso, entonces contiene la razón social, el RUC, la serie y correlativo, el detalle, el IGV, el total y el importe en letras.',
            'Dado que emito dos boletas seguidas, cuando comparo sus números, entonces el correlativo avanzó exactamente en uno.',
        ],
    },
    {
        codigo: 'HU-06', titulo: 'Emitir factura a una empresa',
        rol: 'cajero', accion: 'emitir una factura seleccionando al cliente con RUC',
        beneficio: 'atender a los clientes que necesitan sustentar gasto',
        prioridad: 'Media', estimacion: '5 puntos',
        criterios: [
            'Dado que elijo Factura sin seleccionar cliente, cuando intento cobrar, entonces el sistema me pide seleccionar un cliente con RUC.',
            'Dado que selecciono un cliente con DNI y elijo Factura, cuando intento cobrar, entonces el sistema me indica que emita una boleta.',
            'Dado que selecciono un cliente con RUC, cuando cobro, entonces se emite una factura de la serie F001.',
        ],
    },
    {
        codigo: 'HU-07', titulo: 'Registrar un cliente durante la venta',
        rol: 'cajero', accion: 'registrar un cliente nuevo con su DNI o RUC',
        beneficio: 'asociar la compra a su historial',
        prioridad: 'Media', estimacion: '5 puntos',
        criterios: [
            'Dado que ingreso un DNI de menos de 8 dígitos, cuando guardo, entonces el sistema rechaza el registro.',
            'Dado que ingreso un documento ya registrado, cuando guardo, entonces el sistema me avisa que el cliente ya existe.',
            'Dado que elijo el tipo RUC, cuando cambia el formulario, entonces se me pide razón social en lugar de nombres y apellidos.',
        ],
    },
    {
        codigo: 'HU-08', titulo: 'Controlar el stock del catálogo',
        rol: 'administrador', accion: 'registrar productos con su precio y su stock mínimo',
        beneficio: 'mantener el catálogo ordenado y saber qué reponer',
        prioridad: 'Alta', estimacion: '8 puntos',
        criterios: [
            'Dado que registro un producto con stock inicial, cuando se guarda, entonces queda un movimiento de entrada en su kardex.',
            'Dado que el precio de venta es menor al de compra, cuando guardo, entonces el sistema lo rechaza.',
            'Dado que un código de barras ya existe, cuando guardo otro producto con ese código, entonces el sistema lo rechaza.',
        ],
    },
    {
        codigo: 'HU-09', titulo: 'Saber qué productos reponer',
        rol: 'administrador', accion: 'ver los productos cuyo stock llegó al mínimo',
        beneficio: 'hacer el pedido al proveedor a tiempo',
        prioridad: 'Alta', estimacion: '5 puntos',
        criterios: [
            'Dado que un producto tiene stock igual o menor a su mínimo, cuando abro el dashboard, entonces aparece en el panel "Productos por reponer".',
            'Dado el catálogo, cuando activo el filtro "Solo stock bajo", entonces solo veo esos productos.',
            'Dado un producto en alerta, cuando lo veo en el listado, entonces su stock aparece resaltado en rojo.',
        ],
    },
    {
        codigo: 'HU-10', titulo: 'Registrar el ingreso de mercadería',
        rol: 'administrador', accion: 'registrar las unidades que llegan del proveedor',
        beneficio: 'mantener el stock del sistema igual al stock real',
        prioridad: 'Alta', estimacion: '5 puntos',
        criterios: [
            'Dado que registro una entrada de 20 unidades, cuando se guarda, entonces el stock aumenta en 20 y queda el movimiento en el kardex.',
            'Dado que no indico el motivo, cuando guardo, entonces el sistema lo exige.',
            'Dado que registro una salida mayor al stock, cuando guardo, entonces el sistema la rechaza.',
        ],
    },
    {
        codigo: 'HU-11', titulo: 'Corregir el stock tras un conteo físico',
        rol: 'administrador', accion: 'ajustar el stock a la cantidad realmente contada',
        beneficio: 'corregir diferencias sin perder el rastro de la corrección',
        prioridad: 'Media', estimacion: '5 puntos',
        criterios: [
            'Dado que el sistema marca 47 y cuento 50, cuando registro el ajuste, entonces el stock queda en 50 y el kardex registra la diferencia de 3.',
            'Dado que la cantidad contada es igual al stock actual, cuando guardo, entonces el sistema avisa que no hay nada que ajustar.',
        ],
    },
    {
        codigo: 'HU-12', titulo: 'Revisar el historial de un producto',
        rol: 'administrador', accion: 'ver todos los movimientos de un producto',
        beneficio: 'entender por qué su stock es el que es',
        prioridad: 'Media', estimacion: '5 puntos',
        criterios: [
            'Dado un producto, cuando abro su kardex, entonces veo cada movimiento con fecha, tipo, cantidad, stock anterior, stock nuevo, motivo y usuario.',
            'Dado un movimiento originado por una venta, cuando lo veo en el kardex, entonces puedo abrir esa venta desde el mismo listado.',
        ],
    },
    {
        codigo: 'HU-13', titulo: 'Anular una venta mal registrada',
        rol: 'administrador', accion: 'anular un comprobante indicando el motivo',
        beneficio: 'corregir el error sin descuadrar el inventario',
        prioridad: 'Alta', estimacion: '8 puntos',
        criterios: [
            'Dado que anulo una venta, cuando se confirma, entonces las unidades vendidas vuelven al stock.',
            'Dado que anulo una venta, cuando reviso el historial, entonces el comprobante figura como anulado y no se borra.',
            'Dado que soy cajero, cuando intento anular, entonces el sistema no me lo permite.',
            'Dado un comprobante ya anulado, cuando intento anularlo otra vez, entonces el sistema me avisa.',
        ],
    },
    {
        codigo: 'HU-14', titulo: 'Conocer las ventas del día',
        rol: 'administrador', accion: 'ver un panel con las ventas del día y del mes',
        beneficio: 'saber cómo va el negocio sin revisar comprobante por comprobante',
        prioridad: 'Alta', estimacion: '8 puntos',
        criterios: [
            'Dado que hay ventas registradas hoy, cuando abro el dashboard, entonces veo el importe y la cantidad de comprobantes del día.',
            'Dado el dashboard, cuando lo reviso, entonces veo el gráfico de los últimos 7 días, los productos más vendidos y las ventas por categoría.',
            'Dado que una venta fue anulada, cuando reviso los indicadores, entonces esa venta no suma en los totales.',
        ],
    },
    {
        codigo: 'HU-15', titulo: 'Generar el reporte de un período',
        rol: 'administrador', accion: 'generar el reporte de ventas entre dos fechas',
        beneficio: 'analizar el desempeño del negocio y sustentar el IGV',
        prioridad: 'Alta', estimacion: '8 puntos',
        criterios: [
            'Dado un rango de fechas, cuando genero el reporte, entonces veo comprobantes, operación gravada, IGV, total y ticket promedio.',
            'Dado el reporte, cuando lo reviso, entonces veo el desglose por cajero, por categoría y por método de pago.',
            'Dado el reporte, cuando presiono PDF o Excel, entonces se descarga el archivo con los mismos importes que veo en pantalla.',
        ],
    },
    {
        codigo: 'HU-16', titulo: 'Administrar las cuentas del personal',
        rol: 'administrador', accion: 'crear y desactivar usuarios con su rol',
        beneficio: 'controlar quién entra al sistema y qué puede hacer',
        prioridad: 'Alta', estimacion: '5 puntos',
        criterios: [
            'Dado que creo un usuario con rol Cajero, cuando ese usuario ingresa, entonces solo ve las opciones de su rol.',
            'Dado que edito un usuario sin escribir contraseña, cuando guardo, entonces su contraseña anterior se conserva.',
            'Dado que soy el único administrador activo, cuando intento desactivarme, entonces el sistema lo impide.',
        ],
    },
    {
        codigo: 'HU-17', titulo: 'Trabajar sin conexión a internet',
        rol: 'dueño del minimarket', accion: 'usar el sistema aunque se caiga el internet',
        beneficio: 'no detener la atención en caja',
        prioridad: 'Media', estimacion: '3 puntos',
        criterios: [
            'Dado que no hay internet, cuando abro el sistema en la red local, entonces la interfaz se ve completa y los gráficos se dibujan.',
            'Dado que no hay internet, cuando registro una venta, entonces se graba y el comprobante se imprime con normalidad.',
        ],
    },
    {
        codigo: 'HU-18', titulo: 'Proteger los datos del negocio',
        rol: 'dueño del minimarket', accion: 'que el sistema impida accesos y cambios no autorizados',
        beneficio: 'confiar en la información que muestra',
        prioridad: 'Alta', estimacion: '8 puntos',
        criterios: [
            'Dado que un cajero escribe a mano la URL del módulo de usuarios, cuando la abre, entonces el sistema le muestra "Acceso denegado".',
            'Dado que un usuario modifica el precio en el navegador, cuando registra la venta, entonces el sistema cobra el precio guardado en la base de datos.',
            'Dado que una venta falla a la mitad, cuando reviso la base de datos, entonces no queda ningún registro parcial.',
        ],
    },
];

// ---------------------------------------------------------------- casos de uso
const CU = [
    {
        codigo: 'CU-01', nombre: 'Iniciar sesión',
        actor: 'Administrador, Cajero',
        descripcion: 'Permite a un usuario autenticarse para acceder al sistema.',
        precondiciones: ['El usuario tiene una cuenta activa en el sistema.'],
        postcondiciones: ['Se crea la sesión del usuario.', 'El usuario llega a la pantalla inicial de su rol.'],
        principal: [
            'El usuario abre el sistema en el navegador.',
            'El sistema muestra el formulario de inicio de sesión.',
            'El usuario ingresa su usuario y su contraseña.',
            'El usuario presiona el botón Ingresar.',
            'El sistema valida el token CSRF del formulario.',
            'El sistema busca el usuario activo y verifica la contraseña contra su hash.',
            'El sistema regenera el identificador de sesión y guarda los datos del usuario.',
            'El sistema redirige al dashboard si es administrador, o al punto de venta si es cajero.',
        ],
        alternos: [
            ['3a. Campos vacíos', 'El sistema solicita ingresar usuario y contraseña, y regresa al paso 3.'],
            ['6a. Credenciales incorrectas', 'El sistema muestra "Usuario o contraseña incorrectos" sin indicar cuál falló, y regresa al paso 3.'],
            ['6b. Usuario desactivado', 'El sistema trata la cuenta como inexistente y muestra el mismo mensaje genérico.'],
            ['5a. Token CSRF inválido o vencido', 'El sistema informa que la sesión expiró y recarga el formulario.'],
        ],
    },
    {
        codigo: 'CU-02', nombre: 'Registrar venta',
        actor: 'Cajero, Administrador',
        descripcion: 'Registra la venta de uno o más productos, descuenta el stock y emite el comprobante.',
        precondiciones: [
            'El usuario inició sesión.',
            'Existen productos activos con stock disponible.',
            'Existe una serie configurada para el tipo de comprobante.',
        ],
        postcondiciones: [
            'La venta queda registrada con su detalle.',
            'El stock de cada producto se reduce.',
            'Se registra un movimiento de salida en el kardex por cada producto.',
            'El correlativo de la serie avanza en uno.',
        ],
        principal: [
            'El cajero abre el Punto de Venta.',
            'El cajero escanea el código de barras del producto o escribe parte de su nombre.',
            'El sistema muestra el producto con su precio y su stock disponible.',
            'El cajero agrega el producto al carrito.',
            'El sistema calcula la operación gravada, el IGV y el total.',
            'El cajero repite los pasos 2 a 5 por cada producto.',
            'El cajero elige el tipo de comprobante y, si corresponde, selecciona al cliente.',
            'El cajero elige el método de pago e ingresa el monto recibido.',
            'El sistema muestra el vuelto.',
            'El cajero presiona Registrar venta o la tecla F9.',
            'El sistema valida el token CSRF y el rol del usuario.',
            'El sistema abre una transacción y bloquea las filas de los productos del carrito.',
            'El sistema relee de la base de datos el precio y el stock de cada producto.',
            'El sistema reserva el siguiente correlativo de la serie.',
            'El sistema graba la venta, su detalle, el descuento de stock y el kardex.',
            'El sistema confirma la transacción y devuelve el número de comprobante.',
            'El sistema muestra la confirmación con el total y el vuelto.',
        ],
        alternos: [
            ['3a. El producto no existe o está inactivo', 'El sistema avisa que no encontró el producto y regresa al paso 2.'],
            ['4a. El producto no tiene stock', 'El sistema avisa que no hay stock disponible y no lo agrega al carrito.'],
            ['4b. La cantidad supera el stock', 'El sistema ajusta la cantidad al stock disponible y avisa al cajero.'],
            ['7a. Se eligió Factura sin cliente con RUC', 'El sistema exige seleccionar un cliente con RUC y regresa al paso 7.'],
            ['8a. El monto recibido es menor al total', 'El sistema impide continuar y regresa al paso 8.'],
            ['13a. El stock cambió y ya no alcanza', 'El sistema deshace la transacción, no graba nada e informa qué producto quedó sin stock.'],
            ['15a. Ocurre un error de base de datos', 'El sistema ejecuta ROLLBACK, no deja registros parciales e informa el error.'],
        ],
    },
    {
        codigo: 'CU-03', nombre: 'Emitir comprobante',
        actor: 'Cajero, Administrador',
        descripcion: 'Genera el PDF de la boleta o factura en formato ticket de 80 mm.',
        precondiciones: ['La venta está registrada en el sistema.'],
        postcondiciones: ['Se genera el PDF del comprobante.'],
        principal: [
            'El usuario solicita imprimir el comprobante desde el punto de venta o desde el historial.',
            'El sistema recupera la venta, su detalle y los datos del negocio.',
            'El sistema arma el ticket con encabezado, detalle, IGV, total e importe en letras.',
            'El sistema entrega el PDF al navegador para su impresión.',
        ],
        alternos: [
            ['2a. La venta no existe', 'El sistema informa que el comprobante no existe y vuelve al historial.'],
            ['3a. La venta está anulada', 'El comprobante se genera con la marca "ANULADA" en rojo.'],
        ],
    },
    {
        codigo: 'CU-04', nombre: 'Consultar historial de ventas',
        actor: 'Administrador, Cajero',
        descripcion: 'Lista los comprobantes emitidos con filtros por fecha, tipo y estado.',
        precondiciones: ['El usuario inició sesión.'],
        postcondiciones: ['Se muestra el listado filtrado con sus totales.'],
        principal: [
            'El usuario abre el módulo de Ventas.',
            'El sistema muestra las ventas del mes en curso.',
            'El usuario ajusta los filtros de fecha, tipo de comprobante, estado o número.',
            'El sistema muestra los comprobantes que cumplen los filtros y el total emitido y anulado.',
        ],
        alternos: [
            ['2a. El usuario es cajero', 'El sistema muestra únicamente las ventas registradas por ese cajero.'],
            ['4a. No hay resultados', 'El sistema informa que no hay ventas en el período seleccionado.'],
        ],
    },
    {
        codigo: 'CU-05', nombre: 'Anular venta',
        actor: 'Administrador',
        descripcion: 'Anula un comprobante emitido y devuelve las unidades al inventario.',
        precondiciones: ['La venta existe y su estado es EMITIDA.', 'El usuario tiene rol Administrador.'],
        postcondiciones: [
            'La venta queda con estado ANULADA, con su motivo y su fecha.',
            'El stock de cada producto vendido se restituye.',
            'Se registra un movimiento de entrada en el kardex por cada producto.',
        ],
        principal: [
            'El administrador ubica la venta en el historial.',
            'El administrador presiona Anular.',
            'El sistema solicita el motivo de la anulación.',
            'El administrador ingresa el motivo y confirma.',
            'El sistema abre una transacción y bloquea la venta.',
            'El sistema devuelve al stock las unidades de cada línea y registra el kardex.',
            'El sistema marca la venta como ANULADA y confirma la transacción.',
            'El sistema informa que el stock fue restituido.',
        ],
        alternos: [
            ['2a. El usuario es cajero', 'El sistema no muestra el botón y rechaza la acción en el servidor.'],
            ['4a. El motivo tiene menos de 5 caracteres', 'El sistema exige un motivo válido y regresa al paso 3.'],
            ['5a. La venta ya estaba anulada', 'El sistema deshace la transacción e informa la situación.'],
        ],
    },
    {
        codigo: 'CU-06', nombre: 'Gestionar productos',
        actor: 'Administrador',
        descripcion: 'Registra, modifica y desactiva los productos del catálogo.',
        precondiciones: ['Existe al menos una categoría activa.'],
        postcondiciones: ['El catálogo queda actualizado.', 'El stock inicial queda reflejado en el kardex.'],
        principal: [
            'El administrador abre el módulo de Productos.',
            'El administrador presiona Nuevo producto.',
            'El administrador completa los datos del producto.',
            'El administrador guarda el formulario.',
            'El sistema valida los datos y la unicidad del código de barras.',
            'El sistema registra el producto y, si tiene stock inicial, lo registra en el kardex.',
            'El sistema confirma el registro y actualiza el listado.',
        ],
        alternos: [
            ['5a. El código de barras ya existe', 'El sistema rechaza el registro e informa el conflicto.'],
            ['5b. El precio de venta es menor al de compra', 'El sistema rechaza el registro.'],
            ['5c. La imagen supera 2 MB o no es una imagen válida', 'El sistema rechaza el archivo e informa el formato admitido.'],
            ['3a. Edición de un producto existente', 'El campo de stock queda bloqueado: solo cambia desde Inventario.'],
        ],
    },
    {
        codigo: 'CU-07', nombre: 'Gestionar categorías',
        actor: 'Administrador',
        descripcion: 'Administra las categorías con las que se clasifica el catálogo.',
        precondiciones: ['El usuario tiene rol Administrador.'],
        postcondiciones: ['La lista de categorías queda actualizada.'],
        principal: [
            'El administrador abre el módulo de Categorías.',
            'El administrador registra o edita una categoría.',
            'El sistema valida que el nombre no esté repetido.',
            'El sistema guarda la categoría y actualiza el listado.',
        ],
        alternos: [
            ['3a. El nombre ya existe', 'El sistema rechaza el registro.'],
            ['2a. Se intenta desactivar una categoría con productos activos', 'El sistema lo impide e informa el motivo.'],
        ],
    },
    {
        codigo: 'CU-08', nombre: 'Registrar movimiento de inventario',
        actor: 'Administrador',
        descripcion: 'Registra entradas, salidas y ajustes de stock fuera del proceso de venta.',
        precondiciones: ['El producto existe y está activo.'],
        postcondiciones: ['El stock del producto queda actualizado.', 'El movimiento queda registrado en el kardex.'],
        principal: [
            'El administrador abre el módulo de Inventario.',
            'El administrador selecciona el producto y ve su stock actual.',
            'El administrador elige el tipo de movimiento e ingresa la cantidad y el motivo.',
            'El administrador guarda el movimiento.',
            'El sistema abre una transacción y bloquea el producto.',
            'El sistema actualiza el stock y registra el movimiento con el stock anterior y el nuevo.',
            'El sistema confirma la transacción e informa el nuevo stock.',
        ],
        alternos: [
            ['3a. No se indica el motivo', 'El sistema lo exige y regresa al paso 3.'],
            ['6a. La salida supera el stock disponible', 'El sistema deshace la transacción e informa el stock real.'],
            ['6b. En un ajuste, la cantidad contada es igual al stock', 'El sistema informa que no hay diferencia que registrar.'],
        ],
    },
    {
        codigo: 'CU-09', nombre: 'Consultar kardex',
        actor: 'Administrador',
        descripcion: 'Muestra el historial completo de movimientos de un producto.',
        precondiciones: ['El producto existe.'],
        postcondiciones: ['Se muestra el kardex del producto.'],
        principal: [
            'El administrador abre el catálogo y presiona Kardex en un producto.',
            'El sistema muestra la ficha del producto con su stock actual, su mínimo y su precio.',
            'El sistema lista los movimientos del más reciente al más antiguo.',
        ],
        alternos: [
            ['1a. El producto no existe', 'El sistema informa la situación y vuelve al catálogo.'],
            ['3a. El movimiento proviene de una venta', 'El sistema muestra un enlace al comprobante correspondiente.'],
        ],
    },
    {
        codigo: 'CU-10', nombre: 'Gestionar clientes',
        actor: 'Administrador, Cajero',
        descripcion: 'Registra y actualiza los datos de los clientes del minimarket.',
        precondiciones: ['El usuario inició sesión.'],
        postcondiciones: ['El cliente queda registrado o actualizado.'],
        principal: [
            'El usuario abre el módulo de Clientes.',
            'El usuario presiona Nuevo cliente.',
            'El usuario elige el tipo de documento; el formulario se adapta.',
            'El usuario completa los datos y guarda.',
            'El sistema valida el formato y la unicidad del documento.',
            'El sistema registra el cliente y actualiza el listado.',
        ],
        alternos: [
            ['5a. El DNI no tiene 8 dígitos o el RUC no tiene 11', 'El sistema rechaza el registro.'],
            ['5b. El documento ya está registrado', 'El sistema informa que el cliente ya existe.'],
            ['5c. El correo tiene un formato inválido', 'El sistema rechaza el registro.'],
            ['2a. El usuario es cajero e intenta desactivar un cliente', 'El sistema no lo permite.'],
        ],
    },
    {
        codigo: 'CU-11', nombre: 'Consultar dashboard',
        actor: 'Administrador',
        descripcion: 'Muestra los indicadores de gestión del negocio.',
        precondiciones: ['El usuario tiene rol Administrador.'],
        postcondiciones: ['Se muestran los indicadores actualizados.'],
        principal: [
            'El administrador ingresa al sistema.',
            'El sistema calcula las ventas del día y del mes, el ticket promedio y los productos bajo el mínimo.',
            'El sistema dibuja los gráficos de los últimos 7 días, de productos más vendidos y de ventas por categoría.',
            'El sistema lista los productos por reponer y las últimas ventas registradas.',
        ],
        alternos: [
            ['2a. No hay ventas registradas', 'El sistema muestra los indicadores en cero y los paneles informan que no hay datos.'],
        ],
    },
    {
        codigo: 'CU-12', nombre: 'Generar reportes',
        actor: 'Administrador',
        descripcion: 'Genera y exporta el reporte de ventas de un período.',
        precondiciones: ['El usuario tiene rol Administrador.'],
        postcondiciones: ['Se muestra el reporte del período.', 'Opcionalmente se descarga en PDF o CSV.'],
        principal: [
            'El administrador abre el módulo de Reportes.',
            'El administrador elige el rango de fechas o un atajo predefinido.',
            'El sistema calcula el resumen, la evolución diaria y los desgloses por cajero, categoría y método de pago.',
            'El administrador presiona PDF o Excel para exportar.',
            'El sistema genera el archivo con los mismos importes mostrados en pantalla.',
        ],
        alternos: [
            ['2a. Las fechas están invertidas', 'El sistema las corrige automáticamente.'],
            ['2b. Se ingresa una fecha inválida', 'El sistema usa el mes en curso como período por defecto.'],
            ['3a. No hay ventas en el período', 'El sistema muestra los totales en cero.'],
        ],
    },
    {
        codigo: 'CU-13', nombre: 'Gestionar usuarios',
        actor: 'Administrador',
        descripcion: 'Administra las cuentas de acceso y sus roles.',
        precondiciones: ['El usuario tiene rol Administrador.'],
        postcondiciones: ['La cuenta queda creada, modificada, activada o desactivada.'],
        principal: [
            'El administrador abre el módulo de Usuarios.',
            'El administrador registra o edita una cuenta indicando nombre, usuario y rol.',
            'El sistema valida el formato del nombre de usuario y su unicidad.',
            'El sistema guarda la cuenta cifrando la contraseña si se ingresó una.',
        ],
        alternos: [
            ['3a. El nombre de usuario ya existe', 'El sistema rechaza el registro.'],
            ['3b. La contraseña tiene menos de 6 caracteres', 'El sistema rechaza el registro.'],
            ['4a. Se edita sin escribir contraseña', 'El sistema conserva la contraseña anterior.'],
            ['2a. Se intenta desactivar al único administrador activo', 'El sistema lo impide.'],
            ['2b. El administrador intenta desactivarse a sí mismo', 'El sistema lo impide.'],
        ],
    },
];

// ---------------------------------------------------------------- diccionario de datos
const TABLAS = [
    {
        nombre: 'usuarios',
        descripcion: 'Personal autorizado a operar el sistema.',
        campos: [
            ['id_usuario', 'INT', '—', 'No', 'PK', 'Identificador del usuario. Autoincremental.'],
            ['nombre', 'VARCHAR', '100', 'No', '—', 'Nombre completo de la persona.'],
            ['usuario', 'VARCHAR', '50', 'No', 'UK', 'Nombre de acceso. Único en el sistema.'],
            ['password', 'VARCHAR', '255', 'No', '—', 'Hash bcrypt de la contraseña.'],
            ['rol', 'ENUM', '—', 'No', '—', "Administrador o Cajero. Por defecto: Cajero."],
            ['estado', 'TINYINT', '1', 'No', '—', '1 activo, 0 inactivo. Baja lógica.'],
            ['created_at', 'TIMESTAMP', '—', 'No', '—', 'Fecha de registro de la cuenta.'],
            ['updated_at', 'TIMESTAMP', '—', 'Sí', '—', 'Fecha de la última modificación.'],
        ],
    },
    {
        nombre: 'categorias',
        descripcion: 'Clasificación de los productos del catálogo.',
        campos: [
            ['id_categoria', 'INT', '—', 'No', 'PK', 'Identificador de la categoría.'],
            ['nombre', 'VARCHAR', '80', 'No', 'UK', 'Nombre de la categoría. Único.'],
            ['descripcion', 'VARCHAR', '255', 'Sí', '—', 'Detalle de lo que agrupa la categoría.'],
            ['estado', 'TINYINT', '1', 'No', '—', '1 activa, 0 inactiva.'],
            ['created_at', 'TIMESTAMP', '—', 'No', '—', 'Fecha de registro.'],
        ],
    },
    {
        nombre: 'productos',
        descripcion: 'Catálogo de productos. El precio de venta incluye el IGV (RN-03).',
        campos: [
            ['id_producto', 'INT', '—', 'No', 'PK', 'Identificador del producto.'],
            ['codigo_barras', 'VARCHAR', '50', 'No', 'UK', 'Código de barras. Único.'],
            ['nombre', 'VARCHAR', '150', 'No', '—', 'Nombre comercial del producto. Indexado.'],
            ['descripcion', 'VARCHAR', '255', 'Sí', '—', 'Detalle adicional del producto.'],
            ['id_categoria', 'INT', '—', 'No', 'FK', 'Categoría a la que pertenece.'],
            ['precio_compra', 'DECIMAL', '10,2', 'No', '—', 'Costo de adquisición unitario.'],
            ['precio_venta', 'DECIMAL', '10,2', 'No', '—', 'Precio al público, con IGV incluido.'],
            ['stock', 'INT', '—', 'No', '—', 'Unidades disponibles. Solo cambia por ventas o movimientos.'],
            ['stock_minimo', 'INT', '—', 'No', '—', 'Umbral que dispara la alerta de reposición.'],
            ['unidad_medida', 'VARCHAR', '20', 'No', '—', 'Unidad, bolsa, botella, lata, caja, etc.'],
            ['imagen', 'VARCHAR', '255', 'Sí', '—', 'Nombre del archivo en assets/img/productos.'],
            ['estado', 'TINYINT', '1', 'No', '—', '1 activo, 0 inactivo.'],
            ['created_at', 'TIMESTAMP', '—', 'No', '—', 'Fecha de alta en el catálogo.'],
            ['updated_at', 'TIMESTAMP', '—', 'Sí', '—', 'Fecha de la última modificación.'],
        ],
    },
    {
        nombre: 'clientes',
        descripcion: 'Personas naturales (DNI) y empresas (RUC) que compran en el minimarket.',
        campos: [
            ['id_cliente', 'INT', '—', 'No', 'PK', 'Identificador del cliente.'],
            ['tipo_documento', 'ENUM', '—', 'No', '—', 'DNI o RUC.'],
            ['numero_documento', 'VARCHAR', '11', 'No', 'UK', '8 dígitos para DNI, 11 para RUC.'],
            ['nombres', 'VARCHAR', '100', 'Sí', '—', 'Nombres. Solo para personas naturales.'],
            ['apellidos', 'VARCHAR', '100', 'Sí', '—', 'Apellidos. Solo para personas naturales.'],
            ['razon_social', 'VARCHAR', '150', 'Sí', '—', 'Razón social. Solo para empresas.'],
            ['telefono', 'VARCHAR', '20', 'Sí', '—', 'Teléfono de contacto.'],
            ['email', 'VARCHAR', '100', 'Sí', '—', 'Correo electrónico.'],
            ['direccion', 'VARCHAR', '200', 'Sí', '—', 'Dirección, se imprime en la factura.'],
            ['estado', 'TINYINT', '1', 'No', '—', '1 activo, 0 inactivo.'],
            ['created_at', 'TIMESTAMP', '—', 'No', '—', 'Fecha de registro.'],
            ['updated_at', 'TIMESTAMP', '—', 'Sí', '—', 'Fecha de la última modificación.'],
        ],
    },
    {
        nombre: 'series_comprobante',
        descripcion: 'Controla el correlativo de cada serie de comprobantes.',
        campos: [
            ['id_serie', 'INT', '—', 'No', 'PK', 'Identificador de la serie.'],
            ['tipo_comprobante', 'ENUM', '—', 'No', 'UK', 'BOLETA o FACTURA.'],
            ['serie', 'VARCHAR', '4', 'No', 'UK', 'Serie del comprobante: B001, F001.'],
            ['ultimo_correlativo', 'INT', '—', 'No', '—', 'Último número emitido de esa serie.'],
            ['estado', 'TINYINT', '1', 'No', '—', '1 activa, 0 inactiva.'],
        ],
    },
    {
        nombre: 'ventas',
        descripcion: 'Cabecera de cada comprobante emitido.',
        campos: [
            ['id_venta', 'INT', '—', 'No', 'PK', 'Identificador de la venta.'],
            ['id_usuario', 'INT', '—', 'No', 'FK', 'Cajero que registró la venta.'],
            ['id_cliente', 'INT', '—', 'Sí', 'FK', 'Cliente. Nulo si la boleta es a cliente varios.'],
            ['tipo_comprobante', 'ENUM', '—', 'No', 'UK', 'BOLETA o FACTURA.'],
            ['serie', 'VARCHAR', '4', 'No', 'UK', 'Serie del comprobante emitido.'],
            ['correlativo', 'INT', '—', 'No', 'UK', 'Número dentro de la serie.'],
            ['fecha', 'DATETIME', '—', 'No', '—', 'Fecha y hora de la emisión. Indexada.'],
            ['subtotal', 'DECIMAL', '10,2', 'No', '—', 'Operación gravada, sin IGV.'],
            ['igv', 'DECIMAL', '10,2', 'No', '—', 'Impuesto General a las Ventas.'],
            ['total', 'DECIMAL', '10,2', 'No', '—', 'Importe total cobrado.'],
            ['monto_pagado', 'DECIMAL', '10,2', 'No', '—', 'Dinero recibido del cliente.'],
            ['vuelto', 'DECIMAL', '10,2', 'No', '—', 'Diferencia devuelta al cliente.'],
            ['metodo_pago', 'ENUM', '—', 'No', '—', 'EFECTIVO, TARJETA, YAPE o PLIN.'],
            ['estado', 'ENUM', '—', 'No', '—', 'EMITIDA o ANULADA. Indexado.'],
            ['motivo_anulacion', 'VARCHAR', '255', 'Sí', '—', 'Razón de la anulación.'],
            ['fecha_anulacion', 'DATETIME', '—', 'Sí', '—', 'Momento en que se anuló.'],
        ],
    },
    {
        nombre: 'detalle_venta',
        descripcion: 'Líneas de productos de cada comprobante.',
        campos: [
            ['id_detalle', 'INT', '—', 'No', 'PK', 'Identificador de la línea.'],
            ['id_venta', 'INT', '—', 'No', 'FK', 'Venta a la que pertenece. Borrado en cascada.'],
            ['id_producto', 'INT', '—', 'No', 'FK', 'Producto vendido.'],
            ['cantidad', 'INT', '—', 'No', '—', 'Unidades vendidas.'],
            ['precio_unitario', 'DECIMAL', '10,2', 'No', '—', 'Precio al momento de la venta, con IGV.'],
            ['subtotal', 'DECIMAL', '10,2', 'No', '—', 'cantidad × precio_unitario.'],
        ],
    },
    {
        nombre: 'movimientos_inventario',
        descripcion: 'Kardex: explica cada variación del stock de un producto.',
        campos: [
            ['id_movimiento', 'INT', '—', 'No', 'PK', 'Identificador del movimiento.'],
            ['id_producto', 'INT', '—', 'No', 'FK', 'Producto afectado. Indexado con la fecha.'],
            ['tipo', 'ENUM', '—', 'No', '—', 'ENTRADA, SALIDA o AJUSTE.'],
            ['cantidad', 'INT', '—', 'No', '—', 'Unidades del movimiento, siempre positivas.'],
            ['stock_anterior', 'INT', '—', 'No', '—', 'Stock antes del movimiento.'],
            ['stock_nuevo', 'INT', '—', 'No', '—', 'Stock después del movimiento.'],
            ['motivo', 'VARCHAR', '255', 'Sí', '—', 'Explicación del movimiento.'],
            ['id_venta', 'INT', '—', 'Sí', 'FK', 'Venta que lo originó, si corresponde.'],
            ['id_usuario', 'INT', '—', 'No', 'FK', 'Usuario responsable del movimiento.'],
            ['fecha', 'DATETIME', '—', 'No', '—', 'Momento del movimiento. Indexada.'],
        ],
    },
    {
        nombre: 'configuracion',
        descripcion: 'Parámetros globales del negocio.',
        campos: [
            ['clave', 'VARCHAR', '50', 'No', 'PK', 'Nombre del parámetro.'],
            ['valor', 'VARCHAR', '255', 'No', '—', 'Valor del parámetro.'],
            ['descripcion', 'VARCHAR', '255', 'Sí', '—', 'Para qué sirve el parámetro.'],
        ],
    },
];

module.exports = { ACTORES, RF, RNF, RN, HU, CU, TABLAS };
