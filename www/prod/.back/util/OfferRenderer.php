<?php
// .back/util/OfferRenderer.php

class OfferRenderer
{
    // Variable statique pour "mettre en cache" le template pendant l'exécution
    private static $cachedTemplate = null;

    private static function getTemplate()
    {
        if (self::$cachedTemplate !== null) {
            return self::$cachedTemplate;
        }

        // ARBORESCENCE :
        // 1. __DIR__ = /www/prod/.back/util
        // 2. /..    = /www/prod/.back
        // 3. /../../ = /www/prod
        // 4. /../../../ = /www/ (Racine commune)

        $path = __DIR__ . '/../../../cdn/assets/elements/card_template.html';

        if (file_exists($path)) {
            self::$cachedTemplate = file_get_contents($path);
        } else {
            // Log pour debug si le fichier est encore introuvable
            error_log("Template manquant au chemin : " . $path);

            self::$cachedTemplate = "
            <div class='card'>
                <h3>{{STAGE_NAME}} (Template fallback)</h3>
                <p>{{STAGE_COMPANY}}</p>
                <a href='{{DETAIL_URL}}'>Détails</a>
            </div>";
        }

        return self::$cachedTemplate;
    }

    /**
     * Génère le HTML d'une carte d'offre
     */
    public static function render($offer): string
    {
        $template = self::getTemplate();

        // On construit l'URL absolue pour éviter les problèmes de dossiers (offres/offres/...)
        $baseUrl = defined('SITE_URL') ? SITE_URL : '';
        $detailUrl = $baseUrl . "/offres/offer_detail.php?id=" . $offer['id'];

        // Préparation des données de remplacement
        $replacements = [
            '{{DETAIL_URL}}' => $detailUrl,
            '{{STAGE_ID}}' => $offer['id'],
            '{{STAGE_NAME}}' => htmlspecialchars($offer['position'] ?? $offer['title'] ?? 'Sans titre'),
            '{{STAGE_COMPANY}}' => htmlspecialchars($offer['company_name'] ?? 'Entreprise inconnue'),
            '{{STAGE_POSITION}}' => htmlspecialchars($offer['location'] ?? 'N/C'),
            '{{STAGE_DESC}}' => mb_strimwidth(htmlspecialchars($offer['description'] ?? ''), 0, 150, "..."),
            '{{STAGE_DATE}}' => isset($offer['created_at']) ? date('d/m/Y', strtotime($offer['created_at'])) : '--/--/----',
            '{{STAGE_STATUS}}' => strtoupper($offer['state'] ?? 'UNKNOWN'),
            '{{STAGE_TAG_CLASS}}' => OfferHelper::getStateClass($offer['state'] ?? 'draft'),
            '{{STAGE_TYPE}}' => OfferHelper::formatJobType($offer['job_type'] ?? 'full-time'),
            '{{STAGE_SALARY}}' => OfferHelper::formatSalary($offer['salary_min'] ?? null, $offer['salary_max'] ?? null)
        ];

        return strtr($template, $replacements);
    }
}