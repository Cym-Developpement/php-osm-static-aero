<?php

namespace Ycdev\OsmStaticAero;

/**
 * Table de symbologie OpenAIP : couleurs, traits et pictogrammes.
 *
 * Les valeurs proviennent de la definition de style publiee par OpenAIP
 * (depot openAIP/openaip-map-resources, resources/layers/layers.js), pour que
 * la legende decrive exactement ce que rendent les tuiles OpenAIP superposees
 * a la carte. Chaque entree cite la couche d'origine et le filtre qui la
 * declenche, afin qu'une mise a jour amont soit facile a reporter.
 *
 * Une adaptation assumee : les remplissages du style d'origine sont tres
 * transparents (5 % a 20 %), ce qui convient a une carte ou ils s'etendent sur
 * plusieurs centimetres, mais rend une vignette de quelques millimetres
 * indistincte du blanc. Les teintes sont donc reprises telles quelles, les
 * opacites remontees a un niveau lisible.
 *
 * @package Ycdev\OsmStaticAero
 * @see https://github.com/openAIP/openaip-map-resources Symboles et style, CC BY-NC-SA 4.0
 */
class OpenAipSymbology
{
    /**
     * Groupes de la legende complete, dans l'ordre d'affichage.
     *
     * @return array[] Liste de ['title' => string, 'entries' => array[]]
     */
    public static function groups(): array
    {
        return [
            [
                'title'   => 'Espaces aeriens controles',
                'entries' => [
                    // Couche airspace_ab_border : type=other, icao_class in (a,b)
                    ['label' => 'Classe A / B', 'type' => 'airspace', 'stroke' => '339e2f', 'dash' => [10, 6], 'band' => ['mode' => 'solid', 'color' => '339e2f', 'alpha' => 0.45]],
                    // airspace_cd_border : type=other, icao_class in (c,d)
                    ['label' => 'Classe C / D', 'type' => 'airspace', 'stroke' => '339e2f', 'band' => ['mode' => 'hatch', 'color' => '339e2f']],
                    // airspace_e_border : type=other, icao_class=e
                    ['label' => 'Classe E', 'type' => 'airspace', 'stroke' => '154d9a'],
                    // airspace_f_border / _offset
                    ['label' => 'Classe F', 'type' => 'airspace', 'stroke' => '154d9a', 'band' => ['mode' => 'solid', 'color' => '7691c3', 'alpha' => 0.55]],
                    // airspace_g_border / _offset : trait a 50 % d'opacite
                    ['label' => 'Classe G', 'type' => 'airspace', 'stroke' => '154d9a', 'strokeAlpha' => 0.5, 'dash' => [5, 5], 'band' => ['mode' => 'solid', 'color' => '7691c3', 'alpha' => 0.3]],
                    // airspace_ctr_border + airspace_ctr_fill
                    ['label' => 'CTR', 'type' => 'airspace', 'stroke' => '154d9a', 'dash' => [12, 4], 'fill' => 'da6f86', 'fillAlpha' => 0.35],
                    // airspace_tma_cta_border / _offset : type in (tma,cta)
                    ['label' => 'TMA / CTA', 'type' => 'airspace', 'stroke' => '154d9a', 'band' => ['mode' => 'solid', 'color' => 'da6f86', 'alpha' => 0.5]],
                ],
            ],
            [
                'title'   => 'Zones reglementees et militaires',
                'entries' => [
                    // airspace_rdp_border / _offset : type in (restricted,danger,prohibited)
                    ['label' => 'Zones P / R / D', 'type' => 'airspace', 'stroke' => '9a0e0e', 'band' => ['mode' => 'hatch', 'color' => '9a0e0e']],
                    // airspace_tsa_border
                    ['label' => 'TSA', 'type' => 'airspace', 'stroke' => '9a0e0e', 'band' => ['mode' => 'hatch', 'color' => '9a0e0e']],
                    // airspace_tra_border : meme rouge, trait tirete
                    ['label' => 'TRA', 'type' => 'airspace', 'stroke' => '9a0e0e', 'dash' => [10, 6], 'band' => ['mode' => 'hatch', 'color' => '9a0e0e']],
                    // airspace_tfr_border : restriction temporaire, trait a 50 %
                    ['label' => 'TFR (temporaire)', 'type' => 'airspace', 'stroke' => '9a0e0e', 'strokeAlpha' => 0.5],
                    // airspace_alwapro_border / _fill : type in (alert,warning,protected)
                    ['label' => 'Alert / Warning / Protected', 'type' => 'airspace', 'stroke' => '9335c9', 'dash' => [12, 4], 'fill' => '9335c9', 'fillAlpha' => 0.25],
                    // airspace_moa_border / _fill : type in (mtr,mta,mrt)
                    ['label' => 'Zones et routes militaires', 'type' => 'airspace', 'stroke' => 'ff9200', 'dash' => [4, 4], 'fill' => 'ff9200', 'fillAlpha' => 0.25],
                    // airspace_overflight_restriction_border
                    ['label' => 'Restriction de survol', 'type' => 'airspace', 'stroke' => '77159a'],
                    // airspace_adiz_border / _offset
                    ['label' => 'ADIZ', 'type' => 'airspace', 'stroke' => '560096', 'band' => ['mode' => 'solid', 'color' => '7a0096', 'alpha' => 0.4]],
                ],
            ],
            [
                'title'   => 'Autres espaces et services',
                'entries' => [
                    // airspace_gliding_sector : type in (gliding_sector,vfr_sector,lta,uta)
                    ['label' => 'Secteur planeur / VFR', 'type' => 'airspace', 'stroke' => 'ffd700', 'fill' => 'ffd700', 'fillAlpha' => 0.45],
                    // airspace_aerial_sporting_recreational_border / _offset
                    ['label' => 'Sport aerien et loisirs', 'type' => 'airspace', 'stroke' => '008baf', 'band' => ['mode' => 'hatch', 'color' => '008baf']],
                    // airspace_tmz_border
                    ['label' => 'TMZ (transpondeur)', 'type' => 'airspace', 'stroke' => '154d9a', 'dash' => [10, 10]],
                    // airspace_rmz_tiz_tia_border / _fill : type in (rmz,tiz,tia)
                    ['label' => 'RMZ / TIZ / TIA', 'type' => 'airspace', 'stroke' => '154d9a', 'dash' => [3, 3], 'fill' => '6586af', 'fillAlpha' => 0.3],
                    // airspace_traffic_border / _offset : type in (matz,atz,htz)
                    ['label' => 'ATZ / MATZ / HTZ', 'type' => 'airspace', 'stroke' => '154d9a', 'band' => ['mode' => 'hatch', 'color' => '154d9a']],
                    // airspace_trp_border
                    ['label' => 'Route de transit', 'type' => 'airspace', 'stroke' => '154d9a', 'dash' => [4, 4]],
                    // airspace_ways_border / _offset / _fill : type=awy
                    ['label' => 'Voie aerienne (AWY)', 'type' => 'airspace', 'stroke' => '575757', 'band' => ['mode' => 'hatch', 'color' => '575757'], 'fill' => 'cecece', 'fillAlpha' => 0.3],
                    // airspace_fir_fis_acc_border : type in (fir,acc_sector,fis_sector)
                    ['label' => 'FIR / FIS / ACC', 'type' => 'airspace', 'stroke' => '6ec920', 'strokeAlpha' => 0.6, 'dash' => [10, 5]],
                    // airspace_uir_border
                    ['label' => 'UIR', 'type' => 'airspace', 'stroke' => '5b9c26', 'strokeAlpha' => 0.6, 'dash' => [10, 5]],
                ],
            ],
            [
                'title'   => 'Terrains et plateformes',
                'entries' => [
                    ['label' => 'Aeroport', 'type' => 'icon', 'icon' => 'apt-medium'],
                    ['label' => 'Aerodrome civil', 'type' => 'icon', 'icon' => 'af_civil-medium'],
                    ['label' => 'Mixte civil / militaire', 'type' => 'icon', 'icon' => 'apt_mil_civil-medium'],
                    ['label' => 'Militaire', 'type' => 'icon', 'icon' => 'ad_mil-medium'],
                    ['label' => 'Terrain ferme', 'type' => 'icon', 'icon' => 'ad_closed-medium'],
                    ['label' => 'Hydrobase', 'type' => 'icon', 'icon' => 'af_water-medium'],
                    ['label' => 'Helistation civile', 'type' => 'icon', 'icon' => 'heli_civil-medium'],
                    ['label' => 'Helistation militaire', 'type' => 'icon', 'icon' => 'heli_mil-medium'],
                    ['label' => 'Piste revetue', 'type' => 'icon', 'icon' => 'runway_paved-medium'],
                    ['label' => 'Piste non revetue', 'type' => 'icon', 'icon' => 'runway_unpaved-medium'],
                    ['label' => 'Avion leger / ULM', 'type' => 'icon', 'icon' => 'light_aircraft-medium'],
                    ['label' => 'Plateforme (LS)', 'type' => 'icon', 'icon' => 'ls-medium'],
                    ['label' => 'Piste agricole', 'type' => 'icon', 'icon' => 'ls_agri-medium'],
                    ['label' => 'Altiport', 'type' => 'icon', 'icon' => 'ls_alti-medium'],
                    ['label' => 'Parachutisme', 'type' => 'icon', 'icon' => 'parachute-small'],
                    ['label' => 'Aeromodelisme', 'type' => 'icon', 'icon' => 'rc_airfield'],
                ],
            ],
            [
                'title'   => 'Vol a voile et vol libre',
                'entries' => [
                    ['label' => 'Site de vol a voile', 'type' => 'icon', 'icon' => 'gliding-medium'],
                    ['label' => 'Treuil planeur', 'type' => 'icon', 'icon' => 'gliding_winch-medium'],
                    ['label' => 'Circuit planeur', 'type' => 'icon', 'icon' => 'traffic_circuit_glider-medium'],
                    ['label' => 'Circuit avion', 'type' => 'icon', 'icon' => 'traffic_circuit_motor_powered-medium'],
                    ['label' => 'Vol libre', 'type' => 'icon', 'icon' => 'hang_gliding-medium'],
                    ['label' => 'Vol libre : decollage', 'type' => 'icon', 'icon' => 'hang_gliding_take_off-small'],
                    ['label' => 'Vol libre : atterrissage', 'type' => 'icon', 'icon' => 'hang_gliding_landing-small'],
                ],
            ],
            [
                'title'   => 'Radionavigation',
                'entries' => [
                    ['label' => 'VOR', 'type' => 'icon', 'icon' => 'navaid_vor-medium'],
                    ['label' => 'VOR-DME', 'type' => 'icon', 'icon' => 'navaid_vor_dme-medium'],
                    ['label' => 'VORTAC', 'type' => 'icon', 'icon' => 'navaid_vortac-medium'],
                    ['label' => 'TACAN', 'type' => 'icon', 'icon' => 'navaid_tacan-medium'],
                    ['label' => 'DME', 'type' => 'icon', 'icon' => 'navaid_dme-medium'],
                    ['label' => 'NDB', 'type' => 'icon', 'icon' => 'navaid_ndb-medium'],
                    ['label' => 'Rose des vents', 'type' => 'icon', 'icon' => 'navaid_rose-medium'],
                ],
            ],
            [
                'title'   => 'Points de report',
                'entries' => [
                    ['label' => 'Obligatoire', 'type' => 'icon', 'icon' => 'reporting_point_compulsory-medium'],
                    ['label' => 'Sur demande', 'type' => 'icon', 'icon' => 'reporting_point_request-medium'],
                ],
            ],
            [
                'title'   => 'Obstacles',
                'entries' => [
                    ['label' => 'Obstacle', 'type' => 'icon', 'icon' => 'obstacle_obstacle'],
                    ['label' => 'Pylone / antenne', 'type' => 'icon', 'icon' => 'obstacle_tower'],
                    ['label' => 'Eolienne', 'type' => 'icon', 'icon' => 'obstacle_wind_turbine'],
                    ['label' => 'Cheminee', 'type' => 'icon', 'icon' => 'obstacle_chimney'],
                    ['label' => 'Batiment', 'type' => 'icon', 'icon' => 'obstacle_building'],
                ],
            ],
            [
                'title'   => 'Thermiques',
                'entries' => [
                    ['label' => 'Fiabilite faible', 'type' => 'icon', 'icon' => 'hotspot_poor-medium'],
                    ['label' => 'Fiabilite moyenne', 'type' => 'icon', 'icon' => 'hotspot_fair-medium'],
                    ['label' => 'Fiabilite bonne', 'type' => 'icon', 'icon' => 'hotspot_high-medium'],
                    ['label' => 'Fiabilite tres bonne', 'type' => 'icon', 'icon' => 'hotspot_very_high-medium'],
                    ['label' => 'Source industrielle', 'type' => 'icon', 'icon' => 'hotspot_industrial'],
                ],
            ],
        ];
    }

    /**
     * Noms des pictogrammes utilises par la legende complete.
     *
     * Utile pour alimenter OpenAipSymbols::prefetch() avant un rendu hors ligne.
     *
     * @param array[]|null $groups Groupes a inspecter, null pour la table complete
     * @return string[]
     */
    public static function iconNames($groups = null): array
    {
        $names = [];

        foreach ($groups === null ? static::groups() : $groups as $group) {
            foreach ($group['entries'] as $entry) {
                if (isset($entry['icon'])) {
                    $names[] = $entry['icon'];
                }
            }
        }

        return \array_values(\array_unique($names));
    }
}
