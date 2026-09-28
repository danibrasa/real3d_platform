#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Recoge obra nueva publicada en RD: quien construye, que, y como localizarle.

Para que sirve
--------------
Dos cosas a la vez, porque se visita la misma pagina:

  1. ANALISIS. Responder con datos preguntas de producto que ahora mismo
     contestamos por intuicion: cuantas viviendas tiene un proyecto tipico
     (Starter permite 20), en que precios se mueve (de ahi sale si 149 $/mes
     es caro), que zonas concentran la obra nueva, y cuantos tienen ya visor
     3D, que es el hueco de verdad y no el imaginado.

  2. LISTA PARA DESPUES. Con quien hablar cuando el producto este listo, con
     su telefono o su web ya recogidos, para no tener que volver a rastrear
     todo ese dia.

De aqui NO SALE NINGUN ENVIO
---------------------------
Este guion lee y escribe un JSON. No manda correos, ni formularios, ni
mensajes: no sabe hacerlo y no debe aprender. La instruccion es explicita y
es de producto, no de escrupulo: primero la aplicacion armada, testeada y
optima; escribirle a una promotora con un producto a medias se gasta el
unico primer contacto que hay con ella.

Cada fila guarda la URL de donde salio. Nada de esto vale como dato hasta
que se abre ese enlace.

Educacion con el servidor ajeno: se comprueba robots.txt, se va de una
peticion por segundo y se dice quien llama. Estas paginas son publicas y
estan para que las lean, pero eso no es excusa para castigarlas.

Uso: prospectos.py [--salida fichero.json] [--paginas N] [--sin-fichas]
"""

import argparse
import json
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from html import unescape

AGENTE = "Real3D-analisis-de-mercado (contacto: hola@real3d.io)"
ESPERA = 1.0

FUENTES = [
    {
        "nombre": "portalinmobiliariord",
        "listado": "https://portalinmobiliariord.com/proyectos/",
        "paginado": "https://portalinmobiliariord.com/proyectos/page/%d/",
    },
]

# El marcado de las fichas, leido del HTML de verdad y no supuesto.
#
# Se recorta primero la tarjeta entera y despues se saca cada campo de dentro.
# Intentarlo de una pasada con grupos opcionales y `.*?` no funciona: el
# perezoso se come el contenido y los opcionales casan vacio, asi que salian
# veintiun proyectos con todos los campos en blanco... y el informe concluia
# tan tranquilo que "ninguno anuncia visor 3D".
TARJETA = re.compile(
    r'<a href="(?P<url>[^"]+)" class="prv-project-card">(?P<cuerpo>.*?)</a>', re.S)

CAMPOS = {
    "nombre": re.compile(r'class="prv-project-name">([^<]*)<'),
    "zona": re.compile(r'class="prv-project-loc">([^<]*)<'),
    "tipo": re.compile(r'class="prv-project-type">([^<]*)<'),
    "estado": re.compile(r'class="prv-project-badge">([^<]*)<'),
    "precio": re.compile(r'class="prv-project-price">([^<]*)<'),
    "promotora": re.compile(r'class="prv-project-dev">([^<]*)<'),
}

# Lo que delata que ya tienen visor.
#
# Dos veces se ha medido mal esto. Primero buscandolo en la tarjeta del
# listado, que lleva nombre, zona y precio: salia "0 de 21", que no medía el
# mercado sino lo cortas que son las tarjetas. Despues en la ficha pero con
# un "360" pelado en el patron, que casa con el width="360" del logo del
# portal: salia "21 de 21". Cien por cien es tan improbable como cero.
#
# Asi que el 360 solo cuenta acompanado, y se mira el cuerpo de la pagina y
# no el <head> ni los <script>.
#
# Y tercera correccion: "vista 360" no es un visor. En inmobiliaria dominicana
# significa panoramica -- "sky bar con vista 360", "vistas 360 y acceso a la
# zona gastronomica"-- que es justo lo contrario de lo que buscamos: presume
# de lo que se ve DESDE el edificio, no de poder verlo por dentro. Asi que el
# 360 solo cuenta con un verbo de recorrer delante.
SEÑALES_3D = re.compile(
    r"tour\s+virtual|recorrido\s+virtual|matterport|visor\s*3d|render\s*3d|"
    r"realidad\s+virtual|virtual\s+tour|walkthrough|maqueta\s+interactiva|"
    r"(?:tour|recorrido|paseo|visita)\w*\s*(?:de\s*)?360",
    re.I)

# La prueba fuerte, que no depende de como lo llamen: el visor incrustado.
# Una promotora puede tener tour y no nombrarlo en el texto, pero el iframe
# esta o no esta.
INCRUSTADO = re.compile(
    r"matterport|kuula|pannellum|momento360|sketchfab|krpano|theasys|"
    r"eyespy360|cupix|marzipano|roundme|klapty", re.I)

# Un iframe que no sea video ni mapa: candidato a visor aunque no lo parezca.
IFRAME = re.compile(r"<iframe[^>]+src=\"([^\"]+)\"", re.I)
NO_ES_VISOR = re.compile(r"youtube|youtu\.be|vimeo|google\.com/maps|"
                         r"facebook|instagram|recaptcha|doubleclick", re.I)

# Lo que no es contenido: ahi dentro no se busca ni visor ni contacto.
DECORADO = re.compile(r"<script.*?</script>|<style.*?</style>|<head.*?</head>",
                      re.I | re.S)

# Como localizar a la promotora el dia que toque. Son datos que ella misma
# publica en su ficha precisamente para que la llamen.
CONTACTO = {
    "email": re.compile(r"[\w.+-]+@[\w-]+\.[\w.-]+"),
    "telefono": re.compile(r"(?:\+?1[\s.-]?)?\(?8[024]9\)?[\s.-]?\d{3}[\s.-]?\d{4}"),
    "whatsapp": re.compile(r"(?:wa\.me|api\.whatsapp\.com)[^\"'\s<>]*"),
    "web": re.compile(r'href="(https?://(?!portalinmobiliariord\.com)'
                      r'[^"]*?)"[^>]*>(?:[^<]*(?:sitio|web|www)[^<]*)<', re.I),
}

# Correos del propio portal: son suyos, no de la promotora.
NO_ES_CONTACTO = re.compile(r"@(?:portalinmobiliariord|sentry|example|wixpress)",
                            re.I)


def pedir(url):
    peticion = urllib.request.Request(url, headers={"User-Agent": AGENTE})
    with urllib.request.urlopen(peticion, timeout=40) as r:
        return r.read().decode("utf-8", errors="replace")


def permitido(url):
    """Lo que diga robots.txt, no lo que me convenga."""
    partes = urllib.parse.urlparse(url)
    try:
        robots = pedir("%s://%s/robots.txt" % (partes.scheme, partes.netloc))
    except Exception:
        # Sin robots.txt legible no se asume permiso: se asume que no.
        return False

    # Reglas del agente generico, que es el que nos aplica.
    reglas = []
    for bloque in re.split(r"(?im)^user-agent:", robots):
        if bloque.strip().lower().startswith("*"):
            reglas += re.findall(r"(?im)^disallow:\s*(\S+)", bloque)

    return not any(partes.path.startswith(r) for r in reglas if r != "/")


def limpiar(texto):
    return re.sub(r"\s+", " ", unescape(texto or "")).strip().strip("📍").strip()


def a_numero(texto):
    """El precio "Desde USD 115,000" en numero, o None si no se entiende."""
    if not texto:
        return None
    m = re.search(r"([\d][\d.,]{2,})", texto.replace(" ", ""))
    if not m:
        return None
    # En estas paginas la coma es separador de miles y el punto tambien.
    solo = re.sub(r"[.,]", "", m.group(1))
    return int(solo) if solo.isdigit() else None


def mirar_ficha(html):
    """Lo que solo esta en la pagina del proyecto: si hay visor, y el contacto."""
    cuerpo = DECORADO.sub(" ", html)
    señal = SEÑALES_3D.search(cuerpo)

    # El incrustado se busca en el HTML entero: un visor puede cargarse
    # desde un <script>, que es justo lo que DECORADO acaba de tirar.
    marca = INCRUSTADO.search(html)
    ajenos = [u for u in IFRAME.findall(html) if not NO_ES_VISOR.search(u)]

    contacto = {}
    for clave, patron in CONTACTO.items():
        vistos = []
        for m in patron.finditer(cuerpo):
            v = unescape(m.group(1) if patron.groups else m.group(0)).strip()
            if clave == "email" and NO_ES_CONTACTO.search(v):
                continue
            if v not in vistos:
                vistos.append(v)
        contacto[clave] = vistos[:4] or None

    return {
        "anuncia_3d": bool(señal),
        "señal_3d": señal.group(0) if señal else None,
        # Lo dicho y lo incrustado son dos preguntas distintas: una promotora
        # puede tener visor sin nombrarlo, o nombrarlo sin tenerlo.
        "visor_incrustado": bool(marca) or bool(ajenos),
        "marca_visor": marca.group(0) if marca else None,
        "iframes_ajenos": ajenos[:3] or None,
        "contacto": contacto,
    }


def normalizar(clave, valor):
    """Para comparar: de un wa.me solo el numero, de un telefono solo digitos."""
    if clave in ("telefono", "whatsapp"):
        return re.sub(r"\D", "", valor)[-10:]
    return valor.lower().rstrip("/")


def quitar_lo_que_sale_en_todas(filas, umbral=0.5):
    """Un telefono que aparece en las 21 fichas es del portal, no de nadie.

    Es la regla que habria cazado sola los tres errores de la primera vuelta:
    el mismo 809-608-4271 en todas, el mismo tailwindcss@2.2.19 en todas, y
    el mismo width=360 en todas. Lo repetido es decorado; lo propio de una
    ficha es lo que solo sale en ella.

    No se puede aplicar con dos fichas: con pocas, cualquier coincidencia
    parece decorado. Por debajo de diez se deja el dato crudo y se dice.
    """
    mirados = [f for f in filas if f.get("contacto") is not None]
    if len(mirados) < 10:
        for f in mirados:
            f["contacto_filtrado"] = False
            f["contacto_hay"] = any(f["contacto"].values())
        return

    tope = max(2, int(len(mirados) * umbral))

    for clave in CONTACTO:
        cuantas = {}
        for f in mirados:
            for v in (f["contacto"].get(clave) or []):
                # El texto prerrellenado de un wa.me lleva el nombre del
                # proyecto, asi que se compara el numero y no el enlace.
                n = normalizar(clave, v)
                cuantas[n] = cuantas.get(n, 0) + 1

        comunes = {v for v, n in cuantas.items() if n > tope}
        for f in mirados:
            propios = [v for v in (f["contacto"].get(clave) or [])
                       if normalizar(clave, v) not in comunes]
            f["contacto"][clave] = propios or None

    for f in mirados:
        f["contacto_filtrado"] = True
        f["contacto_hay"] = any(f["contacto"].values())


def recoger(fuente, paginas):
    encontrados = []

    for n in range(1, paginas + 1):
        url = fuente["listado"] if n == 1 else fuente["paginado"] % n

        try:
            html = pedir(url)
        except urllib.error.HTTPError as e:
            if e.code == 404:
                break  # Se acabaron las paginas.
            print("  %s -> HTTP %s" % (url, e.code), file=sys.stderr)
            break
        except Exception as e:
            print("  %s -> %s" % (url, e), file=sys.stderr)
            break

        tarjetas = list(TARJETA.finditer(html))
        if not tarjetas:
            break

        for t in tarjetas:
            cuerpo = t.group("cuerpo")

            def campo(c, _cuerpo=cuerpo):
                m = CAMPOS[c].search(_cuerpo)
                return m.group(1) if m else None

            encontrados.append({
                "fuente": fuente["nombre"],
                "origen": url,
                "ficha": t.group("url"),
                "nombre": limpiar(campo("nombre")),
                "promotora": limpiar(campo("promotora")) or None,
                "zona": limpiar(campo("zona")) or None,
                "tipo": limpiar(campo("tipo")) or None,
                "estado": limpiar(campo("estado")) or None,
                "precio_texto": limpiar(campo("precio")) or None,
                "precio_desde_usd": a_numero(campo("precio")),
                # Se rellenan visitando la ficha; None significa "no mirado",
                # que no es lo mismo que "no tiene".
                "anuncia_3d": None,
                "contacto": None,
            })

        print("  pagina %d: %d proyectos" % (n, len(tarjetas)), flush=True)
        time.sleep(ESPERA)

    return encontrados


def main():
    p = argparse.ArgumentParser()
    p.add_argument("--salida", default="/tmp/prospectos.json")
    p.add_argument("--paginas", type=int, default=5)
    p.add_argument("--sin-fichas", action="store_true",
                   help="solo el listado: ni visor 3D ni contacto")
    args = p.parse_args()

    todos = []
    for fuente in FUENTES:
        print("== %s" % fuente["nombre"])

        if not permitido(fuente["listado"]):
            print("  robots.txt no lo permite: se salta", file=sys.stderr)
            continue

        todos += recoger(fuente, args.paginas)

    # Un mismo proyecto puede salir en dos sitios.
    unicos = {}
    for fila in todos:
        unicos.setdefault(fila["ficha"], fila)
    filas = list(unicos.values())

    # La ficha de cada proyecto, una peticion por proyecto: es donde esta lo
    # unico que de verdad mide el hueco (si ya tienen visor) y lo unico que
    # servira para hablar con ellos cuando toque.
    if not args.sin_fichas:
        print("\n== fichas (%d)" % len(filas))
        for i, fila in enumerate(filas, 1):
            try:
                fila.update(mirar_ficha(pedir(fila["ficha"])))
            except Exception as e:
                # Sin poder leerla no se dice ni que si ni que no.
                fila["no_se_pudo_leer"] = str(e)
            if i % 5 == 0 or i == len(filas):
                print("  %d/%d" % (i, len(filas)), flush=True)
            time.sleep(ESPERA)

        quitar_lo_que_sale_en_todas(filas)

    with open(args.salida, "w", encoding="utf-8") as f:
        json.dump(filas, f, ensure_ascii=False, indent=2)

    print("\n%d proyectos en %s" % (len(filas), args.salida))
    print("De aqui no sale ningun envio: primero la aplicacion.")
    return 0 if filas else 1


if __name__ == "__main__":
    sys.exit(main())
