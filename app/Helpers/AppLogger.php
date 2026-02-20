<?php
/**
 * Logger applicatif - même approche que BackendPHP (logs dédiés dans logs/)
 * Format : date('Y-m-d H:i:s') + message, écriture dans logs/{nom}.log
 */
class AppLogger
{
    private static function getLogDir(): string
    {
        return dirname(__DIR__, 2) . '/logs';
    }

    /**
     * Écrit un message dans un fichier de log dédié
     * @param string $logName Nom du fichier sans extension (ex: classement, arbitres)
     * @param string $msg Message à enregistrer
     */
    public static function log(string $logName, string $msg): void
    {
        $logDir = self::getLogDir();
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/' . preg_replace('/[^a-z0-9_-]/i', '', $logName) . '.log';
        @file_put_contents($logFile, date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND | LOCK_EX);
    }
}
