<?php

$includes = [];
if (PHP_VERSION_ID < 80000) {
    $includes[] = __DIR__ . '/phpstan-baseline-7x.neon';
} elseif (PHP_VERSION_ID < 80100) {
    $includes[] = __DIR__ . '/phpstan-baseline-8.0.neon';
} else {
    $includes[] = __DIR__ . '/phpstan-baseline-8.1+.neon';
}

if (PHP_VERSION_ID >= 80000) {
    $includes[] = __DIR__ . '/phpstan-baseline-8x.neon';
}

return [
    'includes' => $includes
];
