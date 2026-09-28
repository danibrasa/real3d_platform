#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Que dicen los proyectos recogidos sobre las decisiones de producto abiertas.

No es un informe de mercado: es responder con datos a preguntas concretas que
ahora mismo estamos contestando por intuicion.

Uso: analizar-prospectos.py [fichero.json]
"""

import collections
import json
import sys

ruta = sys.argv[1] if len(sys.argv) > 1 else "/tmp/prospectos.json"
filas = json.load(open(ruta, encoding="utf-8"))

print("=" * 68)
print("%d proyectos de obra nueva publicados" % len(filas))
print("=" * 68)


def reparto(campo, titulo, tope=12):
    valores = [f[campo] for f in filas if f.get(campo)]
    cuenta = collections.Counter(valores)
    print("\n%s (%d con dato, %d sin el)"
          % (titulo, len(valores), len(filas) - len(valores)))
    for v, n in cuenta.most_common(tope):
        print("   %-40s %s" % (v[:40], "#" * n + " " + str(n)))


# --- Quien construye -------------------------------------------------------
promotoras = [f["promotora"] for f in filas if f.get("promotora")]
print("\nPROMOTORAS: %d distintas en %d proyectos"
      % (len(set(promotoras)), len(promotoras)))
repetidas = [(p, n) for p, n in collections.Counter(promotoras).most_common() if n > 1]
if repetidas:
    print("   con mas de un proyecto:")
    for p, n in repetidas:
        print("     %-38s %d proyectos" % (p[:38], n))
else:
    print("   ninguna repite: mercado muy repartido, muchos clientes pequenos")
    print("   en vez de pocos grandes.")

reparto("tipo", "TIPO DE PROYECTO")
reparto("estado", "ESTADO DE LA OBRA")


# --- Donde -----------------------------------------------------------------
def zona_corta(z):
    if not z:
        return None
    for clave in ("Punta Cana", "Bavaro", "Cap Cana", "Las Terrenas",
                  "Santo Domingo", "Santiago", "Puerto Plata", "Cabarete",
                  "Juan Dolio", "La Romana", "Jarabacoa", "Samana",
                  "Miches", "Sosua"):
        if clave.lower() in z.lower():
            return clave
    return z.split(",")[-1].strip()


for f in filas:
    f["zona_corta"] = zona_corta(f.get("zona"))
reparto("zona_corta", "ZONA")


# --- Precio ----------------------------------------------------------------
precios = sorted(f["precio_desde_usd"] for f in filas if f.get("precio_desde_usd"))
if precios:
    medio = precios[len(precios) // 2]
    print("\nPRECIO DE ENTRADA (desde, USD) -- %d con dato" % len(precios))
    print("   minimo   %s" % f"{precios[0]:,}")
    print("   mediana  %s" % f"{medio:,}")
    print("   maximo   %s" % f"{precios[-1]:,}")

    # Lo que de verdad decide si el producto es caro.
    for comision in (0.03, 0.05):
        print("   con una comision del %d%%, la primera venta deja %s USD"
              % (comision * 100, f"{int(medio * comision):,}"))

    print("\n   Professional cuesta 1.430 USD/ano.")
    print("   Al precio mediano y 3%% de comision: se paga con %.1f viviendas al ano."
          % (1430 / (medio * 0.03)))


# --- El hueco --------------------------------------------------------------
#
# Dos preguntas distintas, y hacen falta las dos: una promotora puede tener
# visor y no nombrarlo, o nombrarlo y no tenerlo. Lo dicho se mide en el texto;
# lo puesto, en si hay un iframe de Matterport y companía.
mirados = [f for f in filas if f.get("anuncia_3d") is not None]
dicho = [f for f in mirados if f["anuncia_3d"]]
puesto = [f for f in mirados if f.get("visor_incrustado")]

print("\nVISOR 3D (en la ficha del proyecto, no en la tarjeta del listado)")
print("   fichas leidas:            %d de %d" % (len(mirados), len(filas)))

if not mirados:
    print("   ninguna legible: de esto no se concluye nada.")
else:
    print("   lo nombran en el texto:   %d" % len(dicho))
    print("   lo tienen incrustado:     %d" % len(puesto))
    for f in dicho:
        print("     dice:  %-30s %s" % (f["nombre"][:30], f.get("\u0073e\u00f1al_3d")))
    for f in puesto:
        print("     tiene: %-30s %s" % (f["nombre"][:30],
                                        f.get("marca_visor") or f.get("iframes_ajenos")))
    if not dicho and not puesto:
        print("   ninguno, por las dos vias. Que coincidan importa: una sola")
        print("   podria ser un fallo de medida; las dos a la vez, ya no.")


# --- Con quien se podra hablar (cuando el producto este listo) --------------
print("\nCONTACTO DE LA PROMOTORA (para cuando toque, no para ahora)")
if not mirados:
    print("   no se miraron las fichas.")
else:
    filtrado = any(f.get("contacto_filtrado") for f in mirados)
    for clave, etiqueta in (("telefono", "telefono"), ("email", "correo"),
                            ("whatsapp", "whatsapp"), ("web", "web propia")):
        n = sum(1 for f in mirados if (f.get("contacto") or {}).get(clave))
        print("   %-10s %2d de %d" % (etiqueta, n, len(mirados)))

    alcanzables = [f for f in mirados if f.get("contacto_hay")]
    conocidas = {f["promotora"] for f in mirados if f.get("promotora")}
    empresas = {f["promotora"] for f in alcanzables if f.get("promotora")}
    print("   ---")
    # Lo que importa no es el numero de proyectos sino el de promotoras: a una
    # con cuatro proyectos se le escribe una vez, no cuatro.
    print("   promotoras localizables aqui:     %d de %d" % (len(empresas), len(conocidas)))

    if filtrado and not alcanzables:
        print()
        print("   Cero no es un fallo de medida: el portal se interpone. Todas")
        print("   las fichas llevan SU telefono y SU whatsapp, el mismo en las")
        print("   %d, con el nombre del proyecto en el texto prerrellenado." % len(mirados))
        print("   La promotora no aparece por ningun lado.")
        print()
        print("   Consecuencia practica: este portal sirve para saber QUIEN")
        print("   construye QUE, no para llegar a ella. Para eso hace falta otra")
        print("   fuente: su propia web, el registro, o el portal mismo como")
        print("   intermediario, que es a fin de cuentas su negocio.")


# --- Lo que falta ----------------------------------------------------------
print("\nCALIDAD DEL DATO")
for campo in ("promotora", "zona", "precio_desde_usd", "tipo", "estado"):
    faltan = sum(1 for f in filas if not f.get(campo))
    print("   %-18s falta en %d de %d" % (campo, faltan, len(filas)))

print("\nCada fila lleva su enlace de origen: nada de esto es dato hasta abrirlo.")
print("De aqui no sale ningun envio.")
