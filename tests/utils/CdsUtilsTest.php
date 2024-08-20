<?php declare (strict_types = 1);

use IllinoisPublicMedia\NprCds\Libraries\Utilities\Cds_utils;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../libraries/utilities/cds_utils.php';

final class CdsUtilsTest extends TestCase
{
    private ?Cds_utils $cds_utils;

    public function testGetImageFilename(): void
    {
        $data = [
            'id' => 'g-s1-17257',
            'isRestrictedToAuthorizedOrgServiceIds' => false,
            'height' => 683,
            'href' => 'https=>//npr.brightspotcdn.com/dims3/default/strip/false/crop/911x683+57+0/resize/911x683!/?url=http%3A%2F%2Fnpr-brightspot.s3.amazonaws.com%2Ffd%2F7c%2F3b5faa1c4a54b24e400c55b19854%2Fgettyimages-1497461299.jpg',
            'hrefTemplate' => 'https=>//npr.brightspotcdn.com/dims3/default/strip/false/crop/911x683+57+0/resize/{width}/quality/{quality}/format/{format}/?url=http%3A%2F%2Fnpr-brightspot.s3.amazonaws.com%2Ffd%2F7c%2F3b5faa1c4a54b24e400c55b19854%2Fgettyimages-1497461299.jpg',
            'rels' => [
                'image-standard',
                'scalable',
            ],
            'type' => 'image/jpeg',
            'width' => 911,
        ];

        $out = $this->cds_utils->get_image_url($data);

        $expected = 'gettyimages-1497461299.jpeg';
        $actual = $out['filename'];

        $this->assertEquals($expected, $actual);
    }

    protected function setUp(): void
    {
        $this->cds_utils = new Cds_utils();
    }

    protected function tearDown(): void
    {
        $this->cds_utils = null;
    }
}
