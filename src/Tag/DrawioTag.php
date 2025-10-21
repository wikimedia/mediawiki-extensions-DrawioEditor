<?php

namespace MediaWiki\Extension\DrawioEditor\Tag;

use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MWStake\MediaWiki\Component\FormEngine\FormLoaderSpecification;
use MWStake\MediaWiki\Component\GenericTagHandler\ClientTagSpecification;
use MWStake\MediaWiki\Component\GenericTagHandler\GenericTag;
use MWStake\MediaWiki\Component\GenericTagHandler\ITagHandler;
use MWStake\MediaWiki\Component\InputProcessor\Processor\StringValue;

class DrawioTag extends GenericTag {

	/**
	 * @param string $defaultEditMode
	 */
	public function __construct( private readonly string $defaultEditMode = 'inline' ) {
	}

	/**
	 * @inheritDoc
	 */
	public function getTagNames(): array {
		return [
			'bs:drawio',
			'drawio'
		];
	}

	/**
	 * @return bool
	 */
	public function hasContent(): bool {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getParamDefinition(): ?array {
		$filename = ( new StringValue() )
			// Placeholder to prevent errors while VE inspector is being initialized
			->setDefaultValue(
				Message::newFromKey( 'drawioeditor-ve-drawio-tag-name-placeholder' )->text() . uniqid( '_' )
			)
			->setRequired( false );

		$editmode = new StringValue();
		$editmode->setDefaultValue( $this->defaultEditMode );

		$alt = new StringValue();

		$alignment = new StringValue();
		$alignment->setDefaultValue( "center" );

		return [
			'filename' => $filename,
			'editmode' => $editmode,
			'alt' => $alt,
			'alignment' => $alignment,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getClientTagSpecification(): ClientTagSpecification|null {
		return new ClientTagSpecification(
			'Drawio',
			Message::newFromKey( "drawioeditor-ve-drawio-description" ),
			new FormLoaderSpecification(
			'drawioeditor.tag.Form', [ 'ext.drawio.tag.form' ]
			),
			Message::newFromKey( "drawioeditor-ve-drawio-title" )
		);
	}

	public function getHandler( MediaWikiServices $services ): ITagHandler {
		return new DrawioTagHandler();
	}
}
