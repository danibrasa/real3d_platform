/**
 * Elegir una vivienda tocando el modelo.
 *
 * El visor tenia las cajas de cada vivienda (invisibles, salvo la elegida)
 * y sabia ir a una desde la lista; lo que no habia era el camino contrario,
 * que es el que promete un visor 3D: tocar la vivienda que te interesa.
 *
 * Esta aparte de viewer-public.js y sin tocar el documento para poder
 * probarse con node (tools/probar-visor-eleccion.mjs): un rayo, unas cajas,
 * unos eventos de puntero, y que salga la vivienda que toca.
 */
import * as THREE from 'three';

/**
 * Un toque es apretar y soltar casi en el mismo sitio y en poco tiempo.
 * Lo demas es arrastrar para girar, y no debe elegir nada.
 */
export function esUnToque(inicio, fin, maxPx = 8, maxMs = 500) {
    if (!inicio || !fin) return false;
    const dx = fin.x - inicio.x;
    const dy = fin.y - inicio.y;
    return Math.hypot(dx, dy) <= maxPx && (fin.t - inicio.t) <= maxMs;
}

/** Coordenadas normalizadas (-1..1) de un punto de pantalla dentro del lienzo. */
export function ndcDesde(x, y, rect) {
    return {
        x: ((x - rect.left) / rect.width) * 2 - 1,
        y: -((y - rect.top) / rect.height) * 2 + 1,
    };
}

/**
 * El id de la vivienda cuya caja esta bajo el puntero, o null.
 *
 * Se lanza el rayo caja a caja y no con intersectObjects: las cajas van
 * invisibles hasta que se eligen, y aqui se quiere acertar tambien en las
 * invisibles. Con varias, la mas cercana a la camara. `cajas` es un Map de
 * id de vivienda a malla, que es como las guarda el visor.
 */
export function viviendaBajoElPuntero(camera, cajas, ndc, raycaster = new THREE.Raycaster()) {
    raycaster.setFromCamera(new THREE.Vector2(ndc.x, ndc.y), camera);

    let mejor = null;
    for (const [unitId, mesh] of cajas) {
        const toques = [];
        mesh.raycast(raycaster, toques);
        if (toques.length && (!mejor || toques[0].distance < mejor.distance)) {
            mejor = { unitId, distance: toques[0].distance };
        }
    }

    return mejor ? mejor.unitId : null;
}

/**
 * Engancha el lienzo: un toque sobre una vivienda llama a elegir(id); con
 * raton, la mano encima de una vivienda. `camara()` y `cajas()` se piden en
 * cada evento porque el visor las crea despues de engancharse.
 *
 * Devuelve lo que desengancha. `ahora` se puede dar para las pruebas.
 */
export function conectarToques(lienzo, camara, cajas, elegir, { conRaton = true, ahora = () => performance.now() } = {}) {
    let inicio = null;
    let ultimoMove = 0;

    const bajoElPuntero = (ev) => viviendaBajoElPuntero(camara(), cajas(), ndcDesde(ev.clientX, ev.clientY, lienzo.getBoundingClientRect()));

    const alApretar = (ev) => { inicio = { x: ev.clientX, y: ev.clientY, t: ahora() }; };
    const alSoltar = (ev) => {
        const fin = { x: ev.clientX, y: ev.clientY, t: ahora() };
        const toque = esUnToque(inicio, fin);
        inicio = null;
        if (!toque) return;
        const unitId = bajoElPuntero(ev);
        if (unitId !== null) elegir(unitId);
    };
    const alMover = (ev) => {
        const t = ahora();
        if (t - ultimoMove < 80) return;
        ultimoMove = t;
        lienzo.style.cursor = bajoElPuntero(ev) !== null ? 'pointer' : '';
    };

    lienzo.addEventListener('pointerdown', alApretar);
    lienzo.addEventListener('pointerup', alSoltar);
    if (conRaton) lienzo.addEventListener('pointermove', alMover);

    return () => {
        lienzo.removeEventListener('pointerdown', alApretar);
        lienzo.removeEventListener('pointerup', alSoltar);
        lienzo.removeEventListener('pointermove', alMover);
    };
}
