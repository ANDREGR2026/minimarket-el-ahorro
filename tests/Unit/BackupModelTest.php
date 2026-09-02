<?php

require_once __DIR__ . '/DatabaseTestCase.php';
require_once ROOT_PATH . '/models/Backup.php';

/**
 * Pruebas del modelo de backups. Usa un directorio temporal propio para no
 * tocar la carpeta backups/ real del proyecto.
 */
class BackupModelTest extends DatabaseTestCase
{
    private string $directorioTemporal;
    private Backup $backup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directorioTemporal = sys_get_temp_dir() . '/minimarket_backup_test_' . uniqid();
        $this->backup = new Backup($this->directorioTemporal);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->directorioTemporal)) {
            foreach (glob($this->directorioTemporal . '/*') as $archivo) {
                unlink($archivo);
            }
            rmdir($this->directorioTemporal);
        }

        parent::tearDown();
    }

    public function test_listar_vacio_sin_backups(): void
    {
        $this->assertSame([], $this->backup->listar());
        $this->assertNull($this->backup->ultimoBackup());
    }

    public function test_generar_crea_archivo_sql_con_estructura_y_datos(): void
    {
        $this->conexion->exec("
            INSERT INTO usuarios (nombre, usuario, password, rol)
            VALUES ('Prueba Backup', 'prueba.backup', 'hash', 'Cajero')
        ");

        $resultado = $this->backup->generar();

        $this->assertFileExists($resultado['ruta']);

        $contenido = file_get_contents($resultado['ruta']);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `usuarios`', $contenido);
        $this->assertStringContainsString('CREATE TABLE', $contenido);
        $this->assertStringContainsString('INSERT INTO `usuarios`', $contenido);
        $this->assertStringContainsString('prueba.backup', $contenido);
    }

    public function test_generar_deja_el_backup_disponible_en_listar(): void
    {
        $resultado = $this->backup->generar();

        $lista = $this->backup->listar();
        $this->assertCount(1, $lista);
        $this->assertSame($resultado['nombre'], $lista[0]['nombre']);
        $this->assertSame($resultado['nombre'], $this->backup->ultimoBackup()['nombre']);
    }

    public function test_aplicar_retencion_conserva_solo_los_mas_recientes(): void
    {
        // Tres archivos .sql con mtime distinto, simulando tres backups.
        for ($i = 0; $i < 3; $i++) {
            $ruta = $this->directorioTemporal . "/minimarket_falso_{$i}.sql";
            mkdir($this->directorioTemporal, 0775, true);
            file_put_contents($ruta, '-- backup de prueba');
            touch($ruta, time() - (3 - $i) * 100);
        }

        $this->backup->aplicarRetencion(2);

        $this->assertCount(2, $this->backup->listar());
    }

    public function test_aplicar_retencion_cero_no_borra_nada(): void
    {
        mkdir($this->directorioTemporal, 0775, true);
        file_put_contents($this->directorioTemporal . '/minimarket_falso.sql', '-- backup de prueba');

        $this->backup->aplicarRetencion(0);

        $this->assertCount(1, $this->backup->listar());
    }

    public function test_corresponde_desactivado_siempre_falso(): void
    {
        $this->assertFalse($this->backup->corresponde('Desactivado'));
        $this->assertFalse($this->backup->corresponde('ValorNoValido'));
    }

    public function test_corresponde_sin_backups_previos_es_verdadero(): void
    {
        $this->assertTrue($this->backup->corresponde('Diario'));
        $this->assertTrue($this->backup->corresponde('Mensual'));
    }

    public function test_corresponde_diario_falso_si_el_ultimo_es_de_hoy(): void
    {
        $this->backup->generar();

        $this->assertFalse($this->backup->corresponde('Diario'));
    }

    public function test_corresponde_diario_verdadero_si_el_ultimo_paso_hace_mas_de_un_dia(): void
    {
        mkdir($this->directorioTemporal, 0775, true);
        $ruta = $this->directorioTemporal . '/minimarket_viejo.sql';
        file_put_contents($ruta, '-- backup de prueba');
        touch($ruta, time() - (60 * 60 * 24 + 60));

        $this->assertTrue($this->backup->corresponde('Diario'));
    }

    public function test_ruta_usa_basename_evitando_escapar_el_directorio(): void
    {
        $ruta = $this->backup->ruta('../../etc/passwd');

        $this->assertSame($this->directorioTemporal . '/passwd', $ruta);
    }
}
