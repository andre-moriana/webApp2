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
        $configFile = __DIR__ . '/email.config.php';
        
        if (file_exists($configFile)) {
            self::$config = require $configFile;
        } else {
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
        }
    }
}

