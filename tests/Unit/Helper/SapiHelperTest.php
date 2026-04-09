<?php

declare(strict_types=1);

namespace DvsaLoggerTest\Unit\Helper;

use DvsaLogger\Helper\SapiHelper;
use PHPUnit\Framework\TestCase;

class SapiHelperTest extends TestCase
{
    private SapiHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new SapiHelper();
    }

    public function testRequestIsConsoleReturnsBool(): void
    {
        $result = $this->helper->requestIsConsole();
        $this->assertSame(php_sapi_name() === 'cli', $result);
    }
}
