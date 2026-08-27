<?php

/**
 * Muestra el mensaje guardado por flash() en la petición anterior.
 */

$__flash = flash_obtener();

if ($__flash):
    $clases = [
        'exito' => 'alerta-exito',
        'error' => 'alerta-error',
        'aviso' => 'alerta-aviso',
    ];
    $clase = $clases[$__flash['tipo']] ?? 'alerta-aviso';
?>
    <div class="<?= $clase ?> mb-5" role="status">
        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
            <?php if ($__flash['tipo'] === 'exito'): ?>
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd" />
            <?php else: ?>
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 9a1 1 0 012 0v4a1 1 0 11-2 0V9zm1-4a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
            <?php endif; ?>
        </svg>
        <span><?= e($__flash['mensaje']) ?></span>
    </div>
<?php endif; ?>
