<?php

declare(strict_types=1);

namespace C1\SvgViewHelpers\ViewHelpers;

use C1\SvgViewHelpers\Utilities\TypoScript;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

class SymbolViewHelper extends AbstractTagBasedViewHelper
{
    protected string $symbolsFile = '';
    protected string $baseClass = '';
    protected bool $preload = false;
    protected array $settings = [];

    /*
     * Cache buster per absolute file name, see getCacheBuster().
     * @var array<string, string>
     */
    private static array $cacheBusters = [];

    /**
     * @var PageRenderer
     */
    protected PageRenderer $pageRenderer;
    /**
     * @param PageRenderer $pageRenderer
     */
    public function injectPageRenderer(PageRenderer $pageRenderer): void
    {
        $this->pageRenderer = $pageRenderer;
    }

    // Initialize the viewhelper
    public function initialize(?array $settings = null): void
    {
        parent::initialize();
        $this->settings = is_array($settings) ? $settings : $this->getTypoScriptSettings();
        $this->setSymbolFile();
        $this->setBaseClass();
        $this->setPreload();
    }

    // Return the TypoScript settings for this extension
    protected function getTypoScriptSettings(): array
    {
        return TypoScript::getSettings();
    }

    // Initialize viewhelper arguments
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('class', 'string', 'CSS class(es) for this element');
        $this->registerArgument('title', 'string', 'Tooltip text of element');
        $this->registerArgument('identifier', 'string', 'the identifier of the Icon as given in the svg-sprite', true);
        $this->registerArgument('symbolFile', 'string', 'Path to a symbolfile or key from typoscript presets to use.', false, 'default');
        $this->registerArgument('baseClass', 'string', 'base css classname', false);
        $this->registerArgument('role', 'string', 'the role-attribute, default is graphics-symbol', false, 'graphics-symbol');
        $this->registerArgument('ariaLabel', 'string', 'the aria-label attribute which describes the svg image', false);
        $this->registerArgument('cacheBuster', 'boolean', 'Add a cache buster', false, true);
        $this->registerArgument('preload', 'boolean', 'Add preload tag', false);
    }

    // Get a tagBuilder instance
    private function getTagBuilder(): TagBuilder
    {
        /** @var TagBuilder */
        return GeneralUtility::makeInstance('TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder');
    }

    // TypoScript is not guaranteed to be loaded, so presets may be missing entirely
    private function getPresets(): array
    {
        return $this->settings['svg']['symbol']['presets'] ?? [];
    }

    /*
     * Set the symbolFile, either from
     * - TypoScript presets
     * - symbolFile argument
     */
    private function setSymbolFile(): void
    {
        $presets = $this->getPresets();
        if (
            $this->hasArgument('symbolFile')
            && isset($presets[$this->arguments['symbolFile']])
            && array_key_exists('file', $presets[$this->arguments['symbolFile']])
        ) {
            $this->symbolsFile = (string)$presets[$this->arguments['symbolFile']]['file'];
        } else {
            // No preset of that name: treat the argument as a path. It always has a
            // value -- registerArgument() gives it the default 'default'.
            $this->symbolsFile = (string)$this->arguments['symbolFile'];
        }
    }

    /*
     * Set the baseClass either from
     * - baseClass argument
     * - TypoScript Presets
     * - fallback to "icon-default" if no base class could be set from the two above
     */
    private function setBaseClass(): void
    {
        // Cast both: Fluid does not coerce scalars, so a "string" argument can arrive as
        // an int, and a preset value comes from TypoScript untyped.
        if ($this->arguments['baseClass']) {
            $this->baseClass = (string)$this->arguments['baseClass'];
        } else {
            $this->baseClass = (string)$this->getPresetFromSettings('baseClass', 'icon-default');
        }
    }

    /*
     * Set the preload argument either from (in this order)
     * - preload viewhelper argument
     * - TypoScript Presets
     * - fallback to off if the value was not set by the 2 options above
     */
    private function setPreload(): void
    {
        if ($this->hasArgument('preload')) {
            $this->preload = $this->toBoolean($this->arguments['preload']);
        } else {
            // Also through toBoolean(): TypoScript hands over strings, and assigning
            // one to the bool property directly made every non-empty value true, so
            // "preload = false" switched preloading on.
            $this->preload = $this->toBoolean($this->getPresetFromSettings('preload', false));
        }
    }

    /**
     * we cant set mixed here for $key and return type because no support for it in PHP 7.4
     * @phpstan-ignore-next-line
     */
    private function getPresetFromSettings(string $key, $default)
    {
        $presets = $this->getPresets();
        if (
            isset($presets[$this->arguments['symbolFile']])
            && array_key_exists($key, $presets[$this->arguments['symbolFile']])
        ) {
            return $presets[$this->arguments['symbolFile']][$key];
        }
        return $default;
    }

    /**
     * we cant set mixed here because no support for it in PHP 7.4
     * @phpstan-ignore-next-line
     */
    private function toBoolean($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /*
     * Get css class names based on
     * - baseClass argument
     * - optional additional class viewhelper argument
     */
    private function getCssClassNames(): string
    {
        $classNames = [
            $this->baseClass,
            $this->baseClass . '-' . $this->arguments['identifier'],
            $this->baseClass . '-' . $this->arguments['identifier'] . '-dims',
        ];

        if ($this->hasArgument('class') && $this->arguments['class'] !== '') {
            $classNames[] = $this->arguments['class'];
        }
        return implode(' ', $classNames);
    }

    // Get absolute file name and path of the symbolFile
    private function getAbsoluteFilename(): string
    {
        return GeneralUtility::getFileAbsFileName($this->symbolsFile);
    }

    /*
     * Get the resolved web path to the symbolFile.
     *
     * EXT: paths go through getPublicResourceWebPath(), which is the only one that maps
     * an extension resource to its published _assets/ location. getAbsoluteWebPath()
     * alone returns the absolute server path for anything it cannot place below the
     * public directory, which in Composer mode is every extension in vendor/.
     */
    private function getSymbolFilePath(): string
    {
        if (PathUtility::isExtensionPath($this->symbolsFile)) {
            return PathUtility::getPublicResourceWebPath($this->symbolsFile);
        }
        return PathUtility::getAbsoluteWebPath($this->getAbsoluteFilename());
    }

    // Get public path of the symbolFile
    private function getSvgPublicFile(): string
    {
        if ($this->symbolsFile === '') {
            return $this->symbolsFile;
        }
        $path = $this->getSymbolFilePath();
        return $path !== '' ? $path : $this->symbolsFile;
    }

    // Return cache buster enabled or not
    private function cacheBusterEnabled(): bool
    {
        return $this->arguments['cacheBuster'] ? true : false;
    }

    /*
     * Get cache buster string.
     *
     * Memoised per absolute file name, because this runs once for the <use> tag and
     * again for the preload header, for every icon on the page. Hashing a sprite is
     * proportional to its size, so without this a page reads the same file dozens of
     * times per request.
     *
     * Static rather than per-instance: Fluid hands out one ViewHelper instance per tag,
     * so an instance cache would not help across the icons of a page. The settings are
     * deliberately NOT cached this way -- they differ between requests, and functional
     * tests run several requests in one process.
     *
     * The trade-off is that a sprite replaced while the process is alive keeps its old
     * hash. That is a non-issue for a web request and acceptable for a worker, since
     * the value is only a cache buster.
     */
    private function getCacheBuster(): string
    {
        if ($this->symbolsFile === '') {
            return '';
        }
        $absoluteFilename = $this->getAbsoluteFilename();
        if (!array_key_exists($absoluteFilename, self::$cacheBusters)) {
            self::$cacheBusters[$absoluteFilename] = file_exists($absoluteFilename)
                ? '?cb=' . md5_file($absoluteFilename)
                : '';
        }
        return self::$cacheBusters[$absoluteFilename];
    }

    private function getSymbolFileURL(): string
    {
        $url = $this->getSvgPublicFile();
        if ($this->cacheBusterEnabled()) {
            $url .= $this->getCacheBuster();
        }
        return $url;
    }

    // Build the use tag
    private function buildUseTag(): string
    {
        $xlink = $this->getSymbolFileURL() . '#' . $this->arguments['identifier'];
        $tagBuilder = $this->getTagBuilder();
        $tagBuilder->setTagName('use');
        $tagBuilder->addAttribute('xlink:href', $xlink);
        return $tagBuilder->render();
    }

    // Build the SVG tag
    private function buildSvgTag(): string
    {
        $tagBuilder = $this->getTagBuilder();
        $tagBuilder->setTagName('svg');

        if ($this->hasArgument('ariaLabel') && $this->arguments['ariaLabel'] != '') {
            $tagBuilder->addAttribute('aria-label', ($this->arguments['ariaLabel']));
        }

        if ($this->hasArgument('role') && $this->arguments['role'] != '') {
            $tagBuilder->addAttribute('role', ($this->arguments['role']));
        }
        $tagBuilder->setContent($this->buildUseTag());

        return $tagBuilder->render();
    }

    // Build the outer span tag
    private function buildTag(): string
    {
        $this->tag->setTagName('span');
        if ($this->hasArgument('title') && $this->arguments['title'] != '') {
            $this->tag->addAttribute('title', $this->arguments['title']);
        }
        $this->tag->addAttribute('class', $this->getCssClassNames());
        $this->tag->setContent($this->buildSvgTag());

        return $this->tag->render();
    }

    // Built through the TagBuilder rather than concatenated: addHeaderData() does no
    // escaping of its own, and nothing constrains the symbolFile argument to a path.
    private function addPreloadHeader(): void
    {
        $tagBuilder = $this->getTagBuilder();
        $tagBuilder->setTagName('link');
        $tagBuilder->addAttribute('rel', 'preload');
        $tagBuilder->addAttribute('href', $this->getSymbolFileURL());
        $tagBuilder->addAttribute('as', 'image');
        $tagBuilder->addAttribute('fetchpriority', 'high');
        $this->pageRenderer->addHeaderData($tagBuilder->render());
    }

    // Render the viewhelper output
    public function render(): string
    {
        if ($this->preload) {
            $this->addPreloadHeader();
        }
        return $this->buildTag();
    }
}
