<?php

namespace IllinoisPublicMedia\NprCds\Extensions;

use ExpressionEngine\Service\Addon\Controllers\Extension\AbstractRoute;

class BeforeChannelEntrySave extends AbstractRoute
{
    public function query_cds($entry, $values)
    {
    }
}
