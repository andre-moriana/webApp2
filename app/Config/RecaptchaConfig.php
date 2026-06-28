<?php
/**
 * Configuration de Google reCAPTCHA v3.
 *
 * Les valeurs sont lues depuis les variables d'environnement (.env) :
 *   RECAPTCHA_ENABLED    : "true"/"false" (par défaut activé si une clé secrète est présente)
 *   RECAPTCHA_SITE_KEY   : clé publique (côté navigateur)
 *   RECAPTCHA_SECRET_KEY : clé secrète (côté serveur)
 *   RECAPTCHA_MIN_SCORE  : score minimal accepté entre 0.0 et 1.0 (par défaut 0.5)
 */
class RecaptchaConfig {
    const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public static function getSiteKey(): string {
        return self::env('RECAPTCHA_SITE_KEY', '');
    }

    public static function getSecretKey(): string {
        return self::env('RECAPTCHA_SECRET_KEY', '');
    }

    public static function getMinScore(): float {
        $score = self::env('RECAPTCHA_MIN_SCORE', '0.5');
        $score = is_numeric($score) ? (float) $score : 0.5;
        // Garde-fou : rester dans [0,1]
        return max(0.0, min(1.0, $score));
    }

    /**
     * reCAPTCHA est actif si explicitement activé OU si une paire de clés est fournie.
     * Permet de désactiver proprement en local (RECAPTCHA_ENABLED=false).
     */
    public static function isEnabled(): bool {
        $flag = strtolower(self::env('RECAPTCHA_ENABLED', ''));
        if ($flag === 'false' || $flag === '0' || $flag === 'off') {
            return false;
        }
        if ($flag === 'true' || $flag === '1' || $flag === 'on') {
            return self::getSiteKey() !== '' && self::getSecretKey() !== '';
        }
        // Non spécifié : actif uniquement si les deux clés sont présentes
        return self::getSiteKey() !== '' && self::getSecretKey() !== '';
    }

    private static function env(string $key, string $default): string {
        $value = getenv($key);
        if ($value === false || $value === '') {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }
        if ($value === null || $value === '') {
            return $default;
        }
        $value = trim((string) $value);
        // Retirer d'éventuels guillemets entourants
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = substr($value, -1);
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }
        return $value;
    }
}
