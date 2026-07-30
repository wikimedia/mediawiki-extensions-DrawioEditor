window.drawioeditor = window.drawioeditor || {};
window.drawioeditor.tag = window.drawioeditor.tag || {};

mw.hook( 'ext.visualEditorPlus.tags.getTagDefinition' ).add( ( definitionData, tagDefinition ) => {
	const isDrawio = definitionData.tagname === 'bs:drawio' || definitionData.name === 'drawio';
	const isDrawioSketch = definitionData.tagname === 'bs:drawio-sketch' ||
		definitionData.name === 'drawio-sketch';
	if ( !isDrawio && !isDrawioSketch ) {
		return;
	}
	if ( typeof drawioeditor.tag.Definition !== 'function' ) {
		return;
	}

	tagDefinition.instance = new drawioeditor.tag.Definition( definitionData );
} );
