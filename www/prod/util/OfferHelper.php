<?php

class OfferHelper {
    /**
     * Format salary range for display
     */
    public static function formatSalary($min, $max, $currency = 'EUR') {
        if (empty($min) && empty($max)) {
            return 'Non spécifié';
        }

        if ($min === $max) {
            return number_format($min, 0, ',', ' ') . ' ' . $currency;
        }

        return number_format($min, 0, ',', ' ') . ' - ' .
            number_format($max, 0, ',', ' ') . ' ' . $currency;
    }

    /**
     * Parse JSON skills to array
     */
    public static function parseSkills($jsonSkills) {
        if (empty($jsonSkills)) {
            return [];
        }

        $skills = json_decode($jsonSkills, true);
        return is_array($skills) ? $skills : [];
    }

    /**
     * Get CSS class for state badge
     */
    public static function getStateClass($state) {
        $classes = [
            'open'      => 'tag-success',
            'pending'   => 'tag-warning',
            'draft'     => 'tag-secondary',
            'closed'    => 'tag-danger',
            'archived'  => 'tag-light'
        ];

        return $classes[$state] ?? 'tag-default';
    }

    /**
     * Format state for display
     */
    public static function formatState($state) {
        $states = [
            'open'      => 'Ouverte',
            'pending'   => 'En attente',
            'draft'     => 'Brouillon',
            'closed'    => 'Fermée',
            'archived'  => 'Archivée'
        ];

        return $states[$state] ?? $state;
    }

    /**
     * Format job type for display
     */
    public static function formatJobType($jobType) {
        $types = [
            'full-time'   => 'Temps plein',
            'part-time'   => 'Temps partiel',
            'contract'    => 'Contrat',
            'temporary'   => 'Temporaire',
            'internship'  => 'Stage',
            'volunteer'   => 'Bénévolat'
        ];

        return $types[$jobType] ?? $jobType;
    }
}