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
| `tools/revisor.py [rango]` | Revisa un cambio sin conocer el razonamiento de quien lo escribio: recibe el mensaje del commit -que es la afirmacion a comprobar- y el diff, y busca afirmaciones sin respaldo, tests que pasarian con el fallo presente, y caminos que devuelven exito sin hacer nada. Devuelve 1 solo con hallazgos graves. |
| `comprobacion-nocturna.sh` | Recorre el alta entera en dev.real3d.io como una promotora nueva y avisa por correo si algo se rompe, o si algo responde 200 sin hacer lo que debe. Se limpia lo que crea. Lo lanza `real3d-comprobacion.timer` a las 4:15. |
| `real3d-backup.sh` | Copia diaria (base de datos y `.env`) con réplica de los volcados y de `storage/app` en la VM .13. Lo lanza `real3d-backup.timer` a las 03:30. |

## Instalar o actualizar en el servidor

```bash
sudo install -m 755 deploy/*.sh /usr/local/bin/
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
