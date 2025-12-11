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
        return self::$config['from_email'] ?? 'noreply@archers-gemenos.com';
    }
    
    public static function getFromName() {
        if (self::$config === null) {
            self::loadConfig();
        }
        return self::$config['from_name'] ?? 'Portail Archers de Gémenos';
    }
    
    private static function loadConfig() {
        $configFile = __DIR__ . '/email.config.php';
        
        if (file_exists($configFile)) {
            self::$config = require $configFile;
        } else {
            // Configuration par défaut
            self::$config = [
                'contact_email' => 'andremoriana@gmail.com',
                'from_email' => 'noreply@archers-gemenos.com',
                'from_name' => 'Portail Archers de Gémenos'
            ];
        }
    }
}

