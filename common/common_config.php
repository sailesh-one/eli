<?php

global $config;
$base_url = $config['base_url'] ?? 'https://jlr-udms.cartrade.com';
$document_base_url = $config['document_base_url'] ;

return [
    'base_url' => $base_url ?? 'https://jlr-udms.cartrade.com',
    'document_base_url' => $document_base_url,
    'title' => [
        'Mr.'   => 'Mr.',
        'Mrs.'  => 'Mrs.',
        'Ms.'   => 'Ms.',
        'M/s'   => 'M/s',
        'Miss' => 'Miss',
        'Dr.'   => 'Dr.',
        'Prof.' => 'Prof.',
        'Hon.'  => 'Hon.', 
        'Sir'  => 'Sir',
        'Madam'=> 'Madam',
    ],

    'years'=> array_combine(range(date('Y'), 1980), range(date('Y'), 1980)),
    'months' => array_combine(range(1, 12), array_map(fn($m) => date('M', mktime(0, 0, 0, $m, 1)), range(1, 12))),

    'colors' => [
        '1'  => 'Beige',
        '2'  => 'Black',
        '3'  => 'Blue',
        '4'  => 'Bronze',
        '5'  => 'Brown',
        '6'  => 'Gold',
        '7'  => 'Green',
        '8'  => 'Grey',
        '9'  => 'Maroon',
        '10' => 'Orange',
        '11' => 'Purple',
        '12' => 'Red',
        '13' => 'Silver',
        '14' => 'White',
        '15' => 'Yellow',
        '16' => 'Custom Colour'
    ],


];
