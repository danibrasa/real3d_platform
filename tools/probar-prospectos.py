#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Las tres medidas falsas de prospectos.py, clavadas para que no vuelvan.

Cada caso de aqui es una cadena real sacada del HTML que engano a una version
anterior del extractor. Las tres devolvian exito y un numero redondo:

  - width="360" del logo del portal          ->  "21 de 21 tienen visor 3D"
  - "Sky bar con vista 360"                  ->  lo mismo, y es un bar
  - el 809-608-4271 del portal en las 21     ->  "21 promotoras localizables"

No se prueba que las funciones existan: se prueba que distinguen. Es la
diferencia entre un test y un saludo.

Se ejecuta solo (python3 tools/probar-prospectos.py) y tambien desde
php artisan test, via tests/Feature/ProspectosTest.php, que es como entra en
el CI sin tener que anadirle un paso.
"""

import os
import sys
import unittest

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import prospectos as P  # noqa: E402


class SenalesDe3D(unittest.TestCase):
    """Lo que dice la ficha. El 360 pelado casa con media pagina."""

    def test_el_ancho_del_logo_no_es_un_visor(self):
        # Esta linea sale en las 21 fichas: es el logo del portal.
        html = '<img src="/uploads/logo-main-1.svg" width="360" height="80">'
        self.assertIsNone(P.SEÑALES_3D.search(html))

    def test_una_vista_panoramica_no_es_un_visor(self):
        # En inmobiliaria dominicana "vista 360" es la vista DESDE el
        # edificio, que es justo lo contrario de poder verlo por dentro.
        for texto in ('<div class="prv-amenidad">Sky bar con vista 360</div>',
                      'Acabados de lujo, vistas 360° y acceso a la zona.',
                      'Terraza con vista 360 grados al mar'):
            with self.subTest(texto=texto):
                self.assertIsNone(P.SEÑALES_3D.search(texto))

    def test_un_recorrido_si_lo_es(self):
        for texto in ("Tour virtual disponible", "recorrido virtual del piso",
                      "Visita nuestro tour 360", "recorrido 360 de la vivienda",
                      "Ver en Matterport", "visor 3D interactivo",
                      "maqueta interactiva del proyecto"):
            with self.subTest(texto=texto):
                self.assertIsNotNone(P.SEÑALES_3D.search(texto))


class VisorIncrustado(unittest.TestCase):
    """Lo que tiene la ficha. No depende de como lo llamen."""

    def test_un_iframe_de_matterport_cuenta(self):
        html = '<iframe src="https://my.matterport.com/show/?m=abc"></iframe>'
        self.assertEqual(P.mirar_ficha(html)["visor_incrustado"], True)

    def test_un_video_de_youtube_no_cuenta(self):
        html = '<iframe src="https://www.youtube.com/embed/abc"></iframe>'
        self.assertEqual(P.mirar_ficha(html)["visor_incrustado"], False)

    def test_un_mapa_de_google_no_cuenta(self):
        html = '<iframe src="https://www.google.com/maps/embed?pb=x"></iframe>'
        self.assertEqual(P.mirar_ficha(html)["visor_incrustado"], False)

    def test_el_visor_dentro_de_un_script_tambien(self):
        # DECORADO tira los <script> antes de buscar texto, asi que el
        # incrustado tiene que mirarse sobre el HTML entero.
        html = '<script>new Pannellum("#visor", {})</script>'
        self.assertEqual(P.mirar_ficha(html)["visor_incrustado"], True)


class LoQueSaleEnTodas(unittest.TestCase):
    """Un telefono repetido en las 21 fichas es del portal, no de nadie."""

    def fichas(self, cuantas, telefono, propio_en=None):
        filas = []
        for i in range(cuantas):
            tel = [telefono]
            if propio_en is not None and i == propio_en:
                tel.append("809-555-1234")
            filas.append({"promotora": "Promotora %d" % i,
                          "contacto": {"telefono": tel, "email": None,
                                       "whatsapp": None, "web": None}})
        return filas

    def test_el_repetido_se_va(self):
        filas = self.fichas(21, "809-608-4271")
        P.quitar_lo_que_sale_en_todas(filas)
        self.assertTrue(all(f["contacto"]["telefono"] is None for f in filas))
        self.assertTrue(all(f["contacto_hay"] is False for f in filas))

    def test_el_propio_se_queda(self):
        filas = self.fichas(21, "809-608-4271", propio_en=7)
        P.quitar_lo_que_sale_en_todas(filas)
        self.assertEqual(filas[7]["contacto"]["telefono"], ["809-555-1234"])
        self.assertTrue(filas[7]["contacto_hay"])
        self.assertIsNone(filas[3]["contacto"]["telefono"])

    def test_el_whatsapp_se_compara_por_el_numero(self):
        # El texto prerrellenado lleva el nombre del proyecto, asi que los 21
        # enlaces son distintos aunque el numero sea el mismo.
        filas = [{"promotora": "P%d" % i,
                  "contacto": {"whatsapp": ["wa.me/18096084271?text=proyecto%d" % i],
                               "telefono": None, "email": None, "web": None}}
                 for i in range(21)]
        P.quitar_lo_que_sale_en_todas(filas)
        self.assertTrue(all(f["contacto"]["whatsapp"] is None for f in filas))

    def test_con_pocas_fichas_no_se_filtra_y_se_dice(self):
        # Con tres fichas cualquier coincidencia parece decorado. Antes de
        # tirar dato bueno, mejor no filtrar y que conste que no se filtro.
        filas = self.fichas(3, "809-608-4271")
        P.quitar_lo_que_sale_en_todas(filas)
        self.assertTrue(all(f["contacto"]["telefono"] for f in filas))
        self.assertTrue(all(f["contacto_filtrado"] is False for f in filas))

    def test_un_paquete_del_cdn_no_es_un_correo(self):
        filas = [{"promotora": "P%d" % i,
                  "contacto": {"email": ["tailwindcss@2.2.19"], "telefono": None,
                               "whatsapp": None, "web": None}} for i in range(21)]
        P.quitar_lo_que_sale_en_todas(filas)
        self.assertTrue(all(f["contacto"]["email"] is None for f in filas))


class LecturaDeLaTarjeta(unittest.TestCase):
    """El extractor que devolvio 21 proyectos con todos los campos vacios."""

    TARJETA = (
        '<a href="https://portalinmobiliariord.com/proyectos/x/" class="prv-project-card">'
        '<span class="prv-project-badge">En Construcción</span>'
        '<h2 class="prv-project-name">Bávaro Green Park</h2>'
        '<p class="prv-project-loc">Bávaro, La Altagracia</p>'
        '<span class="prv-project-type">Residencial</span>'
        '<span class="prv-project-price">Desde USD 115,000</span>'
        '<span class="prv-project-dev">Green Park Group</span></a>')

    def test_saca_los_campos_y_no_cadenas_vacias(self):
        m = P.TARJETA.search(self.TARJETA)
        self.assertIsNotNone(m, "la tarjeta no casa: el marcado habra cambiado")
        cuerpo = m.group("cuerpo")
        for campo, esperado in (("nombre", "Bávaro Green Park"),
                                ("zona", "Bávaro, La Altagracia"),
                                ("tipo", "Residencial"),
                                ("estado", "En Construcción"),
                                ("promotora", "Green Park Group")):
            with self.subTest(campo=campo):
                hallado = P.CAMPOS[campo].search(cuerpo)
                self.assertIsNotNone(hallado, "%s no casa" % campo)
                self.assertEqual(P.limpiar(hallado.group(1)), esperado)

    def test_el_precio_en_numero(self):
        self.assertEqual(P.a_numero("Desde USD 115,000"), 115000)
        self.assertEqual(P.a_numero("RD$ 8.500.000"), 8500000)
        self.assertIsNone(P.a_numero("Precio a consultar"))
        self.assertIsNone(P.a_numero(None))


class Robots(unittest.TestCase):
    """Se comprobaba una vez y valia para las veintitantas peticiones."""

    def setUp(self):
        self.original = P._REGLAS.copy()

    def tearDown(self):
        P._REGLAS.clear()
        P._REGLAS.update(self.original)

    def test_se_pregunta_por_cada_ruta(self):
        P._REGLAS["ejemplo.test"] = ["/proyectos/privado"]
        self.assertTrue(P.permitido("https://ejemplo.test/proyectos/"))
        self.assertFalse(P.permitido("https://ejemplo.test/proyectos/privado/x/"))

    def test_el_sitio_entero_cerrado_se_respeta(self):
        # "Disallow: /" se descartaba junto con las reglas vacias, asi que el
        # unico caso en que decir que no es obligatorio era el que se saltaba.
        P._REGLAS["cerrado.test"] = ["/"]
        self.assertFalse(P.permitido("https://cerrado.test/lo-que-sea"))

    def test_sin_robots_legible_no_se_pide_nada(self):
        P._REGLAS["mudo.test"] = None
        self.assertFalse(P.permitido("https://mudo.test/proyectos/"))

    def test_pedir_si_permite_no_pide_lo_prohibido(self):
        P._REGLAS["cerrado.test"] = ["/"]
        with self.assertRaises(P.NoPermitido):
            P.pedir_si_permite("https://cerrado.test/proyectos/")


if __name__ == "__main__":
    unittest.main(verbosity=2)
