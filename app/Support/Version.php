<?php

namespace App\Support;

/**
 * De que version se trata y que hay desplegado ahora mismo.
 *
 * Son dos cosas distintas y conviene no mezclarlas:
 *  - release: el tag publicado (v1.4.0), que es lo que aparece en el changelog.
 *  - build:   el despliegue concreto que esta corriendo (commit y fecha), que es
 *             lo que resuelve la duda de "¿esto ya tiene el arreglo?".
 *
 * Los datos salen de version.json, que escribe el script de despliegue en cada
 * release. En local ese fichero no existe: entonces se cae a git, y si tampoco
 * hay git, a valores de desarrollo. Nunca lanza excepcion.
 */
class Version
{
    private static ?array $cache = null;

    /** @return array{release: string, commit: string, commit_short: string, deployed_at: ?string, environment: string} */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        return self::$cache = self::read() + [
            'release' => 'dev',
            'commit' => 'unknown',
            'commit_short' => 'unknown',
            'deployed_at' => null,
            'environment' => app()->environment(),
        ];
    }

    /** El tag publicado, por ejemplo "v1.4.0" (o "dev" en local). */
    public static function release(): string
    {
        return self::all()['release'];
    }

    /** Commit corto del despliegue, por ejemplo "a9b4dde". */
    public static function commit(): string
    {
        return self::all()['commit_short'];
    }

    /** Texto para mostrar: "v1.4.0 (a9b4dde)". */
    public static function label(): string
    {
        $v = self::all();

        return $v['commit_short'] !== 'unknown'
            ? "{$v['release']} ({$v['commit_short']})"
            : $v['release'];
    }

    private static function read(): array
    {
        $path = base_path('version.json');

        if (is_readable($path)) {
            $data = json_decode((string) file_get_contents($path), true);
            if (is_array($data)) {
                return array_filter($data, fn ($v) => $v !== null && $v !== '');
            }
        }

        // Sin version.json (entorno local): se intenta git, sin romper si no hay.
        $head = base_path('.git/HEAD');
        if (is_readable($head)) {
            $ref = trim((string) file_get_contents($head));
            $sha = str_starts_with($ref, 'ref: ')
                ? @file_get_contents(base_path('.git/'.substr($ref, 5)))
                : $ref;
            if (is_string($sha) && ($sha = trim($sha)) !== '') {
                return ['commit' => $sha, 'commit_short' => substr($sha, 0, 7)];
            }
        }

        return [];
    }
}
