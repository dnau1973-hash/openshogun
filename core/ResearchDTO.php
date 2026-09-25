<?php
declare(strict_types=1);

/**
 * Data Transfer Object pour une Technologie Féodale (Recherche)
 * Standard PHP 8.2+ typé et sécurisé
 */
class ResearchDTO {
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly string $description,
        public readonly int $currentLevel,
        public readonly int $nextLevel,
        public readonly int $costMetal,
        public readonly int $costCrystal,
        public readonly int $costDeuterium,
        public readonly int $duration,
        public readonly bool $canResearch,
        public readonly string $category,
        public readonly string $categoryLabel,
        public readonly string $icon,
        public readonly bool $canAfford,
        public readonly int $missingMetal = 0,
        public readonly int $missingCrystal = 0,
        public readonly int $missingDeuterium = 0
    ) {}

    /**
     * Formate la durée en chaîne HH:MM:SS avec divisions entières strictes
     * Élimine l'erreur PHP 'Implicit conversion from float to int loses precision'
     */
    public function getFormattedDuration(): string {
        $hours = intdiv($this->duration, 3600);
        $minutes = intdiv($this->duration % 3600, 60);
        $seconds = $this->duration % 60;
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * Formate la durée en format lisible (ex: 1h 20m 15s)
     */
    public function getHumanDuration(): string {
        $hours = intdiv($this->duration, 3600);
        $minutes = intdiv($this->duration % 3600, 60);
        $seconds = $this->duration % 60;

        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . 'h';
        }
        if ($minutes > 0) {
            $parts[] = $minutes . 'm';
        }
        if ($seconds > 0 || empty($parts)) {
            $parts[] = $seconds . 's';
        }

        return implode(' ', $parts);
    }

    /**
     * Coût total combiné de la recherche
     */
    public function getTotalCost(): int {
        return $this->costMetal + $this->costCrystal + $this->costDeuterium;
    }
}
