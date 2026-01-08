function DrawioEditor( id, filename, editMode, type, updateHeight, updateWidth,
	updateMaxWidth, baseUrl, latestIsApproved, imageURL, theme ) {
	const that = this;

	this.id = id;
	this.filename = filename;
	this.editMode = editMode;
	this.imgType = type;
	this.updateHeight = updateHeight;
	this.updateWidth = updateWidth;
	this.updateMaxWidth = updateMaxWidth;
	this.baseUrl = baseUrl;
	this.latestIsApproved = latestIsApproved;
	this.theme = theme;

	// Could be 'en', 'fr', 'de-formal', 'zh-hant', ...
	const currentUserLanguage = mw.user.options.get( 'language', 'en' ).split( '-' );
	this.language = currentUserLanguage[ 0 ];

	if ( this.imgType === 'svg' ) {
		this.imgMimeType = 'image/svg+xml';
	} else if ( this.imgType === 'png' ) {
		this.imgMimeType = 'image/png';
	} else {
		throw new Error( 'unkown file type' );
	}

	this.imageBox = $( '#drawio-img-box-' + id );
	this.image = $( '#drawio-img-' + id );
	this.imageURL = imageURL || undefined;
	this.imageHref = $( '#drawio-img-href-' + id );
	this.placeholder = $( '#drawio-placeholder-' + id );

	this.iframeBox = $( '#drawio-iframe-box-' + id );
	this.iframeBox.resizable( {
		handles: 's',
		distance: 0,
		start: function () {
			that.showOverlay();
		},
		stop: function () {
			$( this ).css( 'width', '' );
			that.hideOverlay();
		}
	} );
	this.iframeBox.resizable( 'enable' );

	this.iframeOverlay = $( '#drawio-iframe-overlay-' + id );
	this.iframeOverlay.hide();

	const params = new URLSearchParams( {
		embed: '1',
		proto: 'json',
		spin: '1',
		analytics: '0',
		picker: '0',
		lang: this.language,
		stealth: '1',
		ui: this.theme,
		libraries: '1',
		configure: '1',
		splash: '0'
	} );

	// ERM43219 Deactivate clibs
	// Append clibs manually so semicolons remain unencoded
	// const customShapeLibraries = require( './customShapeLibraries.json' );
	// const clibsParam = `&clibs=${ customShapeLibraries.customShapeLibraries }`;
	// const iframeUrl = `${ this.baseUrl }/?${ params.toString() }${ clibsParam }`;
	const iframeUrl = `${ this.baseUrl }/?${ params.toString() }`;

	this.iframe = $( '<iframe>' )
		.attr( {
			src: iframeUrl,
			id: `drawio-iframe-${ id }`
		} )
		.addClass( 'DrawioEditorIframe' );
	this.iframe.appendTo( this.iframeBox );
	this.fullscreenDialog = new FullscreenDialog( this.iframe );

	this.iframe.on( 'load', () => {
		this.iframeWindow = this.iframe.prop( 'contentWindow' );
	} );

	this.show();
}

DrawioEditor.prototype.destroy = function () {
	this.iframe.remove();
};

DrawioEditor.prototype.show = function () {
	this.imageBox.hide();

	if ( this.editMode === 'inline' ) {
		this.iframeBox.height( Math.max( this.imageBox.height() + 100, 800 ) );
		this.iframeBox.show();
	} else if ( this.editMode === 'fullscreen' ) {
		this.fullscreenDialog.show( this.iframe );
	} else {
		throw new Error( 'unknown edit mode' );
	}

	$( '#approved-displaywarning' ).remove();
	if ( !this.latestIsApproved ) {
		const msg = mw.message( 'drawioeditor-approved-editwarning' ).escaped();
		$( '#bodyContent' ).before( '<p id="warningmsg" class="successbox">' + msg + '</p>' );
	}
};

DrawioEditor.prototype.hide = function () {
	this.iframeBox.hide();
	this.imageBox.show();
	this.fullscreenDialog.close();
};

DrawioEditor.prototype.showOverlay = function () {
	this.iframeOverlay.show();
};

DrawioEditor.prototype.hideOverlay = function () {
	this.iframeOverlay.hide();
};

DrawioEditor.prototype.updateImage = function ( imageinfo ) {
	this.imageURL = imageinfo.url + '?ts=' + imageinfo.timestamp;
	this.image.attr( 'src', this.imageURL );
	this.imageHref.attr( 'href', imageinfo.descriptionurl );
	if ( this.updateHeight ) {
		this.image.css( 'height', imageinfo.height );
	}
	if ( this.updateWidth ) {
		this.image.css( 'width', imageinfo.width );
	}
	if ( this.updateMaxWidth ) {
		this.image.css( 'max-width', imageinfo.width );
	}
	if ( this.placeholder ) {
		this.placeholder.hide();
		this.image.show();
	}
};

DrawioEditor.prototype.sendMsgToIframe = function ( data ) {
	this.iframeWindow.postMessage( JSON.stringify( data ), this.baseUrl );
};

DrawioEditor.prototype.showDialog = function ( title, message ) {
	this.hideSpinner();
	this.sendMsgToIframe( {
		action: 'dialog',
		title: title,
		message: message,
		button: 'Discard',
		modified: true
	} );
};

DrawioEditor.prototype.showSpinner = function () {
	this.iframeBox.resizable( 'disable' );
	this.showOverlay();
	this.sendMsgToIframe( {
		action: 'spinner',
		show: true
	} );
};

DrawioEditor.prototype.hideSpinner = function () {
	this.iframeBox.resizable( 'enable' );
	this.hideOverlay();
	this.sendMsgToIframe( {
		action: 'spinner',
		show: false
	} );
};

DrawioEditor.prototype.normalizeDataURLForDrawio = function ( dataURL ) {
	const parts = dataURL.match( /^data:([^;]+);base64,(.+)$/ );
	if ( !parts ) {
		return dataURL;
	}

	const binaryString = atob( parts[ 2 ] );
	const bytes = Uint8Array.from( binaryString, ( char ) => char.charCodeAt( 0 ) );
	const decoder = new TextDecoder( 'utf-8' );
	const svgText = decoder.decode( bytes );

	// Convert non-ASCII characters to XML entities, as DrawIO does not handle
	// raw UTF-8 characters (ü) well, but only escaped char references (&#xFC;)
	const normalized = svgText.replace( /[\u0080-\uFFFF]/g, ( char ) => (
		'&#x' + char.charCodeAt( 0 ).toString( 16 ).toUpperCase() + ';'
	) );

	const utf8Bytes = new TextEncoder().encode( normalized );
	const newBinaryString = String.fromCharCode( ...utf8Bytes );
	const newBase64 = btoa( newBinaryString );
	return 'data:' + parts[ 1 ] + ';base64,' + newBase64;
};

DrawioEditor.prototype.downloadFromWiki = function () {
	const that = this;
	const xhr = new XMLHttpRequest();
	xhr.onreadystatechange = function () {
		if ( this.readyState == 4 ) { // eslint-disable-line eqeqeq
			if ( this.status == 200 ) { // eslint-disable-line eqeqeq
				const res = this.response;
				const fr = new FileReader();
				fr.onload = function ( ev ) {
					const normalizedDataURL = that.normalizeDataURLForDrawio( ev.target.result );
					that.loadImageFromDataURL( res.type, normalizedDataURL );
				};
				fr.readAsDataURL( res );
			} else {
				that.showDialog( 'Load failed',
					'HTTP request to fetch image failed: ' + this.status +
			'<br>Image: ' + that.imageURL );
			}
		}
	};
	xhr.onload = function () {

	};
	xhr.open( 'GET', this.imageURL );
	xhr.responseType = 'blob';
	xhr.send();
};

DrawioEditor.prototype.loadImageFromDataURL = function ( type, dataurl ) {
	if ( type != this.imgMimeType ) { // eslint-disable-line eqeqeq
		this.showDialog( 'Load failed',
			'Invalid mime type when loading image from wiki:' +
		'<br>Actual: ' + type + ' / Expected: ' + this.imgMimeType +
		'<br>Image: ' + this.imageURL );
		return;
	}
	if ( this.imgType === 'svg' ) {
		this.sendMsgToIframe( { action: 'load', xml: dataurl } );
	} else if ( this.imgType === 'png' ) {
		this.sendMsgToIframe( { action: 'load', xmlpng: dataurl } );
	}
};

DrawioEditor.prototype.loadImage = function () {
	if ( this.imageURL === undefined ) {
		// just load without data if there's no current image
		this.sendMsgToIframe( { action: 'load' } );
		return;
	}
	// fetch image from wiki. it must contain both image data and
	// draw.io xml data. see DrawioEditor.saveCallback()
	this.downloadFromWiki();
};

/**
 * Upload the Drawio diagram to the wiki using the custom API module.
 *
 * @param {Blob} blob - The diagram file blob to upload.
 */
DrawioEditor.prototype.uploadToWiki = async function ( blob ) {
	const formData = new FormData();
	formData.append( 'action', 'drawioeditor-save-diagram' );
	formData.append( 'token', mw.user.tokens.get( 'csrfToken' ) );
	formData.append( 'format', 'json' );
	formData.append( 'file', blob, this.filename );

	try {
		// Perform the upload request
		const response = await fetch( mw.util.wikiScript( 'api' ), {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		} );

		if ( !response.ok ) {
			throw new Error( `HTTP ${ response.status } - ${ response.statusText }` );
		}

		const data = await response.json();

		if ( data.upload ) {
			// Upload succeeded, update image
			this.updateImage( data.upload.imageinfo );
			this.hideSpinner();
			return;
		}

		this.hideSpinner();

		if ( data.error ) {
			// Known API error
			this.showDialog(
				'Save failed',
				`Upload error: ${ data.error.info }`
			);
		} else {
			// Unexpected or malformed API response
			this.showDialog(
				'Save failed',
				'Unexpected response. See console for details.'
			);
			console.error( '[DrawioEditor] Unexpected upload response:', data ); // eslint-disable-line no-console
		}
	} catch ( error ) {
		// Network or fatal error
		this.hideSpinner();
		this.showDialog(
			'Save failed',
			`Upload failed: ${ error.message }. See console for details.`
		);
		console.error( '[DrawioEditor] Upload error:', error ); // eslint-disable-line no-console
	}
};

DrawioEditor.prototype.save = function ( datauri ) {
	// the data in the data uri contains both the image _and_ draw.io XML, see
	// this.saveCallback()

	const parts = /^data:([^;,=]+\/[^;,=]+)?((?:;[^;,=]+=[^;,=]+)+)?(?:;(base64))?,(.+)$/.exec( datauri );

	// currently this save/upload to wiki code assumes that drawio passes data
	// URIs with base64 encoded data. this is currently the case but may not be
	// true forever. the check below errors out if the URI data is not base64
	// encoded (and if the data URI is otherwise deemed invalid.
	if ( !parts || parts[ 1 ] != this.imgMimeType || parts[ 3 ] != 'base64' || // eslint-disable-line eqeqeq
			typeof parts[ 4 ] !== 'string' || parts[ 4 ].length < 1 ) {
		this.showDialog( 'Save failed', 'Got unexpected data from drawio export.' );
		return;
	}

	// convert base64 to uint8 array
	const datastr = atob( parts[ 4 ] );
	const data = new Uint8Array( datastr.length );
	for ( let i = 0; i < datastr.length; i++ ) {
		data[ i ] = datastr.charCodeAt( i );
	}

	this.uploadToWiki( new Blob( [ data ], { type: this.imgMimeType } ) );
};

DrawioEditor.prototype.exit = function () {
	this.hide();
	editor = null; // eslint-disable-line no-use-before-define
	$( '#warningmsg' ).hide();
	this.destroy();
};

DrawioEditor.prototype.saveCallback = function () {
	this.showSpinner();

	// xmlsvg and xmlpng are known to work. the xml prefix causes the original
	// chart.io xml data to be added to the file, so it can be reimported later
	// without any data loss.
	const format = 'xml' + this.imgType;

	this.sendMsgToIframe( {
		action: 'export',
		embedImages: true,
		format: format
	} );

	// TODO: prevent exit while saving
};

DrawioEditor.prototype.exportCallback = function ( type, data ) {
	this.showSpinner();
	this.save( data );
};

DrawioEditor.prototype.exitCallback = function () {
	this.exit();
};

DrawioEditor.prototype.initCallback = function () {
	this.loadImage();
};

function FullscreenDialog( $iFrame ) {
	FullscreenDialog.super.call( this, {} );

	this.$iFrame = $iFrame;
	this.windowManager = new OO.ui.WindowManager();
	$( document.body ).append( this.windowManager.$element );
	this.windowManager.addWindows( [ this ] );
}
OO.inheritClass( FullscreenDialog, OO.ui.Dialog );

FullscreenDialog.static.name = 'drawioFullscreenDialog';

FullscreenDialog.prototype.getSize = function () {
	return 'full';
};

FullscreenDialog.prototype.initialize = function () {
	FullscreenDialog.super.prototype.initialize.apply( this, arguments );

	this.content = new OO.ui.PanelLayout( {
		padded: false,
		expanded: true
	} );
	this.$body.append( this.content.$element );
};

FullscreenDialog.prototype.show = function () {
	this.windowManager.openWindow( this );
	this.content.$element.append( this.$iFrame );
};

FullscreenDialog.prototype.close = function () {
	this.windowManager.closeWindow( this );
};

var editor; // eslint-disable-line no-var

window.editDrawio = function ( id, filename, editMode, type, updateHeight, updateWidth,
	updateMaxWidth, baseUrl, latestIsApproved, imageURL, theme ) {
	if ( !editor ) {
		window.drawioEditorBaseUrl = baseUrl;
		editor = new DrawioEditor( id, filename, editMode, type, updateHeight, updateWidth,
			updateMaxWidth, baseUrl, latestIsApproved, imageURL, theme );
	} else {
		alert( 'Only one DrawioEditor can be open at the same time!' );
	}
};

async function drawioHandleMessage( e ) {
	if (
		// we only act on event coming from "baseUrl" iframes
		!window?.drawioEditorBaseUrl?.startsWith( e.origin ) ||
		!editor ||
		// ignore e.g. { type: "WINDOWED-notify" }
		typeof e.data !== 'string'
	) {
		return;
	}

	const evdata = JSON.parse( e.data );

	switch ( evdata.event ) {
		case 'configure':
			await configureCallback( e );
			break;

		case 'init':
			editor.initCallback();
			break;

		case 'load':
			break;

		case 'save':
			editor.saveCallback();
			break;

		case 'export':
			editor.exportCallback( evdata.format, evdata.data );
			break;

		case 'exit':
			editor.exitCallback();
			// editor is null after this callback
			break;

		default:
			alert( 'Received unknown event from drawio iframe: ' + evdata.event );
	}
}

async function configureCallback( e ) {
	try {
		const response = await fetch(
			mw.util.wikiScript() + '?' + new URLSearchParams( {
				action: 'raw',
				title: 'MediaWiki:DrawioEditorConfig.json',
				ctype: 'application/json'
			} )
		);

		if ( !response.ok ) {
			throw new Error( `HTTP error ${ response.status }` );
		}

		let config = {
			defaultAdaptiveColors: 'none'
		};
		const contentType = response.headers.get( 'Content-Type' );
		const text = await response.text();

		if ( !text.trim() ) {
			console.warn( '[DrawioEditor] Config page is empty. Using default config.' ); // eslint-disable-line no-console
		} else if ( contentType?.includes( 'application/json' ) || text.trim().startsWith( '{' ) ) {
			try {
				config = JSON.parse( text );
			} catch ( parseErr ) {
				console.warn( '[DrawioEditor] Failed to parse JSON in config. Using default config.', parseErr ); // eslint-disable-line no-console
			}
		} else {
			console.warn( '[DrawioEditor] Config content not JSON-like. Using default config.' ); // eslint-disable-line no-console
		}

		e.source.postMessage( JSON.stringify( {
			action: 'configure',
			config
		} ), e.origin );
	} catch ( err ) {
		console.error( '[DrawioEditor] Configure load failed:', err ); // eslint-disable-line no-console
	}
}

window.addEventListener( 'message', drawioHandleMessage );

$( document ).on( 'click', '.drawioeditor-edit', function () {
	const data = $( this ).data();
	editDrawio( // eslint-disable-line no-undef
		data.targetId,
		data.imgName,
		data.editMode,
		data.type,
		data.height,
		data.width,
		data.maxWidth,
		data.baseUrl,
		data.latestIsApproved,
		data.imgUrl,
		data.theme
	);
} );
