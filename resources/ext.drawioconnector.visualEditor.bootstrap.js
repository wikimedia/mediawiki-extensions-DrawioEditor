var pluginModules = require( './pluginModules.json' );
mw.loader.using( 'ext.drawioconnector.visualEditor' ).done( function () {
	mw.loader.using( pluginModules );
} );