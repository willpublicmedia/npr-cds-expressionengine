<?php declare (strict_types = 1);

use IllinoisPublicMedia\NprCds\Libraries\Utilities\Cds_utils;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../libraries/utilities/cds_utils.php';

final class NprCdsUtilsTest extends TestCase
{
    private ?Cds_utils $cds_utils;

    private ?CdsUtilsProvider $cds_utils_provider;

    public function testSanityIsSane(): void
    {
        $x = 2;
        $this->assertEquals(1, $x);
    }

    protected function setUp(): void
    {
        $this->cds_utils = new Cds_utils();
        $this->cds_utils_provider = new CdsUtilsProvider();
    }

    protected function tearDown(): void
    {
        $this->cds_utils = null;
        $this->cds_utils_provider = null;
    }

    private function image_provider(): array
    {
        $array_data = $this->cds_utils_provider->image_provider();
        $object_data = $this->cds_utils_provider->image_provider([], 'class');
        $out = array_merge($array_data, $object_data);
        return $out;
    }
}
