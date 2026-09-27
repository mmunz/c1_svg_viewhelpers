# Changelog

## 2.0.0

Major because of the breaking changes below, several of which alter how an existing
installation behaves without any configuration change on your side. Read that section
before upgrading.

### Breaking

- **TYPO3 v12 is no longer supported.** Supported are TYPO3 v13.4 and v14.3; stay on 1.x
  for v12.
- **Move settings from `plugin.tx_c1svgviewhelpers.svg` to
  `plugin.tx_c1svgviewhelpers.settings.svg`.** Please adapt your TypoScript config.
- **Preloading is off unless something asks for it.** The fallback used to be on, which
  contradicted this file and the README. If you relied on it being enabled without
  configuring it, set `preload = 1` on the preset or pass `preload="1"` on the tag.
- **`preload = false` in TypoScript now disables preloading.** The preset value was
  assigned to a typed bool property without being parsed, so every non-empty string —
  `false` included — turned it *on*. Values other than `1`/`true`/`on`/`yes` now count
  as off; `preload = 0`, as shipped, is unaffected.
- **An explicit `symbolFile` path no longer preloads by itself.** Presets are looked up
  by the `symbolFile` argument, so passing a path matched no preset and fell back to the
  old default of on.
- **`role=""` is no longer rendered.** An explicit empty value reached the markup as
  `role=""`, which is invalid ARIA. Matches how `ariaLabel` and `title` already behaved.

### Added

- TYPO3 v14 support.
- Site set `c1/svg-viewhelpers-default`.
- A placeholder symbol file, shipped as the target of the `default` preset. The preset
  had always pointed at a file that did not exist in the package, so a fresh install
  rendered a silent 404. It contains one symbol, `placeholder`.
- Experimental: preload header tag for faster loading of the symbols file. Off by
  default; enable it in the settings or with a ViewHelper argument.
  Chrome logs "was preloaded using link preload but not used within a few seconds"
  alongside it, see https://bugs.chromium.org/p/chromium/issues/detail?id=1065069

### Fixed

- **The static template is registered again.** It had been replaced by the site set,
  leaving sites that include TypoScript via template records with no way to load the
  presets.
- **Symbol file URLs are correct in Composer mode.** `EXT:` paths now resolve to their
  published `_assets/` location (System Resource API on TYPO3 v14,
  `PathUtility::getPublicResourceWebPath()` on v13); previously an absolute server path
  could end up in the markup. Note this requires the symbol file to live under an extension's
  `Resources/Public`.
- **The preload header is escaped.** It was assembled by string concatenation and
  handed to `PageRenderer::addHeaderData()`, which escapes nothing, so a file name
  containing `&` produced invalid markup — and markup in `symbolFile` reached the page
  head verbatim.
- **A missing or unreadable TypoScript configuration no longer turns an icon into a
  500.** Rendering falls back to the documented defaults and logs a warning. This covers
  CLI and scheduler contexts as well as cached frontend scope.
- Rendering without any TypoScript no longer emits PHP warnings about undefined array
  keys.

### Performance

- Each sprite is hashed once per request rather than once per icon, and twice per icon
  when preloading was on.

### Internal

- Unit test suite restored — it referenced a testing framework that is not a dependency
  and a directory that did not exist, and never ran. Both suites now run in CI against
  TYPO3 13 and 14 on PHP 8.2, 8.3 and 8.4 (testing-framework 9, PHPUnit 11/12), and the
  functional tests fail on deprecations. phpstan analyses against TYPO3 v14.
- `saschaegerer/phpstan-typo3` is actually loaded now; it was installed but inert, so
  no TYPO3 rules or stubs were ever applied.
- `composerUpdate` reports its own exit code, so a failing dependency resolution can no
  longer pass as a green CI step.
- Workflow hardened: least-privilege `permissions`, actions pinned to commit hashes with
  Dependabot and a 7 day cooldown, no credentials persisted in the workspace.
- Dropped two dependencies that did nothing: `phpspec/prophecy` (unused) and
  `sbuerk/typo3-cmscomposerinstallers-testingframework-bridge` (replaced by
  `typo3/testing-framework` since 8.0.0).
- `declare(strict_types=1)` in the production classes.
- Extension version, title and `providesPackages` in `composer.json`, as TYPO3 14.2
  deprecates `ext_emconf.php` as the source of that metadata.

## 1.1.0

TYPO3 v12 support, updated development tooling. No changelog entries were recorded for
this release.

## 1.0.0 - 2022-08-14

- First release
