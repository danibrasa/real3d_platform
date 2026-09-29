# Scripts de operativa

Los scripts que despliegan y respaldan Real3D.io. Viven instalados en
`/usr/local/bin/` de la VM de producción (194.41.119.105); esta carpeta es la
copia de referencia, para que no existan solo en la máquina.

| Script | Qué hace |
|---|---|
| `real3d-deploy.sh [rama]` | Despliega producción por versiones. Construye la versión nueva, migra y cachea, y solo entonces cambia el enlace `current`. Si la comprobación de salud falla, vuelve sola a la anterior. |
| `real3d-rollback.sh [version]` | Vuelve a una versión anterior. `--lista` para verlas. **No revierte migraciones de base de datos.** |
| `staging-deploy.sh [rama]` | Actualiza staging.real3d.io. Sin argumentos despliega `main`, que es lo que hace el workflow en cada fusion. Con un nombre de rama, permite ver un pull request montado sin fusionarlo. |
| `deploy-status.sh` | Que version hay en cada entorno y que commits estan fusionados en `main` sin publicar. Solo lee. Funciona en cualquiera de las dos VMs: usa el primer clon del repositorio que encuentra. |
| `dev-switch.sh [rama]` | Pone el entorno de desarrollo (dev.real3d.io, en la máquina auxiliar) en una rama. Aparta los cambios sin guardar con `git stash` en vez de abortar. Sin versiones ni vuelta atrás: es un entorno desechable. |
| `dev-refresh.sh` | Recrea la base de datos de desarrollo desde la copia del día y la anonimiza. De paso comprueba que la copia se restaura. Vive en la máquina auxiliar. |
| `tools/revisor.py [rango]` | Revisa un cambio sin conocer el razonamiento de quien lo escribio: recibe el mensaje del commit -que es la afirmacion a comprobar- y el diff, y busca afirmaciones sin respaldo, tests que pasarian con el fallo presente, y caminos que devuelven exito sin hacer nada. Devuelve 1 solo con hallazgos graves. **Desde el 29-sep-2026 no hay crédito de API para esto**: la revisión la hace un subagente en la sesión de Claude Code con el mismo encargo (`INSTRUCCIONES`), y `fusionar.py` se lanza con `--revisada-en-sesion`. |
| `docs/guia-de-montaje.md` | Cómo se monta un visor, paso a paso, con lo que aceptamos y la lista de comprobación antes de darlo por montado. Para quien lo hace por primera vez. |
| `tools/recorrido-visor.mjs <url>` | Abre un visor con Chromium (Playwright) como un teléfono y dice si pinta, si la lista de viviendas aparece y abre fichas, si desborda y si la consola está limpia. Lo llama la comprobación nocturna en su paso 10; necesita `npx playwright install --with-deps chromium` en la máquina auxiliar. |
| `CORREO=… CLAVE=… node tools/recorrido-panel.mjs <base> [rutas…]` | Entra en el panel con esa cuenta y mira las pantallas como un teléfono de 390 px (o `ANCHO=1280`): que no desborden, que lo importante se vea, que la consola esté limpia; `CAPTURAS=dir` guarda pantallazos. Sin rutas recorre la bandeja y el primer lead; la nocturna lo llama en el paso 9c con la promotora del recorrido. |
| `comprobacion-nocturna.sh` | Recorre el alta entera en dev.real3d.io como una promotora nueva y avisa por correo si algo se rompe, o si algo responde 200 sin hacer lo que debe. Se limpia lo que crea. Lo lanza `real3d-comprobacion.timer` a las 4:15. |
| `real3d-backup.sh` | Copia diaria (base de datos y `.env`) con réplica de los volcados y de `storage/app` en la VM .13. Lo lanza `real3d-backup.timer` a las 03:30. |
| `comprobar-copia.sh` | Restaura la copia más reciente en una base aparte y comprueba que sirve: edad, tablas, migraciones, usuarios y proyectos. Lo llama la comprobación nocturna. |
| `salud-de-produccion.sh [url]` | Lee `/salud` de producción (base, correo, cola, planificador y disco, que desde fuera no se ven) y mira cuánto le queda al certificado. Lo llama la comprobación nocturna. |
| `php artisan visor:preparar --ahora` | Una vez tras desplegar la versión que trae el fondo 360 en 2K y el modelo con Draco: saca esas versiones de lo que ya estaba subido. Lo nuevo las saca solo al subir. |
| `nginx-ficheros.conf` | El `location /_ficheros/` interno por el que nginx sirve el modelo y el fondo 360 cuando la aplicación contesta con `X-Accel-Redirect`. Se copia a `/etc/nginx/snippets/real3d-ficheros.conf` cambiando `__RAIZ__` por la raíz del sitio, se incluye en su bloque `server`, y se pone `FICHEROS_POR_NGINX=true` en el `.env`. Sin eso PHP sigue sirviéndolos él mismo, que funciona pero cuesta un proceso por descarga. |
| `real3d-schedule.service` + `.timer` | Lanza `schedule:run` cada minuto en producción. Sin esto las tareas programadas no fallan: no ocurren. `/salud` lo vigila con un latido. |
| `dev-schedule.service` + `.timer`, `dev-queue.service` | Lo mismo para desarrollo en la VM .13: planificador y worker de colas, para que desarrollo se comporte como producción. |

## Instalar o actualizar en el servidor

```bash
sudo install -m 755 deploy/*.sh /usr/local/bin/
sudo install -m 644 deploy/real3d-*.service deploy/real3d-*.timer /etc/systemd/system/ && sudo systemctl daemon-reload
```

Los scripts no llevan ninguna credencial dentro: leen lo que necesitan de
`/root/.*-pass` y del `.env` compartido.

## Cosas que conviene saber antes de tocarlos

- **No hay `cron` instalado** en las VMs. Todo lo periódico va con temporizadores
  de systemd (`real3d-backup.timer`, `real3d-schedule.timer`,
  `real3d-comprobacion.timer`).
- **El enlace `public/storage` lo crea root**, apuntando a
  `shared/storage/app/public`. No sirve `artisan storage:link` como `www-data`:
  `public/` es de `deploy:www-data` sin escritura de grupo, así que falla y deja
  los archivos públicos dando 404.
- **La comprobación de salud mira también un archivo público.** Solo con la
  portada no basta: el 26-sep-2026 un despliegue se declaró correcto mientras el
  logo de `/developers` daba 404, porque la página devolvía 200 con la imagen
  rota dentro.
- GitHub Actions los invoca por SSH como el usuario `deploy`, que tiene `sudo`
  limitado a estos scripts (`/etc/sudoers.d/deploy-real3d`).
- **La rama `production` la mueve el workflow, no estos scripts.** Se apunta al
  commit que ha quedado servido al terminar el despliegue, leyendolo del propio
  servidor (`git -C /var/www/real3d/current rev-parse HEAD`) en vez de darlo por
  supuesto: `real3d-deploy.sh main` despliega el HEAD de `main` en el momento de
  ejecutarse, que puede ser mas nuevo que el commit que disparo el run si se han
  fusionado mas pull requests mientras esperaba aprobacion.
- Por eso un despliegue lanzado **a mano por SSH** deja la rama desfasada:
  `deploy-status.sh` lo avisa. Si pasa, o se relanza desde Actions o se mueve la
  rama a mano.
