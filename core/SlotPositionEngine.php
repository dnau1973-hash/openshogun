<?php
/**
 * SlotPositionEngine - Gestion dynamique et calibration des coordonnées des emplacements (OpenShogun)
 * Permet à l'administrateur d'ajuster en Drag & Drop les positions (X, Y) sur les cartes
 * et persiste la configuration dans config/slot_positions.json.
 */

class SlotPositionEngine {
    private static string $filePath = __DIR__ . '/../config/slot_positions.json';

    /**
     * Rayon maximal de déplacement autorisé lors de la calibration (sécurité anti-dérive).
     * Empêche de déplacer un bâtiment au-delà de ±10% par rapport à son emplacement d'origine.
     */
    public const MAX_DELTA_PERCENT = 10.0;

    public static function getDefaults(string $view): array {
        if ($view === 'resources') {
            return [
                "1"  => [ "left" => 10.5, "top" => 31.5, "width" => 12.0, "height" => 16.0, "z" => 43 ],
                "2"  => [ "left" => 9.5,  "top" => 40.5, "width" => 12.0, "height" => 16.0, "z" => 52 ],
                "3"  => [ "left" => 11.5, "top" => 51.0, "width" => 12.0, "height" => 16.0, "z" => 62 ],
                "4"  => [ "left" => 19.5, "top" => 38.0, "width" => 12.0, "height" => 16.0, "z" => 49 ],
                "5"  => [ "left" => 23.5, "top" => 47.5, "width" => 12.0, "height" => 16.0, "z" => 59 ],
                "6"  => [ "left" => 20.8, "top" => 65.5, "width" => 12.0, "height" => 16.0, "z" => 77 ],
                "7"  => [ "left" => 28.5, "top" => 73.0, "width" => 12.0, "height" => 16.0, "z" => 85 ],
                "8"  => [ "left" => 30.0, "top" => 57.0, "width" => 12.0, "height" => 16.0, "z" => 68 ],
                "9"  => [ "left" => 38.5, "top" => 63.0, "width" => 12.0, "height" => 16.0, "z" => 74 ],
                "10" => [ "left" => 49.5, "top" => 61.5, "width" => 12.0, "height" => 16.0, "z" => 73 ],
                "11" => [ "left" => 59.5, "top" => 55.5, "width" => 12.0, "height" => 16.0, "z" => 67 ],
                "12" => [ "left" => 59.0, "top" => 75.5, "width" => 12.0, "height" => 16.0, "z" => 87 ],
                "13" => [ "left" => 68.0, "top" => 72.5, "width" => 12.0, "height" => 16.0, "z" => 84 ],
                "14" => [ "left" => 67.5, "top" => 46.0, "width" => 12.0, "height" => 16.0, "z" => 57 ],
                "15" => [ "left" => 71.5, "top" => 37.0, "width" => 12.0, "height" => 16.0, "z" => 48 ],
                "16" => [ "left" => 80.0, "top" => 32.0, "width" => 12.0, "height" => 16.0, "z" => 43 ],
                "17" => [ "left" => 72.0, "top" => 25.5, "width" => 12.0, "height" => 16.0, "z" => 37 ],
                "18" => [ "left" => 64.0, "top" => 18.5, "width" => 12.0, "height" => 16.0, "z" => 30 ],
                "19" => [ "left" => 55.5, "top" => 13.0, "width" => 12.0, "height" => 16.0, "z" => 24 ],
                "bunker-hq" => [ "left" => 41.5, "top" => 18.0, "width" => 19.0, "height" => 32.0, "z" => 42 ]
            ];
        }

        if ($view === 'city') {
            return [
                "19" => [ "left" => 51.6, "top" => -1.3, "width" => 27.6, "height" => 28.6, "z" => 15 ],
                "20" => [ "left" => 35.4, "top" => 12.0, "width" => 10.5, "height" => 18.2, "z" => 20 ],
                "21" => [ "left" => 45.4, "top" => 16.5, "width" => 10.9, "height" => 18.9, "z" => 22 ],
                "22" => [ "left" => 27.1, "top" => 21.1, "width" => 11.3, "height" => 19.5, "z" => 24 ],
                "23" => [ "left" => 38.5, "top" => 27.0, "width" => 11.6, "height" => 20.2, "z" => 26 ],
                "24" => [ "left" => 19.1, "top" => 30.2, "width" => 12.0, "height" => 20.8, "z" => 28 ],
                "25" => [ "left" => 32.0, "top" => 36.7, "width" => 12.4, "height" => 21.5, "z" => 30 ],
                "26" => [ "left" => 23.8, "top" => 47.5, "width" => 12.7, "height" => 22.1, "z" => 32 ],
                "27" => [ "left" => 61.0, "top" => 27.0, "width" => 11.6, "height" => 20.2, "z" => 26 ],
                "28" => [ "left" => 72.3, "top" => 32.2, "width" => 11.6, "height" => 20.2, "z" => 28 ],
                "29" => [ "left" => 54.3, "top" => 37.4, "width" => 12.0, "height" => 20.8, "z" => 30 ],
                "30" => [ "left" => 66.0, "top" => 43.2, "width" => 12.0, "height" => 20.8, "z" => 32 ],
                "31" => [ "left" => 48.0, "top" => 47.8, "width" => 12.4, "height" => 21.5, "z" => 34 ],
                "32" => [ "left" => 59.2, "top" => 54.3, "width" => 12.4, "height" => 21.5, "z" => 36 ],
                "33" => [ "left" => 41.2, "top" => 58.9, "width" => 12.7, "height" => 22.1, "z" => 38 ],
                "34" => [ "left" => 1.5,  "top" => 48.2, "width" => 11.6, "height" => 23.4, "z" => 45 ],
                "gateway" => [ "left" => 13.1, "top" => 76.8, "width" => 8.0, "height" => 12.4, "z" => 50 ]
            ];
        }

        return [];
    }

    public static function getAll(): array {
        $defaults = [
            'resources' => self::getDefaults('resources'),
            'city' => self::getDefaults('city')
        ];

        if (!file_exists(self::$filePath)) {
            return $defaults;
        }

        $content = @file_get_contents(self::$filePath);
        $data = json_decode($content, true);
        if (!is_array($data)) {
            return $defaults;
        }

        $result = [];
        foreach (['resources', 'city'] as $v) {
            $result[$v] = [];
            $vDefaults = $defaults[$v];
            $vData = is_array($data[$v] ?? null) ? $data[$v] : [];

            foreach ($vDefaults as $k => $def) {
                $keyStr = (string)$k;
                if (isset($vData[$keyStr]) && is_array($vData[$keyStr])) {
                    $result[$v][$keyStr] = array_merge($def, $vData[$keyStr]);
                } elseif (isset($vData[$k]) && is_array($vData[$k])) {
                    $result[$v][$keyStr] = array_merge($def, $vData[$k]);
                } else {
                    $result[$v][$keyStr] = $def;
                }
            }
        }

        return $result;
    }

    public static function getPositions(string $view): array {
        $all = self::getAll();
        return $all[$view] ?? self::getDefaults($view);
    }

    public static function savePositions(string $view, array $positions): bool {
        $all = self::getAll();
        $defaults = self::getDefaults($view);

        $sanitized = [];
        foreach ($defaults as $key => $def) {
            $keyStr = (string)$key;
            $p = null;
            if (isset($positions[$keyStr]) && is_array($positions[$keyStr])) {
                $p = $positions[$keyStr];
            } elseif (isset($positions[$key]) && is_array($positions[$key])) {
                $p = $positions[$key];
            }

            if ($p !== null) {
                $rawLeft = (float)($p['left'] ?? $def['left']);
                $rawTop  = (float)($p['top'] ?? $def['top']);

                // Sécurité anti-dérive : limitation du déplacement à ±MAX_DELTA_PERCENT par rapport à la position d'origine
                $minLeft = max(0.0, (float)$def['left'] - self::MAX_DELTA_PERCENT);
                $maxLeft = min(95.0, (float)$def['left'] + self::MAX_DELTA_PERCENT);
                $minTop  = max(0.0, (float)$def['top'] - self::MAX_DELTA_PERCENT);
                $maxTop  = min(95.0, (float)$def['top'] + self::MAX_DELTA_PERCENT);

                $clampedLeft = min($maxLeft, max($minLeft, $rawLeft));
                $clampedTop  = min($maxTop, max($minTop, $rawTop));

                $sanitized[$keyStr] = [
                    'left' => round($clampedLeft, 2),
                    'top' => round($clampedTop, 2),
                    'width' => round((float)($p['width'] ?? $def['width']), 2),
                    'height' => round((float)($p['height'] ?? $def['height']), 2),
                    'z' => (int)($p['z'] ?? $def['z'])
                ];
            } else {
                $sanitized[$keyStr] = $def;
            }
        }

        $all[$view] = $sanitized;

        $dir = dirname(self::$filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $res = @file_put_contents(
            self::$filePath,
            json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );

        if ($res !== false) {
            @chmod(self::$filePath, 0666);
            return true;
        }

        return false;
    }

    public static function resetPositions(string $view): bool {
        return self::savePositions($view, self::getDefaults($view));
    }

    /**
     * Génère les règles CSS de positionnement exact pour la vue spécifiée.
     */
    public static function renderCss(string $view): string {
        $positions = self::getPositions($view);
        $css = "<style id=\"rts-calibrated-positions\">\n";

        if ($view === 'resources') {
            foreach ($positions as $key => $pos) {
                if ($key === 'bunker-hq') {
                    $css .= ".hotspot-bunker-hq { left: {$pos['left']}% !important; top: {$pos['top']}% !important; width: {$pos['width']}% !important; height: {$pos['height']}% !important; z-index: {$pos['z']} !important; }\n";
                } else {
                    $css .= ".hotspot-slot-{$key} { left: {$pos['left']}% !important; top: {$pos['top']}% !important; width: {$pos['width']}% !important; height: {$pos['height']}% !important; z-index: {$pos['z']} !important; }\n";
                }
            }
        } elseif ($view === 'city') {
            foreach ($positions as $key => $pos) {
                if ($key === 'gateway') {
                    $css .= ".hotspot-city-slot-gateway { left: {$pos['left']}% !important; top: {$pos['top']}% !important; width: {$pos['width']}% !important; height: {$pos['height']}% !important; z-index: {$pos['z']} !important; }\n";
                } else {
                    $css .= ".hotspot-city-slot-{$key} { left: {$pos['left']}% !important; top: {$pos['top']}% !important; width: {$pos['width']}% !important; height: {$pos['height']}% !important; z-index: {$pos['z']} !important; }\n";
                }
            }
        }

        $css .= "</style>\n";
        return $css;
    }
}

