<?php

use PHPUnit\Framework\TestCase;

require_once ROOT_PATH . '/controllers/BackupController.php';
require_once ROOT_PATH . '/models/Backup.php';
require_once ROOT_PATH . '/models/Configuracion.php';

class BackupControllerTest extends TestCase
{
    public function test_configuracion_actual_usa_valores_por_defecto(): void
    {
        $configuracion = $this->createMock(Configuracion::class);
        $configuracion->method('obtener')->willReturnMap([
            ['backup_frecuencia', 'Semanal', 'Semanal'],
            ['backup_conservar', 10, '10'],
        ]);

        $controller = new BackupController($this->createMock(Backup::class), $configuracion);

        $this->assertSame(['frecuencia' => 'Semanal', 'conservar' => 10], $controller->configuracionActual());
    }

    public function test_guardar_configuracion_rechaza_frecuencia_invalida(): void
    {
        $controller = new BackupController($this->createMock(Backup::class), $this->createMock(Configuracion::class));

        $resultado = $controller->guardarConfiguracion(['frecuencia' => 'Anual', 'conservar' => 10]);

        $this->assertFalse($resultado['ok']);
    }

    public function test_guardar_configuracion_rechaza_conservar_fuera_de_rango(): void
    {
        $controller = new BackupController($this->createMock(Backup::class), $this->createMock(Configuracion::class));

        $bajo = $controller->guardarConfiguracion(['frecuencia' => 'Diario', 'conservar' => 0]);
        $alto = $controller->guardarConfiguracion(['frecuencia' => 'Diario', 'conservar' => 400]);

        $this->assertFalse($bajo['ok']);
        $this->assertFalse($alto['ok']);
    }

    public function test_guardar_configuracion_exitosa_persiste_ambas_claves(): void
    {
        $configuracion = $this->createMock(Configuracion::class);
        $configuracion->expects($this->exactly(2))->method('guardar')
            ->willReturnCallback(function ($clave, $valor) {
                static $esperado = ['backup_frecuencia' => 'Mensual', 'backup_conservar' => '5'];
                TestCase::assertSame($esperado[$clave], $valor);
            });

        $controller = new BackupController($this->createMock(Backup::class), $configuracion);

        $resultado = $controller->guardarConfiguracion(['frecuencia' => 'Mensual', 'conservar' => 5]);

        $this->assertTrue($resultado['ok']);
    }

    public function test_generar_manual_delega_al_modelo_con_la_retencion_configurada(): void
    {
        $configuracion = $this->createMock(Configuracion::class);
        $configuracion->method('obtener')->willReturn('7');

        $modelo = $this->createMock(Backup::class);
        $modelo->expects($this->once())->method('generar')->with(7)
            ->willReturn(['nombre' => 'minimarket_test.sql', 'ruta' => '/tmp/minimarket_test.sql']);

        $controller = new BackupController($modelo, $configuracion);

        $resultado = $controller->generarManual();

        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('minimarket_test.sql', $resultado['mensaje']);
    }

    public function test_ejecutar_si_corresponde_no_genera_si_el_modelo_dice_que_no_toca(): void
    {
        $configuracion = $this->createMock(Configuracion::class);
        $configuracion->method('obtener')->willReturn('Desactivado');

        $modelo = $this->createMock(Backup::class);
        $modelo->method('corresponde')->willReturn(false);
        $modelo->expects($this->never())->method('generar');

        (new BackupController($modelo, $configuracion))->ejecutarSiCorresponde();
    }

    public function test_ejecutar_si_corresponde_genera_cuando_el_modelo_dice_que_si(): void
    {
        $configuracion = $this->createMock(Configuracion::class);
        $configuracion->method('obtener')->willReturn('Semanal');

        $modelo = $this->createMock(Backup::class);
        $modelo->method('corresponde')->willReturn(true);
        $modelo->expects($this->once())->method('generar');

        (new BackupController($modelo, $configuracion))->ejecutarSiCorresponde();
    }

    public function test_ejecutar_si_corresponde_ignora_excepciones_del_modelo(): void
    {
        $configuracion = $this->createMock(Configuracion::class);
        $configuracion->method('obtener')->willReturn('Diario');

        $modelo = $this->createMock(Backup::class);
        $modelo->method('corresponde')->willReturn(true);
        $modelo->method('generar')->willThrowException(new Exception('disco lleno'));

        // No debe propagar la excepcion.
        (new BackupController($modelo, $configuracion))->ejecutarSiCorresponde();
        $this->assertTrue(true);
    }

    public function test_descargar_devuelve_false_si_el_archivo_no_existe(): void
    {
        $modelo = $this->createMock(Backup::class);
        $modelo->method('ruta')->willReturn('/ruta/que/no/existe/backup.sql');

        $controller = new BackupController($modelo, $this->createMock(Configuracion::class));

        $this->assertFalse($controller->descargar('backup.sql'));
    }
}
