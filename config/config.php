<?php

return [
    'env' => 'defelop',
    '\App\Service\*Service' => \DI\create('\App\Service\*Service'),
];