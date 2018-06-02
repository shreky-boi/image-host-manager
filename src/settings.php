<?php
return [
    'settings' => [
        'appName' => getenv('APP_NAME') ?: 'Image Host Manager',
        'imagesBaseUrl' => getenv('IMAGES_BASE_URL') ?: '',
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        'users' => [
            'available' => json_decode(getenv('USERS') ?: '{}', true),
            'current' => isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null,
        ],
        'aws' => [
            'key' => getenv('AWS_ACCESS_KEY'),
            'secret' => getenv('AWS_SECRET_KEY'),
            'bucket' => getenv('AWS_S3_BUCKET'),
            'region' => getenv('AWS_S3_REGION')
        ]
    ],
];
