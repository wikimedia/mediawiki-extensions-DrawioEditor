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
	this.filename = mw.config.get( 'wgTitle' ) + "-" + ( Math.floor( Math.random() * 100000000) + 1 );

	// remove input field with links in it
	this.input.$element.remove();

	this.createLayout();

	this.form.$element.append(
		this.indexLayout.$element
	);
};

ve.ui.DrawioInspector.prototype.createLayout = function ( ) {
	this.indexLayout = new OO.ui.PanelLayout( {
		expanded: false,
		padded: true
	} );

	// 'Used with Drawio tag' Label
	this.insertLabel = new OO.ui.LabelWidget( {
		label: OO.ui.deferMsg( 'drawioconnector-tag-drawio-desc' )
	} );
	
	// InputWidget for file Name
	this.fileNameInputWidget = new OO.ui.TextInputWidget();
	this.fileNameInputLayout = new OO.ui.FieldLayout( this.fileNameInputWidget, {
		align: 'left',
		label: 'File Name'
	} )

	// InputWidget for file extension
	this.savingFormatWidget = new OO.ui.ButtonSelectWidget( {
		items: [
			new OO.ui.ButtonOptionWidget( {
				data: 'png',
				label: 'png'
			} ),
			new OO.ui.ButtonOptionWidget( {
				data: 'svg',
				label: 'svg'
			} )
		]
	} );
	this.savingFormatLayout = new OO.ui.FieldLayout( this.savingFormatWidget, {
		align: 'left',
		label: 'File Format'
	} )

	// set default values
	this.fileNameInputWidget.setValue( this.filename );
	this.fileNameInputWidget.setDisabled( true );
	this.savingFormatWidget.selectItem( this.savingFormatWidget.items[0] );

	this.indexLayout.$element.append( 
		this.insertLabel.$element,
		this.fileNameInputLayout.$element,
		this.savingFormatLayout.$element 
	);
}

ve.ui.DrawioInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.DrawioInspector.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			this.selectedNode.getAttribute( 'mw' ).attrs;
			this.actions.setAbilities( { done: true } );
		}, this );
}	

ve.ui.DrawioInspector.prototype.updateMwData = function ( mwData ) {
	ve.ui.DrawioInspector.super.prototype.updateMwData.call( this, mwData );
	
	var filename = this.fileNameInputWidget.getValue();
	if ( filename.match( /[a-zA-Z0-9\s_\\.\-\(\):]/ ) ) {
		mwData.attrs.filename = filename;
	}
	mwData.attrs.type = this.savingFormatWidget.findSelectedItem().getData();
}

/* Registration */
ve.ui.windowFactory.register( ve.ui.DrawioInspector );


