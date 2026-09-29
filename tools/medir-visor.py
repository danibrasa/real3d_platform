#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Cuanto pesa un visor: lo que un comprador tiene que bajar antes de ver algo.

La auditoria del 29-sep-2026 (docs/auditoria-visor-2026-09-29.md) salio de la
consola de Chrome y no se podia repetir. Esto es la parte repetible: lee la
pagina del visor, saca de ella las direcciones del modelo y del fondo 360,
pregunta a cada una cuanto pesa (HEAD, sin bajarlo) y suma. No mide lo que el
navegador ejecuta -- eso solo lo mide un navegador -- pero mide lo que mas
pesa, que es lo que decide si el visor sirve en un telefono.

Uso: [CLAVE_WEB=...] [PRESUPUESTO_MB=4] medir-visor.py https://real3d.io/projects/salado

Sale con 1 si el total pasa del presupuesto. El presupuesto por defecto es el
objetivo del plan (3.2): menos de 4 MB antes de poder mirar. Hoy no se cumple
en ningun proyecto real; el guion existe para que se note cuando se cumpla y
para que no vuelva a dejar de cumplirse.

Las dos primeras vueltas de este guion aprobaron mal: una sumaba 0,02 MB
porque la pagina lleva las direcciones en un JSON con las barras escapadas y
no encontraba ninguna; la otra sumaba el video de 315 MB que el visor no
carga. Las dos cosas estan en tools/probar-medir-visor.py para que no vuelvan.
"""

import os
import re
import sys

# Solo lo que el visor carga para pintar. La pagina lleva tambien la
# miniatura y otros /files/ que no pesan en lo que se mide aqui.
DEL_VISOR = ("model_3d", "image_360", "video_360")


def ficheros_del_visor(html):
    """(fondo, direcciones) a partir del HTML del visor.

    La pagina lleva las direcciones dentro de un JSON, con las barras
    escapadas (\\/api\\/projects\\/...): se desescapan antes de buscar. El fondo
    es 'video' o 'image', con la misma regla que viewer-public.js:
    background_type, y 'video' si no dice nada.
    """
    texto = html.replace("\\/", "/")
    patron = r"/api/projects/[^\"'\s<]+/files/(?:%s)(?:\?f=[^\"'\s<]*)?" % "|".join(DEL_VISOR)
    ficheros = sorted(set(re.findall(patron, texto)))

    m = re.search(r'"background_type"\s*:\s*"(video|image)"', texto)
    fondo = m.group(1) if m else "video"

    return fondo, ficheros


def se_carga(fichero, fondo):
    """El visor carga UN fondo, video o imagen; el otro no cuenta."""
    descartado = "image_360" if fondo == "video" else "video_360"
    return descartado not in fichero


def medir(url, sesion, presupuesto_mb, salida=print):
    """Imprime la tabla y devuelve el total en MB. Lanza SystemExit si no hay visor."""
    r = sesion.get(url, timeout=60)
    if r.status_code != 200:
        raise SystemExit("el visor devuelve %s" % r.status_code)

    base = re.match(r"https?://[^/]+", url).group(0)
    fondo, ficheros = ficheros_del_visor(r.text)

    if not ficheros:
        # Un medidor que no encuentra nada tiene que decirlo, no aprobar.
        raise SystemExit("no encuentro ni modelo ni fondo 360 en la pagina: o no es un visor, o el guion no mide nada")

    total = len(r.content) / 1048576
    salida("fondo del visor: %s" % fondo)
    salida("%-52s %10s  %s" % ("recurso", "MB", "cabeceras"))
    salida("%-52s %10.2f" % ("html", total))

    for f in ficheros:
        h = sesion.head(base + f, timeout=60, allow_redirects=True)
        tam = int(h.headers.get("Content-Length") or 0) / 1048576
        if not se_carga(f, fondo):
            salida("%-52s %10.2f  (no se carga con fondo %s)" % (f[:52], tam, fondo))
            continue
        total += tam
        salida("%-52s %10.2f  %s%s%s" % (
            f[:52], tam,
            "cache=" + (h.headers.get("Cache-Control") or "-"),
            " etag" if h.headers.get("ETag") else " sin-etag",
            " " + h.headers["Content-Encoding"] if h.headers.get("Content-Encoding") else "",
        ))

    salida("-" * 64)
    salida("%-52s %10.2f  (presupuesto %.1f)" % ("TOTAL antes de poder mirar", total, presupuesto_mb))
    salida("no medido: JS de Three.js (~0,4 MB por CDN), memoria, tiempo de pintado")
    return total


def main():
    import requests

    if len(sys.argv) < 2:
        sys.exit(__doc__)

    url = sys.argv[1]
    presupuesto = float(os.environ.get("PRESUPUESTO_MB", "4"))
    clave = os.environ.get("CLAVE_WEB", "")

    s = requests.Session()
    s.auth = ("real3d", clave) if clave else None
    s.headers["User-Agent"] = "Real3D medir-visor"

    total = medir(url, s, presupuesto)
    if total > presupuesto:
        print("PESA DEMASIADO: %.1f MB, y el objetivo es %.1f" % (total, presupuesto))
        sys.exit(1)


if __name__ == "__main__":
    main()
