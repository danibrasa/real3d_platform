# Registro de cambios

Todas las versiones publicadas de Real3D.io. A partir de la 1.0.0 este archivo lo
genera release-please a partir de los mensajes de commit, así que no se edita a mano.

Formato: [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).
Versionado: [SemVer](https://semver.org/lang/es/).

## 1.0.0 (2026-09-26)

Primera versión etiquetada. Recoge el estado de la plataforma tal y como llevaba
meses funcionando en producción, más el trabajo de puesta en orden de septiembre.
Las versiones siguientes ya se generan solas desde los commits.

### Novedades

* Visor 3D con modelos GLB, GLTF y FBX sobre vídeo o imagen 360, con ajustes de
  cámara, iluminación, suelo y escala guardados por proyecto.
* Panel de administración: proyectos, tipologías, viviendas, galería, planes de
  pago, avance de obra y consultas.
* Subida de archivos grandes por trozos, para los vídeos 360.
* Portal público con buscador, fichas de vivienda con SEO, directorio de
  promotoras y blog.
* Mapa de ubicación con puntos de interés.
* Generación de PDF: ficha de vivienda y calendario de pagos.
* Chatbot con IA por proyecto, y captura de contactos.
* API pública v1 con autenticación por token, y webhooks.
* Suscripciones y facturación con Stripe, con alta de empresas.
* Widget incrustable para webs de terceros.

### Correcciones

* El registro de analítica descartaba los rastreadores: el de Meta había generado
  2,9 millones de eventos (10 GB) siguiendo URLs que crecían sin fin.
* Los enlaces de idioma y moneda ya no arrastran parámetros ajenos, que era lo que
  generaba esas URLs infinitas.
* El enlace `public/storage` se crea en cada despliegue: sin él, las imágenes
  servidas desde `storage` daban 404.

### Cambios internos

* Despliegue por versiones, con vuelta atrás en segundos.
* Comprobaciones y despliegue automáticos con GitHub Actions, con aprobación
  manual antes de producción.
* Entorno de staging, separado y con datos anonimizados.
* Copias de seguridad diarias, replicadas en otra máquina y con restauración
  probada.
* Los tests corren contra MariaDB, porque hay migraciones con sintaxis de MySQL.
