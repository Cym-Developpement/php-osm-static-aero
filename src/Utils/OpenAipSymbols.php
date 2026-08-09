<?php

namespace Ycdev\OsmStaticAero\Utils;

/**
 * Fournit les pictogrammes OpenAIP sous forme de PNG, a la taille voulue.
 *
 * Les symboles sont publies en SVG par OpenAIP dans le depot
 * openAIP/openaip-map-resources, sous licence CC BY-NC-SA 4.0. Ils ne sont pas
 * embarques dans cette bibliotheque, qui est sous MIT : melanger les deux
 * licences dans un meme paquet poserait probleme. Ils sont donc telecharges au
 * premier usage puis mis en cache sur disque, comme le sont deja les tuiles.
 *
 * Le SVG est un atout ici : il est rasterise a la taille finale demandee, donc
 * a la resolution d'impression, sans jamais agrandir un bitmap.
 *
 * @package Ycdev\OsmStaticAero\Utils
 */
class OpenAipSymbols
{
    /**
     * Depot des symboles OpenAIP.
     */
    const BASE_URL = 'https://raw.githubusercontent.com/openAIP/openaip-map-resources/HEAD/resources/svg/';

    /**
     * Attribution imposee par la licence CC BY-NC-SA 4.0 des symboles.
     */
    const ATTRIBUTION = 'Symboles © openAIP (CC BY-NC-SA 4.0)';

    /**
     * @var string|null Dossier de cache ; par defaut .openaip_symbols dans le
     *                  repertoire de travail, a cote de .tiles_cache
     */
    public static $cacheDirectory = null;

    /**
     * @var bool Autorise le telechargement des SVG absents du cache. A passer a
     *           false pour garantir un rendu hors ligne.
     */
    public static $allowDownload = true;

    /**
     * @var int Delai maximal d'un telechargement, en secondes
     */
    public static $timeout = 15;

    /**
     * @var string[] Symboles qu'on n'a pas pu obtenir, pour diagnostic
     */
    private static $missing = [];

    /**
     * @var array|null Rasteriseur retenu : ['bin' => chemin, 'type' => 'rsvg'|'magick']
     */
    private static $rasterizer = null;

    /**
     * PNG d'un symbole OpenAIP, rasterise a la hauteur demandee.
     *
     * @param string $name Nom du symbole, sans extension (ex. 'gliding-medium')
     * @param int $height Hauteur voulue en pixels
     * @return string|null Chemin du PNG, null si le symbole est indisponible
     */
    public static function pngPath(string $name, int $height)
    {
        if ($height < 1) {
            return null;
        }

        $pngPath = static::cacheDirectory() . '/' . $name . '@' . $height . '.png';

        if (\is_file($pngPath) && \filesize($pngPath) > 0) {
            return $pngPath;
        }

        $svgPath = static::svgPath($name);

        if ($svgPath === null) {
            return null;
        }

        return static::rasterize($svgPath, $pngPath, $height) ? $pngPath : null;
    }

    /**
     * Telecharge a l'avance une liste de symboles, pour qu'un rendu ulterieur
     * n'ait plus besoin du reseau.
     *
     * @param string[] $names
     * @return int Nombre de symboles disponibles apres l'operation
     */
    public static function prefetch(array $names): int
    {
        $ok = 0;

        foreach (\array_unique($names) as $name) {
            if (static::svgPath($name) !== null) {
                $ok++;
            }
        }

        return $ok;
    }

    /**
     * Symboles demandes et non obtenus depuis le demarrage.
     *
     * @return string[]
     */
    public static function missingSymbols(): array
    {
        return \array_values(\array_unique(static::$missing));
    }

    /**
     * Dossier de cache, cree au besoin.
     *
     * @return string
     */
    private static function cacheDirectory(): string
    {
        $dir = static::$cacheDirectory !== null
            ? static::$cacheDirectory
            : (\getcwd() . '/.openaip_symbols');

        if (! \is_dir($dir)) {
            @\mkdir($dir, 0777, true);
        }

        return $dir;
    }

    /**
     * SVG local d'un symbole, telecharge s'il manque.
     *
     * @param string $name
     * @return string|null
     */
    private static function svgPath(string $name)
    {
        // Le nom sert de composant de chemin : on n'accepte que ce que produit
        // le depot amont, pas de separateur ni de remontee de repertoire.
        if (! \preg_match('/^[A-Za-z0-9_.-]+$/', $name)) {
            static::$missing[] = $name;
            return null;
        }

        $svgPath = static::cacheDirectory() . '/' . $name . '.svg';

        if (\is_file($svgPath) && \filesize($svgPath) > 0) {
            return $svgPath;
        }

        if (! static::$allowDownload) {
            static::$missing[] = $name;
            return null;
        }

        $curl = \curl_init();
        \curl_setopt($curl, CURLOPT_URL, static::BASE_URL . \rawurlencode($name) . '.svg');
        \curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        \curl_setopt($curl, CURLOPT_TIMEOUT, static::$timeout);
        \curl_setopt($curl, CURLOPT_USERAGENT, 'php-osm-static-aero');

        $data = \curl_exec($curl);
        $code = \curl_getinfo($curl, CURLINFO_HTTP_CODE);
        \curl_close($curl);

        // Un 404 renvoie une page HTML : sans ce controle on ecrirait un faux
        // SVG en cache, que le rasteriseur refuserait a chaque rendu suivant.
        if ($code !== 200 || ! \is_string($data) || \strpos($data, '<svg') === false) {
            static::$missing[] = $name;
            return null;
        }

        \file_put_contents($svgPath, $data);

        return $svgPath;
    }

    /**
     * Convertit un SVG en PNG a hauteur fixe, fond transparent.
     *
     * @param string $svgPath
     * @param string $pngPath
     * @param int $height
     * @return bool
     */
    private static function rasterize(string $svgPath, string $pngPath, int $height): bool
    {
        $tool = static::rasterizer();

        if ($tool === null) {
            static::$missing[] = \basename($svgPath, '.svg');
            return false;
        }

        if ($tool['type'] === 'rsvg') {
            $command = \escapeshellarg($tool['bin'])
                . ' --keep-aspect-ratio -h ' . $height
                . ' -o ' . \escapeshellarg($pngPath)
                . ' ' . \escapeshellarg($svgPath);
        } else {
            // Densite volontairement haute : ImageMagick rasterise d'abord, puis
            // reduit. Partir trop bas donnerait un contour crenele.
            $command = \escapeshellarg($tool['bin'])
                . ' -background none -density 600 ' . \escapeshellarg($svgPath)
                . ' -resize x' . $height
                . ' ' . \escapeshellarg($pngPath);
        }

        \exec($command . ' 2>/dev/null', $output, $status);

        return $status === 0 && \is_file($pngPath) && \filesize($pngPath) > 0;
    }

    /**
     * Outil de rasterisation disponible sur la machine.
     *
     * rsvg-convert d'abord : c'est le moteur SVG que ImageMagick appelle
     * lui-meme en delegue, autant lui parler directement.
     *
     * @return array|null
     */
    private static function rasterizer()
    {
        if (static::$rasterizer !== null) {
            return static::$rasterizer === false ? null : static::$rasterizer;
        }

        foreach ([['rsvg-convert', 'rsvg'], ['magick', 'magick'], ['convert', 'magick']] as $candidate) {
            $path = \trim((string) \shell_exec('command -v ' . \escapeshellarg($candidate[0]) . ' 2>/dev/null'));

            if ($path !== '') {
                static::$rasterizer = ['bin' => $path, 'type' => $candidate[1]];
                return static::$rasterizer;
            }
        }

        static::$rasterizer = false;

        return null;
    }
}
