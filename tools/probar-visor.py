#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Lo del visor que se puede probar sin navegador, con node y three.

El visor es lo que se vende y lo unico del producto que no pasaba por ningun
test: solo se podia mirar con un navegador y una cuenta. Lo que se ha ido
sacando a modulos puros (elegir una vivienda tocando el modelo) se prueba
aqui con la misma version de three que carga la pagina. Corre solo y desde
php artisan test, como los demas tools/probar-*.py.
"""

import os
import subprocess
import sys
import unittest

AQUI = os.path.dirname(os.path.abspath(__file__))
RAIZ = os.path.dirname(AQUI)


class ElVisorConNode(unittest.TestCase):
    def test_elegir_una_vivienda_tocando_el_modelo(self):
        # three viene con npm ci; sin el no se puede probar, y eso es un fallo
        # y no un salto: un test que se salta solo no protege de nada.
        self.assertTrue(os.path.isdir(os.path.join(RAIZ, "node_modules", "three")),
                        "falta three en node_modules: npm ci")

        r = subprocess.run(["node", os.path.join(AQUI, "probar-visor-eleccion.mjs")],
                           capture_output=True, text=True, cwd=RAIZ, timeout=60)

        self.assertEqual(r.returncode, 0, r.stderr.strip()[-600:] or r.stdout)
        self.assertIn("comprobaciones bien", r.stdout)


if __name__ == "__main__":
    unittest.main(verbosity=2)
