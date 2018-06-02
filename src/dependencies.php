<?php

use App\Models\Image;
use App\Services\ImageService;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Slim\Flash\Messages;
use Slim\Http\Environment;
use Slim\Http\Uri;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;

// Include models
foreach (glob(__DIR__ . '/models/*.php') as $filename) {
    require $filename;
}

// Include services
foreach (glob(__DIR__ . '/services/*.php') as $filename) {
    require $filename;
}

// DIC configuration
$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $router = $c->router;
    $settings = $c->settings['renderer'];
    $view = new Twig($settings['template_path'], [
        // We don't need to cache right now.
        // 'cache' => 'path/to/cache'
    ]);
    $uri = Uri::createFromEnvironment(new Environment($_SERVER));
    $view->addExtension(new TwigExtension($router, $uri));
    $view->getEnvironment()->addGlobal('app_name', $c->settings['appName']);
    $view->getEnvironment()->addGlobal('images_base_url', $c->settings['imagesBaseUrl']);
    $view->getEnvironment()->addGlobal('current_user', $c->settings['users']['current']);
    return $view;
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->settings['logger'];
    $logger = new Logger($settings['name']);
    $logger->pushProcessor(new UidProcessor());
    $logger->pushHandler(new StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

// flash messages
$container['flash'] = function ($c) {
    return new Messages();
};

// csrf
$container['csrf'] = function ($c) {
    $csrfGuard = new Slim\Csrf\Guard();
    $csrfGuard->setFailureCallable(function ($request, $response, $next) use ($c) {
        // Let's attempt to redirect back, otherwise homepage is fine.
        $uri = $request->getServerParam('HTTP_REFERER') ?: $request->getUri()->withPath($c->router->pathFor('list'));
        // Let's flash a message!
        $c->flash->addMessage('danger', 'Whoops! Something was not quite right with your form submission.');

        return $response->withRedirect((string) $uri);
    });
    return $csrfGuard;
};

// s3
$container['s3'] = function ($c) {
    $credentials = ['key' => $c->settings['aws']['key'], 'secret' => $c->settings['aws']['secret']];
    return new S3Client(array(
        'credentials' => $credentials,
        'region' => $c->settings['aws']['region'],
        'version' => 'latest'
    ));
};

// image service
$container['images'] = function ($c) {
    return new ImageService($c);
};

// flysystem
$container['flysystem'] = function ($c) {
    $adapter = new AwsS3Adapter($c->s3, $c->settings['aws']['bucket']);
    return new Filesystem($adapter);
};
