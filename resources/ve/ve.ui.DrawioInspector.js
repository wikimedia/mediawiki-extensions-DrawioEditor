ve.ui.DrawioInspector = function VeUiAttachmentInspector( config ) {
	// Parent constructor
	ve.ui.DrawioInspector.super.call( this, ve.extendObject( { padded: true }, config ) );
};

/* Inheritance */
OO.inheritClass( ve.ui.DrawioInspector, ve.ui.MWLiveExtensionInspector );

/* Static properties */
ve.ui.DrawioInspector.static.name = 'drawioInspector';
ve.ui.DrawioInspector.static.title = mw.message( 'drawioconnector-ve-drawio-title' ).text();
ve.ui.DrawioInspector.static.modelClasses = [ ve.dm.DrawioNode ];
ve.ui.DrawioInspector.static.dir = 'ltr';

// This tag does not have any content
ve.ui.DrawioInspector.static.allowedEmpty = true;
ve.ui.DrawioInspector.static.selfCloseEmptyBody = false;

/**
 * @inheritdoc
 */
ve.ui.DrawioInspector.prototype.initialize = function () {
	ve.ui.DrawioInspector.super.prototype.initialize.call( this );

	const filenameProcessor = { processor: new drawioeditor.FilenameProcessor() };
	mw.hook( 'drawioeditor.makeFilenameProcessor' ).fire( filenameProcessor );
	this.filenameProcessor = filenameProcessor.processor;

	this.filename = this.filenameProcessor.initializeFilename();

	// remove input field with links in it
	this.input.$element.remove();

	this.createLayout();

	this.form.$element.append(
		this.indexLayout.$element
	);
};

ve.ui.DrawioInspector.prototype.createLayout = function () {
	this.indexLayout = new OO.ui.PanelLayout( {
		expanded: false,
		padded: true
	} );

	// InputWidget for file Name
	this.fileNameInputWidget = new OO.ui.TextInputWidget( {
		validate: this.filenameProcessor.validateFilename
	} );
	this.fileNameInputWidget.on( 'change', this.onFileNameChange, [], this );
	this.fileNameInputLayout = new OO.ui.FieldLayout( this.fileNameInputWidget, {
		align: 'left',
		label: OO.ui.deferMsg( 'drawioconnector-ve-drawio-tag-name' ),
		help: OO.ui.deferMsg( 'drawioconnector-ve-drawio-tag-name-help' )
	} );
	this.fileNameInputWidget.setValue( this.filename );

	// InputWidget for Alt Text
	this.altTextInputWidget = new OO.ui.TextInputWidget();
	this.altTextInputLayout = new OO.ui.FieldLayout( this.altTextInputWidget, {
		align: 'left',
		label: OO.ui.deferMsg( 'drawioconnector-ve-drawio-alt-label' ),
		help: OO.ui.deferMsg( 'drawioconnector-ve-drawio-alt-help' )
	} );

	// InputWidget for alignment
	this.alignmentInputWidget = new OO.ui.DropdownInputWidget( {
		options: [
			{ data: 'center', label: 'center' },
			{ data: 'left', label: 'left' },
			{ data: 'right', label: 'right' }
		]
	} );
	this.alignmentInputLayout = new OO.ui.FieldLayout( this.alignmentInputWidget, {
		align: 'left',
		label: OO.ui.deferMsg( 'drawioconnector-ve-drawio-alignment-label' ),
		help: OO.ui.deferMsg( 'drawioconnector-ve-drawio-alignment-help' )
	} );

	this.indexLayout.$element.append(
		this.fileNameInputLayout.$element,
		this.altTextInputLayout.$element,
		this.alignmentInputLayout.$element
	);
};

ve.ui.DrawioInspector.prototype.onFileNameChange = function () {
	const actions = this.actions;
	actions.setAbilities( { done: false } );
	this.fileNameInputWidget.getValidity().done( () => {
		actions.setAbilities( { done: true } );
	} );
};

ve.ui.DrawioInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.DrawioInspector.super.prototype.getSetupProcess.call( this, data )
		.next( () => {
			const attributes = this.selectedNode.getAttribute( 'mw' ).attrs;
			if ( attributes.filename ) {
				this.fileNameInputWidget.setValue( attributes.filename );
			}
			if ( attributes.alt ) {
				this.altTextInputWidget.setValue( attributes.alt );
			}
			if ( attributes.alignment ) {
				this.alignmentInputWidget.setValue( attributes.alignment );
			}
			this.actions.setAbilities( { done: true } );
		} );
};

ve.ui.DrawioInspector.prototype.updateMwData = function ( mwData ) {
	ve.ui.DrawioInspector.super.prototype.updateMwData.call( this, mwData );

	const filename = this.fileNameInputWidget.getValue();
	const altText = this.altTextInputWidget.getValue();
	const alignment = this.alignmentInputWidget.getValue();

	// Get rid of the symbols which should not be in the filename
	mwData.attrs.filename = this.filenameProcessor.sanitizeFilename( filename );
	mwData.attrs.alt = altText;
	mwData.attrs.alignment = alignment;
};

/* Registration */
ve.ui.windowFactory.register( ve.ui.DrawioInspector );
