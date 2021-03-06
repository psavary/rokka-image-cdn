<?php

namespace Rokka\Client\Core;

use Rokka\Client\Core\DynamicMetadata\DynamicMetadataInterface;

/**
 * Represents the metadata of an image
 */
class SourceImage
{
    /**
     * @var string
     */
    public $organization;

    /**
     * @var string
     */
    public $binaryHash;

    /**
     * @var string
     */
    public $hash;

    /**
     * @var string Original filename that was used when added to service
     */
    public $name;

    /**
     * @var string Original format when it was uploaded (3 letter ending of file)
     */
    public $format;

    /**
     * @var integer Size of image in bytes
     */
    public $size;

    /**
     * @var integer Width of image in pixels
     */
    public $width;

    /**
     * @var integer Height of image in pixels
     */
    public $height;

    /**
     * @var array Static metadata
     */
    public $staticMetadata;

    /**
     * @var DynamicMetadataInterface[] Dynamic metadata
     */
    public $dynamicMetadata;

    /**
     * @var \DateTime When this image was first created
     */
    public $created;

    /**
     * @var string
     */
    public $link;

    /**
     * Constructor.
     *
     * @param string    $organization    Organization
     * @param string    $binaryHash      Binary hash
     * @param string    $hash            Hash
     * @param string    $name            Original name
     * @param string    $format          Format
     * @param integer   $size            File size in bytes
     * @param integer   $width           Width in pixels
     * @param integer   $height          Height in pixels
     * @param array     $staticMetadata  Static metadata
     * @param array     $dynamicMetadata Dynamic metadata
     * @param \DateTime $created         Created at date
     * @param string    $link            Link to the image
     */
    public function __construct(
        $organization,
        $binaryHash,
        $hash,
        $name,
        $format,
        $size,
        $width,
        $height,
        array $staticMetadata,
        array $dynamicMetadata,
        \DateTime $created,
        $link
    ) {
        $this->organization = $organization;
        $this->binaryHash = $binaryHash;
        $this->hash = $hash;
        $this->name = $name;
        $this->format = $format;
        $this->size = $size;
        $this->width = $width;
        $this->height = $height;
        $this->staticMetadata = $staticMetadata;
        $this->dynamicMetadata = $dynamicMetadata;
        $this->created = $created;
        $this->link = $link;
    }

    /**
     * Create a source image from the JSON data.
     *
     * @param string|array $data    JSON data
     * @param boolean      $isArray If the data provided is already an array
     *
     * @return SourceImage
     */
    public static function createFromJsonResponse($data, $isArray = false)
    {
        if (!$isArray) {
            $data = json_decode($data, true);
        }

        if (!isset($data['static_metadata'])) {
            $data['static_metadata'] = [];
        }

        $dynamic_metadata = [];

        // Rebuild the DynamicMetadata associated to the current SourceImage
        if (isset($data['dynamic_metadata']) && !empty($data['dynamic_metadata']['elements'])) {
            foreach ($data['dynamic_metadata']['elements'] as $name => $metadata) {

                $metaClass = 'Rokka\Client\Core\DynamicMetadata\\' . $name;
                if (class_exists($metaClass)) {
                    /** @var DynamicMetadataInterface $metaClass */
                    $meta = $metaClass::createFromJsonResponse($metadata, true);
                    $dynamic_metadata[$name] = $meta;
                }
            }
        }

        return new SourceImage(
            $data['organization'],
            $data['binary_hash'],
            $data['hash'],
            $data['name'],
            $data['format'],
            $data['size'],
            $data['width'],
            $data['height'],
            $data['static_metadata'],
            $dynamic_metadata,
            new \DateTime($data['created']),
            $data['link']
        );
    }
}
