<?php

namespace IllinoisPublicMedia\NprCds\Libraries\Configuration;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

class Npr_constants
{
    const NPR_PRODUCTION_URL = 'https://content.api.npr.org';

    const NPR_STAGING_URL = 'https://stage-content.api.npr.org';

    // HTTP status code = OK
	const NPR_CDS_STATUS_OK = 200;

	// HTTP status code for successful deletion
	const NPR_CDS_DELETE_OK = 204;

	// Default URL for pulling stories
	const NPR_CDS_VERSION = 'v1';
}
