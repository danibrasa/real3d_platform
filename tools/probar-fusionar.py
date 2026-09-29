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

    def test_el_revisor_mira_desde_donde_la_rama_salio_de_main(self):
        # Con dos puntos git compara las dos puntas, y una rama que se quedo
        # atras de main "borra" lo que main gano despues: el revisor paro una
        # rama de documentacion por borrar una funcion que no tocaba.
        llamadas = []

        def run(*a, **k):
            llamadas.append(a[0])
            return Salida(0)

        self.fus.subprocess = types.SimpleNamespace(run=run, TimeoutExpired=ErrorDeCuelgue)
        self.fus.revisar("rama-de-prueba")

        rango = [x for x in llamadas[-1] if "origin/" in x][0]
        self.assertEqual(rango, "origin/main...origin/rama-de-prueba")

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


class ElBucleDeVerdad(unittest.TestCase):
    """main() conducido entero, con git y GitHub de mentira.

    Porque probar puede_seguir() por separado sigue dejando fuera el cable:
    con la condicion del bucle invertida, o pasandole las ramas donde van las
    banderas, la funcion seguiria siendo correcta y la rama se fusionaria
    igual. Aqui se mira lo unico que importa de verdad, que es si se llamo a
    GitHub para fusionar.

    Para comprobar que estos tests sirven, en fusionar.py:

        if not puede_seguir(veredicto, ramas):     -> 1 error
        if puede_seguir(veredicto, banderas):      -> 2 fallos y 2 errores

    Las dos son las que propuso el revisor cuando dijo que probar la funcion
    suelta no cubria el cable. Tenia razon, y quedan cazadas.
    """

    def preparar(self, veredicto, argv):
        fus = cargar_fusionar()
        fus.log = lambda *a: None
        fus.git = lambda *a: "sin git"
        fus.revisar = lambda rama: veredicto
        fus.pr_de = lambda rama: {"number": 7, "head": {"sha": "abc1234"}}
        fus.estado_fusion = lambda n, intentos=15: {
            "head": {"sha": "abc1234"}, "mergeable_state": "clean"}
        fus.esperar_ci = lambda sha: "success"     # si no, espera de verdad
        fus.time = types.SimpleNamespace(sleep=lambda s: None)

        # La rama existe en origin.
        fus.subprocess = types.SimpleNamespace(
            run=lambda *a, **k: Salida(0), TimeoutExpired=ErrorDeCuelgue)

        self.llamadas = []

        def api(ruta, metodo="GET", cuerpo=None):
            self.llamadas.append((metodo, ruta))
            return 200, {"sha": "fus1234"}

        fus.api = api
        fus.sys = types.SimpleNamespace(argv=["fusionar.py"] + argv, exit=sys.exit)

        return fus

    def fusiono(self):
        return any("/merge" in ruta for _, ruta in self.llamadas)

    def test_con_hallazgos_no_se_llama_a_github_para_fusionar(self):
        fus = self.preparar("hallazgos", ["rama-x"])

        with self.assertRaises(SystemExit) as salida:
            fus.main()

        self.assertFalse(self.fusiono(), "se fusiono una rama con hallazgos graves")
        self.assertEqual(salida.exception.code, 1,
                         "termino en 0: quien lo lance en un guion no se entera")

    def test_sin_revision_tampoco(self):
        fus = self.preparar("sin-revision", ["rama-x"])

        with self.assertRaises(SystemExit):
            fus.main()

        self.assertFalse(self.fusiono())

    def test_con_la_bandera_si_se_fusiona(self):
        fus = self.preparar("hallazgos", ["rama-x", "--aunque-haya-hallazgos"])
        fus.main()

        self.assertTrue(self.fusiono(),
                        "con el permiso dado a mano tampoco se fusiono")

    def test_una_revision_limpia_fusiona_sin_banderas(self):
        fus = self.preparar("limpia", ["rama-x"])
        fus.main()

        self.assertTrue(self.fusiono(), "una rama limpia no llego a fusionarse")

    # --- Una pila no se salta la puerta -----------------------------------
    #
    # El 29-sep-2026 el revisor detuvo la primera rama de una pila de seis con
    # un hallazgo grave, y la tercera -que llevaba dentro a la primera- salio
    # limpia y se fusiono. El hallazgo entro en main sin que nadie lo leyera.

    def preparar_pila(self, veredictos, contenidas):
        fus = self.preparar("limpia", list(veredictos))
        fus.revisar = lambda rama: veredictos[rama]
        fus.contiene = lambda rama, otra: (rama, otra) in contenidas
        return fus

    def test_una_rama_apilada_sobre_una_detenida_no_se_fusiona(self):
        fus = self.preparar_pila({"a": "hallazgos", "b": "limpia"}, {("b", "a")})

        with self.assertRaises(SystemExit) as salida:
            fus.main()

        self.assertFalse(self.fusiono(), "b se fusiono llevando dentro a la a detenida")
        self.assertNotEqual(salida.exception.code, 0)

    def test_una_rama_independiente_sigue_adelante(self):
        # Lo contrario importa igual: parar toda la tanda por una rama que
        # no tiene nada que ver seria otra forma de no fusionar nunca.
        fus = self.preparar_pila({"a": "hallazgos", "b": "limpia"}, set())

        with self.assertRaises(SystemExit):
            fus.main()          # termina en 1 por la a, pero despues de la b

        self.assertTrue(self.fusiono(), "la b, independiente y limpia, no se fusiono")


if __name__ == "__main__":
    unittest.main(verbosity=2)
