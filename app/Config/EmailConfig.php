<?php

class EmailConfig {
    private static $config = null;
    
    public static function getContactEmail() {
        if (self::$config === null) {
            self::loadConfig();
        }
        return self::$config['contact_email'] ?? 'andremoriana@gmail.com';
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
            'contact_email' => 'andremoriana@gmail.com',
            'from_email' => 'noreply@ArcTraining.eu',
            'from_name' => 'Portail Arc Training',
            'use_smtp' => false,
            'smtp_host' => 'localhost',
            'smtp_port' => 25,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => ''
        ];
        
        // Charger les variables d'environnement depuis le fichier .env de WebApp2
        $envFile = __DIR__ . '/../../.env';
        
        $envVars = [];
        if (file_exists($envFile)) {
            $envVars = self::parseEnvFile($envFile);
        }
        
        // Utiliser les variables d'environnement pour surcharger la config
        // Priorité: getenv() > fichier .env > valeurs par défaut
        if (getenv('CONTACT_EMAIL') || isset($envVars['CONTACT_EMAIL'])) {
            $contactEmail = getenv('CONTACT_EMAIL') ?: $envVars['CONTACT_EMAIL'];
            self::$config['contact_email'] = trim($contactEmail, '"');
        }
        if (getenv('SMTP_HOST') || isset($envVars['SMTP_HOST'])) {
            self::$config['smtp_host'] = getenv('SMTP_HOST') ?: $envVars['SMTP_HOST'];
        }
        if (getenv('SMTP_PORT') || isset($envVars['SMTP_PORT'])) {
            self::$config['smtp_port'] = (int)(getenv('SMTP_PORT') ?: $envVars['SMTP_PORT']);
        }
        if (getenv('SMTP_SECURE') || isset($envVars['SMTP_SECURE'])) {
            self::$config['smtp_encryption'] = getenv('SMTP_SECURE') ?: $envVars['SMTP_SECURE'];
        }
        if (getenv('SMTP_USERNAME') || isset($envVars['SMTP_USERNAME'])) {
            self::$config['smtp_username'] = getenv('SMTP_USERNAME') ?: $envVars['SMTP_USERNAME'];
        }
        if (getenv('SMTP_PASSWORD') || isset($envVars['SMTP_PASSWORD'])) {
            self::$config['smtp_password'] = getenv('SMTP_PASSWORD') ?: $envVars['SMTP_PASSWORD'];
        }
        if (getenv('SMTP_FROM_EMAIL') || isset($envVars['SMTP_FROM_EMAIL'])) {
            $fromEmail = getenv('SMTP_FROM_EMAIL') ?: $envVars['SMTP_FROM_EMAIL'];
            self::$config['from_email'] = trim($fromEmail, '"');
        }
        if (getenv('SMTP_FROM_NAME') || isset($envVars['SMTP_FROM_NAME'])) {
            $fromName = getenv('SMTP_FROM_NAME') ?: $envVars['SMTP_FROM_NAME'];
            self::$config['from_name'] = trim($fromName, '"');
        }
        if (getenv('EMAIL_METHOD') || isset($envVars['EMAIL_METHOD'])) {
            $method = getenv('EMAIL_METHOD') ?: $envVars['EMAIL_METHOD'];
            self::$config['use_smtp'] = ($method === 'smtp');
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
                    $value = trim($value);
                    $vars[$key] = $value;
                }
            }
        }
        return $vars;
    }
}

