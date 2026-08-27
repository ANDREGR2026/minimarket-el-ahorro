<?php

/**
 * Bootstrap para las pruebas unitarias del sistema MINIMARKET.
 *
 * Configura el entorno de testing: sesión en modo CLI,
 * carga el autoloader de Composer y las funciones utilitarias.
 */

// ── Sesión sin cookies para que funcione en CLI (PHPUnit) ──
ini_set('session.use_cookies', '0');
ini_set('session.use_only_cookies', '0');
ini_set('session.cache_limiter', '');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Autoloader de Composer (PHPUnit y sus dependencias) ──
require_once __DIR__ . '/../vendor/autoload.php';

// ── Funciones utilitarias y constantes del proyecto ──
$_ENV['DB_NAME'] = 'minimarket_test';
$_ENV['DB_HOST'] = 'localhost';
$_ENV['DB_USER'] = 'root';
$_ENV['DB_PASS'] = '';

require_once __DIR__ . '/../config.php';
