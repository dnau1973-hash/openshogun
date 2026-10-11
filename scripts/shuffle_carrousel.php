<?php
/**
 * Script de renommage aléatoire des images du carrousel
 * Format de nommage : carrousel_XX.jpg
 */

$dir = __DIR__ . '/../public/assets/carrousel';
if (!is_dir($dir)) {
    die("Dossier introuvable : $dir\n");
}

$files = glob($dir . '/*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE);
if (empty($files)) {
    die("Aucune image trouvée dans $dir\n");
}

$total = count($files);
echo "Trouvé {$total} images à mélanger et renommer.\n";

// Mélange aléatoire
shuffle($files);

// Étape 1 : Renommage intermédiaire temporaire pour éviter toute collision
$tempFiles = [];
foreach ($files as $i => $file) {
    $tempName = $dir . '/temp_shuf_' . bin2hex(random_bytes(4)) . '_' . ($i + 1) . '.tmp';
    if (!rename($file, $tempName)) {
        die("Erreur de renommage temporaire pour : $file\n");
    }
    $tempFiles[] = $tempName;
}

// Étape 2 : Renommage définitif selon la nomenclature carrousel_XX.jpg
$padLen = ($total >= 100) ? 3 : 2;
foreach ($tempFiles as $i => $tempFile) {
    $num = $i + 1;
    $numStr = sprintf('%0' . $padLen . 'd', $num);
    $finalName = $dir . '/carrousel_' . $numStr . '.jpg';
    if (!rename($tempFile, $finalName)) {
        die("Erreur de renommage définitif vers : $finalName\n");
    }
    echo "[$num / $total] -> carrousel_{$numStr}.jpg\n";
}

echo "Succès : {$total} images mélangées et renommées avec la nomenclature carrousel_XX.jpg !\n";

