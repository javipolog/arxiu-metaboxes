<?php
/**
 * Arxiu Date Detector - Sistema de detecció de dates en 3 nivells
 * 
 * Nivell 1: Anàlisi del títol/nom de fitxer
 * Nivell 2: Text visible a la imatge (via Gemini)
 * Nivell 3: Context visual (via Gemini)
 * 
 * @package Arxiu_Imagen_IA
 * @since 12.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Arxiu_Date_Detector {
    
    /**
     * Nivells de confiança
     */
    const CONFIDENCE_HIGH   = 'high';
    const CONFIDENCE_MEDIUM = 'medium';
    const CONFIDENCE_LOW    = 'low';
    
    /**
     * NIVELL 1: Analitza el títol/nom de fitxer per trobar dates
     */
    public static function analyze_title( $title ) {
        $title = mb_strtolower( trim( $title ) );
        
        $result = [
            'found'       => false,
            'year'        => null,
            'year_end'    => null,
            'month'       => null,
            'day'         => null,
            'confidence'  => null,
            'source'      => 'title',
            'reason'      => null
        ];
        
        // ═══════════════════════════════════════════════════════════════════
        // PATRÓ 1: Data completa (dd/mm/yyyy, dd-mm-yyyy, dd.mm.yyyy)
        // ═══════════════════════════════════════════════════════════════════
        if ( preg_match( '/(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/', $title, $matches ) ) {
            $day   = intval( $matches[1] );
            $month = intval( $matches[2] );
            $year  = intval( $matches[3] );
            
            if ( $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31 && $year >= 1800 && $year <= date('Y') ) {
                return array_merge( $result, [
                    'found'      => true,
                    'year'       => $year,
                    'month'      => $month,
                    'day'        => $day,
                    'confidence' => self::CONFIDENCE_HIGH,
                    'reason'     => "Data exacta trobada: {$day}/{$month}/{$year}"
                ]);
            }
        }
        
        // ═══════════════════════════════════════════════════════════════════
        // PATRÓ 2: Mes i any en text (setembre 1986, septiembre de 1986)
        // ═══════════════════════════════════════════════════════════════════
        $mesos = [
            'gener' => 1, 'enero' => 1, 'january' => 1, 'gen' => 1, 'ene' => 1,
            'febrer' => 2, 'febrero' => 2, 'february' => 2, 'feb' => 2,
            'març' => 3, 'marzo' => 3, 'march' => 3, 'mar' => 3,
            'abril' => 4, 'april' => 4, 'abr' => 4,
            'maig' => 5, 'mayo' => 5, 'may' => 5, 'mai' => 5,
            'juny' => 6, 'junio' => 6, 'june' => 6, 'jun' => 6,
            'juliol' => 7, 'julio' => 7, 'july' => 7, 'jul' => 7,
            'agost' => 8, 'agosto' => 8, 'august' => 8, 'ago' => 8, 'aug' => 8,
            'setembre' => 9, 'septiembre' => 9, 'september' => 9, 'set' => 9, 'sep' => 9,
            'octubre' => 10, 'october' => 10, 'oct' => 10,
            'novembre' => 11, 'noviembre' => 11, 'november' => 11, 'nov' => 11,
            'desembre' => 12, 'diciembre' => 12, 'december' => 12, 'des' => 12, 'dic' => 12
        ];
        
        foreach ( $mesos as $mes_nom => $mes_num ) {
            $patterns = [
                "/{$mes_nom}\s+(?:de\s+)?(\d{4})/u",
                "/(\d{4})\s+{$mes_nom}/u"
            ];
            
            foreach ( $patterns as $pattern ) {
                if ( preg_match( $pattern, $title, $matches ) ) {
                    $year = intval( $matches[1] );
                    if ( $year >= 1800 && $year <= date('Y') ) {
                        return array_merge( $result, [
                            'found'      => true,
                            'year'       => $year,
                            'month'      => $mes_num,
                            'confidence' => self::CONFIDENCE_HIGH,
                            'reason'     => "Mes i any trobats: {$mes_nom} {$year}"
                        ]);
                    }
                }
            }
        }
        
        // ═══════════════════════════════════════════════════════════════════
        // PATRÓ 3: Any sol + possible festa (1986, festes 1986)
        // ═══════════════════════════════════════════════════════════════════
        if ( preg_match( '/\b(1[89]\d{2}|20[0-2]\d)\b/', $title, $matches ) ) {
            $year = intval( $matches[1] );
            
            $result = array_merge( $result, [
                'found'      => true,
                'year'       => $year,
                'confidence' => self::CONFIDENCE_HIGH,
                'reason'     => "Any trobat: {$year}"
            ]);
            
            // Buscar si hi ha referència a una festa per deduir el mes
            $festa_match = Arxiu_Knowledge_Base::find_festa_in_text( $title );
            if ( $festa_match && isset( $festa_match['festa']['mes'] ) && $festa_match['festa']['mes'] ) {
                $result['month'] = $festa_match['festa']['mes'];
                
                if ( isset( $festa_match['festa']['dia_inici'] ) && 
                     isset( $festa_match['festa']['dia_fi'] ) &&
                     $festa_match['festa']['dia_inici'] === $festa_match['festa']['dia_fi'] ) {
                    $result['day'] = $festa_match['festa']['dia_inici'];
                }
                
                $result['reason'] .= " + {$festa_match['festa']['nom']}";
            }
            
            return $result;
        }
        
        // ═══════════════════════════════════════════════════════════════════
        // PATRÓ 4: Dècada (anys 80, años 70, dècada dels 90, 80s)
        // ═══════════════════════════════════════════════════════════════════
        $decade_patterns = [
            '/\b(?:anys|años|any|año)\s*[^\d]*?(\d{2})\b/iu',
            '/\b(?:dècada|década|decada)\s*(?:dels?\s*)?(\d{2})\b/iu',
            '/\b[\x27"]?(\d{2})[\x27"]?s\b/i',
        ];
        
        foreach ( $decade_patterns as $pattern ) {
            if ( preg_match( $pattern, $title, $matches ) ) {
                $decade = intval( $matches[1] );
                
                if ( $decade >= 40 && $decade <= 99 ) {
                    $year_start = 1900 + $decade;
                    $year_end = $year_start + 9;
                } elseif ( $decade >= 0 && $decade <= 30 ) {
                    $year_start = 2000 + $decade;
                    $year_end = $year_start + 9;
                } else {
                    continue;
                }
                
                return array_merge( $result, [
                    'found'      => true,
                    'year'       => $year_start,
                    'year_end'   => $year_end,
                    'confidence' => self::CONFIDENCE_MEDIUM,
                    'reason'     => "Dècada trobada: anys {$decade} ({$year_start}-{$year_end})"
                ]);
            }
        }
        
        // ═══════════════════════════════════════════════════════════════════
        // PATRÓ 5: Curs escolar (1985-86, 85-86, curs 1985/86)
        // ═══════════════════════════════════════════════════════════════════
        if ( preg_match( '/\b(?:curs\s*)?(\d{2,4})[\/\-](\d{2})\b/iu', $title, $matches ) ) {
            $year1_str = $matches[1];
            $year2_short = intval( $matches[2] );
            
            // Determinar el primer any complet
            if ( strlen( $year1_str ) === 4 ) {
                $year1 = intval( $year1_str );
            } else {
                $decade = intval( $year1_str );
                $year1 = ( $decade >= 40 ) ? 1900 + $decade : 2000 + $decade;
            }
            
            // El segon any ha de ser consecutiu
            $year2 = $year1 + 1;
            $expected_short = $year2 % 100;
            
            if ( $year2_short === $expected_short && $year1 >= 1900 && $year2 <= date('Y') + 1 ) {
                return array_merge( $result, [
                    'found'      => true,
                    'year'       => $year1,
                    'year_end'   => $year2,
                    'month'      => 9, // Cursos comencen setembre
                    'confidence' => self::CONFIDENCE_HIGH,
                    'reason'     => "Curs escolar detectat: {$year1}-" . substr( $year2, 2 )
                ]);
            }
        }
        
        // ═══════════════════════════════════════════════════════════════════
        // PATRÓ 6: Esdeveniments amb any (inauguració, centenari, aniversari)
        // ═══════════════════════════════════════════════════════════════════
        $esdeveniments = [
            'inauguraci[óo]n?' => 'Inauguració',
            'centenari[o]?' => 'Centenari',
            '(?:\d+[ºªè]?\s*)?aniversari[o]?' => 'Aniversari',
            'homenatg?e' => 'Homenatge',
            'commemoraci[óo]n?' => 'Commemoració',
            'fundaci[óo]n?' => 'Fundació',
        ];
        
        foreach ( $esdeveniments as $pattern => $nom ) {
            if ( preg_match( "/{$pattern}.*?(\d{4})/iu", $title, $matches ) ) {
                $year = intval( $matches[1] );
                if ( $year >= 1800 && $year <= date('Y') ) {
                    return array_merge( $result, [
                        'found'      => true,
                        'year'       => $year,
                        'confidence' => self::CONFIDENCE_HIGH,
                        'reason'     => "{$nom} detectat: {$year}"
                    ]);
                }
            }
        }
        
        // ═══════════════════════════════════════════════════════════════════
        // PATRÓ 7: Només festa sense any - Deduir mes
        // ═══════════════════════════════════════════════════════════════════
        $festa_match = Arxiu_Knowledge_Base::find_festa_in_text( $title );
        if ( $festa_match ) {
            $result['found'] = true;
            
            if ( isset( $festa_match['festa']['mes'] ) && $festa_match['festa']['mes'] ) {
                $result['month'] = $festa_match['festa']['mes'];
            }
            
            if ( isset( $festa_match['festa']['dia_inici'] ) && 
                 isset( $festa_match['festa']['dia_fi'] ) &&
                 $festa_match['festa']['dia_inici'] === $festa_match['festa']['dia_fi'] ) {
                $result['day'] = $festa_match['festa']['dia_inici'];
            }
            
            $result['confidence'] = self::CONFIDENCE_LOW;
            $result['reason'] = "Festa detectada: {$festa_match['festa']['nom']} (any desconegut)";
            
            return $result;
        }
        
        return $result;
    }
    
    /**
     * Combina resultats de títol i IA
     */
    public static function merge_results( $title_result, $ai_result ) {
        // Si el títol té data exacta amb alta confiança, prioritzar
        if ( $title_result['found'] && $title_result['confidence'] === self::CONFIDENCE_HIGH ) {
            // Però si la IA té més detalls (mes/dia), afegir-los
            if ( ! $title_result['month'] && isset( $ai_result['month'] ) && $ai_result['month'] ) {
                $title_result['month'] = $ai_result['month'];
                $title_result['reason'] .= " + IA detecta mes";
            }
            if ( ! $title_result['day'] && isset( $ai_result['day'] ) && $ai_result['day'] ) {
                $title_result['day'] = $ai_result['day'];
            }
            return $title_result;
        }
        
        // Si el títol té any però no mes, i la IA té mes, combinar
        if ( $title_result['found'] && $title_result['year'] && ! $title_result['month'] ) {
            if ( isset( $ai_result['month'] ) && $ai_result['month'] ) {
                $title_result['month'] = $ai_result['month'];
                $title_result['reason'] .= " + mes detectat per IA";
            }
            return $title_result;
        }
        
        // Si el títol només té festa (mes) sense any, i la IA té any
        if ( $title_result['found'] && ! $title_result['year'] && $title_result['month'] ) {
            if ( isset( $ai_result['year'] ) && $ai_result['year'] ) {
                $title_result['year'] = $ai_result['year'];
                $title_result['year_end'] = $ai_result['year_end'] ?? null;
                $title_result['confidence'] = $ai_result['confidence'] ?? self::CONFIDENCE_MEDIUM;
                $title_result['reason'] .= " + any estimat per IA: {$ai_result['year']}";
                return $title_result;
            }
        }
        
        // Si no hi ha res del títol, usar IA
        if ( ! $title_result['found'] && $ai_result && isset( $ai_result['year'] ) ) {
            return [
                'found'      => true,
                'year'       => $ai_result['year'],
                'year_end'   => $ai_result['year_end'] ?? null,
                'month'      => $ai_result['month'] ?? null,
                'day'        => $ai_result['day'] ?? null,
                'confidence' => $ai_result['confidence'] ?? self::CONFIDENCE_LOW,
                'source'     => 'ai',
                'reason'     => $ai_result['reason'] ?? 'Estimació per IA'
            ];
        }
        
        return $title_result;
    }
    
    /**
     * Estima la dècada basant-se en característiques visuals
     * (per a ús intern i debugging)
     */
    public static function get_visual_indicators() {
        return [
            'foto_bn_antiga' => [
                'descripcio' => 'Foto en blanc i negre, qualitat baixa',
                'rang' => [ 1900, 1960 ],
                'confiança' => self::CONFIDENCE_LOW
            ],
            'foto_bn_professional' => [
                'descripcio' => 'Foto B/N qualitat professional',
                'rang' => [ 1940, 1975 ],
                'confiança' => self::CONFIDENCE_LOW
            ],
            'color_desaturat' => [
                'descripcio' => 'Color desaturat/groguenc',
                'rang' => [ 1970, 1990 ],
                'confiança' => self::CONFIDENCE_LOW
            ],
            'color_viu' => [
                'descripcio' => 'Color viu, qualitat digital',
                'rang' => [ 1995, date('Y') ],
                'confiança' => self::CONFIDENCE_LOW
            ],
            'seat_600' => [
                'descripcio' => 'SEAT 600 visible',
                'rang' => [ 1957, 1973 ],
                'confiança' => self::CONFIDENCE_MEDIUM
            ],
            'seat_127' => [
                'descripcio' => 'SEAT 127 visible',
                'rang' => [ 1972, 1985 ],
                'confiança' => self::CONFIDENCE_MEDIUM
            ],
            'seat_ibiza_1' => [
                'descripcio' => 'SEAT Ibiza 1a gen',
                'rang' => [ 1984, 1993 ],
                'confiança' => self::CONFIDENCE_MEDIUM
            ],
            'tv_tubo' => [
                'descripcio' => 'TV de tub catòdic',
                'rang' => [ 1960, 2005 ],
                'confiança' => self::CONFIDENCE_LOW
            ],
            'mobil_analog' => [
                'descripcio' => 'Mòbil analògic/Nokia antic',
                'rang' => [ 1995, 2007 ],
                'confiança' => self::CONFIDENCE_MEDIUM
            ],
            'smartphone' => [
                'descripcio' => 'Smartphone visible',
                'rang' => [ 2007, date('Y') ],
                'confiança' => self::CONFIDENCE_HIGH
            ]
        ];
    }
    
    /**
     * Valida si una data és coherent
     */
    public static function validate_date( $year, $month = null, $day = null ) {
        $current_year = (int) date( 'Y' );
        
        // Any ha d'estar entre 1800 i ara
        if ( $year < 1800 || $year > $current_year ) {
            return false;
        }
        
        // Mes ha d'estar entre 1 i 12
        if ( $month !== null && ( $month < 1 || $month > 12 ) ) {
            return false;
        }
        
        // Dia ha d'estar entre 1 i 31
        if ( $day !== null && ( $day < 1 || $day > 31 ) ) {
            return false;
        }
        
        // Si tenim tots els camps, validar que el dia existeix en aquell mes
        if ( $year && $month && $day ) {
            $days_in_month = cal_days_in_month( CAL_GREGORIAN, $month, $year );
            if ( $day > $days_in_month ) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Formata una data per mostrar
     */
    public static function format_date( $year, $month = null, $day = null, $year_end = null ) {
        $mesos = [
            1 => 'gener', 2 => 'febrer', 3 => 'març', 4 => 'abril',
            5 => 'maig', 6 => 'juny', 7 => 'juliol', 8 => 'agost',
            9 => 'setembre', 10 => 'octubre', 11 => 'novembre', 12 => 'desembre'
        ];
        
        $result = '';
        
        if ( $day ) {
            $result .= $day . ' ';
        }
        
        if ( $month && isset( $mesos[ $month ] ) ) {
            $result .= $mesos[ $month ] . ' ';
        }
        
        $result .= $year;
        
        if ( $year_end && $year_end !== $year ) {
            $result .= '-' . $year_end;
        }
        
        return trim( $result );
    }
}
