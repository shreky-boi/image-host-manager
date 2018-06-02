<?php

namespace App\Models;

class Image
{
    /**
     * Basename (aka ObjectKey)
     *
     * @var string
     */
    public $basename;

    /**
     * File extension
     *
     * @var string
     */
    public $extension;

    /**
     * File name, without extension
     *
     * @var string
     */
    public $filename;

    /**
     * Timestamp of last modified
     *
     * @var string
     */
    public $timestamp;

    /**
     * File type
     *
     * @var string
     */
    public $type;

    /**
     * Slug for image
     *
     * @var string
     */
    public $slug;

    /**
     * Description of image (aka Alt Text)
     *
     * @var string
     */
    public $description;

    /**
     * Tags
     *
     * @var array
     */
    public $tags = [];

    /**
     * Creates a new image object.
     *
     * @param array  $attributes
     */
    public function __construct(array $attributes = array())
    {
        array_walk($attributes, function ($value, $key) {
            $setter = sprintf('set%s', ucfirst($key));
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            } elseif (property_exists(get_class($this), $key)) {
                $this->$key = $value;
            }
        });

        if ($this->filename && $this->extension) {
            $this->slug = sprintf('/%s/%s', rawurlencode($this->filename), $this->extension);
        }
    }

    /**
     * Attempts to coerce an array of attributes (ideally from the Flysystem package)
     * into appropriate attributes to new up an Image.
     *
     * @param  array   $attributes
     *
     * @return Image
     */
    public static function fromFlysystem(array $attributes)
    {
        if (isset($attributes['metadata']) && is_array($attributes['metadata'])) {
            array_walk($attributes['metadata'], function ($value, $key) use (&$attributes) {
                $attributes[$key] = $value;
            });
        }

        return new static($attributes);
    }

    /**
     * Builds an object key from a filename and extension.
     *
     * @param  string  $name
     * @param  string  $extension
     *
     * @return string
     */
    public static function getObjectKey($name, $extension)
    {
        return sprintf('%s.%s', $name, $extension);
    }

    /**
     * Converts tags to string.
     *
     * @return string
     */
    public function getTagsString()
    {
        return implode(',', $this->tags);
    }

    /**
     * Checks if the image is a file.
     *
     * @return boolean
     */
    public function isFile()
    {
        return $this->type == 'file';
    }

    /**
     * Checks if the image is an....image?
     *
     * @return boolean
     */
    public function isImage()
    {
        return $this->isFile() && in_array($this->extension, ['png', 'jpg', 'jpeg', 'gif']);
    }

    /**
     * Attempts to format the given input into proper looking tags and sets them
     * accordingly.
     *
     * @param mixed
     *
     * @return Image
     */
    public function setTags($tags)
    {
        if (!is_array($tags)) {
            $tags = array_values(
                array_filter(
                    array_map('strtolower', array_map('trim', explode('|', preg_replace('/[\n\r,]/', '|', $tags))))
                )
            );
        }

        $this->tags = $tags;

        return $this;
    }
}
