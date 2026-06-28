<?php
/**
 * Service de vérification de Google reCAPTCHA v3 côté serveur.
 *
 * Usage :
 *   $result = RecaptchaService::verify($_POST['recaptcha_token'] ?? '', 'register');
 *   if (!$result['success']) { ... rejeter ... }
 */
class RecaptchaService {
    /**
     * Vérifie un token reCAPTCHA v3 auprès de l'API Google.
     *
     * @param string $token  Token généré côté navigateur (grecaptcha.execute)
     * @param string $action Action attendue (doit correspondre à celle du front)
     * @return array{success:bool, score:float|null, reason:string}
     */
    public static function verify(string $token, string $action = ''): array {
        // Si reCAPTCHA est désactivé (ex. développement local), on laisse passer.
        if (!RecaptchaConfig::isEnabled()) {
            return ['success' => true, 'score' => null, 'reason' => 'disabled'];
        }

        if ($token === '') {
            error_log('reCAPTCHA: token manquant');
            return ['success' => false, 'score' => null, 'reason' => 'missing_token'];
        }

        $postFields = [
            'secret'   => RecaptchaConfig::getSecretKey(),
            'response' => $token,
        ];
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $postFields['remoteip'] = $_SERVER['REMOTE_ADDR'];
        }

        $response = self::postToGoogle($postFields);
        if ($response === null) {
            // En cas d'indisponibilité de l'API Google, on évite de bloquer
            // totalement les inscriptions : on log et on laisse passer (fail-open).
            error_log('reCAPTCHA: impossible de contacter l\'API de vérification (fail-open)');
            return ['success' => true, 'score' => null, 'reason' => 'verify_unreachable'];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            error_log('reCAPTCHA: réponse invalide de l\'API');
            return ['success' => false, 'score' => null, 'reason' => 'invalid_response'];
        }

        if (empty($data['success'])) {
            $errors = isset($data['error-codes']) ? implode(',', (array) $data['error-codes']) : 'unknown';
            error_log('reCAPTCHA: échec de validation - ' . $errors);
            return ['success' => false, 'score' => null, 'reason' => 'failed:' . $errors];
        }

        // Vérifier l'action attendue (protège contre la réutilisation d'un token)
        if ($action !== '' && isset($data['action']) && $data['action'] !== $action) {
            error_log('reCAPTCHA: action inattendue - reçu "' . $data['action'] . '", attendu "' . $action . '"');
            return ['success' => false, 'score' => $data['score'] ?? null, 'reason' => 'action_mismatch'];
        }

        // Vérifier le score (v3)
        $score = isset($data['score']) ? (float) $data['score'] : null;
        if ($score !== null && $score < RecaptchaConfig::getMinScore()) {
            error_log('reCAPTCHA: score trop faible (' . $score . ' < ' . RecaptchaConfig::getMinScore() . ')');
            return ['success' => false, 'score' => $score, 'reason' => 'low_score'];
        }

        return ['success' => true, 'score' => $score, 'reason' => 'ok'];
    }

    /**
     * Envoie la requête de vérification à Google (cURL si dispo, sinon file_get_contents).
     * @return string|null Corps de la réponse, ou null en cas d'échec réseau.
     */
    private static function postToGoogle(array $postFields): ?string {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, RecaptchaConfig::VERIFY_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($response === false || $httpCode !== 200) {
                return null;
            }
            return $response;
        }

        // Repli sans cURL
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($postFields),
                'timeout' => 10,
            ],
        ]);
        $response = @file_get_contents(RecaptchaConfig::VERIFY_URL, false, $context);
        return $response === false ? null : $response;
    }
}
