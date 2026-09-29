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
import subprocess
import re
import sys
import time
import uuid
from html.parser import HTMLParser

import requests

BASE = (sys.argv[1] if len(sys.argv) > 1 else "https://dev.real3d.io").rstrip("/")
CLAVE_WEB = os.environ.get("CLAVE_WEB", "")
SELLO = time.strftime("%H%M%S")

# La promotora de esta vuelta. Es a quien deben llegar los avisos de los leads,
# y por eso se le pasa al gancho del correo: que la cola se vacie no dice a
# quien fue nada.
CORREO_PROMOTORA = "ana.%s@recorrido-automatico.invalid" % SELLO

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


def slug_publico(idp):
    """El slug de ESTE proyecto, sacado de su pagina de edicion.

    No esta en el formulario porque no es editable, asi que se saca del enlace
    a la ficha publica, que es de donde lo cogeria una persona.

    Se exige que lleve el sello de esta vuelta. Antes valia el primer enlace
    /projects/<algo> que apareciera, y en esa pagina puede haber enlaces a
    otros proyectos -un listado relacionado, una miga de pan-: el paso habria
    comprobado el visor de un proyecto ajeno y habria dado verde o rojo sin
    medir lo que dice medir. Hoy acierta, pero por suerte.

    Devuelve None si no aparece ninguno suyo, que es lo que pasa mientras el
    proyecto sigue en borrador.
    """
    r = s.get(BASE + "/admin/projects/%d/edit" % idp, timeout=30)

    for candidato in re.findall(r"/projects/([a-z0-9-]+)", r.text):
        if not candidato.isdigit() and SELLO in candidato:
            return candidato

    return None


def elegir(forms, pista=""):
    """El formulario cuya accion contenga la pista.

    Si se da una pista y ningun formulario casa, devuelve None. Antes caia al
    "primero con campos", y esa red se comio una comprobacion entera: el paso
    que miraba si el plan gratuito tenia boton de pedir visor decia que SI
    porque recogia el formulario de cerrar sesion. Peor todavia, un paso
    llego a enviar a una direccion los campos de otro formulario -- con su
    _method=DELETE dentro -- y lo que volvia era un 405 que parecia un fallo
    del producto.

    Un buscador que siempre encuentra algo no informa de nada. Sin pista si
    vale el primero util, que es el caso de "la unica forma de esta pagina".
    """
    if pista:
        return next((f for f in forms if pista in f["action"]), None)

    utiles = [f for f in forms if len(f["campos"]) > 1]
    return utiles[0] if utiles else (forms[0] if forms else None)


def comprobar_que_sale_el_aviso(notas, si_falla, marca):
    """Que el aviso del lead salga de la cola de verdad.

    Que el servidor responda 200 no significa nada: el aviso a la promotora se
    encola, y si el worker no lo procesa el lead se pierde igual. Eso paso en
    produccion y nadie se entero en semanas. Lo comparten los dos caminos por
    los que entra un lead -el formulario y el chatbot- porque el segundo no
    avisaba a nadie y tardo meses en notarse: solo se vigilaba el primero.

    La marca es un texto que solo lleva el aviso de este camino. Los dos avisan
    a la misma promotora, asi que mirar solo el destinatario dejaba que el
    correo del formulario contara por el del chatbot.
    """
    comprobar = os.environ.get("GANCHO_CORREO", "").strip()
    if not comprobar:
        notas.append("sin GANCHO_CORREO: no se comprueba que el aviso salga")
        return

    r = subprocess.run(comprobar.split() + [CORREO_PROMOTORA, marca],
                       capture_output=True, text=True)
    for linea in (r.stdout + r.stderr).strip().split("\n"):
        if linea:
            notas.append(linea)
    if r.returncode != 0:
        notas.append("PROBLEMA: " + si_falla)


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
        "email": CORREO_PROMOTORA,
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


@paso("5. Llegar al panel y saber que hacer")
def panel(notas, _):
    """No basta con que cargue: tiene que decirle por donde empezar.

    Antes de los primeros pasos, una promotora recien registrada entraba y veia
    un menu y nada mas. Eso no da un error en ningun sitio: da una pantalla
    vacia, que es de lo que nadie se queja y todo el mundo abandona.
    """
    r = s.get(BASE + "/admin", timeout=30)
    if r.status_code != 200:
        raise RuntimeError("el panel devuelve %s (acaba en %s)"
                           % (r.status_code, r.url.replace(BASE, "")))

    texto = re.sub(r"<[^>]+>", " ", r.text)

    guia = "Primeros pasos" in texto or "First steps" in texto
    notas.append("el panel le dice por donde empezar: %s" % ("si" if guia else "NO"))
    if not guia:
        notas.append("PROBLEMA: una promotora nueva entra y no sabe que hacer")

    # Y que el primer paso sea el que toca: crear el proyecto.
    if guia and "Crea tu proyecto" not in texto and "Create your project" not in texto:
        notas.append("PROBLEMA: el primer paso no es crear el proyecto")


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


@paso("7a. Sin plan de pago, el visor esta cerrado")
def visor_cerrado_sin_plan(notas, idp):
    """El plan gratuito da el producto entero menos el visor.

    Es lo unico que cuesta dinero hacer -lo monta el equipo, uno a uno- y por
    tanto lo unico que separa el plan gratuito del de pago. Durante un tiempo
    no lo comprobo nadie: una promotora gratuita pedia su visor y se lo
    montabamos, asi que los dos planes daban lo mismo.
    """
    r = s.get(BASE + "/admin/projects/%d/edit" % idp, timeout=30)

    hay_boton = elegir(formularios(r.text), "pedir-visor") is not None
    notas.append("con el plan gratuito, el panel ofrece el boton: %s"
                 % ("SI" if hay_boton else "no"))
    if hay_boton:
        notas.append("PROBLEMA: el plan gratuito puede pedir lo unico que se paga")

    # Y que no sea una puerta muda: tiene que decir que falta y adonde ir.
    texto = re.sub(r"<[^>]+>", " ", r.text)
    explica = "visor" in texto.lower() and ("plan" in texto.lower() or "planes" in texto.lower())
    notas.append("y le explica que hace falta un plan: %s" % ("si" if explica else "NO"))
    if not explica:
        notas.append("PROBLEMA: se le cierra la puerta sin decirle por que")

    # Y por detras tampoco, que es donde de verdad se cuela la gente. Con el
    # token de la propia pagina: sin el, lo que se comprueba es que funciona la
    # proteccion CSRF, que no es lo que se esta preguntando aqui.
    token = re.search(r'name="_token"\s+value="([^"]+)"', r.text)
    if not token:
        notas.append("sin token en la pagina: no se puede probar el atajo")
        return

    s.post(BASE + "/admin/projects/%d/pedir-visor" % idp,
           data={"_token": token.group(1)}, timeout=60)

    r = s.get(BASE + "/admin/projects/%d/edit" % idp, timeout=30)
    colado = "Visor pedido el" in re.sub(r"<[^>]+>", " ", r.text)
    notas.append("pidiendolo a mano, con token valido, se cuela: %s"
                 % ("SI" if colado else "no"))
    if colado:
        notas.append("PROBLEMA: la puerta solo esta en la pantalla, no en el servidor")


@paso("7b. Contrata un plan")
def contratar(notas, idp):
    """Lo que en produccion hace una contratacion.

    Aqui no se puede contratar porque el entorno de desarrollo no tiene
    pasarela, asi que se hace por el otro camino legitimo: el que usa un
    superadmin al conceder un plan a mano. No es saltarse la puerta -- el paso
    anterior acaba de comprobar que esta cerrada -- es pasar por ella.
    """
    gancho = os.environ.get("GANCHO_PLAN", "").strip()
    if not gancho:
        notas.append("sin GANCHO_PLAN: el recorrido acabara antes del visor")
        return False

    r = subprocess.run(gancho.split() + [str(idp)], capture_output=True, text=True)
    for linea in (r.stdout + r.stderr).strip().splitlines():
        if linea:
            notas.append(linea)
    if r.returncode != 0:
        raise RuntimeError("el gancho del plan fallo")
    return True


@paso("7c. La promotora avisa de que esta lista")
def pedir_visor(notas, idp):
    """La costura entre lo que hace ella y lo que hace el equipo.

    Antes de esto, una promotora terminaba de cargar sus viviendas y se quedaba
    delante de un aviso que decia "lo hace el equipo de Real3D" sin ningun boton.
    Que ese boton exista y funcione es parte del camino, no un extra.
    """
    # Sin plan contratado esto no aplica: el paso anterior acaba de comprobar
    # que el visor esta cerrado, y seguir aqui solo produciria un fallo
    # confuso que parece del producto y es de la configuracion.
    if not os.environ.get("GANCHO_PLAN", "").strip():
        notas.append("sin GANCHO_PLAN no hay plan de pago: este paso no aplica")
        notas.append("el recorrido acabara antes del visor")
        return

    r = s.get(BASE + "/admin/projects/%d/edit" % idp, timeout=30)

    f = elegir(formularios(r.text), "pedir-visor")
    if not f:
        notas.append("PROBLEMA: no hay forma de avisar al equipo de que falta el visor")
        return

    r = s.post(BASE + "/admin/projects/%d/pedir-visor" % idp,
               data=dict(f["campos"]), timeout=60)
    notas.append("avisar al equipo devuelve %s" % r.status_code)

    r = s.get(BASE + "/admin/projects/%d/edit" % idp, timeout=30)
    confirmado = "Visor pedido el" in re.sub(r"<[^>]+>", " ", r.text)
    notas.append("la ficha confirma que esta pedido: %s" % ("si" if confirmado else "NO"))
    if not confirmado:
        notas.append("PROBLEMA: se pide y no queda constancia")

    # La cola del equipo tiene sus proyectos: una promotora no debe verla.
    r = s.get(BASE + "/admin/visores-pendientes", timeout=30)
    notas.append("la cola del equipo le devuelve %s (debe ser 403)" % r.status_code)
    if r.status_code != 403:
        notas.append("PROBLEMA: una promotora ve los proyectos de las demas")


@paso("7d. El equipo monta el visor")
def montar_visor(notas, idp):
    """El unico tramo que no hace la promotora.

    Se ejecuta el mandato que venga en GANCHO_VISOR, con el id del proyecto. Si
    no hay ninguno, el recorrido sigue sin visor y se detiene en el paso 9, que
    tambien es un resultado valido: comprueba que publicar queda bloqueado.
    """
    gancho = os.environ.get("GANCHO_VISOR", "").strip()
    if not gancho or not os.environ.get("GANCHO_PLAN", "").strip():
        notas.append("sin GANCHO_VISOR o sin GANCHO_PLAN: el recorrido acabara")
        notas.append("antes del comprador")
        return False

    r = subprocess.run(gancho.split() + [str(idp)], capture_output=True, text=True)
    for linea in (r.stdout + r.stderr).strip().split("\n"):
        if linea:
            notas.append(linea)
    if r.returncode != 0:
        raise RuntimeError("el gancho del visor fallo")

    # Que el fichero se pueda SERVIR no se comprueba aqui sino en el paso 10,
    # con el proyecto ya publicado: en este momento sigue en borrador y la API
    # lo oculta por diseño, asi que preguntar ahora solo mide eso.
    return True


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

    r = s.get(BASE + "/admin/projects/%d/edit" % idp, timeout=30)
    texto = re.sub(r"<[^>]+>", " ", r.text)
    con_visor = (os.environ.get("GANCHO_VISOR", "").strip() != ""
                 and os.environ.get("GANCHO_PLAN", "").strip() != "")

    pub = requests.Session()
    pub.auth = s.auth
    visible = ("Recorrido automatico %s" % SELLO) in pub.get(BASE + "/projects", timeout=30).text
    en_portal = ("Recorrido automatico %s" % SELLO) in pub.get(BASE + "/portal", timeout=30).text

    notas.append("visible en /projects: %s" % ("si" if visible else "no"))
    notas.append("visible en /portal:   %s" % ("si" if en_portal else "no"))

    if con_visor:
        # Con visor montado y coordenadas puestas tiene que salir en los dos.
        if not visible:
            notas.append("PROBLEMA: hay visor montado y aun asi no se publica")
        elif not en_portal:
            notas.append("PROBLEMA: publicado pero no aparece en el portal")
    else:
        # Sin visor no debe publicarse, y sobre todo debe DECIR por que.
        explica = "Falta el visor" in texto or "viewer is missing" in texto
        notas.append("la ficha explica que falta: %s" % ("si" if explica else "NO"))
        if not explica:
            notas.append("PROBLEMA: no se publica y no se dice por que")
        if visible:
            raise RuntimeError("se ha publicado un proyecto sin nada que enseñar")

    return idp


@paso("9. Un comprador pregunta por una vivienda")
def preguntar(notas, idp):
    pub = requests.Session()
    pub.auth = s.auth

    slug = slug_publico(idp)
    if not slug:
        # Sin visor el proyecto sigue en borrador y no hay ficha publica que
        # visitar. No es una rotura: es el reparto funcionando. Este tramo se
        # prueba entero en cuanto el equipo suba el modelo o el fondo 360.
        notas.append("el proyecto sigue en borrador: falta que el equipo monte el visor")
        notas.append("el tramo del comprador queda pendiente de eso, no roto")
        return

    # El formulario de consulta esta en la ficha de informacion, no en el visor
    # 3D: desde el visor se contacta por WhatsApp o por el chatbot, que tienen
    # su propio camino.
    r = pub.get(BASE + "/projects/%s/info" % slug, timeout=30)
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

    # La marca es el asunto de ESTE aviso: sin ella, el del chatbot y este
    # se contarian el uno por el otro, porque avisan a la misma promotora.
    comprobar_que_sale_el_aviso(notas, "la consulta se guarda pero el aviso no sale",
                                "Recorrido automatico %s - Comprador Extranjero" % SELLO)


@paso("9b. Un comprador pregunta por el chatbot y deja su contacto")
def preguntar_por_el_chatbot(notas, idp):
    """El otro camino por el que entra un lead.

    Hasta ahora solo se vigilaba el formulario. El chatbot guardaba el
    contacto y no avisaba a nadie, y como el recorrido no pasaba por ahi, nada
    lo dijo. Se hace lo que hace el widget desde el navegador -mandar un
    mensaje, dejar nombre y correo- y despues se mira lo que un comprador no
    ve: que la promotora lo tiene en su bandeja y que el aviso sale de la cola.

    Si el asistente contesta con su disculpa de siempre, el proveedor de IA
    esta caido. El servidor responde 200 igual, asi que hay que leer lo que
    dice, no el codigo.
    """
    pub = requests.Session()
    pub.auth = s.auth

    slug = slug_publico(idp)
    if not slug:
        notas.append("el proyecto no llego a publicarse: este paso no aplica")
        return

    sesion = str(uuid.uuid4())
    r = pub.post(BASE + "/api/projects/%s/chat" % slug, json={
        "message": "Hola, cual es la vivienda mas barata que teneis disponible?",
        "session_id": sesion,
    }, timeout=90)
    notas.append("el chatbot devuelve %s" % r.status_code)

    if r.status_code == 503:
        notas.append("el chatbot esta apagado: %s" % r.json().get("error", ""))
        notas.append("este paso no aplica hasta que se encienda")
        return
    if r.status_code != 200:
        raise RuntimeError("el chatbot rechazo el mensaje")

    respuesta = r.json().get("message", "")
    if not respuesta.strip():
        notas.append("PROBLEMA: el asistente contesta con un mensaje vacio")
    elif ("problema para responder" in respuesta
          or "trouble responding" in respuesta):
        notas.append("PROBLEMA: el asistente pide disculpas: el proveedor de IA no contesta")
    else:
        notas.append("el asistente contesta (%d caracteres)" % len(respuesta))

    correo = "chat.%s@recorrido-automatico.invalid" % SELLO
    r = pub.post(BASE + "/api/projects/%s/chat/lead" % slug, json={
        "session_id": sesion,
        "name": "Comprador Del Chat",
        "email": correo,
        "phone": "+1 305 555 0101",
    }, timeout=60)
    notas.append("dejar el contacto devuelve %s" % r.status_code)
    if r.status_code != 200 or not r.json().get("success"):
        raise RuntimeError("el chatbot no guardo el contacto")

    # Lo que ve la promotora: el lead en su bandeja, con este correo. Guardarlo
    # en una tabla que nadie mira es perderlo con mas pasos.
    bandeja = s.get(BASE + "/admin/inquiries", timeout=30)
    if correo in bandeja.text:
        notas.append("la promotora lo ve en su bandeja de consultas")
    else:
        notas.append("PROBLEMA: el lead del chat no aparece en la bandeja de la promotora")

    comprobar_que_sale_el_aviso(notas, "el chat guarda el contacto pero el aviso no sale",
                                "Recorrido automatico %s - Comprador Del Chat" % SELLO)


@paso("10. Se da de baja y el visor deja de verse")
def darse_de_baja(notas, idp):
    """La otra mitad del circuito del dinero.

    Hasta aqui el recorrido comprueba que se puede contratar, que montamos el
    visor y que se publica. Faltaba lo que pasa al dejar de pagar, que es lo
    que decide si el producto se vende o se regala: el visor se quedaba
    sirviendo para siempre, y por dos puertas -la pagina y la API por la que
    sale el modelo-.

    Se comprueba tambien lo que NO debe perderse. Llevarse por delante la
    pagina, las viviendas o el formulario de contacto seria cobrarle a una
    promotora por algo que le habiamos dicho que era gratis.
    """
    gancho = os.environ.get("GANCHO_BAJA", "").strip()
    if not gancho:
        notas.append("sin GANCHO_BAJA: no se comprueba que la baja quite el visor")
        return

    pub = requests.Session()
    pub.auth = s.auth

    slug = slug_publico(idp)
    if not slug:
        notas.append("el proyecto no llego a publicarse: este paso no aplica")
        return

    # Primero, que hubiera algo que quitar. Sin esto, todo lo de abajo saldria
    # en verde con un visor que nunca funciono.
    antes = pub.get(BASE + "/projects/%s" % slug, timeout=30)
    notas.append("pagando, el visor devuelve %s" % antes.status_code)
    if antes.status_code != 200:
        notas.append("PROBLEMA: el visor no se servia ni estando al corriente")
        return

    # Y el fichero, que es lo que el visor pinta. Que exista en disco no es que
    # funcione: el gancho corria como root y dejaba el directorio con permisos
    # que el servidor web no puede atravesar, asi que el fichero estaba, la
    # base de datos decia upload_complete, el paso 7d decia "subido e identico
    # al original", y el visor devolvia 404 a cualquiera que lo abriera.
    antes_fichero = pub.get(BASE + "/api/projects/%s/files/image_360" % slug, timeout=30)
    notas.append("y el fondo 360 se sirve: %s" % antes_fichero.status_code)
    if antes_fichero.status_code != 200:
        notas.append("PROBLEMA: el fichero esta subido pero el servidor no lo puede leer")
        return

    proceso = subprocess.run(gancho.split() + [str(idp)], capture_output=True, text=True)
    for linea in (proceso.stdout + proceso.stderr).strip().splitlines():
        if linea:
            notas.append(linea)
    if proceso.returncode != 0:
        raise RuntimeError("el gancho de la baja fallo")

    # A la ficha de informacion, no a un 404: quien mira es un comprador.
    despues = pub.get(BASE + "/projects/%s" % slug, timeout=30, allow_redirects=False)
    notas.append("tras la baja, el visor devuelve %s" % despues.status_code)
    if despues.status_code != 302:
        notas.append("PROBLEMA: el visor se sigue sirviendo despues de la baja")

    # La otra puerta: el fondo y el modelo salen por la API, y con la direccion
    # se descargan sin pasar por la pagina.
    #
    # Esta ruta iba por id mientras sus vecinas iban por slug, y esta
    # comprobacion paso una temporada pidiendo por slug: 404 siempre, "el 3D no
    # se descarga", y no miraba nada. Lo delato desactivar el muro a proposito y
    # ver que seguia dando 404. Hoy todas van por slug, y hay un test que lo
    # exige para que no vuelva a pasar.
    fichero = pub.get(BASE + "/api/projects/%s/files/image_360" % slug, timeout=30)
    notas.append("y el fondo 360 por la API devuelve %s" % fichero.status_code)
    if fichero.status_code != 404:
        notas.append("PROBLEMA: el 3D se descarga igual conociendo la direccion")

    # Y lo que se queda, que es el plan gratuito entero.
    ficha = pub.get(BASE + "/projects/%s/info" % slug, timeout=30)
    notas.append("la ficha publica sigue devolviendo %s" % ficha.status_code)
    if ficha.status_code != 200:
        notas.append("PROBLEMA: la baja se ha llevado por delante el plan gratuito")


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
    visor_cerrado_sin_plan(idp)
    contratar(idp)
    pedir_visor(idp)
    montar_visor(idp)
    publicar(idp)
    preguntar(idp)
    preguntar_por_el_chatbot(idp)
    # El ultimo, porque deja a la promotora sin plan: cualquier paso detras se
    # encontraria el producto a medias y contaria un fallo que no existe.
    darse_de_baja(idp)

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
