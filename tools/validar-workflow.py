#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Comprueba el YAML y la sintaxis bash de los pasos `run` de un workflow."""
import re
import subprocess
import sys

import yaml

ruta = sys.argv[1]
d = yaml.safe_load(open(ruta, encoding="utf-8"))
print("YAML OK, jobs: %s" % list(d["jobs"].keys()))

fallos = 0
for nombre, job in d["jobs"].items():
    for paso in job.get("steps", []):
        guion = paso.get("run")
        if not guion:
            continue

        # Las expresiones de GitHub no son bash: se sustituyen por un valor
        # cualquiera para poder pasarle el analizador de sintaxis.
        limpio = re.sub(r"\$\{\{[^}]*\}\}", "valor", guion)

        r = subprocess.run(["bash", "-n"], input=limpio,
                           capture_output=True, text=True)
        estado = "OK" if r.returncode == 0 else "MAL"
        print("  %-14s %-42s %s" % (nombre, (paso.get("name") or "")[:42], estado))
        if r.returncode != 0:
            print(r.stderr)
            fallos += 1

sys.exit(1 if fallos else 0)
