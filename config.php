<?php

/**
 * Configuración global del sistema.
 * Define las rutas base y carga las variables de entorno desde el archivo .env
 */

$__rootPath = str_replace(DIRECTORY_SEPARATOR, '/', __DIR__);
define('ROOT_PATH', $__rootPath);

// Solo se recorta el DOCUMENT_ROOT si realmente es un prefijo de ROOT_PATH.
// Si el servidor lo reporta vacío o distinto (proxies, servidores de
// desarrollo no estándar), str_replace('', '', ...) dejaría pasar la ruta
// completa del disco tal cual dentro de BASE_URL -y de cualquier enlace
// que se arme con ella, como el del correo de recuperación-. Ante la duda
// se usa '/' como respaldo seguro en vez de filtrar la ruta del servidor.
$__documentRoot = str_replace(DIRECTORY_SEPARATOR, '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
$__relativePath = '';

if ($__documentRoot !== '' && strpos($__rootPath, $__documentRoot) === 0) {
    $__relativePath = trim(substr($__rootPath, strlen($__documentRoot)), '/');
}

if ($__relativePath !== '') {
    define('BASE_URL', '/' . $__relativePath . '/');
} else {
    define('BASE_URL', '/');
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', ROOT_PATH);
}

// Carga las variables de entorno desde un archivo .env plano
function loadEnv($path)
{
    if (!file_exists($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;

        list($name, $value) = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name]    = $value;
            $_SERVER[$name] = $value;
        }
    }
}

loadEnv(ROOT_PATH . '/.env');

// Zona horaria de Perú
date_default_timezone_set('America/Lima');

/**
 * Escapa texto para imprimirlo en HTML de forma segura.
 */
function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/**
 * URL de un archivo estático con la fecha de modificación como parámetro,
 * para que el navegador pida la versión nueva apenas cambia el archivo
 * (evita tener que forzar recarga cuando se actualiza el CSS/JS).
 */
function asset_url($rutaRelativa)
{
    $rutaAbsoluta = ROOT_PATH . '/' . ltrim($rutaRelativa, '/');
    $version      = file_exists($rutaAbsoluta) ? filemtime($rutaAbsoluta) : time();

    return BASE_URL . $rutaRelativa . '?v=' . $version;
}

/**
 * Formatea un monto como moneda peruana.
 */
function money($monto)
{
    return 'S/ ' . number_format((float) $monto, 2, '.', ',');
}

/**
 * Formatea un tamaño en bytes como texto legible (KB, MB...).
 */
function tamano_legible($bytes)
{
    $bytes = (float) $bytes;
    $unidades = ['B', 'KB', 'MB', 'GB'];

    $indice = 0;
    while ($bytes >= 1024 && $indice < count($unidades) - 1) {
        $bytes /= 1024;
        $indice++;
    }

    return number_format($bytes, $indice === 0 ? 0 : 1) . ' ' . $unidades[$indice];
}

/**
 * Genera y valida el token CSRF de la sesión.
 */
function csrf_token()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_validar($token)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Devuelve la fecha indicada escrita en espanol.
 * Ejemplo: "martes 26 de agosto de 2026"
 */
function strftime_es($timestamp = null)
{
    $timestamp = $timestamp ?? time();

    $dias = [
        'Sunday' => 'domingo', 'Monday' => 'lunes', 'Tuesday' => 'martes',
        'Wednesday' => 'miércoles', 'Thursday' => 'jueves',
        'Friday' => 'viernes', 'Saturday' => 'sábado',
    ];

    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'setiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    $dia = $dias[date('l', $timestamp)];
    $mes = $meses[(int) date('n', $timestamp)];

    return $dia . ' ' . date('j', $timestamp) . ' de ' . $mes . ' de ' . date('Y', $timestamp);
}

/**
 * Formatea una fecha de la base de datos para mostrarla al usuario.
 */
function fecha_hora($valor)
{
    if (empty($valor)) return '';

    return date('d/m/Y H:i', strtotime($valor));
}

function fecha_corta($valor)
{
    if (empty($valor)) return '';

    return date('d/m/Y', strtotime($valor));
}

/**
 * Guarda un mensaje que se mostrara una sola vez en la siguiente pantalla.
 */
function flash($tipo, $mensaje)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function flash_obtener()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['flash'])) return null;

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

/**
 * Redirige a una ruta interna del sistema y detiene la ejecucion.
 */
function redirigir($ruta)
{
    header('Location: ' . BASE_URL . ltrim($ruta, '/'));
    exit;
}

/**
 * Responde en formato JSON. Se usa en los endpoints de la carpeta api/.
 */
function json_respuesta($datos, $codigo = 200)
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}
