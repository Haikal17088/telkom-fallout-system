<?php

// Vercel Functions have a read-only application filesystem. Store compiled
// Blade templates in the temporary writable directory instead.
$compiledViewsPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'laravel-views';

if (! is_dir($compiledViewsPath)) {
    mkdir($compiledViewsPath, 0775, true);
}

if (! is_dir($compiledViewsPath) || ! is_writable($compiledViewsPath)) {
    error_log('Laravel compiled views directory is not writable: ' . $compiledViewsPath);
    http_response_code(500);
    exit('Application initialization failed.');
}

// Set all supported PHP environment sources before Laravel loads config/view.php.
putenv('VIEW_COMPILED_PATH=' . $compiledViewsPath);
$_ENV['VIEW_COMPILED_PATH'] = $compiledViewsPath;
$_SERVER['VIEW_COMPILED_PATH'] = $compiledViewsPath;

require __DIR__ . '/../public/index.php';
