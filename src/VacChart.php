<?php

namespace Ycdev\OsmStaticAero;

/**
 * Ycdev\OsmStaticAero\VacChart fetches official French VAC charts from the SIA.
 *
 * Les cartes sont publiees dans l'eAIP, en acces libre et sans authentification :
 *   https://www.sia.aviation-civile.gouv.fr/media/dvd/eAIP_<CYCLE>/Atlas-VAC/PDF_AIPparSSection/VAC/AD/AD-2.<OACI>.pdf
 *
 * Le dossier de cycle change tous les 28 jours (cycle AIRAC) et les anciens sont
 * purges du serveur : le cycle courant est donc decouvert au vol, jamais code en
 * dur. Le cache disque est cloisonne par cycle, pour qu'un changement de cycle
 * force le retelechargement au lieu de resservir une carte perimee.
 *
 * Le rendu PNG s'appuie sur Ghostscript, avec ImageMagick en secours. Sans l'un
 * ni l'autre, download() reste utilisable et toPng() retourne null.
 *
 * Usage :
 *   $vac = new VacChart();
 *   $png = $vac->toPng('LFCT');
 *   if ($png === null) {
 *       echo $vac->getLastError();
 *   }
 *
 * @package Ycdev\OsmStaticAero
 */
class VacChart
{
    /**
     * Racine publique de l'eAIP.
     */
    const ROOT = 'https://www.sia.aviation-civile.gouv.fr/media/dvd';

    /**
     * Chemin d'une carte VAC a l'interieur d'un dossier de cycle.
     */
    const CHART_PATH = 'Atlas-VAC/PDF_AIPparSSection/VAC/AD';

    /**
     * Date AIRAC connue servant d'origine. Les cycles tombent tous les 28 jours
     * a partir de la, ce qui rend les dates calculables sans table.
     */
    const AIRAC_EPOCH = '2020-01-02';

    /**
     * @var string Repertoire de cache, un sous-dossier par cycle
     */
    public $cacheDirectory = '.vac_cache';

    /**
     * @var string Journal des echecs
     */
    public $logFile = 'vac-errors.log';

    /**
     * @var int Delai d'attente cURL, en secondes. Une VAC pese jusqu'a 1 Mo.
     */
    public $timeout = 30;

    /**
     * @var int Tentatives par carte avant abandon
     */
    public $maxAttempts = 3;

    /**
     * @var int Nombre de cycles sondes en remontant. Le SIA en garde deux en
     * ligne ; 4 laisse de la marge.
     */
    public $probedCycles = 4;

    /**
     * @var string|null Cycle decouvert, memorise pour la duree du script
     */
    private $cycle = null;

    /**
     * @var string|null Motif du dernier echec
     */
    private $lastError = null;

    /**
     * @param array $options Surcharge des proprietes publiques
     */
    public function __construct(array $options = [])
    {
        foreach ($options as $key => $value) {
            if (\property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Dernier motif d'echec, ou null si tout s'est bien passe.
     *
     * @return string|null
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Dates AIRAC les plus recentes, de la plus proche a la plus ancienne.
     *
     * @param int $count Nombre de dates a produire
     * @return \DateTimeImmutable[]
     */
    public function airacDates(int $count = 4): array
    {
        $epoch = new \DateTimeImmutable(self::AIRAC_EPOCH);
        $today = new \DateTimeImmutable('today');
        $days  = (int) $epoch->diff($today)->format('%a');
        $last  = $epoch->modify('+' . (\intdiv($days, 28) * 28) . ' days');

        $dates = [];
        for ($i = 0; $i < $count; $i++) {
            $dates[] = $last->modify('-' . ($i * 28) . ' days');
        }

        return $dates;
    }

    /**
     * Nom du dossier eAIP correspondant a une date AIRAC (ex. eAIP_09_JUL_2026).
     *
     * @param \DateTimeImmutable $date
     * @return string
     */
    public function cycleName(\DateTimeImmutable $date): string
    {
        return 'eAIP_' . \strtoupper($date->format('d_M_Y'));
    }

    /**
     * Cycle actuellement en ligne, decouvert en sondant les dates AIRAC en
     * remontant. Le resultat est memorise : un seul sondage par execution.
     *
     * @return string|null Nom du dossier, ou null si aucun cycle ne repond
     */
    public function getCycle()
    {
        if ($this->cycle !== null) {
            return $this->cycle;
        }

        foreach ($this->airacDates($this->probedCycles) as $date) {
            $name = $this->cycleName($date);
            if ($this->cycleExists($name)) {
                $this->cycle = $name;
                return $name;
            }
        }

        $this->lastError = 'Aucun cycle AIRAC en ligne parmi les ' . $this->probedCycles . ' derniers';
        $this->log('-', $this->lastError);

        return null;
    }

    /**
     * Force l'usage d'un cycle donne, en court-circuitant la decouverte.
     *
     * @param string $cycle Nom du dossier (ex. eAIP_09_JUL_2026)
     * @return $this Fluent interface
     */
    public function setCycle(string $cycle)
    {
        $this->cycle = $cycle;
        return $this;
    }

    /**
     * Url publique de la carte VAC d'un aerodrome.
     *
     * @param string $icao Code OACI (ex. LFCT)
     * @return string|null null si aucun cycle n'est disponible
     */
    public function url(string $icao)
    {
        $cycle = $this->getCycle();

        if ($cycle === null) {
            return null;
        }

        return self::ROOT . '/' . $cycle . '/' . self::CHART_PATH . '/AD-2.' . $this->normaliseIcao($icao) . '.pdf';
    }

    /**
     * Recupere la carte VAC et retourne le chemin du fichier local.
     *
     * Le fichier est mis en cache sous <cacheDirectory>/<cycle>/ : un appel
     * ulterieur dans le meme cycle ne refait aucune requete.
     *
     * @param string $icao Code OACI (ex. LFCT)
     * @param string|null $destination Chemin de copie supplementaire (optionnel)
     * @return string|null Chemin du PDF, ou null en cas d'echec
     */
    public function download(string $icao, string $destination = null)
    {
        $this->lastError = null;
        $icao            = $this->normaliseIcao($icao);

        if (!\preg_match('/^[A-Z]{4}$/', $icao)) {
            $this->lastError = "Code OACI invalide : $icao";
            return null;
        }

        $cycle = $this->getCycle();

        if ($cycle === null) {
            return null;
        }

        $directory = $this->cacheDirectory . '/' . $cycle;
        $file      = $directory . '/AD-2.' . $icao . '.pdf';

        if (!\is_file($file) || \filesize($file) === 0) {
            $data = $this->fetch($this->url($icao), $icao);

            if ($data === null) {
                return null;
            }

            if (!\is_dir($directory) && !\mkdir($directory, 0777, true) && !\is_dir($directory)) {
                $this->lastError = "Impossible de creer le repertoire de cache : $directory";
                return null;
            }

            \file_put_contents($file, $data);
        }

        if ($destination !== null && !\copy($file, $destination)) {
            $this->lastError = "Copie impossible vers : $destination";
            return null;
        }

        return $destination !== null ? $destination : $file;
    }

    /**
     * Recupere la carte VAC et retourne directement ses octets.
     *
     * @param string $icao Code OACI
     * @return string|null Contenu du PDF, ou null en cas d'echec
     */
    public function getPdf(string $icao)
    {
        $file = $this->download($icao);
        return $file === null ? null : \file_get_contents($file);
    }

    /**
     * Rend une page de la carte VAC en PNG.
     *
     * Les VAC sont des PDF vectoriels au format A5 : la page 1 porte la carte
     * d'atterrissage a vue, les suivantes les consignes et l'infrastructure.
     * Le PNG est mis en cache a cote du PDF, sous le meme cycle.
     *
     * @param string $icao Code OACI (ex. LFCT)
     * @param int $page Numero de page, 1 par defaut
     * @param int $dpi Resolution du rendu
     * @param string|null $destination Chemin de copie supplementaire (optionnel)
     * @return string|null Chemin du PNG, ou null en cas d'echec
     */
    public function toPng(string $icao, int $page = 1, int $dpi = 300, string $destination = null)
    {
        $this->lastError = null;

        $pdf = $this->download($icao);

        if ($pdf === null) {
            return null;
        }

        if ($page < 1) {
            $this->lastError = "Numero de page invalide : $page";
            return null;
        }

        $icao = $this->normaliseIcao($icao);
        $png  = \dirname($pdf) . '/AD-2.' . $icao . '_p' . $page . '_' . $dpi . 'dpi.png';

        if (!\is_file($png) || \filesize($png) === 0) {
            if (!$this->rasterize($pdf, $png, $page, $dpi)) {
                @\unlink($png);
                $this->log($icao, 'rendu PNG impossible : ' . $this->lastError, 1, $pdf);
                return null;
            }
        }

        if ($destination !== null && !\copy($png, $destination)) {
            $this->lastError = "Copie impossible vers : $destination";
            return null;
        }

        return $destination !== null ? $destination : $png;
    }

    /**
     * Nombre de pages d'une carte VAC.
     *
     * @param string $icao Code OACI
     * @return int|null null en cas d'echec
     */
    public function pageCount(string $icao)
    {
        $pdf = $this->download($icao);

        if ($pdf === null) {
            return null;
        }

        $magick = $this->binary('magick', 'convert');

        if ($magick === null) {
            $this->lastError = 'ImageMagick introuvable : comptage des pages impossible';
            return null;
        }

        \exec(\escapeshellarg($magick) . ' identify ' . \escapeshellarg($pdf) . ' 2>/dev/null', $output, $code);

        return ($code === 0 && $output) ? \count($output) : null;
    }

    /**
     * Recupere plusieurs cartes d'un coup.
     *
     * @param string[] $icaos Codes OACI
     * @return array<string, string|null> Code OACI => chemin du PDF ou null
     */
    public function downloadAll(array $icaos): array
    {
        $results = [];

        foreach ($icaos as $icao) {
            $results[$this->normaliseIcao($icao)] = $this->download($icao);
        }

        return $results;
    }

    /**
     * Supprime les cartes des cycles autres que le cycle courant.
     *
     * Une VAC perimee qui traine dans un cache est une carte qu'on finit par
     * imprimer : mieux vaut ne pas la garder.
     *
     * @return int Nombre de fichiers supprimes
     */
    public function purgeOldCycles(): int
    {
        $cycle = $this->getCycle();

        if ($cycle === null || !\is_dir($this->cacheDirectory)) {
            return 0;
        }

        $removed = 0;

        foreach (\glob($this->cacheDirectory . '/eAIP_*', GLOB_ONLYDIR) as $directory) {
            if (\basename($directory) === $cycle) {
                continue;
            }

            // Tout le contenu, pas seulement les PDF : les PNG rendus par
            // toPng() vivent dans le meme dossier de cycle.
            foreach (\glob($directory . '/*') as $file) {
                if (\is_file($file) && \unlink($file)) {
                    ++$removed;
                }
            }

            @\rmdir($directory);
        }

        return $removed;
    }

    /**
     * Met un code OACI en forme.
     *
     * @param string $icao
     * @return string
     */
    private function normaliseIcao(string $icao): string
    {
        return \strtoupper(\trim($icao));
    }

    /**
     * Verifie qu'un dossier de cycle est en ligne.
     *
     * @param string $cycle Nom du dossier
     * @return bool
     */
    private function cycleExists(string $cycle): bool
    {
        $curl = \curl_init();
        \curl_setopt_array($curl, [
            CURLOPT_URL            => self::ROOT . '/' . $cycle . '/Atlas-VAC/home.htm',
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_USERAGENT      => 'php-osm-static-aero (VacChart)',
        ]);
        \curl_exec($curl);
        $code = (int) \curl_getinfo($curl, CURLINFO_HTTP_CODE);
        unset($curl);

        return $code === 200;
    }

    /**
     * Telecharge une url, avec reessais et controle du contenu.
     *
     * @param string $url
     * @param string $icao Pour le journal
     * @return string|null Octets du PDF, ou null
     */
    private function fetch(string $url, string $icao)
    {
        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            $curl = \curl_init();
            \curl_setopt_array($curl, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_USERAGENT      => 'php-osm-static-aero (VacChart)',
            ]);

            $body  = \curl_exec($curl);
            $errno = \curl_errno($curl);
            $error = \curl_error($curl);
            $code  = (int) \curl_getinfo($curl, CURLINFO_HTTP_CODE);
            // Depuis PHP 8.0 un CurlHandle est libere par le compteur de
            // references : curl_close() n'a plus d'effet, et est deprecie en 8.5.
            unset($curl);

            // Un 404 signifie que le terrain n'a pas de VAC publiee : insister
            // ne servirait a rien.
            if ($errno !== 0) {
                $reason    = "cURL #$errno $error";
                $retryable = true;
            } elseif ($code === 404) {
                $reason    = 'HTTP 404, pas de carte VAC publiee pour ce terrain';
                $retryable = false;
            } elseif ($code !== 200) {
                $reason    = "HTTP $code";
                $retryable = $code >= 500 || $code === 429;
            } elseif (\strncmp((string) $body, '%PDF', 4) !== 0) {
                // Une page d'erreur HTML servie en 200 ne doit pas finir en
                // cache sous un nom de PDF.
                $reason    = 'reponse non PDF (' . \strlen((string) $body) . ' octets)';
                $retryable = true;
            } else {
                return $body;
            }

            if (!$retryable || $attempt >= $this->maxAttempts) {
                $this->lastError = "$icao : $reason" . ($attempt > 1 ? " apres $attempt essais" : '');
                $this->log($icao, $reason, $attempt, $url);
                return null;
            }

            \usleep(500000 * (1 << ($attempt - 1)));
        }

        return null;
    }

    /**
     * Rend une page de PDF en PNG, via Ghostscript puis ImageMagick en secours.
     *
     * @param string $pdf Chemin du PDF source
     * @param string $png Chemin du PNG a produire
     * @param int $page Numero de page
     * @param int $dpi Resolution
     * @return bool
     */
    private function rasterize(string $pdf, string $png, int $page, int $dpi): bool
    {
        // Ghostscript d'abord : c'est le moteur qu'ImageMagick appellerait de
        // toute facon, il selectionne la page sans rendre les autres, et il
        // echappe aux politiques de securite qui bloquent souvent PDF sous
        // ImageMagick. Les *AlphaBits lissent traits et textes, indispensable
        // sur une carte.
        $gs = $this->binary('gs');

        if ($gs !== null) {
            $command = \escapeshellarg($gs)
            . ' -q -dNOPAUSE -dBATCH -dSAFER'
            . ' -sDEVICE=png16m -dTextAlphaBits=4 -dGraphicsAlphaBits=4'
            . ' -r' . (int) $dpi
            . ' -dFirstPage=' . (int) $page . ' -dLastPage=' . (int) $page
            . ' -sOutputFile=' . \escapeshellarg($png)
            . ' ' . \escapeshellarg($pdf) . ' 2>&1';

            \exec($command, $output, $code);

            if ($code === 0 && \is_file($png) && \filesize($png) > 0) {
                return true;
            }

            $this->lastError = 'ghostscript code ' . $code . ' ' . \implode(' | ', \array_slice($output, 0, 3));
        }

        $magick = $this->binary('magick', 'convert');

        if ($magick !== null) {
            // Les pages sont indexees a partir de 0 dans la syntaxe fichier[n].
            $command = \escapeshellarg($magick)
            . ' -density ' . (int) $dpi
            . ' ' . \escapeshellarg($pdf . '[' . ($page - 1) . ']')
            . ' -background white -alpha remove -alpha off'
            . ' ' . \escapeshellarg($png) . ' 2>&1';

            \exec($command, $magickOutput, $magickCode);

            if ($magickCode === 0 && \is_file($png) && \filesize($png) > 0) {
                return true;
            }

            $this->lastError = 'imagemagick code ' . $magickCode . ' ' . \implode(' | ', \array_slice($magickOutput, 0, 3));
        }

        if ($this->lastError === null) {
            $this->lastError = 'ni ghostscript ni imagemagick disponibles';
        }

        return false;
    }

    /**
     * Localise le premier binaire disponible parmi ceux proposes.
     *
     * @param string ...$names
     * @return string|null
     */
    private function binary(string ...$names)
    {
        foreach ($names as $name) {
            $path = \trim((string) \shell_exec('command -v ' . \escapeshellarg($name) . ' 2>/dev/null'));

            if ($path !== '') {
                return $path;
            }
        }

        return null;
    }

    /**
     * Journalise un echec.
     *
     * @param string $icao
     * @param string $reason
     * @param int $attempts
     * @param string $url
     * @return void
     */
    private function log(string $icao, string $reason, int $attempts = 1, string $url = '')
    {
        $line = \sprintf(
            "[%s] %-6s %-52s %s%s\n",
            \date('Y-m-d H:i:s'),
            $icao,
            $reason . ($attempts > 1 ? " ($attempts essais)" : ''),
            $this->cycle !== null ? $this->cycle : '-',
            $url !== '' ? '  ' . $url : ''
        );

        \file_put_contents($this->logFile, $line, FILE_APPEND);

        if (\php_sapi_name() === 'cli') {
            \fwrite(STDERR, 'VAC : ' . \trim($line) . "\n");
        }
    }
}
