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

# Usage
TODO

# Privacy
TODO

