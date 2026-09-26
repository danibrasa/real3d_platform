<?php

namespace App\Providers;

use App\Support\Version;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Sentry\State\Scope;

/**
 * Añade a cada error el contexto que hace falta para actuar sobre el.
 *
 * Un aviso que solo dice "excepcion en linea 42" obliga a investigar desde cero.
 * Con la version desplegada y quien lo sufrio, casi siempre se sabe de que va
 * antes de abrir el codigo: si el fallo aparecio con la ultima publicacion, o
 * si le pasa a una promotora concreta.
 */
class SentryContextProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! app()->bound('sentry')) {
            return;
        }

        \Sentry\configureScope(function (Scope $scope): void {
            $v = Version::all();

            // Version desplegada: responde a "¿esto lo rompimos al publicar?"
            $scope->setTag('release', $v['release']);
            $scope->setTag('commit', $v['commit_short']);

            if ($v['deployed_at']) {
                $scope->setContext('despliegue', [
                    'version' => $v['release'],
                    'commit' => $v['commit'],
                    'desplegado' => $v['deployed_at'],
                ]);
            }

            // Quien lo sufrio. Sin datos de mas: id y correo bastan para avisarle,
            // y el correo solo si send_default_pii lo permite.
            if ($user = Auth::user()) {
                $scope->setUser(array_filter([
                    'id' => $user->getAuthIdentifier(),
                    'email' => config('sentry.send_default_pii') ? $user->email : null,
                ]));
            }
        });
    }
}
