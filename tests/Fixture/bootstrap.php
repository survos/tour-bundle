<?php

declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';
// Simulate Composer's installed package record in the isolated fixture process;
// no Composer repository overrides or edits to vendor are needed.
$data = Composer\InstalledVersions::getAllRawData();
$data[0]['versions']['survos/tour-bundle'] = ['pretty_version' => 'dev-main', 'version' => 'dev-main', 'install_path' => dirname(__DIR__, 2), 'dev_requirement' => false];
Composer\InstalledVersions::reload($data[0]);
