# Auditoría del visor — 29 de septiembre de 2026

Tarea 3.1 del plan hacia el PMV. Medido en producción (real3d.io, v1.4.0) con un
proyecto real: **Salado Golf And Beach** (`/projects/salado`, 61 viviendas, modelo
GLB + foto 360), desde Chrome en escritorio con conexión de fibra. Lo que se mide
aquí es lo que se vende; era la parte menos comprobada de todo el producto.

**Límite de esta medida:** no se pudo emular un móvil de verdad (la ventana no
bajó de 982 px). Lo de móvil que hay abajo sale de leer el código y del
escritorio; la medida en un teléfono real y en 4G queda pendiente y es lo
primero de la 3.2.

## Los números

### El visor (`/projects/salado`)

| Qué | Medido |
|---|---|
| Bytes para verse | **55,7 MB** (16 peticiones) |
| Modelo 3D (`model_3d`) | **35,6 MB**, GLB sin comprimir (el cargador admite Draco; el fichero no lo usa) |
| Foto 360 (`image_360`) | **21,0 MB**, JPEG de 8K, una sola calidad |
| Three.js y cargadores (CDN jsdelivr) | 0,39 MB |
| HTML | 7 KB · TTFB 39 ms · DOMContentLoaded 342 ms · load 1,1 s |
| Memoria JS tras cargar | 147 MB |
| Errores de consola | ninguno |
| Cabeceras del modelo y el 360 | `Cache-Control: max-age=3600`, sin `ETag`, sin compresión, servidos **a través de PHP** (`response()->file`), no por nginx |

En fibra, el modelo tarda 1,2 s y el 360 0,8 s. **En 4G (≈ 10 Mbit/s reales) esos
mismos 55 MB son ~45 segundos** antes de que se vea nada más que la barra de
progreso. No hay nada que ver mientras tanto: la página es negra con la barra.

### La ficha pública (`/projects/salado/info`)

| Qué | Medido |
|---|---|
| Bytes | **53,3 MB** (88 peticiones): la ficha carga también el modelo y el 360 para su vista 3D |
| LCP (lo más grande pintado) | **7,4 s** |
| Galería | 46 imágenes en el DOM; las 8 mayores entre **0,9 y 2,8 MB cada una** (PNG de render sin redimensionar) |
| Imágenes sin `alt` | 14 de 46 |
| Formulario de consulta, WhatsApp, chatbot, mapa | están y funcionan |

### Lo que hay en el disco de producción

| Proyecto | Ficheros del visor |
|---|---|
| 1 salado (público) | vídeo 360 **315 MB** + modelo 35 MB + 360 20 MB |
| 2, 3, 5 (ocultos) | **el mismo vídeo de 315 MB**, tres veces más; modelos de 15–35 MB |
| 4 salado-iii (oculto) | modelo **69 MB** |

El vídeo 360 no se usa en `salado` (el proyecto tiene el fondo en modo imagen),
pero cuando un proyecto lo usa, son 315 MB por visita.

## Las cinco cosas peores, por orden

1. **Las viviendas no se pueden tocar en el 3D.** El visor no tiene *raycaster*
   ni mapeo de mallas: la única forma de llegar a una vivienda es la lista de
   tarjetas de debajo. El panel de detalle (precio, WhatsApp, consultar, ficha,
   compartir) funciona bien una vez abierto, pero "toca la vivienda que te
   interesa" —que es lo que promete un visor 3D— hoy no existe. La herramienta
   de mapeo del panel de administración existe; el visor público no la usa.

2. **55 MB para ver algo.** Modelo sin Draco (35 MB; comprimido serían 5–8),
   360 de 8K en una sola calidad (21 MB; a 4K son ~5), y nada progresivo: no se
   ve el 360 mientras baja el modelo, no hay imagen de espera con el render.
   Esto decide solo si el visor sirve en un teléfono en RD.

3. **La ficha pública pesa lo mismo que el visor y pinta a los 7,4 s.** Carga el
   modelo y el 360 enteros para una vista previa, y una galería de PNG de
   render sin redimensionar (hasta 2,8 MB la imagen). Es la página que llega
   por WhatsApp y por Google: el comprador la abre en el móvil.

4. **Sin WebGL no hay nada.** El código detecta la calidad del GPU pero no tiene
   camino para "este navegador no puede": ni mensaje, ni ficha con renders en su
   lugar. Y las instrucciones son de ratón ("Click izq + arrastrar · Scroll =
   zoom · Click der") también en pantallas táctiles.

5. **Los ficheros grandes salen por PHP.** Cada visita del modelo y del 360 pasa
   por un proceso php-fpm que lee 35 MB y los escribe; con `max-age=3600` y sin
   `ETag`, el navegador los vuelve a pedir al cabo de una hora. nginx debería
   servirlos directo (`X-Accel-Redirect`), con caché larga e inmutable por
   versión de fichero. Y el mismo vídeo de 315 MB está copiado en tres
   proyectos: no hay deduplicación ni límite razonable al subir.

## Lo que está bien y conviene no romper

- Sin errores de consola, carga en 1,1 s en fibra, TTFB de 39 ms.
- El detalle de vivienda tiene los cuatro botones que importan.
- La ficha tiene formulario, WhatsApp, chatbot y mapa.
- Hay detección de calidad por GPU (alta/media/baja) con sombras y *pixel ratio*
  ajustados, y una barra de progreso real (`lengthComputable`).
- Los recursos de Three.js van por CDN y pesan poco.

## Lo que esto cambia en el plan

- **3.2 (presupuesto de rendimiento)** pasa a ser lo primero de la fase 3, con
  objetivo medible: interactivo en menos de 3 s en 4G, es decir **menos de 4 MB
  antes de poder mirar** (360 a 2K primero, modelo Draco después, render de
  espera mientras tanto). Y la nocturna midiendo el peso servido por proyecto.
- **3.3 (vivienda desde el visor)** es más grande de lo estimado: no es
  "mejorar los gestos", es construir el *picking* de viviendas sobre el mapeo
  que ya existe en el panel. Sube de M a L.
- **3.4 (sin WebGL)** sigue en S: la ficha ya sirve de respaldo, falta el desvío.
- Entra una tarea nueva, **3.7 servir los ficheros por nginx** (S): X-Accel,
  caché inmutable por versión, y tope de tamaño al montar (un vídeo 360 de más
  de 100 MB no debería aceptarse sin recomprimir).
- Entra en la **fase 4** una regla de montaje: todo modelo se pasa por Draco y
  toda 360 se genera en 2K/4K/8K antes de publicar. Es trabajo del equipo, no
  de la promotora, y por eso va en "nuestra cocina".

## Cómo se ha medido

`performance.getEntriesByType('resource')` y `navigation` desde la consola de
Chrome, cabeceras con `fetch(HEAD)`, LCP con `PerformanceObserver`, y
`find`/`du` en el disco de producción. Los datos de proyectos y ficheros salen
de la base de producción, solo lectura.
