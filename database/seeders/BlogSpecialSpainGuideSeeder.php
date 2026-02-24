<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogSpecialSpainGuideSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('role', 'superadmin')->first()
            ?? User::first();

        $categoryLegal = BlogCategory::where('slug', 'legal-fiscal')->first();

        // Tags
        $tagSlugs = [
            'inversion-extranjera' => ['name' => 'Inversion Extranjera', 'name_en' => 'Foreign Investment'],
            'espanoles' => ['name' => 'Inversores Espanoles', 'name_en' => 'Spanish Investors'],
            'fiscalidad' => ['name' => 'Fiscalidad', 'name_en' => 'Taxation'],
            'alquiler-vacacional' => ['name' => 'Alquiler Vacacional', 'name_en' => 'Vacation Rental'],
            'confotur' => ['name' => 'CONFOTUR', 'name_en' => 'CONFOTUR'],
            'punta-cana' => ['name' => 'Punta Cana', 'name_en' => 'Punta Cana'],
        ];

        $tagIds = [];
        foreach ($tagSlugs as $slug => $data) {
            $tag = BlogTag::firstOrCreate(
                ['slug' => $slug],
                ['name' => $data['name'], 'name_en' => $data['name_en']]
            );
            $tagIds[] = $tag->id;
        }

        // ─── Article ───────────────────────────────────────────

        $post = BlogPost::firstOrCreate(
            ['slug' => 'guia-completa-espanol-comprar-punta-cana-invertir'],
            [
                'title' => 'Guia Completa: Como Comprar una Propiedad en Punta Cana Siendo Espanol y Rentabilizarla con Alquiler Vacacional',
                'title_en' => 'Complete Guide: How to Buy Property in Punta Cana as a Spanish Citizen and Earn Vacation Rental Income',

                'excerpt' => 'Todo lo que un ciudadano espanol con residencia fiscal en Espana necesita saber para comprar un inmueble en Punta Cana, gestionar los aspectos legales y fiscales en ambos paises, y explotarlo como alquiler vacacional con un operador local. Proceso paso a paso con ejemplos reales y numeros.',
                'excerpt_en' => 'Everything a Spanish citizen with tax residency in Spain needs to know to buy property in Punta Cana, manage legal and tax aspects in both countries, and operate it as a vacation rental with a local manager. Step-by-step process with real examples and numbers.',

                'body' => $this->bodyEs(),
                'body_en' => $this->bodyEn(),

                'featured_image_path' => null,
                'featured_image_alt' => 'Inversor espanol firmando contrato de compra de propiedad en Punta Cana',
                'featured_image_alt_en' => 'Spanish investor signing property purchase contract in Punta Cana',

                'category_id' => $categoryLegal?->id,
                'author_id' => $author?->id,

                'status' => 'published',
                'published_at' => now(),

                'meta_title' => 'Comprar Propiedad en Punta Cana Siendo Espanol | Guia Legal y Fiscal Completa 2026',
                'meta_title_en' => 'Buy Property in Punta Cana as a Spanish Citizen | Complete Legal & Tax Guide 2026',
                'meta_description' => 'Guia paso a paso para espanoles: comprar inmueble en Punta Cana, fiscalidad en Espana y RD, Ley CONFOTUR, Modelo 720, alquiler vacacional y rentabilidad real con ejemplos.',
                'meta_description_en' => 'Step-by-step guide for Spanish citizens: buying property in Punta Cana, taxation in Spain and DR, CONFOTUR Law, Modelo 720, vacation rental and real ROI with examples.',
                'meta_keywords' => 'comprar propiedad punta cana espanol, inversion inmobiliaria republica dominicana espana, fiscalidad inmueble extranjero espana, modelo 720, CONFOTUR, alquiler vacacional punta cana, doble imposicion espana republica dominicana',

                'is_featured' => true,
                'views_count' => 0,
            ]
        );

        $post->tags()->syncWithoutDetaching($tagIds);

        $this->command->info("Special guide post created/updated: {$post->title}");
    }

    private function bodyEs(): string
    {
        return <<<'MARKDOWN'
## Introduccion

Cada vez mas espanoles miran hacia el Caribe como destino de inversion inmobiliaria. Punta Cana, con mas de 7 millones de turistas al ano, un convenio de doble imposicion con Espana vigente desde 2014 y una ley de incentivos fiscales (CONFOTUR) que puede eliminar impuestos durante 15 anos, se ha convertido en uno de los destinos mas atractivos para el inversor espanol.

Pero el proceso tiene complejidades: hay que navegar dos sistemas fiscales, entender la operativa legal dominicana, elegir bien el operador de alquiler vacacional y, sobre todo, tener claros los numeros.

**Esta guia cubre absolutamente todo el proceso**, desde la decision inicial hasta que recibes tu primer ingreso por alquiler, con ejemplos y cifras reales.

---

## Paso 1: Preparacion Desde Espana

### 1.1 Documentacion que necesitas

Como ciudadano espanol **no necesitas visado** para entrar a Republica Dominicana. Recibes un permiso de turista de 30 dias a tu llegada, suficiente para iniciar el proceso de compra.

Documentos que debes preparar **antes de viajar**:

- **Pasaporte vigente** (minimo 6 meses de validez)
- **Certificado de residencia fiscal** — Solicitar en la AEAT (Agencia Tributaria). Necesario para aplicar el Convenio de Doble Imposicion (CDI) con RD.
- **Prueba de fondos** — Extractos bancarios de los ultimos 3-6 meses. Se necesitan para cumplimiento anti-lavado.
- **Apostilla de La Haya** — Cualquier documento espanol que se use en RD debe llevar apostilla.
- **Poder notarial** (opcional pero recomendado) — Si no puedes estar presente en todos los pasos, un poder otorgado ante notario espanol (apostillado) permite que tu abogado dominicano actue en tu nombre.

### 1.2 Contratar un abogado en RD

**Este es el paso mas importante.** No compres nunca un inmueble en el extranjero sin representacion legal local.

Un abogado inmobiliario en Republica Dominicana te costara entre el **1% y el 1.5% del precio de compra**. Sus funciones:

- Obtener tu **RNC** (Registro Nacional del Contribuyente) — el NIF dominicano
- Realizar el **due diligence** completo del inmueble
- Verificar que el proyecto tiene certificacion **CONFOTUR**
- Redactar o revisar los contratos
- Representarte ante el Registro de Titulos y la DGII
- Gestionar el pago de impuestos de transferencia

> **Consejo:** Busca abogados con experiencia especifica en inversores espanoles. Muchos despachos en Punta Cana tienen personal hispanohablante (obviamente) y entienden las implicaciones fiscales del CDI Espana-RD.

---

## Paso 2: Seleccion del Inmueble

### 2.1 Zonas principales de Punta Cana

| Zona | Precio medio 1BR | Perfil | Rentabilidad alquiler |
|------|-------------------|--------|----------------------|
| **Bavaro** | $95,000 - $160,000 | Turismo masivo, maxima ocupacion | Alta (70-85% ocupacion) |
| **Cap Cana** | $154,000 - $220,000 | Premium, golf, marina, playas privadas | Media-alta, tarifa premium |
| **Cocotal / Vista Cana** | $110,000 - $170,000 | Residencial, golf, tranquilo | Media, buena revalorizacion |
| **Veron / Downtown** | $80,000 - $120,000 | Mas asequible, zona local | Alta ocupacion, tarifa menor |

### 2.2 Preventa vs. entrega inmediata

**Preventa (en construccion):**
- Precios un **15-25% por debajo** del valor de mercado al completarse
- Financiacion directa del promotor sin intereses (tipicamente 30/70 o 40/60)
- Plazo de entrega: 12-24 meses
- Riesgo: retrasos en construccion, cambios en acabados

**Entrega inmediata (reventa o proyecto terminado):**
- Puedes empezar a generar ingresos desde el dia 1
- Precio de mercado completo
- Financiacion solo via banco dominicano o fondos propios

> **Para un inversor espanol que busca rentabilidad por alquiler**, la preventa es la opcion mas inteligente financieramente: pagas en cuotas sin intereses durante la construccion y compras por debajo del valor de mercado. El inconveniente es que no generas ingresos hasta la entrega.

### 2.3 Que buscar: checklist

- ✅ Proyecto con certificacion **CONFOTUR** vigente
- ✅ Titulo de propiedad limpio (tu abogado lo verifica)
- ✅ Promotor con historial de proyectos entregados
- ✅ Zona con alta demanda turistica y buenas resenas en Airbnb/Booking
- ✅ Amenidades que atraen al turista: piscina, seguridad 24h, parking, cercania a la playa
- ✅ Administracion o rental pool disponible en el complejo

---

## Paso 3: El Proceso de Compra

### 3.1 Reserva

Firmas un **contrato de reserva** y pagas un deposito de **$1,000 a $5,000 USD**. Este monto es descontable del precio final. La reserva te da exclusividad sobre la unidad durante un periodo (tipicamente 15-30 dias) mientras se prepara el contrato formal.

### 3.2 Contrato de Promesa de Venta

Es el contrato principal. Incluye:

- Precio total y forma de pago
- Cronograma de pagos (si es preventa)
- Fecha estimada de entrega
- Penalizaciones por incumplimiento
- Condiciones de cancelacion

Al firmar este contrato, pagas el **10% del precio** como entrada (a cuenta del total).

**Ejemplo para un apartamento de $150,000 USD en Bavaro (preventa):**

| Concepto | Monto | Momento |
|----------|-------|---------|
| Reserva | $3,000 | Al reservar |
| Firma de promesa (10%) | $12,000 | A los 15-30 dias |
| Cuotas durante construccion (20%) | $30,000 | 18 meses (~$1,667/mes) |
| Entrega (70%) | $105,000 | Al recibir llaves |
| **Total** | **$150,000** | |

### 3.3 Due Diligence (30-60 dias)

Tu abogado verifica en paralelo:

1. **Titulo de propiedad** en el Registro de Titulos — que este limpio, sin cargas ni embargos
2. **Certificacion CONFOTUR** del proyecto — numero de resolucion, impuestos exentos y periodo
3. **Permisos de construccion** — licencia municipal, aprobacion del MOPC
4. **Situacion fiscal del promotor** — que este al dia con la DGII
5. **Deslinde** — verificacion de linderos y superficie real

### 3.4 Acto de Venta y Transferencia de Titulo

Al completar el pago total:

1. Se firma el **acto de venta** ante notario dominicano
2. Se paga el **impuesto de transferencia** a la DGII (3% del valor catastral, **exento si tiene CONFOTUR**)
3. Se registra la propiedad en el **Registro de Titulos**
4. Recibes tu **Certificado de Titulo** a tu nombre

**Tiempo total** desde la firma hasta tener titulo: aproximadamente 60-90 dias para inmuebles terminados.

---

## Paso 4: Costes de Compra Detallados

### 4.1 Sin CONFOTUR

| Concepto | % sobre precio | En un piso de $150,000 |
|----------|---------------|----------------------|
| Impuesto de transferencia | 3% | $4,500 |
| Honorarios abogado | 1.5% | $2,250 |
| Notario | ~1% | $1,500 |
| Registro de titulo | ~1% | $1,500 |
| ITBIS sobre servicios profesionales | 18% de ~$3,750 | $675 |
| **Total costes de cierre** | **~6.9%** | **$10,425** |

### 4.2 Con CONFOTUR

| Concepto | % sobre precio | En un piso de $150,000 |
|----------|---------------|----------------------|
| ~~Impuesto de transferencia~~ | ~~3%~~ **EXENTO** | $0 |
| Honorarios abogado | 1.5% | $2,250 |
| Notario | ~1% | $1,500 |
| Registro de titulo | ~1% | $1,500 |
| ITBIS sobre servicios profesionales | 18% de ~$3,750 | $675 |
| **Total costes de cierre** | **~3.9%** | **$5,925** |

> **Ahorro CONFOTUR en la compra: $4,500 USD** solo en el impuesto de transferencia. Pero el verdadero ahorro viene despues, con la exencion del IPI anual y del impuesto sobre la renta por alquileres.

---

## Paso 5: Fiscalidad — El Tema Clave Para Espanoles

Este es el apartado mas importante de toda la guia. Como residente fiscal espanol con un inmueble en el extranjero, tienes obligaciones en **ambos paises**.

### 5.1 En Republica Dominicana

#### Impuestos anuales sobre la propiedad

| Impuesto | Tasa | Condicion | Con CONFOTUR |
|----------|------|-----------|-------------|
| **IPI** (impuesto a la propiedad) | 1% anual | Sobre valor combinado de inmuebles que excedan ~$165,000 USD | **Exento 10-15 anos** |

#### Impuestos sobre ingresos por alquiler

| Impuesto | Tasa | Base | Con CONFOTUR |
|----------|------|------|-------------|
| **Impuesto sobre la renta** (no residente) | 27% | Renta neta (despues de gastos deducibles) | **Exento hasta 10 anos** |
| **ITBIS** (IVA dominicano) | 18% | Sobre servicios de alojamiento corto plazo | Se aplica siempre |

**Gastos deducibles** en RD (para calcular renta neta):
- Comisiones del operador/property manager
- Mantenimiento y reparaciones
- Seguros
- Cuotas de comunidad (HOA)
- Depreciacion del inmueble
- Suministros (internet, agua, electricidad de areas comunes)

### 5.2 En Espana

#### Modelo 720 — Declaracion de bienes en el extranjero

**Obligatorio** si el valor del inmueble supera los **50,000 EUR**.

- **Plazo:** Antes del 31 de marzo de cada ano
- **Que declaras:** Tipo de inmueble, direccion completa, fecha de adquisicion, valor de adquisicion, porcentaje de titularidad
- **Es solo informativo** — no se paga impuesto con este modelo
- **En anos sucesivos** solo se re-presenta si el valor sube mas de 20,000 EUR respecto a la ultima declaracion
- **Atencion:** La no presentacion sigue siendo infraccion grave en Espana, aunque las sanciones desproporcionadas se reformaron tras la sentencia del TJUE de 2022

#### IRPF — Rendimientos del capital inmobiliario

Los ingresos por alquiler del inmueble en Punta Cana **se declaran en tu Renta anual** como rendimientos del capital inmobiliario:

- Se suman a tu **base general** del IRPF (tributacion al tipo marginal: 19% a 47% segun tramo)
- Puedes deducir los gastos relacionados (los mismos que en RD: gestion, mantenimiento, seguros, amortizacion del inmueble, intereses de prestamo)
- **Deduccion por doble imposicion internacional:** El impuesto pagado en RD (27%) se descuenta de la cuota del IRPF espanol sobre esa misma renta

> **En la practica:** Si tu tipo marginal del IRPF es del 30% y ya pagaste un 27% en RD, solo pagarias un 3% adicional en Espana sobre esos ingresos. Si tu marginal es inferior al 27%, no pagarias nada adicional (pero tampoco te devuelven el exceso).

#### Renta imputada — Si el piso NO esta alquilado

Si el inmueble esta vacio parte del ano (no es tu vivienda habitual), Hacienda te **imputa una renta ficticia**:

- **1,1% del valor de adquisicion** para inmuebles en el extranjero (no hay valor catastral espanol)
- Proporcionalmente a los dias no alquilados
- Se suma a tu base general del IRPF

**Ejemplo:** Piso comprado por 140,000 EUR, vacio 3 meses al ano:
- Renta imputada = 140,000 x 1,1% x (90/365) = **$379 EUR** adicionales a tu base imponible

#### Plusvalia por venta

Si vendes el inmueble:
- La ganancia tributa en la **base del ahorro**: 19% (primeros 6,000 EUR), 21% (6,000-50,000), 23% (50,000-200,000), 27% (200,000-300,000), 28% (mas de 300,000)
- Se aplica el credito por doble imposicion del 27% pagado en RD
- **Obligacion adicional:** Si la inversion supera 300,000 EUR, debes presentar el **Modelo D-7A** ante el Ministerio de Economia dentro del mes siguiente a la operacion

### 5.3 Resumen fiscal: un ejemplo completo

**Perfil:** Maria, espanola, residente fiscal en Madrid, tipo marginal IRPF del 37%.
**Inmueble:** Apartamento 1BR en Bavaro, compra $150,000 USD (~140,000 EUR), proyecto CONFOTUR.

**Ingresos anuales por alquiler:** $25,000 USD brutos

| Concepto | En RD (USD) | En Espana (EUR) |
|----------|-------------|-----------------|
| Ingresos brutos alquiler | $25,000 | ~23,360 EUR |
| Gastos deducibles (operador 25%, HOA, mantenimiento, seguro) | -$9,500 | -8,875 EUR |
| **Renta neta** | **$15,500** | **14,485 EUR** |
| Impuesto en RD (27% no residente) | **EXENTO (CONFOTUR)** | — |
| IRPF Espana (37% marginal) | — | 5,359 EUR |
| Deduccion doble imposicion | — | 0 EUR (no pago en RD) |
| **Impuesto efectivo total** | **$0** | **~5,359 EUR** |

> **Con CONFOTUR, Maria solo paga impuestos en Espana.** Sin CONFOTUR, pagaria $4,185 USD en RD pero los descontaria del IRPF espanol (solo pagaria la diferencia).

**Importante:** Aunque con CONFOTUR no pagas impuesto sobre la renta en RD, **si debes pagar el ITBIS (18%)** sobre los servicios de alojamiento. Este impuesto lo suele gestionar el operador y se repercute al huesped.

---

## Paso 6: Financiacion

### 6.1 Opcion A — Financiacion del promotor (la mas comun)

La mayoria de promotores en Punta Cana ofrecen planes de pago **sin intereses durante la construccion**:

| Fase | Porcentaje | Ejemplo ($150,000) |
|------|-----------|-------------------|
| Reserva | 5-10% | $7,500 - $15,000 |
| Durante construccion (12-24 meses) | 20-30% | $30,000 - $45,000 |
| A la entrega | 60-70% | $90,000 - $105,000 |

**No requiere verificacion crediticia ni historial en RD.** Es simplemente un plan de pagos fraccionado.

### 6.2 Opcion B — Hipoteca dominicana

Si necesitas financiar la entrega (el 60-70% final):

| Parametro | Condiciones tipicas |
|-----------|-------------------|
| Financiacion maxima (LTV) | 60-70% del valor de tasacion |
| Tipo de interes | 8-11% en USD |
| Plazo | 15-20 anos |
| Ingresos minimos | $2,500-$4,000 USD/mes demostrables |
| Documentacion | Pasaporte, extractos bancarios (3-6 meses), declaracion de renta espanola (2 anos), RNC |

**Bancos que financian a extranjeros:** Banco Popular Dominicano, BHD Leon, Scotiabank RD, Banreservas.

> **Atencion:** Los tipos de interes en RD (8-11%) son significativamente mas altos que en Espana. Considera la Opcion C.

### 6.3 Opcion C — Apalancamiento con tu vivienda en Espana

Si tienes una vivienda en propiedad en Espana (con o sin hipoteca), puedes solicitar un **prestamo con garantia hipotecaria** (o ampliar tu hipoteca actual):

| Parametro | Condiciones tipicas |
|-----------|-------------------|
| Tipo de interes fijo | 2,5% - 3,5% |
| Tipo variable | Euribor + 1-2% |
| Plazo | Hasta 25-30 anos |
| Financiacion | Hasta 60-70% del valor de tasacion de tu vivienda en Espana (menos hipoteca pendiente) |

**Ejemplo:** Tu piso en Madrid vale 300,000 EUR con 100,000 EUR de hipoteca pendiente. Podrias obtener hasta (300,000 x 70%) - 100,000 = **110,000 EUR** adicionales al 3% de interes, en lugar del 9% en RD.

> **Esta es la opcion mas inteligente financieramente** si dispones de patrimonio en Espana. La diferencia de tipos de interes (3% vs 9%) supone un ahorro de miles de euros al ano.

### 6.4 Opcion D — Compra al contado

Si dispones de ahorro o liquidez suficiente. Ten en cuenta:

- Las transferencias internacionales deben hacerse a traves de canales bancarios formales
- El banco receptor en RD solicitara justificacion del origen de fondos
- Conserva todos los justificantes de transferencia (los necesitaras para el Modelo 720 y posible venta futura)
- Considera usar servicios como Wise o OFX para obtener mejores tipos de cambio que los bancos tradicionales

---

## Paso 7: Explotacion Vacacional con Operador Local

### 7.1 Modelo de operacion

Existen dos modelos principales:

**A) Rental Pool del complejo:**
- El promotor o una empresa asociada gestiona todas las unidades del complejo como un pool
- Ingresos se reparten entre propietarios participantes (por m2 o por unidad)
- Menos control, pero totalmente pasivo
- Comision: 30-40% de los ingresos brutos

**B) Operador independiente (property manager):**
- Contratas a un gestor que maneja TU unidad especificamente
- Publicacion en Airbnb, Booking.com, VRBO bajo tu cuenta o la del operador
- Mas control sobre precios, disponibilidad y uso personal
- Comision: 20-30% de los ingresos brutos (25% es lo estandar)

> **Recomendacion para inversores espanoles:** El operador independiente (Opcion B) te da mas control, transparencia y generalmente mejor rentabilidad. Pero si quieres maxima despreocupacion, el rental pool funciona.

### 7.2 Que hace un buen operador

Servicios que debe incluir la comision del 25%:

- **Marketing y distribucion:** Listing profesional en Airbnb, Booking.com, VRBO, Expedia. Fotos profesionales, descripcion optimizada
- **Revenue management:** Ajuste dinamico de precios segun temporada, demanda, eventos
- **Comunicacion con huespedes:** Respuestas 24/7, check-in/check-out
- **Limpieza y lavanderia:** Coordinacion de equipos, control de calidad
- **Mantenimiento:** Reparaciones menores, coordinacion con tecnicos para las mayores
- **Suministros:** Reposicion de amenities, ropa de cama
- **Cumplimiento fiscal:** Gestion del ITBIS, informes para la DGII
- **Reportes al propietario:** Estado de cuenta mensual con ingresos, gastos y ocupacion

### 7.3 Temporadas en Punta Cana

| Temporada | Meses | Ocupacion tipica | Tarifa nocturna (1BR) |
|-----------|-------|-------------------|---------------------|
| **Alta** | Dic - Abr | 80-95% | $120 - $180 USD |
| **Media** | May - Jun, Nov | 60-75% | $90 - $130 USD |
| **Baja** | Jul - Oct | 45-60% | $70 - $100 USD |

### 7.4 Uso personal

La mayoria de operadores te permiten **usar tu propiedad 2-4 semanas al ano** sin coste (fuera de temporada alta) o descontando solo la limpieza. Avisando con antelacion y respetando reservas existentes.

> **Dato fiscal:** Los dias que uses personalmente la propiedad **no generan renta** pero tampoco son deducibles. En Espana, esos dias computan como renta imputada (1,1% del valor de adquisicion prorrateado).

---

## Paso 8: Los Numeros — Ejemplo Completo de Rentabilidad

Vamos a poner numeros reales a una inversion tipica de un espanol en Punta Cana.

### Perfil de inversion

- **Inmueble:** Apartamento 1 dormitorio en Bavaro, 65 m2, proyecto CONFOTUR
- **Precio:** $150,000 USD (~140,000 EUR)
- **Forma de pago:** 50% al contado ($75,000) + hipoteca espanola $75,000 al 3% a 20 anos
- **Costes de cierre (con CONFOTUR):** ~$5,925

### Ingresos anuales por alquiler

| Concepto | Calculo | Importe |
|----------|---------|---------|
| Noches alquiladas (70% ocupacion) | 365 x 0.70 | 256 noches |
| Tarifa media noche | — | $110 USD |
| **Ingreso bruto anual** | 256 x $110 | **$28,160 USD** |

### Gastos anuales

| Concepto | Importe (USD) |
|----------|--------------|
| Comision operador (25%) | $7,040 |
| Cuota HOA / mantenimiento comunidad | $2,400 |
| Seguro | $600 |
| Internet + servicios | $960 |
| Mantenimiento y reparaciones | $1,200 |
| Cuota hipoteca espanola ($75,000 al 3%, 20 anos) | $4,992 (12 x $416) |
| Provisiones / imprevistos | $600 |
| **Total gastos** | **$17,792** |

### Resultado neto

| Concepto | Importe |
|----------|---------|
| Ingreso bruto | $28,160 |
| Total gastos | -$17,792 |
| **Flujo neto antes de impuestos** | **$10,368** |
| Impuesto RD (CONFOTUR = exento) | $0 |
| IRPF Espana (37% marginal sobre renta neta sin hipoteca) | ~$5,750 EUR* |
| Deduccion doble imposicion (RD) | $0 |
| **Flujo neto despues de impuestos** | **~$4,200 USD** |

*Nota: Los intereses de la hipoteca ($2,160 USD) son deducibles en IRPF espanol. La base imponible en Espana se calcula sobre la renta neta despues de todos los gastos deducibles.

### Rentabilidad total

| Metrica | Valor |
|---------|-------|
| **Rentabilidad bruta** (ingreso bruto / precio) | 18.8% |
| **Rentabilidad neta antes de impuestos** (flujo neto / inversion total) | 6.6% |
| **Cash-on-cash return** (flujo neto / capital desembolsado $80,925) | 5.2% |
| **Revalorizacion estimada anual** | 4-9% |
| **Rentabilidad total estimada** (cash flow + apreciacion) | 10-15% |

> **Comparativa:** Un piso de alquiler en Madrid capital rinde un 4-5% bruto. En Punta Cana, con CONFOTUR y buena gestion, puedes superar el 6% neto mas revalorizacion. Y eso sin contar que disfrutas del apartamento unas semanas al ano en el Caribe.

---

## Paso 9: Residencia en RD (Opcional Pero Beneficiosa)

Si tu inversion supera los $200,000 USD, puedes solicitar la **residencia por inversion** en Republica Dominicana:

| Parametro | Detalle |
|-----------|---------|
| Inversion minima | $200,000 USD en inmuebles |
| Permiso inicial | 1 ano |
| Renovacion | Cada 4 anos |
| Ciudadania | Posible tras 6 meses de residencia |

**Ventajas:**
- Cedula de identidad dominicana (facilita tramites bancarios y legales)
- Posibilidad de abrir cuenta bancaria local mas facilmente
- Sistema fiscal territorial: los primeros 3 anos solo tributas por rentas dominicanas
- No implica perder tu residencia fiscal espanola (puedes tener residencia legal en RD sin ser residente fiscal alli)

**Otras opciones de residencia:**
- **Rentista:** Demostrar $2,000 USD/mes de ingresos pasivos
- **Pensionista:** Demostrar $1,500 USD/mes de pension

> **Atencion:** Si pasas mas de 183 dias al ano en RD, podrias convertirte en residente fiscal dominicano y dejar de serlo espanol. Esto tiene implicaciones fiscales profundas. Consulta con tu asesor fiscal antes de dar este paso.

---

## Paso 10: Cronograma Completo

| Mes | Accion |
|-----|--------|
| **Mes 1** | Investigacion, seleccion de zona y proyecto, primer contacto con promotor |
| **Mes 2** | Viaje a Punta Cana, visita de proyectos, contratacion de abogado, firma de reserva |
| **Mes 3** | Due diligence, firma de contrato de promesa de venta, inicio de pagos |
| **Meses 4-18** | Pagos durante construccion (si es preventa) |
| **Mes 18-20** | Entrega del inmueble, firma de acto de venta, registro de titulo |
| **Mes 20** | Tramitacion del RNC, alta en DGII |
| **Mes 21** | Contratacion de operador, equipamiento del apartamento, sesion de fotos profesional |
| **Mes 22** | Lanzamiento en plataformas (Airbnb, Booking), primeras reservas |
| **Mes 24** | Operacion en velocidad crucero, primeros reportes de rentabilidad |
| **Marzo ano siguiente** | Presentacion Modelo 720, declaracion de renta IRPF |

---

## Checklist Final del Inversor Espanol

### Antes de la compra
- [ ] Certificado de residencia fiscal AEAT
- [ ] Pasaporte vigente (6+ meses)
- [ ] Abogado inmobiliario contratado en RD
- [ ] Asesor fiscal en Espana informado de la operacion
- [ ] Prueba de fondos preparada
- [ ] Proyecto CONFOTUR verificado (numero de resolucion)

### Durante la compra
- [ ] RNC obtenido (NIF dominicano)
- [ ] Due diligence completado por abogado
- [ ] Contrato revisado por abogado
- [ ] Transferencias bancarias documentadas
- [ ] Impuestos de transferencia pagados (o exencion CONFOTUR aplicada)
- [ ] Titulo de propiedad registrado a tu nombre

### Despues de la compra
- [ ] Operador de alquiler vacacional contratado
- [ ] Alta en DGII para actividad de alquiler
- [ ] Seguro de propiedad contratado
- [ ] Modelo 720 presentado (antes del 31 de marzo)
- [ ] Ingresos por alquiler declarados en IRPF
- [ ] Deduccion por doble imposicion aplicada correctamente
- [ ] Archivo de todos los justificantes de gastos (para deducciones IRPF)

---

## Conclusion

Invertir en Punta Cana siendo espanol es un proceso perfectamente viable, regulado y — con la ley CONFOTUR — fiscalmente muy ventajoso. El convenio de doble imposicion entre Espana y RD te protege de pagar dos veces, y la alta demanda turistica garantiza ocupaciones que muchos destinos europeos envidian.

Las claves del exito son tres:

1. **Buen asesoramiento legal y fiscal** en ambos paises
2. **Elegir un proyecto CONFOTUR** en una zona de alta demanda
3. **Un operador profesional** que maximice tus ingresos y te quite las preocupaciones

Con una inversion desde $80,000 USD de capital propio, puedes tener un activo en el Caribe que genera un 6-10% neto anual, se revaloriza, y te permite disfrutar de unas semanas de vacaciones bajo el sol dominicano cada ano.

> **Aviso legal:** Esta guia tiene caracter informativo y no sustituye el asesoramiento profesional. Las leyes fiscales y los procedimientos pueden cambiar. Consulta siempre con un abogado y un asesor fiscal antes de tomar decisiones de inversion.
MARKDOWN;
    }

    private function bodyEn(): string
    {
        return <<<'MARKDOWN'
## Introduction

More and more Spanish citizens are looking at the Caribbean as a real estate investment destination. Punta Cana, with over 7 million tourists per year, a double taxation treaty with Spain in force since 2014, and a tax incentive law (CONFOTUR) that can eliminate taxes for up to 15 years, has become one of the most attractive destinations for Spanish investors.

But the process has its complexities: you need to navigate two tax systems, understand Dominican legal procedures, choose the right vacation rental operator, and above all, have clarity on the numbers.

**This guide covers absolutely every step**, from the initial decision to receiving your first rental income, with real examples and figures.

---

## Step 1: Preparation From Spain

### 1.1 Documentation you need

As a Spanish citizen, **you don't need a visa** to enter the Dominican Republic. You receive a 30-day tourist permit on arrival, sufficient to begin the purchase process.

Documents to prepare **before traveling**:

- **Valid passport** (minimum 6 months validity)
- **Tax residency certificate** — Request from Spain's AEAT (Tax Agency). Required to apply the Double Taxation Treaty (DTT) with DR.
- **Proof of funds** — Bank statements from the last 3-6 months. Needed for anti-money laundering compliance.
- **Hague Apostille** — Any Spanish document used in DR must carry an apostille.
- **Power of attorney** (optional but recommended) — If you can't be present at every step, a notarized power of attorney (apostilled) allows your Dominican lawyer to act on your behalf.

### 1.2 Hiring a lawyer in DR

**This is the most important step.** Never buy property abroad without local legal representation.

A real estate lawyer in the Dominican Republic will cost between **1% and 1.5% of the purchase price**. Their functions:

- Obtaining your **RNC** (Registro Nacional del Contribuyente) — the Dominican tax ID
- Conducting complete **due diligence** on the property
- Verifying the project has **CONFOTUR** certification
- Drafting or reviewing contracts
- Representing you before the Title Registry and DGII
- Managing transfer tax payments

> **Tip:** Look for lawyers with specific experience serving Spanish investors. Many firms in Punta Cana understand the tax implications of the Spain-DR DTT.

---

## Step 2: Property Selection

### 2.1 Main areas in Punta Cana

| Area | Average 1BR price | Profile | Rental yield |
|------|-------------------|---------|-------------|
| **Bavaro** | $95,000 - $160,000 | Mass tourism, maximum occupancy | High (70-85% occupancy) |
| **Cap Cana** | $154,000 - $220,000 | Premium, golf, marina, private beaches | Medium-high, premium rates |
| **Cocotal / Vista Cana** | $110,000 - $170,000 | Residential, golf, quiet | Medium, good appreciation |
| **Veron / Downtown** | $80,000 - $120,000 | Most affordable, local area | High occupancy, lower rates |

### 2.2 Pre-construction vs. ready to move in

**Pre-construction:**
- Prices **15-25% below** completed market value
- Direct developer financing with 0% interest (typically 30/70 or 40/60)
- Delivery timeline: 12-24 months
- Risk: construction delays, finishing changes

**Ready to move in (resale or completed project):**
- Start generating income from day one
- Full market price
- Financing only through Dominican bank or own funds

> **For a Spanish investor seeking rental income**, pre-construction is the smartest financial option: you pay in installments without interest during construction and buy below market value. The downside is no income until delivery.

### 2.3 What to look for: checklist

- ✅ Project with valid **CONFOTUR** certification
- ✅ Clean property title (your lawyer verifies)
- ✅ Developer with a track record of delivered projects
- ✅ Area with high tourist demand and good reviews on Airbnb/Booking
- ✅ Amenities that attract tourists: pool, 24h security, parking, beach proximity
- ✅ Available property management or rental pool

---

## Step 3: The Purchase Process

### 3.1 Reservation

You sign a **reservation agreement** and pay a deposit of **$1,000 to $5,000 USD**. This amount is deducted from the final price. The reservation gives you exclusivity on the unit for a period (typically 15-30 days) while the formal contract is prepared.

### 3.2 Promise of Sale Contract

This is the main contract. It includes:

- Total price and payment method
- Payment schedule (for pre-construction)
- Estimated delivery date
- Penalties for non-compliance
- Cancellation conditions

Upon signing, you pay **10% of the price** as a down payment (against the total).

**Example for a $150,000 USD apartment in Bavaro (pre-construction):**

| Item | Amount | Timing |
|------|--------|--------|
| Reservation | $3,000 | At reservation |
| Promise signing (10%) | $12,000 | Within 15-30 days |
| Construction installments (20%) | $30,000 | 18 months (~$1,667/mo) |
| Delivery (70%) | $105,000 | Upon receiving keys |
| **Total** | **$150,000** | |

### 3.3 Due Diligence (30-60 days)

Your lawyer verifies in parallel:

1. **Property title** at the Title Registry — clean, no liens or encumbrances
2. **CONFOTUR certification** of the project — resolution number, exempt taxes and period
3. **Construction permits** — municipal license, MOPC approval
4. **Developer's tax status** — up to date with DGII
5. **Survey** — boundary and actual surface area verification

### 3.4 Sale Deed and Title Transfer

Upon completing full payment:

1. The **sale deed** is signed before a Dominican notary
2. The **transfer tax** is paid to DGII (3% of cadastral value, **exempt with CONFOTUR**)
3. The property is registered at the **Title Registry**
4. You receive your **Certificate of Title** in your name

**Total time** from signing to having title: approximately 60-90 days for completed properties.

---

## Step 4: Detailed Purchase Costs

### 4.1 Without CONFOTUR

| Item | % of price | On a $150,000 property |
|------|-----------|----------------------|
| Transfer tax | 3% | $4,500 |
| Attorney fees | 1.5% | $2,250 |
| Notary | ~1% | $1,500 |
| Title registration | ~1% | $1,500 |
| ITBIS on professional services | 18% of ~$3,750 | $675 |
| **Total closing costs** | **~6.9%** | **$10,425** |

### 4.2 With CONFOTUR

| Item | % of price | On a $150,000 property |
|------|-----------|----------------------|
| ~~Transfer tax~~ | ~~3%~~ **EXEMPT** | $0 |
| Attorney fees | 1.5% | $2,250 |
| Notary | ~1% | $1,500 |
| Title registration | ~1% | $1,500 |
| ITBIS on professional services | 18% of ~$3,750 | $675 |
| **Total closing costs** | **~3.9%** | **$5,925** |

> **CONFOTUR savings on purchase: $4,500 USD** just on transfer tax. But the real savings come later, with annual IPI exemption and rental income tax exemption.

---

## Step 5: Taxation — The Key Issue for Spanish Citizens

This is the most important section of the entire guide. As a Spanish tax resident with property abroad, you have obligations in **both countries**.

### 5.1 In the Dominican Republic

#### Annual property taxes

| Tax | Rate | Condition | With CONFOTUR |
|-----|------|-----------|--------------|
| **IPI** (property tax) | 1% annually | On combined property value exceeding ~$165,000 USD | **Exempt 10-15 years** |

#### Rental income taxes

| Tax | Rate | Base | With CONFOTUR |
|-----|------|------|--------------|
| **Income tax** (non-resident) | 27% | Net rental income (after deductible expenses) | **Exempt up to 10 years** |
| **ITBIS** (Dominican VAT) | 18% | On short-term accommodation services | Always applies |

### 5.2 In Spain

#### Modelo 720 — Foreign assets declaration

**Mandatory** if the property value exceeds **50,000 EUR**.

- **Deadline:** Before March 31 each year
- **What you declare:** Property type, full address, acquisition date, acquisition value, ownership percentage
- **It's purely informational** — no tax is paid through this form
- **Subsequent years:** Only re-filed if value increases by more than 20,000 EUR from last declaration
- **Warning:** Non-filing remains a serious infraction in Spain

#### IRPF — Real estate income

Rental income from your Punta Cana property **must be declared in your annual tax return** as real estate income:

- Added to your **general tax base** (taxed at marginal rate: 19% to 47% depending on bracket)
- You can deduct related expenses (management, maintenance, insurance, property depreciation, loan interest)
- **International double taxation credit:** Tax paid in DR (27%) is credited against Spanish IRPF on that same income

> **In practice:** If your marginal IRPF rate is 30% and you already paid 27% in DR, you'd only pay an additional 3% in Spain on that income. If your marginal rate is below 27%, you'd pay nothing additional.

#### Imputed income — If the property is NOT rented

If the property sits vacant part of the year, Spanish tax authorities **impute fictitious income**:

- **1.1% of acquisition value** for properties abroad
- Proportional to unoccupied days
- Added to your general tax base

#### Capital gains on sale

If you sell the property:
- Gains taxed in the **savings base**: 19% (first 6,000 EUR), 21% (6,000-50,000), 23% (50,000-200,000), 27% (200,000-300,000), 28% (over 300,000)
- The 27% capital gains tax paid in DR is credited
- **Additional requirement:** If the investment exceeds 300,000 EUR, you must file **Model D-7A** with Spain's Ministry of Economy within one month

### 5.3 Tax summary: a complete example

**Profile:** Maria, Spanish, tax resident in Madrid, 37% marginal IRPF rate.
**Property:** 1BR apartment in Bavaro, purchased for $150,000 USD (~140,000 EUR), CONFOTUR project.

**Annual rental income:** $25,000 USD gross

| Item | In DR (USD) | In Spain (EUR) |
|------|-------------|----------------|
| Gross rental income | $25,000 | ~23,360 EUR |
| Deductible expenses (25% operator, HOA, maintenance, insurance) | -$9,500 | -8,875 EUR |
| **Net income** | **$15,500** | **14,485 EUR** |
| DR tax (27% non-resident) | **EXEMPT (CONFOTUR)** | — |
| Spain IRPF (37% marginal) | — | 5,359 EUR |
| Double taxation credit | — | 0 EUR (no DR tax paid) |
| **Total effective tax** | **$0** | **~5,359 EUR** |

> **With CONFOTUR, Maria only pays taxes in Spain.** Without CONFOTUR, she'd pay $4,185 USD in DR but would deduct it from her Spanish IRPF (only paying the difference).

---

## Step 6: Financing

### 6.1 Option A — Developer financing (most common)

Most developers in Punta Cana offer **0% interest payment plans during construction**:

| Phase | Percentage | Example ($150,000) |
|-------|-----------|-------------------|
| Reservation | 5-10% | $7,500 - $15,000 |
| During construction (12-24 months) | 20-30% | $30,000 - $45,000 |
| At delivery | 60-70% | $90,000 - $105,000 |

**No credit check or DR credit history required.** It's simply an installment payment plan.

### 6.2 Option B — Dominican mortgage

If you need to finance the delivery payment (60-70%):

| Parameter | Typical terms |
|-----------|--------------|
| Maximum financing (LTV) | 60-70% of appraised value |
| Interest rate | 8-11% in USD |
| Term | 15-20 years |
| Minimum income | $2,500-$4,000 USD/month demonstrable |

**Banks that finance foreigners:** Banco Popular Dominicano, BHD Leon, Scotiabank DR, Banreservas.

### 6.3 Option C — Leveraging your Spanish property

If you own property in Spain, you can take out a **home equity loan** (or extend your current mortgage):

| Parameter | Typical terms |
|-----------|--------------|
| Fixed interest rate | 2.5% - 3.5% |
| Variable rate | Euribor + 1-2% |
| Term | Up to 25-30 years |
| Financing | Up to 60-70% of your Spanish property's appraised value (minus outstanding mortgage) |

> **This is the smartest financial option** if you have equity in Spain. The interest rate difference (3% vs 9%) saves thousands of euros per year.

---

## Step 7: Vacation Rental Operation with a Local Manager

### 7.1 Operating models

**A) Complex rental pool:**
- The developer or associated company manages all units as a pool
- Income shared among participating owners
- Less control but completely passive
- Commission: 30-40% of gross income

**B) Independent property manager:**
- You hire a manager who handles YOUR unit specifically
- Listed on Airbnb, Booking.com, VRBO
- More control over pricing, availability, and personal use
- Commission: 20-30% of gross income (25% is standard)

### 7.2 What a good operator does

Services included in the 25% commission:

- **Marketing and distribution:** Professional listings on all platforms, professional photos
- **Revenue management:** Dynamic pricing based on season, demand, events
- **Guest communication:** 24/7 responses, check-in/check-out
- **Cleaning and laundry:** Team coordination, quality control
- **Maintenance:** Minor repairs, coordination for major ones
- **Supplies:** Amenity and linen replenishment
- **Tax compliance:** ITBIS management, DGII reporting
- **Owner reports:** Monthly statements with income, expenses, and occupancy

### 7.3 Seasons in Punta Cana

| Season | Months | Typical occupancy | Nightly rate (1BR) |
|--------|--------|-------------------|-------------------|
| **High** | Dec - Apr | 80-95% | $120 - $180 USD |
| **Medium** | May - Jun, Nov | 60-75% | $90 - $130 USD |
| **Low** | Jul - Oct | 45-60% | $70 - $100 USD |

---

## Step 8: The Numbers — Complete ROI Example

### Investment profile

- **Property:** 1-bedroom apartment in Bavaro, 65 m2, CONFOTUR project
- **Price:** $150,000 USD (~140,000 EUR)
- **Payment:** 50% cash ($75,000) + Spanish home equity loan $75,000 at 3% over 20 years
- **Closing costs (with CONFOTUR):** ~$5,925

### Annual rental income

| Item | Calculation | Amount |
|------|------------|--------|
| Nights rented (70% occupancy) | 365 x 0.70 | 256 nights |
| Average nightly rate | — | $110 USD |
| **Gross annual income** | 256 x $110 | **$28,160 USD** |

### Annual expenses

| Item | Amount (USD) |
|------|-------------|
| Operator commission (25%) | $7,040 |
| HOA / community maintenance | $2,400 |
| Insurance | $600 |
| Internet + utilities | $960 |
| Maintenance and repairs | $1,200 |
| Spanish mortgage payment ($75,000 at 3%, 20 years) | $4,992 (12 x $416) |
| Contingency | $600 |
| **Total expenses** | **$17,792** |

### Net result

| Item | Amount |
|------|--------|
| Gross income | $28,160 |
| Total expenses | -$17,792 |
| **Net cash flow before taxes** | **$10,368** |
| DR income tax (CONFOTUR = exempt) | $0 |
| Spain IRPF (37% marginal on net income) | ~5,750 EUR* |
| Double taxation credit | $0 |
| **Net cash flow after taxes** | **~$4,200 USD** |

### Total return

| Metric | Value |
|--------|-------|
| **Gross yield** | 18.8% |
| **Net yield before taxes** | 6.6% |
| **Cash-on-cash return** | 5.2% |
| **Estimated annual appreciation** | 4-9% |
| **Estimated total return** | 10-15% |

> **Comparison:** A rental apartment in central Madrid yields 4-5% gross. In Punta Cana, with CONFOTUR and good management, you can exceed 6% net plus appreciation.

---

## Step 9: DR Residency (Optional But Beneficial)

If your investment exceeds $200,000 USD, you can apply for **investor residency**:

| Parameter | Detail |
|-----------|--------|
| Minimum investment | $200,000 USD in real estate |
| Initial permit | 1 year |
| Renewal | Every 4 years |
| Citizenship | Possible after 6 months of residency |

> **Caution:** If you spend more than 183 days per year in DR, you could become a Dominican tax resident and lose Spanish tax residency. Consult your tax advisor before taking this step.

---

## Step 10: Complete Timeline

| Month | Action |
|-------|--------|
| **Month 1** | Research, area and project selection, initial developer contact |
| **Month 2** | Trip to Punta Cana, project visits, lawyer engagement, reservation signing |
| **Month 3** | Due diligence, promise of sale signing, payment start |
| **Months 4-18** | Construction payments (if pre-construction) |
| **Month 18-20** | Property delivery, sale deed, title registration |
| **Month 20** | RNC processing, DGII registration |
| **Month 21** | Operator hiring, apartment furnishing, professional photo session |
| **Month 22** | Platform launch (Airbnb, Booking), first bookings |
| **Month 24** | Cruising speed operation, first profitability reports |
| **Next March** | File Modelo 720, IRPF tax declaration |

---

## Final Checklist for the Spanish Investor

### Before purchase
- [ ] AEAT tax residency certificate
- [ ] Valid passport (6+ months)
- [ ] DR real estate lawyer hired
- [ ] Spanish tax advisor informed
- [ ] Proof of funds prepared
- [ ] CONFOTUR project verified (resolution number)

### During purchase
- [ ] RNC obtained (Dominican tax ID)
- [ ] Due diligence completed by lawyer
- [ ] Contract reviewed by lawyer
- [ ] Bank transfers documented
- [ ] Transfer taxes paid (or CONFOTUR exemption applied)
- [ ] Property title registered in your name

### After purchase
- [ ] Vacation rental operator hired
- [ ] DGII registration for rental activity
- [ ] Property insurance contracted
- [ ] Modelo 720 filed (before March 31)
- [ ] Rental income declared in IRPF
- [ ] Double taxation credit correctly applied
- [ ] All expense receipts filed (for IRPF deductions)

---

## Conclusion

Investing in Punta Cana as a Spanish citizen is a perfectly viable, regulated, and — with the CONFOTUR law — highly tax-advantageous process. The double taxation treaty between Spain and DR protects you from paying twice, and the high tourist demand ensures occupancy rates that many European destinations envy.

The three keys to success are:

1. **Good legal and tax advice** in both countries
2. **Choosing a CONFOTUR project** in a high-demand area
3. **A professional operator** who maximizes your income and removes worries

With an investment starting from $80,000 USD in equity, you can own a Caribbean asset generating 6-10% net annually, appreciating in value, and allowing you to enjoy a few weeks of vacation under the Dominican sun each year.

> **Legal disclaimer:** This guide is for informational purposes and does not replace professional advice. Tax laws and procedures may change. Always consult with a lawyer and tax advisor before making investment decisions.
MARKDOWN;
    }
}
