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
| `main` | Lo que hay en producción. Protegida: solo entra por pull request. |
| `staging` | Lo que se está validando. Al fusionar, se despliega en staging.real3d.io |
| `feat/<issue>-<slug>` | Nuevas funciones |
| `fix/<slug>` · `hotfix/<slug>` | Errores normales o urgencias de producción |

Commits con Conventional Commits (`feat:`, `fix:`, `chore:`, `style:`, `ci:`). Cada salida a
producción lleva su etiqueta SemVer.

### Circuito
1. Rama a partir de `main`.
2. Desarrollo en local, con su test y su migración.
3. Pull request. El CI comprueba estilo (Pint), compila los assets y pasa los tests.
4. Validación en staging (`staging-deploy.sh <rama>` permite probar un PR sin fusionarlo).
5. Merge en `main` → se despliega producción.

Un `hotfix/` puede saltarse staging, pero nunca el CI. Todo fix de un error de producción incluye
un test que lo reproduce.

## Entornos

| | Producción | Staging |
|---|---|---|
| URL | https://real3d.io | https://staging.real3d.io (con contraseña, `noindex`) |
| Ruta | `/var/www/real3d/current` | `/var/www/staging` |
| Base de datos | `realestate_3d` | `realestate_3d_staging` (datos anonimizados) |
| Rama | `main` | `staging` |

Los dos viven en la VM 194.41.119.105 (VM 115 `dani` en Proxmox, nodo2). **Cuidado:** existe una
VM gemela en 194.41.119.13 con una copia de febrero de 2026; no es producción y no se toca.

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
- `staging-deploy.sh [rama]` — actualiza staging.

## Servicios (systemd, no hay cron instalado)
- `real3d-queue` — worker de colas (webhooks, correos)
- `real3d-schedule.timer` — `schedule:run` cada minuto
- `real3d-backup.timer` — copia diaria a las 03:30, replicada en la VM .13

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
