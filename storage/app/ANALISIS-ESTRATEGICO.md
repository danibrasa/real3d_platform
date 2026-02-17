# Analisis Estrategico - RealEstate 3D Platform
**Fecha:** 2026-02-14 | **Actualizado:** 2026-02-17
**Version:** 3.0

---

## 1. Estado Actual de la Plataforma

### 1.1 Inventario Tecnico

| Metrica | Valor |
|---------|-------|
| Rutas | 115 |
| Tablas BD | 17 (+ personal_access_tokens, chatbot_conversations, chatbot_messages, currencies, construction_*) |
| Vistas Blade | 28 custom + 18 components |
| Controllers | 28 |
| Modelos | 14 |
| Migraciones | 21 |
| Archivos JS | 9 (viewer-public, viewer-admin, viewer-bbox-mapper, viewer-units, admin-upload, viewer-analytics, embed-widget + 2 Vite) |
| Roles de usuario | 5 (superadmin, gestor, inmobiliaria, agente, user) |
| Gates de autorizacion | 16 |
| API endpoints | 20 (internos + v1 con token auth) |

### 1.2 Stack Tecnologico

- **Backend:** Laravel 12, PHP 8.2, MariaDB 10.11
- **Frontend:** Tailwind CSS 3.1, Alpine.js 3.4, Vite 7
- **3D Engine:** Three.js v0.162.0 (CDN via importmap)
- **Auth:** Laravel Breeze (session-based) + Laravel Sanctum (API token auth)
- **AI:** OpenAI / Anthropic API (chatbot, configurable via .env)
- **Storage:** Local filesystem (storage/app/private/)
- **Queue/Cache/Sessions:** Database-backed

### 1.3 Funcionalidades Implementadas

**Core 3D:**
- Visor Three.js con modelos GLB/GLTF (compresion DRACO)
- Video 360 esferico como cielo envolvente
- Presets de iluminacion (morning/noon/evening)
- Suelo con texturas (grass/concrete/dirt/custom)
- OrbitControls (rotacion, zoom, pan)
- Persistencia de settings del visor

**Gestion de Unidades:**
- CRUD completo con tipologias
- Status: available/reserved/sold
- Bounding box 3D (bbox mapping) para posicionar unidades en el modelo
- Filtros por piso, habitaciones, precio, area
- Planos de planta por unidad/tipologia
- Focusing de camara a unidades seleccionadas

**Comercial:**
- Landing pages por proyecto con galeria, visor 3D embebido, tabla de unidades
- Formulario de contacto con seleccion de unidad
- Integracion WhatsApp
- Unit sharing via query params
- Email de notificacion y auto-respuesta

**Admin:**
- Dashboard con estadisticas
- Upload chunked (5MB) para video/3D/texturas
- Editor de settings del visor con preview en vivo
- Gestion de galeria con ordenamiento
- Gestion de consultas (leidas/no leidas)
- Sistema de roles con 4 niveles admin + gates

**SEO/Marketing:**
- Open Graph y Twitter Card meta tags
- Google Analytics/GTM por proyecto
- Landing page publica SaaS (welcome)
- Listado publico con buscador y filtros (ubicacion, habitaciones, precio, orden)

**i18n / Multi-moneda:**
- Espanol e ingles con selector de idioma
- Multi-moneda (USD, DOP, EUR, CAD) con tasas configurables
- Campos traducibles por proyecto (descripcion, tagline, ubicacion, WhatsApp message)

**API Publica:**
- API v1 versionada con token auth (Sanctum)
- Endpoints: projects, units (con filtros), availability
- Tokens scoped por proyecto con rate limit configurable
- CORS configurado para widgets embebidos
- Widget embebible JS para sitios externos
- Panel admin de gestion de tokens

**Chatbot IA:**
- Asistente virtual en landing pages con contexto del proyecto
- Soporte OpenAI y Anthropic (configurable)
- Captura de leads automatica → Inquiry
- Persistencia de conversaciones por sesion
- Widget Alpine.js flotante con lead capture form

**Analytics:**
- Tracking de interaccion 3D (viewer_events)
- Dashboard de analytics por proyecto
- Metricas: sesiones, unidades vistas, WhatsApp clicks, PDF downloads, chatbot interactions
- Bridge a Google Analytics via gtag

### 1.4 Lo que FALTA (gaps detectados)

| Area | Estado |
|------|--------|
| PWA / Service Worker | No implementado (descartado Fase 3 — UX limitada en mobile) |
| Internacionalizacion (i18n) | **Implementado** (ES/EN, middleware SetLocale, campos traducibles) |
| Cache de queries | No - sin Cache:: en controllers |
| Tests custom | No - solo tests de Breeze por defecto |
| robots.txt / sitemap.xml | **Implementado** (SitemapController, robots.txt) |
| API token auth | **Implementado** (Sanctum, API v1 versionada, tokens scoped por proyecto) |
| Multi-moneda | **Implementado** (USD/DOP/EUR/CAD, CurrencyService, selector) |
| Analytics del visor 3D | **Implementado** (viewer_events, dashboard, batch tracking) |
| Planes de pago | **Implementado** (payment_plans, timeline visual, simulador) |
| Progreso de obra | **Implementado** (construction_phases, timeline publico, fotos) |
| Chatbot IA | **Implementado** (OpenAI/Anthropic, Alpine.js widget, lead capture) |
| WebXR / AR | No implementado (descartado Fase 3 — experiencia UX insuficiente) |

---

## 2. Analisis Competitivo

### 2.1 Competidores Directos

#### Matterport
- **Que es:** Lider mundial en digital twins 3D (escaneo de espacios existentes)
- **Precio:** Free (1 espacio) → $9.99/mo (5) → $57.99/mo (25) → $309/mo (100+)
- **Fortalezas:** Dollhouse view, mediciones, floor plans automaticos, enorme ecosistema de integraciones
- **Debilidad critica:** Requiere escanear propiedades EXISTENTES. Inutil para pre-construccion. No tiene gestion de unidades. No tiene sistema multi-rol. Sin foco LATAM.

#### Cupix
- **Que es:** Plataforma de digital twins para construccion y real estate
- **Precio:** $200-500+/mes
- **Fortalezas:** SiteView para progreso de obra, comparacion temporal, integracion BIM
- **Debilidad:** Enfocado en construccion industrial, no en ventas inmobiliarias. Sin gestion de unidades ni lead capture. Precio alto.

#### CloudPano
- **Que es:** Tours 360 con white-label y lead capture
- **Precio:** $19-99/mes
- **Fortalezas:** White-label genuino, lead capture integrado, heatmaps
- **Debilidad:** Solo fotos 360, no modelos 3D reales. Sin video 360. Sin gestion de unidades. Sin multi-rol. Requiere propiedades existentes.

#### EyeSpy360
- **Que es:** Tours virtuales con hotspots y floor plans
- **Precio:** Free → $14.99/mo → $99/mo
- **Fortalezas:** Floor plans interactivos, hotspots, lead capture basico
- **Debilidad:** Sin modelos 3D. Sin video 360. Sin gestion de unidades. Single-listing focus.

#### Kuula
- **Que es:** Tours 360 simples y accesibles
- **Precio:** Free → $12/mo → $36/mo
- **Fortalezas:** Extremadamente facil de usar, barato, soporte VR
- **Debilidad:** Muy basico. Sin lead capture, sin floor plans, sin 3D, sin nada especializado para real estate.

#### Nodalview
- **Que es:** Fotografia inmobiliaria + tours virtuales (Europa)
- **Precio:** EUR 29-149/mes
- **Fortalezas:** Virtual staging con IA, lead tracking sofisticado, multi-idioma (incluye espanol)
- **Debilidad:** Solo fotos. Sin modelos 3D. Sin pre-construccion. Enfoque europeo, sin presencia LATAM.

#### Zillow 3D Home
- **Que es:** Tours 3D gratuitos integrados en Zillow
- **Fortalezas:** Gratis, distribucion masiva en USA
- **Debilidad:** Solo USA, solo Zillow, calidad mediocre, sin customizacion, sin gestion de unidades.

### 2.2 Competidores LATAM

| Plataforma | Pais | 3D/VR | Pre-construccion | Gestion Unidades | Lead Capture | Nivel de Amenaza |
|------------|------|-------|-----------------|------------------|-------------|-----------------|
| La Haus | CO/MX | Embebido (terceros) | Si (marketplace) | No | Si (via plataforma) | Medio |
| Habi | CO | No | No | No | No | Bajo |
| Properati | Multi | No | Parcial | No | Basico | Bajo |
| SuperCasas | DR | No | Listings basicos | No | Basico | Bajo |
| **RealEstate 3D** | **DR** | **Three.js nativo** | **Si (core)** | **Si (bbox 3D)** | **Si (built-in)** | **—** |

**Observacion clave:** El mercado de Punta Cana depende casi totalmente de WhatsApp, PDFs y sitios web basicos. Hay un gap masivo en herramientas digitales para ventas pre-construccion.

### 2.3 Matriz Comparativa de Features

| Feature | Matterport | CloudPano | EyeSpy360 | Nodalview | RealEstate 3D |
|---------|-----------|-----------|-----------|-----------|---------------|
| Visor 3D de modelos (GLB) | No | No | No | No | **SI** |
| Video 360 | No | No | No | No | **SI** |
| Tours foto 360 | Si | Si | Si | Si | No |
| Gestion de unidades | No | No | No | No | **SI** |
| Bbox 3D mapping | No | No | No | No | **SI** |
| Lead capture | Via integracion | Si | Si | Si | **SI** |
| Chatbot IA 24/7 | No | No | No | No | **SI** |
| Multi-rol (4 niveles) | No | No | No | Basico | **SI** |
| Landing pages proyecto | No | No | Mini-site | Mini-site | **SI** |
| Galeria | No | No | No | No | **SI** |
| API publica con tokens | Enterprise | No | No | No | **SI** |
| Widget embebible | Enterprise | Si | Si | No | **SI** |
| Multi-idioma (ES/EN) | No | No | No | Parcial | **SI** |
| Multi-moneda | No | No | No | No | **SI** |
| White-label | Enterprise | Si | Si | No | **SI (self-hosted)** |
| Pre-construccion ready | No | No | No | No | **SI** |
| LATAM / Espanol nativo | No | No | No | Parcial | **SI** |
| Self-hosted | No | No | No | No | **SI** |

---

## 3. Tendencias Proptech 2025-2026

### 3.1 IA en Real Estate
- **Virtual staging con IA**: Herramientas como REimagine Home generan interiores amueblados por $5-30/imagen vs $1,000-5,000 de staging fisico. **Nota:** Descartado para nuestra plataforma por riesgo legal — renders IA pueden ser vinculantes para el vendedor. Mejor que el promotor suba renders oficiales.
- **Chatbots IA 24/7**: Respuesta inmediata a leads internacionales en diferentes zonas horarias. **Implementado** con soporte OpenAI/Anthropic.
- **Prediccion de precios**: Estimacion de ROI para compradores-inversores en Punta Cana. **Implementado** como Calculadora de Inversion.

### 3.2 WebGL/WebXR
- Three.js sigue dominando el ecosistema WebGL open-source
- WebXR madura: tours VR en browser sin app
- Apple Vision Pro impulsa interes en experiencias espaciales web
- AR "Place the Building": ver el desarrollo en el sitio fisico con el telefono

### 3.3 Mobile-First
- LATAM: >75% del trafico web es movil
- RD: ~85% penetracion smartphone, bajo uso desktop
- WhatsApp es el canal principal de ventas inmobiliarias en DR
- Touch controls impecables y rendimiento en mobile son obligatorios

### 3.4 Multi-Idioma / Multi-Moneda
- Compradores Punta Cana: dominicanos (DOP), estadounidenses (USD), canadienses (CAD), europeos (EUR)
- Contenido minimo en espanol e ingles
- Precios en USD y DOP (la mayoria del real estate DR se cotiza en USD)

### 3.5 Analytics de Comportamiento
- Heatmaps de interaccion 3D (donde miran, cuanto tiempo, que unidades)
- Tracking per-lead de engagement
- Datos para ajustar pricing (unidades con alta demanda soportan aumentos)

---

## 4. Roadmap Estrategico de Mejoras

### Fase 1: Quick Wins de Impacto (1-2 semanas)

Mejoras que generan valor inmediato con esfuerzo moderado:

| # | Mejora | Impacto | Esfuerzo |
|---|--------|---------|----------|
| 1.1 | **SEO tecnico**: robots.txt, sitemap.xml dinamico, meta descriptions en todas las paginas | Alto | Bajo |
| 1.2 | **Compartir en redes**: botones de share (WhatsApp, Facebook, Twitter, copiar link) en landing y visor | Alto | Bajo |
| 1.3 | **Notificaciones en tiempo real**: badge con count de consultas no leidas en nav del admin | Medio | Bajo |
| 1.4 | **Comparador de unidades**: seleccionar 2-3 unidades y ver side-by-side (area, precio, piso, habitaciones) | Alto | Medio |
| 1.5 | **Mejora de performance 3D movil**: LOD (Level of Detail), lazy loading del visor, placeholder hasta interaccion | Alto | Medio |
| 1.6 | **PDF exportable**: generar ficha de unidad en PDF (especificaciones, plano, precio, contacto) | Alto | Medio |

### Fase 2: Diferenciadores Competitivos (1-3 meses)

Features que crean separacion real vs competencia:

| # | Mejora | Impacto | Esfuerzo |
|---|--------|---------|----------|
| 2.1 | **Internacionalizacion (i18n)**: espanol/ingles en toda la plataforma, toggle de idioma | Critico | Alto |
| 2.2 | **Multi-moneda**: mostrar precios en USD/DOP/EUR con toggle, tipo de cambio configurable | Alto | Medio |
| 2.3 | **Analytics del visor 3D**: tracking de interaccion (que unidades se ven, tiempo, clics), dashboard para inmobiliaria | Muy Alto | Alto |
| 2.4 | **Planes de pago interactivos**: visualizacion de hitos de pago por unidad (30% inicial, 10% en obra gris, etc.) | Alto | Medio |
| 2.5 | **WhatsApp Business API**: respuesta automatica al primer contacto, routing a agente correcto, tracking de conversacion | Alto | Alto |
| 2.6 | **Progreso de obra**: modulo con timeline de hitos, fotos/video de avance, notificacion a compradores | Alto | Alto |
| 2.7 | **Calculadora de inversion**: proyeccion de rental yield, ROI estimado basado en comparables de la zona | Alto | Medio |

### Fase 3: Expansion de Plataforma (3-6 meses)

Features que escalan el producto:

| # | Mejora | Impacto | Esfuerzo | Estado |
|---|--------|---------|----------|--------|
| 3.1 | **Galeria de Interiores por Tipologia** (rediseñado — ver nota) | Medio | Bajo | `[DESCARTADO → REDISEÑADO]` |
| 3.2 | **Chatbot IA en landing**: asistente 24/7 multilingue que responde preguntas sobre unidades, precios, disponibilidad | Alto | Alto | `[COMPLETADO]` |
| 3.3 | **PWA (Progressive Web App)**: offline viewing de modelos ya cargados, notificaciones push | Medio | Medio | `[DESCARTADO]` |
| 3.4 | **API publica con token auth**: permitir a portales (Properati, SuperCasas) embeber datos del proyecto | Alto | Medio | `[COMPLETADO]` |
| 3.5 | **WebXR / AR**: "Coloca el edificio" en el sitio fisico usando la camara del movil | Alto | Alto | `[DESCARTADO]` |
| 3.6 | **Floor plans automaticos**: extraer planos 2D desde el modelo 3D | Medio | Alto | `[DESCARTADO]` |
| 3.7 | **Atribucion de agente y comisiones**: tracking de que agente trajo que lead, calculo de comisiones | Alto | Medio | `[DESCARTADO]` |

#### Decisiones de descarte — Fase 3

**3.1 Virtual Staging con IA → DESCARTADO**
> Motivo: En el mundo de real estate, los renders pueden ser **vinculantes legalmente** para el vendedor respecto al producto que debe entregar. Generar renders con IA presenta riesgo de crear imagenes que no correspondan con el producto final, lo cual podria exponer al promotor a reclamaciones legales. Se rediseño como "Galeria de Interiores por Tipologia" donde el promotor/gestor sube sus propios renders oficiales. Esta funcionalidad queda cubierta parcialmente por el sistema de galeria existente y podria implementarse como extension menor en el futuro.

**3.3 PWA (Progressive Web App) → DESCARTADO**
> Motivo: La experiencia de usuario en modo offline para modelos 3D seria muy limitada (los modelos GLB son pesados para almacenar en cache del navegador). Las notificaciones push aportan valor marginal dado que WhatsApp ya cumple esa funcion. El esfuerzo no se justifica por el impacto real.

**3.5 WebXR / AR → DESCARTADO**
> Motivo: La tecnologia WebXR para "colocar el edificio" en el sitio fisico no ofrece una experiencia de usuario suficientemente buena en la mayoria de dispositivos moviles del mercado LATAM. El tracking espacial es inconsistente, la escala es dificil de calibrar, y los modelos 3D de edificios pre-construccion no estan optimizados para AR. Mejor esperar a que la tecnologia madure.

**3.6 Floor Plans Automaticos → DESCARTADO**
> Motivo: Extraer planos 2D desde modelos 3D GLB es un problema tecnico no resuelto de forma fiable. Los modelos arquitectonicos raramente tienen la geometria limpia necesaria para cortes de planta automaticos. Los planos profesionales que ya suben los promotores son superiores en calidad y precision. La solucion actual (subir planos por unidad/tipologia) es adecuada.

**3.7 Atribucion de Agente y Comisiones → DESCARTADO**
> Motivo: Cada inmobiliaria gestiona sus comisiones de forma interna con sus propios sistemas y acuerdos. Implementar un sistema de comisiones generico no aportaria valor real, ya que las estructuras de comision varian enormemente entre inmobiliarias (porcentaje fijo, escalonado, por volumen, etc.). El sistema RBAC existente ya permite tracking basico de que agente esta asociado a que proyecto.

#### Implementaciones completadas — Fase 3

**3.2 Chatbot IA en Landing `[COMPLETADO]`**
- ChatbotService con soporte OpenAI y Anthropic (configurable via .env)
- Contexto automatico del proyecto: unidades, precios, tipologias, planes de pago, progreso de obra
- Widget Alpine.js flotante con persistencia de sesion (sessionStorage)
- Captura de leads: detecta email/telefono, crea Inquiry vinculada
- Rate limit: 10 msg/min por IP, 20 msgs max por conversacion
- Bilingue (ES/EN) segun locale del usuario
- Analytics: chatbot_opened, chatbot_message_sent, chatbot_lead_captured
- Archivos: ChatbotService.php, ChatbotController.php, chatbot-widget.blade.php, config/chatbot.php, 2 modelos, 1 migracion

**3.4 API Publica con Token Auth `[COMPLETADO]`**
- Laravel Sanctum instalado con modelo custom ApiToken (project_ids, rate_limit, is_active)
- API v1 versionada: GET /api/v1/projects, /projects/{slug}, /projects/{slug}/units, /projects/{slug}/units/{id}, /projects/{slug}/availability
- Filtros en unidades: status, bedrooms, min_price, max_price, min_area
- Tokens scoped por proyecto (project_ids JSON, null = todos)
- Rate limiting configurable por token (60/120/300/600 req/min)
- Middleware EnsureTokenProjectAccess
- CORS configurado para cross-origin (config/cors.php)
- Panel admin: CRUD de tokens con plaintext one-time display
- Widget embebible: `<script>` tag con iframe (embed-widget.js + EmbedController)
- Documentacion de endpoints en la vista admin
- Archivos: ApiToken.php, V1/ProjectController.php, ApiTokenController.php, EnsureTokenProjectAccess.php, 3 vistas admin, embed-widget.js, widget.blade.php, config/cors.php, 2 migraciones

### Fase 4: Marketplace (6-12 meses)

El juego a largo plazo:

| # | Mejora | Impacto | Esfuerzo |
|---|--------|---------|----------|
| 4.1 | **Directorio de desarrollos**: listado agregado de todos los proyectos en la plataforma, SEO organico | Muy Alto | Alto |
| 4.2 | **Modelo SaaS con tiers**: pricing por proyecto/mes, planes para desarrolladores vs agencias | Alto | Medio |
| 4.3 | **Multi-pais**: expansion a Mexico (Cancun, Riviera Maya), Colombia (Cartagena), Panama | Muy Alto | Alto |
| 4.4 | **Integracion CRM**: conectar con HubSpot, Salesforce, CRMs locales | Medio | Medio |
| 4.5 | **Arquitectura tokenization-ready**: preparar para propiedad fraccionada via blockchain cuando la regulacion lo permita | Bajo (ahora) | Alto |

---

## 5. Posicionamiento Competitivo

### Propuesta de Valor Unica

> RealEstate 3D es la primera plataforma de visualizacion 3D disenada especificamente para ventas pre-construccion en America Latina. A diferencia de Matterport (que escanea propiedades existentes), CloudPano/EyeSpy360 (que crean tours de fotos) o portales de listings (que muestran imagenes estaticas), RealEstate 3D renderiza modelos arquitectonicos 3D interactivamente en el navegador, mapea unidades individuales dentro del modelo con disponibilidad y precios en tiempo real, captura y enruta leads a traves de una jerarquia de ventas multi-nivel, y ofrece la experiencia digital profesional que los compradores internacionales esperan.

### Diferenciadores Clave

| Diferenciador | Por que Importa | Quien mas lo Tiene |
|--------------|-----------------|-------------------|
| 3D interactivo desde modelos arquitectonicos (GLB) | Los proyectos pre-construccion existen como modelos 3D, no como fotos | Nadie en RE LATAM |
| Video 360 + modelo 3D en un visor | Inmersion mas rica que solo fotos | Nadie |
| Bbox mapping 3D con disponibilidad por unidad | Gestion de inventario visual en tiempo real | Nadie |
| Jerarquia de roles de 4 niveles | Refleja la cadena real: developer → agencia → agente | Nadie en visualizacion RE |
| LATAM-first, espanol nativo | Construido para el mercado, no adaptado a el | Solo portales de listings |
| Self-hosted / propiedad de datos | Developers controlan sus datos y branding | Solo soluciones custom |
| Pre-construccion como caso de uso principal | Disenado para vender lo no construido | Nadie (todas asumen propiedades existentes) |

---

## 6. Riesgos y Mitigacion

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|-----------|
| Matterport agrega features pre-construccion | Media | Alto | Moverse rapido, dominar el nicho LATAM antes de que localicen |
| Developers resisten herramientas digitales | Media | Alto | Onboarding concierge; mostrar ROI con early adopters |
| Calidad variable de modelos 3D | Alta | Medio | Guias de optimizacion GLB; servicio de QA de modelos |
| Compradores internacionales desconfian de plataformas desconocidas | Media | Medio | UX profesional, SSL, badges de verificacion de developer |
| Performance 3D pobre en moviles | Alta | Alto | LOD agresivo, compresion DRACO/meshopt, carga progresiva |
| Competidor lanza alternativa LATAM-focused | Baja (corto plazo) | Alto | Construir network effects (directorio multi-developer) que generen switching costs |

---

## 7. Metricas de Exito

### KPIs por Fase

**Fase 1 (Quick Wins):**
- Tiempo de carga del visor 3D en movil < 5 segundos
- Tasa de bounce en landing page < 40%
- Consultas generadas por proyecto por semana

**Fase 2 (Diferenciadores):**
- Tiempo promedio en visor 3D > 2 minutos
- Conversion de vista a consulta > 5%
- Numero de proyectos activos en la plataforma
- NPS de developers y agencias > 50

**Fase 3 (Expansion):**
- Usuarios activos mensuales
- Proyectos de multiples paises
- Revenue recurrente mensual (MRR)

**Fase 4 (Marketplace):**
- Trafico organico al directorio
- Proyectos onboarded por mes sin intervencion manual
- Expansion geografica (paises activos)

---

## 8. Conclusion

La ventana de oportunidad esta abierta: la inversion proptech en LATAM ha crecido exponencialmente pero nadie esta construyendo infraestructura de visualizacion para el segmento pre-construccion. Los competidores globales (Matterport, CloudPano) sirven propiedades existentes en mercados desarrollados. Los competidores LATAM (La Haus, Properati) son marketplaces sin herramientas de visualizacion propias.

RealEstate 3D ocupa un espacio unico: plataforma de visualizacion 3D + gestion de ventas + lead capture, disenada desde cero para pre-construccion en LATAM.

### Estado de ejecucion (Febrero 2026)

**Fases completadas:**
- Fase 1 (Quick Wins): 6/6 tareas completadas — SEO, sharing, notificaciones, comparador, performance 3D, PDF
- Fase 2 (Diferenciadores): 7/7 tareas completadas — i18n, multi-moneda, analytics, planes de pago, WhatsApp tracking, progreso de obra, calculadora inversion
- Fase 3 (Expansion): 2/7 tareas implementadas (API publica + Chatbot IA), 5 descartadas con justificacion

**Proximos pasos:**
1. **Lanzar con 2-3 desarrollos de Punta Cana** como clientes de referencia — la plataforma esta funcionalmente completa
2. **Activar el chatbot IA** con API keys de produccion y validar engagement/lead capture
3. **Distribuir API tokens** a portales inmobiliarios y CRMs interesados
4. **Avanzar a Fase 4 (Marketplace)** — directorio de desarrollos, modelo SaaS, expansion multi-pais

Moverse con decision ahora establece a RealEstate 3D como lider de categoria antes de que los players grandes inevitablemente pongan atencion en este mercado.

---

## 9. Plan de Ejecucion Detallado — Fases 1 y 2

> Leyenda de estado: `[PENDIENTE]` | `[EN PROGRESO]` | `[COMPLETADO]` | `[BLOQUEADO]`

---

### FASE 1: Quick Wins de Impacto

---

#### 1.1 SEO Tecnico `[COMPLETADO]`

**Objetivo:** Posicionar la plataforma en buscadores para captar trafico organico de compradores internacionales buscando "apartments Punta Cana", "pre-construction Dominican Republic", etc.

**Estado actual:** robots.txt existe pero sin referencia a sitemap. No hay sitemap.xml. No hay `<meta name="description">` en ninguna pagina. Las paginas de visor tienen OG tags pero sin meta description estandar. No hay datos estructurados (JSON-LD).

**Implementacion paso a paso:**

**1.1.1 — robots.txt optimizado**
- Actualizar `/public/robots.txt` con directivas: Allow para rutas publicas, Disallow para `/admin/*`, `/api/*`, `/profile`
- Agregar `Sitemap: https://dominio.com/sitemap.xml`
- Archivos: `public/robots.txt`

**1.1.2 — Sitemap XML dinamico**
- Crear ruta `GET /sitemap.xml` que genere XML con todos los proyectos publicados
- Incluir: pagina principal, listado de proyectos, landing de cada proyecto, visor de cada proyecto
- Agregar `<lastmod>` basado en `updated_at` del proyecto
- Prioridades: home (1.0), proyecto landing (0.8), visor (0.7), listado (0.9)
- Archivos: `routes/web.php`, nuevo `SitemapController.php`

**1.1.3 — Meta descriptions en todas las paginas**
- `welcome.blade.php`: description estatica de la plataforma
- `viewer/index.blade.php`: "Explora proyectos inmobiliarios en 3D..."
- `viewer/landing.blade.php`: usar `$project->description` (truncado a 160 chars)
- `viewer/show.blade.php`: "Visor 3D interactivo de {proyecto}"
- `layouts/app.blade.php`: meta description por defecto + slot para override
- `layouts/guest.blade.php`: meta description por defecto
- Agregar `<link rel="canonical">` en todas las paginas publicas

**1.1.4 — Datos estructurados JSON-LD**
- Agregar `RealEstateListing` schema en `viewer/landing.blade.php` con: name, description, url, image, numberOfRooms, floorSize, price, address
- Agregar `WebSite` schema en `welcome.blade.php`
- Agregar `BreadcrumbList` en paginas de proyecto

**UX esperada:** No visible para el usuario final, pero mejora drasticamente la presentacion en resultados de Google (rich snippets con precio, imagen, habitaciones).

---

#### 1.2 Compartir en Redes Sociales `[COMPLETADO]`

**Objetivo:** Multiplicar el alcance organico permitiendo a compradores compartir proyectos y unidades con un solo clic. WhatsApp es critico — es el canal #1 en DR/LATAM.

**Estado actual:** Existen botones de "copiar link" en la landing y visor. OG/Twitter tags implementados en paginas de proyecto. No hay botones de share a redes sociales. No hay WhatsApp share (solo contacto directo).

**Implementacion paso a paso:**

**1.2.1 — Componente Blade reutilizable `share-buttons`**
- Crear `resources/views/components/share-buttons.blade.php`
- Props: `url`, `title`, `description`, `image` (opcional)
- Botones: WhatsApp, Facebook, Twitter/X, LinkedIn, Copiar Link
- Diseño: barra flotante lateral en desktop (fixed left), barra inferior en mobile (fixed bottom)
- Animacion: aparece con scroll (despues de 200px), iconos con hover scale
- Feedback visual al copiar: toast "Link copiado" con check animado

**1.2.2 — Integracion en vistas publicas**
- `viewer/landing.blade.php`: share del proyecto completo + share individual por unidad (boton en modal de unidad)
- `viewer/show.blade.php`: share del visor con `?unit=ID` si hay unidad seleccionada
- `viewer/index.blade.php`: share del listado (opcional)
- `welcome.blade.php`: share de la plataforma

**1.2.3 — WhatsApp share inteligente**
- Mensaje pre-formateado con emoji: "🏠 Mira este proyecto: {nombre} en {ubicacion}\n💰 Desde USD {precio_min}\n🔗 {url}"
- Para unidades: "🏠 Unidad {id} - {hab} hab, {area}m² por USD {precio}\n📍 {proyecto} en {ubicacion}\n🔗 {url}?unit={id}"

**1.2.4 — Tracking de shares**
- Enviar evento `gtag('event', 'share', {method: 'whatsapp', content_type: 'project', item_id: slug})` en cada click

**UX esperada:** Barra de share elegante que no distrae del contenido. En movil, botones grandes y accesibles con el pulgar. Feedback inmediato al copiar. WhatsApp abre la app directamente con mensaje listo para enviar.

---

#### 1.3 Notificaciones en Tiempo Real `[COMPLETADO]`

**Objetivo:** Que los agentes y gestores vean nuevas consultas al instante sin recargar la pagina, reduciendo el tiempo de respuesta al lead (critico en ventas inmobiliarias: responder en <5 min multiplica x10 la conversion).

**Estado actual:** El badge de consultas no leidas existe en la navegacion pero solo se actualiza con recarga de pagina. Notificaciones son solo via email. No hay broadcasting ni WebSocket configurado.

**Implementacion paso a paso:**

**1.3.1 — Polling ligero (fase inicial, sin WebSocket)**
- Crear endpoint `GET /api/admin/notifications/count` protegido por auth
- Retorna JSON: `{unread_inquiries: N, latest_inquiry: {id, project_name, time_ago}}`
- Alpine.js poll cada 30 segundos en `layouts/navigation.blade.php`
- Actualizar badge sin recarga de pagina
- Animacion de pulse cuando el count cambia (nueva consulta)

**1.3.2 — Toast de nueva consulta**
- Cuando el count aumenta, mostrar toast tipo: "Nueva consulta en {Proyecto} — hace 1 min"
- Toast con boton "Ver" que lleva a la consulta
- Auto-dismiss en 8 segundos con barra de progreso
- Sonido sutil opcional (icono de campana toggle en nav)

**1.3.3 — Dropdown de notificaciones**
- Icono de campana en el header (al lado del nombre de usuario)
- Dropdown con las 5 consultas mas recientes no leidas
- Cada item muestra: nombre del contacto, proyecto, tiempo relativo
- Boton "Ver todas" al final
- Badge numerico sobre la campana

**UX esperada:** El admin siente la plataforma "viva". Las nuevas consultas aparecen de forma no intrusiva pero inmediatamente visible. El sonido opcional crea urgencia sin ser molesto. El dropdown permite triaje rapido sin salir de la pagina actual.

---

#### 1.4 Comparador de Unidades `[COMPLETADO]`

**Objetivo:** Los compradores internacionales comparan 2-3 unidades antes de decidir. Actualmente deben anotar datos manualmente. Un comparador side-by-side profesional acelera la decision de compra.

**Estado actual:** Landing page tiene tabla de unidades con filtros y modal de detalle individual. No existe funcionalidad de comparacion. API de unidades disponible.

**Implementacion paso a paso:**

**1.4.1 — Boton "Comparar" en tabla de unidades**
- Agregar checkbox en cada fila de la tabla de unidades (maximo 3 seleccionables)
- Barra flotante inferior aparece al seleccionar la primera unidad: "1 unidad seleccionada — Selecciona hasta 3"
- La barra muestra mini-badges con el identificador de cada unidad seleccionada
- Boton "Comparar" se activa con 2+ unidades seleccionadas
- Boton "X" para deseleccionar individual

**1.4.2 — Modal/Panel de comparacion**
- Modal fullscreen en mobile, panel lateral ancho en desktop
- Layout: columnas side-by-side (2 o 3 segun seleccion)
- Header de cada columna: identificador + badge de estado (disponible/reservado)
- Filas comparativas con resaltado de diferencias:
  - Tipologia
  - Piso
  - Dormitorios / Banos
  - Area (m²) — resaltar la mayor en verde
  - Precio (USD) — resaltar la menor en verde
  - Precio por m² (calculado) — resaltar el menor
  - Plano de planta (miniatura clicable)
  - Estado de disponibilidad
- Fila final: boton "Consultar" y "WhatsApp" por cada unidad

**1.4.3 — Share de comparacion**
- Boton "Compartir comparacion" genera URL con query params: `?compare=A101,B202,C303`
- Al abrir la URL, la comparacion se carga automaticamente
- Mensaje WhatsApp: "Estoy comparando estas unidades del proyecto {nombre}: {url}"

**1.4.4 — Comparacion en el visor 3D**
- Las unidades en comparacion se resaltan en el modelo 3D con colores distintos (rojo, azul, verde)
- Click en unidad en el comparador hace focus de camara a esa unidad en el 3D

**UX esperada:** Flujo intuitivo — seleccionar unidades con checkboxes, ver comparacion instantanea. Diferencias resaltadas visualmente para decision rapida. La opcion de compartir la comparacion permite que una pareja o familia tome la decision juntos.

---

#### 1.5 Performance 3D en Movil `[COMPLETADO]`

**Objetivo:** Reducir tiempo de carga del visor 3D en moviles de gama media a <5 segundos. Mejorar FPS a >30 fps estable. El 85% del trafico en DR es movil.

**Estado actual:** DRACO compression activa. Pixel ratio cap en 2x. Shadow maps 2048x2048 en todos los dispositivos. No hay deteccion de mobile ni ajuste de calidad adaptativo. Esfera 360 con 64x32 segments sin variacion. No hay lazy loading del visor.

**Implementacion paso a paso:**

**1.5.1 — Deteccion de capacidad del dispositivo**
- Detectar mobile via `navigator.userAgent` + `'ontouchstart' in window`
- Evaluar GPU: `renderer.getContext().getParameter(renderer.getContext().MAX_TEXTURE_SIZE)`
- Clasificar en 3 tiers: `high` (desktop/tablet potente), `medium` (mobile gama alta), `low` (mobile gama media-baja)

**1.5.2 — Ajuste adaptativo de calidad**
- `high`: shadow 2048, pixel ratio 2, sphere 64x32, ground texture 512
- `medium`: shadow 1024, pixel ratio 1.5, sphere 32x16, ground texture 256
- `low`: shadows OFF, pixel ratio 1, sphere 24x12, ground texture 128
- Reducir tone mapping a `LinearToneMapping` en low tier
- Desactivar antialiasing en low tier

**1.5.3 — Lazy loading del visor 3D**
- En mobile: mostrar imagen de thumbnail del proyecto como placeholder
- Boton overlay: "Tocar para cargar visor 3D" con icono de 3D
- Solo inicializar Three.js al hacer tap (ahorra ~2MB de descarga + CPU de init)
- En desktop: cargar normalmente (sin lazy)
- Barra de progreso durante la carga del modelo

**1.5.4 — Barra de progreso de carga**
- Interceptar el `onProgress` callback del GLTFLoader
- Mostrar barra de progreso con porcentaje: "Cargando modelo 3D... 45%"
- Animacion suave de la barra
- Al completar, fade-out del overlay y fade-in del visor

**1.5.5 — Monitor de FPS y downgrade automatico**
- Medir FPS durante los primeros 3 segundos de render
- Si FPS < 20: reducir shadow map, reducir pixel ratio, notificar "Calidad reducida para mejor rendimiento"
- Toggle manual: icono de engranaje en esquina del visor para cambiar calidad

**UX esperada:** En movil, el usuario ve una imagen atractiva del proyecto con un boton claro para cargar el 3D. La carga muestra progreso real. El visor se adapta al dispositivo automaticamente. Si el rendimiento cae, se ajusta sin que el usuario tenga que hacer nada.

---

#### 1.6 PDF Exportable — Ficha de Unidad `[COMPLETADO]`

**Objetivo:** Generar fichas PDF profesionales por unidad que los agentes envien por WhatsApp/email a compradores. Reemplaza los PDFs hechos a mano en Canva/PowerPoint que usan actualmente las inmobiliarias.

**Estado actual:** DomPDF instalado. Existe un PDF para analisis estrategico. Datos completos de unidad disponibles (modelo Unit con tipologia, proyecto, plano). No existe ficha de unidad.

**Implementacion paso a paso:**

**1.6.1 — Ruta y controlador**
- `GET /projects/{slug}/units/{unit}/pdf` — publica (sin auth, como brochure)
- `UnitPdfController` con metodo `generate()`
- Rate limit: 10 PDFs/minuto por IP

**1.6.2 — Diseño del PDF (A4, profesional)**
- **Header**: logo RealEstate 3D + nombre del proyecto + ubicacion
- **Hero**: plano de planta de la unidad (si existe) o imagen de galeria del proyecto
- **Datos principales** (grid 2x3):
  - Identificador / Piso
  - Dormitorios / Banos
  - Area (m²) / Precio (USD)
- **Descripcion** de la tipologia (si existe)
- **Tabla de especificaciones**:
  - Tipologia, Estado, Precio/m²
  - Entrega estimada del proyecto
- **Galeria**: 2-3 imagenes del proyecto (miniatura)
- **QR Code**: URL directa al visor 3D con `?unit=ID`
- **Footer**: datos de contacto del proyecto (email, WhatsApp), disclaimer legal
- **Branding**: colores cyan/dark del proyecto, tipografia profesional

**1.6.3 — Boton de descarga en la UI**
- En `viewer/landing.blade.php`: icono PDF en cada fila de la tabla de unidades + boton en modal de detalle
- En `viewer/show.blade.php`: boton "Descargar ficha PDF" en panel de detalle de unidad
- Icono: documento con flecha de descarga, tooltip "Descargar ficha PDF"

**1.6.4 — Generacion de QR en el PDF**
- Usar libreria PHP `simplesoftwareio/simple-qrcode` o generar via SVG inline
- QR apunta a `{dominio}/projects/{slug}?unit={identifier}`
- Tamano: 2cm x 2cm en esquina inferior derecha

**UX esperada:** El agente encuentra la unidad, hace clic en el icono de PDF, descarga un PDF impecable en 2 segundos que puede enviar inmediatamente por WhatsApp. El comprador recibe un documento profesional con toda la info + QR para ver el proyecto en 3D.

---

### FASE 2: Diferenciadores Competitivos

---

#### 2.1 Internacionalizacion (i18n) `[COMPLETADO]`

**Objetivo:** Soportar espanol e ingles como minimo. Los compradores de Punta Cana son ~50% extranjeros (USA, Canada, Europa). Ningún competidor LATAM ofrece i18n real en visualización 3D.

**Estado actual:** Todo el texto esta hardcoded en espanol. Locale configurada como "en" pero sin archivos de traduccion. No hay directorio `lang/`. No se usa `__()` ni `@lang()`.

**Implementacion paso a paso:**

**2.1.1 — Infraestructura de traducciones**
- Crear directorios `lang/es/` y `lang/en/`
- Archivos por modulo: `general.php`, `navigation.php`, `viewer.php`, `units.php`, `landing.php`, `admin.php`, `emails.php`
- Middleware `SetLocale` que detecta: (1) parametro `?lang=`, (2) cookie, (3) header `Accept-Language`, (4) default
- Helper `locale_url()` para generar URLs con prefijo de idioma

**2.1.2 — Extraccion de strings (vistas publicas primero)**
- `welcome.blade.php`: ~40 strings
- `viewer/landing.blade.php`: ~60 strings (incluye filtros, modal, CTA)
- `viewer/show.blade.php`: ~20 strings
- `viewer/index.blade.php`: ~30 strings
- `layouts/navigation.blade.php`: ~15 strings
- Reemplazar cada string hardcoded por `{{ __('modulo.key') }}`

**2.1.3 — Selector de idioma**
- Icono de globo con dropdown: "ES" / "EN" con banderas
- Posicion: navbar, al lado del nombre de usuario (o login)
- Guarda preferencia en cookie `locale` (1 año)
- Cambia idioma sin recargar pagina (Alpine.js + fetch)

**2.1.4 — Contenido dinamico bilingue**
- Campos de proyecto: agregar `description_en`, `tagline_en`, `location_en` al modelo Project
- Migracion para nuevos campos
- Accessor `translated_description` que retorna el campo segun locale

**2.1.5 — Emails bilingues**
- Templates de email con version ES/EN
- Seleccionar idioma segun preferencia del usuario que recibe

**UX esperada:** El comprador americano llega al sitio y ve todo en ingles. Puede cambiar a espanol con un clic. La experiencia es completamente fluida, sin mezcla de idiomas. Los emails de respuesta llegan en el idioma que eligió el usuario.

---

#### 2.2 Multi-moneda `[COMPLETADO]`

**Objetivo:** Mostrar precios en USD (default), DOP, EUR y CAD. El mercado de Punta Cana opera en USD pero compradores locales piensan en DOP y europeos en EUR.

**Estado actual:** Precio unico en `decimal(12,2)`. Formateado con "USD " hardcoded en el modelo Unit y en las vistas. No hay infraestructura de monedas.

**Implementacion paso a paso:**

**2.2.1 — Configuracion de monedas**
- Tabla `currencies`: code (USD, DOP, EUR, CAD), symbol ($, RD$, €, CA$), name, exchange_rate, is_default, updated_at
- Seeder con tasas iniciales
- Panel admin para actualizar tasas manualmente
- Opcional futuro: API de tasas automatica (Open Exchange Rates)

**2.2.2 — Servicio de conversion**
- `App\Services\CurrencyService`: convert($amount, $from, $to), format($amount, $currency), getAvailableCurrencies()
- Facade `Currency::format(150000, 'EUR')` → "€150.000"
- El precio base siempre se almacena en USD

**2.2.3 — Selector de moneda en UI**
- Dropdown al lado del selector de idioma
- Opciones: USD | DOP | EUR | CAD con simbolo
- Guarda en cookie `currency` (1 año)
- Actualiza precios en la pagina via Alpine.js (sin recarga)

**2.2.4 — Actualizacion de vistas**
- `viewer/landing.blade.php`: precios de unidades con conversion
- `viewer/index.blade.php`: filtros de precio adaptados a moneda seleccionada
- `viewer/show.blade.php`: precios en panel de detalle
- PDF de unidad: incluir precio en moneda seleccionada + precio original USD

**2.2.5 — Disclaimer de conversion**
- Texto pequeño: "Precios de referencia en {moneda}. Precio oficial en USD. Tasa: 1 USD = {tasa} {moneda}"
- Fecha de actualizacion de la tasa

**UX esperada:** El usuario selecciona su moneda una vez y toda la plataforma muestra precios convertidos. Los precios incluyen un disclaimer transparente. El selector es discreto pero accesible.

---

#### 2.3 Analytics del Visor 3D `[COMPLETADO]`

**Objetivo:** Entender como los compradores interactuan con el visor 3D para optimizar la experiencia y proporcionar datos de engagement a las inmobiliarias. Ningún competidor ofrece analytics de interaccion 3D.

**Estado actual:** Google Analytics/GTM integrado a nivel de pagina. No hay tracking de eventos custom en el visor. No hay metricas de interaccion 3D.

**Implementacion paso a paso:**

**2.3.1 — Tabla de eventos del visor**
- Migracion: `viewer_events` (id, project_id, session_id, event_type, event_data JSON, unit_id nullable, ip, user_agent, created_at)
- Tipos de evento: `model_loaded`, `unit_selected`, `unit_focused`, `comparison_opened`, `pdf_downloaded`, `inquiry_sent`, `whatsapp_clicked`, `share_clicked`, `session_start`, `session_end`

**2.3.2 — Tracking JS en el visor**
- Endpoint `POST /api/viewer-events` (no auth, rate limited)
- Batch de eventos: enviar cada 10 segundos o al cerrar pagina (`beforeunload`)
- Datos por sesion: duracion, unidades vistas, interacciones con controles, zoom level
- Heatmap data: guardar angulos de camara (donde mira el usuario)

**2.3.3 — Dashboard de analytics en admin**
- Nueva seccion "Analytics" en el admin
- Metricas por proyecto:
  - Sesiones unicas en visor (dia/semana/mes)
  - Tiempo promedio en visor
  - Unidades mas vistas (ranking con barras)
  - Tasa de conversion: vista → consulta
  - Dispositivo: mobile vs desktop (pie chart)
  - Fuente de trafico (si disponible)
- Graficos: Chart.js o ApexCharts via CDN
- Filtros: rango de fechas, comparar periodos

**2.3.4 — Eventos en Google Analytics**
- Enviar `gtag('event', ...)` para cada interaccion
- Categorias: 'viewer_3d', 'unit_interaction', 'lead_generation'
- Permite que inmobiliarias que ya usan GA vean datos integrados

**UX esperada (admin):** Dashboard limpio con las metricas clave visibles de un vistazo. Graficos interactivos. El gestor puede decir al developer: "La unidad B301 tiene 3x mas vistas que las demas — subamos el precio". Datos accionables, no solo numeros.

---

#### 2.4 Planes de Pago Interactivos `[COMPLETADO]`

**Objetivo:** Visualizar la estructura de pago de cada unidad (tipico en pre-construccion: 30% reserva, 20% en obra gris, 20% en acabados, 30% contra entrega). Reduce friccion: el comprador entiende el compromiso financiero sin preguntar.

**Estado actual:** Solo campo `price` en unidades. No hay infraestructura de pagos o hitos financieros.

**Implementacion paso a paso:**

**2.4.1 — Modelo de datos**
- Tabla `payment_plans`: id, project_id, name ("Plan Estandar"), is_default
- Tabla `payment_milestones`: id, payment_plan_id, name ("Reserva"), percentage, description, due_description ("Al firmar contrato"), sort_order
- Un proyecto puede tener multiples planes (ej: "Contado" con descuento, "Financiado")
- CRUD en admin vinculado al proyecto

**2.4.2 — Visualizacion en landing page**
- Seccion "Plan de pago" en landing debajo de la tabla de unidades
- Timeline visual horizontal: circulos conectados por linea
- Cada hito: porcentaje grande + nombre + descripcion
- Al seleccionar unidad: timeline muestra montos absolutos (30% de $150,000 = $45,000)
- Animacion: los montos se calculan y actualizan al cambiar la unidad seleccionada

**2.4.3 — Simulador de pagos**
- Dropdown: "Selecciona una unidad" → muestra desglose completo
- Tabla: Hito | Porcentaje | Monto (USD) | Momento
- Total al final con verificacion (debe sumar 100%)
- Boton "Consultar este plan" pre-llena el formulario de consulta con la unidad y plan

**2.4.4 — Inclusion en PDF de unidad**
- Agregar seccion "Plan de Pago" en la ficha PDF
- Tabla con hitos y montos calculados para esa unidad

**UX esperada:** El comprador ve una timeline visual atractiva. Al seleccionar su unidad, los montos se calculan automaticamente. Entiende el compromiso financiero de inmediato. Puede consultar directamente sobre ese plan especifico.

---

#### 2.5 WhatsApp Business API `[COMPLETADO]` (fase tracking)

**Objetivo:** Elevar WhatsApp de simple enlace wa.me a canal de ventas inteligente: respuestas automaticas, routing por agente, tracking de conversaciones.

**Estado actual:** Boton flotante de WhatsApp con mensaje pre-formateado. Enlace wa.me funcional con numero del proyecto. WhatsApp de contacto por unidad en modal.

**Implementacion paso a paso:**

**2.5.1 — Tracking de clics WhatsApp (inmediato, sin API)**
- Registrar cada click en wa.me como evento en `viewer_events`
- Datos: proyecto, unidad (si aplica), timestamp, device
- Dashboard: "Clics a WhatsApp" como metrica

**2.5.2 — Integracion WhatsApp Cloud API (requiere Meta Business)**
- Configurar Meta Business Manager + WhatsApp Business Account
- Webhook para recibir mensajes entrantes
- Template messages para respuesta automatica:
  - "Gracias por tu interes en {proyecto}. Un agente te contactara en breve."
  - Incluir link a la ficha del proyecto
- Routing: asignar conversacion al agente correcto segun proyecto/unidad

**2.5.3 — Panel de conversaciones en admin**
- Vista de mensajes WhatsApp recibidos vinculados a proyecto/unidad
- Respuesta desde el admin (via API)
- Estado: nuevo/en_progreso/cerrado
- Merge con consultas del formulario (vista unificada de leads)

**2.5.4 — Automatizacion basica**
- Auto-respuesta con horario de oficina: "Estamos disponibles de L-V 9-18h. Te contactaremos manana."
- Follow-up automatico: si no hay respuesta en 24h, enviar recordatorio
- Mensaje de bienvenida con menu: "1. Ver unidades disponibles 2. Hablar con un agente 3. Recibir brochure PDF"

**UX esperada:** El comprador escribe por WhatsApp y recibe respuesta inmediata (automatica). El agente ve la conversacion en el admin con contexto (proyecto, unidad de interes). No se pierde ningun lead.

> **Nota:** La integracion completa con WhatsApp Cloud API requiere una cuenta de Meta Business verificada. La fase 2.5.1 (tracking de clics) se puede implementar de inmediato.

---

#### 2.6 Progreso de Obra `[COMPLETADO]`

**Objetivo:** Modulo para mostrar el avance de construccion del proyecto con timeline, fotos y porcentaje de avance. Genera confianza en compradores y reduce consultas repetitivas sobre "como va la obra".

**Estado actual:** Solo existe campo `estimated_delivery` (date) en proyectos. No hay tablas, modelos ni vistas de progreso.

**Implementacion paso a paso:**

**2.6.1 — Modelo de datos**
- Tabla `construction_phases`: id, project_id, name ("Cimentacion", "Estructura", "Acabados"), description, percentage (0-100), sort_order
- Tabla `construction_updates`: id, project_id, phase_id, title, description, date, progress_percentage (del proyecto total), created_by
- Tabla `construction_update_images`: id, update_id, image_path, caption, sort_order

**2.6.2 — Admin: gestion de progreso**
- Seccion en la edicion del proyecto: "Progreso de Obra"
- Crear/editar fases con nombre y porcentaje objetivo
- Crear actualizaciones: titulo, descripcion, fotos (drag & drop multiple), porcentaje de avance
- Vista previa del timeline

**2.6.3 — Vista publica: timeline de progreso**
- Seccion en `viewer/landing.blade.php` entre galeria y unidades
- Timeline vertical (mobile) / horizontal (desktop)
- Cada fase: nombre, estado (completado/en curso/pendiente), porcentaje
- Barra de progreso global del proyecto (ej: "65% completado")
- Al expandir una fase: fotos de la actualizacion mas reciente con carrusel
- Fecha de ultima actualizacion visible

**2.6.4 — Notificaciones de actualizacion**
- Al publicar una actualizacion: email a compradores con unidad reservada/vendida en ese proyecto
- "Hay novedades en tu proyecto {nombre}: {titulo de la actualizacion}"
- Incluir foto destacada y link a la landing

**UX esperada:** El comprador ve una barra de progreso prominente en la landing del proyecto. Puede explorar cada fase con fotos reales. Genera confianza y emocion por el avance. Recibe emails automaticos cuando hay novedades.

---

#### 2.7 Calculadora de Inversion `[COMPLETADO]`

**Objetivo:** Herramienta interactiva que proyecta el retorno de inversion (ROI) para compradores-inversores. Punta Cana atrae inversores que buscan rental yield de Airbnb/booking. Ningun competidor ofrece esto.

**Estado actual:** No existe ningun calculo financiero. Solo campo de precio por unidad.

**Implementacion paso a paso:**

**2.7.1 — Datos de referencia**
- Configuracion por proyecto (admin): rental_yield_annual (%), average_occupancy (%), appreciation_rate_annual (%), management_fee (%), property_tax_rate (%)
- Valores por defecto para Punta Cana: yield ~8-12%, ocupacion ~65-75%, apreciacion ~5-8%
- Fuentes: datos de Airbnb/Booking de la zona

**2.7.2 — Interfaz de la calculadora**
- Componente en landing page (seccion dedicada) + modal accesible desde unidad
- Inputs del usuario (sliders + inputs numericos):
  - Unidad seleccionada (o precio manual)
  - Enganche / entrada (% del precio)
  - Tipo de cambio si aplica
  - Horizonte de inversion (1-10 años, slider)
  - Ocupacion estimada (slider, default del proyecto)
  - Tarifa noche estimada (input, sugerida por zona)
- Outputs (actualizacion en tiempo real):
  - Ingreso bruto anual estimado
  - Gastos estimados (administracion, impuestos, mantenimiento)
  - Ingreso neto anual
  - ROI anual (%)
  - Payback period (años)
  - Valor estimado de la propiedad en X años (apreciacion)
  - Grafico de proyeccion (lineas: valor propiedad + ingresos acumulados)

**2.7.3 — Visualizacion**
- Graficos con Chart.js: linea de acumulacion de ingresos vs inversion inicial
- KPIs grandes y claros: ROI, Payback, Ingreso mensual
- Disclaimer: "Proyeccion estimativa. Rendimientos pasados no garantizan resultados futuros."

**2.7.4 — Integracion con el flujo de venta**
- Boton "Me interesa esta inversion" → abre formulario de consulta con datos pre-llenados
- Opcion de enviar la proyeccion por email (auto-generado)
- Inclusion en el PDF de la unidad si se genera desde la calculadora

**UX esperada:** El inversor juega con los parametros y ve en tiempo real como se comporta su inversion. Los numeros grandes (ROI, ingreso mensual) captan la atencion. El grafico visualiza el crecimiento. El disclaimer genera confianza por transparencia. El CTA cierra el loop hacia la consulta.

---

### Orden de Ejecucion Recomendado

| Prioridad | Tarea | Justificacion |
|-----------|-------|---------------|
| 1 | 1.1 SEO Tecnico | Base para todo el trafico organico. Sin esto, nadie encuentra la plataforma |
| 2 | 1.6 PDF Exportable | Los agentes lo necesitan HOY. Herramienta de venta directa |
| 3 | 1.2 Compartir en Redes | Multiplicador de alcance. Cada share es publicidad gratuita |
| 4 | 1.5 Performance 3D Movil | 85% del trafico es movil. Si no funciona bien, se van |
| 5 | 1.3 Notificaciones Tiempo Real | Mejora operativa interna. Reduce tiempo de respuesta |
| 6 | 1.4 Comparador de Unidades | Diferenciador UX. Ayuda a la decision de compra |
| 7 | 2.7 Calculadora de Inversion | Hook para inversores. Diferenciador unico |
| 8 | 2.4 Planes de Pago | Reduce friccion financiera. Complementa la calculadora |
| 9 | 2.3 Analytics del Visor | Datos para optimizar todo lo anterior |
| 10 | 2.1 Internacionalizacion | Critico para mercado internacional pero alto esfuerzo |
| 11 | 2.2 Multi-moneda | Complemento natural de i18n |
| 12 | 2.6 Progreso de Obra | Genera confianza a largo plazo |
| 13 | 2.5 WhatsApp Business API | Requiere cuenta Meta Business verificada |
