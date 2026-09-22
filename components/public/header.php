<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../models/Configuracion.php';

$config = new Configuracion();
$nombreComercial = $config->obtener('nombre_comercial', 'EL AHORRO');
$telefonoComercial = $config->obtener('telefono', '987654321');
$direccionComercial = $config->obtener('direccion', 'Av. Principal 123, Lima');
$monedaComercial = $config->obtener('moneda', 'S/');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($nombreComercial) ?> - Tu minimarket de confianza</title>
    <link rel="icon" href="<?= BASE_URL ?>assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset_url('assets/css/tailwind.css') ?>">
    <script src="https://mcp.figma.com/mcp/html-to-design/capture.js" async data-figma-capture></script>
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased flex flex-col min-h-screen">
