/**
 * Arxiu Monòver - Mapbox Ubicació v16.3.0
 * 
 * SOLUCIÓ ROBUSTA:
 * - Construeix índex de slugs al carregar les dades
 * - Usa querySourceFeatures (no queryRenderedFeatures) per obtenir TOTS els POIs
 * - Valida que les features existeixin abans de setFeatureState
 * - Auto-correcció intel·ligent de slugs
 * - Debug detallat per diagnosticar problemes
 */

(function() {
    'use strict';

    const VERSION = '16.3.1';
    
    console.log('═══════════════════════════════════════════════════');
    console.log(`🗺️ MAPBOX UBICACIÓ v${VERSION} CARREGANT...`);
    console.log('═══════════════════════════════════════════════════');

    // ═══════════════════════════════════════════════════════════════════════
    // ESTAT GLOBAL
    // ═══════════════════════════════════════════════════════════════════════
    
    let map = null;
    let config = null;
    let selectedBarri = null;
    let selectedPois = new Set();
    
    // ÍNDEXS DE SLUGS (es construeixen quan les dades carreguen)
    let poiIndex = new Map();      // poi_slug -> feature data
    let barriIndex = new Map();    // barri_slug -> feature data
    let dataLoaded = false;
    let pendingSelection = null;   // Selecció pendent si les dades no han carregat

    // ═══════════════════════════════════════════════════════════════════════
    // INICIALITZACIÓ
    // ═══════════════════════════════════════════════════════════════════════

    document.addEventListener('DOMContentLoaded', function() {
        
        console.log('📋 Verificant dependències...');
        
        if (typeof mapboxgl === 'undefined') {
            console.error('❌ mapboxgl NO DISPONIBLE');
            return;
        }
        console.log('✓ mapboxgl disponible');
        
        if (typeof ArxiuMapboxData === 'undefined') {
            console.error('❌ ArxiuMapboxData NO DEFINIT');
            return;
        }
        console.log('✓ ArxiuMapboxData disponible');
        
        const container = document.getElementById('arxiu-map');
        if (!container) {
            console.error('❌ #arxiu-map NO TROBAT');
            return;
        }
        console.log('✓ #arxiu-map trobat');
        
        if (!ArxiuMapboxData.token) {
            console.error('❌ Token de Mapbox buit!');
            return;
        }
        console.log('✓ Token present');
        
        config = ArxiuMapboxData;
        
        // DEBUG: Mostrar configuració
        console.log('📦 Configuració rebuda:');
        console.log(`   sources.pois: ${config.sources?.pois}`);
        console.log(`   sources.barris: ${config.sources?.barris}`);
        console.log(`   layers.poi: ${config.layers?.poi}`);
        console.log(`   layers.barri: ${config.layers?.barri}`);
        
        initMap();
    });

    function initMap() {
        mapboxgl.accessToken = config.token;
        
        let center = config.center;
        if (!center || !Array.isArray(center) || isNaN(center[0]) || isNaN(center[1])) {
            center = [-0.8389, 38.4372];
        }
        
        let zoom = config.zoom;
        if (!zoom || isNaN(zoom)) {
            zoom = 13.5;
        }
        
        console.log(`📍 Centre: [${center[0]}, ${center[1]}], Zoom: ${zoom}`);
        
        try {
            map = new mapboxgl.Map({
                container: 'arxiu-map',
                style: config.style || 'mapbox://styles/mapbox/light-v11',
                center: center,
                zoom: zoom,
                pitchWithRotate: false,
                dragRotate: false
            });
            
            map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');
            map.on('load', onMapLoad);
            
            console.log('✓ Mapa creat correctament');
            
        } catch (error) {
            console.error('❌ Error creant mapa:', error);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CÀRREGA DEL MAPA
    // ═══════════════════════════════════════════════════════════════════════

    function onMapLoad() {
        console.log('✓ Event map.load disparat');
        
        setTimeout(() => map.resize(), 100);
        
        // Afegir fonts amb promoteId
        map.addSource('src-barris', {
            type: 'vector',
            url: config.sources.barris,
            promoteId: 'barri_slug'
        });
        
        map.addSource('src-pois', {
            type: 'vector',
            url: config.sources.pois,
            promoteId: 'poi_slug'
        });

        // Capa fill barris
        map.addLayer({
            id: 'ly-barri-fill',
            type: 'fill',
            source: 'src-barris',
            'source-layer': config.layers.barri,
            paint: {
                'fill-color': [
                    'case',
                    ['boolean', ['feature-state', 'selected'], false],
                    '#ff6600',
                    '#ffb0e0'
                ],
                'fill-opacity': [
                    'case',
                    ['boolean', ['feature-state', 'selected'], false],
                    0.6,
                    0.35
                ]
            }
        });

        // Capa línia barris
        map.addLayer({
            id: 'ly-barri-line',
            type: 'line',
            source: 'src-barris',
            'source-layer': config.layers.barri,
            paint: {
                'line-color': [
                    'case',
                    ['boolean', ['feature-state', 'selected'], false],
                    '#cc3300',
                    '#666666'
                ],
                'line-width': [
                    'case',
                    ['boolean', ['feature-state', 'selected'], false],
                    5,
                    2
                ]
            }
        });

        // Capa cercles POIs
        map.addLayer({
            id: 'ly-poi-circle',
            type: 'circle',
            source: 'src-pois',
            'source-layer': config.layers.poi,
            paint: {
                'circle-radius': 10,
                'circle-stroke-width': 3,
                'circle-stroke-color': '#fff',
                'circle-color': [
                    'case',
                    ['boolean', ['feature-state', 'selected'], false],
                    '#ff0000',
                    '#0088ff'
                ]
            }
        });

        // Etiquetes POIs
        map.addLayer({
            id: 'ly-poi-label',
            type: 'symbol',
            source: 'src-pois',
            'source-layer': config.layers.poi,
            filter: ['has', 'name'],
            layout: {
                'text-field': ['get', 'name'],
                'text-size': 12,
                'text-offset': [0, 1.5],
                'text-anchor': 'top',
                'text-font': ['Open Sans Bold', 'Arial Unicode MS Bold']
            },
            paint: {
                'text-halo-color': '#fff',
                'text-halo-width': 2,
                'text-color': '#333'
            }
        });

        console.log('✓ Capes afegides');

        // Events de clic
        map.on('click', ['ly-poi-circle', 'ly-poi-label'], onPoiClick);
        map.on('click', 'ly-barri-fill', onBarriClick);

        // Cursors
        ['ly-poi-circle', 'ly-poi-label', 'ly-barri-fill'].forEach(layer => {
            map.on('mouseenter', layer, () => map.getCanvas().style.cursor = 'pointer');
            map.on('mouseleave', layer, () => map.getCanvas().style.cursor = '');
        });

        // Botó netejar
        const clearBtn = document.getElementById('arxiu-ubicacio-clear');
        if (clearBtn) {
            clearBtn.addEventListener('click', clearAll);
        }

        // IMPORTANT: Esperar que les dades es carreguin per construir l'índex
        setupDataLoadListener();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CONSTRUCCIÓ DE L'ÍNDEX DE SLUGS
    // ═══════════════════════════════════════════════════════════════════════

    function setupDataLoadListener() {
        console.log('⏳ Esperant càrrega de dades del tileset...');
        
        let poisLoaded = false;
        let barrisLoaded = false;
        
        function checkAllLoaded() {
            if (poisLoaded && barrisLoaded) {
                dataLoaded = true;
                console.log('═══════════════════════════════════════════════════');
                console.log('✅ DADES CARREGADES COMPLETAMENT');
                console.log(`   📍 POIs indexats: ${poiIndex.size}`);
                console.log(`   🏘️ Barris indexats: ${barriIndex.size}`);
                console.log('═══════════════════════════════════════════════════');
                
                // DEBUG: Mostrar alguns slugs disponibles
                if (poiIndex.size > 0) {
                    const samplePois = Array.from(poiIndex.keys()).slice(0, 5);
                    console.log('   Exemple POIs:', samplePois.join(', '));
                }
                if (barriIndex.size > 0) {
                    const sampleBarris = Array.from(barriIndex.keys()).slice(0, 5);
                    console.log('   Exemple Barris:', sampleBarris.join(', '));
                }
                
                hideLoading();
                
                // Restaurar selecció guardada
                restoreSelection();
                
                // Processar selecció pendent si n'hi ha
                if (pendingSelection) {
                    console.log('🔄 Processant selecció pendent...');
                    applySelectionInternal(pendingSelection.poi, pendingSelection.barri);
                    pendingSelection = null;
                }
            }
        }
        
        // Escoltar l'event sourcedata per saber quan les dades estan disponibles
        map.on('sourcedata', function(e) {
            if (e.sourceId === 'src-pois' && e.isSourceLoaded && !poisLoaded) {
                // Donar temps perquè les features estiguin disponibles
                setTimeout(() => {
                    buildPoiIndex();
                    poisLoaded = true;
                    checkAllLoaded();
                }, 500);
            }
            
            if (e.sourceId === 'src-barris' && e.isSourceLoaded && !barrisLoaded) {
                setTimeout(() => {
                    buildBarriIndex();
                    barrisLoaded = true;
                    checkAllLoaded();
                }, 500);
            }
        });
        
        // Timeout de seguretat
        setTimeout(() => {
            if (!dataLoaded) {
                console.warn('⚠️ Timeout esperant dades. Intentant construir índex igualment...');
                if (!poisLoaded) buildPoiIndex();
                if (!barrisLoaded) buildBarriIndex();
                poisLoaded = true;
                barrisLoaded = true;
                checkAllLoaded();
            }
        }, 5000);
    }

    function buildPoiIndex() {
        try {
            // querySourceFeatures obté TOTES les features de la font, no només les visibles
            const features = map.querySourceFeatures('src-pois', {
                sourceLayer: config.layers.poi
            });
            
            console.log(`📍 POIs trobats al tileset: ${features.length}`);
            
            features.forEach(f => {
                const slug = f.properties?.poi_slug;
                if (slug && !poiIndex.has(slug)) {
                    poiIndex.set(slug, {
                        name: f.properties.name || slug,
                        parentBarri: f.properties.parent_barri_slug,
                        coords: f.geometry?.coordinates
                    });
                }
            });
        } catch (e) {
            console.error('❌ Error construint índex POIs:', e);
        }
    }

    function buildBarriIndex() {
        try {
            const features = map.querySourceFeatures('src-barris', {
                sourceLayer: config.layers.barri
            });
            
            console.log(`🏘️ Barris trobats al tileset: ${features.length}`);
            
            features.forEach(f => {
                const slug = f.properties?.barri_slug;
                if (slug && !barriIndex.has(slug)) {
                    barriIndex.set(slug, {
                        name: f.properties.name || slug
                    });
                }
            });
        } catch (e) {
            console.error('❌ Error construint índex Barris:', e);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // RESTAURAR SELECCIÓ INICIAL
    // ═══════════════════════════════════════════════════════════════════════

    function restoreSelection() {
        const savedSelection = config.selected || [];
        if (savedSelection.length === 0) {
            console.log('✓ Cap selecció prèvia a restaurar');
            return;
        }
        
        console.log('🔄 Restaurant selecció:', savedSelection);
        
        savedSelection.forEach(slug => {
            // Comprovar si és un barri
            if (barriIndex.has(slug)) {
                selectBarriBySlug(slug);
                return;
            }
            
            // Comprovar si és un POI
            if (poiIndex.has(slug)) {
                selectPoiBySlug(slug);
                return;
            }
            
            console.warn(`⚠️ Slug no trobat a l'índex: ${slug}`);
        });
        
        updateUI();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SELECCIÓ PER SLUG (funcions internes)
    // ═══════════════════════════════════════════════════════════════════════

    function selectPoiBySlug(slug, autoSelectParent = true) {
        if (!poiIndex.has(slug)) {
            console.warn(`⚠️ POI no existeix a l'índex: ${slug}`);
            return false;
        }
        
        const poiData = poiIndex.get(slug);
        
        // Aplicar feature state
        map.setFeatureState(
            { source: 'src-pois', sourceLayer: config.layers.poi, id: slug },
            { selected: true }
        );
        
        selectedPois.add(slug);
        syncCheckbox(slug, true);
        
        console.log(`   ✅ POI seleccionat: ${slug} (${poiData.name})`);
        
        // Auto-seleccionar barri pare
        if (autoSelectParent && poiData.parentBarri) {
            if (selectedBarri !== poiData.parentBarri) {
                selectBarriBySlug(poiData.parentBarri);
            }
        }
        
        return true;
    }

    function deselectPoiBySlug(slug) {
        if (!selectedPois.has(slug)) return;
        
        map.setFeatureState(
            { source: 'src-pois', sourceLayer: config.layers.poi, id: slug },
            { selected: false }
        );
        
        selectedPois.delete(slug);
        syncCheckbox(slug, false);
        
        console.log(`   ❌ POI deseleccionat: ${slug}`);
    }

    function selectBarriBySlug(slug) {
        if (!barriIndex.has(slug)) {
            console.warn(`⚠️ Barri no existeix a l'índex: ${slug}`);
            return false;
        }
        
        // Deseleccionar anterior si n'hi ha
        if (selectedBarri && selectedBarri !== slug) {
            map.setFeatureState(
                { source: 'src-barris', sourceLayer: config.layers.barri, id: selectedBarri },
                { selected: false }
            );
            syncCheckbox(selectedBarri, false);
        }
        
        // Seleccionar nou
        map.setFeatureState(
            { source: 'src-barris', sourceLayer: config.layers.barri, id: slug },
            { selected: true }
        );
        
        selectedBarri = slug;
        syncCheckbox(slug, true);
        
        const barriData = barriIndex.get(slug);
        console.log(`   ✅ Barri seleccionat: ${slug} (${barriData.name})`);
        
        return true;
    }

    function deselectBarriBySlug(slug) {
        if (selectedBarri !== slug) return;
        
        map.setFeatureState(
            { source: 'src-barris', sourceLayer: config.layers.barri, id: slug },
            { selected: false }
        );
        
        syncCheckbox(slug, false);
        selectedBarri = null;
        
        console.log(`   ❌ Barri deseleccionat: ${slug}`);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // EVENTS DE CLIC
    // ═══════════════════════════════════════════════════════════════════════

    function onPoiClick(e) {
        if (!e.features || e.features.length === 0) return;
        e.originalEvent.stopPropagation();
        
        const feature = e.features[0];
        const slug = feature.properties.poi_slug;
        const name = feature.properties.name || slug;
        
        console.log('═══════════════════════════════════════════════════');
        console.log(`🖱️ CLIC POI: "${name}" (${slug})`);
        
        // TOGGLE POI
        if (selectedPois.has(slug)) {
            deselectPoiBySlug(slug);
        } else {
            selectPoiBySlug(slug, true);
        }
        
        updateCoords(e.lngLat);
        updateUI();
        logState();
    }

    function onBarriClick(e) {
        if (!e.features || e.features.length === 0) return;
        
        // Ignorar si hi ha POI al mateix punt
        const poisAtPoint = map.queryRenderedFeatures(e.point, {
            layers: ['ly-poi-circle', 'ly-poi-label']
        });
        if (poisAtPoint.length > 0) return;
        
        const feature = e.features[0];
        const slug = feature.properties.barri_slug;
        const name = feature.properties.name || slug;
        
        console.log('═══════════════════════════════════════════════════');
        console.log(`🖱️ CLIC BARRI: "${name}" (${slug})`);
        
        // TOGGLE BARRI
        if (selectedBarri === slug) {
            deselectBarriBySlug(slug);
        } else {
            selectBarriBySlug(slug);
        }
        
        updateCoords(e.lngLat);
        updateUI();
        logState();
    }

    function clearAll() {
        console.log('🧹 NETEJANT...');
        
        if (selectedBarri) {
            deselectBarriBySlug(selectedBarri);
        }
        
        selectedPois.forEach(slug => {
            deselectPoiBySlug(slug);
        });
        
        // Netejar UI
        const areaInput = document.getElementById('arxiu-ubicacio-area');
        const latInput = document.getElementById('arxiu-ubicacio-lat');
        const lngInput = document.getElementById('arxiu-ubicacio-lng');
        const latDisplay = document.getElementById('arxiu-lat-display');
        const lngDisplay = document.getElementById('arxiu-lng-display');
        
        if (areaInput) areaInput.value = '';
        if (latInput) latInput.value = '';
        if (lngInput) lngInput.value = '';
        if (latDisplay) latDisplay.textContent = '—';
        if (lngDisplay) lngDisplay.textContent = '—';
        
        console.log('✅ NETEJAT');
        logState();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CERCA INTEL·LIGENT DE SLUGS
    // ═══════════════════════════════════════════════════════════════════════

    function findBestPoiMatch(inputSlug) {
        if (!inputSlug) return null;
        
        // 1. Coincidència exacta
        if (poiIndex.has(inputSlug)) {
            return inputSlug;
        }
        
        // 2. Normalitzar i buscar
        const normalize = (s) => s.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]/g, '');
        
        const normalizedInput = normalize(inputSlug);
        
        for (const [slug, data] of poiIndex) {
            const normalizedSlug = normalize(slug);
            
            // Coincidència exacta normalitzada
            if (normalizedSlug === normalizedInput) {
                console.log(`   🔄 POI corregit: "${inputSlug}" → "${slug}"`);
                return slug;
            }
            
            // Un conté l'altre
            if (normalizedSlug.includes(normalizedInput) || normalizedInput.includes(normalizedSlug)) {
                console.log(`   🔄 POI aproximat: "${inputSlug}" → "${slug}"`);
                return slug;
            }
        }
        
        // 3. Buscar per paraules clau
        const inputWords = normalizedInput.split(/[^a-z0-9]+/).filter(w => w.length > 2);
        let bestMatch = null;
        let bestScore = 0;
        
        for (const [slug, data] of poiIndex) {
            const slugNorm = normalize(slug);
            const nameNorm = normalize(data.name || '');
            let score = 0;
            
            for (const word of inputWords) {
                if (slugNorm.includes(word)) score += word.length * 2;
                if (nameNorm.includes(word)) score += word.length;
            }
            
            if (score > bestScore) {
                bestScore = score;
                bestMatch = slug;
            }
        }
        
        if (bestMatch && bestScore > 6) {
            console.log(`   🔄 POI trobat per similitud: "${inputSlug}" → "${bestMatch}" (score: ${bestScore})`);
            return bestMatch;
        }
        
        console.warn(`   ⚠️ POI no trobat: "${inputSlug}"`);
        return null;
    }

    function findBestBarriMatch(inputSlug) {
        if (!inputSlug) return null;
        
        // 1. Coincidència exacta
        if (barriIndex.has(inputSlug)) {
            return inputSlug;
        }
        
        // 2. Normalitzar i buscar
        const normalize = (s) => s.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]/g, '');
        
        const normalizedInput = normalize(inputSlug);
        
        for (const [slug, data] of barriIndex) {
            const normalizedSlug = normalize(slug);
            
            if (normalizedSlug === normalizedInput) {
                console.log(`   🔄 Barri corregit: "${inputSlug}" → "${slug}"`);
                return slug;
            }
            
            if (normalizedSlug.includes(normalizedInput) || normalizedInput.includes(normalizedSlug)) {
                console.log(`   🔄 Barri aproximat: "${inputSlug}" → "${slug}"`);
                return slug;
            }
        }
        
        // 3. Buscar per paraules clau
        const inputWords = normalizedInput.split(/[^a-z0-9]+/).filter(w => w.length > 2);
        let bestMatch = null;
        let bestScore = 0;
        
        for (const [slug, data] of barriIndex) {
            const slugNorm = normalize(slug);
            const nameNorm = normalize(data.name || '');
            let score = 0;
            
            for (const word of inputWords) {
                if (slugNorm.includes(word)) score += word.length * 2;
                if (nameNorm.includes(word)) score += word.length;
            }
            
            if (score > bestScore) {
                bestScore = score;
                bestMatch = slug;
            }
        }
        
        if (bestMatch && bestScore > 4) {
            console.log(`   🔄 Barri trobat per similitud: "${inputSlug}" → "${bestMatch}" (score: ${bestScore})`);
            return bestMatch;
        }
        
        console.warn(`   ⚠️ Barri no trobat: "${inputSlug}"`);
        return null;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // API PÚBLICA - APLICAR SELECCIÓ DES DE IA
    // ═══════════════════════════════════════════════════════════════════════

    function applySelection(poiSlug, barriSlug) {
        console.log('═══════════════════════════════════════════════════');
        console.log('🤖 APLICANT SELECCIÓ DES DE IA:');
        console.log(`   POI rebut: "${poiSlug || 'cap'}"`);
        console.log(`   Barri rebut: "${barriSlug || 'cap'}"`);
        
        // Si les dades encara no han carregat, guardar per després
        if (!dataLoaded) {
            console.log('   ⏳ Dades no carregades, guardant selecció pendent...');
            pendingSelection = { poi: poiSlug, barri: barriSlug };
            return true;
        }
        
        return applySelectionInternal(poiSlug, barriSlug);
    }

    function applySelectionInternal(poiSlug, barriSlug) {
        // DEBUG: Mostrar índexs disponibles
        console.log(`   📋 POIs a l'índex: ${poiIndex.size}`);
        console.log(`   📋 Barris a l'índex: ${barriIndex.size}`);
        
        // Buscar coincidències intel·ligents
        const matchedPoi = findBestPoiMatch(poiSlug);
        const matchedBarri = findBestBarriMatch(barriSlug);
        
        console.log(`   → POI resolt: "${matchedPoi || 'cap'}"`);
        console.log(`   → Barri resolt: "${matchedBarri || 'cap'}"`);
        
        // Primer netejar
        clearAll();
        
        // Aplicar barri
        if (matchedBarri) {
            selectBarriBySlug(matchedBarri);
        }
        
        // Aplicar POI (pot auto-seleccionar barri pare si no n'hi ha)
        if (matchedPoi) {
            selectPoiBySlug(matchedPoi, !matchedBarri);
        }
        
        // Actualitzar UI
        updateUI();
        logState();
        
        // Fer zoom
        if (matchedPoi || matchedBarri) {
            zoomToSelection(matchedPoi, matchedBarri);
        }
        
        console.log('═══════════════════════════════════════════════════');
        
        return matchedPoi !== null || matchedBarri !== null;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // UTILITATS
    // ═══════════════════════════════════════════════════════════════════════

    function syncCheckbox(slug, isChecked) {
        const chk = document.querySelector(`.taxonomy-ubicacio input[value="${slug}"]`);
        if (chk && chk.checked !== isChecked) {
            chk.click();
        }
    }

    function updateCoords(lngLat) {
        const latInput = document.getElementById('arxiu-ubicacio-lat');
        const lngInput = document.getElementById('arxiu-ubicacio-lng');
        const latDisplay = document.getElementById('arxiu-lat-display');
        const lngDisplay = document.getElementById('arxiu-lng-display');
        
        const lat = lngLat.lat.toFixed(5);
        const lng = lngLat.lng.toFixed(5);
        
        if (latInput) latInput.value = lat;
        if (lngInput) lngInput.value = lng;
        if (latDisplay) latDisplay.textContent = lat;
        if (lngDisplay) lngDisplay.textContent = lng;
    }

    function updateUI() {
        const areaInput = document.getElementById('arxiu-ubicacio-area');
        if (!areaInput) return;
        
        const names = [];
        
        if (selectedBarri && barriIndex.has(selectedBarri)) {
            const barriData = barriIndex.get(selectedBarri);
            names.push(`📍 ${barriData.name}`);
        }
        
        selectedPois.forEach(slug => {
            if (poiIndex.has(slug)) {
                const poiData = poiIndex.get(slug);
                names.push(`📌 ${poiData.name}`);
            }
        });
        
        areaInput.value = names.join(' | ');
    }

    function hideLoading() {
        const loading = document.getElementById('arxiu-map-loading');
        if (loading) loading.style.display = 'none';
        
        const areaInput = document.getElementById('arxiu-ubicacio-area');
        if (areaInput) areaInput.placeholder = 'Fes clic en un POI o barri del mapa...';
    }
    
    function logState() {
        console.log('───────────────────────────────────────────────────');
        console.log(`📊 selectedBarri = ${selectedBarri || 'null'}`);
        console.log(`📊 selectedPois = [${Array.from(selectedPois).join(', ')}]`);
        console.log('───────────────────────────────────────────────────');
    }
    
    function zoomToSelection(poiSlug, barriSlug) {
        // Prioritzar POI per al zoom
        if (poiSlug && poiIndex.has(poiSlug)) {
            const poiData = poiIndex.get(poiSlug);
            if (poiData.coords) {
                map.flyTo({
                    center: poiData.coords,
                    zoom: 16,
                    duration: 1000
                });
                return;
            }
        }
        
        // Si no hi ha POI, intentar zoom al barri
        if (barriSlug) {
            const barriFeatures = map.querySourceFeatures('src-barris', {
                sourceLayer: config.layers.barri,
                filter: ['==', ['get', 'barri_slug'], barriSlug]
            });
            
            if (barriFeatures.length > 0 && typeof turf !== 'undefined') {
                const bbox = turf.bbox(barriFeatures[0]);
                map.fitBounds(bbox, { padding: 50, duration: 1000 });
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // API PÚBLICA
    // ═══════════════════════════════════════════════════════════════════════

    window.ArxiuUbicacio = {
        getSelection: () => ({ barri: selectedBarri, pois: Array.from(selectedPois) }),
        clearSelection: clearAll,
        applySelection: applySelection,
        
        // Debug helpers
        getPoiIndex: () => new Map(poiIndex),
        getBarriIndex: () => new Map(barriIndex),
        isDataLoaded: () => dataLoaded
    };

    console.log('✓ API ArxiuUbicacio registrada');

})();
