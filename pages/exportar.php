<?php

/**
 * Descarga el reporte de ventas en PDF o CSV.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/ExportarController.php';

Auth::checkRole(['Administrador']);

[$desde, $hasta] = ReporteController::rango($_GET['desde'] ?? null, $_GET['hasta'] ?? null);

$controlador = new ExportarController();

if (($_GET['formato'] ?? 'pdf') === 'csv') {
    $controlador->csv($desde, $hasta);
}

$controlador->pdf($desde, $hasta);
