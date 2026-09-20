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

    // No request is available here, which is one of the three scopes where TYPO3 v13
    // refuses to hand out full TypoScript. Reading settings must degrade to defaults
    // rather than take the page down with it.
    #[Test]
    public function returnsAnEmptyArrayWhenTypoScriptCannotBeRead(): void
    {
        self::assertSame([], TypoScript::getSettings());
    }
}
