// Que el mapeo por nombre case lo que debe, no case lo que no, y saque la
// caja bien. Corre con node desde tools/probar-visor.py.
import assert from 'node:assert/strict';
import { casar, fraccionDeCaja, normalizar, unionDeCajas } from '../public/js/visor-mapeo.js';

assert.equal(normalizar(' Unidad B-203 '), 'unidadb203');
assert.equal(normalizar(null), '');

const unidades = [
    { id: 1, identifier: 'A-101' },
    { id: 2, identifier: 'A-102' },
    { id: 3, identifier: 'B-203' },
    { id: 4, identifier: '1' },          // demasiado corto para casar con nada
    { id: 5, identifier: 'PH-1' },
];
const mallas = ['A101', 'Unidad_A-102', 'Bloque_B_B203_muros', 'B203_suelo', 'Cube.001', 'Piscina', 'Torre1', 'PH1'];

const r = casar(mallas, unidades);
assert.deepEqual(r.get(1), ['A101'], 'igualdad normalizada');
assert.deepEqual(r.get(2), ['Unidad_A-102'], 'termina en el identificador');
assert.deepEqual(r.get(3), ['Bloque_B_B203_muros', 'B203_suelo'], 'las dos mallas de la B-203 se juntan');
assert.equal(r.get(4), undefined, 'un identificador de un caracter no casa con nada');
assert.deepEqual(r.get(5), ['PH1']);
assert.equal([...r.values()].flat().includes('Cube.001'), false, 'lo que no se parece a nada queda fuera');
assert.equal([...r.values()].flat().includes('Torre1'), false, '"1" es demasiado corto para reclamar Torre1');

// Dos viviendas que podrian reclamar la misma malla: gana la mas concreta.
const r2 = casar(['Unidad_A-1011'], [{ id: 1, identifier: 'A-101' }, { id: 9, identifier: 'A-1011' }]);
assert.deepEqual([...r2.entries()], [[9, ['Unidad_A-1011']]]);

// Empate exacto: la malla no se asigna.
const r3 = casar(['A101'], [{ id: 1, identifier: 'A-101' }, { id: 2, identifier: 'a.101' }]);
assert.equal(r3.size, 0, 'con empate no se elige a ciegas');

// La union de cajas y su fraccion.
const u = unionDeCajas([
    { min: { x: 0, y: 0, z: 0 }, max: { x: 2, y: 3, z: 1 } },
    { min: { x: 1, y: -1, z: 0 }, max: { x: 4, y: 2, z: 1 } },
]);
assert.deepEqual(u, { min: { x: 0, y: -1, z: 0 }, max: { x: 4, y: 3, z: 1 } });

const limites = { min: { x: -10, y: 0, z: -10 }, size: { x: 20, y: 10, z: 20 } };
const f = fraccionDeCaja({ min: { x: 0, y: 0, z: -10 }, max: { x: 10, y: 5, z: 0 } }, limites);
assert.deepEqual(f, { cx: 0.75, cy: 0.25, cz: 0.25, sx: 0.5, sy: 0.5, sz: 0.5 });

// Fuera de los limites se recorta a 0..1, y una caja plana no da tamaño cero.
const g = fraccionDeCaja({ min: { x: -30, y: 2, z: 0 }, max: { x: -25, y: 2, z: 0 } }, limites);
assert.equal(g.cx, 0);
assert.equal(g.sy, 0.001);

console.log('visor-mapeo: 14 comprobaciones bien');
