drawioeditor.tag.Definition = function ( spec ) {
	drawioeditor.tag.Definition.super.call( this, spec );
};

OO.inheritClass( drawioeditor.tag.Definition, ext.visualEditorPlus.ui.tag.Definition ); // eslint-disable-line no-undef

drawioeditor.tag.Definition.prototype.getNewElement = function ( inspector, element ) {
	const processorPayload = { processor: new drawioeditor.FilenameProcessor() };
	mw.hook( 'drawioeditor.makeFilenameProcessor' ).fire( processorPayload );

	element.attributes = element.attributes || {};
	element.attributes.mw = element.attributes.mw || {};
	element.attributes.mw.attrs = element.attributes.mw.attrs || {};
	element.attributes.mw.attrs.filename = processorPayload.processor.initializeFilename();
	return element;
};
