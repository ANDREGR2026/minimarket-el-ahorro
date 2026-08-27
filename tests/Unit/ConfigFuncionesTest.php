<?php

use PHPUnit\Framework\TestCase;

/**
 * Pruebas de las funciones utilitarias definidas en config.php:
 * e(), money(), strftime_es(), fecha_hora(), fecha_corta(),
 * csrf_token(), csrf_validar(), csrf_field(), flash(), flash_obtener().
 */
class ConfigFuncionesTest extends TestCase
{
    // ─── e() ─────────────────────────────────────────────────────────

    public function test_e_escapa_html(): void
    {
        $this->assertSame(
            '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;',
            e('<script>alert("xss")</script>')
        );
    }

    public function test_e_escapa_comillas_simples(): void
    {
        $this->assertSame('O&#039;Reilly', e("O'Reilly"));
    }

    public function test_e_devuelve_string_normal_sin_cambios(): void
    {
        $this->assertSame('Hola mundo', e('Hola mundo'));
    }

    public function test_e_convierte_null_a_string_vacio(): void
    {
        $this->assertSame('', e(null));
    }

    public function test_e_convierte_numero_a_string(): void
    {
        $this->assertSame('42', e(42));
    }

    // ─── money() ─────────────────────────────────────────────────────

    public function test_money_formatea_monto_con_miles(): void
    {
        $this->assertSame('S/ 1,234.56', money(1234.56));
    }

    public function test_money_formatea_cero(): void
    {
        $this->assertSame('S/ 0.00', money(0));
    }

    public function test_money_convierte_string_a_float(): void
    {
        $this->assertSame('S/ 50.00', money('50'));
    }

    public function test_money_formatea_monto_sin_decimales(): void
    {
        $this->assertSame('S/ 100.00', money(100));
    }

    // ─── strftime_es() ──────────────────────────────────────────────

    public function test_strftime_es_formatea_fecha_en_espanol(): void
    {
        // 1 de enero de 2025 es miércoles
        $timestamp = mktime(12, 0, 0, 1, 1, 2025);
        $resultado = strftime_es($timestamp);

        $this->assertStringContainsString('miércoles', $resultado);
        $this->assertStringContainsString('1', $resultado);
        $this->assertStringContainsString('enero', $resultado);
        $this->assertStringContainsString('2025', $resultado);
    }

    public function test_strftime_es_mes_agosto(): void
    {
        $timestamp = mktime(12, 0, 0, 8, 15, 2026);
        $resultado = strftime_es($timestamp);

        $this->assertStringContainsString('agosto', $resultado);
        $this->assertStringContainsString('15', $resultado);
    }

    public function test_strftime_es_sin_argumento_usa_fecha_actual(): void
    {
        $resultado = strftime_es();
        $this->assertStringContainsString((string) date('Y'), $resultado);
    }

    // ─── fecha_hora() ────────────────────────────────────────────────

    public function test_fecha_hora_formatea_datetime(): void
    {
        $this->assertSame('15/06/2025 14:30', fecha_hora('2025-06-15 14:30:00'));
    }

    public function test_fecha_hora_valor_vacio_retorna_vacio(): void
    {
        $this->assertSame('', fecha_hora(''));
    }

    public function test_fecha_hora_null_retorna_vacio(): void
    {
        $this->assertSame('', fecha_hora(null));
    }

    // ─── fecha_corta() ──────────────────────────────────────────────

    public function test_fecha_corta_formatea_date(): void
    {
        $this->assertSame('15/06/2025', fecha_corta('2025-06-15'));
    }

    public function test_fecha_corta_valor_vacio_retorna_vacio(): void
    {
        $this->assertSame('', fecha_corta(''));
    }

    // ─── csrf_token() / csrf_validar() / csrf_field() ───────────────

    public function test_csrf_token_genera_token_hexadecimal_64_caracteres(): void
    {
        unset($_SESSION['csrf_token']);
        $token = csrf_token();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function test_csrf_token_reutiliza_token_existente(): void
    {
        unset($_SESSION['csrf_token']);
        $token1 = csrf_token();
        $token2 = csrf_token();
        $this->assertSame($token1, $token2);
    }

    public function test_csrf_validar_acepta_token_correcto(): void
    {
        unset($_SESSION['csrf_token']);
        $token = csrf_token();
        $this->assertTrue(csrf_validar($token));
    }

    public function test_csrf_validar_rechaza_token_incorrecto(): void
    {
        unset($_SESSION['csrf_token']);
        csrf_token();
        $this->assertFalse(csrf_validar('token_invalido_12345'));
    }

    public function test_csrf_validar_rechaza_null(): void
    {
        unset($_SESSION['csrf_token']);
        csrf_token();
        $this->assertFalse(csrf_validar(null));
    }

    public function test_csrf_field_genera_input_hidden(): void
    {
        $campo = csrf_field();
        $this->assertStringContainsString('type="hidden"', $campo);
        $this->assertStringContainsString('name="csrf_token"', $campo);
        $this->assertStringContainsString('value="', $campo);
    }

    // ─── flash() / flash_obtener() ──────────────────────────────────

    public function test_flash_guarda_y_recupera_mensaje(): void
    {
        flash('exito', 'Operación completada');
        $resultado = flash_obtener();

        $this->assertSame('exito', $resultado['tipo']);
        $this->assertSame('Operación completada', $resultado['mensaje']);
    }

    public function test_flash_se_elimina_despues_de_obtener(): void
    {
        flash('error', 'Algo falló');
        flash_obtener(); // primera lectura
        $this->assertNull(flash_obtener()); // ya no existe
    }

    public function test_flash_obtener_sin_mensaje_retorna_null(): void
    {
        unset($_SESSION['flash']);
        $this->assertNull(flash_obtener());
    }

    public function test_flash_sobreescribe_mensaje_anterior(): void
    {
        flash('exito', 'Primer mensaje');
        flash('error', 'Segundo mensaje');
        $resultado = flash_obtener();

        $this->assertSame('error', $resultado['tipo']);
        $this->assertSame('Segundo mensaje', $resultado['mensaje']);
    }
}
