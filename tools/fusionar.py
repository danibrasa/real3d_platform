#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Crea y fusiona en orden las ramas pendientes de Real3D.io.

Lee el token de la variable de entorno GH_TOKEN. No lo escribe en ningun sitio.

Que hace, por cada rama y en el orden de la lista:
  1. Crea la pull request (o reutiliza la que ya hubiera abierta).
  2. Espera a que el CI acabe en verde.
  3. Si la rama se ha quedado atras respecto a main por las fusiones anteriores,
     la actualiza y vuelve a esperar al CI.
  4. Fusiona con commit de merge, no squash: release-please necesita ver los
     commits de Conventional Commits de uno en uno para calcular la version.

Si una rama choca con main, se resuelve con el rerere del clon de /var/www/dev
y se sube; si rerere no lo resuelve, el script para y lo dice en vez de
inventarse nada.

Es reentrante: si se corta a medias, se vuelve a lanzar y sigue donde estaba.
"""

import json
import os
import subprocess
import sys
import time
import urllib.error
import urllib.request

REPO = "danibrasa/real3d_platform"
API = "https://api.github.com"
CLON = "/var/www/dev"
JOB_CI = "Estilo, tests y compilacion"   # el job, como lo ve check-runs
WORKFLOW_CI = "Comprobaciones"           # el workflow, como lo ve actions/runs

# Las ramas se pasan como argumentos, en el orden en que hay que fusionarlas:
#
#   python3 tools/fusionar.py fix/lo-primero feat/lo-segundo
#
# Antes se editaba una lista aqui dentro antes de cada ejecucion, y eso tenia
# dos precios. El pequeno: el repositorio quedaba sucio despues de cada tanda.
# El caro: la lista se colo en un commit de facturacion con el nombre de una
# rama de otro asunto todavia dentro, asi que el fichero que decide QUE se
# fusiona llevaba, sin decirlo, el nombre de algo que nadie habia pedido
# fusionar. Un dato de cada ejecucion no es codigo y no debe vivir en el
# codigo.
#
# El fichero si vive en el repositorio a proposito: antes andaba suelto en
# /root y se editaba a mano en dos sitios, hasta que una copia piso a la otra
# y se perdio la integracion del revisor sin que nada lo dijera.

PIE = (
    "\n\n---\n\n"
    "\U0001F916 Generated with [Claude Code](https://claude.com/claude-code)\n\n"
    "https://claude.ai/code/session_01JLkMTQNtP16zodM5R4QjrT\n"
)

TOKEN = os.environ.get("GH_TOKEN", "").strip()
if not TOKEN:
    sys.exit("falta GH_TOKEN en el entorno")


def api(ruta, metodo="GET", cuerpo=None):
    req = urllib.request.Request(
        ruta if ruta.startswith("http") else API + ruta,
        method=metodo,
        data=json.dumps(cuerpo).encode() if cuerpo is not None else None,
        headers={
            "Authorization": "Bearer " + TOKEN,
            "Accept": "application/vnd.github+json",
            "X-GitHub-Api-Version": "2022-11-28",
            "Content-Type": "application/json",
            "User-Agent": "real3d-merge-script",
        },
    )
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            texto = r.read().decode()
            return r.status, (json.loads(texto) if texto else {})
    except urllib.error.HTTPError as e:
        texto = e.read().decode()
        try:
            return e.code, json.loads(texto)
        except ValueError:
            return e.code, {"message": texto[:400]}


def git(*args, check=True):
    r = subprocess.run(
        ["git", "-C", CLON] + list(args), capture_output=True, text=True
    )
    if check and r.returncode != 0:
        sys.exit("git %s fallo:\n%s%s" % (" ".join(args), r.stdout, r.stderr))
    return r.stdout.strip()


def log(*a):
    print(*a, flush=True)


def io_leer(ruta):
    with open(ruta, encoding="utf-8", errors="replace") as f:
        return f.read()


def cuerpo_pr(rama):
    """Titulo y cuerpo a partir de los commits de la rama."""
    commits = git("log", "--format=%H", "--reverse", "origin/main..origin/" + rama).split()
    primero = git("log", "-1", "--format=%B", commits[0])

    lineas = [l for l in primero.split("\n")
              if not l.startswith(("Co-Authored-By:", "Claude-Session:"))]
    titulo = lineas[0].strip()
    descripcion = "\n".join(lineas[1:]).strip()

    if len(commits) > 1:
        # Con varios commits, el resto se resume en una lista: el cuerpo largo de
        # cada uno ya esta en su propio mensaje.
        otros = git("log", "--format=- %s", "--reverse",
                    "origin/main..origin/" + rama)
        descripcion += "\n\n**Commits**\n\n" + otros

    return titulo, descripcion + PIE


def revisar(rama):
    """Pasa el revisor adversarial por la rama antes de fusionarla.

    Se llama con la clave del entorno de desarrollo. Si no hay clave, no se
    revisa y se dice: callarse haria creer que la revision salio limpia.

    Un hallazgo grave detiene la fusion. Antes solo se imprimia un aviso, con
    el argumento de que quien decide es quien lee; pero esto se lanza sin nadie
    delante y su salida se mira con un tail, asi que el aviso salio por encima
    del corte y una rama se fusiono con dos hallazgos graves que nadie llego a
    leer. Un aviso que depende de que alguien scrollee no es un aviso.

    Una revision que no llego a hacerse tampoco deja seguir, por el mismo
    motivo: nadie ha visto un veredicto. Antes se imprimia "se fusiona sin
    revision" y se fusionaba, que es la version silenciosa del mismo problema
    -- y ademas la que mas facil es de provocar sin querer, porque basta con
    que falte la clave o se cuelgue la llamada.

    Sigue decidiendo una persona, pero tiene que decirlo:
      --aunque-haya-hallazgos   fusiona con hallazgos graves
      --sin-revisor             fusiona cuando la revision no se pudo hacer

    Devuelve "limpia", "hallazgos" o "sin-revision".
    """
    clave = ""
    try:
        with open(CLON + "/.env", encoding="utf-8") as f:
            for linea in f:
                if linea.startswith("ANTHROPIC_API_KEY="):
                    clave = linea.split("=", 1)[1].strip()
                    break
    except OSError:
        pass

    if not clave:
        log("      | NO SE PUDO REVISAR: no hay clave en el .env")
        return "sin-revision"

    entorno = dict(os.environ, GH_ANTHROPIC_KEY=clave, CLON=CLON)

    # Con timeout, y no por prudencia general: sin el, un revisor colgado deja
    # la fusion esperando para siempre y no se ejecuta ninguna de las ramas que
    # avisan. Es decir, el caso que se decia cubierto era justo el unico que no
    # lo estaba.
    try:
        r = subprocess.run(
            # Tres puntos, no dos: con dos, git compara las dos puntas, y una
            # rama que se quedo atras de main "borra" todo lo que main gano
            # despues. El revisor vio desaparecer una funcion entera en una
            # rama de documentacion y la paro, con razon, por lo que veia.
            ["python3", CLON + "/tools/revisor.py", "origin/main...origin/" + rama],
            capture_output=True, text=True, env=entorno, timeout=300,
        )
    except subprocess.TimeoutExpired:
        log("      | NO SE PUDO REVISAR: el revisor se colgo (mas de 5 min)")
        return "sin-revision"

    for linea in r.stdout.strip().split("\n"):
        if linea.strip():
            log("      | " + linea)

    if r.returncode == 1:
        log("      |")
        log("      | HALLAZGOS GRAVES: no se fusiona esta rama.")
        log("      | Leelos y arreglalos, o repite con --aunque-haya-hallazgos")
        return "hallazgos"

    if r.returncode != 0:
        # Cualquier otra cosa -el 2 previsto, pero tambien un cuelgue, un 127 o
        # una excepcion sin capturar- significa que no hubo revision. Tratar
        # solo los codigos previstos dejaba pasar en silencio justo el caso mas
        # probable de rotura real: que el revisor se rompa.
        log("      | NO SE PUDO REVISAR (codigo %d): no se fusiona esta rama." % r.returncode)
        for linea in r.stderr.strip().split("\n")[-3:]:
            if linea.strip():
                log("      | " + linea)

        log("      | Arreglalo, o repite con --sin-revisor")

        return "sin-revision"

    return "limpia"


# Los veredictos del revisor, y que bandera hace falta para pasar por encima
# de cada uno. "limpia" no lleva bandera porque no hay nada que consentir.
BANDERA = {
    "hallazgos": "--aunque-haya-hallazgos",
    "sin-revision": "--sin-revisor",
}


def puede_seguir(veredicto, argumentos):
    """Si con ese veredicto se fusiona o no.

    Es una funcion y no dos lineas dentro del bucle para que se pueda
    comprobar. Los tests miraban lo que devolvia revisar(), que es la mitad
    de la historia: si la comparacion de aqui estuviera del reves, el
    veredicto seria correcto y se fusionaria igual, y ningun test lo diria.
    """
    if veredicto == "limpia":
        return True

    bandera = BANDERA.get(veredicto)

    # Un veredicto que no conocemos no se consiente con ninguna bandera: es
    # un fallo de programacion, no una decision que nadie haya tomado.
    return bandera is not None and bandera in argumentos


def estado_fusion(numero, intentos=15):
    """La PR, esperando a que GitHub sepa si se puede fusionar.

    `mergeable_state` se calcula en diferido: recien creada la PR responde
    'unknown'. Decidir con ese valor fue lo que dejo la septima rama esperando un
    CI que nunca iba a arrancar, porque con conflictos GitHub no construye el
    merge y no lanza los workflows.
    """
    pr = None
    for _ in range(intentos):
        _, pr = api("/repos/%s/pulls/%d" % (REPO, numero))
        if pr.get("mergeable_state") not in (None, "", "unknown"):
            return pr
        time.sleep(5)
    return pr


def pr_de(rama):
    """La PR abierta de esa rama, o None."""
    _, prs = api("/repos/%s/pulls?state=open&head=%s:%s&per_page=5"
                 % (REPO, REPO.split("/")[0], rama))
    return prs[0] if isinstance(prs, list) and prs else None


def estado_ci(sha):
    """(estado, conclusion) del CI sobre ese commit, con lo que el token permita.

    Se prueban dos vias porque no todos los tokens tienen los mismos permisos:
    check-runs necesita `Checks: read` y los workflow runs necesitan
    `Actions: read`. Con cualquiera de las dos vale.
    """
    codigo, d = api("/repos/%s/commits/%s/check-runs" % (REPO, sha))
    if codigo == 200:
        runs = [c for c in d.get("check_runs", []) if c.get("name") == JOB_CI]
        if not runs:
            return "queued", None
        r = sorted(runs, key=lambda c: c.get("started_at") or "")[-1]
        return r.get("status"), r.get("conclusion")

    codigo, d = api("/repos/%s/actions/runs?head_sha=%s&per_page=20" % (REPO, sha))
    if codigo == 200:
        runs = [w for w in d.get("workflow_runs", []) if w.get("name") == WORKFLOW_CI]
        if not runs:
            return "queued", None
        r = sorted(runs, key=lambda w: w.get("created_at") or "")[-1]
        return r.get("status"), r.get("conclusion")

    return None, None


def esperar_ci(sha, minutos=25):
    """Espera a que el CI acabe sobre ese commit. Devuelve la conclusion."""
    limite = time.time() + minutos * 60
    ultimo = None
    while time.time() < limite:
        estado, concl = estado_ci(sha)

        if estado is None:
            # Sin visibilidad del CI se para en vez de fusionar a ciegas: el
            # objetivo del circuito es justamente que no salga nada sin pasarlo.
            sys.exit("      PARO: el token no puede leer el estado del CI.\n"
                     "      Anade al token 'Checks: read' o 'Actions: read'.")

        if estado != ultimo:
            log("      CI: %s%s" % (estado, " -> " + concl if concl else ""))
            ultimo = estado
        if estado == "completed":
            return concl
        time.sleep(20)
    return "tiempo agotado"


def resolver_conflicto(rama):
    """Mete main en la rama y deja que rerere aplique la resolucion guardada."""
    log("      la rama choca con main: resolviendo en el clon")
    git("fetch", "-q", "origin")
    git("checkout", "-q", "-B", rama, "origin/" + rama)
    r = subprocess.run(["git", "-C", CLON, "merge", "--no-edit", "origin/main"],
                       capture_output=True, text=True)

    # Lo que decide si esta resuelto son los marcadores en el fichero, NO el
    # indice: rerere escribe la resolucion pero deja la entrada marcada como
    # conflictiva hasta que se hace `git add`. Mirar el indice hacia que el
    # script se rindiera con el conflicto ya resuelto delante.
    conflictivos = [x for x in git("diff", "--name-only", "--diff-filter=U").split("\n") if x]

    sin_resolver = []
    for f in conflictivos:
        texto = io_leer(os.path.join(CLON, f))
        if "<<<<<<<" in texto:
            sin_resolver.append(f)
        else:
            log("      rerere resolvio %s" % f)
            git("add", f)

    if sin_resolver:
        git("merge", "--abort", check=False)
        sys.exit("      PARO: queda conflicto sin resolver en %s. Hay que mirarlo a mano."
                 % ", ".join(sin_resolver))

    if r.returncode != 0:
        # Los ficheros ya estan resueltos, pero el merge sigue sin cerrar.
        git("commit", "-q", "--no-edit")

    # Lo que diga PHP sobre LO QUE TUVO CONFLICTO, no sobre dos ficheros que
    # escribi a mano el dia que el conflicto cayo justo ahi. Si manana choca
    # otro fichero PHP, tambien tiene que comprobarse: subir a main algo con la
    # sintaxis rota porque no estaba en una lista es precisamente el fallo que
    # este guion dice evitar.
    for f in conflictivos:
        if not f.endswith(".php"):
            continue

        lint = subprocess.run(["php", "-l", os.path.join(CLON, f)],
                              capture_output=True, text=True)
        if lint.returncode != 0:
            sys.exit("      PARO: %s no compila tras resolver:\n%s" % (f, lint.stdout))

    log("      comprobada la sintaxis de %d fichero(s) PHP en conflicto"
        % sum(1 for f in conflictivos if f.endswith(".php")))

    estilo = subprocess.run([os.path.join(CLON, "vendor/bin/pint"), "--test"],
                            cwd=CLON, capture_output=True, text=True)
    if estilo.returncode != 0:
        subprocess.run([os.path.join(CLON, "vendor/bin/pint"), "-q"], cwd=CLON)
        if git("status", "--short"):
            git("commit", "-aqm", "style: pint tras resolver el merge")

    git("push", "-q", "origin", rama)
    log("      resuelto y subido: %s" % git("rev-parse", "--short", "HEAD"))
    return True


def contiene(rama, otra):
    """Si la rama lleva dentro los commits de la otra (esta apilada sobre ella)."""
    return subprocess.run(
        ["git", "-C", CLON, "merge-base", "--is-ancestor", "origin/" + otra, "origin/" + rama],
        capture_output=True, text=True,
    ).returncode == 0


def main():
    ramas = [r for r in sys.argv[1:] if not r.startswith("-")]
    banderas = [a for a in sys.argv[1:] if a.startswith("-")]

    if not ramas:
        sys.exit(
            "uso: fusionar.py <rama> [rama...]\n"
            "     en el orden en que hay que fusionarlas"
        )

    detenidas = []
    detenidas_ramas = []

    git("fetch", "-q", "origin", "--prune")
    log("main esta en %s\n" % git("log", "--oneline", "-1", "origin/main"))

    for i, rama in enumerate(ramas, 1):
        log("[%d/%d] %s" % (i, len(ramas), rama))

        # El repositorio borra la rama al fusionar la PR, asi que una rama que ya
        # no existe en origin es una rama que ya esta dentro.
        existe = subprocess.run(
            ["git", "-C", CLON, "rev-parse", "--verify", "-q", "origin/" + rama],
            capture_output=True, text=True,
        ).returncode == 0

        if not existe:
            log("      ya fusionada y borrada en origin, nada que hacer\n")
            continue

        if not git("rev-list", "--count", "origin/main..origin/" + rama).strip("0"):
            log("      ya esta dentro de main, nada que hacer\n")
            continue

        # Una rama apilada sobre otra detenida lleva dentro lo que el revisor
        # paro. Fusionarla es fusionar aquello por la puerta de atras, y sin
        # que nadie lea el hallazgo: paso el 29-sep-2026, con un hallazgo
        # grave que entro por la PR de la rama siguiente. La puerta cerraba
        # bien para una rama y no para una pila.
        for detenida in detenidas_ramas:
            if contiene(rama, detenida):
                sys.exit("      PARO: %s lleva dentro a %s, que el revisor detuvo.\n"
                         "      Arregla %s primero, o repite con --aunque-haya-hallazgos."
                         % (rama, detenida, detenida))

        pr = pr_de(rama)
        if pr:
            log("      PR #%d ya abierta" % pr["number"])
        else:
            titulo, cuerpo = cuerpo_pr(rama)
            estado, pr = api("/repos/%s/pulls" % REPO, "POST", {
                "title": titulo, "head": rama, "base": "main", "body": cuerpo,
            })
            if estado != 201:
                sys.exit("      no pude crear la PR: %s" % pr.get("message"))
            log("      PR #%d creada: %s" % (pr["number"], titulo))

        numero = pr["number"]

        # Antes de esperar al CI: si hay algo gordo, mejor verlo ya, y sobre
        # todo antes de fusionarlo.
        veredicto = revisar(rama)

        if not puede_seguir(veredicto, banderas):
            detenidas.append("%s (%s)" % (rama, veredicto))
            detenidas_ramas.append(rama)
            continue

        for intento in (1, 2, 3, 4):
            pr = estado_fusion(numero)
            sha, estado = pr["head"]["sha"], pr.get("mergeable_state")
            log("      commit %s, estado %s" % (sha[:7], estado))

            if estado == "dirty":
                resolver_conflicto(rama)
                time.sleep(10)
                continue

            concl = esperar_ci(sha)
            if concl != "success":
                sys.exit("      PARO: el CI acabo en '%s' en %s" % (concl, sha[:7]))

            pr = estado_fusion(numero)
            if pr.get("mergeable_state") == "behind":
                log("      la rama se ha quedado atras: actualizando")
                api("/repos/%s/pulls/%d/update-branch" % (REPO, numero), "PUT")
                time.sleep(15)
                continue

            estado, res = api("/repos/%s/pulls/%d/merge" % (REPO, numero), "PUT",
                              {"merge_method": "merge"})
            if estado == 200:
                log("      fusionada: %s\n" % res.get("sha", "")[:7])
                break
            log("      la fusion no salio (%s): %s" % (estado, res.get("message")))
            time.sleep(20)
        else:
            sys.exit("      PARO: no consegui fusionar %s" % rama)

        git("fetch", "-q", "origin", "--prune")

    git("fetch", "-q", "origin", "--prune")

    if detenidas:
        # Ultima linea y codigo de salida distinto de cero: asi tampoco se
        # pierde cuando la salida se mira con un tail.
        log("=== fusionadas menos %d ===" % len(detenidas))
        log(git("log", "--oneline", "-8", "origin/main"))
        log("")
        log("NO SE FUSIONARON: %s" % ", ".join(detenidas))
        sys.exit(1)

    log("=== todas fusionadas ===")
    log(git("log", "--oneline", "-12", "origin/main"))


if __name__ == "__main__":
    main()
