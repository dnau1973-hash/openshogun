<?php
/**
 * SlotPositionEngine - Gestion dynamique et calibration des coordonnées des emplacements (OpenShogun)
 * Permet à l'administrateur d'ajuster en Drag & Drop les positions (X, Y) sur les cartes
 * et persiste la configuration dans config/slot_positions.json.
 */

class SlotPositionEngine {
    private static string $filePath = __DIR__ . '/../config/slot_positions.json';

    public static function getDefaults(string $view): array {
        if ($view === 'resources') {
            return [
                "1"  => [ "left" => 12.0, "top" => 30.1, "width" => 11.0, "height" => 18.6, "z" => 7 ],
                "2"  => [ "left" => 9.5,  "top" => 40.1, "width" => 11.0, "height" => 18.6, "z" => 8 ],
                "3"  => [ "left" => 11.5, "top" => 50.1, "width" => 11.0, "height" => 18.6, "z" => 9 ],
                "4"  => [ "left" => 20.5, "top" => 36.1, "width" => 11.0, "height" => 18.6, "z" => 8 ],
                "5"  => [ "left" => 24.5, "top" => 45.1, "width" => 11.0, "height" => 18.6, "z" => 9 ],
                "6"  => [ "left" => 21.5, "top" => 64.1, "width" => 11.0, "height" => 18.6, "z" => 12 ],
                "7"  => [ "left" => 29.0, "top" => 71.1, "width" => 11.0, "height" => 18.6, "z" => 13 ],
                "8"  => [ "left" => 31.5, "top" => 55.6, "width" => 11.0, "height" => 18.6, "z" => 10 ],
                "9"  => [ "left" => 39.5, "top" => 60.1, "width" => 11.0, "height" => 18.6, "z" => 11 ],
                "10" => [ "left" => 49.5, "top" => 58.6, "width" => 11.0, "height" => 18.6, "z" => 11 ],
                "11" => [ "left" => 60.5, "top" => 53.1, "width" => 11.0, "height" => 18.6, "z" => 10 ],
                "12" => [ "left" => 59.5, "top" => 73.1, "width" => 11.0, "height" => 18.6, "z" => 13 ],
                "13" => [ "left" => 68.5, "top" => 45.1, "width" => 11.0, "height" => 18.6, "z" => 9 ],
                "14" => [ "left" => 73.0, "top" => 36.1, "width" => 11.0, "height" => 18.6, "z" => 8 ],
                "15" => [ "left" => 80.5, "top" => 30.6, "width" => 11.0, "height" => 18.6, "z" => 7 ],
                "16" => [ "left" => 73.0, "top" => 25.1, "width" => 11.0, "height" => 18.6, "z" => 6 ],
                "17" => [ "left" => 64.5, "top" => 17.6, "width" => 11.0, "height" => 18.6, "z" => 5 ],
                "18" => [ "left" => 56.5, "top" => 10.6, "width" => 11.0, "height" => 18.6, "z" => 4 ],
                "bunker-hq" => [ "left" => 43.5, "top" => 16.0, "width" => 19.0, "height" => 34.0, "z" => 7 ]
            ];
        }

        if ($view === 'city') {
            return [
                "19" => [ "left" => 52.5, "top" => 2.4,  "width" => 18.0, "height" => 27.0, "z" => 10 ],
                "20" => [ "left" => 36.2, "top" => 11.4, "width" => 12.5, "height" => 20.0, "z" => 6 ],
                "21" => [ "left" => 27.0, "top" => 19.1, "width" => 12.0, "height" => 21.0, "z" => 7 ],
                "22" => [ "left" => 19.0, "top" => 25.9, "width" => 13.0, "height" => 20.0, "z" => 8 ],
                "23" => [ "left" => 11.5, "top" => 34.1, "width" => 14.0, "height" => 21.0, "z" => 9 ],
                "24" => [ "left" => 19.5, "top" => 44.2, "width" => 13.0, "height" => 19.0, "z" => 11 ],
                "25" => [ "left" => 28.0, "top" => 34.1, "width" => 13.0, "height" => 21.0, "z" => 10 ],
                "26" => [ "left" => 47.2, "top" => 44.3, "width" => 11.5, "height" => 24.0, "z" => 12 ],
                "27" => [ "left" => 37.2, "top" => 59.7, "width" => 12.5, "height" => 19.0, "z" => 14 ],
                "28" => [ "left" => 47.0, "top" => 64.1, "width" => 13.0, "height" => 21.0, "z" => 15 ],
                "29" => [ "left" => 56.2, "top" => 59.0, "width" => 11.5, "height" => 18.0, "z" => 14 ],
                "30" => [ "left" => 62.2, "top" => 52.5, "width" => 11.5, "height" => 18.0, "z" => 13 ],
                "31" => [ "left" => 59.8, "top" => 37.0, "width" => 11.5, "height" => 18.0, "z" => 10 ],
                "32" => [ "left" => 69.8, "top" => 44.5, "width" => 11.5, "height" => 18.0, "z" => 12 ],
                "33" => [ "left" => 73.8, "top" => 34.5, "width" => 11.5, "height" => 18.0, "z" => 10 ],
                "34" => [ "left" => 17.5, "top" => 56.5, "width" => 13.5, "height" => 20.0, "z" => 15 ],
                "gateway" => [ "left" => 12.0, "top" => 68.5, "width" => 7.5, "height" => 12.0, "z" => 16 ]
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
                $sanitized[$keyStr] = [
                    'left' => round((float)($p['left'] ?? $def['left']), 2),
                    'top' => round((float)($p['top'] ?? $def['top']), 2),
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

