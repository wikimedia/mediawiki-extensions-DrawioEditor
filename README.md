# MediaWiki draw.io editor plugin

This is a MediaWiki plugin that integrates the draw.io flow chart editor.

# Warnings
**Please read these warnings carefully before use**:
- The actual editor functionality is loaded from draw.io. This code only provides integration.
- Be aware that draw.io is an online service and while this plugin integrates the editor using an iframe and communicates with it only locally in your browser (javascript postMessage), it cannot guarantee that the code loaded from draw.io will not upload any data to foreign servers. **This may be a privacy concern. Read the Privacy section for more information. When in doubt, don't use draw.io or this module. You have been warned!**
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
As mentioned in the Warnings Section above, **there are some privacy concerns when using this plugin (or draw.io in general)**. Carefully read the information below, especially when you're running a wiki in a private environment.

**draw.io may change it's code at any time, so the everything below could be outdated by the time you read it.**

## Referrer Leak
The draw.io editor is loaded within an iframe when you click the Edit link for a chart. At this point your browser loads all draw.io code from draw.io servers. While running in the iframe it has no access to your wiki page contents or any other resources your browser may still send a referrer containing the current wiki page's URL to the draw.io servers, which may or may not be a problem in your environment. The wiki setting $wgReferrerPolicy may help you with this, but only for modern browsers.

## Chart Data Privacy
Obviously the chart data must be passed to the draw.io application. The plugin uses the draw.io embed mode and passes the chart data to draw.io running in an iframe using javascript's postMessage. This part happens locally, the data does not leave your browser. Currently, there does not seem to be any interaction with the draw.io servers while editing, which is good but of course could change at any time without you or your wiki's users noticing. When saving, the file data is prepared (exported) by the iframe and passed back to this plugin (again) using postMessage(). The plugin the safely uploads the new version to the wiki file store. While the data passing happens locally and uploading is safe because it's done by this plugin in the main window context, the draw.io data export code running in the iframe seems to require interaction with draw.io servers in some cases.

One example is when you are using the Safari browser and save a chart which uses type png (see Options above). That process does not seem to be entirely implemented in javascript and needs the draw.io servers to generate the PNG data. This means your chart data leaves the browser. The data sent is SSL-encrypted and the draw.io folks probably don't care about your chart, but of course it's up to you to decide wether you can accept his or not. SVG does not seem to have that problem, at least in Chrome, Firefox and Safari, so I recommend against using that. There may be other circumstances under which data leaves the browser. If this is a concern, you should check wether your use cases trigger this behaviour, or not use this plugin and draw.io at all.

Again, be aware that the draw.io code running in the iframe may change its behavior at any time without you noticing. While that code has no access to your wiki, it may cause your chart data to be leaked. If this is a concern, don't use this plugin.
