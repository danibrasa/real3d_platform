# Proyecto: Plataforma de Visualización Inmobiliaria 3D

## Resumen
Plataforma web Laravel para visualizar promociones inmobiliarias en 3D.
- Admin configura proyectos (sube video 360, modelo 3D GLB, ajusta settings)
- Usuarios finales ven los proyectos publicados en un visor Three.js read-only

## Stack
- Laravel 11 + Blade + Tailwind (Breeze)
- Three.js v0.162.0 via CDN importmap
- MariaDB 10.11 (base: realestate_3d, user: realestate, pass: R3alEst4te_2024!)
- PHP 8.2-fpm + Nginx
- Node.js 20 + Vite
- Almacenamiento local en storage/app/projects/

## Ubicación del proyecto
- Laravel: /var/www/3d-platform
- Docs y archivos originales: /root/project-docs/

## Archivos originales del visor Three.js (copiar como referencia)
- /root/project-docs/original-viewer-app.js (722 líneas - Three.js completo)
- /root/project-docs/original-viewer-style.css (CSS del visor)
- /root/project-docs/original-viewer-index.html (HTML original)
- /root/project-docs/PLAN.md (plan completo de implementación)

## Base de datos
DB: realestate_3d
User: realestate
Pass: R3alEst4te_2024!
Host: localhost

### Tablas:
- users (+ columna role ENUM admin/user)
- projects (name, slug, description, location, status draft/published, thumbnail_path, created_by)
- project_files (project_id, file_type video_360/model_3d/ground_texture/thumbnail, storage_path, mime_type, file_size)
- project_settings (project_id UNIQUE, model_rotation, model_scale, model_elevation, ground_height, ground_texture_type, ground_opacity, ground_visible, video_opacity, lighting_preset, camera_position_x/y/z, camera_target_x/y/z, wireframe)
- upload_chunks (upload_id UUID, project_id, file_type, total_chunks, received_chunks, temp_directory)

## Roles
- admin: CRUD proyectos, upload archivos, configurar visor
- user: ver proyectos publicados (read-only)

## Rutas principales
- /admin/* → Panel admin (middleware auth + admin)
- /projects → Listado público de proyectos
- /projects/{slug} → Visor público read-only
- /api/projects/{slug} → JSON con data + settings + file URLs
- /api/projects/{id}/files/{type} → Stream de archivos (video, modelo)

## Visor Three.js - Refactoring
El app.js original se refactoriza en:
- viewer-core.js → clase ViewerEngine (loadFromURL + loadFromFile + applySettings + getCurrentSettings)
- viewer-admin.js → modo admin con controles + save settings
- viewer-public.js → modo usuario, fetch API y apply automático

## Settings del visor (valores de los sliders)
- model_rotation: 0-360 (grados)
- model_scale: 1-200 (porcentaje de baseScale)
- model_elevation: -50 a 50 (offset * 0.5)
- ground_height: -100 a 100 (value * 0.5)
- ground_texture_type: grass/concrete/dirt/custom
- ground_opacity: 0-100
- video_opacity: 0-100
- lighting_preset: morning/noon/evening
- camera_position_x/y/z, camera_target_x/y/z
- wireframe: boolean

## Upload chunked (para videos 360 pesados)
1. POST upload/init → UUID de sesión
2. POST upload/chunk × N (5MB cada chunk)
3. POST upload/complete → ensambla en storage/app/projects/{id}/{type}/

## Nginx
Configurar virtual host apuntando a /var/www/3d-platform/public
Aumentar client_max_body_size a 10M (chunks de 5MB)

## Comandos útiles
- php artisan serve (dev server)
- php artisan migrate
- php artisan db:seed
- npm run dev (Vite)
- npm run build (Vite production)
