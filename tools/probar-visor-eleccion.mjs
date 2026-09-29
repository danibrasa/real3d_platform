// Que tocar el modelo elija la vivienda que toca, y arrastrar no elija nada.
// Corre con node (three en node_modules, la misma version que carga el visor
// por CDN) desde tools/probar-visor.py, que va con php artisan test.
import assert from 'node:assert/strict';
import * as THREE from 'three';
import { esUnToque, ndcDesde, viviendaBajoElPuntero } from '../public/js/visor-eleccion.js';

function caja(id, x, y, z, tam = 2) {
    const mesh = new THREE.Mesh(new THREE.BoxGeometry(tam, tam, tam), new THREE.MeshBasicMaterial());
    mesh.position.set(x, y, z);
    mesh.visible = false;          // como van en el visor hasta que se eligen
    mesh.updateMatrixWorld(true);
    return [id, mesh];
}

const camera = new THREE.PerspectiveCamera(50, 1, 0.1, 100);
camera.position.set(0, 0, 10);
camera.lookAt(0, 0, 0);
camera.updateMatrixWorld(true);

const cajas = new Map([
    caja(101, 0, 0, 0),        // delante, en el centro
    caja(202, 0, 0, -6),       // detras de la anterior, misma linea
    caja(303, 4, 0, 0),        // a la derecha
]);

// Al centro: la de delante, no la de detras.
assert.equal(viviendaBajoElPuntero(camera, cajas, { x: 0, y: 0 }), 101, 'al centro tenia que salir la de delante');

// Hacia la derecha: la 303. En x=4 a distancia 10 con fov 50 el punto cae a ~0.86 del ancho.
assert.equal(viviendaBajoElPuntero(camera, cajas, { x: 0.86, y: 0 }), 303, 'a la derecha tenia que salir la 303');

// Al cielo: nada.
assert.equal(viviendaBajoElPuntero(camera, cajas, { x: 0, y: 0.95 }), null, 'donde no hay caja no puede salir vivienda');

// Sin cajas: nada, y sin romperse.
assert.equal(viviendaBajoElPuntero(camera, new Map(), { x: 0, y: 0 }), null);

// Toque frente a arrastre.
assert.equal(esUnToque({ x: 100, y: 100, t: 0 }, { x: 103, y: 102, t: 120 }), true, 'tres pixeles en 120 ms es un toque');
assert.equal(esUnToque({ x: 100, y: 100, t: 0 }, { x: 160, y: 100, t: 120 }), false, 'sesenta pixeles es arrastrar');
assert.equal(esUnToque({ x: 100, y: 100, t: 0 }, { x: 101, y: 100, t: 900 }), false, 'casi un segundo apretado no es un toque');
assert.equal(esUnToque(null, { x: 0, y: 0, t: 0 }), false);

// De pantalla a coordenadas normalizadas: esquinas y centro.
const rect = { left: 10, top: 20, width: 200, height: 100 };
assert.deepEqual(ndcDesde(110, 70, rect), { x: 0, y: 0 });
assert.deepEqual(ndcDesde(10, 20, rect), { x: -1, y: 1 });
assert.deepEqual(ndcDesde(210, 120, rect), { x: 1, y: -1 });

console.log('visor-eleccion: 11 comprobaciones bien');
