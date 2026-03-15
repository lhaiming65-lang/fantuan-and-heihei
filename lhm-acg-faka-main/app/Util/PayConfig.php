<?php
declare(strict_types=1);

namespace App\Util;


class PayConfig
{
    /**
     * @param string $handle
     * @return array|null
     */
    public static function config(string $handle): ?array
    {
        $path = BASE_PATH . '/app/Pay/' . $handle . '/Config/Config.php';
        return is_file($path) ? require($path) : null;
    }

    /**
     * @param string $handle
     * @return array|null
     */
    public static function info(string $handle): ?array
    {
        $path = BASE_PATH . '/app/Pay/' . $handle . '/Config/Info.php';
        return is_file($path) ? require($path) : null;
    }


    /**
     * @param string $handle
     * @param string $type
     * @param string $message
     */
    public static function log(string $handle, string $type, string $message): void
    {
        $dir = BASE_PATH . "/app/Pay/{$handle}";
        $path = $dir . "/runtime.log";
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (is_dir($dir)) {
            file_put_contents($path, "[{$type}][" . Date::current() . "]:" . $message . PHP_EOL, FILE_APPEND);
        }
    }
}