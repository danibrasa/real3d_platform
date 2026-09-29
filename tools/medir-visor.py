#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Cuanto pesa un visor: lo que un comprador tiene que bajar antes de ver algo.

La auditoria del 29-sep-2026 (docs/auditoria-visor-2026-09-29.md) salio de la
consola de Chrome y no se podia repetir. Esto es la parte repetible: lee la
pagina del visor, saca de ella las direcciones del modelo, el fondo 360 y el
video, pregunta a cada una cuanto pesa (HEAD, sin bajarlo) y suma. No mide lo
que el navegador ejecuta -- eso solo lo mide un navegador -- pero mide lo que
mas pesa, que es lo que decide si el visor sirve en un telefono.

Uso: [CLAVE_WEB=...] [PRESUPUESTO_MB=4] medir-visor.py https://real3d.io/projects/salado

Sale con 1 si el total pasa del presupuesto. El presupuesto por defecto es el
objetivo del plan (3.2): menos de 4 MB antes de poder mirar. Hoy no se cumple
en ningun proyecto real; el guion existe para que se note cuando se cumpla y
para que no vuelva a dejar de cumplirse.
"""

import json
import os
import re
import sys

import requests

if len(sys.argv) < 2:
    sys.exit(__doc__)

URL = sys.argv[1]
PRESUPUESTO = float(os.environ.get("PRESUPUESTO_MB", "4"))
CLAVE = os.environ.get("CLAVE_WEB", "")

s = requests.Session()
s.auth = ("real3d", CLAVE) if CLAVE else None
s.headers["User-Agent"] = "Real3D medir-visor"

r = s.get(URL, timeout=60)
if r.status_code != 200:
    sys.exit("el visor devuelve %s" % r.status_code)

base = re.match(r"https?://[^/]+", URL).group(0)
html_kb = len(r.content) / 1024

# Las direcciones de los ficheros del visor estan en la pagina, en el JSON que
# lee viewer-public.js. Se cogen todas las de /api/projects/.../files/.
# La pagina lleva las direcciones dentro de un JSON, con las barras escapadas
# (\/api\/projects\/...): se desescapan antes de buscar. Sin esto el guion no
# encontraba ninguna, sumaba 0,02 MB y decia que el visor cabia en el
# presupuesto. Un medidor que no encuentra nada tiene que decirlo, no aprobar.
texto = r.text.replace("\\/", "/")
ficheros = sorted(set(re.findall(r"/api/projects/[^\"'\s<]+/files/[a-z_0-9]+(?:\?f=[^\"'\s<]*)?", texto)))
del_visor = [f for f in ficheros if re.search(r"/files/(model_3d|image_360|video_360)\b", f)]
if not del_visor:
    sys.exit("no encuentro ni modelo ni fondo 360 en la pagina: o no es un visor, o el guion no mide nada")

# El visor carga UN fondo, video o imagen, segun lo que diga la pagina (es la
# misma regla que viewer-public.js: background_type, y 'video' si no dice).
m = re.search(r'"background_type"\s*:\s*"(video|image)"', texto)
fondo = m.group(1) if m else "video"
descartado = "image_360" if fondo == "video" else "video_360"

total = html_kb / 1024
print("fondo del visor: %s" % fondo)
print("%-52s %10s  %s" % ("recurso", "MB", "cabeceras"))
print("%-52s %10.2f" % ("html", html_kb / 1024))
for f in ficheros:
    h = s.head(base + f, timeout=60, allow_redirects=True)
    tam = int(h.headers.get("Content-Length") or 0) / 1048576
    if descartado in f:
        print("%-52s %10.2f  (no se carga con fondo %s)" % (f[:52], tam, fondo))
        continue
    total += tam
    print("%-52s %10.2f  %s%s%s" % (
        f[:52], tam,
        "cache=" + (h.headers.get("Cache-Control") or "-"),
        " etag" if h.headers.get("ETag") else " sin-etag",
        " " + h.headers["Content-Encoding"] if h.headers.get("Content-Encoding") else "",
    ))

print("-" * 64)
print("%-52s %10.2f  (presupuesto %.1f)" % ("TOTAL antes de poder mirar", total, PRESUPUESTO))

# Lo que no se mide aqui y hay que mirar con un navegador de verdad:
print("no medido: JS de Three.js (~0,4 MB por CDN), memoria, tiempo de pintado")

if total > PRESUPUESTO:
    print("PESA DEMASIADO: %.1f MB, y el objetivo es %.1f" % (total, PRESUPUESTO))
    sys.exit(1)
