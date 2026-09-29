# Guía de montaje de un visor

Tarea 4.2 del plan hacia el PMV. Para quien monta un visor por primera vez:
si sigues esto de arriba abajo, el visor sale. Si algo de aquí no coincide con
lo que ves en el panel, la guía está mal, no tú: corrígela en el mismo PR.

## 0. Antes de empezar: qué es un visor montado

Un visor está montado cuando un comprador, desde el móvil, abre
`real3d.io/projects/<slug>`, ve el proyecto en 3D sobre su fondo en menos de
tres segundos, **toca una vivienda y le sale su ficha** con precio y botón de
WhatsApp. Todo lo demás es preparación para eso.

## 1. Coger el trabajo en la cola

`Panel → Visores pendientes`. Cada fila es un proyecto que ha pedido visor.

- Pon **quién** lo lleva (tú) y **para cuándo**. Con eso la promotora ve en su
  ficha "en preparación · previsto para el X" y deja de escribir preguntando.
- Cambia el estado a **En preparación**. El reloj de "parado" se reinicia
  con cada cambio de estado; a los cinco días sin moverse sale en rojo y la
  nocturna avisa por correo.
- Apunta las **horas** al terminar cada sesión. Es el número que decide el
  precio del producto: no te lo saltes.

## 2. Recoger el material

En la fila de la cola, el enlace de **Material** lleva a lo que la promotora
entregó: planos, renders, foto o vídeo 360, modelo. Lo que pesa mucho llega
como enlace (Drive, WeTransfer). Si falta algo imprescindible (planos o
renders), escríbele desde la ficha del proyecto antes de empezar: montar sin
planos es montar dos veces.

## 3. Preparar el modelo 3D

Lo que acepta el visor: **GLB** (preferido) o glTF; FBX se sirve tal cual y
no se comprime. Tope de subida: 60 MB.

- **Un objeto por vivienda**, con un nombre reconocible (`A-101`, `B-203`).
  No hace falta que coincida con el identificador del panel: el mapeo se hace
  después con las cajas, pero un modelo ordenado se mapea en minutos y uno
  con todo fundido en una malla, en horas.
- Escala real en metros y el origen en la base del edificio. El visor tiene
  ajustes de rotación, escala y elevación, pero cuanto menos haya que
  corregir ahí, menos sorpresas.
- Texturas dentro del GLB, de 2K como mucho. Un edificio no necesita 8K.
- **La compresión Draco la hace la plataforma al subir** (de 35 MB a 5–8).
  No hace falta comprimir a mano; si lo haces, no pasa nada.

## 4. Preparar el fondo 360

- **Foto 360**: JPEG equirectangular (proporción 2:1), 8192×4096 como mucho,
  tope 30 MB. La plataforma saca sola la versión de 2K que el visor carga
  primero; tú subes la buena.
- **Vídeo 360**: MP4 H.264, 4K como mucho, **tope 100 MB**. Por encima no
  entra: recomprime antes (HandBrake, "Fast 1080p30" con 4K vale). Un vídeo
  de 315 MB son cinco minutos de 4G para un comprador.
- Elige uno. En `Configuración del visor → background_type` se dice cuál usa
  el visor; el otro no se descarga.

## 5. Subir

`Ficha del proyecto → Archivos`. La subida va por trozos y se reanuda si se
corta; subir otra vez con el mismo nombre reemplaza el anterior.

Orden que ahorra viajes: primero la **portada** (thumbnail, la que se ve en
el listado y detrás del cargador), luego el **fondo 360**, luego el
**modelo**. Al subir el 360 y el modelo, la plataforma encola sus versiones
ligeras; tardan un minuto.

## 6. Ajustar el visor

`Ficha del proyecto → Configuración del visor`: rotación, escala y elevación
del modelo; suelo (altura, textura, opacidad); iluminación; cámara inicial.
Guarda una cámara inicial desde la que se vea el edificio entero y el fondo
detrás: es lo primero que ve el comprador y lo que sale en la vista previa.

## 7. Mapear las viviendas

`Ficha del proyecto → Mapeo de viviendas`. Cada vivienda del panel se une a
una **caja** sobre el modelo: es lo que hace que tocar el 3D abra su ficha, y
que elegirla en la lista lleve la cámara a ella.

- Mapea **todas**. Un proyecto con 6 de 61 viviendas mapeadas parece roto:
  el comprador toca y no pasa nada.
- Las cajas son proporciones del modelo, no metros: si se reemplaza el
  modelo por otro con el mismo encuadre, siguen valiendo.
- Objetivo de la 4.3: cincuenta viviendas en treinta minutos. Si te cuesta
  más, apúntalo en las horas y dilo: es la herramienta la que tiene que
  mejorar, no tu paciencia.

## 8. Comprobar antes de enseñarlo

Con el proyecto todavía en borrador, tú lo ves y la promotora también:

- `python3 tools/medir-visor.py https://real3d.io/projects/<slug>` desde la
  máquina auxiliar: cuánto pesa antes de poder mirar. El objetivo son 4 MB.
- `node tools/recorrido-visor.mjs <url>`: lo abre como un teléfono y dice si
  pinta, si la lista de viviendas aparece, si tocar abre la ficha, si la
  consola está limpia.
- A mano, en un móvil de verdad: girar, pellizcar, tocar tres viviendas,
  pulsar WhatsApp.

## 9. Pedir el visto bueno

Cambia el estado a **Listo para revisar**. La promotora ve en su ficha el
enlace al visor y dos botones: *Aprobar* o *Pedir cambios* (con un texto). Lo
que diga llega al equipo por correo y queda en la cola. Si pide cambios, el
estado vuelve a *En preparación*.

## 10. Darlo por montado

Con el visto bueno, **Dar por montado** en la cola. Eso avisa a la promotora
de que ya puede publicar, y **ahí empieza su prueba gratuita**, no antes: no
lo pulses hasta que esté de verdad.

## Lista de comprobación (la que no se salta)

- [ ] Portada subida y se ve en el listado.
- [ ] Fondo 360 subido y elegido en `background_type`; su versión de 2K existe.
- [ ] Modelo subido; existe su versión Draco (o es FBX y se sabe por qué).
- [ ] Cámara inicial guardada con el edificio entero a la vista.
- [ ] Todas las viviendas mapeadas.
- [ ] Precios y disponibilidad revisados con la promotora.
- [ ] Correo de contacto del proyecto puesto (si no, los leads van a la promotora y a nosotros).
- [ ] `medir-visor.py` por debajo de 4 MB (o anotado por qué no).
- [ ] `recorrido-visor.mjs` sin problemas.
- [ ] Probado en un móvil de verdad.
- [ ] Horas apuntadas.
- [ ] Visto bueno de la promotora.
