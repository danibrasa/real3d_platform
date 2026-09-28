# Real3D.io — Plataforma de Visualización Inmobiliaria 3D

SaaS en Laravel para promotoras inmobiliarias: cada proyecto se ve en un visor 3D (modelo GLB/FBX
sobre vídeo o imagen 360), con fichas de vivienda, planes de pago, chatbot, API pública y portal
con blog.

## Stack
- Laravel 12 + Blade + Tailwind (Breeze), PHP 8.2-fpm, Nginx
- Three.js v0.162.0 por importmap desde CDN
- MariaDB 10.11 · Node.js 20 + Vite
- Archivos subidos en `storage/app/` (vídeos 360, modelos, PDFs). Credenciales solo en el `.env`
  de cada entorno, **nunca en el repositorio**.

## Operativa: no se edita nada en el servidor

Producción y staging se despliegan desde este repositorio. Si tocas archivos por SSH, el siguiente
despliegue se los lleva por delante.

### Ramas
| Rama | Qué es |
|---|---|
| `main` | Lo aprobado y listo para salir. Protegida: solo entra por pull request. Al fusionar se despliega en staging.real3d.io sola. |
| `production` | Lo que está sirviendo real3d.io **ahora mismo**. No se fusiona a mano: la mueve el despliegue cuando termina bien. |
| `feat/<issue>-<slug>` | Nuevas funciones |
| `fix/<slug>` · `hotfix/<slug>` | Errores normales o urgencias de producción |

**No hay rama `staging`.** Staging es un espejo de `main`, no una rama aparte: es el
mismo commit el que se valida y el que se publica, así que no puede pasar que se
pruebe una cosa y salga otra. Las ramas por entorno se separan con el tiempo
(un hotfix que entra en producción y nunca vuelve, promociones selectivas
imposibles, conflictos al reintegrar) y esos problemas aquí no existen.

`production` es lo contrario a `main`: no es un sitio donde fusionar, es un
indicador de dónde ha llegado el despliegue. Sirve para `git clone -b production`
cuando hay que levantar lo que está publicado en otro sitio, sin tener que
averiguar antes qué versión hay puesta.

Commits con Conventional Commits (`feat:`, `fix:`, `chore:`, `style:`, `ci:`). Cada salida a
producción lleva su etiqueta SemVer.

### Qué protege a `main` (ruleset, no *branch protection*)

En repositorios privados del plan Free, la protección clásica de ramas no funciona: hay que
usar **Rulesets** (Settings → Rules). El que hay sobre `main` exige:

- **Pull request** para cualquier cambio (0 aprobaciones: el equipo es pequeño y el freno
  de verdad está al publicar, no al fusionar).
- **Que pase `Estilo, tests y compilacion`**, que es el *job* de `ci.yml`. Ojo: en la lista
  va el nombre del job, no el del workflow («Comprobaciones»).
- Ni borrar la rama ni forzar el historial.

**Trampa conocida con release-please.** Sus pull requests las abre el `GITHUB_TOKEN` del
propio workflow, y GitHub aparca en `action_required` los workflows que dispara su propio
token, para que un workflow no pueda encadenarse consigo mismo sin fin. Resultado: el CI de
esas PRs **no arranca solo**, y como ahora es obligatorio, quedan sin poder fusionarse.

Hay que entrar en su ejecución y pulsar *Approve and run*: un clic por cada versión. Si
llega a molestar, la salida estándar es darle a `release.yml` un token propio en un secreto
en vez del `GITHUB_TOKEN`, y entonces su CI arranca como el de cualquier rama.

Esto **no afecta a Dependabot**: sus pull requests sí ejecutan el CI con normalidad.

### Circuito
```
rama  →  PR  →  main  →  staging.real3d.io  →  [aprobación]  →  real3d.io
                              (automático)                      (production)
```

1. Rama a partir de `main`.
2. Desarrollo en dev.real3d.io, con su test y su migración.
3. Pull request. El CI comprueba estilo (Pint), compila los assets y pasa los tests.
   Para verlo montado sin fusionarlo: `staging-deploy.sh <rama>`.
4. Merge en `main` → staging.real3d.io se actualiza solo en cuanto el CI pasa.
5. El equipo valida en staging.
6. **Publicar**: Actions → *Desplegar en produccion* → **Run workflow** sobre `main`, y
   aprobar cuando lo pida. Antes de pedir nada comprueba que el CI esté en verde en `main`
   y lista los commits que va a sacar. Al acabar, la rama `production` apunta a eso.

Publicar es a mano a propósito, y **no se encola por cada fusión**: se pueden meter diez pull
requests, irlos viendo en staging, y publicar una sola vez. Mientras tanto `main` acumula lo
aprobado sin publicar; para ver cuánto, `deploy-status.sh`.

Un `hotfix/` puede aprobarse sin esperar a que nadie valide staging, pero nunca se salta el
CI. Todo fix de un error de producción incluye un test que lo reproduce.

## Entornos

| | Producción | Staging | Desarrollo |
|---|---|---|---|
| URL | https://real3d.io | https://staging.real3d.io | https://dev.real3d.io |
| Acceso | público | contraseña + `noindex` | contraseña + `noindex` |
| Ruta | `/var/www/real3d/current` | `/var/www/staging` | `/var/www/dev` |
| Base de datos | `realestate_3d` | `realestate_3d_staging` | `realestate_3d_dev` |
| | | (datos anonimizados) | (datos anonimizados) |
| Qué sirve | el commit aprobado (= rama `production`) | `main`, automático | la rama en la que se esté trabajando |
| Máquina | 194.41.119.105 | 194.41.119.105 | 194.41.119.13 |

Producción y staging comparten la VM **194.41.119.105** (VM 115 `dani` en Proxmox, nodo2).
El desarrollo va aparte, en la **194.41.119.13** (VM 214 `dani2`, nodo0), precisamente para que
trabajar no pueda tumbar lo que el equipo está revisando. Los nombres de Proxmox están cruzados
respecto a lo que uno esperaría: comprobar siempre la IP, no el nombre.

## Despliegue por versiones

```
/var/www/real3d/
├── current -> releases/<fecha>    lo que sirve nginx
├── releases/                       se guardan las 5 ultimas
└── shared/    .env y storage/      comunes a todas las versiones
```

- `real3d-deploy.sh [rama]` — construye la versión nueva, migra y cachea, y solo entonces cambia
  el enlace `current`. Si algo falla antes, no se activa. Si falla la comprobación de salud,
  vuelve sola a la versión anterior.
- `real3d-rollback.sh [version]` — vuelve atrás en segundos (`--lista` para verlas).
  **Las migraciones de base de datos no se revierten.**
- `staging-deploy.sh [rama]` — actualiza staging. Sin argumentos, `main`; con un nombre de
  rama, permite ver un pull request montado sin fusionarlo.
- `deploy-status.sh` — qué hay en cada entorno y qué commits están fusionados sin publicar.
  Solo lee. Avisa si la rama `production` no coincide con lo que sirve la web, que es la
  señal de que alguien ha desplegado a mano por SSH.

## Servicios (systemd, no hay cron instalado)
- `real3d-queue` — worker de colas (webhooks, correos)
- `real3d-schedule.timer` — `schedule:run` cada minuto
- `real3d-backup.timer` — copia diaria a las 03:30, replicada en la VM .13

## Dependencias

Dependabot abre un pull request al mes por ecosistema (PHP, Node y las propias
acciones de GitHub), agrupando todas las actualizaciones menores en uno solo.
Los avisos de **seguridad** no esperan a esa cita: llegan al momento y con el
arreglo ya preparado.

Los saltos de version mayor estan excluidos a proposito: de Laravel 12 a 13 hay
que leerse las notas de migracion, no fusionarlo a ciegas.

Comprobar a mano el estado: `composer audit` y `npm audit`.

## Tests
`php artisan test`. Corren contra **MariaDB, no sqlite**: hay migraciones con
`ALTER TABLE ... MODIFY COLUMN ENUM`, sintaxis propia de MySQL. Compila los assets antes
(`npm run build`), porque varias vistas piden el manifiesto de Vite y sin él devuelven 500.

## Rutas principales
- `/` — landing · `/portal` y `/portal/blog` — portal público · `/developers` — directorio
- `/projects` — listado · `/projects/{slug}` — visor 3D · `/projects/{slug}/info` — ficha
- `/projects/{slug}/units/{unit}` — detalle de vivienda
- `/admin/*` — panel (middleware `auth` + `admin`)
- `/api/projects/{slug}` — JSON con datos, ajustes y URLs de archivos
- `/api/v1/*` — API pública con token (Sanctum)
- `/embed/{slug}` — widget incrustable

## Analítica del visor
`viewer_events` registra la actividad del visor. **No registra rastreadores**: el filtro está en
`ViewerEventController::BOT_PATTERN`. Hizo falta porque el rastreador de Meta llegó a generar
2,9 millones de filas (10 GB) siguiendo URLs infinitas.

Los enlaces con parámetros se construyen con `App\Support\QueryUrl::with()`, que solo conserva
`lang` y `currency` con valores de una lista cerrada. **No uses `request()->fullUrlWithQuery()`**:
arrastra cualquier parámetro de la URL y fue lo que generó el bucle.

## Subida de archivos grandes (vídeos 360)
1. `POST upload/init` → UUID de sesión
2. `POST upload/chunk` × N (5 MB por trozo)
3. `POST upload/complete` → ensambla en `storage/app/projects/{id}/{type}/`

Nginx necesita `client_max_body_size` por encima del tamaño de trozo.

## Ajustes del visor (`project_settings`)
`model_rotation` 0-360 · `model_scale` 1-200 (% de baseScale) · `model_elevation` -50..50 (×0,5)
`ground_height` -100..100 (×0,5) · `ground_texture_type` grass/concrete/dirt/custom
`ground_opacity` y `video_opacity` 0-100 · `lighting_preset` morning/noon/evening
`camera_position_x/y/z`, `camera_target_x/y/z` · `wireframe` · `background_type` video/image

## Comandos útiles
- `php artisan test` · `vendor/bin/pint` (estilo)
- `php artisan migrate` · `php artisan db:seed`
- `npm run dev` (desarrollo) · `npm run build` (producción)
