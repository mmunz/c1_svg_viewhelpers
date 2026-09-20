<?php

declare(strict_types=1);

namespace C1\SvgViewHelpers\Tests\Functional\Utilities;

use C1\SvgViewHelpers\Utilities\TypoScript;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The unit test of the same name has no DI container, so makeInstance() itself fails.
 * Here the container is real and getConfiguration() is what refuses -- the shape the
 * failure has in production.
 */
final class TypoScriptTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        '../../../c1_svg_viewhelpers',
    ];

    // No ServerRequest outside a frontend request, so v13 throws 1721920500.
    #[Test]
    public function returnsAnEmptyArrayWithoutAServerRequest(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);

        self::assertSame([], TypoScript::getSettings());
    }
}
