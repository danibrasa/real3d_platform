# Scripts de operativa

Los scripts que despliegan y respaldan Real3D.io. Viven instalados en
`/usr/local/bin/` de la VM de producción (194.41.119.105); esta carpeta es la
copia de referencia, para que no existan solo en la máquina.

| Script | Qué hace |
|---|---|
| `real3d-deploy.sh [rama]` | Despliega producción por versiones. Construye la versión nueva, migra y cachea, y solo entonces cambia el enlace `current`. Si la comprobación de salud falla, vuelve sola a la anterior. |
| `real3d-rollback.sh [version]` | Vuelve a una versión anterior. `--lista` para verlas. **No revierte migraciones de base de datos.** |
| `staging-deploy.sh [rama]` | Actualiza staging.real3d.io. Con un nombre de rama, permite probar un pull request sin fusionarlo. |
| `real3d-backup.sh` | Copia diaria (base de datos y `.env`) con réplica de los volcados y de `storage/app` en la VM .13. Lo lanza `real3d-backup.timer` a las 03:30. |

## Instalar o actualizar en el servidor

```bash
sudo install -m 755 deploy/*.sh /usr/local/bin/
```

Los scripts no llevan ninguna credencial dentro: leen lo que necesitan de
`/root/.*-pass` y del `.env` compartido.

## Cosas que conviene saber antes de tocarlos

- **No hay `cron` instalado** en las VMs. Todo lo periódico va con temporizadores
  de systemd (`real3d-backup.timer`, `real3d-schedule.timer`).
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
