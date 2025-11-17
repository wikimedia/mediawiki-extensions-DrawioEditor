const pluginModules = require( './pluginModules.json' );
mw.loader.using( 'ext.drawioconnector.visualEditor' ).then( () => {
	mw.loader.using( pluginModules );
} );
