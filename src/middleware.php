<?php

$app->add(new \Tuupola\Middleware\HttpBasicAuthentication([
    "secure" => false,
    "users" => $app->getContainer()->settings['users']['available']
]));
