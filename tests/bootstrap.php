<?php

declare(strict_types=1);

$loader = require dirname(__DIR__, 3).'/vendor/autoload.php';
$loader->addPsr4('Survos\\TourBundle\\', dirname(__DIR__).'/src');
$loader->addPsr4('Survos\\TourBundle\\Tests\\', __DIR__);
$loader->addPsr4('Survos\\MenuBundle\\', dirname(__DIR__, 2).'/menu-bundle/src');
$loader->addPsr4('Survos\\TablerBundle\\', dirname(__DIR__, 2).'/tabler-bundle/src', true);
$loader->addPsr4('Survos\\FieldBundle\\', dirname(__DIR__, 2).'/field-bundle/src');
