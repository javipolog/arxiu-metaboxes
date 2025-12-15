<?php
/**
 * Arxiu Knowledge Base - Base de coneixement COMPLETA i ROBUSTA de Monòver
 * 
 * Versió millorada amb informació exhaustiva per a:
 * - Anàlisi d'imatges amb Gemini Vision (identificació visual precisa)
 * - Detecció de dates i context temporal
 * - Identificació d'ubicacions i monuments
 * - Context cultural, històric i iconogràfic per a l'arxiu digital
 * 
 * ACTUALITZAT: Desembre 2024 amb informació verificada de fonts oficials
 * 
 * @package Arxiu_Monovar
 * @since 15.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Arxiu_Knowledge_Base {
    
    private static $instance = null;
    private static $cache = [];
    
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /* ═══════════════════════════════════════════════════════════════════════════
     * SECCIÓ 1: INFORMACIÓ GENERAL DE MONÒVER (ACTUALITZADA)
     * ═══════════════════════════════════════════════════════════════════════════ */
    
    public static function get_general_info() {
        return [
            'nom_oficial' => 'Monòver',
            'nom_castella' => 'Monóvar',
            'gentilici' => [
                'valencia' => [ 'monover', 'monovera', 'monovers', 'monoveres' ],
                'castella' => [ 'monovero', 'monovera', 'monoveros', 'monoveras' ]
            ],
            'sobrenom' => 'La ciutat d\'Azorín',
            'comarca' => 'Vinalopó Mitjà',
            'provincia' => 'Alacant',
            'comunitat' => 'Comunitat Valenciana',
            'coordenades' => [ 'lat' => 38.4372, 'lng' => -0.8389 ],
            'altitud' => 341, // metres
            'superficie' => 152.4, // km²
            'poblacio' => 12387,
            'codi_postal' => '03640',
            'titol_ciutat' => 1901,
            'capital_cultural_valenciana' => 2024, // NOU: Reconeixement oficial
            
            'etimologia' => [
                'teoria_llatina' => 'Mons Novar (mont nou)',
                'teoria_arab' => 'Manowar (florit)'
            ],
            
            'cronologia_historica' => [
                'prehistoria' => [
                    'eneolitic' => 'Cerro de los Molinos - assentaments antics',
                    'ibers' => 'Poblats ibers documentats'
                ],
                'romans' => 'Restes de necrópolis romana',
                'arabs' => [
                    'inici' => 'Segle VIII',
                    'fi' => 'Segle XIII',
                    'castell' => 'Construcció castell almohade (s. XII-XIII)'
                ],
                'conquesta_cristiana' => [
                    'tractat_almizra' => 1244,
                    'incorporacio_corona_arago' => 1296
                ],
                'edat_moderna' => [
                    'expulsio_morisca' => 1609, // Quedaren 130 de 1200 habitants
                    'carta_pobla' => 1611, // Anna Maria de Portugal
                    'repoblacio' => 'Colons de la Foia de Castalla'
                ],
                'segle_xviii' => [
                    'descripcio' => 'Època d\'expansió i construcció del patrimoni actual',
                    'torre_rellotge' => 1734,
                    'esglesia_sant_joan' => 1751,
                    'convent_caputxins' => 1756,
                    'orgue' => 1771,
                    'ermita_santa_barbara' => 1799
                ],
                'segle_xix' => [
                    'titol_ciutat' => 1901,
                    'filoxera' => 1904, // Crisi vinícola
                    'industria_calcat' => 'Principis s. XX'
                ],
                'segle_xx' => [
                    'guerra_civil' => [
                        'data' => '6-7 març 1939',
                        'esdeveniment' => 'Exili del Govern de la República des del Fondó',
                        'personatges' => [ 'Juan Negrín', 'Dolores Ibárruri (Pasionaria)', 'Rafael Alberti' ]
                    ]
                ]
            ],
            
            // Silueta característica de la ciutat
            'skyline' => [
                'elements' => [
                    'Castell almohade (turó nord-est)',
                    'Ermita de Santa Bàrbara amb cúpula blava (turó)',
                    'Torre del Rellotge amb cúpula blava',
                    'Campanar de Sant Joan Baptista'
                ],
                'descripcio' => 'Dues collines dominen la ciutat: una amb el castell i l\'altra amb l\'ermita de Santa Bàrbara'
            ]
        ];
    }
    
    /* ═══════════════════════════════════════════════════════════════════════════
     * SECCIÓ 2: LLOCS I EDIFICIS EMBLEMÀTICS (ICONOGRAFIA DETALLADA)
     * ═══════════════════════════════════════════════════════════════════════════ */
    
    public static function get_llocs( $slug = null ) {
        $llocs = [
            
            // ══════════════════════════════════════════════════════
            // MAPA DE CORRESPONDÈNCIA: Slug intern → Slug Tileset
            // ══════════════════════════════════════════════════════
            // Això permet que el Knowledge Base tingui slugs descriptius
            // mentre Mapbox usa els slugs originals del GeoJSON
            
            // ══════════════════════════════════════════════════════
            // PATRIMONI RELIGIÓS
            // ══════════════════════════════════════════════════════
            
            'ermita-santa-barbara' => [
                'nom' => 'Ermita de Santa Bàrbara',
                'tileset_slug' => 'ermita-santa-barbara', // ✓ Coincideix
                'barri_tileset' => 'santa-barbara',
                'nom_alt' => [ 'Ermita de Santa Bárbara', 'Santuari de Santa Bàrbara' ],
                'tipus' => 'ermita',
                'any_construccio' => 1799,
                'any_anterior' => '1692-1694 (Tomàs Estacio)',
                'any_demolicio_anterior' => 1775,
                'primera_pedra' => 1792,
                'estil' => 'Barroc valencià-italià amb elements neoclàssics',
                'autors_possibles' => [ 'José Gonzálvez de Coniedo', 'Lorenzo Chápuli' ],
                
                'descripcio' => 'Ermita ÚNICA en la diòcesi que utilitza la corba, i sols existeix altra ermita amb aquestes característiques a la Comunitat Valenciana: la capella de la Comunió de Santa María d\'Elx. Monument Historicoartístic des de 1983.',
                
                'arquitectura' => [
                    'planta' => 'Rectangular amb capella elíptica interior',
                    'eixos_elipse' => [ 'major' => 12, 'menor' => 9 ], // metres aprox.
                    'orientacio' => 'Nord-Sud (altar al nord)',
                    'estructura' => [
                        'sud' => 'Atri porticat amb tres arcs sobre columnes de pedra',
                        'centre' => 'Capella de planta elíptica',
                        'nord' => 'Sagristia / Casa de la santera'
                    ],
                    'pilastres' => 'Tres parells amb plint i capitell corinti',
                    'materials' => 'Mamposteria, pedra de canteria'
                ],
                
                'caracteristiques_visuals_CLAU' => [
                    '★ CÚPULA DE TEULES BLAVES VIDRIADES - Element distintiu PRINCIPAL',
                    '★ Cúpula de mitja taronja sobre cornisa (elíptica)',
                    '★ Trasdós en doble curvatura visible des de l\'exterior',
                    'Façana de PEDRA OCRE/BEIX (NO pintada de blanc)',
                    'Situada en ALÇADA sobre el nucli urbà antic',
                    'Es veu des de BAIX amb mur de pedra davant',
                    'Frontó triangular mixtilini amb pinacles',
                    'Espadanya barroca amb creu de ferro al centre',
                    'Òcul (finestra oval) vidrat al centre del frontó',
                    'Pòrtic de TRES ARCS sobre columnes (basa dòrica, capitell pla)',
                    'Escalinata d\'accés al pòrtic',
                    'Petit balcó metàl·lic (balconada amb vistes panoràmiques)',
                    'Campanar petit lateral'
                ],
                
                'interior' => [
                    'paviment' => 'Grans lloses de pedra',
                    'decoracio' => 'Guirnaldes lineals, cenefes, motius florals (rosa i verd)',
                    'elements' => 'Florons en relleu, pilastres corínties',
                    'retaule' => 'Imatge de Santa Bàrbara'
                ],
                
                'entorn' => [
                    'Barri de Santa Bàrbara (cases tradicionals baixes)',
                    'Muralla/tàpia de mamposteria envoltant (740 pams de circumferència)',
                    'Vistes panoràmiques: Vall del Vinalopó, Elda, Petrer, Novelda',
                    'Vista al nord: Cerro del Cid',
                    'Sovint es veu roba estesa al voltant (barri popular)'
                ],
                
                'historia_militar' => 'Durant la Guerra Carlista es va fortificar amb aspilleres i tambors defensius',
                
                'com_distingir' => [
                    'DE SANT JOAN' => 'Santa Bàrbara té CÚPULA BLAVA visible i façana de pedra ocre; Sant Joan té CAMPANAR ALT i façana blanca',
                    'DE FOTOS AÈRIES' => 'Cúpula blava sobre teulada de teula, en posició elevada al turó'
                ],
                
                'paraules_clau' => [ 
                    'ermita', 'Santa Bàrbara', 'Santa Barbara', 'barri', 'XVIII', 
                    'cúpula blava', 'teules vidriades', 'turó', 'monument', 
                    'elíptica', 'barroc italià', 'pòrtic tres arcs', 'balcó'
                ],
                'proteccio' => 'Monument Historicoartístic (1983, Acadèmia de San Fernando)',
                'festa' => 'Últim cap de setmana d\'agost',
                'coordenades' => [ 'lat' => 38.439, 'lng' => -0.838 ]
            ],
            
            'esglesia-sant-joan-baptista' => [
                'nom' => 'Església de Sant Joan Baptista',
                'tileset_slug' => 'esglesia-snt-joan',  // ← Slug del GeoJSON/Tileset
                'barri_tileset' => 'Nucli Historic',  // ← Barri al tileset
                'nom_alt' => [ 'Iglesia de San Juan Bautista', 'Església parroquial', 'La Parròquia', 'Església Arxiprestal' ],
                'tipus' => 'esglesia',
                'any_inici' => 1751, // Segons Pascual Madoz, 19 abril 1751
                'estil' => 'Barroc amb influències neoclàssiques',
                'rang' => 'Arxiprestal (des de 1851)',
                
                'descripcio' => 'Església parroquial principal de Monòver. Senzilla però molt sòlida, tota de pedra de canteria excepte les voltes. Conserva un valuós orgue del segle XVIII.',
                
                'arquitectura' => [
                    'planta' => 'Creu llatina',
                    'nau_principal' => 'Volta de canó seguit amb cúpula semiesfèrica',
                    'naus_laterals' => 'Capelles als contraforts perforats',
                    'creuer' => 'Cúpula sobre petxines',
                    'torres' => 'Dues torres (una inacabada)'
                ],
                
                'caracteristiques_visuals_CLAU' => [
                    '★ CAMPANAR ALT i prominent - Element distintiu PRINCIPAL',
                    '★ Façana GRAN i ornamentada a nivell de carrer',
                    '★ Cúpula blau marí visible des de lluny (però menys que Santa Bàrbara)',
                    'Cúpula interior NO visible des del carrer (dins del creuer)',
                    'Color BLANC/CLAR de la façana',
                    'Portal d\'entrada monumental amb columnes',
                    'Situada a una PLAÇA (Plaça de l\'Església/Plaça de la Sala)',
                    'Edificis adjacents als costats',
                    'Rellotge visible al campanar'
                ],
                
                'interior' => [
                    'orgue' => [
                        'any_original' => 1771,
                        'autor_original' => 'Julián de la Orden (conquer)',
                        'reconstruccio' => 'Segle XIX per Alberto Randeynes',
                        'ubicacio' => 'Creuer, costat de l\'Epístola',
                        'estat' => 'Parcialment conservat'
                    ],
                    'capella_remei' => [
                        'acces' => 'Costat de l\'Evangeli',
                        'entrada' => 'Portal propi pel jardinet amb imatge de 1765',
                        'retaule' => 'Fusta daurada i policromada (1774, Francisco Mira)',
                        'camarin' => 'Amb imatge de la PATRONA (Mare de Déu del Remei)',
                        'cupula' => 'Cúpula sobre petxines'
                    ],
                    'capella_sant_miquel' => [
                        'any' => 1813,
                        'estil' => 'Neoclàssic',
                        'planta' => 'Rectangular amb volta de canó'
                    ]
                ],
                
                'com_distingir' => [
                    'DE SANTA BÀRBARA' => 'Sant Joan té CAMPANAR ALT prominent i façana blanca; Santa Bàrbara té CÚPULA BLAVA i façana de pedra ocre'
                ],
                
                'paraules_clau' => [ 
                    'església', 'Sant Joan', 'San Juan Bautista', 'parròquia', 'barroc', 
                    'orgue', 'XVIII', 'campanar', 'plaça', 'arxiprestal', 'patrona', 'Remei'
                ],
                'festa_patronal' => '24 de juny (Sant Joan Baptista)',
                'coordenades' => [ 'lat' => 38.4375, 'lng' => -0.8392 ]
            ],
            
            'torre-del-rellotge' => [
                'nom' => 'Torre del Rellotge',
                'tileset_slug' => 'torre-rellotge',  // ← Slug del GeoJSON/Tileset
                'barri_tileset' => 'Nucli Historic',  // ← Barri al tileset
                'nom_alt' => [ 'Torre del Reloj' ],
                'tipus' => 'torre_civica',
                'any_construccio' => 1734,
                'autor' => 'Tomàs Terol (mestre alacantí)',
                
                'descripcio' => 'Torre cívica EXENTA (no adossada a cap edifici), molt poc habitual al sud valencià. Destinada exclusivament a fins civils: allotjar el rellotge de la ciutat i les campanes. Símbol emblemàtic de Monòver.',
                
                'arquitectura' => [
                    'planta' => 'Quadrada',
                    'altura' => 18, // metres
                    'cossos' => 4, // decreixents en mida segons s\'eleven
                    'situacio' => 'Sobre un montícul, al final d\'un carrer empinat',
                    'inclinacio' => 'Cossos inferiors lleugerament inclinats',
                    'relacio' => 'Pot relacionar-se amb campanars del gòtic català'
                ],
                
                'caracteristiques_visuals_CLAU' => [
                    '★ TORRE EXENTA (no adossada a edifici) - Única al sud valencià',
                    '★ CÚPULA DE TEULA BLAVA VIDRIADA - Com Santa Bàrbara',
                    '★ Quatre cossos decreixents',
                    'Planta QUADRADA',
                    'Rellotge de sol al segon cos',
                    'Rellotge mecànic sobre el de sol',
                    'Dos últims cossos perforats per arcs (campanes)',
                    'Decoració amb boles a l\'últim cos',
                    'Veleta al cim',
                    'Situada en posició elevada, visible des de lluny'
                ],
                
                'elements' => [
                    'rellotge_sol' => 'Segon cos',
                    'rellotge_mecanic' => 'Tercer cos',
                    'campanes' => 'Quart cos (arcs perforats)',
                    'cupula' => 'Teula blava vidriada amb veleta'
                ],
                
                'paraules_clau' => [ 
                    'torre', 'rellotge', 'reloj', 'campanes', 'campanar', 'cívic', 
                    'XVIII', 'símbol', 'exenta', 'cúpula blava', 'gòtic català'
                ],
                'coordenades' => [ 'lat' => 38.437, 'lng' => -0.839 ]
            ],
            
            'castell-monovar' => [
                'nom' => 'Castell de Monòver',
                'nom_alt' => [ 'Castillo de Monóvar', 'Castell almohade' ],
                'tipus' => 'castell',
                'epoca' => 'Finals segle XII - principis XIII',
                'estil' => 'Almohade',
                
                'descripcio' => 'Castell almohade situat al turó més alt del poble (nord-est). Posició estratègica dominant la xarxa de fortificacions del riu Vinalopó (castells d\'Elda, La Torreta, Petrer) i la via de comunicació Pinoso-Jumilla cap a Múrcia i Andalusia. Utilitzat fins principis del segle XVII.',
                
                'arquitectura' => [
                    'estat' => 'En ruïnes, sols conserva part d\'una torre parcialment restaurada',
                    'deteriorament' => [
                        'Sòl argilós amb lliscaments de terra',
                        'Despreniments de vessants (sobretot vessant sud)',
                        'Antigues coves d\'explotació de guix (vessant nord)'
                    ]
                ],
                
                'caracteristiques_visuals_CLAU' => [
                    '★ RUÏNES en un turó elevat al nord-est del poble',
                    '★ Restes de torre parcialment restaurada',
                    'Restes de muralla de pedra',
                    'Vista panoràmica del poble i la vall',
                    'Vegetació abundant al voltant',
                    'Forma part de la SILUETA característica de Monòver'
                ],
                
                'funcio_historica' => 'Control del corredor del Vinalopó Mitjà i via Pinoso-Jumilla',
                
                'paraules_clau' => [ 
                    'castell', 'castillo', 'ruïnes', 'muralla', 'almohade', 'àrab', 
                    'turó', 'medieval', 'torre', 'XII', 'XIII'
                ],
                'proteccio' => 'BIC (Bé d\'Interès Cultural)',
                'coordenades' => [ 'lat' => 38.438, 'lng' => -0.840 ]
            ],
            
            'convent-caputxins' => [
                'nom' => 'Convent dels Caputxins / Església dels Caputxins',
                'nom_alt' => [ 'Convento de Capuchinos', 'Iglesia de los Capuchinos' ],
                'tipus' => 'convent',
                'any_construccio' => 1756, // 11 octubre 1756
                'any_fundacio_hospici' => 1729,
                'estil' => 'Barroc',
                
                'descripcio' => 'Originalment hospici fundat el 1729 pels frares caputxins sota patrocini del duc d\'Híxar. L\'església es va acabar el 1756. El convent es va derrocar als anys 70 per construir el mercat municipal actual; sols queda l\'església.',
                
                'historia' => [
                    1729 => 'Frares caputxins prenen possessió de cases per fundar hospici',
                    1756 => 'Acabament de l\'església (11 octubre)',
                    1835 => 'Desamortització - Passa a titularitat pública',
                    1970 => 'Demolició del convent, construcció del mercat'
                ],
                
                'estat_actual' => 'Sols queda l\'església en peu',
                
                'paraules_clau' => [ 'convent', 'caputxins', 'barroc', 'claustre', 'XVIII', 'mercat' ]
            ],
            
            'ermita-mare-deu-remei' => [
                'nom' => 'Església de la Mare de Déu del Remei',
                'nom_alt' => [ 'Iglesia Virgen del Remedio', 'Ermita del Remei', 'Santuari de la Patrona' ],
                'tipus' => 'esglesia',
                'ubicacio' => 'Cases del Senyor (pedania)',
                
                'descripcio' => 'Església dedicada a la PATRONA DE MONÒVER. La Mare de Déu del Remei és la patrona del poble i les festes patronals (6-10 setembre) es celebren en el seu honor.',
                
                'caracteristiques_visuals_CLAU' => [
                    '★ Qualsevol imatge de la Verge del Remei = PATRONA del poble',
                    'Església rural en pedania',
                    'Façana senzilla',
                    'Entorn de camp i cases baixes',
                    'Prop del Paratge Natural Municipal Monte Coto'
                ],
                
                'significat_cultural' => [
                    '★ PATRONA DE MONÒVER - Molt important!',
                    'Festes Patronals 6-10 setembre en el seu honor',
                    'Ofrena de Flors i Fruits',
                    'Processó solemne',
                    'Gran devoció popular'
                ],
                
                'paraules_clau' => [ 
                    'Mare de Déu', 'Remei', 'Remedio', 'patrona', 'Cases del Senyor', 
                    'festes patronals', 'setembre', 'verge', 'Virgen'
                ],
                'festa' => 'Festes Patronals (6-10 setembre)'
            ],
            
            // ══════════════════════════════════════════════════════
            // PATRIMONI CIVIL I CULTURAL
            // ══════════════════════════════════════════════════════
            
            'ajuntament' => [
                'nom' => 'Ajuntament de Monòver',
                'tileset_slug' => 'ajuntament',  // ← Coincideix
                'barri_tileset' => 'centre-urba',
                'nom_alt' => [ 'Ayuntamiento de Monóvar', 'Casa Consistorial' ],
                'tipus' => 'edifici_public',
                'any_construccio' => 1845,
                'ubicacio' => 'Plaça de Dalt',
                
                'descripcio' => 'Edifici de l\'Ajuntament construït al segle XIX. Seu del govern municipal. Escenari de la FOGUERA DE SANTA CATERINA (novembre).',
                
                'caracteristiques_visuals_CLAU' => [
                    'Edifici institucional amb façana noble',
                    'Façana amb BALCONS prominents',
                    'Escuts o emblemes heràldics',
                    'Plaça al davant (Plaça de Dalt)',
                    '★ FOGUERA DE SANTA CATERINA davant (novembre)'
                ],
                
                'paraules_clau' => [ 'ajuntament', 'ayuntamiento', 'casa consistorial', 'govern', 'plaça', 'XIX', 'foguera', 'Santa Caterina' ]
            ],
            
            'casa-museu-azorin' => [
                'nom' => 'Casa Museu Azorín',
                'tileset_slug' => 'casa-museu-azorin',  // ← Coincideix
                'barri_tileset' => 'centre-urba',
                'nom_alt' => [ 'Casa Museo Azorín' ],
                'tipus' => 'museu',
                'any_residencia' => 1876, // Residència familiar des de 1876
                'any_museu' => 1969,
                'ubicacio' => 'Carrer Salamanca, 6',
                'gestio' => 'Fundació Mediterrani',
                
                'descripcio' => 'Residència de la família Martínez Ruiz des de 1876, ara museu i centre de documentació. Alberga la biblioteca d\'Azorín amb 14.000 volums (alguns del s. XVI) i la seua correspondència. Imprescindible per estudiosos de l\'escriptor.',
                
                'caracteristiques_visuals_CLAU' => [
                    'Casa tradicional de TRES PLANTES',
                    'Façana típica monovera',
                    'Placa commemorativa',
                    'Finestres amb reixes',
                    'Edifici del segle XIX'
                ],
                
                'contingut' => [
                    'biblioteca' => '14.000 volums (alguns del s. XVI)',
                    'correspondencia' => 'Cartes de l\'escriptor',
                    'objectes' => 'Ensers personals d\'Azorín',
                    'periodics' => 'Periòdics monovers microfilmats',
                    'publicacions' => 'Anales Azorinianos, coedicions'
                ],
                
                'paraules_clau' => [ 'Azorín', 'museu', 'escriptor', 'Generació del 98', 'biblioteca', 'literatura', 'Martínez Ruiz' ]
            ],
            
            'teatre-principal' => [
                'nom' => 'Teatre Principal',
                'nom_alt' => [ 'Teatro Principal' ],
                'tipus' => 'teatre',
                'any_construccio' => 1857,
                'any_rehabilitacio' => 2002,
                'descripcio' => 'Teatre construït en 1857, rehabilitat i reinaugurat en 2002.',
                'paraules_clau' => [ 'teatre', 'teatro', 'cultura', 'espectacles', 'XIX' ]
            ],
            
            'casino-monovar' => [
                'nom' => 'Societat Cultural Casino de Monòver',
                'tileset_slug' => 'casino',  // ← Coincideix
                'barri_tileset' => 'centre-urba',
                'nom_alt' => [ 'Casino de Monóvar', 'El Casino' ],
                'tipus' => 'edifici_social',
                'any_fundacio' => 1880,
                'descripcio' => 'Creat per fusió de "Casino del Teatre" i "Cercle Agrícola" l\'any 1880.',
                'paraules_clau' => [ 'casino', 'societat', 'cultural', 'XIX' ]
            ],
            
            'museu-arts-oficis' => [
                'nom' => 'Museu d\'Arts i Oficis Monovers',
                'nom_alt' => [ 'Museo de Artes y Oficios', 'Col·lecció Museogràfica Permanent' ],
                'tipus' => 'museu',
                'any_fundacio' => 1969,
                'fundador' => 'José María Román',
                'descripcio' => 'Museu etnològic que recull peces singulars sobre la història, cultura i tradicions monoveres. Mostra com es treballava antigament, amb quins utensilis i ferramentes.',
                'paraules_clau' => [ 'museu', 'etnologia', 'oficis', 'tradicions', 'artesania' ]
            ],
            
            'muac' => [
                'nom' => 'Museu Urbà d\'Art al Carrer (MUAC)',
                'tipus' => 'art_urba',
                'any_creacio' => 2016,
                'descripcio' => 'Projecte creat per iniciativa popular. Murals i art urbà per les façanes del poble.',
                'paraules_clau' => [ 'MUAC', 'art urbà', 'murals', 'façanes', 'art carrer', 'street art' ]
            ],
            
            // ══════════════════════════════════════════════════════
            // PATRIMONI INDUSTRIAL I VINÍCOLA
            // ══════════════════════════════════════════════════════
            
            'bodegas-monovar' => [
                'nom' => 'Bodegas Monóvar',
                'nom_alt' => [ 'Celler Monòver', 'Bodegues Poveda', 'MGWines Monóvar' ],
                'tipus' => 'bodega',
                'ubicacio' => 'Collado de Azorín (carretera Monòver-Salines)',
                'gestio' => 'MGWines Group',
                
                'descripcio' => 'Celler històric amb més de 500 anys d\'història, especialitzat en la producció de FONDILLÓN. Posseeix la major i més rellevant reserva de Fondillón del món, amb més de 75 tonells de solera centenària i 23 de l\'edició especial Fondillón Sacristía. Tonells centenaris del segle XIX de roure americà salvatge.',
                
                'caracteristiques_visuals_CLAU' => [
                    'Edifici bodega tradicional',
                    'Tonells de fusta centenaris (1.730 litres)',
                    'Caves/Celler subterrani',
                    'Vinyes al voltant (ceps de 80+ anys)',
                    'Paisatge de secà amb pinedes',
                    'Instal·lacions modernes + patrimoni històric'
                ],
                
                'fondillon' => [
                    'tipus' => 'Vi dolç sense fortificar',
                    'raim' => 'Monastrell sobremadurada a la cepa',
                    'graduacio' => '17-18° (natural, no encapçalat)',
                    'envelliment' => 'Mínim 10 anys en tonells centenaris',
                    'DO' => 'Alicante (DOP Vinos Alicante)',
                    'reconeixement' => 'Vi de Luxe Europeu (com Jerez, Oporto, Burdeus, Champagne)',
                    'cita_azorin' => 'Vi centenari, és dolç sense embafament, per la seua densitat entela el vidre, olora a vella caoba.'
                ],
                
                'paraules_clau' => [ 'vi', 'Fondillón', 'bodega', 'celler', 'monastrell', 'vinyes', 'tonells', 'D.O. Alacant' ]
            ],
            
            // ══════════════════════════════════════════════════════
            // PLACES I ESPAIS URBANS
            // ══════════════════════════════════════════════════════
            
            'placa-dalt' => [
                'nom' => 'Plaça de Dalt',
                'nom_alt' => [ 'Plaza de Arriba', 'Plaça Major' ],
                'tipus' => 'placa',
                'descripcio' => 'Plaça principal del poble on s\'ubica l\'Ajuntament. Centre de les festes i actes oficials. Escenari de la FOGUERA DE SANTA CATERINA.',
                
                'caracteristiques_visuals_CLAU' => [
                    'Plaça empedrada',
                    'AJUNTAMENT al fons/davant',
                    'Arbres o palmeres',
                    'Bancs',
                    '★ FOGUERA gran al novembre (Santa Caterina)'
                ],
                
                'esdeveniments' => [
                    'Foguera de Santa Caterina (novembre)',
                    'Actes oficials',
                    'Trobada de Nanos i Gegants'
                ],
                
                'paraules_clau' => [ 'plaça', 'plaza', 'centre', 'ajuntament', 'festes', 'foguera' ]
            ],
            
            'placa-sala' => [
                'nom' => 'Plaça de la Sala',
                'tipus' => 'placa',
                'descripcio' => 'Plaça cèntrica on se celebren concerts i actes festius. Prop de l\'Església de Sant Joan.',
                'esdeveniments' => [ 'Concerts de festes', 'Sessions Vermut', 'Actes culturals' ],
                'paraules_clau' => [ 'plaça', 'concerts', 'festes', 'música' ]
            ],
            
            'placa-malva' => [
                'nom' => 'Plaça de la Malva',
                'tipus' => 'placa',
                'descripcio' => 'Plaça tradicional on se celebra la SANTA TROBADA el Diumenge de Resurrecció durant la Setmana Santa.',
                'paraules_clau' => [ 'plaça', 'Santa Trobada', 'Setmana Santa', 'Pasqua', 'Resurrecció' ]
            ],
            
            'placa-bous' => [
                'nom' => 'Plaça de Bous',
                'nom_alt' => [ 'Plaza de Toros' ],
                'tipus' => 'placa_bous',
                'descripcio' => 'Plaça de bous de Monòver. Utilitzada per a espectacles taurins durant les festes i també per a cinema a l\'aire lliure.',
                'esdeveniments' => [
                    'Bous amb corda (festes)',
                    'Vaquetes',
                    'Cinema d\'estiu'
                ],
                'paraules_clau' => [ 'plaça de bous', 'toros', 'festes', 'bous', 'cinema' ]
            ],
            
            'parc-albereda' => [
                'nom' => 'Parc de l\'Albereda',
                'tipus' => 'parc',
                'descripcio' => 'Parc urbà on se celebren activitats festives, sessions vermut i la Festa dels Nanos i Gegants.',
                'esdeveniments' => [
                    'Sessions Vermut (festes)',
                    'Festa dels Nanos i Gegants',
                    'Activitats familiars'
                ],
                'paraules_clau' => [ 'parc', 'albereda', 'festes', 'Nanos', 'Gegants' ]
            ],
            
            // ══════════════════════════════════════════════════════
            // PATRIMONI MEMORIAL I MEMÒRIA DEMOCRÀTICA
            // ══════════════════════════════════════════════════════
            
            'espai-memorialista-fondo' => [
                'nom' => 'Espai Memorialista del Fondó',
                'nom_alt' => [ 'Centro de Interpretación del Fondó', 'Refugi del Fondó', 'L\'Escoleta del Fondó' ],
                'tipus' => 'memorial',
                'epoca' => 1939,
                'ubicacio' => 'El Fondó (pedania)',
                
                'descripcio' => 'Centre museístic obert a la ciutadania per reflexionar sobre els exilis humans. El 6 i 7 de març de 1939, des de l\'aeròdrom del Fondó van partir cap a l\'exili les personalitats polítiques i militars més importants del govern de la II República.',
                
                'fet_historic' => [
                    'data' => '6-7 març 1939',
                    'esdeveniment' => 'Últims dies de la II República Espanyola',
                    'destins' => [ 'Toulouse (França)', 'Orán (Algèria)' ],
                    'avions' => [ 'De Havilland DH89 Dragon Rapide', 'Douglas DC-2' ]
                ],
                
                'personatges_exiliats' => [
                    'Juan Negrín' => 'President del Govern de la República',
                    'Dolores Ibárruri "Pasionaria"' => 'Dirigent comunista',
                    'Rafael Alberti' => 'Poeta de la Generació del 27',
                    'María Teresa León' => 'Escriptora, esposa d\'Alberti (embarassada)',
                    'Palmiro Togliatti' => 'Representant del Komintern',
                    'altres' => 'Ministres i caps polítics i militars'
                ],
                
                'espais' => [
                    'Centre d\'Interpretació (L\'Escoleta)' => 'Antiga escola rural amb recursos didàctics',
                    'Refugi antiaeri' => [
                        'any' => 1938,
                        'capacitat' => 200,
                        'estructura' => 'Galeria principal amb formigó (20 cm), volta amb cimbres de fusta',
                        'contingut' => 'Exposició dibuixos de xiquets de la Guerra d\'Espanya i de Síria'
                    ],
                    'Aeròdrom' => 'Infraestructura eventual de terra compactada, forma hexagonal',
                    'Quarter militar' => 'Últim lloc de reunió del PCE (nit 6-7 març 1939)',
                    'Quatre hitos de ferro' => 'Senyalització dels espais històrics'
                ],
                
                'caracteristiques_visuals_CLAU' => [
                    'Espai a l\'aire lliure entre camps i pinedes',
                    'Quatre hitos/monòlits de ferro oxidat',
                    'Entrada al refugi subterrani',
                    'Edifici de l\'antiga escola (L\'Escoleta)',
                    'Paisatge de secà mediterrani',
                    'Panells informatius sobre la República'
                ],
                
                'paraules_clau' => [ 
                    'aeroport', 'aeròdrom', 'República', 'exili', 'Guerra Civil', 
                    'memòria', 'Fondó', 'Hondón', 'Negrín', 'Pasionaria', 'Alberti',
                    'refugi', 'memorial', 'memòria democràtica'
                ],
                'visites' => 'Amb reserva prèvia (turismo@monovar.es)'
            ]
        ];
        
        if ( $slug ) {
            return isset( $llocs[ $slug ] ) ? $llocs[ $slug ] : null;
        }
        
        return $llocs;
    }
    
    /* ═══════════════════════════════════════════════════════════════════════════
     * SECCIÓ 3: BARRIS I PEDANIES
     * ═══════════════════════════════════════════════════════════════════════════ */
    
    public static function get_barris( $slug = null ) {
        $barris = [
            
            'centre-historic' => [
                'nom' => 'Centre Històric',
                'nom_alt' => [ 'Casco Antiguo' ],
                'tipus' => 'barri',
                'descripcio' => 'Nucli antic del poble amb carrers estrets i cases tradicionals. Conserva nombrosos edificis del segle XVIII i els característics SOCARRATS als aleros.',
                
                'elements_arquitectonics' => [
                    'SOCARRATS' => 'Aleros decorats amb maons pintats amb òxid de ferro (característica quasi exclusiva de Monòver)',
                    'Cases tradicionals' => 'Façanes típiques monoveres',
                    'Carrers empedrats' => 'Traçat medieval'
                ],
                
                'caracteristiques_visuals' => [
                    '★ SOCARRATS als aleros de les cases',
                    'Carrers estrets i empinats',
                    'Cases blanques amb finestres de fusta',
                    'Reixes de forja'
                ],
                
                'paraules_clau' => [ 'centre', 'antic', 'històric', 'socarrats', 'carrers estrets', 'casco' ]
            ],
            
            'barri-santa-barbara' => [
                'nom' => 'Barri de Santa Bàrbara',
                'nom_alt' => [ 'Barrio de Santa Bárbara' ],
                'tipus' => 'barri',
                'descripcio' => 'Barri situat a la part alta del poble, al voltant de l\'ermita de Santa Bàrbara. Cases tradicionals baixes, ambient popular.',
                
                'caracteristiques_visuals' => [
                    'Cases tradicionals baixes',
                    'Carrers empinats',
                    'Ermita al cim',
                    'Roba estesa als carrers (ambient popular)',
                    'Vistes panoràmiques'
                ],
                
                'festes' => 'Últim cap de setmana d\'agost',
                'paraules_clau' => [ 'Santa Bàrbara', 'barri alt', 'ermita', 'festes agost' ]
            ],
            
            'el-fondo' => [
                'nom' => 'El Fondó',
                'nom_alt' => [ 'El Hondón', 'Fondó' ],
                'tipus' => 'pedania',
                'poblacio' => 80, // habitants aproximadament
                'descripcio' => 'Pedania amb GRAN IMPORTÀNCIA HISTÒRICA. Des del seu aeròdrom va partir cap a l\'exili el Govern de la República el 6-7 de març de 1939.',
                
                'patrimoni' => [
                    'Espai Memorialista del Fondó',
                    'Refugi antiaeri (1938)',
                    'Centre d\'Interpretació (L\'Escoleta)',
                    'Aeròdrom republicà (desaparegut)',
                    'Ermita de Santa Cecília'
                ],
                
                'festes' => 'Segon cap de setmana de juliol',
                'paraules_clau' => [ 'Fondó', 'Hondón', 'República', 'exili', 'aeròdrom', 'memòria' ]
            ],
            
            'cases-del-senyor' => [
                'nom' => 'Cases del Senyor',
                'nom_alt' => [ 'Casas del Señor' ],
                'tipus' => 'pedania',
                'descripcio' => 'Pedania amb l\'església de la Mare de Déu del Remei, PATRONA DE MONÒVER. Prop del Paratge Natural Municipal Monte Coto.',
                
                'patrimoni' => [
                    'Església Mare de Déu del Remei (Patrona)',
                    'Aqüeducte històric'
                ],
                
                'festes' => 'Segon cap de setmana d\'agost',
                'paraules_clau' => [ 'Cases del Senyor', 'Casas del Señor', 'Remei', 'Monte Coto', 'patrona' ]
            ],
            
            'xinorlet' => [
                'nom' => 'Xinorlet',
                'nom_alt' => [ 'Chinorlet' ],
                'tipus' => 'pedania',
                'descripcio' => 'Pedania rural al nord-oest del terme municipal. Zona de cultius i cases disperses.',
                'festes' => 'Tercer cap de setmana d\'agost',
                'rutes' => [ 'PRV-107 Ruta del Xirivell' ],
                'paraules_clau' => [ 'Xinorlet', 'Chinorlet', 'pedania', 'rural', 'camp' ]
            ],
            
            'la-romaneta' => [
                'nom' => 'La Romaneta',
                'tipus' => 'pedania',
                'descripcio' => 'Pedania rural amb jaciments arqueològics (cova sepulcral).',
                'festes' => 'Segon cap de setmana d\'agost',
                'patrimoni' => [ 'Cova sepulcral de la Romaneta', 'Ermita de Sant Roc' ],
                'paraules_clau' => [ 'Romaneta', 'pedania', 'arqueologia', 'cova', 'Sant Roc' ]
            ],
            
            'canyades-don-ciro' => [
                'nom' => 'Canyades de Don Ciro',
                'nom_alt' => [ 'Cañadas de D. Ciro', 'Canyades d\'en Cirus' ],
                'tipus' => 'pedania',
                'descripcio' => 'Pedania rural al sud-est del terme.',
                'festes' => 'Últim cap de setmana de juliol',
                'patrimoni' => [ 'Ermita de Fàtima' ],
                'paraules_clau' => [ 'Canyades', 'Cañadas', 'pedania', 'rural' ]
            ]
        ];
        
        if ( $slug ) {
            return isset( $barris[ $slug ] ) ? $barris[ $slug ] : null;
        }
        
        return $barris;
    }
    
    /* ═══════════════════════════════════════════════════════════════════════════
     * SECCIÓ 4: FESTES I TRADICIONS (ICONOGRAFIA FESTIVA)
     * ═══════════════════════════════════════════════════════════════════════════ */
    
    public static function get_festes( $slug = null ) {
        $festes = [
            
            'festes-patronals' => [
                'nom' => 'Festes Patronals / Festes Majors',
                'nom_alt' => [ 'Fiestas Patronales', 'Festes de Setembre', 'Festes del Remei' ],
                'mes' => 9,
                'dia_inici' => 6,
                'dia_fi' => 10,
                'patrona' => 'Mare de Déu del Remei (Verge del Remei)',
                
                'descripcio' => 'Festes majors de Monòver en honor a la Mare de Déu del Remei (PATRONA). Inclouen actes religiosos, desfilades, música, pirotècnia i tradicions populars.',
                
                'actes_principals' => [
                    'Exaltació de les Reines i Dames d\'Honor (30 agost)',
                    'Entrada de Bandes (6 setembre)',
                    'Pregó de festes',
                    'Ofrena de Flors i Fruits a la Patrona',
                    'Processó de la Mare de Déu del Remei',
                    'Cercaviles de Nanos i Gegants (amb Colla el Xirivell)',
                    'Desfilada multicolor (disfresses)',
                    'Solta de vaquetes',
                    'Bous amb corda',
                    'Mascletà',
                    'Concurs de Gachamigas',
                    'Castell de focs artificials',
                    'Sessions Vermut (Parc Albereda)',
                    'Concerts a Plaça de la Sala',
                    'Dinar de germanor (paella gegant)'
                ],
                
                'elements_visuals_CLAU' => [
                    '★ NANOS I GEGANTS ballant pels carrers',
                    '★ REINES I DAMES D\'HONOR amb vestits de gala',
                    '★ COLLA EL XIRIVELL (dolçaina i tabalet)',
                    'Vestits tradicionals valencians',
                    'Carrosses',
                    'Bandes de música',
                    'Disfresses (desfilada multicolor)',
                    'Flors i fruits (ofrena)',
                    'Pirotècnia, mascletà',
                    'Vaquetes i bous',
                    'Imatge de la Mare de Déu del Remei'
                ],
                
                'musica' => [ 
                    'Colla el Xirivell (dolçaina i tabalet)', 
                    'Bandes de música',
                    'Concerts a la Plaça de la Sala'
                ],
                
                'paraules_clau' => [ 
                    'festes', 'patronals', 'Remei', 'setembre', 'Nanos', 'Gegants', 
                    'processó', 'vaquetes', 'mascletà', 'reines', 'dames', 'ofrena'
                ]
            ],
            
            'sant-antoni' => [
                'nom' => 'Festa de Sant Antoni',
                'nom_alt' => [ 'San Antón', 'Sant Antoni Abat' ],
                'mes' => 1,
                'dia_inici' => 17,
                'dia_fi' => 17,
                
                'descripcio' => 'Festa tradicional amb benedicció d\'animals, romeria i concurs de gachamigas.',
                
                'actes_principals' => [
                    'Concentració al Camp de Marín',
                    'Concurs de gachamigas',
                    'Romeria a l\'Església de Sant Joan Baptista',
                    'Benedicció d\'animals i mascotes',
                    'Repartiment de pans als animals',
                    'Desfilada de cavalls i carruatges'
                ],
                
                'elements_visuals_CLAU' => [
                    '★ CAVALLS i carruatges',
                    '★ GACHAMIGAS cuinant-se en paelles',
                    'Animals i mascotes de tot tipus',
                    'Genets amb vestits tradicionals',
                    'Camp de Marín ple de gent',
                    'Foc de llenya, sarments'
                ],
                
                'paraules_clau' => [ 'Sant Antoni', 'benedicció', 'animals', 'cavalls', 'gachamiga', 'gener', 'romeria' ]
            ],
            
            'setmana-santa' => [
                'nom' => 'Setmana Santa',
                'nom_alt' => [ 'Semana Santa' ],
                'mes' => 3, // Variable març-abril
                
                'descripcio' => 'Celebració amb gran arrelament. Més de 2.000 persones participen en les processons.',
                
                'actes_principals' => [
                    'Diumenge de Rams (palmes i branques d\'olivera)',
                    'Processons nocturnes',
                    'Processó del Sant Enterrament (Divendres Sant)',
                    'SANTA TROBADA (Diumenge de Resurrecció, Plaça de la Malva)'
                ],
                
                'confraries' => [
                    'Nostre Pare Jesús',
                    'El Crist i Ntra Senyora de l\'Esperança',
                    'La Dolorosa',
                    'El Sepulcre',
                    'La Soledat'
                ],
                
                'elements_visuals_CLAU' => [
                    '★ CONFRARES amb túniques (diferents colors)',
                    '★ PASSOS processionals (imatges religioses)',
                    '★ SANTA TROBADA (Crist Ressuscitat i Mare de Déu)',
                    'Ciris i torxes enceses',
                    'Palmes i branques d\'olivera (Rams)',
                    'Processons nocturnes pels carrers',
                    'Plaça de la Malva'
                ],
                
                'paraules_clau' => [ 
                    'Setmana Santa', 'Semana Santa', 'processó', 'confraria', 'passos', 
                    'Rams', 'Santa Trobada', 'Pasqua', 'Resurrecció'
                ]
            ],
            
            'carnestoltes' => [
                'nom' => 'Carnestoltes',
                'nom_alt' => [ 'Carnaval' ],
                'mes' => 2,
                
                'descripcio' => 'Una de les grans festes de Monòver amb cercaviles, música i disfresses.',
                
                'actes_principals' => [
                    'Cercaviles',
                    'Concursos de disfresses',
                    'Música i balls',
                    'Enterrament de la sardina'
                ],
                
                'elements_visuals_CLAU' => [
                    '★ DISFRESSES de tot tipus',
                    'Carrosses temàtiques',
                    'Comparses organitzades',
                    'Confeti i serpentines',
                    'Ambient festiu als carrers'
                ],
                
                'paraules_clau' => [ 'Carnestoltes', 'Carnaval', 'disfresses', 'cercavila', 'febrer', 'comparses' ]
            ],
            
            'fira-santa-caterina' => [
                'nom' => 'Fira de Santa Caterina',
                'nom_alt' => [ 'Feria de Santa Catalina', 'Festes de Santa Caterina' ],
                'mes' => 11,
                'dia' => 25,
                'any_inici' => 1883,
                
                'descripcio' => 'Fira tradicional instaurada en 1883, una de les més antigues de la comarca. Se celebra al voltant del 25 de novembre, dia de Santa Caterina. Destaca la FOGUERA GRAN davant l\'Ajuntament.',
                
                'actes_principals' => [
                    '★ FOGUERA DE SANTA CATERINA davant l\'Ajuntament',
                    'Mercat i parades de productes',
                    'Actuacions musicals',
                    'Activitats culturals',
                    'Degustacions gastronòmiques'
                ],
                
                'elements_visuals_CLAU' => [
                    '★★★ FOGUERA GRAN davant l\'AJUNTAMENT - Element distintiu!',
                    '★ Plaça de Dalt amb FOC i gent al voltant',
                    '★ Façana de l\'Ajuntament de fons',
                    'Parades de mercat (fira)',
                    'Ambient nocturn amb flames',
                    'Gent escalfant-se al foc'
                ],
                
                'ubicacio' => 'Plaça de Dalt (davant l\'Ajuntament)',
                
                'com_identificar' => 'Si veus FOGUERA + AJUNTAMENT = Fira de Santa Caterina (novembre)',
                
                'paraules_clau' => [ 
                    'fira', 'Santa Caterina', 'Santa Catalina', 'novembre', 'mercat', 
                    'comerç', 'foguera', 'foc', 'ajuntament', 'plaça'
                ]
            ],
            
            'falles' => [
                'nom' => 'Falles',
                'mes' => 3,
                'dia_inici' => 15,
                'dia_fi' => 19,
                
                'descripcio' => 'Celebració de les Falles com a la resta del territori valencià.',
                
                'elements_visuals_CLAU' => [
                    'Monuments fallers (ninots)',
                    'Falleres i fallers amb vestits tradicionals',
                    'Pirotècnia, mascletades',
                    'Cremà dels monuments'
                ],
                
                'paraules_clau' => [ 'falles', 'falla', 'ninot', 'cremà', 'març', 'fallera', 'faller' ]
            ],
            
            'nanos-gegants-trobada' => [
                'nom' => 'Trobada de Nanos i Gegants',
                'nom_alt' => [ 'Trobada Nanos i Gegants', 'Festa dels Nanos i Gegants' ],
                'mes' => 9, // Durant les festes patronals
                
                'descripcio' => 'Gran trobada de colles de Nanos i Gegants de tota la Comunitat Valenciana. El 2024 Monòver va acollir la VII Trobada amb més de 3.000 persones i 18 agrupacions.',
                
                'elements_visuals_CLAU' => [
                    '★ FIGURES GEGANTS (gegants i cabezudos)',
                    '★ NANOS (figures de cap gran)',
                    '★ DOLÇAINA I TABALET (música tradicional)',
                    '★ COLLA EL XIRIVELL acompanyant',
                    'Pasacalles multitudinari',
                    'Plaça de l\'Ajuntament plena de figures',
                    'Xiquets corrent amb els nanos',
                    'Ball dels gegants'
                ],
                
                'historia' => 'Primera referència documentada de Nanos i Gegants: 1439 (Orihuela). A Monòver, mencions en 1892, 1900, 1948, 1949. Incorporació definitiva als anys 80.',
                
                'paraules_clau' => [ 
                    'Nanos', 'Gegants', 'gigantes', 'cabezudos', 'trobada', 
                    'dolçaina', 'tabalet', 'Xirivell', 'cercavila'
                ]
            ],
            
            'festes-barri-santa-barbara' => [
                'nom' => 'Festes del Barri de Santa Bàrbara',
                'mes' => 8,
                'periode' => 'Últim cap de setmana d\'agost',
                'descripcio' => 'Festes del barri amb processó de la Santa, Reines i Dames d\'Honor, concerts i activitats.',
                
                'actes' => [
                    'Exaltació de Reines i Dames de Santa Bàrbara',
                    'Processó de Muntada de la Santa',
                    'Banyà (activitat festiva)',
                    'Concerts'
                ],
                
                'paraules_clau' => [ 'Santa Bàrbara', 'barri', 'agost', 'festes', 'reines' ]
            ]
        ];
        
        if ( $slug ) {
            return isset( $festes[ $slug ] ) ? $festes[ $slug ] : null;
        }
        
        return $festes;
    }
    
    /* ═══════════════════════════════════════════════════════════════════════════
     * SECCIÓ 5: PERSONATGES HISTÒRICS
     * ═══════════════════════════════════════════════════════════════════════════ */
    
    public static function get_personatges( $slug = null ) {
        $personatges = [
            
            'azorin' => [
                'nom' => 'José Martínez Ruiz "Azorín"',
                'nom_complet' => 'José Augusto Trinidad Martínez Ruiz',
                'pseudonim' => 'Azorín',
                'naixement' => [
                    'data' => '8 de juny de 1873',
                    'lloc' => 'Monòver'
                ],
                'defuncio' => [
                    'data' => '2 de març de 1967',
                    'lloc' => 'Madrid (C/ Zorrilla, 21)',
                    'edat' => 93
                ],
                'enterrament' => 'Panteó familiar a Monòver (traslladat el 1990)',
                'professio' => [ 'Escriptor', 'Periodista', 'Polític', 'Crític literari' ],
                'moviment' => 'Generació del 98',
                
                'descripcio' => 'Un dels escriptors espanyols més importants del segle XX. Principal exponent de la Generació del 98 (terme que ell mateix va encunyar el 1913). Va cultivar la novel·la, l\'assaig, la crònica periodística i la crítica literària. Estil descriptiu impressionista amb concisió i sobrietat.',
                
                'formacio' => [
                    'batxillerat' => 'Col·legi dels Escolapis de Iecla (8 anys)',
                    'universitat' => 'Dret a València (1888-1896)',
                    'influencies' => [ 'Schopenhauer', 'Nietzsche', 'Krausisme', 'Anarquisme (joventut)' ]
                ],
                
                'trajectoria' => [
                    'pseudonims_inicials' => [ 'Cándido', 'Ahrimán', 'Fray José', 'Juan de Lis' ],
                    'adopcio_azorin' => 1904,
                    'periodisme' => [ 'El País', 'El Progreso', 'ABC', 'La Vanguardia', 'La Nación (Buenos Aires)' ],
                    'politica' => 'Diputat a Corts pel Partit Conservador (5 legislatures, 1907-1919)',
                    'exili' => 'París durant Guerra Civil (1936-1939)'
                ],
                
                'obres_destacades' => [
                    'La voluntad (1902)' => 'Novel·la de l\'abúlia',
                    'Antonio Azorín (1903)' => 'Novel·la',
                    'Las confesiones de un pequeño filósofo' => 'Memòries d\'infantesa',
                    'Castilla (1912)' => 'Assaig sobre l\'essència espanyola',
                    'Ruta de Don Quijote (1905)' => 'Assaig literari',
                    'Don Juan (1922)' => 'Novel·la',
                    'Doña Inés (1925)' => 'Novel·la',
                    'Memorias inmemoriables' => 'Memòries',
                    'Agenda (1959)' => 'Memòries',
                    'Posdata (1959)' => 'Memòries',
                    'Ejercicios de castellano (1960)' => 'Últims treballs'
                ],
                
                'estil_literari' => [
                    'Sobrietat i precisió',
                    'Atenció als detalls',
                    'Tècnica impressionista',
                    'Evocació nostàlgica',
                    'Paisatge i ànima castellana',
                    'Preocupació pel temps cíclic'
                ],
                
                'cita_fondillon' => 'Vi centenari, és dolç sense embafament, per la seua densitat entela el vidre, olora a vella caoba.',
                
                'patrimoni_monovar' => [
                    'Casa Museu Azorín (C/ Salamanca, 6)',
                    'Carrer dedicat',
                    'Biblioteca de 14.000 volums',
                    'Sobrenom de Monòver: "La ciutat d\'Azorín"'
                ],
                
                'reconeixements' => [
                    1950 => 'Medalla del Círculo de Escritores Cinematográficos',
                    1963 => 'Fill adoptiu d\'Alacant'
                ],
                
                'caracteristiques_visuals' => [
                    'Home prim i menut',
                    'Ulls penetrants',
                    'Vestit elegant',
                    'Aspecte intel·lectual',
                    'Moltes caricatures publicades (Bagaria, Sancha, Fresno...)'
                ],
                
                'paraules_clau' => [ 
                    'Azorín', 'escriptor', 'escritor', 'Generació del 98', 'literatura', 
                    'Martínez Ruiz', 'novel·la', 'assaig', 'Castilla'
                ]
            ],
            
            'juan-mallebrera-llau' => [
                'nom' => 'Juan Mallebrera Esteve "Llau"',
                'naixement' => '1869 (Monòver)',
                'defuncio' => '1938',
                'professio' => 'Pintor',
                'descripcio' => 'Un dels pintors monovers més memorables. Va fundar l\'Acadèmia Mallebrera on es van formar molts pintors i escultors.',
                'formacio' => 'Acadèmia de Belles Arts de San Fernando (Madrid)',
                'llegat' => 'Fundador d\'escola d\'art local',
                'paraules_clau' => [ 'Mallebrera', 'Llau', 'pintor', 'acadèmia', 'art', 'pintura' ]
            ],
            
            'jose-vicente-corbi' => [
                'nom' => 'José Vicente Corbí',
                'naixement' => '15 juliol 1927 (Monòver)',
                'defuncio' => '24 novembre 1972',
                'professio' => [ 'Cronista oficial', 'Bibliotecari municipal', 'Investigador' ],
                'descripcio' => 'Primer cronista oficial de Monòver als anys 60. Va estudiar la genealogia d\'Azorín.',
                'paraules_clau' => [ 'cronista', 'historiador', 'biblioteca', 'Corbí' ]
            ]
        ];
        
        if ( $slug ) {
            return isset( $personatges[ $slug ] ) ? $personatges[ $slug ] : null;
        }
        
        return $personatges;
    }
    
    /* ═══════════════════════════════════════════════════════════════════════════
     * SECCIÓ 6: GASTRONOMIA
     * ═══════════════════════════════════════════════════════════════════════════ */
    
    public static function get_gastronomia() {
        return [
            'descripcio_general' => 'La cultura mediterrània envolta la cuina de Monòver. Gastronomia plena de tradició amb plats cuinats al foc de sarments.',
            
            'plats_principals' => [
                'gachamiga' => [
                    'nom' => 'Gachamiga / Gachamigas',
                    'descripcio' => 'Plat TRADICIONAL i ICÒNIC de la zona. Es menja directament de la paella amb forquilla o pa, acompanyat de vi negre. Concursos durant les festes de Sant Antoni i festes patronals.',
                    'ingredients' => [ 'Farina', 'Aigua', 'Oli', 'All', 'Embotit (botifarra, llonganissa)' ],
                    'ocasions' => [ 'Festes de Sant Antoni (17 gener)', 'Festes Patronals (setembre)', 'Concursos' ],
                    'elements_visuals' => [
                        'Paella gran sobre foc de llenya',
                        'Massa espessa amb embotits',
                        'Gent menjant directament de la paella',
                        'Ambient rural, Camp de Marín'
                    ],
                    'paraules_clau' => [ 'gachamiga', 'gachamigas', 'plat típic', 'tradicional', 'Sant Antoni' ]
                ],
                'gazpachos' => [
                    'nom' => 'Gazpachos amb conill i caragols',
                    'descripcio' => 'Plat tradicional de l\'interior cuinat al foc amb sarments.',
                    'elements_visuals' => [ 'Paella de ferro', 'Foc de sarments', 'Torta sense llevat' ],
                    'paraules_clau' => [ 'gazpacho', 'gazpachos', 'conill', 'caragols', 'tradicional' ]
                ],
                'arros' => [
                    'nom' => 'Arròs amb conill i caragols',
                    'descripcio' => 'Arròs tradicional cuinat al foc amb sarments.',
                    'paraules_clau' => [ 'arròs', 'arroz', 'conill', 'caragols' ]
                ]
            ],
            
            'rebosteria' => [
                'crespells' => [ 'nom' => 'Crespells', 'paraules_clau' => [ 'crespells', 'dolç', 'pastes' ] ],
                'coca_mantega' => [ 'nom' => 'Coca de mantega', 'paraules_clau' => [ 'coca', 'mantega', 'dolç' ] ],
                'tonyes' => [ 'nom' => 'Toñas / Tonyes', 'ocasio' => 'Nadal', 'paraules_clau' => [ 'tonya', 'toñas', 'dolç', 'Nadal' ] ],
                'monjavenes' => [ 'nom' => 'Monjàvenes', 'paraules_clau' => [ 'monjàvena', 'dolç' ] ]
            ],
            
            'vins' => [
                'fondillon' => [
                    'nom' => 'Fondillón / Fondellol',
                    'tipus' => 'Vi dolç sense fortificar',
                    'raim' => 'Monastrell sobremadurada a la cepa',
                    'graduacio' => '17-18° (natural)',
                    'envelliment' => 'Mínim 10 anys en tonells centenaris de roure americà',
                    'DO' => 'Alicante (DOP Vinos Alicante)',
                    
                    'historia' => [
                        'origen' => 'Segle XV, Horta d\'Alacant',
                        'fama_mundial' => 'Segles XVI-XIX, exportat a tot Europa',
                        'mencions' => [ 'Shakespeare', 'Alejandro Dumas', 'Emilio Salgari', 'Dostoyevski', 'Daniel Defoe' ],
                        'crisi' => 'Fil·loxera (finals s. XIX)',
                        'recuperacio' => 'Família Poveda + MGWines'
                    ],
                    
                    'cita_azorin' => 'Vi centenari, és dolç sense embafament, per la seua densitat entela el vidre, olora a vella caoba.',
                    
                    'reconeixement' => [
                        'Vi de Luxe Europeu (UE)',
                        'Categoria compartida només amb: Jerez, Oporto, Burdeus, Champagne',
                        'Premi Alimentos de España al Millor Vi 2020'
                    ],
                    
                    'bodegas' => [ 'Bodegas Monóvar (MGWines)', 'Altres cellers de la comarca' ],
                    
                    'caracteristiques' => [
                        'color' => 'Caoba clar a ambre fosc',
                        'aromes' => 'Monastrell envellida, roure, notes amielades, ahumados, salins',
                        'gust' => 'Dolç suau (no empalagós), caramelitzat, ajerezat',
                        'sucre_residual' => '30-34 grams aprox.'
                    ],
                    
                    'paraules_clau' => [ 
                        'Fondillón', 'fondellol', 'vi dolç', 'vino dulce', 'monastrell', 
                        'D.O. Alacant', 'D.O. Alicante', 'tonells', 'centenari'
                    ]
                ]
            ]
        ];
    }
    
    /* ═══════════════════════════════════════════════════════════════════════════
     * SECCIÓ 7: INDÚSTRIA I ECONOMIA
     * ═══════════════════════════════════════════════════════════════════════════ */
    
    public static function get_industria() {
        return [
            'vi' => [
                'nom' => 'Indústria vinícola',
                'descripcio' => 'Producció de vi amb D.O. Alacant, especialment el Fondillón.',
                'productes' => [ 'Fondillón', 'Moscatell', 'Vi negre (Monastrell)' ],
                'epoca_daurada' => 'Segle XIX (exportació a Europa i EUA)',
                'crisi' => 'Fil·loxera (1904)',
                'recuperacio' => 'Segle XXI amb MGWines i altres cellers',
                'enoturisme' => 'Ruta del Vino de Alicante (Monòver soci fundador)',
                'paraules_clau' => [ 'vi', 'vino', 'bodega', 'celler', 'vinya', 'verema', 'Fondillón' ]
            ],
            'calcat' => [
                'nom' => 'Indústria del calçat',
                'descripcio' => 'Una de les principals indústries de Monòver, vinculada a Elda i Petrer (triangle del calçat).',
                'inici' => 'Principis del segle XX',
                'paraules_clau' => [ 'calçat', 'calzado', 'sabata', 'fàbrica', 'indústria' ]
            ],
            'marbre' => [
                'nom' => 'Indústria del marbre',
                'descripcio' => 'Extracció i transformació de marbre i pedra ornamental.',
                'ubicacio' => 'Pedreres al terme municipal (Monte Coto i altres)',
                'paraules_clau' => [ 'marbre', 'mármol', 'pedra', 'pedrera', 'canteria' ]
            ],
            'artesania' => [
                'barrils' => [
                    'nom' => 'Fabricació de barrils',
                    'descripcio' => 'Monòver és un dels pocs llocs de la península on encara es fabriquen barrils artesanalment.',
                    'paraules_clau' => [ 'barril', 'bóta', 'fusta', 'artesania', 'toneller' ]
                ],
                'encaix_bolillos' => [
                    'nom' => 'Encaix de bolillos',
                    'descripcio' => 'Tradició artesanal femenina de teixir encaixos amb bolillos sobre coixí.',
                    'elements' => [ 'Bolillos', 'Coixí/almohada', 'Patró de cartó (picado)', 'Fils (seda, cotó, lli)' ],
                    'paraules_clau' => [ 'encaix', 'bolillos', 'artesania', 'tradició', 'teixit' ]
                ]
            ]
        ];
    }
    
    /* ═══════════════════════════════════════════════════════════════════════════
     * SECCIÓ 8: ELEMENTS CULTURALS DISTINTIUS (ICONOGRAFIA CULTURAL)
     * ═══════════════════════════════════════════════════════════════════════════ */
    
    public static function get_elements_culturals() {
        return [
            'socarrats' => [
                'nom' => 'Socarrats',
                'nom_alt' => [ 'Socarrat' ],
                'tipus' => 'Element arquitectònic tradicional',
                
                'descripcio' => 'Plaques de fang cuit (maons) decorats amb dissenys geomètrics, alfanumèrics i figuratius, pintats amb òxid de ferro (roig/marró) i manganès (negre) sobre capa blanca de caolí. Es col·locaven als aleros de les cases i entre les bigues dels sostres. Element quasi EXCLUSIU de Monòver dins de la comarca.',
                
                'funcio' => [
                    'Decorativa: Embelliment de façanes',
                    'Informativa: Solien indicar dades dels propietaris o any de construcció',
                    'Arquitectònica: Cobriment d\'entrebigues i aleros'
                ],
                
                'caracteristiques_visuals' => [
                    '★ MAONS DECORATS als aleros de les cases',
                    'Dibuixos en ROIG/MARRÓ i NEGRE sobre fons blanc',
                    'Motius geomètrics, vegetals, inscripcions',
                    'Ubicats a les façanes del centre històric'
                ],
                
                'historia' => 'Origen medieval (s. XV), producció principal a Paterna i Manises. A Monòver, exemples notables del s. XVIII.',
                
                'ubicacio' => 'Façanes d\'edificis tradicionals del centre històric',
                'paraules_clau' => [ 'socarrat', 'socarrats', 'alero', 'façana', 'decoració', 'tradicional', 'maó' ]
            ],
            
            'nanos_gegants' => [
                'nom' => 'Nanos i Gegants',
                'nom_alt' => [ 'Gigantes y Cabezudos', 'Gegants i Capgrossos' ],
                'tipus' => 'Figures festives tradicionals',
                
                'descripcio' => 'Figures tradicionals que participen en les cercaviles durant les festes. Els GEGANTS (figures grans portades per una persona a l\'interior) representen rei i reina o personatges històrics. Els NANOS (capgrossos) són figures de cap gran que corren i juguen amb els xiquets. Tradició amb primera referència documentada el 1439 (Orihuela).',
                
                'historia_monovar' => 'Mencions en 1892, 1900, 1948, 1949. Incorporació definitiva als anys 80 del s. XX.',
                
                'associacio' => 'Associació de Nanos i Gegants de Monòver',
                
                'caracteristiques_visuals' => [
                    '★ GEGANTS: Figures de 3-4 metres, vestits tradicionals, rei i reina',
                    '★ NANOS: Caps grans, cossos xicotets, corrent entre la gent',
                    '★ DOLÇAINA i TABALET acompanyant (Colla el Xirivell)',
                    'Vestits colorits i tradicionals',
                    'Passejant pels carrers',
                    'Xiquets corrent darrere/amb ells'
                ],
                
                'actes' => [ 
                    'Cercaviles pels barris', 
                    'Balls tradicionals', 
                    'Festes Patronals (setembre)',
                    'Festa dels Nanos i Gegants (juliol)',
                    'Trobades comarcals/autonòmiques'
                ],
                'paraules_clau' => [ 'nanos', 'gegants', 'gigantes', 'cabezudos', 'cercavila', 'festes', 'tradició' ]
            ],
            
            'colla_xirivell' => [
                'nom' => 'Colla el Xirivell',
                'tipus' => 'Agrupació musical tradicional',
                
                'descripcio' => 'Agrupació de dolçaina i tabalet que acompanya els Nanos i Gegants i altres actes festius. Música tradicional valenciana.',
                
                'instruments' => [ 
                    'Dolçaina: Instrument de vent (fusta amb llengüeta doble)',
                    'Tabalet: Tambor xicotet portat penjat'
                ],
                
                'caracteristiques_visuals' => [
                    'Músics amb vestit tradicional valencià',
                    'Dolçaina (instrument de fusta allargat)',
                    'Tabalet (tambor xicotet)',
                    'Normalment en grup de 2-6 persones',
                    'Acompanyant Nanos i Gegants'
                ],
                
                'actes' => [
                    'Cercaviles de Nanos i Gegants',
                    'Festes Patronals',
                    'Actes institucionals',
                    '9 d\'Octubre (Dia de la Comunitat Valenciana)'
                ],
                
                'paraules_clau' => [ 'Xirivell', 'dolçaina', 'tabalet', 'música tradicional', 'colla' ]
            ],
            
            'cupules_blaves' => [
                'nom' => 'Cúpules blaves vidriades',
                'tipus' => 'Element arquitectònic distintiu',
                
                'descripcio' => 'Element visual CARACTERÍSTIC de la silueta de Monòver. Tant l\'Ermita de Santa Bàrbara com la Torre del Rellotge tenen cúpules de teula blava vidriada, així com l\'Església de Sant Joan Baptista.',
                
                'edificis' => [
                    'Ermita de Santa Bàrbara' => 'Cúpula elíptica de teula blava vidriada (element distintiu principal)',
                    'Torre del Rellotge' => 'Cúpula xicoteta de teula blava vidriada amb veleta',
                    'Església de Sant Joan Baptista' => 'Cúpula blau marí (menys visible des del carrer)'
                ],
                
                'paraules_clau' => [ 'cúpula', 'blava', 'vidriada', 'teula', 'skyline' ]
            ]
        ];
    }
    
    /* ═══════════════════════════════════════════════════════════════════════════
     * SECCIÓ 9: PARATGE NATURAL
     * ═══════════════════════════════════════════════════════════════════════════ */
    
    public static function get_medi_ambient() {
        return [
            'monte_coto' => [
                'nom' => 'Paratge Natural Municipal Monte Coto',
                'declaracio' => '23 març 2007',
                'superficie' => 763.75, // hectàrees
                'ubicacio' => 'Serra del Reclot (2.400 ha totals entre Monòver, el Pinós, l\'Alguenya i la Romana)',
                'altitud_maxima' => 997, // metres (capçalera Barranc de Caseta)
                
                'descripcio' => 'Espai natural protegit amb múltiples valors ecològics, paisatgístics i històrico-culturals. Relleu accidentat amb forts pendents, dominat per massa de pi carrasco.',
                
                'vegetacio' => [
                    'dominant' => 'Pi carrasco (Pinus halepensis)',
                    'zones_interes' => [ 'Barranc de la Quitranera', 'Capçaleres i ombria del Barranc de Caseta' ]
                ],
                
                'usos_tradicionals' => [
                    'Apicultura',
                    'Pasturatge',
                    'Recollida d\'espart',
                    'Obtenció de calç i guix',
                    'Extracció de roques ornamentals',
                    'Carbonatge',
                    'Plantes medicinals',
                    'Recollida d\'arena',
                    'Aprofitament d\'aigua de fonts'
                ],
                
                'construccions' => [
                    'Instal·lacions abandonades de l\'antiga pedrera',
                    'Torre de vigilància contra incendis (Alt Redó)'
                ],
                
                'paraules_clau' => [ 'Monte Coto', 'paratge', 'natura', 'pi', 'serra', 'senderisme' ]
            ],
            
            'paisatge_rural' => [
                'nom' => 'Paisatge rural tradicional',
                'descripcio' => 'Camins i senders que mostren paratges naturals i zones rurals amb desenvolupament tradicional.',
                
                'elements' => [
                    'Camps de cultiu (vinya, ametler, olivera)',
                    'Aterrassaments amb ribassades de pedra',
                    'Vivendes rurals',
                    'Cases cova',
                    'Pous (abastiment domèstic i animals)',
                    'Explotacions de marbre'
                ],
                
                'rutes' => [ 'PRV-107 Ruta del Xirivell' ],
                
                'paraules_clau' => [ 'rural', 'camp', 'vinya', 'ametler', 'olivera', 'ribassada' ]
            ]
        ];
    }
    
    /* ═══════════════════════════════════════════════════════════════════════════
     * SECCIÓ 10: CONTEXT VISUAL PER A IA (GEMINI) - PROMPT MILLORAT
     * ═══════════════════════════════════════════════════════════════════════════ */
    
    public static function get_gemini_context( $tipus = 'complert' ) {
        
        $context_base = "Ets un expert en història, patrimoni i iconografia de Monòver (Alacant, País Valencià). ";
        $context_base .= "Monòver, conegut com 'la ciutat d'Azorín' perquè allí va nàixer l'escriptor José Martínez Ruiz (Azorín, 1873-1967) de la Generació del 98. ";
        $context_base .= "El poble té un important patrimoni arquitectònic del segle XVIII i tradicions festives molt arrelades. ";
        $context_base .= "El 2024 va ser reconegut com a Capital Cultural Valenciana.\n\n";
        
        switch ( $tipus ) {
            
            case 'ubicacio':
            case 'llocs':
                $context = $context_base;
                $context .= "LLOCS EMBLEMÀTICS DE MONÒVER:\n\n";
                
                foreach ( self::get_llocs() as $slug => $lloc ) {
                    $context .= "- " . $lloc['nom'];
                    if ( isset( $lloc['any_construccio'] ) ) {
                        $context .= " (" . $lloc['any_construccio'] . ")";
                    }
                    if ( isset( $lloc['descripcio'] ) ) {
                        $context .= ": " . $lloc['descripcio'];
                    }
                    if ( isset( $lloc['caracteristiques_visuals_CLAU'] ) ) {
                        $context .= " ELEMENTS CLAU: " . implode( '; ', $lloc['caracteristiques_visuals_CLAU'] ) . ".";
                    } elseif ( isset( $lloc['caracteristiques_visuals'] ) ) {
                        $context .= " Característiques: " . implode( ', ', $lloc['caracteristiques_visuals'] ) . ".";
                    }
                    $context .= " [slug: {$slug}]\n";
                }
                
                $context .= "\nBARRIS I PEDANIES:\n";
                foreach ( self::get_barris() as $slug => $barri ) {
                    $context .= "- " . $barri['nom'];
                    if ( isset( $barri['descripcio'] ) ) {
                        $context .= ": " . $barri['descripcio'];
                    }
                    $context .= " [slug: {$slug}]\n";
                }
                
                return $context;
                
            case 'festes':
            case 'dates':
                $context = $context_base;
                $context .= "FESTES I TRADICIONS DE MONÒVER:\n\n";
                
                foreach ( self::get_festes() as $slug => $festa ) {
                    $context .= "## " . $festa['nom'] . "\n";
                    
                    if ( isset( $festa['mes'] ) ) {
                        $mesos = [ 1=>'gener', 2=>'febrer', 3=>'març', 4=>'abril', 5=>'maig', 6=>'juny', 7=>'juliol', 8=>'agost', 9=>'setembre', 10=>'octubre', 11=>'novembre', 12=>'desembre' ];
                        $dates = $mesos[ $festa['mes'] ];
                        if ( isset( $festa['dia_inici'] ) ) {
                            $dates = $festa['dia_inici'];
                            if ( isset( $festa['dia_fi'] ) && $festa['dia_fi'] != $festa['dia_inici'] ) {
                                $dates .= "-" . $festa['dia_fi'];
                            }
                            $dates .= " de " . $mesos[ $festa['mes'] ];
                        }
                        $context .= "Dates: " . $dates . "\n";
                    }
                    
                    if ( isset( $festa['descripcio'] ) ) {
                        $context .= $festa['descripcio'] . "\n";
                    }
                    if ( isset( $festa['elements_visuals_CLAU'] ) ) {
                        $context .= "ELEMENTS VISUALS CLAU: " . implode( '; ', $festa['elements_visuals_CLAU'] ) . "\n";
                    }
                    $context .= "\n";
                }
                
                return $context;
                
            case 'personatges':
                $context = $context_base;
                $context .= "PERSONATGES HISTÒRICS DE MONÒVER:\n\n";
                
                foreach ( self::get_personatges() as $slug => $personatge ) {
                    $context .= "## " . $personatge['nom'] . "\n";
                    if ( isset( $personatge['descripcio'] ) ) {
                        $context .= $personatge['descripcio'] . "\n";
                    }
                    if ( isset( $personatge['obres_destacades'] ) && is_array( $personatge['obres_destacades'] ) ) {
                        $obres = array_keys( $personatge['obres_destacades'] );
                        $context .= "Obres: " . implode( ', ', $obres ) . "\n";
                    }
                    $context .= "\n";
                }
                
                return $context;
                
            case 'gastronomia':
                $context = $context_base;
                $context .= "GASTRONOMIA DE MONÒVER:\n\n";
                
                $gastro = self::get_gastronomia();
                $context .= $gastro['descripcio_general'] . "\n\n";
                
                $context .= "PLATS TÍPICS:\n";
                foreach ( $gastro['plats_principals'] as $plat ) {
                    $context .= "- " . $plat['nom'] . ": " . ( $plat['descripcio'] ?? '' ) . "\n";
                }
                
                $context .= "\nVINS:\n";
                foreach ( $gastro['vins'] as $vi ) {
                    $context .= "- " . $vi['nom'] . ": " . ( $vi['descripcio'] ?? '' ) . "\n";
                }
                
                return $context;
                
            case 'complert':
            default:
                // Context COMPLET i ROBUST per a Gemini
                $info = self::get_general_info();
                $context = $context_base;
                
                $context .= "DADES GENERALS:\n";
                $context .= "- Nom: " . $info['nom_oficial'] . " (" . $info['nom_castella'] . ")\n";
                $context .= "- Sobrenom: " . $info['sobrenom'] . "\n";
                $context .= "- Comarca: " . $info['comarca'] . "\n";
                $context .= "- Província: " . $info['provincia'] . "\n";
                $context .= "- Població: " . number_format( $info['poblacio'], 0, ',', '.' ) . " habitants\n";
                $context .= "- Capital Cultural Valenciana: 2024\n\n";
                
                // ═══════════════════════════════════════════════════════════════
                // GUIA D'IDENTIFICACIÓ VISUAL - MÉS IMPORTANT
                // ═══════════════════════════════════════════════════════════════
                
                $context .= "═══════════════════════════════════════════════════\n";
                $context .= "GUIA D'IDENTIFICACIÓ VISUAL D'EDIFICIS - MOLT IMPORTANT\n";
                $context .= "═══════════════════════════════════════════════════\n\n";
                
                $context .= "🔴 ERMITA DE SANTA BÀRBARA (com distingir-la):\n";
                $context .= "- ★★★ CÚPULA DE TEULES BLAVES VIDRIADES - Element distintiu PRINCIPAL!\n";
                $context .= "- ★★ Cúpula ELÍPTICA (única a la província d'Alacant)\n";
                $context .= "- Façana de PEDRA OCRE/BEIX (NO blanca, NO pintada)\n";
                $context .= "- Situada en ALÇADA sobre cases antigues (barri de Santa Bàrbara)\n";
                $context .= "- Vista des de BAIX amb mur de pedra davant\n";
                $context .= "- Pòrtic de TRES ARCS sobre columnes\n";
                $context .= "- Frontó triangular mixtilini amb espadanya i creu\n";
                $context .= "- Finestres ovals (òculs) a la façana\n";
                $context .= "- Balcó metàl·lic amb vistes panoràmiques\n";
                $context .= "- Any: 1799 - Estil: Barroc italià-valencià\n\n";
                
                $context .= "🔵 ESGLÉSIA DE SANT JOAN BAPTISTA (com distingir-la):\n";
                $context .= "- ★★★ CAMPANAR ALT i prominent - Element distintiu PRINCIPAL!\n";
                $context .= "- ★★ Façana GRAN i ornamentada a nivell de carrer\n";
                $context .= "- Color BLANC/CLAR de la façana (pintada)\n";
                $context .= "- Portal monumental amb columnes\n";
                $context .= "- Cúpula blau marí (menys visible que Santa Bàrbara)\n";
                $context .= "- Cúpula interior NO visible des del carrer (dins del creuer)\n";
                $context .= "- Situada a una PLAÇA (Plaça de l'Església/Sala), NO en turó\n";
                $context .= "- Edificis adjacents als costats\n";
                $context .= "- Any: 1751 - Conté orgue de 1771\n\n";
                
                $context .= "🟢 TORRE DEL RELLOTGE (com distingir-la):\n";
                $context .= "- ★★★ TORRE EXENTA (sola, NO adossada a cap edifici) - Única al sud valencià!\n";
                $context .= "- ★★ CÚPULA BLAVA vidriada amb veleta al cim\n";
                $context .= "- QUATRE COSSOS decreixents sobre planta QUADRADA\n";
                $context .= "- Rellotge de sol (2n cos) + Rellotge mecànic (3r cos)\n";
                $context .= "- Campanes als arcs perforats (4t cos)\n";
                $context .= "- Decoració amb boles a l'últim cos\n";
                $context .= "- 18 metres d'alçada\n";
                $context .= "- Situada en posició elevada, visible des de lluny\n";
                $context .= "- Any: 1734 - Símbol cívic de la ciutat\n\n";
                
                $context .= "🟡 MARE DE DÉU DEL REMEI (PATRONA):\n";
                $context .= "- ★★★ PATRONA DE MONÒVER - Molt important!\n";
                $context .= "- Qualsevol imatge de la Verge del Remei = patrona del poble\n";
                $context .= "- Festes Patronals 6-10 setembre en el seu honor\n";
                $context .= "- Església a Cases del Senyor (pedania)\n";
                $context .= "- Capella dedicada dins de Sant Joan Baptista\n\n";
                
                $context .= "🟠 FOGUERA DE SANTA CATERINA:\n";
                $context .= "- ★★★ FOGUERA gran DAVANT L'AJUNTAMENT - Element distintiu!\n";
                $context .= "- Si veus FOGUERA + FAÇANA AJUNTAMENT = Santa Caterina (novembre)\n";
                $context .= "- Plaça de Dalt, gent escalfant-se al foc\n";
                $context .= "- Fira tradicional des de 1883 (25 novembre)\n\n";
                
                $context .= "🟣 NANOS I GEGANTS:\n";
                $context .= "- ★★★ Figures gegants i capgrossos amb DOLÇAINA I TABALET\n";
                $context .= "- Colla el Xirivell acompanyant\n";
                $context .= "- Cercaviles pels carrers durant festes\n";
                $context .= "- Xiquets corrent amb els nanos\n\n";
                
                $context .= "🏰 CASTELL DE MONÒVER:\n";
                $context .= "- RUÏNES al turó nord-est del poble\n";
                $context .= "- Torre parcialment restaurada\n";
                $context .= "- Restes de muralla, vegetació abundant\n";
                $context .= "- Part de la silueta característica\n";
                $context .= "- Època: Almohade (s. XII-XIII)\n\n";
                
                // Patrimoni memorial
                $context .= "🏛️ ESPAI MEMORIALISTA DEL FONDÓ:\n";
                $context .= "- Aeròdrom republicà (1939) - Exili del govern de la República\n";
                $context .= "- Refugi antiaeri, Centre d'Interpretació (L'Escoleta)\n";
                $context .= "- Quatre hitos de ferro al paisatge\n";
                $context .= "- Personatges: Negrín, Pasionaria, Alberti\n\n";
                
                // Elements culturals
                $context .= "ELEMENTS CULTURALS DISTINTIUS:\n";
                $context .= "- SOCARRATS: Aleros decorats amb maons pintats (roig/negre) - Característica quasi exclusiva de Monòver\n";
                $context .= "- CÚPULES BLAVES: Element visual del skyline (Santa Bàrbara, Torre, Sant Joan)\n";
                $context .= "- Colla el Xirivell: Dolçaina i tabalet (música tradicional)\n";
                $context .= "- Fondillón: Vi dolç centenari D.O. Alacant (monastrell)\n";
                $context .= "- Gachamiga: Plat tradicional (festes de Sant Antoni)\n\n";
                
                // Festes principals
                $context .= "FESTES PRINCIPALS I ELEMENTS VISUALS:\n";
                $context .= "- Festes Patronals (6-10 setembre): Nanos i Gegants, Processó, Vaquetes, Reines i Dames, Ofrena\n";
                $context .= "- Sant Antoni (17 gener): Cavalls, animals, gachamigas, Camp de Marín\n";
                $context .= "- Setmana Santa: Processons, confraries, passos religiosos, Santa Trobada (Plaça de la Malva)\n";
                $context .= "- Carnestoltes (febrer): Disfresses, cercaviles, comparses\n";
                $context .= "- Fira Santa Caterina (novembre): FOGUERA davant Ajuntament, mercat\n";
                $context .= "- Falles (març): Monuments fallers, falleres\n\n";
                
                // Personatges
                $context .= "PERSONATGE MÉS IMPORTANT - AZORÍN:\n";
                $context .= "- José Martínez Ruiz 'Azorín' (1873-1967)\n";
                $context .= "- Escriptor de la Generació del 98\n";
                $context .= "- Monòver = 'La ciutat d'Azorín'\n";
                $context .= "- Casa Museu Azorín amb 14.000 volums\n\n";
                
                // Pedanies
                $context .= "PEDANIES:\n";
                $context .= "- El Fondó: Lloc històric de l'exili republicà (1939), Espai Memorialista\n";
                $context .= "- Cases del Senyor: Església de la Patrona (Mare de Déu del Remei)\n";
                $context .= "- Xinorlet, La Romaneta, Canyades de Don Ciro\n";
                
                return $context;
        }
    }
    
    /**
     * Genera prompt específic per a anàlisi d'ubicació
     */
    public static function get_ubicacio_prompt() {
        $context = self::get_gemini_context( 'complert' );
        
        $prompt = $context . "\n\n";
        $prompt .= "═══════════════════════════════════════════════════\n";
        $prompt .= "INSTRUCCIONS PER A L'ANÀLISI:\n";
        $prompt .= "═══════════════════════════════════════════════════\n\n";
        $prompt .= "Analitza aquesta imatge i identifica si correspon a algun lloc de Monòver.\n\n";
        $prompt .= "PASSOS:\n";
        $prompt .= "1. Busca elements distintius: cúpules blaves, campanars, torres, façanes\n";
        $prompt .= "2. Compara amb les característiques visuals CLAU de cada edifici\n";
        $prompt .= "3. Tingues en compte el context: posició elevada, plaça, entorn\n\n";
        $prompt .= "Si reconèixes el lloc, indica:\n";
        $prompt .= "1. Nom del lloc (en valencià)\n";
        $prompt .= "2. Slug identificador (ex: ermita-santa-barbara)\n";
        $prompt .= "3. Nivell de confiança (high/medium/low)\n";
        $prompt .= "4. Raonament detallat (quins elements has identificat)\n\n";
        $prompt .= "Respon en format JSON.\n";
        
        return $prompt;
    }
    
    /* ═══════════════════════════════════════════════════════════════════════════
     * SECCIÓ 11: FUNCIONS D'UTILITAT I CERCA
     * ═══════════════════════════════════════════════════════════════════════════ */
    
    public static function search( $keyword ) {
        $results = [];
        $keyword = mb_strtolower( $keyword );
        
        foreach ( self::get_llocs() as $slug => $item ) {
            if ( self::match_keywords( $item, $keyword ) ) {
                $results['llocs'][ $slug ] = $item;
            }
        }
        
        foreach ( self::get_barris() as $slug => $item ) {
            if ( self::match_keywords( $item, $keyword ) ) {
                $results['barris'][ $slug ] = $item;
            }
        }
        
        foreach ( self::get_festes() as $slug => $item ) {
            if ( self::match_keywords( $item, $keyword ) ) {
                $results['festes'][ $slug ] = $item;
            }
        }
        
        foreach ( self::get_personatges() as $slug => $item ) {
            if ( self::match_keywords( $item, $keyword ) ) {
                $results['personatges'][ $slug ] = $item;
            }
        }
        
        return $results;
    }
    
    private static function match_keywords( $item, $keyword ) {
        if ( isset( $item['nom'] ) && stripos( $item['nom'], $keyword ) !== false ) {
            return true;
        }
        
        if ( isset( $item['nom_alt'] ) ) {
            foreach ( $item['nom_alt'] as $nom ) {
                if ( stripos( $nom, $keyword ) !== false ) return true;
            }
        }
        
        if ( isset( $item['paraules_clau'] ) ) {
            foreach ( $item['paraules_clau'] as $pk ) {
                if ( stripos( $pk, $keyword ) !== false ) return true;
            }
        }
        
        if ( isset( $item['descripcio'] ) && stripos( $item['descripcio'], $keyword ) !== false ) {
            return true;
        }
        
        return false;
    }
    
    public static function get_all_lloc_slugs() {
        return array_keys( self::get_llocs() );
    }
    
    public static function get_all_barri_slugs() {
        return array_keys( self::get_barris() );
    }
    
    public static function get_all_keywords() {
        $keywords = [];
        
        foreach ( self::get_llocs() as $lloc ) {
            if ( isset( $lloc['paraules_clau'] ) ) {
                $keywords = array_merge( $keywords, $lloc['paraules_clau'] );
            }
        }
        
        foreach ( self::get_festes() as $festa ) {
            if ( isset( $festa['paraules_clau'] ) ) {
                $keywords = array_merge( $keywords, $festa['paraules_clau'] );
            }
        }
        
        foreach ( self::get_personatges() as $personatge ) {
            if ( isset( $personatge['paraules_clau'] ) ) {
                $keywords = array_merge( $keywords, $personatge['paraules_clau'] );
            }
        }
        
        $culturals = self::get_elements_culturals();
        foreach ( $culturals as $element ) {
            if ( isset( $element['paraules_clau'] ) ) {
                $keywords = array_merge( $keywords, $element['paraules_clau'] );
            }
        }
        
        return array_unique( $keywords );
    }
    
    public static function get_llocs_by_type( $tipus ) {
        $llocs = self::get_llocs();
        return array_filter( $llocs, function( $lloc ) use ( $tipus ) {
            return isset( $lloc['tipus'] ) && $lloc['tipus'] === $tipus;
        });
    }
    
    public static function get_festes_by_month( $mes ) {
        $festes = self::get_festes();
        return array_filter( $festes, function( $festa ) use ( $mes ) {
            return isset( $festa['mes'] ) && $festa['mes'] == $mes;
        });
    }
    
    /**
     * Buscar festa en text
     */
    public static function find_festa_in_text( $text ) {
        $text = mb_strtolower( $text );
        
        foreach ( self::get_festes() as $slug => $festa ) {
            if ( stripos( $text, mb_strtolower( $festa['nom'] ) ) !== false ) {
                return [ 'slug' => $slug, 'festa' => $festa ];
            }
            
            if ( isset( $festa['nom_alt'] ) ) {
                foreach ( $festa['nom_alt'] as $nom ) {
                    if ( stripos( $text, mb_strtolower( $nom ) ) !== false ) {
                        return [ 'slug' => $slug, 'festa' => $festa ];
                    }
                }
            }
            
            if ( isset( $festa['paraules_clau'] ) ) {
                foreach ( $festa['paraules_clau'] as $pk ) {
                    if ( stripos( $text, mb_strtolower( $pk ) ) !== false ) {
                        return [ 'slug' => $slug, 'festa' => $festa ];
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * MAPA DE CORRESPONDÈNCIA: Slug Knowledge Base → Slug Tileset Mapbox
     * Necessari perquè els slugs del GeoJSON són diferents
     */
    public static function get_tileset_slug_map() {
        return [
            // POIs
            'ermita-santa-barbara'          => 'ermita-santa-barbara',
            'esglesia-sant-joan-baptista'   => 'esglesia-snt-joan',
            'torre-del-rellotge'            => 'torre-rellotge',
            'exconvent-caputxins'           => 'exconvent-caputxins',
            'casino'                        => 'casino',
            'ajuntament'                    => 'ajuntament',
            'teatre-principal'              => 'teatre-principal',
            'teatre-fleta'                  => 'teatre-fleta',
            'plaça-sala'                    => 'plaça-sala',
            'plaça-malva'                   => 'plaça-malva',
            'plaça-bous'                    => 'plaça-bous',
            'parc-salitre'                  => 'parc-salitre',
            'parc-alameda'                  => 'alameda',
            'castell'                       => 'castell',
            'casa-museu-azorin'             => 'casa-museu-azorin',
            'casa-boles'                    => 'casa-de-les-boles',
            'jardinet'                      => 'jardinet',
            'mercat-central'                => 'mercat-mentral',
            'quartell-guardia-civil'        => 'quartell-guardi-civil',
            'ceip-cervantes'                => 'col·legi-cervantes',
            'matao'                         => 'matao',
            'cooperativa'                   => 'antigua-cooperativa',
            'moli-dalt'                     => 'moli-de-dalt',
            'moli-baix'                     => 'moli-de-baix',
            'refugi-fondo'                  => 'refugi-del-fondo',
            'ermita-fatima'                 => 'ermita-de-nostra-senyora-de-fatima',
            'ermita-sant-blai'              => 'ermita-de-san-blai',
            'ermita-sant-roc'               => 'ermita-de-san-roc',
            'ermita-sagrat-cor'             => 'ermita-sagrat-cor',
            'pont-gota'                     => 'aqueducte-pont-de-la-gota',
            'xemeneia-alcoholera'           => 'xemeneia-de-lalcoholera',
            'xemeneia-sabons'               => 'xemeneia-sabons',
            'casa-pintors'                  => 'casa-dels-pintors',
            'alqueria-xinosa'               => 'torre-i-alqueria-de-xinosa',
            'camp-futbol-moreres'           => 'camp-futbol-moreres',
            'llar-pensionista'              => 'llar-del-pensionista',
            'casa-carlos-tortosa'           => 'casa-de-carlos-tortosa',
            
            // Barris
            'centre-urba'                   => 'centre-urba',
            'nucli-historic'                => 'Nucli Historic',
            'santa-barbara'                 => 'santa-barbara',
            'convent-mercat'                => 'convent-mercat',
            'salitre-camp-marin'            => 'salitre-camp-marin',
            'quartell-cooperativa'          => 'quartell-coperativa',
            'alameda-camp-futbol'           => 'alameda-camp-futbol',
            'la-gilma-plaça-bous'           => 'la-gilma-plaça-bous',
            'la-goletja'                    => 'la-goletja',
            'xinosa'                        => 'xinosa',
            'cases-del-senyor'              => 'cases-del-senyor',
            'el-fondo'                      => 'el-fondo',
            'la-canalosa'                   => 'la-canalosa',
            'els-molins'                    => 'els-molins',
            'partides-rurals'               => 'partides-rurals',
        ];
    }
    
    // ═══════════════════════════════════════════════════════════════════════════
    // SISTEMA ESCALABLE DE POIS - Font de veritat: GeoJSON/Tileset
    // ═══════════════════════════════════════════════════════════════════════════
    
    /**
     * POIs del tileset (font de veritat)
     * Aquesta llista es pot actualitzar automàticament via sincronització
     * o manualment quan s'afegeixen nous POIs al Mapbox
     */
    public static function get_pois_tileset() {
        // Intentar obtenir POIs sincronitzats des de la base de dades
        $cached_pois = get_option( 'arxiu_pois_tileset', null );
        if ( $cached_pois && is_array( $cached_pois ) ) {
            return $cached_pois;
        }
        
        // Fallback: llista estàtica del GeoJSON (actualitzar si s'afegeixen nous POIs)
        return [
            'casino' => ['nom' => 'Casino', 'tipus' => 'Monument BRL', 'barri' => 'centre-urba'],
            'teatre-fleta' => ['nom' => 'Teatre Fleta', 'tipus' => 'Monument', 'barri' => 'centre-urba'],
            'teatre-principal' => ['nom' => 'Teatre Principal', 'tipus' => 'Monument', 'barri' => 'centre-urba'],
            'ajuntament' => ['nom' => 'Ajuntament', 'tipus' => 'Monument BRL', 'barri' => 'centre-urba'],
            'col·legi-cervantes' => ['nom' => 'CEIP Cervantes', 'tipus' => 'Monument BRL', 'barri' => 'centre-urba'],
            'plaça-sala' => ['nom' => 'Plaça de La Sala', 'tipus' => 'Plaça', 'barri' => 'centre-urba'],
            'parc-salitre' => ['nom' => 'Parc Del Salitre', 'tipus' => 'Parc', 'barri' => 'salitre-camp-marin'],
            'quartell-guardi-civil' => ['nom' => 'Quartell Guàrdia Civil', 'tipus' => '', 'barri' => 'quartell-coperativa'],
            'matao' => ['nom' => 'Mataó', 'tipus' => '', 'barri' => 'quartell-coperativa'],
            'antigua-cooperativa' => ['nom' => 'Antigua Cooperativa', 'tipus' => '', 'barri' => 'quartell-coperativa'],
            'esglesia-snt-joan' => ['nom' => 'Església de Sant Joan Baptista', 'tipus' => 'Monument BRL', 'barri' => 'Nucli Historic'],
            'jardinet' => ['nom' => 'El Jardinet', 'tipus' => 'Parc', 'barri' => 'Nucli Historic'],
            'torre-rellotge' => ['nom' => 'Torre del Rellotge', 'tipus' => 'Monument BRL', 'barri' => 'Nucli Historic'],
            'plaça-malva' => ['nom' => 'Plaça de la Malva', 'tipus' => 'Plaça', 'barri' => 'santa-barbara'],
            'ermita-santa-barbara' => ['nom' => 'Ermita Santa Bàrbara', 'tipus' => 'Monument BIC', 'barri' => 'santa-barbara'],
            'exconvent-caputxins' => ['nom' => 'Ex-convent dels Caputxins', 'tipus' => 'Monument BRL', 'barri' => 'convent-mercat'],
            'mercat-mentral' => ['nom' => 'Mercat Central', 'tipus' => 'Mercat', 'barri' => 'convent-mercat'],
            'plaça-bous' => ['nom' => 'Plaça de Bous', 'tipus' => 'Monument BRL', 'barri' => 'la-gilma-plaça-bous'],
            'camp-futbol-moreres' => ['nom' => 'Camp de Futbol Les Moreres', 'tipus' => 'Instalació Esportiva', 'barri' => 'alameda-camp-futbol'],
            'alameda' => ['nom' => 'Parc La Alameda', 'tipus' => 'Parc', 'barri' => 'alameda-camp-futbol'],
            'castell' => ['nom' => 'Castell', 'tipus' => 'Monument BIC', 'barri' => 'la-goletja'],
            'casa-dels-pintors' => ['nom' => 'Casa dels Pintors', 'tipus' => '', 'barri' => 'la-goletja'],
            'torre-i-alqueria-de-xinosa' => ['nom' => 'Torre i Alqueria de Xinosa', 'tipus' => 'Monument BIC', 'barri' => 'xinosa'],
            'escut-nobiliari-del-duc-dhixar' => ['nom' => 'Escut Nobiliari del Duc d\'Híxar', 'tipus' => 'Monument BIC', 'barri' => 'partides-rurals'],
            'ermita-de-les-cases-del-senyor' => ['nom' => 'Ermita de les Cases del Senyor', 'tipus' => 'Monument BRL', 'barri' => 'cases-del-senyor'],
            'aqueducte-pont-de-la-gota' => ['nom' => 'Aqüeducte Pont de la Gota', 'tipus' => 'Espai Etnològic', 'barri' => 'centre-urba'],
            'refugi-del-fondo' => ['nom' => 'Refugi del Fondó', 'tipus' => 'Monument BRL', 'barri' => 'el-fondo'],
            'ermita-de-nostra-senyora-de-fatima' => ['nom' => 'Ermita de Nostra Senyora de Fátima', 'tipus' => 'Monument BRL', 'barri' => 'la-canalosa'],
            'ermita-de-san-blai' => ['nom' => 'Ermita de San Blai', 'tipus' => 'Monument BRL', 'barri' => 'partides-rurals'],
            'ermita-de-san-roc' => ['nom' => 'Ermita de San Roc', 'tipus' => 'Monument BRL', 'barri' => 'partides-rurals'],
            'moli-de-dalt' => ['nom' => 'Molí de Dalt', 'tipus' => 'Espai Etnològic', 'barri' => 'els-molins'],
            'moli-de-baix' => ['nom' => 'Molí de Baix', 'tipus' => 'Espai Etnològic', 'barri' => 'els-molins'],
            'partidor-de-la-dotzena' => ['nom' => 'Partidor de la Dotzena', 'tipus' => 'Espai Etnològic', 'barri' => 'partides-rurals'],
            'xemeneia-de-lalcoholera' => ['nom' => 'Xemeneia de l\'Alcoholera', 'tipus' => 'Espai Etnològic', 'barri' => 'centre-urba'],
            'moli-del-safareig' => ['nom' => 'Molí del Safareig', 'tipus' => 'Espai Etnològic', 'barri' => 'partides-rurals'],
            'aqueducte-de-les-cases-del-senyor' => ['nom' => 'Aqüeducte de les Cases del Senyor', 'tipus' => 'Espai Etnològic', 'barri' => 'cases-del-senyor'],
            'aqueducte-de-la-pedrera' => ['nom' => 'Aqüeducte de la Pedrera', 'tipus' => 'Espai Etnològic', 'barri' => 'partides-rurals'],
            'ermita-santa-caterina-dalexandria' => ['nom' => 'Ermita Santa Caterina d\'Alexandria', 'tipus' => 'Monument BRL', 'barri' => 'partides-rurals'],
            'ermita-sagrat-cor' => ['nom' => 'Ermita Sagrat Cor', 'tipus' => 'Monument BRL', 'barri' => 'partides-rurals'],
            'xemeneia-sabons' => ['nom' => 'Xemeneia Sabons', 'tipus' => 'Espai Etnològic', 'barri' => 'centre-urba'],
            'algepsars-de-la-cavafria' => ['nom' => 'Algepsars de la Cavafría', 'tipus' => 'Espai Etnològic', 'barri' => 'partides-rurals'],
            'caleres-del-coto' => ['nom' => 'Caleres del Coto', 'tipus' => 'Espai Etnològic', 'barri' => 'partides-rurals'],
            'casa-museu-azorin' => ['nom' => 'Casa Museu Azorín', 'tipus' => 'Monument BRL', 'barri' => 'centre-urba'],
            'llar-del-pensionista' => ['nom' => 'Llar del Pensionista', 'tipus' => 'Monument BRL', 'barri' => 'centre-urba'],
            'casa-de-carlos-tortosa' => ['nom' => 'Casa de Carlos Tortosa', 'tipus' => 'Monument BRL', 'barri' => 'centre-urba'],
            'casa-de-les-boles' => ['nom' => 'Casa de les Boles', 'tipus' => 'Monument BRL', 'barri' => 'centre-urba'],
            'refugi-canters-1' => ['nom' => 'Refugi canters 1', 'tipus' => 'Espai Etnològic', 'barri' => 'xinosa'],
            'refugi-canters-5' => ['nom' => 'Refugi canters 5', 'tipus' => 'Espai Etnològic', 'barri' => 'xinosa'],
            'refugi-canters-6' => ['nom' => 'Refugi canters 6', 'tipus' => 'Espai Etnològic', 'barri' => 'xinosa'],
            'serreta-la-vella' => ['nom' => 'Serreta la Vella', 'tipus' => 'BRL', 'barri' => 'centre-urba'],
            'sambo' => ['nom' => 'Sambo', 'tipus' => 'BRL', 'barri' => 'partides-rurals'],
            'els-molins' => ['nom' => 'Els Molins', 'tipus' => 'BRL', 'barri' => 'partides-rurals'],
            'llometa' => ['nom' => 'Llometa', 'tipus' => 'BRL', 'barri' => 'partides-rurals'],
            'calafuig' => ['nom' => 'Calafuig', 'tipus' => 'BRL', 'barri' => 'partides-rurals'],
            'pena-de-la-zafra' => ['nom' => 'Peña de la Zafra', 'tipus' => 'BRL', 'barri' => 'partides-rurals'],
            'pla-manya' => ['nom' => 'Pla Manyà', 'tipus' => 'BRL', 'barri' => 'partides-rurals'],
            'cueva-de-la-romaneta' => ['nom' => 'Cueva de la Romaneta', 'tipus' => 'BRL', 'barri' => 'partides-rurals'],
            'cerro-casas-de-leon' => ['nom' => 'Cerro Casas de León', 'tipus' => 'Jaciment Arqueològic', 'barri' => 'partides-rurals'],
            'cerro-casas-de-la-pedrera' => ['nom' => 'Cerro Casas de la Pedrera', 'tipus' => 'Jaciment Arqueològic', 'barri' => 'partides-rurals'],
            'casas-del-altet' => ['nom' => 'Casas del Altet', 'tipus' => 'Jaciment Arqueològic', 'barri' => 'partides-rurals'],
        ];
    }
    
    /**
     * Barris del tileset (font de veritat)
     */
    public static function get_barris_tileset() {
        return [
            'centre-urba' => ['nom' => 'Centre Urbà'],
            'Nucli Historic' => ['nom' => 'Nucli Històric'],
            'santa-barbara' => ['nom' => 'Santa Bàrbara'],
            'convent-mercat' => ['nom' => 'Convent-Mercat'],
            'salitre-camp-marin' => ['nom' => 'Salitre-Camp Marín'],
            'quartell-coperativa' => ['nom' => 'Quartell-Cooperativa'],
            'alameda-camp-futbol' => ['nom' => 'Alameda-Camp Futbol'],
            'la-gilma-plaça-bous' => ['nom' => 'La Gilma-Plaça Bous'],
            'la-goletja' => ['nom' => 'La Goletja'],
            'xinosa' => ['nom' => 'Xinosa'],
            'cases-del-senyor' => ['nom' => 'Cases del Senyor'],
            'el-fondo' => ['nom' => 'El Fondó'],
            'la-canalosa' => ['nom' => 'La Canalosa'],
            'els-molins' => ['nom' => 'Els Molins'],
            'partides-rurals' => ['nom' => 'Partides Rurals'],
        ];
    }
    
    /**
     * Enriquiments visuals per a POIs específics
     * Usa els slugs del tileset com a clau
     * Pots afegir nous enriquiments sense modificar get_pois_tileset()
     */
    public static function get_pois_enriquiments() {
        return [
            'ermita-santa-barbara' => [
                'visuals' => [
                    '★ CÚPULA DE TEULES BLAVES VIDRIADES - Element distintiu PRINCIPAL',
                    '★ Situada en ALÇADA sobre el nucli urbà',
                    'Façana de PEDRA OCRE/BEIX (NO pintada de blanc)',
                    'Espadanya barroca amb creu de ferro',
                ],
                'keywords' => ['ermita', 'Santa Bàrbara', 'cúpula blava', 'alçada', 'pedra ocre'],
            ],
            'torre-rellotge' => [
                'visuals' => [
                    '★ TORRE EXENTA (no adossada a edifici) - Única al sud valencià',
                    '★ CÚPULA DE TEULA BLAVA VIDRIADA',
                    'Quatre cossos decreixents',
                    'Rellotge de sol al segon cos',
                ],
                'keywords' => ['torre', 'rellotge', 'campanar', 'cúpula blava', 'exenta'],
            ],
            'esglesia-snt-joan' => [
                'visuals' => [
                    '★ CAMPANAR ALT i prominent - Element distintiu PRINCIPAL',
                    '★ Façana GRAN i ornamentada a nivell de carrer',
                    'Cúpula blau marí (però menys visible que Santa Bàrbara)',
                    'Dues torres (una inacabada)',
                ],
                'keywords' => ['església', 'Sant Joan', 'campanar', 'parròquia'],
            ],
            'castell' => [
                'visuals' => [
                    '★ RUÏNES sobre turó elevat',
                    'Torres cilíndriques',
                    'Visible des de lluny pel seu emplaçament',
                ],
                'keywords' => ['castell', 'ruïnes', 'almohade', 'turó'],
            ],
            'ajuntament' => [
                'visuals' => [
                    'Edifici institucional amb façana noble',
                    'BALCONS prominents',
                    'Plaça al davant (Plaça de Dalt)',
                ],
                'keywords' => ['ajuntament', 'plaça', 'govern'],
            ],
            'casa-museu-azorin' => [
                'visuals' => [
                    'Casa senyorial del segle XIX',
                    'Façana amb balcons de ferro forjat',
                ],
                'keywords' => ['Azorín', 'museu', 'escriptor', 'casa'],
            ],
            'plaça-bous' => [
                'visuals' => [
                    '★ Plaça de toros circular',
                    'Arquitectura neoàrab/mudèjar',
                ],
                'keywords' => ['plaça bous', 'toros', 'arena'],
            ],
        ];
    }
    
    /**
     * Actualitzar POIs del tileset des d'un GeoJSON
     * Cridar això quan es sincronitzi amb Mapbox
     */
    public static function update_pois_from_geojson( $geojson_data ) {
        if ( ! isset( $geojson_data['features'] ) ) {
            return false;
        }
        
        $pois = [];
        foreach ( $geojson_data['features'] as $feature ) {
            $props = $feature['properties'] ?? [];
            $slug = $props['poi_slug'] ?? null;
            
            if ( $slug ) {
                $pois[ $slug ] = [
                    'nom' => $props['name'] ?? $slug,
                    'tipus' => $props['tipus'] ?? '',
                    'barri' => $props['parent_barri_slug'] ?? '',
                ];
            }
        }
        
        if ( ! empty( $pois ) ) {
            update_option( 'arxiu_pois_tileset', $pois );
            return count( $pois );
        }
        
        return false;
    }
    
    /**
     * Obtenir llista d'ubicacions per a la IA
     * FORMAT ESCALABLE: Usa directament els slugs del tileset
     */
    public static function get_ubicacions_per_ia() {
        $ubicacions = [
            'pois' => [],
            'barris' => []
        ];
        
        // Obtenir POIs del tileset (font de veritat)
        $pois_tileset = self::get_pois_tileset();
        $enriquiments = self::get_pois_enriquiments();
        
        foreach ( $pois_tileset as $slug => $poi ) {
            $item = [
                'slug' => $slug,  // ← SLUG EXACTE del tileset
                'nom' => $poi['nom'],
                'tipus' => $poi['tipus'] ?? '',
                'barri_slug' => $poi['barri'] ?? '',
            ];
            
            // Afegir enriquiments si existeixen
            if ( isset( $enriquiments[ $slug ] ) ) {
                if ( isset( $enriquiments[ $slug ]['visuals'] ) ) {
                    $item['visuals'] = $enriquiments[ $slug ]['visuals'];
                }
                if ( isset( $enriquiments[ $slug ]['keywords'] ) ) {
                    $item['keywords'] = $enriquiments[ $slug ]['keywords'];
                }
            }
            
            $ubicacions['pois'][] = $item;
        }
        
        // Obtenir barris del tileset
        foreach ( self::get_barris_tileset() as $slug => $barri ) {
            $ubicacions['barris'][] = [
                'slug' => $slug,  // ← SLUG EXACTE del tileset
                'nom' => $barri['nom'],
            ];
        }
        
        return $ubicacions;
    }
    
    /**
     * Generar text de context d'ubicacions per al prompt de Gemini
     * USA ELS SLUGS EXACTES DEL TILESET - Sistema escalable
     */
    public static function get_ubicacions_context() {
        $ubicacions = self::get_ubicacions_per_ia();
        
        $text = "\n\n═══ LLOCS DE MONÒVER - USA AQUESTS SLUGS EXACTAMENT ═══\n\n";
        
        $text .= "PUNTS D'INTERÈS (POIs):\n";
        foreach ( $ubicacions['pois'] as $poi ) {
            $text .= "• {$poi['nom']}\n";
            $text .= "  poi_slug: \"{$poi['slug']}\" | barri_slug: \"{$poi['barri_slug']}\"\n";
            
            // Si té enriquiments visuals, mostrar-los
            if ( isset( $poi['visuals'] ) && ! empty( $poi['visuals'] ) ) {
                $visuals_text = implode( ' | ', array_slice( $poi['visuals'], 0, 3 ) );
                $text .= "  IDENTIFICACIÓ: {$visuals_text}\n";
            }
        }
        
        $text .= "\nBARRIS:\n";
        foreach ( $ubicacions['barris'] as $barri ) {
            $text .= "• {$barri['nom']} → barri_slug: \"{$barri['slug']}\"\n";
        }
        
        $text .= "\n⚠️ IMPORTANT: Copia els slugs EXACTAMENT com apareixen (amb accents, guions, etc.)\n";
        
        return $text;
    }
    
    /**
     * Buscar ubicació per nom o text
     */
    public static function find_ubicacio( $text ) {
        $text_lower = mb_strtolower( $text );
        $resultats = [];
        
        // Buscar en llocs
        foreach ( self::get_llocs() as $slug => $lloc ) {
            $score = 0;
            
            // Nom exacte
            if ( stripos( $text_lower, mb_strtolower( $lloc['nom'] ) ) !== false ) {
                $score += 100;
            }
            
            // Noms alternatius
            if ( isset( $lloc['nom_alt'] ) ) {
                foreach ( $lloc['nom_alt'] as $alt ) {
                    if ( stripos( $text_lower, mb_strtolower( $alt ) ) !== false ) {
                        $score += 80;
                        break;
                    }
                }
            }
            
            // Paraules clau
            if ( isset( $lloc['paraules_clau'] ) ) {
                foreach ( $lloc['paraules_clau'] as $pk ) {
                    if ( stripos( $text_lower, mb_strtolower( $pk ) ) !== false ) {
                        $score += 10;
                    }
                }
            }
            
            if ( $score > 0 ) {
                $resultats[] = [
                    'slug' => $slug,
                    'nom' => $lloc['nom'],
                    'tipus' => 'poi',
                    'score' => $score,
                    'barri' => $lloc['barri'] ?? null
                ];
            }
        }
        
        // Buscar en barris
        foreach ( self::get_barris() as $slug => $barri ) {
            $score = 0;
            
            if ( stripos( $text_lower, mb_strtolower( $barri['nom'] ) ) !== false ) {
                $score += 80;
            }
            
            if ( isset( $barri['paraules_clau'] ) ) {
                foreach ( $barri['paraules_clau'] as $pk ) {
                    if ( stripos( $text_lower, mb_strtolower( $pk ) ) !== false ) {
                        $score += 10;
                    }
                }
            }
            
            if ( $score > 0 ) {
                $resultats[] = [
                    'slug' => $slug,
                    'nom' => $barri['nom'],
                    'tipus' => 'barri',
                    'score' => $score
                ];
            }
        }
        
        // Ordenar per score descendent
        usort( $resultats, function( $a, $b ) {
            return $b['score'] - $a['score'];
        });
        
        return $resultats;
    }
}
