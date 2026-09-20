<?php

declare(strict_types=1);

namespace C1\SvgViewHelpers\Tests\Unit\Utilities;

use C1\SvgViewHelpers\Utilities\TypoScript;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TypoScriptTest extends UnitTestCase
{
    // The fallback path instantiates a LogManager, which the framework's tearDown
    // integrity check would otherwise report as leaked framework state.
    protected bool $resetSingletonInstances = true;

    // Reading settings must degrade to defaults rather than take the page down.
    #[Test]
    public function returnsAnEmptyArrayWhenTypoScriptCannotBeRead(): void
    {
        self::assertSame([], TypoScript::getSettings());
    }
}
