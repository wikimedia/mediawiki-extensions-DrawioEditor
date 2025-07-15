<?php

namespace MediaWiki\Extension\DrawioEditor\Api;

use File;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Message\Message;
use MediaWiki\Specials\SpecialUpload;
use MediaWiki\Title\Title;
use MWFileProps;
use RepoGroup;
use RuntimeException;
use SVGReader;
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

		$mime = $props['file-mime'] ?? null;
		if ( $mime && $mime !== 'unknown/unknown' ) {
			[ $major, $minor ] = File::splitMime( $mime );
			$props['major_mime'] = $major;
			$props['minor_mime'] = $minor;
			$props['mime'] = "$major/$minor";
			$props['media_type'] = 'IMAGE';

			if ( $mime === 'image/svg+xml' ) {
				$svgReader = new SVGReader( $tempFilePath );
				$svgMetadata = $svgReader->getMetadata();
				$props['width'] = $svgMetadata['width'] ?? 100;
				$props['height'] = $svgMetadata['height'] ?? 100;
			}
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
