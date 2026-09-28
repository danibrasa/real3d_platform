#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Recorrido de alta de una promotora nueva, cronometrado.

La idea es no hacer trampas. El script no sabe que campos espera el servidor:
lee el formulario HTML de cada pagina y rellena lo que ve, como haria una
persona. Si el servidor rechaza algo, o si un campo obligatorio no aparece en el
formulario, eso es fricción y sale en el informe en vez de arreglarse por detras.

Uso: CLAVE_WEB=<pass http> onboarding.py https://dev.real3d.io
"""

import io
import os
import re
import sys
import time
from html.parser import HTMLParser

import requests

BASE = (sys.argv[1] if len(sys.argv) > 1 else "https://dev.real3d.io").rstrip("/")
CLAVE_WEB = os.environ.get("CLAVE_WEB", "")
SELLO = time.strftime("%H%M%S")

s = requests.Session()
s.auth = ("real3d", CLAVE_WEB) if CLAVE_WEB else None
s.headers["User-Agent"] = "Real3D onboarding (recorrido de alta)"

pasos = []
inicio_global = time.time()


class Formularios(HTMLParser):
    """Saca los formularios de una pagina: accion, metodo y campos."""

    def __init__(self):
        super().__init__()
        self.forms = []
        self._actual = None

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == "form":
            self._actual = {
                "action": a.get("action", ""),
                "method": (a.get("method") or "get").lower(),
                "campos": {},
                "requeridos": [],
                "tipos": {},
                "opciones": {},
            }
        elif self._actual is not None and tag in ("input", "select", "textarea"):
            nombre = a.get("name")
            if not nombre:
                return
            self._actual["campos"][nombre] = a.get("value", "")
            self._actual["tipos"][nombre] = a.get("type", tag)
            if "required" in a:
                self._actual["requeridos"].append(nombre)
            if tag == "select":
                self._actual["opciones"][nombre] = []
                self._select = nombre
        elif self._actual is not None and tag == "option":
            sel = getattr(self, "_select", None)
            if sel and a.get("value"):
                self._actual["opciones"].setdefault(sel, []).append(a["value"])
                # La opcion marcada es la que enviaria el navegador.
                if "selected" in a:
                    self._actual["campos"][sel] = a["value"]

    def handle_endtag(self, tag):
        if tag == "form" and self._actual is not None:
            self.forms.append(self._actual)
            self._actual = None


def formularios(html):
    p = Formularios()
    p.feed(html)
    return p.forms


def elegir(forms, pista=""):
    """El formulario cuya accion contenga la pista, o el primero con campos."""
    for f in forms:
        if pista and pista in f["action"]:
            return f
    utiles = [f for f in forms if len(f["campos"]) > 1]
    return utiles[0] if utiles else (forms[0] if forms else None)


def paso(nombre):
    def deco(fn):
        def envuelta(*a, **kw):
            t = time.time()
            notas = []
            try:
                resultado = fn(notas, *a, **kw)
                ok = True
            except Exception as e:
                resultado, ok = None, False
                notas.append("ROTO: %s: %s" % (type(e).__name__, e))
            dur = time.time() - t
            pasos.append((nombre, dur, ok, notas))
            print("  %-42s %5.1fs  %s" % (nombre, dur, "ok" if ok else "FALLO"), flush=True)
            for n in notas:
                print("       - %s" % n, flush=True)
            return resultado
        return envuelta
    return deco


def errores(html):
    """Los mensajes de error que se le enseñan al usuario."""
    txt = re.sub(r"<[^>]+>", " ", html)
    fuera = []
    # Ojo con aflojar esto: "required" e "invalid" sueltos salen en atributos
    # HTML y en clases CSS de cualquier pagina, y convertian cada recorrido en
    # una falsa alarma. Solo frases que un humano leeria como un error.
    for m in re.finditer(
        r"(El campo [a-z_ ]{2,30} es obligatorio"
        r"|The [a-z_ ]{2,30} field is required"
        r"|no es v[aá]lido"
        r"|is not a valid"
        r"|ya ha sido registrado"
        r"|has already been taken)[^.]{0,80}", txt, re.I):
        t = " ".join(m.group(0).split())
        if t not in fuera:
            fuera.append(t)
    return fuera[:5]


# ---------------------------------------------------------------- el recorrido

@paso("1. Encontrar donde darse de alta")
def buscar_alta(notas):
    r = s.get(BASE + "/", timeout=30)
    if "register/business" not in r.text:
        notas.append("la portada NO enlaza a /register/business: hay que saber la URL")
    r = s.get(BASE + "/register/business", timeout=30)
    if r.status_code != 200:
        raise RuntimeError("la pagina de alta devuelve %s" % r.status_code)
    return r.text


@paso("2. Crear la cuenta")
def registrarse(notas, html):
    f = elegir(formularios(html), "register/business")
    if not f:
        raise RuntimeError("no hay formulario de registro en la pagina")

    notas.append("campos del formulario: %s" % ", ".join(
        k for k in f["campos"] if k != "_token"))

    datos = dict(f["campos"])
    datos.update({
        "name": "Ana Promotora",
        "email": "ana.%s@recorrido-automatico.invalid" % SELLO,
        "password": "UnaClaveLarga2026!",
        "password_confirmation": "UnaClaveLarga2026!",
    })
    faltan = [k for k in datos if datos[k] == "" and k != "_token"]
    if faltan:
        notas.append("campos que el script no supo rellenar: %s" % ", ".join(faltan))

    r = s.post(BASE + "/register/business", data=datos, timeout=30)
    if r.status_code >= 400 or errores(r.text):
        notas.append("respuesta %s. %s" % (r.status_code, "; ".join(errores(r.text))))
        raise RuntimeError("el alta fue rechazada")
    notas.append("acaba en: %s" % r.url.replace(BASE, ""))
    return r


@paso("3. Rellenar la ficha de empresa")
def ficha_empresa(notas, _):
    r = s.get(BASE + "/onboarding/company", timeout=30)
    f = elegir(formularios(r.text), "onboarding/company")
    if not f:
        raise RuntimeError("no hay formulario de empresa")

    notas.append("campos: %s" % ", ".join(k for k in f["campos"] if k != "_token"))
    if f["requeridos"]:
        notas.append("marcados obligatorios en el HTML: %s" % ", ".join(f["requeridos"]))

    datos = dict(f["campos"])
    datos.update({
        "company_name": "Recorrido automatico %s" % SELLO,
        "phone": "+1 809 555 0100",
        "country": "DO",
        "city": "Punta Cana",
    })

    r = s.post(BASE + "/onboarding/company", data=datos, timeout=30)
    if errores(r.text):
        notas.append("rechazado: %s" % "; ".join(errores(r.text)))
        raise RuntimeError("la ficha de empresa fue rechazada")
    notas.append("acaba en: %s" % r.url.replace(BASE, ""))


@paso("4. Elegir plan")
def elegir_plan(notas, _):
    r = s.get(BASE + "/onboarding/plan", timeout=30)
    forms = formularios(r.text)
    f = elegir(forms, "onboarding/plan")
    if not f:
        raise RuntimeError("no hay formulario de plan")

    if f["opciones"]:
        notas.append("opciones: %s" % f["opciones"])

    datos = dict(f["campos"])
    if "plan" in datos and not datos["plan"]:
        datos["plan"] = "starter"
    # Alpine rellena `interval` en el navegador; un script sin JS no, asi que se
    # pone aqui lo que pondria Alpine. Ya no hace falta desde que la plantilla
    # lleva value="monthly" de respaldo, pero se deja por si se mira otra rama.
    if not datos.get("interval"):
        datos["interval"] = "monthly"

    r = s.post(BASE + "/onboarding/plan", data=datos, timeout=30)
    notas.append("acaba en: %s" % r.url.replace(BASE, ""))
    if "stripe" in r.url or "checkout" in r.url:
        notas.append("OJO: el plan basico manda a pagar; no se puede probar sin tarjeta")
        raise RuntimeError("bloqueado en la pasarela de pago")


@paso("5. Llegar al panel")
def panel(notas, _):
    r = s.get(BASE + "/admin", timeout=30)
    if r.status_code != 200:
        raise RuntimeError("el panel devuelve %s (acaba en %s)"
                           % (r.status_code, r.url.replace(BASE, "")))
    if "proyecto" not in r.text.lower() and "project" not in r.text.lower():
        notas.append("el panel no menciona proyectos: no guia sobre que hacer ahora")


@paso("6. Crear el primer proyecto")
def crear_proyecto(notas, _):
    r = s.get(BASE + "/admin/projects/create", timeout=30)
    if r.status_code != 200:
        raise RuntimeError("la pagina de crear proyecto devuelve %s" % r.status_code)

    f = elegir(formularios(r.text), "admin/projects")
    datos = dict(f["campos"])
    datos.update({
        "name": "Recorrido automatico %s" % SELLO,
        "description": "Proyecto de prueba del recorrido de alta.",
        "location": "Bavaro, Punta Cana",
    })
    notas.append("campos: %s" % ", ".join(k for k in f["campos"] if k != "_token"))

    r = s.post(BASE + "/admin/projects", data=datos, timeout=60)
    if errores(r.text):
        notas.append("rechazado: %s" % "; ".join(errores(r.text)))

    m = re.search(r"/admin/projects/(\d+)", r.url)
    if not m:
        r2 = s.get(BASE + "/admin/projects", timeout=30)
        ids = re.findall(r"/admin/projects/(\d+)", r2.text)
        if not ids:
            raise RuntimeError("no encuentro el proyecto recien creado")
        idp = max(int(i) for i in ids)
    else:
        idp = int(m.group(1))
    notas.append("proyecto #%d" % idp)
    return idp


@paso("7. Importar las viviendas desde un Excel/CSV")
def importar(notas, idp):
    r = s.get(BASE + "/admin/projects/%d/units/import" % idp, timeout=30)
    if r.status_code != 200:
        raise RuntimeError("la pantalla de importar devuelve %s" % r.status_code)

    # Un fichero como los que manda una promotora de verdad: cabeceras en
    # castellano, precios con simbolo y separador de miles, superficies con
    # unidad y coma decimal.
    csv = (
        "Unidad;Planta;Dormitorios;Banos;Superficie;Precio;Estado\n"
        "A-101;1;2;2;85,5 m2;$185.000;Disponible\n"
        "A-102;1;3;2;102,0 m2;$225.000;Disponible\n"
        "A-201;2;2;2;85,5 m2;$192.000;Reservado\n"
        "A-202;2;3;2;102,0 m2;$235.000;Disponible\n"
        "B-101;1;1;1;55,0 m2;$135.000;Vendido\n"
    )

    f = elegir(formularios(r.text), "analizar")
    datos = {"_token": (f or {}).get("campos", {}).get("_token", "")}
    campo = next((k for k, t in (f or {}).get("tipos", {}).items() if t == "file"), "fichero")
    notas.append("campo del fichero: %s" % campo)

    r = s.post(BASE + "/admin/projects/%d/units/import/analizar" % idp,
               data=datos, files={campo: ("viviendas.csv", csv, "text/csv")}, timeout=60)
    if r.status_code >= 400:
        raise RuntimeError("el analisis devuelve %s" % r.status_code)

    detectadas = re.findall(r"A-10[12]|B-101", r.text)
    notas.append("reconoce las filas en la vista previa: %s" % ("si" if detectadas else "NO"))

    f2 = elegir(formularios(r.text), "confirmar")
    if not f2:
        raise RuntimeError("no hay formulario de confirmacion tras analizar")

    r = s.post(BASE + "/admin/projects/%d/units/import/confirmar" % idp,
               data=dict(f2["campos"]), timeout=60)
    if r.status_code >= 400:
        raise RuntimeError("la confirmacion devuelve %s" % r.status_code)

    r = s.get(BASE + "/admin/projects/%d/units" % idp, timeout=30)
    n = len(re.findall(r"A-101|A-102|A-201|A-202|B-101", r.text))
    notas.append("viviendas visibles despues: %d referencias" % n)
    if n == 0:
        raise RuntimeError("se importo pero no se ven las viviendas")


@paso("8. Publicar el proyecto")
def publicar(notas, idp):
    r = s.get(BASE + "/admin/projects/%d/edit" % idp, timeout=30)
    f = elegir(formularios(r.text), "/admin/projects/%d" % idp)
    if not f:
        raise RuntimeError("no encuentro el formulario de edicion")

    if "status" in f["opciones"]:
        notas.append("estados posibles: %s" % f["opciones"]["status"])

    datos = dict(f["campos"])
    datos["status"] = "public"
    datos.setdefault("_method", "PUT")
    datos["_method"] = "PUT"

    r = s.post(BASE + "/admin/projects/%d" % idp, data=datos, timeout=60)
    if errores(r.text):
        notas.append("avisos: %s" % "; ".join(errores(r.text)))

    # Con el reparto acordado, una promotora NO publica un proyecto sin visor:
    # el montaje 3D lo hace el equipo. Lo que se comprueba aqui es que se le
    # explique, no que publique. Un rechazo mudo seria el fallo.
    r = s.get(BASE + "/admin/projects/%d/edit" % idp, timeout=30)
    texto = re.sub(r"<[^>]+>", " ", r.text)

    explica = "Falta el visor" in texto or "viewer is missing" in texto
    notas.append("la ficha dice que falta para publicar: %s" % ("si" if explica else "NO"))
    if not explica:
        notas.append("PROBLEMA: no se publica y no se dice por que")

    pub = requests.Session()
    pub.auth = s.auth
    visible = ("Recorrido automatico %s" % SELLO) in pub.get(BASE + "/projects", timeout=30).text
    notas.append("visible para un visitante: %s (correcto: sin visor, no)"
                 % ("si" if visible else "no"))

    if visible:
        raise RuntimeError("se ha publicado un proyecto sin nada que enseñar")
    return idp


@paso("9. Un comprador pregunta por una vivienda")
def preguntar(notas, idp):
    pub = requests.Session()
    pub.auth = s.auth

    # El slug no esta en el formulario (no es editable): se saca del enlace de
    # "ver la ficha publica" que hay en la pagina, que es de donde lo cogeria
    # una persona.
    r = s.get(BASE + "/admin/projects/%d/edit" % idp, timeout=30)
    candidatos = [x for x in re.findall(r'/projects/([a-z0-9-]+)', r.text)
                  if not x.isdigit()]
    slug = candidatos[0] if candidatos else None
    if not slug:
        # Sin visor el proyecto sigue en borrador y no hay ficha publica que
        # visitar. No es una rotura: es el reparto funcionando. Este tramo se
        # prueba entero en cuanto el equipo suba el modelo o el fondo 360.
        notas.append("el proyecto sigue en borrador: falta que el equipo monte el visor")
        notas.append("el tramo del comprador queda pendiente de eso, no roto")
        return

    r = pub.get(BASE + "/projects/" + slug, timeout=30)
    notas.append("la ficha publica devuelve %s" % r.status_code)
    if r.status_code != 200:
        notas.append("esperado: el proyecto sigue en borrador hasta que el equipo")
        notas.append("monte el visor. Este paso se prueba entero cuando lo haya.")
        return

    f = elegir(formularios(r.text), "inquiry")
    if not f:
        notas.append("PROBLEMA: no hay formulario de consulta en la ficha publica")
        raise RuntimeError("no se puede preguntar")

    datos = dict(f["campos"])
    datos.update({
        "name": "Comprador Extranjero",
        "email": "comprador.%s@recorrido-automatico.invalid" % SELLO,
        "phone": "+1 305 555 0100",
        "message": "Me interesa la A-102. Puedo comprar desde el extranjero?",
    })
    r = pub.post(BASE + "/projects/%s/inquiry" % slug, data=datos, timeout=60)
    notas.append("enviar la consulta devuelve %s" % r.status_code)
    if r.status_code >= 400:
        raise RuntimeError("la consulta fue rechazada")


# ------------------------------------------------------------------- ejecucion

print("Recorrido de alta en %s\n" % BASE)

html = buscar_alta()
r = registrarse(html)
ficha_empresa(r)
elegir_plan(r)
panel(r)
idp = crear_proyecto(r)
if idp:
    importar(idp)
    publicar(idp)
    preguntar(idp)

total = time.time() - inicio_global
rotos = [p for p in pasos if not p[2]]

print("\n" + "=" * 62)
print("TOTAL: %.0f segundos (%.1f minutos) en %d pasos" % (total, total / 60, len(pasos)))
print("pasos que fallaron: %d" % len(rotos))
for n, d, ok, notas in pasos:
    if not ok:
        print("  - %s" % n)

# Tambien se considera fallo un paso que va bien pero avisa de un PROBLEMA: son
# los casos en que el producto responde 200 y aun asi no hace lo que debe, que
# es justo lo que este recorrido existe para pillar.
con_problema = [n for n, d, ok, notas in pasos
                if ok and any(x.startswith("PROBLEMA") for x in notas)]
for n in con_problema:
    print("  - %s (responde bien pero avisa de un problema)" % n)

sys.exit(1 if (rotos or con_problema) else 0)
