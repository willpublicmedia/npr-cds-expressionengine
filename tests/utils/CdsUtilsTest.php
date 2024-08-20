<?php declare (strict_types = 1);

use IllinoisPublicMedia\NprCds\Libraries\Utilities\Cds_utils;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../libraries/utilities/cds_utils.php';

final class NprCdsUtilsTest extends TestCase
{
    private ?Cds_utils $cds_utils;

    public function testSanityIsSane(): void
    {
        $x = 2;
        $this->assertEquals(1, $x);
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
