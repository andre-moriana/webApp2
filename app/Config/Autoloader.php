<?php
class Autoloader {
    private static $directories = [
        'app/Controllers/',
        'app/Models/',
        'app/Services/',
        'app/Config/'
    ];
   
    public static function register() {
        spl_autoload_register([__CLASS__, 'load']);
    }
   
    public static function load($className) {
        foreach (self::$directories as $directory) {
            $file = $directory . $className . '.php';
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
        return false;
    }
}

// Enregistrer l'autoloader
Autoloader::register();
