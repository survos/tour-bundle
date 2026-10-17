<?php

declare(strict_types=1);

require __DIR__.'/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$browser = dirname(__DIR__).'/browser';
if ('/app.js' === $path || '/driver.css' === $path) {
    header('Content-Type: '.('/app.js' === $path ? 'text/javascript' : 'text/css'));
    readfile('/app.js' === $path ? dirname(__DIR__, 4).'/var/tour-fixture/app.js' : $browser.'/node_modules/driver.js/dist/driver.css');

    return;
}
$kernel = new Survos\TourBundle\Tests\Fixture\Kernel('browser', true);
$kernel->boot();
try {
    echo $kernel->getContainer()->get(Survos\TourBundle\Tests\Fixture\FixturePage::class)->render($_GET['mode'] ?? 'survos', $_GET['role'] ?? 'anonymous', isset($_GET['empty']));
} finally {
    $kernel->shutdown();
}
