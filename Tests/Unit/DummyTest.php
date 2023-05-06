<?php

namespace C1\SvgViewHelpers\Tests\Unit;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DummyTest extends UnitTestCase
{
    /**
     * @test
     */
    public function dummyTest(): void
    {
        self::assertGreaterThan(0, time());
    }
}
