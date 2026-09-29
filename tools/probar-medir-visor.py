#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Que el medidor del visor mida lo que el visor carga, y no apruebe en vacio.

Sus dos primeras vueltas aprobaron mal: una no encontraba ninguna direccion
(la pagina las lleva en un JSON con las barras escapadas) y sumo 0,02 MB; la
otra sumo el video de 315 MB que el visor no carga porque el fondo es la
imagen. Cada una tiene aqui su test. Corre solo y desde php artisan test.
"""

import importlib.util
import os
import unittest

AQUI = os.path.dirname(os.path.abspath(__file__))


def cargar():
    spec = importlib.util.spec_from_file_location("medir_visor", os.path.join(AQUI, "medir-visor.py"))
    modulo = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(modulo)
    return modulo


# Un trozo de pagina como la de verdad: JSON con las barras escapadas.
PAGINA = r'''<script>window.PROJECT_DATA = {"settings":{"background_type":"image"},
"files":{"video_360":"\/api\/projects\/1\/files\/video_360","image_360":"\/api\/projects\/1\/files\/image_360",
"model_3d":"\/api\/projects\/1\/files\/model_3d?f=salado.glb","thumbnail":"\/api\/projects\/1\/files\/thumbnail"}};</script>'''

PESOS_MB = {"video_360": 315, "image_360": 20, "model_3d": 35, "thumbnail": 0.7}


class Respuesta:
    def __init__(self, status=200, texto="", tam_mb=0):
        self.status_code = status
        self.text = texto
        self.content = texto.encode()
        self.headers = {"Content-Length": str(int(tam_mb * 1048576)), "Cache-Control": "max-age=3600"}


class SesionDeMentira:
    """Contesta la pagina y un HEAD por fichero con el peso de la tabla."""

    def __init__(self, pagina):
        self.pagina = pagina
        self.pedidos = []

    def get(self, url, timeout=0):
        return Respuesta(200, self.pagina)

    def head(self, url, timeout=0, allow_redirects=True):
        self.pedidos.append(url)
        for tipo, mb in PESOS_MB.items():
            if "/files/" + tipo in url:
                return Respuesta(200, "", mb)
        return Respuesta(404)


class MedirElVisor(unittest.TestCase):
    def setUp(self):
        self.mv = cargar()
        self.lineas = []

    def test_desescapa_las_barras_y_encuentra_los_ficheros(self):
        fondo, ficheros = self.mv.ficheros_del_visor(PAGINA)

        self.assertEqual(fondo, "image")
        self.assertEqual(len(ficheros), 3, "tenia que encontrar modelo, imagen y video")
        self.assertTrue(all(f.startswith("/api/projects/1/files/") for f in ficheros))

    def test_la_miniatura_no_es_del_visor(self):
        _, ficheros = self.mv.ficheros_del_visor(PAGINA)

        self.assertFalse(any("thumbnail" in f for f in ficheros))

    def test_suma_un_solo_fondo_el_que_dice_la_pagina(self):
        total = self.mv.medir("https://x.invalid/projects/p", SesionDeMentira(PAGINA), 4, self.lineas.append)

        # modelo 35 + imagen 20; el video de 315 no, porque el fondo es imagen.
        self.assertAlmostEqual(total, 55, delta=0.1)
        self.assertTrue(any("no se carga con fondo image" in l for l in self.lineas))

    def test_con_fondo_video_es_al_reves(self):
        pagina = PAGINA.replace('"background_type":"image"', '"background_type":"video"')

        total = self.mv.medir("https://x.invalid/projects/p", SesionDeMentira(pagina), 4, self.lineas.append)

        self.assertAlmostEqual(total, 350, delta=0.1)

    def test_sin_decir_fondo_se_supone_video_como_hace_el_visor(self):
        pagina = PAGINA.replace('"background_type":"image"', '"otro":"dato"')

        fondo, _ = self.mv.ficheros_del_visor(pagina)

        self.assertEqual(fondo, "video")

    def test_un_fichero_sin_tamano_para_en_vez_de_contar_cero(self):
        class SinTamano(SesionDeMentira):
            def head(self, url, timeout=0, allow_redirects=True):
                r = super().head(url, timeout, allow_redirects)
                r.headers.pop("Content-Length", None)
                return r

        with self.assertRaises(SystemExit) as salida:
            self.mv.medir("https://x.invalid/projects/p", SinTamano(PAGINA), 4, self.lineas.append)

        self.assertIn("no se puede medir", str(salida.exception))

    def test_sin_ficheros_se_niega_en_vez_de_aprobar(self):
        # Lo que hizo la primera vuelta: 0,02 MB y aprobado.
        with self.assertRaises(SystemExit) as salida:
            self.mv.medir("https://x.invalid/projects", SesionDeMentira("<html>solo una lista</html>"), 4, self.lineas.append)

        self.assertIn("no mide nada", str(salida.exception))


if __name__ == "__main__":
    unittest.main(verbosity=2)
