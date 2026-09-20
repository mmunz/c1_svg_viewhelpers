<?php

declare(strict_types=1);

namespace C1\SvgViewHelpers\Tests\Functional\Utilities;

use C1\SvgViewHelpers\Utilities\TypoScript;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The unit test of the same name reaches the fallback without a DI container, so
 * makeInstance() itself fails. Here the container is real and the ConfigurationManager
 * is constructed properly -- it is getConfiguration() that refuses, which is the shape
 * of the failure in production.
 */
final class TypoScriptTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        '../../../c1_svg_viewhelpers',
    ];

    // Outside a frontend request there is no ServerRequest, so TYPO3 v13 throws
    // NoServerRequestGivenException (1721920500) -- one of the three scopes where full
    // TypoScript is unavailable. Rendering an icon there must not escalate to a 500.
    #[Test]
    public function returnsAnEmptyArrayWithoutAServerRequest(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);

        self::assertSame([], TypoScript::getSettings());
    }
}
