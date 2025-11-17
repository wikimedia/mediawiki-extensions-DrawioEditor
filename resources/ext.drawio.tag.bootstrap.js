window.drawioeditor = window.drawioeditor || {};
window.drawioeditor.tag = window.drawioeditor.tag || {};

mw.hook( 'ext.visualEditorPlus.tags.getTagDefinition' ).add( ( definitionData, tagDefinition ) => {
	if ( definitionData.tagname !== 'bs:drawio' && definitionData.name !== 'drawio' ) {
		return;
	}
	if ( typeof drawioeditor.tag.Definition !== 'function' ) {
		return;
	}

	tagDefinition.instance = new drawioeditor.tag.Definition( definitionData );
} );
