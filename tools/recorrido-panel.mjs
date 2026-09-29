#!/usr/bin/env node
// El panel abierto desde un telefono, como lo abre una promotora que recibe
// el aviso de un lead en el coche.
//
// Entra con la cuenta que se le da, recorre las pantallas que una promotora
// usa a diario (la bandeja de consultas, un lead, la tabla de viviendas) a
// 390 px de ancho y mira lo que no puede mirar un test de PHP: que la pagina
// no desborde de lado, que lo importante se vea sin hacer zoom, que los
// botones se puedan tocar con el dedo y que la consola este limpia.
//
// Uso: CORREO=... CLAVE=... [CLAVE_WEB=...] [CAPTURAS=dir] [ANCHO=390] node tools/recorrido-panel.mjs https://dev.real3d.io [/admin/projects/3/units ...]
// Sin rutas, recorre la bandeja y el primer lead. Sale con 1 si hay problemas.
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';
import { join } from 'node:path';

const base = (process.argv[2] || '').replace(/\/$/, '');
const rutas = process.argv.slice(3);
const correo = process.env.CORREO || '';
const clave = process.env.CLAVE || '';
const claveWeb = process.env.CLAVE_WEB || '';
const capturas = process.env.CAPTURAS || '';
if (!base || !correo || !clave) {
    console.error('uso: CORREO=... CLAVE=... node tools/recorrido-panel.mjs <base> [rutas...]');
    process.exit(2);
}
if (capturas) mkdirSync(capturas, { recursive: true });

const notas = [];
const problemas = [];
const errores = [];
// Un telefono salvo que se pida otro ancho (para mirar la misma pantalla de escritorio).
const ANCHO = Number(process.env.ANCHO || 390);
const telefono = ANCHO < 600;

const navegador = await chromium.launch({ args: ['--no-sandbox'] });
try {
    const contexto = await navegador.newContext({
        httpCredentials: claveWeb ? { username: 'real3d', password: claveWeb } : undefined,
        viewport: { width: ANCHO, height: telefono ? 844 : 900 },
        deviceScaleFactor: telefono ? 2 : 1,
        isMobile: telefono,
        hasTouch: telefono,
        userAgent: telefono
            ? 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1 Real3D-recorrido'
            : undefined,
    });
    const pagina = await contexto.newPage();
    pagina.on('pageerror', (e) => errores.push(String(e.message || e)));
    pagina.on('console', (m) => { if (m.type() === 'error') errores.push(m.text()); });

    // Entrar.
    await pagina.goto(base + '/login', { waitUntil: 'domcontentloaded', timeout: 60000 });
    await pagina.fill('input[name="email"]', correo);
    await pagina.fill('input[name="password"]', clave);
    await Promise.all([pagina.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 }), pagina.click('button[type="submit"]')]);
    if (pagina.url().includes('/login')) {
        problemas.push('no se pudo entrar con la cuenta dada');
        throw new Error('sin sesion');
    }
    notas.push('entra y llega a ' + pagina.url().replace(base, ''));

    // Lo que se mira en cada pantalla.
    async function mirar(ruta, nombre, comprobar) {
        const r = await pagina.goto(base + ruta, { waitUntil: 'domcontentloaded', timeout: 60000 });
        const estado = r?.status();
        if (estado !== 200) {
            problemas.push(`${nombre}: devuelve ${estado}`);
            return;
        }
        await pagina.waitForTimeout(300);

        const medidas = await pagina.evaluate(() => {
            const d = document.documentElement;
            // Los controles que un dedo tiene que acertar: un boton, un select
            // o una casilla de menos de 36 px de alto es cosa de raton. Los
            // enlaces de texto no se cuentan: en una tabla los hay a docenas.
            const pequenos = [...document.querySelectorAll('button, select, input:not([type=hidden])')]
                .filter((e) => e.offsetParent !== null)
                .map((e) => ({ r: e.getBoundingClientRect(), t: (e.textContent || e.getAttribute('aria-label') || e.name || '').trim().slice(0, 30) }))
                .filter(({ r }) => r.width > 0 && r.height > 0 && r.height < 36)
                .map(({ t }) => t);
            return { scrollWidth: d.scrollWidth, ancho: window.innerWidth, pequenos: pequenos.slice(0, 8), total: pequenos.length };
        });
        if (medidas.scrollWidth > medidas.ancho + 2) {
            problemas.push(`${nombre}: desborda de lado (${medidas.scrollWidth} px en una pantalla de ${medidas.ancho})`);
        } else {
            notas.push(`${nombre}: cabe en ${medidas.ancho} px`);
        }
        if (medidas.total && telefono) {
            problemas.push(`${nombre}: ${medidas.total} controles de menos de 36 px de alto, que un dedo no acierta (${medidas.pequenos.join(' | ')})`);
        } else if (medidas.total) {
            notas.push(`${nombre}: ${medidas.total} controles de menos de 36 px de alto (${medidas.pequenos.join(' | ')})`);
        }

        if (comprobar) await comprobar(nombre);

        if (capturas) {
            await pagina.screenshot({ path: join(capturas, nombre.replace(/[^a-z0-9]+/gi, '-') + '-' + ANCHO + '.png'), fullPage: false });
        }
    }

    // Visible de verdad: con caja, dentro de la pantalla y sin nada encima.
    // isVisible() da por visible lo recortado por un overflow, que era justo
    // el fallo de la bandeja.
    async function visible(nombre, selector, que) {
        const el = await pagina.$(selector);
        if (!el) { problemas.push(`${nombre}: no esta ${que}`); return false; }
        const estado = await el.evaluate((e) => {
            const r = e.getBoundingClientRect();
            if (!r.width || !r.height) return 'sin caja';
            if (r.left < 0 || r.right > window.innerWidth + 1) return `recortado (${Math.round(r.left)}..${Math.round(r.right)} px en ${window.innerWidth})`;
            const centro = document.elementFromPoint(r.left + r.width / 2, Math.min(r.top + r.height / 2, window.innerHeight - 1));
            if (r.top < window.innerHeight && centro && !e.contains(centro) && !centro.contains(e)) return 'tapado por ' + centro.tagName.toLowerCase();
            return 'ok';
        });
        if (estado !== 'ok') problemas.push(`${nombre}: ${que} ${estado}`);
        return estado === 'ok';
    }

    if (rutas.length) {
        for (const ruta of rutas) await mirar(ruta, ruta.replace(/^\//, ''), null);
    } else {
        await mirar('/admin/inquiries', 'bandeja', async (n) => {
            const enlace = await pagina.$('a[href*="/admin/inquiries/"]');
            if (!enlace) { notas.push('bandeja: sin consultas que abrir'); return; }
            // El filtro de arriba tambien se llama "estado": el del lead es
            // el que va en su formulario.
            await visible(n, 'form[action*="/estado"] select[name="estado"]', 'el estado del lead');
            // Con telefono, el WhatsApp tiene que estar: el correo solo no vale.
            const hayTelefono = await pagina.$('a[href^="tel:"]');
            await visible(n, hayTelefono ? 'a[href^="https://wa.me/"]' : 'a[href^="mailto:"]', hayTelefono ? 'el WhatsApp' : 'el correo');
        });
        const enlace = await pagina.$('a[href*="/admin/inquiries/"]');
        const destino = enlace ? await enlace.getAttribute('href') : null;
        if (destino) {
            await mirar(destino.replace(base, ''), 'lead', async (n) => {
                await visible(n, 'form[action*="/estado"] select[name="estado"]', 'el cambio de estado');
                const hayTelefono = await pagina.$('a[href^="tel:"]');
                await visible(n, hayTelefono ? 'a[href^="https://wa.me/"]' : 'a[href^="mailto:"]', hayTelefono ? 'el WhatsApp' : 'el correo');
            });
        }
    }
} catch (e) {
    if (!problemas.length) problemas.push('el recorrido se rompio: ' + (e.message || e));
} finally {
    await navegador.close();
}

for (const n of notas) console.log(n);
const ajenos = errores.filter((e) => !/analytics|gtag|googletagmanager|favicon/i.test(e));
if (ajenos.length) problemas.push('la consola tiene errores: ' + ajenos.slice(0, 3).join(' | '));
for (const p of problemas) console.log('PROBLEMA: ' + p);
process.exit(problemas.length ? 1 : 0);
