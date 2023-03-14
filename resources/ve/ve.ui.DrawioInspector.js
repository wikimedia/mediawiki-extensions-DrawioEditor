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
	// filename must only contain alphanumeric characters, dashes and underscores
	this.filename = this.filename.replace( /[^a-zA-Z0-9_-]/g, '_' );

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

	// InputWidget for file Name
	this.fileNameInputWidget = new OO.ui.TextInputWidget( {
		validate: function ( value ) {
			if ( value === '' ) {
				return false;
			}
			if ( !value.match( /^[\w,-.\s]+$/ ) ) {
				return false;
			}
			return true;
		}
	} );
	this.fileNameInputWidget.on( 'change', this.onFileNameChange, [], this );
	this.fileNameInputLayout = new OO.ui.FieldLayout( this.fileNameInputWidget, {
		align: 'left',
		label: OO.ui.deferMsg( 'drawioconnector-ve-drawio-tag-name' )
	} );

	// set default values
	this.fileNameInputWidget.setValue( this.filename );

	this.indexLayout.$element.append(
		this.fileNameInputLayout.$element
	);
};

ve.ui.DrawioInspector.prototype.onFileNameChange = function () {
	var actions = this.actions;
	actions.setAbilities( { done: false } );
	this.fileNameInputWidget.getValidity().done( function() {
		actions.setAbilities( { done: true } );
	} );
};

ve.ui.DrawioInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.DrawioInspector.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			this.selectedNode.getAttribute( 'mw' ).attrs;
			this.actions.setAbilities( { done: true } );
		}, this );
};

ve.ui.DrawioInspector.prototype.updateMwData = function ( mwData ) {
	ve.ui.DrawioInspector.super.prototype.updateMwData.call( this, mwData );

	var filename = this.fileNameInputWidget.getValue();
	mwData.attrs.filename = filename;
	// filename must only contain alphanumeric characters, and underscores
	mwData.attrs.filename = mwData.attrs.filename.replace( /[^a-zA-Z0-9_]/g, '_' );
};

/* Registration */
ve.ui.windowFactory.register( ve.ui.DrawioInspector );


