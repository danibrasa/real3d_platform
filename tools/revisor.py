#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Revisa un cambio sin haber oido las razones de quien lo escribio.

Por que existe: el 28-sep-2026, en una sola sesion, afirme con total seguridad
cuatro cosas que eran falsas -que Dependabot llevaba semanas bloqueado, que el
ruleset no exigia CI, que el importador fallaba, que un test cubria algo que no
cubria- y las cuatro las cazo otra persona o la casualidad. El codigo lo escribo
yo casi entero; a ese ritmo, eso no escala.

La idea no es que un modelo revise mejor que yo. Es que este no ha oido mi
razonamiento: recibe el diff y el mensaje del commit, que es exactamente la
afirmacion que hay que comprobar, y nada mas. No se le puede convencer con una
historia que nunca ha escuchado.

Busca a proposito mis fallos, no los del producto en general:

  1. Lo que el mensaje afirma y el diff no sostiene.
  2. Tests que pasarian igual con el fallo presente.
  3. Caminos que devuelven exito sin hacer lo que dicen.
  4. Fallos tragados: excepciones capturadas y olvidadas, `?? valor`,
     resultados que nadie mira.

Uso: GH_ANTHROPIC_KEY=... revisor.py [rango]   (por defecto origin/main..HEAD)

Codigos de salida:
  0  revisado, sin hallazgos graves
  1  revisado, con hallazgos graves
  2  NO se pudo revisar

El 2 existe porque el propio revisor se encontro este fallo a si mismo: devolvia
0 tanto si no habia hallazgos como si la respuesta no se podia interpretar. Es
decir, quien lo usara veria "todo bien" justo cuando la revision no se hizo, que
es exactamente la clase de fallo que esta herramienta busca.
"""

import json
import os
import subprocess
import sys
import urllib.error
import urllib.request

RANGO = sys.argv[1] if len(sys.argv) > 1 else "origin/main..HEAD"
CLON = os.environ.get("CLON", "/var/www/dev")
CLAVE = os.environ.get("GH_ANTHROPIC_KEY", "").strip()
MODELO = os.environ.get("REVISOR_MODELO", "claude-sonnet-5")

# Un diff enorme se revisa mal y cuesta caro. Por encima de esto se avisa y se
# revisa igual, pero sabiendo que la lectura sera superficial.
MAX_CARACTERES = 120_000

INSTRUCCIONES = """\
Eres un revisor de codigo adversarial. NO conoces el razonamiento de quien
escribio esto: solo tienes el mensaje del commit y el diff. El mensaje es la
AFIRMACION que hay que poner a prueba, no un contexto de confianza.

Quien escribe este codigo es un agente de IA que trabaja rapido y cuyo fallo
caracteristico no es escribir codigo roto, sino afirmar de mas: dar por
comprobado lo que no comprobo, y escribir tests que pasan con el fallo presente.

Busca, por este orden:

1. AFIRMACIONES SIN RESPALDO. Cosas que el mensaje del commit da por hechas o
   verificadas y que el diff no sostiene. Si dice "comprobado que X", ¿se ve en
   el diff que X se comprueba?
2. TESTS VACIOS. Tests que pasarian igual si el fallo que dicen cubrir siguiera
   ahi. Muy tipico: probar solo el caso negativo (que alguien NO puede hacer
   algo) cuando el fallo era que NADIE podia hacerlo.
3. EXITO FALSO. Caminos que devuelven 200, `true` o siguen adelante sin haber
   hecho lo que dicen: validaciones que no se aplican, campos que se ignoran por
   no estar en fillable, correos que se encolan pero no se pueden construir.
4. FALLOS TRAGADOS. try/catch que no registran nada, `?? valor por defecto` que
   esconde un dato ausente, resultados de funciones que nadie mira.

NO comentes estilo, nombres, ni preferencias. NO elogies. Si algo esta bien, no
lo menciones.

Responde SOLO con JSON, sin texto alrededor:

{"hallazgos": [
  {"gravedad": "alta|media|baja",
   "fichero": "ruta/del/fichero.php",
   "que": "que esta mal, en una frase",
   "porque": "por que importa, concreto",
   "como_comprobarlo": "que haria yo para confirmarlo en un minuto"}
]}

Si no encuentras nada que merezca la pena, devuelve {"hallazgos": []}. Es una
respuesta perfectamente valida: inventarse hallazgos para parecer util es el
mismo fallo que se intenta cazar.
"""


def git(*args):
    return subprocess.run(
        ["git", "-C", CLON] + list(args), capture_output=True, text=True, check=True
    ).stdout


def preguntar(texto):
    peticion = urllib.request.Request(
        "https://api.anthropic.com/v1/messages",
        method="POST",
        data=json.dumps({
            "model": MODELO,
            # Amplio a proposito: el modelo razona antes de responder y ese
            # razonamiento consume del mismo presupuesto. Con 4000 se gastaba
            # entero pensando y devolvia un bloque de pensamiento sin respuesta,
            # que ademas parecia "no interpretable" en vez de "te has quedado
            # sin sitio". Y con 16000 se quedo dos veces seguidas sin sitio
            # ante un diff de 16 KB, que no es grande: el presupuesto va con
            # el tamano del diff, no con el de la respuesta.
            "max_tokens": 32000,
            "system": INSTRUCCIONES,
            "messages": [{"role": "user", "content": texto}],
        }).encode(),
        headers={
            "x-api-key": CLAVE,
            "anthropic-version": "2023-06-01",
            "content-type": "application/json",
        },
    )

    with urllib.request.urlopen(peticion, timeout=300) as r:
        respuesta = json.loads(r.read().decode())

    # No siempre viene el texto en el primer bloque: el modelo razona antes, y
    # ese razonamiento llega como un bloque aparte.
    for b in respuesta.get("content", []):
        if b.get("type") == "text":
            return b.get("text", "")

    if respuesta.get("stop_reason") == "max_tokens":
        raise RuntimeError(
            "el modelo se quedo sin tokens antes de contestar; "
            "sube max_tokens o parte el diff en trozos"
        )

    return ""


def desde_la_base(rango, base_comun):
    """El rango que se le da a git log y a git diff, con la misma base.

    'main...rama' significa para git diff "desde donde la rama salio de main",
    que es lo que hay que revisar; pero para git log significa "lo que tiene
    uno y no el otro", en los dos sentidos, y el revisor leyo como afirmaciones
    de la rama los commits que main habia ganado por su cuenta. Se resuelve la
    base una vez y se usa 'base..rama' para las dos cosas.
    """
    if "..." not in rango:
        return rango

    a, b = rango.split("...", 1)
    return "%s..%s" % (base_comun(a, b), b)


def main():
    if not CLAVE:
        print("NO SE PUDO REVISAR: falta GH_ANTHROPIC_KEY")
        return 2

    # Si git falla -rango inventado, clon que no esta- la excepcion sin
    # capturar termina el proceso con codigo 1, que aqui significa "revisado,
    # con hallazgos graves". Es la misma confusion que se acaba de arreglar en
    # la otra punta del guion, y la encontro el propio revisor.
    try:
        rango = desde_la_base(RANGO, lambda a, b: git("merge-base", a, b).strip())
        mensajes = git("log", "--format=%B%n---", rango).strip()
        diff = git("diff", rango)
    except subprocess.CalledProcessError as e:
        print("NO SE PUDO REVISAR: git fallo en %s\n%s" % (RANGO, e.stderr.strip()[:300]))
        return 2
    except OSError as e:
        print("NO SE PUDO REVISAR: no se pudo ejecutar git: %s" % e)
        return 2

    if not diff.strip():
        print("no hay nada que revisar en %s" % RANGO)
        return 0

    recortado = len(diff) > MAX_CARACTERES
    if recortado:
        diff = diff[:MAX_CARACTERES]

    print("revisando %s (%d KB de diff%s)"
          % (RANGO, len(diff) // 1024, ", recortado" if recortado else ""), flush=True)

    try:
        respuesta = preguntar(
            "MENSAJE DEL COMMIT (la afirmacion a comprobar):\n\n%s\n\n"
            "DIFF:\n\n%s" % (mensajes, diff)
        )
    except Exception as e:
        # Tampoco aqui vale callarse: sin revision, quien llama tiene que
        # enterarse de que no la hubo.
        print("NO SE PUDO REVISAR: %s: %s" % (type(e).__name__, e))
        return 2

    # Puede llegar envuelto en markdown por muy claras que sean las instrucciones.
    inicio, fin = respuesta.find("{"), respuesta.rfind("}")
    if inicio == -1:
        print("NO SE PUDO REVISAR: la respuesta no traia JSON\n" + respuesta[:500])
        return 2

    try:
        hallazgos = json.loads(respuesta[inicio:fin + 1]).get("hallazgos", [])
    except ValueError:
        print("NO SE PUDO REVISAR: el JSON no se puede interpretar\n" + respuesta[:500])
        return 2

    if not hallazgos:
        print("\nsin hallazgos")
        return 0

    orden = {"alta": 0, "media": 1, "baja": 2}
    hallazgos.sort(key=lambda h: orden.get(h.get("gravedad"), 3))

    print()
    for h in hallazgos:
        print("[%s] %s" % (h.get("gravedad", "?").upper(), h.get("fichero", "?")))
        print("   %s" % h.get("que", ""))
        print("   por que: %s" % h.get("porque", ""))
        print("   comprobar: %s" % h.get("como_comprobarlo", ""))
        print()

    graves = [h for h in hallazgos if h.get("gravedad") == "alta"]
    print("%d hallazgo(s), %d grave(s)" % (len(hallazgos), len(graves)))

    # Codigo 1 solo con hallazgos graves: que un revisor pare la cadena por una
    # observacion menor acaba en que se ignore siempre.
    return 1 if graves else 0


if __name__ == "__main__":
    sys.exit(main())
