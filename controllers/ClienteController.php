<?php

require_once __DIR__ . '/../models/Cliente.php';

/**
 * Reglas de negocio del registro de clientes.
 *
 * RN: una persona natural se identifica con DNI de 8 digitos y una empresa
 * con RUC de 11 digitos.
 */
class ClienteController
{
    private $modelo;

    public function __construct()
    {
        $this->modelo = new Cliente();
    }

    public function listar($filtros = [])
    {
        return $this->modelo->listar($filtros);
    }

    public function obtener($id)
    {
        return $this->modelo->obtenerPorId($id);
    }

    public function buscar($termino)
    {
        return $this->modelo->buscar($termino);
    }

    public function historial($id)
    {
        return $this->modelo->historialCompras($id);
    }

    public function guardar($datos)
    {
        $id   = !empty($datos['id_cliente']) ? (int) $datos['id_cliente'] : null;
        $tipo = ($datos['tipo_documento'] ?? 'DNI') === 'RUC' ? 'RUC' : 'DNI';

        $limpio = [
            'tipo_documento'   => $tipo,
            'numero_documento' => trim($datos['numero_documento'] ?? ''),
            'nombres'          => trim($datos['nombres'] ?? ''),
            'apellidos'        => trim($datos['apellidos'] ?? ''),
            'razon_social'     => trim($datos['razon_social'] ?? ''),
            'telefono'         => trim($datos['telefono'] ?? ''),
            'email'            => trim($datos['email'] ?? ''),
            'direccion'        => trim($datos['direccion'] ?? ''),
            'estado'           => isset($datos['estado']) ? (int) $datos['estado'] : 1,
        ];

        $error = $this->validar($limpio, $id);
        if ($error !== null) {
            return ['ok' => false, 'mensaje' => $error];
        }

        // Los campos que no corresponden al tipo de documento quedan vacios
        if ($tipo === 'RUC') {
            $limpio['nombres']   = null;
            $limpio['apellidos'] = null;
        } else {
            $limpio['razon_social'] = null;
        }

        foreach (['telefono', 'email', 'direccion'] as $campo) {
            if ($limpio[$campo] === '') {
                $limpio[$campo] = null;
            }
        }

        if ($id === null) {
            $this->modelo->crear($limpio);

            return ['ok' => true, 'mensaje' => 'Cliente registrado correctamente.'];
        }

        $this->modelo->actualizar($id, $limpio);

        return ['ok' => true, 'mensaje' => 'Cliente actualizado correctamente.'];
    }

    private function validar($datos, $id)
    {
        $documento = $datos['numero_documento'];

        if ($documento === '') {
            return 'El número de documento es obligatorio.';
        }

        if (!ctype_digit($documento)) {
            return 'El número de documento solo puede contener dígitos.';
        }

        if ($datos['tipo_documento'] === 'DNI' && strlen($documento) !== 8) {
            return 'El DNI debe tener exactamente 8 dígitos.';
        }

        if ($datos['tipo_documento'] === 'RUC' && strlen($documento) !== 11) {
            return 'El RUC debe tener exactamente 11 dígitos.';
        }

        if ($datos['tipo_documento'] === 'DNI') {
            if ($datos['nombres'] === '' || $datos['apellidos'] === '') {
                return 'Ingrese los nombres y apellidos del cliente.';
            }
        } else {
            if ($datos['razon_social'] === '') {
                return 'Ingrese la razón social de la empresa.';
            }
        }

        if ($datos['email'] !== '' && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            return 'El correo electrónico no tiene un formato válido.';
        }

        if ($datos['telefono'] !== '' && !preg_match('/^[0-9 ]{6,20}$/', $datos['telefono'])) {
            return 'El teléfono solo puede contener dígitos y espacios.';
        }

        if ($this->modelo->documentoExiste($documento, $id)) {
            return 'Ya existe un cliente registrado con ese número de documento.';
        }

        return null;
    }

    public function cambiarEstado($id, $estado)
    {
        $this->modelo->cambiarEstado((int) $id, (int) $estado);

        return [
            'ok'      => true,
            'mensaje' => (int) $estado === 1 ? 'Cliente activado.' : 'Cliente desactivado.',
        ];
    }
}
