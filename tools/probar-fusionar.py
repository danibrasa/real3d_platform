#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Que la puerta del revisor cierre, y para cada forma de no estar limpia.

Esto existe porque ya fallo una vez. El revisor devolvia "hallazgos graves",
fusionar.py imprimia un aviso y fusionaba igual, y el aviso se perdio por
encima del corte de un tail: la rama entro con dos hallazgos graves que nadie
leyo. El arreglo fue detener la fusion; este fichero es la parte que faltaba,
porque en el commit siguiente escribi "comprobados los cinco desenlaces uno a
uno" y la comprobacion era un guion de usar y tirar fuera del repositorio. Una
comprobacion que no se puede repetir no protege de nada.

Se ejecuta solo (python3 tools/probar-fusionar.py) y tambien desde
php artisan test, via tests/Feature/HerramientasTest.php.
"""

import builtins
import importlib.util
import io
import os
import sys
import types
import unittest

AQUI = os.path.dirname(os.path.abspath(__file__))


def cargar_fusionar():
    """fusionar.py sin tocar red ni git: solo se usa revisar()."""
    os.environ.setdefault("GH_TOKEN", "para-el-test")

    spec = importlib.util.spec_from_file_location("fusionar_bajo_prueba",
                                                  os.path.join(AQUI, "fusionar.py"))
    modulo = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(modulo)
    return modulo


class Salida:
    """Lo que devuelve subprocess.run, con el codigo que interese."""

    def __init__(self, codigo):
        self.returncode = codigo
        self.stdout = "[ALTA] un hallazgo de ejemplo"
        self.stderr = "detalle del fallo"


class ErrorDeCuelgue(Exception):
    pass


class PuertaDelRevisor(unittest.TestCase):
    def setUp(self):
        self.fus = cargar_fusionar()
        self.fus.log = lambda *a: None          # sin ruido en la salida
        self.clave = "sk-de-mentira"
        self._open = builtins.open
        builtins.open = self._abrir

    def tearDown(self):
        builtins.open = self._open

    def _abrir(self, ruta, *a, **k):
        # La clave se lee del .env del clon; aqui se la damos sin fichero.
        if str(ruta).endswith("/.env"):
            if not self.clave:
                raise OSError("no hay .env")
            return io.StringIO("ANTHROPIC_API_KEY=%s\n" % self.clave)
        return self._open(ruta, *a, **k)

    def _con_codigo(self, codigo):
        self.fus.subprocess = types.SimpleNamespace(
            run=lambda *a, **k: Salida(codigo), TimeoutExpired=ErrorDeCuelgue)
        return self.fus.revisar("rama-de-prueba")

    # --- Lo unico que deja fusionar ---------------------------------------

    def test_revision_limpia_deja_seguir(self):
        self.assertEqual(self._con_codigo(0), "limpia")

    # --- Lo que no ---------------------------------------------------------

    def test_hallazgos_graves_detienen(self):
        self.assertEqual(self._con_codigo(1), "hallazgos")

    def test_el_codigo_previsto_de_no_revisable_detiene(self):
        self.assertEqual(self._con_codigo(2), "sin-revision")

    def test_un_codigo_imprevisto_tambien_detiene(self):
        # Un 127 es "no existe el interprete". Tratar solo los codigos
        # previstos dejaba pasar en silencio el caso mas probable de rotura
        # real: que el revisor se rompa.
        self.assertEqual(self._con_codigo(127), "sin-revision")

    def test_sin_clave_no_se_fusiona_a_ciegas(self):
        self.clave = ""
        self.assertEqual(self._con_codigo(0), "sin-revision")

    def test_un_revisor_colgado_detiene(self):
        def revienta(*a, **k):
            raise ErrorDeCuelgue()

        self.fus.subprocess = types.SimpleNamespace(
            run=revienta, TimeoutExpired=ErrorDeCuelgue)

        self.assertEqual(self.fus.revisar("rama-de-prueba"), "sin-revision")

    def test_las_ramas_se_pasan_por_argumento(self):
        # Vivieron dentro del fichero y una lista vieja se colo en un commit
        # que no tenia nada que ver.
        fuente = io.open(os.path.join(AQUI, "fusionar.py"), encoding="utf-8").read()

        self.assertNotIn("RAMAS = [", fuente)
        self.assertIn("ramas = [r for r in sys.argv[1:]", fuente)


class LaDecisionDeFusionar(unittest.TestCase):
    """Lo que se hace con el veredicto, que es la otra mitad de la historia.

    Los tests de arriba comprueban lo que devuelve revisar(). Si la
    comparacion que decide estuviera del reves, el veredicto seria correcto y
    la rama se fusionaria igual, y nada lo diria.
    """

    def setUp(self):
        self.puede = cargar_fusionar().puede_seguir

    def test_solo_una_revision_limpia_pasa_sin_permiso(self):
        self.assertTrue(self.puede("limpia", []))
        self.assertFalse(self.puede("hallazgos", []))
        self.assertFalse(self.puede("sin-revision", []))

    def test_cada_bandera_abre_solo_su_puerta(self):
        # Que --sin-revisor no sirva para colar hallazgos graves, ni al reves:
        # son dos decisiones distintas y quien las toma tiene que decir cual.
        self.assertTrue(self.puede("hallazgos", ["--aunque-haya-hallazgos"]))
        self.assertFalse(self.puede("hallazgos", ["--sin-revisor"]))

        self.assertTrue(self.puede("sin-revision", ["--sin-revisor"]))
        self.assertFalse(self.puede("sin-revision", ["--aunque-haya-hallazgos"]))

    def test_las_dos_juntas_valen_para_las_dos(self):
        ambas = ["--aunque-haya-hallazgos", "--sin-revisor"]

        self.assertTrue(self.puede("hallazgos", ambas))
        self.assertTrue(self.puede("sin-revision", ambas))

    def test_un_veredicto_desconocido_no_pasa_con_nada(self):
        # Seria un fallo de programacion, no una decision de nadie: ninguna
        # bandera debe consentirlo.
        todas = ["--aunque-haya-hallazgos", "--sin-revisor", "--lo-que-sea"]

        self.assertFalse(self.puede("regular", todas))
        self.assertFalse(self.puede("", todas))
        self.assertFalse(self.puede(None, todas))


if __name__ == "__main__":
    unittest.main(verbosity=2)
