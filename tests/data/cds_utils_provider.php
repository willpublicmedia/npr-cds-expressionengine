<?php declare (strict_types = 1);
require_once __DIR__ . '/../../vendor/autoload.php';

class CdsUtilsProvider
{
    private $image_data = [
        'g-s1-17257' => [
            [
                'expected' => 'gettyimages-1497461299.jpg',
                'height' => 683,
                'href' => 'https=>//npr.brightspotcdn.com/dims3/default/strip/false/crop/911x683+57+0/resize/911x683!/?url=http%3A%2F%2Fnpr-brightspot.s3.amazonaws.com%2Ffd%2F7c%2F3b5faa1c4a54b24e400c55b19854%2Fgettyimages-1497461299.jpg',
                'hrefTemplate' => 'https=>//npr.brightspotcdn.com/dims3/default/strip/false/crop/911x683+57+0/resize/{width}/quality/{quality}/format/{format}/?url=http%3A%2F%2Fnpr-brightspot.s3.amazonaws.com%2Ffd%2F7c%2F3b5faa1c4a54b24e400c55b19854%2Fgettyimages-1497461299.jpg',
                'rels' => [
                    'image-standard',
                    'scalable',
                ],
                'type' => 'image/jpeg',
                'width' => 911]
            ,
            [
                'expected' => 'gettyimages-1497461299.jpg',
                'height' => 412,
                'href' => 'https=>//npr.brightspotcdn.com/dims3/default/strip/false/crop/732x412+122+55/resize/732x412!/?url=http%3A%2F%2Fnpr-brightspot.s3.amazonaws.com%2Ffd%2F7c%2F3b5faa1c4a54b24e400c55b19854%2Fgettyimages-1497461299.jpg',
                'hrefTemplate' => 'https=>//npr.brightspotcdn.com/dims3/default/strip/false/crop/732x412+122+55/resize/{width}/quality/{quality}/format/{format}/?url=http%3A%2F%2Fnpr-brightspot.s3.amazonaws.com%2Ffd%2F7c%2F3b5faa1c4a54b24e400c55b19854%2Fgettyimages-1497461299.jpg',
                'rels' => [
                    'image-wide',
                    'scalable',
                ],
                'type' => 'image/jpeg',
                'width' => 732,
            ],
            [
                'expected' => 'gettyimages-1497461299.jpg',
                'height' => 426,
                'href' => 'https=>//npr.brightspotcdn.com/dims3/default/strip/false/crop/426x426+279+61/resize/426x426!/?url=http%3A%2F%2Fnpr-brightspot.s3.amazonaws.com%2Ffd%2F7c%2F3b5faa1c4a54b24e400c55b19854%2Fgettyimages-1497461299.jpg',
                'hrefTemplate' => 'https=>//npr.brightspotcdn.com/dims3/default/strip/false/crop/426x426+279+61/resize/{width}/quality/{quality}/format/{format}/?url=http%3A%2F%2Fnpr-brightspot.s3.amazonaws.com%2Ffd%2F7c%2F3b5faa1c4a54b24e400c55b19854%2Fgettyimages-1497461299.jpg',
                'rels' => [
                    'image-square',
                    'scalable',
                ],
                'type' => 'image/jpeg',
                'width' => 426,
            ],
            [
                'expected' => 'gettyimages-1497461299.jpg',
                'height' => 683,
                'href' => 'https=>//npr.brightspotcdn.com/dims3/default/strip/false/crop/1024x683+0+0/resize/1024x683!/?url=http%3A%2F%2Fnpr-brightspot.s3.amazonaws.com%2Ffd%2F7c%2F3b5faa1c4a54b24e400c55b19854%2Fgettyimages-1497461299.jpg',
                'hrefTemplate' => 'https=>//npr.brightspotcdn.com/dims3/default/strip/false/crop/1024x683+0+0/resize/{width}/quality/{quality}/format/{format}/?url=http%3A%2F%2Fnpr-brightspot.s3.amazonaws.com%2Ffd%2F7c%2F3b5faa1c4a54b24e400c55b19854%2Fgettyimages-1497461299.jpg',
                'rels' => [
                    'image-slide',
                    'scalable',
                ],
                'type' => 'image/jpeg',
                'width' => 1024,
            ],
            [
                'expected' => 'gettyimages-1497461299.jpg',
                'height' => 471,
                'href' => 'https=>//npr.brightspotcdn.com/dims3/default/strip/false/crop/1024x471+0+67/resize/1024x471!/?url=http%3A%2F%2Fnpr-brightspot.s3.amazonaws.com%2Ffd%2F7c%2F3b5faa1c4a54b24e400c55b19854%2Fgettyimages-1497461299.jpg',
                'hrefTemplate' => 'https=>//npr.brightspotcdn.com/dims3/default/strip/false/crop/1024x471+0+67/resize/{width}/quality/{quality}/format/{format}/?url=http%3A%2F%2Fnpr-brightspot.s3.amazonaws.com%2Ffd%2F7c%2F3b5faa1c4a54b24e400c55b19854%2Fgettyimages-1497461299.jpg',
                'rels' => [
                    'image-brick',
                    'scalable',
                ],
                'type' => 'image/jpeg',
                'width' => 1024,
            ],
            [
                'expected' => 'gettyimages-1497461299.jpg',
                'height' => 683,
                'href' => 'https=>//npr.brightspotcdn.com/dims3/default/strip/false/crop/512x683+256+0/resize/512x683!/?url=http%3A%2F%2Fnpr-brightspot.s3.amazonaws.com%2Ffd%2F7c%2F3b5faa1c4a54b24e400c55b19854%2Fgettyimages-1497461299.jpg',
                'hrefTemplate' => 'https=>//npr.brightspotcdn.com/dims3/default/strip/false/crop/512x683+256+0/resize/{width}/quality/{quality}/format/{format}/?url=http%3A%2F%2Fnpr-brightspot.s3.amazonaws.com%2Ffd%2F7c%2F3b5faa1c4a54b24e400c55b19854%2Fgettyimages-1497461299.jpg',
                'rels' => [
                    'image-vertical',
                    'scalable',
                ],
                'type' => 'image/jpeg',
                'width' => 512,
            ],
            [
                'expected' => 'gettyimages-1497461299.jpg',
                'height' => 683,
                'href' => 'https=>//npr.brightspotcdn.com/dims3/default/strip/false/crop/1024x683+0+0/resize/1024x683!/?url=http%3A%2F%2Fnpr-brightspot.s3.amazonaws.com%2Ffd%2F7c%2F3b5faa1c4a54b24e400c55b19854%2Fgettyimages-1497461299.jpg',
                'hrefTemplate' => 'https=>//npr.brightspotcdn.com/dims3/default/strip/false/crop/1024x683+0+0/resize/{width}/quality/{quality}/format/{format}/?url=http%3A%2F%2Fnpr-brightspot.s3.amazonaws.com%2Ffd%2F7c%2F3b5faa1c4a54b24e400c55b19854%2Fgettyimages-1497461299.jpg',
                'rels' => [
                    'primary',
                    'image-custom',
                    'scalable',
                ],
                'type' => 'image/jpeg',
                'width' => 1024,
            ],
        ],
        'id' => 'g-s1-17257',
        'isRestrictedToAuthorizedOrgServiceIds' => false,
    ];

    public function image_provider($story_ids = [], $format = 'array'): array | \stdClass
    {
        $data = [];

        if (count($story_ids) === 0) {
            $data = $this->image_data;
        } else {
            foreach ($story_ids as $id) {
                $data[$id] = $this->image_data[$id];
            }
        }

        if ($format === 'class') {
            foreach ($data as $id => $document) {
                $data[$id] = $this->to_object($document);
            }
        }

        return $data;
    }

    private function to_object(array $data): \stdClass
    {
        $out = new \stdClass();

        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $out->$k = is_array($v)
                ? $this->to_object($v)
                : $v;
            }
        } else {
            $out = (object) $data;
        }

        return $out;
    }
}
