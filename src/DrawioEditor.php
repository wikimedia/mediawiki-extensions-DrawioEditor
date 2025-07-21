<?php

namespace MediaWiki\Extension\DrawioEditor;

use File;
use FileRepo;
use MediaWiki\Config\Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\DrawioEditor\MXDocumentExtractor\NullExtractor;
use MediaWiki\Extension\DrawioEditor\MXDocumentExtractor\PNG;
use MediaWiki\Extension\DrawioEditor\MXDocumentExtractor\SVG;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Title\Title;

class DrawioEditor {

	/**
	 * @var Config
	 */
	protected $config;

	/**
	 * @var MediaWikiServices
	 */
	protected $services;

	public function __construct() {
		$this->services = MediaWikiServices::getInstance();
		$this->config = $this->services->getMainConfig();
	}

	/**
	 * Parser hook handler for <drawio>
	 *
	 * @param string|null $data A string with the content of the tag, or null.
	 * @param array $attribs The attributes of the tag.
	 * @param Parser $parser Parser instance available to render
	 *                             wikitext into html, or parser methods.
	 * @param PPFrame $frame Can be used to see what template
	 *                             arguments ({{{1}}}) this hook was used with.
	 *
	 * @return string HTML to insert in the page.
	 */
	public function parseExtension( $data, array $attribs, Parser $parser, PPFrame $frame ) {
		// Extract name as option from tag <drawio filename=FileName .../>
		$name = array_key_exists( 'filename', $attribs )
			? $attribs[ 'filename' ]
			: null;

		// Call general parse-generator routine
		return $this->parse( $parser, $name, $attribs );
	}

	/**
	 * Parser hook handler for {{drawio}}
	 *
	 * @param Parser &$parser Parser instance available to render
	 *                             wikitext into html, or parser methods.
	 * @param string|null $name File name of chart.
	 *
	 * @return array HTML to insert in the page.
	 */
	public function parseLegacyParserFunc( Parser &$parser, $name = null ) {
		/* parse named arguments */
		$opts = [];
		foreach ( array_slice( func_get_args(), 2 ) as $rawopt ) {
			$opt = explode( '=', $rawopt, 2 );
			$opts[ trim( $opt[ 0 ] ) ] = count( $opt ) === 2 ? trim( $opt[ 1 ] ) : true;
		}

		// Call general parse-generator routine
		return $this->parse( $parser, $name, $opts );
	}

	/**
	 * Generates the HTML required to embed a SVG/PNG DrawIO diagram, supports
	 * a few formatting options to control with width/height, and image format.
	 *
	 * @param Parser &$parser Parser instance available to render
	 *                             wikitext into html, or parser methods.
	 * @param string|null $name File name of chart.
	 * @param array $opts Further attributes as associative array:
	 *                             width, height, max-height, type.
	 *
	 * @return array HTML to insert in the page.
	 */
	public function parse( &$parser, $name, $opts ) {
		/* disable caching before any output is generated */
		$parser->getOutput()->updateCacheExpiry( 0 );

		$opt_type = $opts[ 'type' ] ?? $this->config->get( 'DrawioEditorImageType' );
		$opt_height = $opts[ 'height' ] ?? 'auto';
		$opt_width = $opts[ 'width' ] ?? '100%';
		$opt_max_width = $opts[ 'max-width' ] ?? false;
		$opt_alt = $opts[ 'alt' ] ?? false;
		$alignment = $opts[ 'alignment' ] ?? 'center';

		/* process input */
		if ( $name == null || !strlen( $name ) ) {
			return $this->errorMessage( 'Usage Error' );
		}
		$opt_type = strtolower( $opt_type );
		if ( $opt_type === 'svg' ) {
			// allow fallback to png
			$tryTypes = [ 'svg', 'png' ];
		} elseif ( $opt_type === 'png' ) {
			$tryTypes = [ 'png' ];
		} else {
			return $this->errorMessage( 'Invalid type' );
		}

		$len_regex = '/^((0|auto|chart)|[0-9]+(\.[0-9]+)?(px|%|mm|cm|in|em|ex|pt|pc))$/';
		$len_regex_max = '/^((0|none|chart)|[0-9]+(\.[0-9]+)?(px|%|mm|cm|in|em|ex|pt|pc))$/';

		if ( !preg_match( $len_regex, $opt_height ) ) {
			return $this->errorMessage( 'Invalid height' );
		}
		if ( !preg_match( $len_regex, $opt_width ) ) {
			return self::errorMessage( 'Invalid width' );
		}

		if ( $opt_max_width ) {
			if ( !preg_match( '/%$/', $opt_width ) ) {
				return $this->errorMessage( 'max-width is only allowed when width is relative' );
			}
			if ( !preg_match( $len_regex_max, $opt_max_width ) ) {
				return $this->errorMessage( 'Invalid max-width' );
			}
		} else {
			$opt_max_width = 'chart';
		}

		$name = wfStripIllegalFilenameChars( $name );
		$dispname = htmlspecialchars( $name, ENT_QUOTES );

		if ( $opt_alt ) {
			$alt = htmlspecialchars( $opt_alt, ENT_QUOTES );
		} else {
			$alt = $name;
		}

		/* random id to reference html elements */
		$id = mt_rand();

		/* prepare image information */
		$repo = $this->services->getRepoGroup();
		$img = null;
		$img_name = "$name.$opt_type";

		foreach ( $tryTypes as $ext ) {
			foreach ( [ "$name.drawio.$ext", "$name.$ext" ] as $imgNameTry ) {
				$img = $repo->findFile( $imgNameTry );
				if ( $img ) {
					$opt_type = $ext;
					$img_name = $imgNameTry;
					break 2;
				}
			}
		}

		$noApproved = false;
		$latest_is_approved = true;
		if ( $img ) {
			$img_url_ts = null;
			$displayImage = $img;
			$hookRunner = $this->services->getHookContainer();
			$hookRunner->run( 'DrawioGetFile', [ &$img, &$latest_is_approved, $parser->getUserIdentity(),
			&$noApproved, &$displayImage ] );
			$img_url_ts = $displayImage->getUrl();
			$img_desc_url = $img->getDescriptionUrl();
			$img_height = $img->getHeight() . 'px';
			$img_width = $img->getWidth() . 'px';
		} else {
			$img_url_ts = '';
			$img_desc_url = '';
			$img_height = 0;
			$img_width = 0;
		}

		$css_img_height = $opt_height === 'chart' ? $img_height : $opt_height;
		$css_img_width = $opt_width === 'chart' ? $img_width : $opt_width;
		$css_img_max_width = $opt_max_width === 'chart' ? $img_width : $opt_max_width;

		/* get and check base url */
		$base_url = filter_var( $this->config->get( 'DrawioEditorBackendUrl' ),
			FILTER_VALIDATE_URL );
		if ( !$base_url ) {
			return $this->errorMessage( 'Invalid base url' );
		}

		/* prepare edit href */
		$editLabel = wfMessage( 'edit' )->escaped();
		$attribs = [
			'class' => 'drawioeditor-edit',
			'title' => $editLabel,
			'data-target-id' => $id,
			'data-img-name' => $img_name,
			'data-type' => $opt_type,
			'data-height' => $opt_height === 'chart' ? 'true' : 'false',
			'data-width' => $opt_width === 'chart' ? 'true' : 'false',
			'data-max-width' => $opt_max_width === 'chart' ? 'true' : 'false',
			'data-base-url' => $base_url,
			'data-latest-is-approved' => $latest_is_approved ? 'true' : 'false',
			'data-img-url' => $img ? $img->getUrl() : ""
		];
		$edit_ahref = Html::element( 'a', $attribs, $editLabel );

		/* output begin */
		$output = Html::openElement( 'div' );

		$user = RequestContext::getMain()->getUser();
		$permisionManager = $this->services->getPermissionManager();
		$userHasRight = $permisionManager->userHasRight( $user, 'approverevisions' );

		if ( $noApproved ) {
			$output .= Html::element( 'p',
				[ 'class' => 'successbox' ],
				wfMessage( "drawioeditor-noapproved", $name )->escaped()
			);

			if ( $userHasRight ) {
				$output .= ' ' . Html::element( 'a',
					[ 'href' => $img_desc_url ],
					wfMessage( "drawioeditor-approve-link" )->escaped()
				);
			}

			global $egApprovedRevsBlankFileIfUnapproved;
			if ( $egApprovedRevsBlankFileIfUnapproved ) {
				$img = null;
				$edit_ahref = '';
			}
		} else {
			if ( $img ) {
				if ( !$latest_is_approved ) {
					$output .= Html::element( 'p', [
						'class' => 'successbox',
						'id' => 'approved-displaywarning'
					], wfMessage( "drawioeditor-approved-displaywarning" )->escaped()
					);
				}
				if ( $userHasRight ) {
					$output .= ' ' . Html::element( 'a',
						[ 'href' => $img_desc_url ],
						wfMessage( "drawioeditor-changeapprove-link" )->escaped()
					);
				}
			}
		}

		/* div around the image */
		$output .= Html::openElement( 'div', [ 'id' => "drawio-img-box-$id" ] );

		/* display edit link */
		if ( !$this->isReadOnly( $img, $parser ) ) {
			$output .= Html::openElement( 'div', [
				'class' => 'mw-editdrawio-wrapper',
				'align' => 'right'
			] );
			$output .= Html::openElement( 'span', [ 'class' => 'mw-editdrawio' ] );
			$output .= Html::element( 'span',
				[ 'class' => 'mw-editsection-bracket' ],
				'['
			);
			$output .= $edit_ahref;
			$output .= Html::element( 'span',
				[ 'class' => 'mw-editsection-bracket' ],
				']'
			);
			$output .= Html::closeElement( 'span' );
			$output .= Html::closeElement( 'div' );
		}

		/* prepare image */
		$img_style = sprintf( 'height: %s; width: %s; max-width: %s;',
			$css_img_height, $css_img_width, $css_img_max_width );
		if ( !$img ) {
			$img_style .= ' display:none;';
		}

		$imgAttribs = [
			'id' => "drawio-img-$id",
			'title' => "drawio: $dispname",
			'style' => $img_style
		];

		if ( $opt_type === 'svg' ) {
			$imgAttribs['data'] = $img_url_ts;
			$imgAttribs['type'] = 'image/svg+xml';
		} elseif ( $opt_type === 'png' ) {
			$imgAttribs['src'] = $img_url_ts;
			$imgAttribs['alt'] = $alt;
		}

		if ( $img && $opt_type === 'png' ) {
			$mxDocumentExtractor = $this->getMXDocumentExtractor( $opt_type, $img->getRepo() );
			$mxDocument = $mxDocumentExtractor->extractMXDocument( $img );
			$imageMapGenerator = new ImageMapGenerator();
			$imageMapName = "drawio-map-$id";
			$imageMap = $imageMapGenerator->generateImageMap( $mxDocument, $imageMapName );

			// Add usemap if an image map is generated
			$imgAttribs['usemap'] = "#$imageMapName";
		}

		/* Generate image HTML */
		if ( $opt_type === 'svg' ) {
			$img_html = Html::element( 'object', $imgAttribs );
			$icon = Html::element( 'a', [
				'href' => $img_desc_url,
				'title' => $dispname,
				'class' => 'oo-ui-icon-info mw-ui-icon mw-ui-icon-element'
			] );
			$img_html .= Html::rawElement( 'div', [ 'class' => 'drawio-caption-icon' ], $icon );
		} elseif ( $opt_type === 'png' ) {
			$img_html = Html::openElement( 'a', [
				'id' => "drawio-img-href-$id",
				'href' => $img_desc_url
			] );
			$img_html .= Html::element( 'img', $imgAttribs );
			if ( isset( $imageMap ) ) {
				$img_html .= $imageMap;
			}
			$img_html .= Html::closeElement( 'a' );
		}

		/* output image and optionally a placeholder if the image does not exist yet */
		if ( !$img && !$noApproved ) {
			// show placeholder
			$output .= Html::rawElement( 'div', [
				'id' => "drawio-placeholder-$id",
				'class' => 'DrawioEditorInfoBox'
			], Html::element( 'b', [], $dispname ) );
		} else {
			// the image or object element must be there in any case
			// (it's hidden as long as there is no content.)
			$output .= Html::rawElement( 'div',
				[ 'class' => "drawio-img-container drawio-align-$alignment" ],
				$img_html
			);
		}

		$output .= Html::closeElement( 'div' );

		/* editor and overlay divs, iframe is added by javascript on demand */
		$output .= Html::openElement( 'div', [
			'id' => "drawio-iframe-box-$id",
			'style' => 'display:none;'
		] );
		$output .= Html::element( 'div', [
			'id' => "drawio-iframe-overlay-$id",
			'class' => 'DrawioEditorOverlay',
			'style' => 'display:none;'
		] );
		$output .= Html::closeElement( 'div' );

		/* output end */
		$output .= Html::closeElement( 'div' );

		/*
		 * link the image to the ParserOutput, so that the mediawiki knows that
		 * it is used by the hosting page (through the DrawioEditor extension).
		 * Note: This only works if the page is edited after the image has been
		 * created (i.e. saved in the DrawioEditor for the first time).
		 */
		if ( $img ) {
			$parser->getOutput()->addImage( $img->getTitle()->getDBkey() );
		}

		$parser->getOutput()->addModules( [ 'ext.drawioeditor' ] );
		$parser->getOutput()->addModuleStyles( [
			'ext.drawioeditor.styles',
			'mediawiki.ui.icon'
		] );

		return [ $output, 'isHTML' => true, 'noparse' => true ];
	}

	/**
	 * @param string $opt_type
	 * @param FileRepo $repo
	 * @return IMXDocumentExtractor
	 */
	private function getMXDocumentExtractor( $opt_type, $repo ) {
		$extractor = null;
		$backend = $repo->getBackend();
		switch ( $opt_type ) {
			case 'png':
				$extractor = new PNG( $backend );
				break;
			case 'svg':
				$extractor = new SVG( $backend );
				break;
			default:
				$extractor = new NullExtractor( $backend );
				break;
		}
		return $extractor;
	}

	/**
	 * @param string $msg
	 * @return array
	 */
	private function errorMessage( $msg ) {
		$output = Html::openElement( 'div', [
			'class' => 'DrawioEditorInfoBox',
			'style' => 'border-color:red;'
		] );
		$output .= Html::rawElement( 'p',
			[ 'style' => 'color: red;' ],
			'DrawioEditor Usage Error:<br/>' . htmlspecialchars( $msg )
		);
		$output .= Html::closeElement( 'div' );

		return [ $output, 'isHTML' => true, 'noparse' => true ];
	}

	/**
	 * @param File|null $img
	 * @param Parser $parser
	 * @return bool
	 */
	private function isReadOnly( $img, $parser ) {
		$user = RequestContext::getMain()->getUser();
		$permissionManager = $this->services->getPermissionManager();
		$pageRef = $parser->getPage();
		$title = Title::castFromPageReference( $pageRef );
		if ( !$title ) {
			return true;
		}

		$isProtected = $this->services->getRestrictionStore()->isProtected( $title, 'edit' );
		$uploadsEnabled = $this->config->get( 'EnableUploads' );
		$canUpload = $permissionManager->userCan( 'upload', $user, $title );
		$canReupload = $permissionManager->userCan( 'reupload', $user, $title );

		return !$uploadsEnabled || !$canUpload || !$canReupload || $isProtected;
	}
}
