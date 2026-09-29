/**
 * Mapear viviendas por el nombre de las mallas del modelo.
 *
 * Situar 61 viviendas a mano, una a una con seis deslizadores, es una tarde.
 * Si quien hizo el modelo puso nombre a cada vivienda -- "A-101",
 * "Unidad_B203" -- se puede hacer solo: se casa cada malla con la vivienda
 * de identificador parecido y su caja sale de la geometria. Sin tocar el
 * documento, para probarse con node (tools/probar-visor-mapeo.mjs).
 */

/** "Unidad B-203 " y "unidadb203" son lo mismo: solo letras y numeros, en minusculas. */
export function normalizar(texto) {
    return String(texto ?? '').toLowerCase().replace(/[^a-z0-9]/g, '');
}

/**
 * Que mallas corresponden a que vivienda.
 *
 * Casa cuando el nombre normalizado de la malla contiene el identificador
 * sin que lo toque otro digito por ningun lado: Unidad_A-101, A101_suelo y
 * Bloque_B_A101_muros son A-101; Unidad_A-1011 no. Varias mallas de la
 * misma vivienda (muros, suelo) se juntan. El identificador tiene que tener
 * al menos tres caracteres: con "1" o "2" casaria con todo. Si dos viviendas
 * pudieran reclamar la misma malla, gana la mas larga (la mas concreta); si
 * empatan, la malla no se asigna: mejor sin mapear que mal.
 *
 * @param {string[]} nombresDeMallas
 * @param {{id: number, identifier: string}[]} unidades
 * @returns {Map<number, string[]>} id de vivienda -> nombres de malla
 */
export function casar(nombresDeMallas, unidades) {
    const candidatas = unidades
        .map((u) => ({ id: u.id, clave: normalizar(u.identifier) }))
        .filter((u) => u.clave.length >= 3);

    const resultado = new Map();
    for (const nombre of nombresDeMallas) {
        const n = normalizar(nombre);
        if (!n) continue;

        const casan = candidatas.filter((u) => {
            const i = n.indexOf(u.clave);
            if (i < 0) return false;
            const antes = i > 0 ? n[i - 1] : '';
            const despues = n[i + u.clave.length] ?? '';
            return !/[0-9]/.test(antes) && !/[0-9]/.test(despues);
        });
        if (!casan.length) continue;

        const masLarga = Math.max(...casan.map((u) => u.clave.length));
        const mejores = casan.filter((u) => u.clave.length === masLarga);
        if (mejores.length !== 1) continue;

        const lista = resultado.get(mejores[0].id) ?? [];
        lista.push(nombre);
        resultado.set(mejores[0].id, lista);
    }

    return resultado;
}

/** La caja que envuelve a varias: minimos de minimos y maximos de maximos. */
export function unionDeCajas(cajas) {
    const u = { min: { x: Infinity, y: Infinity, z: Infinity }, max: { x: -Infinity, y: -Infinity, z: -Infinity } };
    for (const c of cajas) {
        for (const eje of ['x', 'y', 'z']) {
            u.min[eje] = Math.min(u.min[eje], c.min[eje]);
            u.max[eje] = Math.max(u.max[eje], c.max[eje]);
        }
    }
    return u;
}

/**
 * Una caja en coordenadas del mundo, como fracciones de los limites del
 * modelo (0..1), que es como las guarda la vivienda: centro y tamaño. Si se
 * reemplaza el modelo por otro con el mismo encuadre, siguen valiendo.
 */
export function fraccionDeCaja(caja, limites) {
    const f = {};
    const ejes = { x: 'x', y: 'y', z: 'z' };
    for (const eje of Object.keys(ejes)) {
        const tam = limites.size[eje] || 1;
        const centro = (caja.min[eje] + caja.max[eje]) / 2;
        f['c' + eje] = Math.min(1, Math.max(0, (centro - limites.min[eje]) / tam));
        f['s' + eje] = Math.min(1, Math.max(0.001, (caja.max[eje] - caja.min[eje]) / tam));
    }
    return { cx: f.cx, cy: f.cy, cz: f.cz, sx: f.sx, sy: f.sy, sz: f.sz };
}
