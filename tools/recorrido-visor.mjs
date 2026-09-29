#!/usr/bin/env node
// El visor abierto con un navegador de verdad, como lo abre un comprador.
//
// Todo lo demas del recorrido nocturno pregunta al servidor; esto pregunta
// al navegador: si el cargador desaparece, si el modelo llega a cargarse,
// si la lista de viviendas aparece y tocar una abre su ficha, si en un
// telefono la pagina no desborda, y si la consola esta limpia. Es lo unico
// que puede ver un JavaScript roto en el visor, que es lo que se vende.
//
// Uso: [CLAVE_WEB=...] node tools/recorrido-visor.mjs https://dev.real3d.io/projects/<slug>
// Necesita Playwright con Chromium: npx playwright install --with-deps chromium
// Sale con 1 si hay problemas, y los lista.
import { chromium } from 'playwright';

const url = process.argv[2];
if (!url) {
    console.error('uso: recorrido-visor.mjs <url del visor>');
    process.exit(2);
}

const clave = process.env.CLAVE_WEB || '';
const notas = [];
const problemas = [];
const errores = [];
let bytes = 0;

// La nocturna corre como root, que es quien instalo los navegadores de
// Playwright (/root/.cache/ms-playwright); Chromium como root necesita
// --no-sandbox, y en una maquina sin escritorio no hay nada que aislar.
const navegador = await chromium.launch({ args: ['--no-sandbox'] });
try {
    // Un telefono: es donde lo abre el comprador, y donde mas cosas fallan.
    const contexto = await navegador.newContext({
        httpCredentials: clave ? { username: 'real3d', password: clave } : undefined,
        viewport: { width: 390, height: 844 },
        deviceScaleFactor: 2,
        isMobile: true,
        hasTouch: true,
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1 Real3D-recorrido',
    });
    const pagina = await contexto.newPage();
    pagina.on('pageerror', (e) => errores.push(String(e.message || e)));
    pagina.on('console', (m) => { if (m.type() === 'error') errores.push(m.text()); });
    pagina.on('response', (r) => { const n = Number(r.headers()['content-length'] || 0); if (n > 0) bytes += n; });

    const t0 = Date.now();
    const respuesta = await pagina.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
    notas.push(`la pagina devuelve ${respuesta?.status()}`);
    if (!respuesta || respuesta.status() !== 200) problemas.push(`el visor devuelve ${respuesta?.status()}`);

    // Primero que el cargador exista: sin el, la pagina no es el visor (o el
    // marcado cambio), y "desaparecio" no puede darse por bueno.
    const hayCargador = await pagina.$('#loading-overlay');
    if (!hayCargador) problemas.push('no hay cargador (#loading-overlay): esta pagina no es el visor, o cambio el marcado');

    const sinCargador = hayCargador && await pagina.waitForFunction(() => {
        const o = document.getElementById('loading-overlay');
        if (!o) return false;
        const e = getComputedStyle(o);
        return e.display === 'none' || e.opacity === '0';
    }, null, { timeout: 90000 }).then(() => true).catch(() => false);
    const ms = Date.now() - t0;
    if (sinCargador) notas.push(`el cargador desaparece a los ${ms} ms`);
    else if (hayCargador) problemas.push('el cargador no desaparece en 90 s: el visor no llega a pintar');

    // Los datos del proyecto, tal como los lee el visor. Sin ellos no se
    // puede saber si hay modelo, y eso es un problema, no un "no hay".
    const datos = await pagina.evaluate(() => {
        try { return JSON.parse(document.getElementById('project-data').textContent); } catch (e) { return null; }
    });
    if (!datos || typeof datos.files !== 'object') problemas.push('no se pueden leer los datos del proyecto (#project-data con files): el visor no tiene con que cargar');
    const tieneModelo = !!datos?.files?.model_3d;
    const listo = await pagina.evaluate(() => window.viewerAPI?.isReady?.() === true);
    notas.push(`modelo en la pagina: ${tieneModelo ? 'si' : 'no'}; cargado: ${listo ? 'si' : 'no'}`);
    if (tieneModelo && !listo) problemas.push('hay modelo y no llego a cargarse');

    if (!(await pagina.$('canvas'))) problemas.push('no hay lienzo: WebGL no arranco');

    const desborda = await pagina.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    notas.push(`en un telefono ${desborda ? 'DESBORDA a lo ancho' : 'no desborda'}`);
    if (desborda) problemas.push('la pagina desborda a lo ancho en un telefono');

    const tactil = await pagina.evaluate(() => {
        const e = document.querySelector('.solo-tactil');
        return e ? getComputedStyle(e).display !== 'none' : false;
    });
    notas.push(`instrucciones para el dedo: ${tactil ? 'si' : 'NO'}`);
    if (!tactil) problemas.push('en un telefono se ven las instrucciones de raton, o ninguna');

    const hayLista = await pagina.waitForSelector('#units-grid .unit-card', { timeout: 30000 }).then(() => true).catch(() => false);
    const viviendas = hayLista ? await pagina.$$eval('#units-grid .unit-card', (c) => c.length) : 0;
    notas.push(`viviendas en la lista: ${viviendas}`);
    if (!hayLista) problemas.push('la lista de viviendas no aparece');

    if (hayLista) {
        await pagina.locator('#units-grid .unit-card').first().tap();
        const ficha = await pagina.waitForSelector('#unit-detail-panel', { state: 'visible', timeout: 10000 }).then(() => true).catch(() => false);
        notas.push(`tocar una vivienda de la lista ${ficha ? 'abre su ficha' : 'NO abre su ficha'}`);
        if (!ficha) problemas.push('tocar una vivienda de la lista no abre su ficha');
    }

    // Tocar el modelo no puede romper nada, tenga o no viviendas mapeadas.
    const lienzo = await pagina.$('canvas');
    if (lienzo) {
        const caja = await lienzo.boundingBox();
        if (caja) {
            await pagina.touchscreen.tap(caja.x + caja.width / 2, caja.y + caja.height / 2);
            await pagina.waitForTimeout(500);
            notas.push('tocar el modelo no rompe nada');
        }
    }

    notas.push(`bajado: ${(bytes / 1048576).toFixed(1)} MB`);
    if (errores.length) problemas.push(`errores en consola: ${errores.slice(0, 3).join(' | ').slice(0, 400)}`);
} catch (e) {
    problemas.push(`el navegador se rompio: ${String(e.message || e).slice(0, 300)}`);
} finally {
    await navegador.close();
}

for (const n of notas) console.log(n);
for (const p of problemas) console.log('PROBLEMA: ' + p);
process.exit(problemas.length ? 1 : 0);
