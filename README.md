# C1 SVG viewhelpers

SVG related ViewHelpers for TYPO3 Fluid.

## Installation

via composer:

```
composer req c1/c1-svg-viewhelpers
```

## Configuration

1. Include the static TypoScript setup and constants
2. Create a symbols file and CSS (or SCSS or LESS) classes, see below
3. Include the generated S(CSS) or LESS files
4. Configure the presets in the TypoScript constants and or setup, i.e. set
   plugin.tx_c1svgviewhelpers.svg.symbol.presets.default to point to the generated symbol file and add
   more preset keys if needed. For convenience you should always keep the default key which allows you to use 
   the svgvh:symbol viewhelper without providing the symbolFile argument.
5. Add basic CSS for the icons to properly display. E.g. if your icons are prefixed with .icon-default:
   ```css
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

- the symbol file is cachable by the browser
- only one HTTP request for all icons in one symbol file
- the icons can be styled using CSS (but manipulation of the SVG with JavaScript is NOT possible)

See below for more information about SVG symbol files and how to generate them.

#### Usage

```fluid
<svgvh:symbol identifier='icon-id' />
```

will output something like:

```
<span class="icon-default icon-default-icon-id icon-default-icon-id-dims">
    <svg role="graphics-symbol">
        <use xlink:href="/path/to/sprite-default.svg?cb=5db10127a446fff1f0d0240086487da1#icon-id"></use>
    </svg>
</span>
```

#### Arguments

| attribute      | Description                                                    | Type      | default         | required    |
|:---------------|:---------------------------------------------------------------| :---      |:----------------| :---        |
| identifier     | icon id in the symbols file                                    | string    |                 | yes         |
| symbolFile     | Preset identifier or path to file, also supports EXT: notation | string    | default         | no          |
| baseClass      | Prefix for the icon's class names                              | string    | icon-default    | no          |
| role           | role for accessibility                                         | string    | graphics-symbol | no          |
| ariaLabel      | Sets the aria-label on the svg tag for accessibility           | string    |                 | no          |
| cacheBuster    | Add a cache buster parameter to the symbolFile url             | bool      | true            | no          |

In addition all universal tag attributes are supported:

class, dir, id, lang, style, title, accesskey, tabindex and onclick

#### Creating SVG symbols file and SCSS

There are many ways to create the needed symbols file and there are plugins for webpack, gulp, grunt etc.

One simple solution is to the npm package [svg-sprite](https://github.com/svg-sprite/svg-sprite/)
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
