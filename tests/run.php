<?php

declare(strict_types=1);

$autoload = dirname(__DIR__, 3).'/autoload.php';

if (!is_file($autoload)) {
    $autoload = dirname(__DIR__).'/vendor/autoload.php';
}

require $autoload;
require __DIR__.'/Style/StyleManagerClassImporterTest.php';

$test = new C4Y\One4you\Tests\Style\StyleManagerClassImporterTest();
$test->run();

fwrite(STDOUT, "StyleManagerClassImporter tests passed.\n");
