<?php
/**
 * Page de Mode Maintenance - OpenShogun
 * Fichier : views/maintenance.php
 */
require_once __DIR__ . '/../core/LiveOpsEngine.php';
$liveOps = new LiveOpsEngine();
$status = $liveOps->getMaintenanceStatus();
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conseil de Guerre du Shōgunat &bull; Maintenance du Royaume</title>
    <link rel="stylesheet" href="/public/css/tabler/tabler.min.css?v=1.0.0-beta21">
    <link rel="stylesheet" href="/public/fontawesome/css/all.min.css">
    <style>
        body {
            background: radial-gradient(circle at center, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .maintenance-card {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            max-width: 580px;
            width: 100%;
            overflow: hidden;
        }
        .torii-icon {
            font-size: 3.5rem;
            color: #ef4444;
            animation: pulse-glow 3s infinite ease-in-out;
        }
        @keyframes pulse-glow {
            0%, 100% { filter: drop-shadow(0 0 10px rgba(239, 68, 68, 0.4)); transform: scale(1); }
            50% { filter: drop-shadow(0 0 25px rgba(239, 68, 68, 0.8)); transform: scale(1.04); }
        }
        .font-game {
            font-family: Georgia, "Times New Roman", serif;
            letter-spacing: 0.05em;
        }
    </style>
</head>
<body>
    <div class="container p-3">
        <div class="maintenance-card mx-auto text-center p-4 p-md-5">
            <div class="mb-4">
                <i class="fa-solid fa-torii-gate torii-icon"></i>
            </div>
            
            <h1 class="h2 font-game fw-bold text-white mb-2">Le Conseil de Guerre Délibère</h1>
            <div class="text-danger small text-uppercase fw-bold letter-spacing-1 mb-4">
                <i class="fa-solid fa-shield-halved me-1"></i>Trêve &amp; Consolidation des Domaines Féodaux
            </div>

            <div class="p-3 rounded mb-4 text-start border border-secondary" style="background: rgba(30, 41, 59, 0.6);">
                <div class="d-flex align-items-start gap-2">
                    <i class="fa-solid fa-scroll text-warning mt-1"></i>
                    <div class="text-slate-200 small" style="line-height: 1.5;">
                        « <?= htmlspecialchars($status['message'] ?? 'Le Shōgunat procède à des manœuvres de maintenance et consolidation des forteresses. Réouverture imminente.') ?> »
                    </div>
                </div>
            </div>

            <?php if (!empty($status['scheduled_end'])): ?>
                <div class="mb-4">
                    <span class="badge bg-warning text-dark py-2 px-3 fw-bold font-monospace fs-4">
                        <i class="fa-solid fa-hourglass-half me-1"></i>Réouverture estimée : <?= htmlspecialchars($status['scheduled_end']) ?>
                    </span>
                </div>
            <?php endif; ?>

            <p class="text-secondary small mb-4">
                Vos fiefs, réserves de riz, convois de transport et garnisons sont protégés par le décret impérial durant cette interruption de service.
            </p>

            <div class="d-flex justify-content-center gap-2">
                <button type="button" onclick="location.reload()" class="btn btn-outline-light btn-lg px-4">
                    <i class="fa-solid fa-arrows-rotate me-2"></i>Vérifier la Réouverture
                </button>
                <a href="/?action=logout" class="btn btn-ghost-secondary btn-lg">
                    Déconnexion
                </a>
            </div>

            <div class="mt-4 pt-3 border-top border-secondary small text-muted">
                OpenShogun &bull; Direction Live Ops &amp; Shōgunat
            </div>
        </div>
    </div>
</body>
</html>

