<?php

/**
 * Definicion del menu lateral.
 * Cada entrada indica que roles pueden verla.
 */

function menu_items()
{
    return [
        [
            'clave'  => 'dashboard',
            'texto'  => 'Dashboard',
            'ruta'   => 'dashboard',
            'roles'  => ['Administrador'],
            'icono'  => '<path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>',
        ],
        [
            'clave'  => 'pos',
            'texto'  => 'Punto de Venta',
            'ruta'   => 'pos',
            'roles'  => ['Administrador', 'Cajero'],
            'icono'  => '<path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.2 14.6l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.86-7.01L19.42 4l-3.87 7H8.53L4.27 2H1v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.48 17 7 17h12v-2H7.42c-.13 0-.25-.11-.22-.4z"/>',
        ],
        [
            'clave'  => 'ventas',
            'texto'  => 'Ventas',
            'ruta'   => 'ventas',
            'roles'  => ['Administrador', 'Cajero'],
            'icono'  => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>',
        ],
        [
            'clave'  => 'productos',
            'texto'  => 'Productos',
            'ruta'   => 'productos',
            'roles'  => ['Administrador', 'Cajero'],
            'icono'  => '<path d="M12 2 3 7v10l9 5 9-5V7l-9-5zm0 2.3 6.5 3.6L12 11.5 5.5 7.9 12 4.3zM5 9.6l6 3.3v6.5l-6-3.3V9.6zm8 9.8v-6.5l6-3.3v6.5l-6 3.3z"/>',
        ],
        [
            'clave'  => 'categorias',
            'texto'  => 'Categorías',
            'ruta'   => 'categorias',
            'roles'  => ['Administrador'],
            'icono'  => '<path d="M12 2 2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>',
        ],
        [
            'clave'  => 'inventario',
            'texto'  => 'Inventario',
            'ruta'   => 'inventario',
            'roles'  => ['Administrador'],
            'icono'  => '<path d="M20 2H4c-1.1 0-2 .9-2 2v4h2V4h16v4h2V4c0-1.1-.9-2-2-2zM4 20v-4H2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4h-2v4H4zM2 10h20v4H2v-4z"/>',
        ],
        [
            'clave'  => 'clientes',
            'texto'  => 'Clientes',
            'ruta'   => 'clientes',
            'roles'  => ['Administrador', 'Cajero'],
            'icono'  => '<path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>',
        ],
        [
            'clave'  => 'reportes',
            'texto'  => 'Reportes',
            'ruta'   => 'reportes',
            'roles'  => ['Administrador'],
            'icono'  => '<path d="M5 9.2h3V19H5V9.2zM10.6 5h2.8v14h-2.8V5zm5.6 8H19v6h-2.8v-6z"/>',
        ],
        [
            'clave'  => 'usuarios',
            'texto'  => 'Usuarios',
            'ruta'   => 'usuarios',
            'roles'  => ['Administrador'],
            'icono'  => '<path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>',
        ],
    ];
}
