<?php

declare(strict_types=1);

namespace App\Tenancy;

/** Datos para dar de alta una institución (tools/alta_tenant.php). Se valida todo antes de crear nada. */
final class SolicitudAlta
{
    /** Subdominios que no pueden ser de un colegio (infraestructura y suplantación). */
    public const RESERVADOS = ['www', 'maestro', 'admin', 'api', 'app', 'mail', 'correo', 'panel', 'soporte', 'ftp', 'cpanel', 'webmail', 'static', 'cdn'];

    public readonly string $baseDatos;

    public function __construct(
        public readonly string $slug,
        public readonly string $razonSocial,
        public readonly string $email,
        public readonly string $adminDni,
        public readonly string $adminNombres,
        public readonly string $adminApellidos,
        public readonly string $adminUsuario = 'admin',
        public readonly string $tipo = 'COLEGIO',
        public readonly EstadoTenant $estado = EstadoTenant::Prueba,
        public readonly ?string $dominio = null,
        ?string $baseDatos = null,
        /** Fase 4B.6: con los datos de ejemplo anonimizados, para que el colegio pruebe el sistema. */
        public readonly bool $demo = false,
    ) {
        $errores = [];
        if (!Tenant::slugValido($slug)) {
            $errores[] = 'slug: minúsculas, dígitos y guiones, sin guion al principio ni al final (máx. 63)';
        } elseif (in_array($slug, self::RESERVADOS, true)) {
            $errores[] = "slug: «{$slug}» está reservado";
        }
        $this->baseDatos = $baseDatos ?? 'sge_' . str_replace('-', '_', $slug);
        if (preg_match('/^[A-Za-z0-9_]{1,64}$/', $this->baseDatos) !== 1) {
            $errores[] = 'base: solo letras, dígitos y guion bajo (máx. 64)';
        }
        if (trim($razonSocial) === '' || mb_strlen($razonSocial) > 200) {
            $errores[] = 'razón social: obligatoria, máx. 200 caracteres';
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errores[] = 'email: no es una dirección válida';
        }
        if (preg_match('/^\d{8}$/', $adminDni) !== 1) {
            $errores[] = 'DNI del administrador: 8 dígitos';
        }
        if (trim($adminNombres) === '' || trim($adminApellidos) === '') {
            $errores[] = 'nombres y apellidos del administrador: obligatorios';
        }
        // SP_REGISTRAR_PERSONAL recibe el usuario como VARCHAR(8).
        if (preg_match('/^[A-Za-z0-9._-]{3,8}$/', $adminUsuario) !== 1) {
            $errores[] = 'usuario del administrador: 3 a 8 letras, dígitos, punto, guion o guion bajo';
        }
        if (!in_array($tipo, ['COLEGIO', 'INSTITUTO', 'CETPRO'], true)) {
            $errores[] = 'tipo: COLEGIO, INSTITUTO o CETPRO';
        }
        if ($dominio !== null && ResolverTenant::normalizarHost($dominio) !== $dominio) {
            $errores[] = 'dominio: nombre de host en minúsculas, sin protocolo ni puerto';
        }
        if ($errores !== []) {
            throw new \InvalidArgumentException("Datos de alta inválidos:\n  - " . implode("\n  - ", $errores));
        }
    }
}
