# C1 SVG viewhelpers

SVG related ViewHelpers for TYPO3 Fluid.

## Requirements

TYPO3 v12 or v13. Tested against PHP 8.2, 8.3 and 8.4.

Note the site set used below needs TYPO3 v13.1; on v12 and v13.0 use the static template.

## Installation

via composer:

```
composer req c1/c1-svg-viewhelpers
```

## Configuration

1. Load the extension's TypoScript:
   - **TYPO3 v13.1 and newer:** add the site set `c1/svg-viewhelpers-default` to your site,
     either in *Site Management > Sites* or as a `dependencies` entry in the site's
     `config.yaml`.
   - **TYPO3 v12:** select *SVG Viewhelpers: Default* under *Include static (from extensions)*
     in your root TypoScript template record.
2. Create a symbols file and CSS (or SCSS or LESS) classes, see below
3. Include the generated S(CSS) or LESS files
4. Configure the presets in the TypoScript constants and or setup, i.e. set
   plugin.tx_c1svgviewhelpers.settings.svg.symbol.presets.default to point to the generated symbol file and add
   more preset keys if needed. For convenience you should always keep the default key which allows you to use
   the svgvh:symbol viewhelper without providing the symbolFile argument.
5. Add basic CSS for the icons to properly display. E.g. if your icons are prefixed with .icon-default:
   ```scss
    .icon-default {
        display: inline-block;
        >svg {
            width: 100%;
            height: 100%;
        }
    }
   ```

## ViewHelpers

### svgvh:symbol

Renders an icon from an SVG symbol file. The icon is wrapped in a span tag as SVG with an xlink:href attribute
referencing an external SVG symbols file.

Using an SVG symbols file has some benefits, e.g.

- the symbol file is cacheable by the browser
- only one HTTP request for all icons in one symbol file
- the icons can be styled using CSS, see note below (but manipulation of the SVG with JavaScript is NOT possible)

See below for more information about SVG symbol files and how to generate them.

#### Usage

```html
<svgvh:symbol identifier='icon-id' />
```

will output something like:

```html
<span class="icon-default icon-default-icon-id icon-default-icon-id-dims">
    <svg role="graphics-symbol">
        <use xlink:href="/path/to/sprite-default.svg?cb=5db10127a446fff1f0d0240086487da1#icon-id" />
    </svg>
</span>
```

Out of the box the `default` preset points at a symbol file shipped with the extension that
contains a single symbol, `placeholder` — a dashed frame with a question mark. So

```html
<svgvh:symbol identifier='placeholder' />
```

renders a visible placeholder before you have generated your own symbol file. It exists so
that a preset which has not been configured yet fails visibly instead of rendering nothing.
Point the preset at your own file as described under Configuration; your own identifiers only
work once you do.

#### Arguments

| attribute   | Description                                                             | Type   | default         | required |
|:------------|:------------------------------------------------------------------------|:-------|:----------------|:---------|
| identifier  | icon id in the symbols file                                             | string |                 | yes      |
| symbolFile  | Preset identifier or path to file, also supports EXT: notation          | string | default         | no       |
| baseClass   | Prefix for the icon's class names                                       | string | see below       | no       |
| class       | Additional CSS class(es), **appended** to the generated ones            | string |                 | no       |
| title       | Tooltip text, rendered on the outer `span`                              | string |                 | no       |
| role        | role attribute on the `svg`; an empty value omits the attribute         | string | graphics-symbol | no       |
| ariaLabel   | Sets the aria-label on the svg tag for accessibility                    | string |                 | no       |
| cacheBuster | Add a cache buster parameter to the symbolFile url                      | bool   | true            | no       |
| preload     | Preload the symbols file by inserting a link rel="preload" tag          | bool   | false           | no       |

`baseClass` is resolved in this order: the argument, then the `baseClass` of the preset
selected by `symbolFile`, then `icon-default`. The site set ships `icon-default` as the
preset value, so that is the effective default until you change it.

`class` does not replace the generated class names, it is appended to them:

```html
<svgvh:symbol identifier='house' class='is-active' />
<!-- class="icon-default icon-default-house icon-default-house-dims is-active" -->
```

`preload` is off unless you ask for it, either per tag or through the preset.

Beyond these, Fluid's tag attribute handling applies: any further attribute — `dir`, for
instance — is passed through to the outer `span`, as are the `data` and `aria` arrays and
`additionalAttributes`.

## Creating SVG symbols file and SCSS

There are many ways to create the needed symbols file and there are plugins for webpack, gulp, grunt etc.

One simple solution is to install the npm package [svg-sprite](https://github.com/svg-sprite/svg-sprite/)
which we can use to create the symbol file from a set of svg icons and also generated an SCSS file
which contains the icon dimensions.

Create a svg-sprite.config.json for svg-sprite:

```json
{
  "shape": {
    "id": {
      "separator": ""
    }
  },
  "mode": {
    "symbol": {
      "dest": "target_path",
      "sprite": "sprite-default.svg",
      "prefix": ".icon-default-%s",
      "render": {
        "scss": {
          "dest": "target_path/_icon-default.scss"
        }
      }
    }
  }
}
```

Then run svg-sprite while providing the path to your svg icons:

```shell
svg-sprite --config svg-sprite.config.json path/to/*.svg
```

If successful, this will generate
* target_path/_icon-default.scss - the file with default dimensions for the icons
* target_path/sprite-default.svg - the symbol file containing all icons

## Notes

To be able to style the icons using CSS you need to prepare the single SVG files:

Assuming you want to be able to style the stroke or fill color of an icon, replace its color value with 'currentValue' to make it use the parents color.

See https://stackoverflow.com/questions/13000682/how-do-i-have-an-svg-image-inherit-colors-from-the-html-document

## Changelog

[CHANGELOG.md](CHANGELOG.md)
