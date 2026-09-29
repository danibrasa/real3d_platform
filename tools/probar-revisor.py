#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Que el revisor lea solo lo de la rama, desde donde salio de main.

Con 'main...rama', git diff da lo que la rama cambia desde su base -- bien --
pero git log da lo que tiene uno y no el otro, en los dos sentidos: el revisor
leyo como afirmaciones de una rama de documentacion los commits que main
habia ganado por su cuenta, y la paro por "afirmar" cosas que no estaban en
su diff. Corre solo y desde php artisan test.
"""

import importlib.util
import os
import sys
import unittest

AQUI = os.path.dirname(os.path.abspath(__file__))


def cargar():
    os.environ.setdefault("GH_ANTHROPIC_KEY", "para-el-test")
    argv, sys.argv = sys.argv, ["revisor.py"]
    try:
        spec = importlib.util.spec_from_file_location("revisor_bajo_prueba", os.path.join(AQUI, "revisor.py"))
        modulo = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(modulo)
    finally:
        sys.argv = argv
    return modulo


class ElRango(unittest.TestCase):
    def setUp(self):
        self.rev = cargar()

    def test_tres_puntos_se_convierten_en_base_y_rama(self):
        base = lambda a, b: "abc123" if (a, b) == ("origin/main", "origin/rama") else self.fail("pidio otra base")

        self.assertEqual(self.rev.desde_la_base("origin/main...origin/rama", base), "abc123..origin/rama")

    def test_dos_puntos_se_dejan_como_estan(self):
        nunca = lambda a, b: self.fail("no tenia que pedir la base")

        self.assertEqual(self.rev.desde_la_base("origin/main..HEAD", nunca), "origin/main..HEAD")

    def test_un_commit_suelto_tambien(self):
        nunca = lambda a, b: self.fail("no tenia que pedir la base")

        self.assertEqual(self.rev.desde_la_base("abc123", nunca), "abc123")


if __name__ == "__main__":
    unittest.main(verbosity=2)
