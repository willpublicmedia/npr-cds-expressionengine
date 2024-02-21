<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Dto\Http;

use ExpressionEngine\Service\Model\Column\Serialized\Json;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

class Api_response
{
    public ?object $json;

    public ?int $code;

    public ?array $messages;

    public ?string $raw;

    public ?string $url;
}
