<?php

namespace MediaWiki\Extension\DrawioEditor\Api;

use DOMDocument;
use DOMElement;
use DOMXPath;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Message\Message;
use MediaWiki\Specials\SpecialUpload;
use MediaWiki\Title\Title;
use MWFileProps;
use RepoGroup;
use RuntimeException;
use Wikimedia\Mime\MimeAnalyzer;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\Util\UploadedFile;
use Wikimedia\ParamValidator\Util\UploadedFileStream;

/**
 * API module for saving a Drawio diagram as a File page.
 */
class SaveDrawioDiagram extends ApiBase {

	/** @var RepoGroup */
	protected $repoGroup;

	/** @var MimeAnalyzer */
	protected $mimeAnalyzer;

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param RepoGroup $repoGroup
	 * @param MimeAnalyzer $mimeAnalyzer
	 */
	public function __construct( ApiMain $main, string $action, RepoGroup $repoGroup, MimeAnalyzer $mimeAnalyzer ) {
		parent::__construct( $main, $action );
		$this->repoGroup = $repoGroup;
		$this->mimeAnalyzer = $mimeAnalyzer;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$user = $this->getContext()->getUser();

		/** @var UploadedFile */
		$file = $params[ 'file' ];

		// Get upload stream
		try {
			/** @var UploadedFileStream */
			$stream = $file->getStream();
		} catch ( RuntimeException $e ) {
			$this->dieWithError( 'Could not get stream: ' . $e->getMessage() );
		}

		$filename = $file->getClientFilename();
		if ( !$filename ) {
			$this->dieWithError( 'No uploaded filename' );
		}

		$ext = pathinfo( $filename, PATHINFO_EXTENSION );
		if ( !$this->isAllowedFileType( $ext ) ) {
			$this->dieWithError( "\"$ext\" files are not permitted on this wiki" );
		}

		if ( str_contains( $filename, ':' ) ) {
			// Validate user specified namespace
			$title = Title::newFromText( $filename );
			if ( !$title || $title->getNamespace() === 0 ) {
				$this->dieWithError( 'Invalid filename' );
			}
		}

		$title = Title::makeTitleSafe( NS_FILE, $filename );
		if ( !$title || !$title->canExist() ) {
			$this->dieWithError( 'Invalid filename' );
		}

		$repoFile = $this->repoGroup->getLocalRepo()->newFile( $title );
		if ( !$repoFile ) {
			$this->dieWithError( 'Could not create LocalFile' );
		}

		// Analyse file properties
		$mwProps = new MWFileProps( $this->mimeAnalyzer );
		$tempFilePath = $stream->getMetadata( 'uri' );
		if ( !$tempFilePath ) {
			$this->dieWithError( 'Could not determine uploaded file temp path' );
		}

		$props = $mwProps->getPropsFromPath( $tempFilePath, true );
		if ( $props['mime'] === 'image/svg+xml' ) {
			$this->modifyImage( $tempFilePath );
		}

		// Store file in repo
		$status = $repoFile->publish( $tempFilePath );
		if ( !$status->isOK() ) {
			$this->dieWithError( 'Could not publish uploaded diagram: ' . $status->getWikiText() );
		}

		// Register upload metadata
		$status = $repoFile->recordUpload3(
			$status->value,
			'DrawioEditor',
			SpecialUpload::getInitialPageText(),
			$user,
			$props
		);

		if ( !$status->isOK() ) {
			$errors = $status->getMessages();
			$this->dieWithError( Message::newFromSpecifier( $errors[0] )->text() );
		}

		// Respond with file details
		$this->getResult()->addValue( null, 'upload', [
			'result' => 'Success',
			'imageinfo' => [
				'timestamp' => wfTimestamp( TS_ISO_8601, $repoFile->getTimestamp() ?? time() ),
				'url' => $repoFile->getFullUrl(),
				'descriptionurl' => $repoFile->getDescriptionUrl(),
				'width' => $repoFile->getWidth(),
				'height' => $repoFile->getHeight()
			]
		] );
	}

	/**
	 * Used for post-processing SVG files.
	 *
	 * @param string $filePath
	 *
	 * @return void
	 */
	private function modifyImage( string $filePath ): void {
		$dom = new DOMDocument();
		$dom->load( $filePath );

		$xpath = new DOMXPath( $dom );
		$xpath->registerNamespace( 'svg', 'http://www.w3.org/2000/svg' );
		$xpath->registerNamespace( 'xhtml', 'http://www.w3.org/1999/xhtml' );

		$this->enforceAnchorTargetTop( $xpath );
		$this->fixTransparentStyle( $xpath );

		$dom->save( $filePath );
	}

	/**
	 * Set target="_top" on SVG <a> links unless target="_blank" is set,
	 * to prevent links opening inside the <object> and ensure
	 * they open in the top-level window.
	 *
	 * @param DOMXPath $xpath
	 */
	private function enforceAnchorTargetTop( DOMXPath $xpath ): void {
		$anchors = [];

		$svgAnchors = $xpath->query( '//svg:a' );
		foreach ( $svgAnchors as $anchor ) {
			$anchors[] = $anchor;
		}

		$htmlAnchors = $xpath->query( '//svg:foreignObject//xhtml:a' );
		foreach ( $htmlAnchors as $anchor ) {
			$anchors[] = $anchor;
		}

		foreach ( $anchors as $anchor ) {
			/** @var DOMElement $anchor */
			if ( $anchor->hasAttribute( 'target' ) && $anchor->getAttribute( 'target' ) === '_blank' ) {
				// user explicitly set 'Open in New Window'
				continue;
			}
			$anchor->setAttribute( 'target', '_top' );
		}
	}

	/**
	 * Adds fill-opacity=0 to transparent elements
	 *
	 * ERM45109
	 *
	 * @param DOMXPath $xpath
	 *
	 * @return void
	 */
	private function fixTransparentStyle( DOMXPath $xpath ): void {
		$allElements = $xpath->query( '//*[@fill="transparent"]' );
		foreach ( $allElements as $element ) {
			/** @var DOMElement $element */
			$element->setAttribute( 'fill-opacity', '0' );
		}
	}

	/**
	 * Check if a given file type is permitted.
	 *
	 * @param string $extension File type, e.g. 'svg', 'png'
	 *
	 * @return bool
	 */
	protected function isAllowedFileType( string $extension ): bool {
		$allowed = $this->getConfig()->get( 'FileExtensions' );
		return in_array( strtolower( $extension ), $allowed, true );
	}

	/**
	 * @inheritDoc
	 */
	public function needsToken(): string {
		return 'csrf';
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		return [
			'file' => [
				ParamValidator::PARAM_TYPE => 'upload',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}

}
