#!/usr/bin/env bash
#
# Minifica el HTML ya generado en ./docs.
#
# Se usa igual en local, dentro del contenedor y en CI. El binario (minhtml, de
# minify-html) se descarga la primera vez y queda cacheado en var/bin/, que no
# se versiona; si ya está en el PATH se usa ese.
#
#   bin/minify-docs.sh              # minifica ./docs
#   bin/minify-docs.sh ruta/otra    # minifica otra carpeta
#
set -euo pipefail

MINHTML_VERSION="${MINHTML_VERSION:-0.18.1}"
DESTINO="${1:-docs}"

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CACHE="$RAIZ/var/bin"
BINARIO="$CACHE/minhtml"

# minhtml del sistema, si lo hay
if command -v minhtml > /dev/null 2>&1; then
    BINARIO="$(command -v minhtml)"
fi

if [ ! -x "$BINARIO" ]; then
    case "$(uname -s)-$(uname -m)" in
        Linux-x86_64)   ARCHIVO="minhtml-${MINHTML_VERSION}-x86_64-unknown-linux-gnu" ;;
        Linux-aarch64)  ARCHIVO="minhtml-${MINHTML_VERSION}-aarch64-unknown-linux-gnu" ;;
        Darwin-x86_64)  ARCHIVO="minhtml-${MINHTML_VERSION}-x86_64-apple-darwin" ;;
        Darwin-arm64)   ARCHIVO="minhtml-${MINHTML_VERSION}-aarch64-apple-darwin" ;;
        *)
            echo "Plataforma no contemplada: $(uname -s)-$(uname -m)" >&2
            echo "Instala minhtml manualmente y vuelve a lanzar el script." >&2
            exit 1
            ;;
    esac

    echo "Descargando minhtml ${MINHTML_VERSION}…"
    mkdir -p "$CACHE"
    curl -fsSL -o "$BINARIO" \
        "https://github.com/wilsonzlin/minify-html/releases/download/v${MINHTML_VERSION}/${ARCHIVO}"
    chmod +x "$BINARIO"
fi

if [ ! -d "$DESTINO" ]; then
    echo "No existe la carpeta '$DESTINO'. Genera antes el sitio estático." >&2
    exit 1
fi

TOTAL=$(find "$DESTINO" -name '*.html' | wc -l)
if [ "$TOTAL" -eq 0 ]; then
    echo "No hay ficheros .html en '$DESTINO'." >&2
    exit 1
fi

# du -sk: kilobytes, disponible por igual en GNU y BSD
antes=$(du -sk "$DESTINO" | cut -f1)

# --keep-closing-tags y --keep-html-and-head-opening-tags mantienen el marcado
# explícito: los rastreadores de Telegram, WhatsApp y Facebook leen las meta
# etiquetas de <head> y conviene no darles sorpresas.
NUCLEOS=$(nproc 2> /dev/null || sysctl -n hw.ncpu 2> /dev/null || echo 4)

echo "Minificando ${TOTAL} ficheros HTML de '${DESTINO}' con ${NUCLEOS} procesos…"

# minhtml escribe en stdout el nombre de cada fichero: con 16.000 páginas es
# ruido puro. Los errores siguen apareciendo por stderr.
find "$DESTINO" -name '*.html' -print0 \
    | xargs -0 -n 200 -P "$NUCLEOS" "$BINARIO" --keep-closing-tags --keep-html-and-head-opening-tags \
    > /dev/null

despues=$(du -sk "$DESTINO" | cut -f1)

resumen=$(awk -v a="$antes" -v d="$despues" -v ruta="$DESTINO" \
    'BEGIN { printf "%s: %.1f MB → %.1f MB (%d%% menos)", ruta, a/1024, d/1024, 100 - (d*100/a) }')

echo "$resumen"

# En CI, deja constancia en el resumen de la ejecución
if [ -n "${GITHUB_STEP_SUMMARY:-}" ]; then
    printf '### HTML minificado\n\n%s\n' "$resumen" >> "$GITHUB_STEP_SUMMARY"
fi
