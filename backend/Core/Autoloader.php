<?php
/**
 * Autoloader simple pour les classes backend.
 */

declare(strict_types=1);

spl_autoload_register(function (string $className): void {
    $directories = [
        __DIR__,
        __DIR__ . '/../Controllers',
        __DIR__ . '/../Controllers/WorkshopControllers',
        __DIR__ . '/../Services',
        __DIR__ . '/../Repositories',
        __DIR__ . '/../Models',
    ];

    foreach ($directories as $directory) {
        $path = $directory . '/' . $className . '.php';
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});
