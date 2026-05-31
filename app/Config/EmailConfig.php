<?php

class EmailConfig {
    private static $config = null;
    
    public static function getContactEmail() {
        if (self::$config === null) {
            self::loadConfig();
        }
        return self::$config['contact_email'] ?? 'webmaster@arctraining.fr';
    }
    
    public static function getFromEmail() {
        if (self::$config === null) {
            self::loadConfig();
        }
        return self::$config['from_email'] ?? 'noreply@ArcTraining.eu';
    }
    
    public static function getFromName() {
        if (self::$config === null) {
            self::loadConfig();
        }
        return self::$config['from_name'] ?? 'Portail Arc Training';
    }
    
    public static function getSmtpHost() {
        if (self::$config === null) {
            self::loadConfig();
        }
        return self::$config['smtp_host'] ?? 'localhost';
    }
    
    public static function getSmtpPort() {
        if (self::$config === null) {
            self::loadConfig();
        }
        return self::$config['smtp_port'] ?? 25;
    }
    
    public static function getSmtpUsername() {
        if (self::$config === null) {
            self::loadConfig();
        }
        return self::$config['smtp_username'] ?? '';
    }
    
    public static function getSmtpPassword() {
        if (self::$config === null) {
            self::loadConfig();
        }
        return self::$config['smtp_password'] ?? '';
    }
    
    public static function getSmtpEncryption() {
        if (self::$config === null) {
            self::loadConfig();
        }
        return self::$config['smtp_encryption'] ?? ''; // 'tls' ou 'ssl' ou ''
    }
    
    public static function useSmtp() {
        if (self::$config === null) {
            self::loadConfig();
        }
        return self::$config['use_smtp'] ?? false;
    }
    
    private static function loadConfig() {
        // Configuration par défaut
        self::$config = [
            'contact_email' => 'webmaster@arctraining.fr',
            'from_email' => 'noreply@arctraining.fr',
            'from_name' => 'Portail Arc Training',
            'use_smtp' => false,
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls',
        ];

        // Fichier de config dédié (email.config.php)
        $configFile = __DIR__ . '/email.config.php';
        if (file_exists($configFile)) {
            $fileConfig = require $configFile;
            if (is_array($fileConfig)) {
                self::$config = array_merge(self::$config, $fileConfig);
            }
        }

        $envVars = self::getEnvVars();

        self::applyEnvVar($envVars, 'CONTACT_EMAIL', 'contact_email');
        self::applyEnvVar($envVars, 'SMTP_HOST', 'smtp_host');
        self::applyEnvVar($envVars, 'SMTP_PORT', 'smtp_port', true);
        self::applyEnvVar($envVars, 'SMTP_SECURE', 'smtp_encryption');
        self::applyEnvVar($envVars, 'SMTP_USERNAME', 'smtp_username');
        self::applyEnvVar($envVars, 'SMTP_PASSWORD', 'smtp_password');
        self::applyEnvVar($envVars, 'SMTP_FROM_EMAIL', 'from_email');
        self::applyEnvVar($envVars, 'SMTP_FROM_NAME', 'from_name');

        if (self::envValue($envVars, 'EMAIL_METHOD') !== null) {
            self::$config['use_smtp'] = (self::envValue($envVars, 'EMAIL_METHOD') === 'smtp');
        }

        // Compléter depuis le .env du backend si SMTP non configuré côté WebApp
        self::mergeBackendEnvIfNeeded($envVars);
        self::mergeBackendEnvFromSiblingPath($envVars);

        // Activer SMTP automatiquement si un relais est configuré
        if (
            !empty(self::$config['smtp_host'])
            && self::$config['smtp_host'] !== 'localhost'
            && !empty(self::$config['smtp_username'])
        ) {
            self::$config['use_smtp'] = true;
        }
    }

    private static function getEnvVars(): array {
        $envFile = __DIR__ . '/../../.env';
        $fromFile = file_exists($envFile) ? self::parseEnvFile($envFile) : [];
        $merged = $fromFile;
        foreach ($_ENV as $key => $value) {
            if ($value !== '' && $value !== null) {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }

    private static function envValue(array $envVars, string $key): ?string {
        $value = getenv($key);
        if ($value === false || $value === '') {
            $value = $envVars[$key] ?? null;
        }
        if ($value === null || $value === '') {
            return null;
        }
        return self::cleanEnvValue((string)$value);
    }

    private static function applyEnvVar(array $envVars, string $envKey, string $configKey, bool $asInt = false): void {
        $value = self::envValue($envVars, $envKey);
        if ($value === null) {
            return;
        }
        self::$config[$configKey] = $asInt ? (int)$value : $value;
    }

    private static function cleanEnvValue(string $value): string {
        $value = trim($value);
        if (
            (strlen($value) >= 2 && $value[0] === '"' && substr($value, -1) === '"')
            || (strlen($value) >= 2 && $value[0] === "'" && substr($value, -1) === "'")
        ) {
            $value = substr($value, 1, -1);
        }
        return trim($value);
    }

    private static function mergeBackendEnvIfNeeded(array &$envVars): void {
        if (!empty(self::$config['smtp_host']) && self::$config['smtp_host'] !== 'localhost') {
            return;
        }

        $backendPath = self::resolveBackendPath($envVars);
        if ($backendPath === null) {
            return;
        }

        $backendEnvFile = $backendPath . '/.env';
        if (!file_exists($backendEnvFile)) {
            return;
        }

        $backendEnv = self::parseEnvFile($backendEnvFile);
        $envVars = array_merge($backendEnv, $envVars);
        self::applyBackendEnvMap($backendEnv);

        if (self::envValue($backendEnv, 'EMAIL_METHOD') === 'smtp') {
            self::$config['use_smtp'] = true;
        }
    }

    public static function resolveBackendPath(?array $envVars = null): ?string {
        $envVars = $envVars ?? self::getEnvVars();
        $candidates = [];
        foreach (['BACKEND_PATH', 'BACKEND_ROOT'] as $key) {
            $value = self::envValue($envVars, $key);
            if ($value !== null) {
                $candidates[] = $value;
            }
        }
        $webRoot = dirname(__DIR__, 2);
        $candidates[] = $webRoot . '/../BackendPHP';
        $candidates[] = $webRoot . '/../wamp64/www/BackendPHP';
        $candidates[] = '/home/www/BackendPHP';

        foreach ($candidates as $path) {
            if (is_string($path) && $path !== '' && is_dir($path)) {
                return rtrim(str_replace('\\', '/', $path), '/');
            }
        }
        return null;
    }

    /**
     * Sur IONOS, le backend est souvent dans /home/www/BackendPHP à côté du portail.
     */
    private static function mergeBackendEnvFromSiblingPath(array &$envVars): void {
        if (!empty(self::$config['smtp_host']) && self::$config['smtp_host'] !== 'localhost') {
            return;
        }

        $candidates = [
            dirname(__DIR__, 2) . '/../BackendPHP/.env',
            '/home/www/BackendPHP/.env',
        ];
        $backendPath = self::resolveBackendPath($envVars);
        if ($backendPath !== null) {
            $candidates[] = $backendPath . '/.env';
        }

        foreach ($candidates as $envFile) {
            if (!is_readable($envFile)) {
                continue;
            }
            $backendEnv = self::parseEnvFile($envFile);
            $envVars = array_merge($backendEnv, $envVars);
            self::applyBackendEnvMap($backendEnv);
            if (self::envValue($backendEnv, 'EMAIL_METHOD') === 'smtp') {
                self::$config['use_smtp'] = true;
            }
            return;
        }
    }

    private static function applyBackendEnvMap(array $backendEnv): void {
        $map = [
            'CONTACT_EMAIL' => 'contact_email',
            'SMTP_HOST' => 'smtp_host',
            'SMTP_PORT' => 'smtp_port',
            'SMTP_SECURE' => 'smtp_encryption',
            'SMTP_USERNAME' => 'smtp_username',
            'SMTP_PASSWORD' => 'smtp_password',
            'SMTP_FROM_EMAIL' => 'from_email',
            'SMTP_FROM_NAME' => 'from_name',
        ];
        foreach ($map as $envKey => $configKey) {
            if (!empty(self::$config[$configKey]) && self::$config[$configKey] !== 'localhost') {
                continue;
            }
            $value = self::envValue($backendEnv, $envKey);
            if ($value !== null) {
                self::$config[$configKey] = ($configKey === 'smtp_port') ? (int)$value : $value;
            }
        }
    }
    
    /**
     * Parse un fichier .env simple
     */
    private static function parseEnvFile($filePath) {
        $vars = [];
        if (file_exists($filePath)) {
            $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Ignorer les commentaires
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                // Parser les lignes KEY=VALUE
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $vars[$key] = self::cleanEnvValue(trim($value));
                }
            }
        }
        return $vars;
    }
}

