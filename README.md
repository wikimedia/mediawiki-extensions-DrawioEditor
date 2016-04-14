# MediaWiki draw.io editor plugin

Please note: This README is still work in progress...

This is a parser plugin that integrates the draw.io flow chart editor into MediaWiki.

# Warnings
Please read carefully before use:
- The actual editor functionality is loaded from draw.io. This code only provides integration.
- Be aware that draw.io is an online service and while this plugin integrates the editor using an iframe and communicates with it only locally in your browser (javascript postMessage), it cannot guarantee that the code loaded from draw.io will not upload any data to foreign servers. This may be a privacy concern. Read the Privacy section for more information. When in doubt, don't use draw.io or this module. You have been warned!
- This plugin is quite new and probably still has bugs, so it may or may not work with your installation.

# Features
- Chart creation and editing
- Inline Editing and javascript uploads on save, you never leave the wiki page
- Image files are stored in the standard wiki file store
- Versioning is provided by the file store
- Draw.io original XML data is stored within the image files, so only one file must be stored per chart
- SVG and PNG support, type can be configured globally and changed on a per-image basis
- Multiple charts per page

# Installation

1. Install the NativeSvgHandler MediaWiki plugin:

   https://www.mediawiki.org/wiki/Extension:NativeSvgHandler

2. Clone this plugin into a folder named DrawioEditor within your wiki's extensions folder:
   ```shell
   cd /where/your/wiki/is/extensions
   git clone https://github.com/mgeb/mediawiki-drawio-editor DrawioEditor
   ```

3. Activate the plugin in LocalSettings.php:

  ```
  require_once "$IP/extensions/DrawioEditor/DrawioEditor.php";
  ```

# Usage
## Add a chart
1. Add the following tag to any wiki page to insert a draw.io chart:
   ```wiki
   {{#drawio:ChartName}}
   ```
  
   `ChartName` must be unique and will be used as the basename for the backing file.
2. Since no chart exists at this point, a placeholder will be shown. Click on Edit at the top right of it.
3. Draw your chart, click Save to save and Exit to leave Edit mode.

## Edit a chart
Each chart will have an Edit button at it's top right. Click it to get back into the editor. Click save to save and Exit to get out of the editor. If a wiki page has multiple charts, only one can be edited at the same time.

## View or revert to old versions
On each save, a new version of the backing file will be added to the wiki file store. You can get there by clicking the chart while you're not editing. There you can look at and revert to old versions like with any file uploaded to the wiki.

## Options ##
The drawio tag supports the following options. They are not recommended to be used under normal circumstances.

* _type_: Set the image type to either svg or png. svg is default unless you set $wgDrawioEditorImageType to png in LocalSettings.php.
  
  ```wiki
  {{#drawio:ChartName|type=png}}
  ```
  
  This example will create a png instead of a svg, which is not recommended.
* _width_: Set a fixed width for the image. Must be a positive integer.
  
  ```wiki
  {{#drawio:ChartName|width=200}}
  ```
* _height_: Set a fixed height for the image. Must be a positive integer.
  
  ```wiki
  {{#drawio:ChartName|height=400}}
  ```  

You may combine options by separating them with additional pipe characters, e.g.:
```wiki
{{#drawio:ChartName|type=png|height=400}}
``` 

# Privacy
TODO

