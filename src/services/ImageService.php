<?php

namespace App\Services;

use App\Models\Image;
use Exception;
use League\Uri;
use ReflectionMethod;
use Slim\Container;

class ImageService
{
    /**
     * Framework DI Container
     *
     * @var Slim\Container
     */
    private $container;

    /**
     * Creates a new image service.
     *
     * @param Slim\Container  $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Fetches all appropriate images from the file system.
     *
     * @return array
     */
    public function all()
    {
        $results = array_map(function ($object) {
            return new Image($object);
        }, $this->container->flysystem->listContents());

        return array_filter($results, function ($image) {
            return $image->isImage();
        });
    }

    /**
     * Attempts to fetch a single object from the file system.
     *
     * @param  string $objectKey
     *
     * @return Image|null
     */
    public function find($objectKey)
    {
        try {
            $s3Object = $this->container->s3->getObject([
                'Bucket' => $this->container->settings['aws']['bucket'],
                'Key' => $objectKey
            ]);

            $r = new ReflectionMethod(get_class($this->container->flysystem->getAdapter()), 'normalizeResponse');
            $r->setAccessible(true);
            $file = $r->invoke(
                $this->container->flysystem->getAdapter(),
                $s3Object->toArray(),
                $objectKey
            );

            return Image::fromFlysystem($file);
        } catch (Aws\S3\Exception\S3Exception $e) {
            return null;
        }
    }

    /**
     * Loops over the given array of images and moves a given image to the top of
     * the array if the image basename matches the given key.
     *
     * @param  array   $images
     * @param  string  $key
     *
     * @return array
     */
    public static function moveToTopByKey(array $images, $key)
    {
        if ($key) {
            $indexes = array_filter(array_map(function ($image, $index) use ($args) {
                return strpos($image->basename, $args['added']) !== false ? $index : null;
            }, $images, array_keys($images)));
            array_walk($indexes, function ($currentIndex) use (&$args) {
                $match = array_splice($images, $currentIndex, 1);
                $images = array_values($images);
                array_splice($images, 0, 0, $match);
            });
        }

        return $images;
    }

    /**
     * Sorts the given array of images based on timestamp (last modified date).
     *
     * @param  array   $images
     *
     * @return array
     */
    public static function sortByUpdated(array $images)
    {
        usort($images, function ($a, $b) {
            return $b->timestamp - $a->timestamp;
        });

        return $images;
    }

    /**
     * Attempts to update the meta data for a given image.
     *
     * @param  Image   $image
     *
     * @return boolean
     * @throws Exception
     */
    public function updateMetaData(Image $image)
    {
        return $this->updateMetaDataByKey($image->basename, [
            'description' => $image->description,
            'tags' => $image->getTagsString()
        ]);
    }

    /**
     * Attempts to update the meta data for a given object key.
     *
     * @param  string  $objectKey
     * @param  array   $metadata
     *
     * @return boolean
     * @throws Exception
     */
    public function updateMetaDataByKey($objectKey, array $metadata)
    {
        $bucket = $this->container->settings['aws']['bucket'];
        $s3Object = $this->container->s3->copyObject([
            'ACL' => 'public-read',
            'Bucket' => $bucket,
            'CopySource' => sprintf('%s/%s', $bucket, $objectKey),
            'Key' => $objectKey,
            'Metadata' => $metadata,
            'MetadataDirective' => 'REPLACE'
        ]);

        return true;
    }

    /**
     * Attempts to create a new object in the filesystem using a copy of the
     * contents from the given url. If a name is provided, it will be used to
     * compose the object key/filename.
     *
     * When everything works it will return then newly minted object key.
     *
     * @param  string $url
     * @param  string|null $name
     *
     * @return string
     * @throws Exception
     */
    public function uploadImageByUrl($url, $name = null)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('A valid url is required to add a new image.');
        }

        $fileUri = Uri\parse($url);
        $pathinfo = League\Flysystem\Util::pathinfo($fileUri['path']);
        // Let's use the user provided file name, if given, otherwise created a default with distinction.
        $pathinfo['filename'] = $name ?: sprintf('%s-%d', $pathinfo['filename'], time());
        // Let's remove all whitespace from file name.
        $pathinfo['filename'] = preg_replace('/\s/', '-', $pathinfo['filename']);
        // Let's remove all non-alphanumeric and hyphen characters from file name.
        $pathinfo['filename'] = preg_replace("/[^A-Za-z0-9\-]/", '', $pathinfo['filename']);
        // Let's combine the cleansed file name with the extension to create the object key.
        $objectKey = strtolower(sprintf('%s.%s', $pathinfo['filename'], $pathinfo['extension']));

        if ($this->container->flysystem->has($objectKey)) {
            throw new Exception('A file with that name already exists.');
        }

        $stream = fopen($url, 'r');
        $this->container->flysystem->writeStream(
            $objectKey,
            $stream
        );
        fclose($stream);

        $this->container->flysystem->setVisibility($objectKey, 'public');

        return $objectKey;
    }
}
