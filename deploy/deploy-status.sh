#!/bin/bash
# Que hay en cada entorno y que falta por desplegar.
#
# Responde de un vistazo a "que version esta en la web", "que esta esperando en
# staging" y "que pull requests hay fusionados sin publicar". Solo lee: no
# despliega, no cambia ramas, no toca la base de datos.
#
# Uso: deploy-status.sh
set -uo pipefail

# Sirve desde cualquiera de las maquinas: se usa el primer repositorio que haya.
REPO=""
for r in /var/www/dev /var/www/staging /var/www/real3d/current; do
    [ -d "$r/.git" ] && REPO="$r" && break
done
[ -n "$REPO" ] || { echo "no encuentro ningun clon del repositorio"; exit 1; }

cd "$REPO"
git fetch -q origin --prune --tags 2>/dev/null

# Un campo de version.json sin depender de jq, que no esta instalado en las VMs.
campo() { echo "${1:-}" | tr ',' '\n' | grep "\"$2\"" | cut -d'"' -f4; }

echo "== entornos"
printf '%-12s %-10s %-9s %-16s %s\n' ENTORNO VERSION COMMIT DESPLEGADO URL

PROD=$(curl -sL --max-time 15 https://real3d.io/version 2>/dev/null)
PROD_SHA=$(campo "$PROD" commit)
printf '%-12s %-10s %-9s %-16s %s\n' produccion \
    "$(campo "$PROD" release)" "$(campo "$PROD" commit_short)" \
    "$(campo "$PROD" deployed_at)" https://real3d.io

# Staging va con contraseña; el fichero solo existe en la maquina que lo sirve.
STG=""
if [ -r /root/.staging-web-pass ]; then
    STG=$(curl -sL --max-time 15 -u "real3d:$(cat /root/.staging-web-pass)" \
        https://staging.real3d.io/version 2>/dev/null)
fi
if [ -n "$STG" ]; then
    printf '%-12s %-10s %-9s %-16s %s\n' staging \
        "$(campo "$STG" release)" "$(campo "$STG" commit_short)" \
        "$(campo "$STG" deployed_at)" https://staging.real3d.io
else
    printf '%-12s %s\n' staging "(sin credenciales en esta maquina para consultarlo)"
fi

echo
echo "== ramas"
printf '%-12s %s\n' main "$(git log --oneline -1 origin/main 2>/dev/null)"
if git rev-parse -q --verify origin/production >/dev/null; then
    printf '%-12s %s\n' production "$(git log --oneline -1 origin/production)"

    # La rama la mueve el workflow al terminar bien. Si no coincide con lo que
    # sirve la web, alguien ha desplegado a mano por SSH o el paso final fallo:
    # en ese caso manda la web, no la rama.
    RAMA_SHA=$(git rev-parse origin/production)
    if [ -n "$PROD_SHA" ] && [ "$RAMA_SHA" != "$PROD_SHA" ]; then
        echo
        echo "   AVISO: la rama production dice ${RAMA_SHA:0:7} pero la web sirve ${PROD_SHA:0:7}."
        echo "   Manda la web. Suele significar un despliegue a mano por SSH."
    fi
else
    printf '%-12s %s\n' production "(no existe todavia; la crea el primer despliegue)"
fi

echo
echo "== fusionado en main y sin publicar"
if [ -n "$PROD_SHA" ] && git cat-file -e "$PROD_SHA^{commit}" 2>/dev/null; then
    PENDIENTE=$(git log --oneline "$PROD_SHA..origin/main" 2>/dev/null)
    if [ -z "$PENDIENTE" ]; then
        echo "nada: produccion esta al dia con main"
    else
        echo "$PENDIENTE" | sed 's/^/  /'
        echo
        echo "  $(echo "$PENDIENTE" | wc -l) commit(s) esperando aprobacion para salir a produccion."
    fi
else
    echo "no puedo comparar: el commit de produccion no esta en este clon"
    echo "(los clones de despliegue son superficiales, --depth 20)"
fi
