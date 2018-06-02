<?php

use App\Models\Image;
use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->group('', function () use ($app) {
    $app->get('/[?added={added}]', function (Request $request, Response $response, array $args) {

        $args['added'] = $request->getQueryParam('added');
        $args['csrf_name'] = $request->getAttribute('csrf_name');
        $args['csrf_value'] = $request->getAttribute('csrf_value');
        $args['flash'] = $this->flash->getMessages();

        // Let's get our images from the bucket, sorted by most recently updated
        // first, then attempt to move our target image to the top, if given.
        $args['images'] = $this->images->moveToTopByKey(
            $this->images->sortByUpdated(
                $this->images->all()
            ),
            $args['added']
        );

        return $this->renderer->render($response, 'index.twig', $args);
    })->setName('list');

    $app->get('/{name}/{extension}', function (Request $request, Response $response, array $args) {
        $objectKey = Image::getObjectKey($args['name'], $args['extension']);

        $args['csrf_name'] = $request->getAttribute('csrf_name');
        $args['csrf_value'] = $request->getAttribute('csrf_value');
        $args['flash'] = $this->flash->getMessages();

        if ($this->flysystem->has($objectKey)) {
            $args['image'] =$this->images->find($objectKey);

            return $this->renderer->render($response, 'edit.twig', $args);
        }

        throw new \Slim\Exception\NotFoundException($request, $response);
    })->setName('edit');

    $app->put('/{name}/{extension}', function (Request $request, Response $response, array $args) {
        $objectKey = Image::getObjectKey($args['name'], $args['extension']);

        if ($this->flysystem->has($objectKey)) {
            try {
                $image = new Image(['basename' => $objectKey]);
                $image->description = $request->getParam('description');
                $image->setTags($request->getParam('tags'));

                $this->images->updateMetaData($image);
                $this->flash->addMessage('success', 'Image updated!');
            } catch (Exception $e) {
                $this->flash->addMessage('danger', $e->getMessage());
            }

            $url = $this->router->pathFor('edit', ['name' => $args['name'], 'extension' => $args['extension']]);

            return $response->withRedirect($url);
        }

        throw new \Slim\Exception\NotFoundException($request, $response);
    })->setName('update');

    $app->post('/add', function (Request $request, Response $response, array $args) {

        $uploadFileName = null;

        try {
            $uploadFileName = $this->images->uploadImageByUrl(
                $request->getParam('url'),
                $request->getParam('name')
            );

            $this->flash->addMessage('success', 'Image added!');
        } catch (Exception $e) {
            $this->flash->addMessage('danger', $e->getMessage());
        }

        $url = $this->router->pathFor('list', array_filter(['added' => $uploadFileName], 'strlen'));

        return $response->withRedirect($url);
    });
})->add($container->get('csrf'));
