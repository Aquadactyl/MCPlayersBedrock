<?php
// Run with php -d phar.readonly=0 build.php SOURCE_DIRECTORY OUTPUT_FILE.
$source = realpath($argv[1]);
$output = $argv[2];
$phar = new Phar($output);
$phar->startBuffering();
$phar->buildFromDirectory($source, '/(?:\.php|\.yml)$/');
$phar->setStub('<?php __HALT_COMPILER();');
$phar->stopBuffering();
echo $output . PHP_EOL;
