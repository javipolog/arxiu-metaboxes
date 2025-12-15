# Arxiu Monòver - Plugin WordPress

Plugin unificat per a la gestió completa del CPT Arxiu amb IA (Gemini Vision), dates, ubicacions (Mapbox), gèneres, autor i col·lecció.

## Versió Actual: 17.2.0

### Changelog

#### 17.2.0 (Desembre 2024) - Fase 3: Integració Mediateca
- **📷 Columna IA a la Mediateca**
  - Nova columna "🤖 IA" a la llista de fitxers
  - Indicadors visuals: ✅ analitzat, ⏳ pendent, 🔄 processant, ❌ error
  - Botó ràpid per afegir a la cua
  
- **🔄 Accions Bulk**
  - Nova acció "Afegir a cua IA" per múltiples imatges
  - Selecciona imatges i analitza-les totes d'un cop
  
- **⚡ Auto-anàlisi (Opcional)**
  - Opció per analitzar automàticament imatges noves
  - Configurable des de Configuració
  - Respecta límit de quota
  
- **📋 Detalls d'Attachment**
  - Informació d'anàlisi al modal de la mediateca
  - Mostra gènere, tags, resum
  - Botó per re-analitzar
  
- **🚀 Càrrega Instantània**
  - Quan selecciones una imatge ja analitzada, les dades es carreguen instantàniament
  - Sense espera - sense crida a l'API

#### 17.1.0 (Desembre 2024) - Fase 2: Sistema de Cua
- **🔄 Sistema de Cua Intel·ligent**
  - Nova taula `wp_arxiu_queue` per gestionar processament
  - WP-Cron cada 2 minuts per processar imatges en background
  - Batch de 5 imatges per execució (respecta quota gratuïta)
  - Retry automàtic si falla (màx 3 intents)
  
- **📊 Dashboard de Cua**
  - Nova pàgina "Arxius > Cua IA" al menú admin
  - Estadístiques en temps real: pendents, processant, completats, fallats
  - Barra de quota diària (1.400/dia per defecte)
  - Accions: processar ara, reintentar fallats, buidar cua
  
- **🎯 Metadades d'Attachments**
  - Resultats d'anàlisi guardats com a post_meta dels attachments
  - Accés instantani sense recalcular
  - `_arxiu_analysis` - JSON complet de l'anàlisi
  - `_arxiu_analyzed_at` - Timestamp de l'anàlisi
  
- **⚡ Control de Quota**
  - Límit diari automàtic (1.400 crides)
  - Pausa automàtica si es rep error 429
  - Despausar manualment des del dashboard

#### 17.0.0 (Desembre 2024) - Fase 1: Optimització API
- **🚀 Anàlisi Unificada**
  - 1 sola crida a Gemini en lloc de 2 (50% menys peticions!)
  - Retorna: gènere + tags + data + ubicació tot junt
  - Millor aprofitament de la quota gratuïta

- **🤖 Models Gemini Actualitzats**
  - Nou model per defecte: `gemini-2.5-flash` (gratuït + thinking)
  - Model alternatiu: `gemini-2.5-flash-lite` (ultra ràpid)
  - Fallback automàtic si un model no està disponible

- **📊 Control de Quota**
  - Comptador de crides diàries
  - Informació de quota a la resposta
  - Límit diari configurable (1.500 RPD gratuït)

- **🔧 Mode Debug**
  - Nova constant `ARXIU_DEBUG` per controlar logging
  - Desactivat per defecte en producció
  - Activar amb `define('ARXIU_DEBUG', true);`

- **⚡ Optimitzacions**
  - Cache millorada amb clau única per imatge
  - Retry intel·ligent amb models alternatius
  - Millor gestió d'errors de quota (429)

#### 15.1.0 - Knowledge Base i Detecció Visual
- Sistema de detecció de cares amb coordenades corregides
- Knowledge base amb característiques visuals per a IA

#### 15.0.0 - Correcció Crítica de Detecció
- Fix del bug de posicionament de rectangles de detecció
- Ús de coordenades DOM en lloc de natural image dimensions

#### 14.x - Versions anteriors
- Integració Gemini Vision API
- Sistema de predicció de dates
- Mapa Mapbox bàsic

## Estructura de Fitxers

```
arxiu-monovar/
├── arxiu-monovar.php          # Fitxer principal del plugin
├── README.md                  # Aquest fitxer
├── assets/
│   ├── css/
│   │   ├── admin.css          # Estils generals d'admin
│   │   └── mapbox-ubicacio.css # Estils del mapa
│   └── js/
│       ├── admin.js           # Lògica principal d'admin
│       └── mapbox-ubicacio.js # Mòdul del mapa
├── includes/
│   ├── class-credentials.php  # Gestió de credencials API
│   ├── class-date-detector.php # Detecció de dates
│   ├── class-gemini-api.php   # Integració Gemini Vision (v17.0+)
│   ├── class-genre-classifier.php # Classificador de gèneres
│   ├── class-knowledge-base.php # Base de coneixement de Monòver
│   ├── class-mapbox-sync.php  # Sincronització amb Mapbox
│   └── class-queue-manager.php # 🆕 Gestió de cua (v17.1+)
└── templates/
    ├── metabox-autor-colleccio.php
    ├── metabox-data.php
    ├── metabox-generes.php
    ├── metabox-imagen.php
    ├── metabox-ubicacio.php
    ├── queue-page.php         # 🆕 Dashboard de cua
    ├── queue-status.php       # 🆕 Estat de la cua
    ├── settings-mapbox.php
    ├── settings-page.php
    └── sync-mapbox.php
```

## Sistema de Cua (v17.1+)

### Funcionament

```
Upload 100 imatges
       │
       ▼
┌─────────────────────┐
│ Afegir a cua        │ (instant)
│ status: pending     │
└─────────┬───────────┘
          │
     WP-Cron cada 2 min
          │
          ▼
┌─────────────────────┐
│ Processar 5 imatges │
│ status: processing  │
└─────────┬───────────┘
          │
          ▼
┌─────────────────────┐     ┌─────────────────────┐
│ Cridar Gemini       │────▶│ Guardar metadata    │
│ analyze_complete()  │     │ status: completed   │
└─────────────────────┘     └─────────────────────┘

⏱️ 100 imatges = ~40 minuts (en background)
```

### Accedir a resultats des de codi

```php
// Verificar si attachment està analitzat
$analyzed = Arxiu_Queue_Manager::is_attachment_analyzed( $attachment_id );

// Obtenir resultats de l'anàlisi
$analysis = Arxiu_Queue_Manager::get_attachment_analysis( $attachment_id );
if ( $analysis ) {
    echo $analysis['genre'];          // "fotografia"
    echo $analysis['summary'];        // "Descripció de la imatge..."
    print_r( $analysis['tags_val'] ); // ["festa", "processó", ...]
}
```

## Integració Mediateca (v17.2+)

### Columna IA a la Llista de Fitxers

Quan vas a **Multimèdia > Biblioteca** en mode llista, veuràs una columna "🤖 IA":

| Icona | Significat |
|-------|------------|
| 📷/📄/🎨 | Analitzat (gènere detectat) |
| ⏳ | A la cua (pendent) |
| 🔄 | Processant ara |
| ❌ | Error (pots reintentar) |
| ➕ | No analitzat (clic per afegir) |

### Acció Bulk

1. Selecciona múltiples imatges a la mediateca
2. Tria "🤖 Afegir a cua IA" del menú d'accions
3. Les imatges s'afegiran a la cua de processament

### Auto-anàlisi

Per activar l'anàlisi automàtica:
1. Ves a **Arxius > Configuració**
2. A la secció "Integració Mediateca"
3. Marca "Analitzar automàticament imatges noves"

### Càrrega Instantània

Quan selecciones una imatge al metabox d'un Arxiu:
- Si ja està analitzada → les dades es carreguen instantàniament
- Si no està analitzada → es fa l'anàlisi amb Gemini

Això significa que si tens 1.000 imatges pre-analitzades, pots crear arxius sense esperar!

## API JavaScript del Mapa

### ArxiuUbicacio (window.ArxiuUbicacio)

```javascript
// Seleccionar una ubicació des de codi (p.ex. des de l'anàlisi IA)
ArxiuUbicacio.selectFromIA('torre-del-rellotge'); // Retorna true/false

// Obtenir la selecció actual
ArxiuUbicacio.getSelection(); // { barri: 'centre', pois: ['torre-del-rellotge'], lat: '38.4372', lng: '-0.8389' }

// Obtenir totes les ubicacions disponibles
ArxiuUbicacio.getAllLocations(); // [{ slug, name, type }, ...]

// Netejar selecció
ArxiuUbicacio.clearSelection();

// Volar a una ubicació
ArxiuUbicacio.flyTo('ermita-santa-barbara');
```

## Configuració Mapbox

1. Ves a **Arxius > Mapbox** al menú d'admin
2. Introdueix el teu Access Token de Mapbox
3. Configura els tilesets de POIs i Barris
4. Guarda els canvis

## Ús de la Detecció IA

1. Puja una imatge al metabox "Imatge Arxiu"
2. Fes clic a "Analitzar amb IA"
3. La IA detectarà:
   - Elements visuals
   - Persones (amb rectangles de detecció)
   - Ubicació probable
   - Data estimada
   - Festes detectades

4. Si es detecta una ubicació, fes clic a "Marcar" per seleccionar-la al mapa

## Requisits

- WordPress 5.9+
- PHP 7.4+
- Token de Mapbox (per al mapa)
- API Key de Google Gemini (per a l'anàlisi IA)

## Autors

- Javi Polo
- ARXIU MONÒVER
