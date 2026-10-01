<?php
/**
 * MailService — Service de Messagerie Transactionnelle & Transporteur SMTP/Local
 * Architecture sans dépendance externe (PHP natif avec stream sockets RFC 5321 & fonction mail)
 */
declare(strict_types=1);

require_once __DIR__ . '/GameConfig.php';
require_once __DIR__ . '/Database.php';

class MailService {
    private static ?self $instance = null;
    private const CRYPTO_CIPHER = 'aes-256-cbc';

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Clé de chiffrement interne pour le mot de passe SMTP
     */
    private function getEncryptionKey(): string {
        $keySource = defined('DB_PASS') ? DB_PASS : 'OpenShogunSecretKey2026';
        return hash('sha256', $keySource, true);
    }

    /**
     * Chiffre le mot de passe SMTP avant stockage
     */
    public function encryptPassword(string $plain): string {
        if ($plain === '') return '';
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($plain, self::CRYPTO_CIPHER, $this->getEncryptionKey(), 0, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }

    /**
     * Déchiffre le mot de passe SMTP pour la connexion
     */
    public function decryptPassword(string $encoded): string {
        if ($encoded === '') return '';
        $raw = base64_decode($encoded, true);
        if ($raw === false || !str_contains($raw, '::')) {
            return $encoded; // Fallback texte clair pour rétro-compatibilité
        }
        [$iv, $encrypted] = explode('::', $raw, 2);
        if (strlen($iv) !== 16) return $encoded;
        $decrypted = openssl_decrypt($encrypted, self::CRYPTO_CIPHER, $this->getEncryptionKey(), 0, $iv);
        return $decrypted !== false ? $decrypted : '';
    }

    /**
     * Charge la configuration complète du service de messagerie
     */
    public function getConfig(): array {
        $settings = GameConfig::load();
        $storedPass = (string)($settings['mail_password'] ?? '');
        $decryptedPass = $this->decryptPassword($storedPass);

        return [
            'driver'               => (string)($settings['mail_driver'] ?? 'mail'), // 'smtp' ou 'mail'
            'host'                 => (string)($settings['mail_host'] ?? '127.0.0.1'),
            'port'                 => (int)($settings['mail_port'] ?? 587),
            'encryption'           => (string)($settings['mail_encryption'] ?? 'tls'), // 'tls', 'ssl', 'none'
            'username'             => (string)($settings['mail_username'] ?? ''),
            'password'             => $decryptedPass,
            'password_masked'      => $decryptedPass !== '' ? '••••••••' : '',
            'from_address'         => (string)($settings['mail_from_address'] ?? 'noreply@openshogun.local'),
            'from_name'            => (string)($settings['mail_from_name'] ?? 'OpenShogun — Le Shōgunat'),
            'require_verification' => (bool)($settings['mail_require_verification'] ?? true),
        ];
    }

    /**
     * Enregistre les paramètres du service mail
     */
    public function saveConfig(array $data): bool {
        $driver = in_array($data['driver'] ?? '', ['smtp', 'mail'], true) ? $data['driver'] : 'mail';
        $host = trim((string)($data['host'] ?? '127.0.0.1'));
        $port = max(1, min(65535, (int)($data['port'] ?? 587)));
        $encryption = in_array($data['encryption'] ?? '', ['tls', 'ssl', 'none'], true) ? $data['encryption'] : 'tls';
        $username = trim((string)($data['username'] ?? ''));
        $fromAddress = trim((string)($data['from_address'] ?? 'noreply@openshogun.local'));
        $fromName = trim((string)($data['from_name'] ?? 'OpenShogun — Le Shōgunat'));
        $requireVerif = !empty($data['require_verification']);

        GameConfig::set('mail_driver', $driver);
        GameConfig::set('mail_host', $host);
        GameConfig::set('mail_port', $port);
        GameConfig::set('mail_encryption', $encryption);
        GameConfig::set('mail_username', $username);
        GameConfig::set('mail_from_address', $fromAddress);
        GameConfig::set('mail_from_name', $fromName);
        GameConfig::set('mail_require_verification', $requireVerif);

        // Mise à jour du mot de passe uniquement si renseigné
        if (isset($data['password']) && $data['password'] !== '' && $data['password'] !== '••••••••') {
            $encrypted = $this->encryptPassword((string)$data['password']);
            GameConfig::set('mail_password', $encrypted);
        }

        return true;
    }

    /**
     * Envoie un e-mail via le transporteur configuré (SMTP ou local mail())
     */
    public function sendEmail(string $to, string $subject, string $htmlBody, string $textBody = ''): array {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => "Adresse de destination invalide : {$to}"];
        }

        $config = $this->getConfig();

        if ($textBody === '') {
            $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));
        }

        if ($config['driver'] === 'smtp') {
            return $this->sendSmtp($to, $subject, $htmlBody, $textBody, $config);
        }

        return $this->sendLocalMail($to, $subject, $htmlBody, $textBody, $config);
    }

    /**
     * Alias ergonomique de sendEmail()
     */
    public function send(string $to, string $subject, string $htmlBody, string $textBody = ''): array {
        return $this->sendEmail($to, $subject, $htmlBody, $textBody);
    }

    /**
     * Envoi via la fonction mail() native de PHP avec headers multipart/alternative
     */
    private function sendLocalMail(string $to, string $subject, string $htmlBody, string $textBody, array $config): array {
        $boundary = '----=_Part_' . md5((string)microtime(true)) . '_' . bin2hex(random_bytes(8));

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'From: ' . $this->formatHeaderAddress($config['from_name'], $config['from_address']);
        $headers[] = 'Reply-To: ' . $config['from_address'];
        $headers[] = 'X-Mailer: OpenShogun Mailer / PHP ' . phpversion();
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $textBody . "\r\n\r\n";

        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $htmlBody . "\r\n\r\n";

        $body .= "--{$boundary}--\r\n";

        $headerStr = implode("\r\n", $headers);
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $sent = @mail($to, $encodedSubject, $body, $headerStr);
        if (!$sent) {
            $lastErr = error_get_last();
            $msg = $lastErr['message'] ?? 'Échec lors de l\'appel à la fonction mail() du serveur.';
            return ['success' => false, 'error' => $msg];
        }

        return ['success' => true, 'message' => "E-mail transmis au système de messagerie local (mail())."];
    }

    /**
     * Transporteur SMTP natif sur sockets PHP (RFC 5321 / STARTTLS / AUTH LOGIN)
     */
    private function sendSmtp(string $to, string $subject, string $htmlBody, string $textBody, array $config): array {
        $host = $config['host'];
        $port = $config['port'];
        $encryption = $config['encryption'];
        $timeout = 10;

        $target = ($encryption === 'ssl') ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}";

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $socket = @stream_socket_client($target, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            return ['success' => false, 'error' => "Connexion SMTP impossible à {$target} : [{$errno}] {$errstr}"];
        }

        stream_set_timeout($socket, $timeout);

        try {
            $resp = $this->readSmtpResponse($socket);
            if (!str_starts_with($resp, '220')) {
                throw new Exception("Bannière SMTP inattendue : {$resp}");
            }

            // EHLO
            $clientHost = gethostname() ?: 'localhost';
            $this->writeSmtpCommand($socket, "EHLO {$clientHost}");
            $ehloResp = $this->readSmtpResponse($socket);

            // STARTTLS si configuré
            if ($encryption === 'tls') {
                $this->writeSmtpCommand($socket, "STARTTLS");
                $tlsResp = $this->readSmtpResponse($socket);
                if (!str_starts_with($tlsResp, '220')) {
                    throw new Exception("Négociation STARTTLS refusée : {$tlsResp}");
                }

                $cryptoOk = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if (!$cryptoOk) {
                    throw new Exception("Échec de l'établissement du chiffrement TLS.");
                }

                // Re-EHLO sous TLS
                $this->writeSmtpCommand($socket, "EHLO {$clientHost}");
                $this->readSmtpResponse($socket);
            }

            // Authentification si utilisateur renseigné
            if ($config['username'] !== '') {
                $this->writeSmtpCommand($socket, "AUTH LOGIN");
                $authResp = $this->readSmtpResponse($socket);
                if (!str_starts_with($authResp, '334')) {
                    throw new Exception("AUTH LOGIN non pris en charge : {$authResp}");
                }

                $this->writeSmtpCommand($socket, base64_encode($config['username']));
                $userResp = $this->readSmtpResponse($socket);
                if (!str_starts_with($userResp, '334')) {
                    throw new Exception("Nom d'utilisateur SMTP rejeté : {$userResp}");
                }

                $this->writeSmtpCommand($socket, base64_encode($config['password']));
                $passResp = $this->readSmtpResponse($socket);
                if (!str_starts_with($passResp, '235')) {
                    throw new Exception("Mot de passe SMTP rejeté : {$passResp}");
                }
            }

            // MAIL FROM
            $fromAddr = $config['from_address'];
            $this->writeSmtpCommand($socket, "MAIL FROM:<{$fromAddr}>");
            $mfResp = $this->readSmtpResponse($socket);
            if (!str_starts_with($mfResp, '250')) {
                throw new Exception("MAIL FROM refusé : {$mfResp}");
            }

            // RCPT TO
            $this->writeSmtpCommand($socket, "RCPT TO:<{$to}>");
            $rcptResp = $this->readSmtpResponse($socket);
            if (!str_starts_with($rcptResp, '250') && !str_starts_with($rcptResp, '251')) {
                throw new Exception("Destinataire rejeté ({$to}) : {$rcptResp}");
            }

            // DATA
            $this->writeSmtpCommand($socket, "DATA");
            $dataResp = $this->readSmtpResponse($socket);
            if (!str_starts_with($dataResp, '354')) {
                throw new Exception("Échec commande DATA : {$dataResp}");
            }

            // Construction du message RFC 2822 MIME
            $boundary = '----=_Part_' . md5((string)microtime(true)) . '_' . bin2hex(random_bytes(8));
            $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

            $rawMsg = "From: " . $this->formatHeaderAddress($config['from_name'], $fromAddr) . "\r\n";
            $rawMsg .= "To: <{$to}>\r\n";
            $rawMsg .= "Date: " . date(DATE_RFC2822) . "\r\n";
            $rawMsg .= "Subject: {$encodedSubject}\r\n";
            $rawMsg .= "MIME-Version: 1.0\r\n";
            $rawMsg .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $rawMsg .= "X-Mailer: OpenShogun Native SMTP Client\r\n\r\n";

            $rawMsg .= "--{$boundary}\r\n";
            $rawMsg .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $rawMsg .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $rawMsg .= $textBody . "\r\n\r\n";

            $rawMsg .= "--{$boundary}\r\n";
            $rawMsg .= "Content-Type: text/html; charset=UTF-8\r\n";
            $rawMsg .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $rawMsg .= $htmlBody . "\r\n\r\n";

            $rawMsg .= "--{$boundary}--\r\n";
            $rawMsg .= ".\r\n";

            // Envoi des données
            fwrite($socket, $rawMsg);
            $finResp = $this->readSmtpResponse($socket);
            if (!str_starts_with($finResp, '250')) {
                throw new Exception("Erreur de validation des données SMTP : {$finResp}");
            }

            // QUIT
            $this->writeSmtpCommand($socket, "QUIT");
            fclose($socket);

            return [
                'success' => true,
                'message' => "E-mail expédié avec succès via le serveur SMTP ({$host}:{$port})."
            ];

        } catch (Exception $e) {
            @fclose($socket);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function writeSmtpCommand($socket, string $cmd): void {
        fwrite($socket, $cmd . "\r\n");
    }

    private function readSmtpResponse($socket): string {
        $response = '';
        while (!feof($socket)) {
            $line = fgets($socket, 1024);
            if ($line === false) break;
            $response .= $line;
            // Réponse multiligne si le 4ème caractère est '-'
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return trim($response);
    }

    private function formatHeaderAddress(string $name, string $email): string {
        if ($name === '') return "<{$email}>";
        return '=?UTF-8?B?' . base64_encode($name) . "?= <{$email}>";
    }

    /**
     * Envoie l'e-mail de confirmation d'inscription avec le token d'activation
     */
    public function sendVerificationEmail(string $toEmail, string $username, string $rawToken, string $faction = 'terran'): array {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $activationUrl = "{$scheme}://{$host}/?action=verify_email&token=" . urlencode($rawToken);

        $subject = "🏯 Ratification de votre Fief Féodal — Activez votre compte Daimyō";

        $htmlBody = $this->renderFeudalEmailTemplate([
            'username'       => $username,
            'faction'        => $faction,
            'activation_url' => $activationUrl,
            'expires_hours'  => 24,
            'title'          => "Ratification du Pacte Féodal",
            'preheader'      => "Votre domaine castral attend vos ordres. Confirmez votre décret pour sceller votre allégeance."
        ]);

        $textBody = "Salutations noble Daimyō {$username},\n\n"
                  . "Votre domaine castral sur OpenShogun a été fondé avec succès.\n"
                  . "Pour ratifier votre décret et déverrouiller l'accès complet à votre fief, veuillez activer votre compte en visitant ce lien sous 24 heures :\n\n"
                  . "{$activationUrl}\n\n"
                  . "Que les Kamis guident vos pas sur la Voie du Shogun.\n"
                  . "Le Conseil des Régents d'OpenShogun\n";

        return $this->sendEmail($toEmail, $subject, $htmlBody, $textBody);
    }

    /**
     * Envoie un e-mail de test administrateur pour valider la configuration
     */
    public function sendTestEmail(string $toEmail): array {
        $config = $this->getConfig();
        $timestamp = date('d/m/Y à H:i:s');
        $subject = "🧪 Test de Messagerie Impériale — OpenShogun [{$config['driver']}]";

        $htmlBody = "
        <div style='background-color:#1e1e2d; padding:30px; font-family:Arial,sans-serif; color:#e2e8f0;'>
            <div style='max-width:550px; margin:0 auto; background:#2a2b3d; border:1px solid #3f4254; border-radius:10px; overflow:hidden;'>
                <div style='background:#181824; padding:20px; text-align:center; border-bottom:2px solid #eab308;'>
                    <h2 style='color:#eab308; margin:0;'>🏯 OpenShogun &bull; Diagnostic Messagerie</h2>
                </div>
                <div style='padding:25px;'>
                    <p style='color:#22c55e; font-weight:bold; font-size:1.1rem; margin-top:0;'>✔ Connexion et transport validés avec succès !</p>
                    <p style='color:#94a3b8; font-size:0.95rem; line-height:1.5;'>
                        Cet e-mail confirme que le transporteur de messagerie configuré dans le panneau d'administration fonctionne parfaitement.
                    </p>
                    <div style='background:#1e1e2d; padding:15px; border-radius:6px; font-family:monospace; font-size:0.85rem; color:#cbd5e1; margin:20px 0;'>
                        <div><strong>Mode de transport :</strong> " . strtoupper($config['driver']) . "</div>
                        <div><strong>Hôte / Port :</strong> {$config['host']}:{$config['port']} (" . strtoupper($config['encryption']) . ")</div>
                        <div><strong>Expéditeur :</strong> {$config['from_name']} &lt;{$config['from_address']}&gt;</div>
                        <div><strong>Horodatage :</strong> {$timestamp}</div>
                    </div>
                    <p style='color:#64748b; font-size:0.8rem; margin-bottom:0;'>Message automatique généré depuis le Panneau d'Administration d'OpenShogun.</p>
                </div>
            </div>
        </div>";

        $textBody = "Test de Messagerie Impériale — OpenShogun\n\n"
                  . "Connexion et transport validés avec succès !\n"
                  . "Transporteur : " . strtoupper($config['driver']) . "\n"
                  . "Serveur : {$config['host']}:{$config['port']} (" . strtoupper($config['encryption']) . ")\n"
                  . "Expéditeur : {$config['from_address']}\n"
                  . "Date : {$timestamp}\n";

        return $this->sendEmail($toEmail, $subject, $htmlBody, $textBody);
    }

    /**
     * Génère le template d'e-mail HTML immersif inspiré du Japon féodal
     */
    public function renderFeudalEmailTemplate(array $vars): string {
        $username = htmlspecialchars($vars['username'] ?? 'Daimyō');
        $factionName = match($vars['faction'] ?? 'terran') {
            'vorash' => 'Clan Takeda (Cavalerie & Honneur)',
            'aethelis' => 'Clan Mori (Mer & Sanctuaires)',
            default => 'Clan Oda (Artillerie & Conquête)'
        };
        $activationUrl = htmlspecialchars($vars['activation_url'] ?? '#');
        $expiresHours = (int)($vars['expires_hours'] ?? 24);
        $title = htmlspecialchars($vars['title'] ?? 'Ratification du Pacte Féodal');
        $preheader = htmlspecialchars($vars['preheader'] ?? '');

        return <<<HTML
<!DOCTYPE html>
<html lang="fr" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; background-color: #0f172a; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .content-padding { padding: 25px 20px !important; }
            .btn-cta { display: block !important; width: 100% !important; text-align: center !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #0f172a;">

    <!-- Pré-header masqué -->
    <div style="display: none; font-size: 1px; color: #0f172a; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;">
        {$preheader}
    </div>

    <!-- Table Wrapper -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #0f172a;">
        <tr>
            <td align="center" style="padding: 40px 15px;">
                
                <!-- Conteneur Carte Email -->
                <table role="presentation" class="email-container" border="0" cellpadding="0" cellspacing="0" width="600" style="max-width: 600px; background-color: #1e293b; border-radius: 12px; overflow: hidden; border: 1px solid #334155; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);">
                    
                    <!-- En-tête Héroïque Féodale -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #1e1e2d 0%, #2e1065 100%); padding: 35px 20px; border-bottom: 3px solid #eab308;">
                            <div style="font-size: 42px; margin-bottom: 10px;">🏯</div>
                            <h1 style="margin: 0; color: #f8fafc; font-size: 24px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; font-family: Georgia, 'Times New Roman', serif;">
                                La Voie du Shōgun
                            </h1>
                            <div style="color: #fef08a; font-size: 12px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; margin-top: 6px;">
                                Chroniques Féodales du Sengoku
                            </div>
                        </td>
                    </tr>

                    <!-- Corps du Message -->
                    <tr>
                        <td class="content-padding" style="padding: 35px 35px 25px 35px; color: #e2e8f0; font-size: 15px; line-height: 1.65;">
                            
                            <h2 style="margin: 0 0 16px 0; color: #f8fafc; font-size: 20px; font-weight: 700;">
                                Salutations, noble Seigneur <span style="color: #eab308;">{$username}</span> !
                            </h2>

                            <p style="margin: 0 0 18px 0; color: #cbd5e1;">
                                Votre demande de fondation d'un nouveau fief au sein du <strong>{$factionName}</strong> a été enregistrée par le Grand Conseil Impérial de Kyōto.
                            </p>

                            <p style="margin: 0 0 25px 0; color: #cbd5e1;">
                                Pour sceller votre pacte féodal, décréter l'immunité de vos terres et ouvrir les portes de votre château fort, veuillez ratifier votre adresse e-mail en cliquant sur le bouton ci-dessous :
                            </p>

                            <!-- Bouton Call-To-Action Stylisé -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{$activationUrl}" target="_blank" class="btn-cta" style="background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%); color: #ffffff; text-decoration: none; padding: 15px 36px; border-radius: 8px; font-weight: 800; font-size: 16px; display: inline-block; letter-spacing: 0.5px; border: 1px solid #ef4444; box-shadow: 0 4px 14px rgba(185, 28, 28, 0.4);">
                                            ⚔️ Activer mon Fief Daimyō
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Avertissement Sécurité & Expiration -->
                            <div style="background-color: #0f172a; border-left: 4px solid #eab308; padding: 14px 18px; border-radius: 0 8px 8px 0; margin: 25px 0; font-size: 13px; color: #94a3b8;">
                                <strong style="color: #fef08a;">⚠️ Validité du sceau impérial :</strong> Ce lien d'activation expirera dans <strong>{$expiresHours} heures</strong>. Au-delà, un nouvel e-mail devra être sollicité.
                            </div>

                            <!-- Fallback URL Texte Brut -->
                            <p style="margin: 25px 0 8px 0; color: #64748b; font-size: 12px;">
                                Si le bouton ci-dessus ne réagit pas, copiez et collez l'adresse suivante dans la barre de votre navigateur :
                            </p>
                            <div style="background-color: #0f172a; padding: 10px 14px; border-radius: 6px; font-family: Consolas, Monaco, monospace; font-size: 11px; color: #38bdf8; word-break: break-all; border: 1px solid #1e293b;">
                                {$activationUrl}
                            </div>

                        </td>
                    </tr>

                    <!-- Pied de Page Féodal -->
                    <tr>
                        <td align="center" style="background-color: #0f172a; padding: 25px; border-top: 1px solid #334155; color: #64748b; font-size: 12px; line-height: 1.5;">
                            <div style="color: #94a3b8; font-weight: 600; margin-bottom: 4px;">
                                Que les Kamis veillent sur votre lignée.
                            </div>
                            <div>
                                Le Conseil des Régents &bull; OpenShogun
                            </div>
                            <div style="margin-top: 12px; color: #475569; font-size: 11px;">
                                Vous recevez ce message car une inscription a été initiée avec votre adresse e-mail. Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet envoi.
                            </div>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
HTML;
    }
}
