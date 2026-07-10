<?php

/**
 * PHPUnit bootstrap: prefer the isolated test database before Laravel boots.
 *
 * Never run tests against the development MySQL database. See tests/README.md.
 */
require __DIR__.'/../vendor/autoload.php';

$forced = [
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'mysql',
    'DB_DATABASE' => 'expenses_test',
    'DB_URL' => '',
];

foreach ($forced as $key => $value) {
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
    putenv("{$key}={$value}");
}
