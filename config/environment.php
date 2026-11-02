<?php
declare(strict_types=1);

$laravelDirectory = __DIR__ . '/../laravel';
$autoload = $laravelDirectory . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
    \Dotenv\Dotenv::createImmutable($laravelDirectory)->safeLoad();
}

function appConfigValue(string $name, string $default = ''): string
{
    $value = getenv($name);
    if ($value !== false && $value !== '') {
        return $value;
    }

    $value = $_ENV[$name] ?? $_SERVER[$name] ?? '';
    return is_string($value) && $value !== '' ? $value : $default;
}
