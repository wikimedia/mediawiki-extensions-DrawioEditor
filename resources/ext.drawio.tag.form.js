window.drawioeditor = window.drawioeditor || {};
window.drawioeditor.tag = window.drawioeditor.tag || {};

drawioeditor.tag.Form = function ( config ) {
	drawioeditor.tag.Form.super.call( this, {
		definition: {
			buttons: []
		}
	} );
	this.inspector = config.inspector;
	this.definitions = config.definition.paramDefinitions;
	this.filenameProcessor = new drawioeditor.FilenameProcessor();
};

OO.inheritClass( drawioeditor.tag.Form, mw.ext.forms.standalone.Form );

drawioeditor.tag.Form.prototype.makeItems = function () {
	const me = this;

	return [
		{
			type: 'text',
			name: 'filename',
			required: true,
			label: mw.msg( 'drawioeditor-ve-drawio-tag-name' ),
			help: mw.msg( 'drawioeditor-ve-drawio-tag-name-help' ),
			widget_validate: function ( value ) { // eslint-disable-line camelcase
				return me.filenameProcessor.validateFilename( value );
			}
		},
		{
			type: 'dropdown',
			name: 'editmode',
			value: this.definitions.editmode.default || 'inline',
			options: [
				{ data: 'inline', label: mw.msg( 'drawioeditor-ve-drawio-editmode-label-inline' ) },
				{ data: 'fullscreen', label: mw.msg( 'drawioeditor-ve-drawio-editmode-label-fullscreen' ) }
			],
			label: mw.msg( 'drawioeditor-ve-drawio-editmode-label' ),
			help: mw.msg( 'drawioeditor-ve-drawio-editmode-help' )
		},
		{
			type: 'text',
			name: 'alt',
			label: mw.msg( 'drawioeditor-ve-drawio-alt-label' ),
			help: mw.msg( 'drawioeditor-ve-drawio-alt-help' )
		},
		{
			type: 'dropdown',
			name: 'alignment',
			options: [
				{ data: 'center', label: mw.msg( 'drawioeditor-ve-drawio-alignment-label-center' ) },
				{ data: 'left', label: mw.msg( 'drawioeditor-ve-drawio-alignment-label-left' ) },
				{ data: 'right', label: mw.msg( 'drawioeditor-ve-drawio-alignment-label-right' ) }
			],
			label: mw.msg( 'drawioeditor-ve-drawio-alignment-label' ),
			help: mw.msg( 'drawioeditor-ve-drawio-alignment-help' )
		},
		{
			type: 'dropdown',
			name: 'theme',
			options: [
				{ data: 'min', label: mw.msg( 'drawioeditor-ve-drawio-theme-minimal-label' ) },
				{ data: 'kennedy', label: mw.msg( 'drawioeditor-ve-drawio-theme-classic-label' ) },
				{ data: 'sketch', label: mw.msg( 'drawioeditor-ve-drawio-theme-sketch-label' ) },
				{ data: 'dark', label: mw.msg( 'drawioeditor-ve-drawio-theme-dark-label' ) },
				{ data: 'simple', label: mw.msg( 'drawioeditor-ve-drawio-theme-simple-label' ) }
			],
			label: mw.msg( 'drawioeditor-ve-drawio-theme-label' ),
			help: mw.msg( 'drawioeditor-ve-drawio-theme-help' )
		}
	];
};

drawioeditor.tag.Form.prototype.onRenderComplete = function ( form ) {
	const initValue = form.getItem( 'filename' ).getValue();
	let newValue = initValue;
	if ( !initValue ) {
		newValue = this.filenameProcessor.initializeFilename();
	} else {
		newValue = this.filenameProcessor.sanitizeFilename( initValue );
	}
	setTimeout( () => {
		// Exec in next loop
		if ( initValue !== newValue ) {
			form.getItem( 'filename' ).setValue( newValue );
		}
	}, 1 );
};
