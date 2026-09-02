# Sistema Web para la Gestión de un Minimarket

Proyecto final del curso **Desarrollo de Sistemas de Información**.

Sistema web para administrar la operación diaria de un minimarket: punto de venta con
emisión de boleta y factura, control de catálogo e inventario con kardex, registro de
clientes, reportes de gestión y backups configurables de la base de datos.

---

## Tecnologías

| Capa | Tecnología |
|---|---|
| Servidor web | Apache 2.4 (Laragon) |
| Lenguaje | PHP 8.3 sin framework, con arquitectura MVC propia |
| Base de datos | MySQL 8.4 · InnoDB · utf8mb4 |
| Acceso a datos | PDO con sentencias preparadas |
| Interfaz | TailwindCSS 4 compilado localmente |
| Interactividad | JavaScript nativo, sin librerías de terceros. Incluye un componente propio de lista desplegable |
| Gráficos | Chart.js (servido desde el propio proyecto) |
| PDF | FPDF |

Todo funciona **sin conexión a internet**: las hojas de estilo y las librerías están
compiladas y guardadas dentro del proyecto.

---

## Instalación

### 1. Requisitos

- [Laragon](https://laragon.org/) con PHP 8.3 y MySQL 8.4 (también funciona con XAMPP).
- Un navegador actualizado: Chrome, Edge o Firefox.

> **Aviso:** si tiene XAMPP y Laragon instalados a la vez, levante solo uno. Ambos usan los
> puertos 80 y 3306, y el segundo en arrancar fallará.

### 2. Copiar el proyecto

Coloque la carpeta `MINIMARKET` dentro del directorio `www` de Laragon:

```
C:\laragon\www\MINIMARKET
```

### 3. Crear la base de datos

Abra **phpMyAdmin** desde Laragon (o use la consola de MySQL) e importe, en este orden:

1. `database/schema.sql` — crea la base de datos `minimarket` y sus nueve tablas.
2. `database/seed.sql` — carga la configuración del negocio, los usuarios, las categorías,
   41 productos y 10 clientes de prueba.

Desde la consola de Laragon también puede ejecutar:

```bash
mysql -u root < database/schema.sql
```

```bash
mysql -u root < database/seed.sql
```

### 4. Configurar la conexión

Copie `.env.example` como `.env` y ajústelo si su MySQL usa otras credenciales. Los valores
por defecto corresponden a una instalación limpia de Laragon:

```
DB_HOST=localhost
DB_NAME=minimarket
DB_USER=root
DB_PASS=
```

### 4b. Configurar el correo (opcional, para "olvidé mi contraseña")

El mismo `.env` acepta variables `MAIL_*` para el envío del correo de recuperación de
contraseña (ver `.env.example`). Sin PHPMailer instalado, el sistema usa la función `mail()`
nativa de PHP como respaldo. Para enviar por SMTP (Gmail, Mailtrap, etc.) instale PHPMailer:

```bash
composer require phpmailer/phpmailer
```

### 5. Abrir el sistema

Inicie Apache y MySQL desde Laragon y visite:

```
http://localhost/MINIMARKET/
```

---

## Credenciales de prueba

| Rol | Usuario | Contraseña |
|---|---|---|
| Administrador | `admin` | `admin123` |
| Cajero | `cajero` | `cajero123` |
| Almacenero | `almacen` | `almacen123` |

El **administrador** accede a todo el sistema. El **cajero** solo al punto de venta, a la
consulta del catálogo, al registro de clientes y a sus propias ventas. El **almacenero** solo
al inventario (kardex, entradas/salidas/ajustes de stock) y a la consulta del catálogo de
productos.

---

## Datos de demostración

Para que el dashboard y los reportes tengan contenido durante la sustentación, puede
generar ventas de los últimos 30 días:

```bash
php database/demo_ventas.php
```

El script registra las ventas usando el mismo modelo que el punto de venta, de modo que el
stock y el kardex quedan consistentes. Para volver al estado inicial, reimporte
`schema.sql` y `seed.sql`.

---

## Estructura del proyecto

```
MINIMARKET/
├── config.php              Rutas base, carga del .env y funciones auxiliares
├── index.php               Punto de entrada: redirige al login o a la pantalla del rol
├── .htaccess               URLs amigables y bloqueo de carpetas internas
├── api/                    Endpoints JSON que consume el punto de venta
├── assets/
│   ├── css/                input.css (fuente) y tailwind.css (compilado)
│   ├── js/                 pos.js, select.js y chart.min.js
│   └── img/productos/      Imágenes del catálogo
├── components/             Layout, menú lateral y mensajes reutilizables
├── controllers/            Reglas de negocio y validaciones
├── database/
│   ├── Conexion.php        Conexión PDO única
│   ├── schema.sql          Creación de la base de datos
│   ├── seed.sql            Datos iniciales
│   └── demo_ventas.php     Generador de ventas de demostración
├── docs/                   Documentación del proyecto (ver más abajo)
├── lib/fpdf/               Librería de generación de PDF
├── middleware/Auth.php     Control de sesión y de rol
├── models/                 Acceso a datos y transacciones
├── pages/                  Pantallas del sistema
└── storage/comprobantes/   Comprobantes emitidos en PDF
```

---

## Documentación

Los entregables del curso están en la carpeta `docs/`:

| Documento | Contenido |
|---|---|
| `01_Documento_de_Requerimientos.docx` | Alcance, actores, 37 requerimientos funcionales, 20 no funcionales, 14 reglas de negocio y matriz de trazabilidad |
| `02_Historias_de_Usuario.docx` | 18 historias de usuario con sus criterios de aceptación |
| `03_Casos_de_Uso.docx` | 13 casos de uso con flujo principal y flujos alternos |
| `04_Diagramas_UML.docx` | Casos de uso, clases, entidad-relación, secuencia, actividades y despliegue |
| `05_Diccionario_de_Datos.docx` | Detalle de las nueve tablas, sus relaciones e índices |
| `06_Manual_de_Usuario.docx` | Guía paso a paso por módulo, con capturas del sistema |

También se incluyen las fuentes:

- `docs/diagramas/*.mmd` — código Mermaid de cada diagrama, con su PNG renderizado.
- `docs/capturas/*.png` — capturas de pantalla del sistema.
- `docs/generador/` — scripts que generan los seis documentos Word.

### Regenerar la documentación

Los seis documentos Word se generan desde código, así que cualquier corrección se aplica
editando `docs/generador/datos.js` y volviendo a ejecutar:

```bash
npm run docs
```

Para volver a renderizar los diagramas a partir de sus fuentes Mermaid:

```bash
npm run docs:diagramas
```

Para volver a tomar las capturas de pantalla del manual (requiere el sistema corriendo en
Laragon y Google Chrome instalado):

```bash
npm run docs:capturas
```

> Al abrir cada documento en Word, actualice el índice: clic derecho sobre él →
> *Actualizar campos* → *Actualizar toda la tabla*.

---

## Desarrollo

### Recompilar los estilos

Después de agregar clases de Tailwind en las vistas, recompile la hoja de estilos:

```bash
npm run css
```

Durante el desarrollo, para recompilar automáticamente al guardar:

```bash
npm run css:watch
```

---

## Decisiones de diseño

**El precio de venta incluye el IGV.** Como en el comercio minorista peruano el precio de
góndola ya lo incluye, la venta descompone el impuesto hacia atrás:
`subtotal = total ÷ 1,18` e `IGV = total − subtotal`. Así el total cobrado nunca sufre
descuadres por redondeo.

**El precio nunca se toma del navegador.** Al registrar la venta, el servidor vuelve a leer
el precio y el stock de cada producto desde la base de datos con `SELECT … FOR UPDATE`.

**El registro y la anulación son atómicos.** Comprobante, detalle, descuento de stock y
kardex se graban dentro de una única transacción: si algo falla, no queda ningún registro
parcial.

**El correlativo se reserva bloqueando la fila de la serie**, de modo que dos cajas
simultáneas nunca obtengan el mismo número de comprobante.

**Nada se elimina físicamente.** Productos, categorías, clientes y usuarios se desactivan,
conservando su historial.

**Todo cambio de stock deja rastro.** Ventas, ingresos, mermas, ajustes y anulaciones
generan una fila en el kardex con el stock anterior y el nuevo.

---

## Limitaciones conocidas

- No emite comprobantes electrónicos ante la SUNAT.
- No incluye módulo de compras ni de órdenes a proveedores.
- No maneja ventas al crédito ni cuentas por cobrar.
- Está pensado para un solo local y una sola caja.
- El escaneo de código de barras/QR por cámara usa la `BarcodeDetector` nativa del navegador
  (Chrome/Edge); en navegadores sin soporte (Safari, Firefox) use un lector físico USB, que
  sigue funcionando igual que antes.
- El correo de recuperación de contraseña requiere conexión a internet solo si se configura
  un servidor SMTP externo (Gmail, etc.); el resto del sistema sigue funcionando sin ella.
