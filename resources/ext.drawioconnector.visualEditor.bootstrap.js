const pluginModules = require( './pluginModules.json' );
mw.loader.using( 'ext.drawioconnector.visualEditor' ).done( () => {
	mw.loader.using( pluginModules );
} );
