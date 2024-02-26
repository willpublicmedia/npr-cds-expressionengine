<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Dto\Http;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

class Api_response
{
    public ?object $json = null;

    public ?int $code = null;

    public ?array $messages = null;

    public ?string $raw = null;

    public ?string $url = null;
}
