<?php

class OfferRenderer {
    private static $richTemplate = null;
    private static $stdTemplate = null;
    private const CDN_BASE = "http://cdn.localhost:8080/assets/templates/";

    /**
     * Renders an offer card.
     * @param array $offer Raw data from DB
     * @param string $format 'rich' or 'std'
     */
    public static function render(array $offer, string $format = 'rich'): string {
        $template = self::fetchTemplate($format);

        // If template fetch fails, return a basic fallback so the page isn't empty
        if (!$template) {
            return "<div class='card'><h4>" . htmlspecialchars($offer['position']) . "</h4><p>Template error.</p></div>";
        }

        $placeholders = self::getPlaceholders($offer);
        return strtr($template, $placeholders);
    }

    private static function fetchTemplate(string $format): ?string {
        if ($format === 'rich') {
            if (self::$richTemplate === null) {
                self::$richTemplate = @file_get_contents(self::CDN_BASE . "rich-template.html");
            }
            return self::$richTemplate;
        } else {
            if (self::$stdTemplate === null) {
                self::$stdTemplate = @file_get_contents(self::CDN_BASE . "card_template.html");
            }
            return self::$stdTemplate;
        }
    }

    private static function getPlaceholders(array $offer): array {
        // Build skills HTML list
        $skillsHtml = '';
        $skills = OfferHelper::parseSkills($offer['required_skills'] ?? '');
        foreach ($skills as $skill) {
            $skillsHtml .= '<span class="skill-tag">' . htmlspecialchars($skill) . '</span>';
        }

        return [
            '{{ID}}'              => $offer['id'] ?? 0,
            '{{POSITION}}'        => htmlspecialchars($offer['position'] ?? 'Titre non spécifié'),
            '{{COMPANY_NAME}}'    => htmlspecialchars($offer['company_name'] ?? 'Entreprise'),
            '{{LOCATION}}'        => htmlspecialchars($offer['location'] ?? 'N/A'),
            '{{DATE}}'            => isset($offer['created_at']) ? date('d/m/Y', strtotime($offer['created_at'])) : '',
            '{{DESCRIPTION}}'     => htmlspecialchars(substr($offer['description'] ?? '', 0, 160)) . '...',
            '{{TAG_CLASS}}'       => OfferHelper::getStateClass($offer['state'] ?? ''),
            '{{FORMATTED_STATE}}' => OfferHelper::formatState($offer['state'] ?? ''),
            '{{JOB_TYPE}}'        => OfferHelper::formatJobType($offer['job_type'] ?? ''),
            '{{REMOTE_TYPE}}'     => ucfirst($offer['remote_type'] ?? 'on-site'),
            '{{EXP_LEVEL}}'       => ucfirst($offer['experience_level'] ?? 'N/A'),
            '{{SALARY}}'          => OfferHelper::formatSalary($offer['salary_min'] ?? 0, $offer['salary_max'] ?? 0, $offer['salary_currency'] ?? 'EUR'),
            '{{SKILLS_HTML}}'     => $skillsHtml
        ];
    }
}