<?php

namespace MediaWiki\Extension\DrawioEditor\Tag;

use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MWStake\MediaWiki\Component\FormEngine\FormLoaderSpecification;
use MWStake\MediaWiki\Component\GenericTagHandler\ClientTagSpecification;
use MWStake\MediaWiki\Component\GenericTagHandler\ITagHandler;

/**
 * Variant of the <drawio> tag that opens the editor in draw.io's "sketch" UI.
 * It behaves exactly like the regular tag, but forces the sketch interface and
 * uses a dedicated, whiteboard-style placeholder.
 */
class DrawioSketchTag extends DrawioTag {

	/**
	 * @inheritDoc
	 */
	public function getTagNames(): array {
		return [
			'bs:drawio-sketch',
			'drawio-sketch'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getClientTagSpecification(): ClientTagSpecification|null {
		return new ClientTagSpecification(
			'DrawioSketch',
			Message::newFromKey( "drawioeditor-ve-drawiosketch-description" ),
			new FormLoaderSpecification(
				'drawioeditor.tag.Form', [ 'ext.drawio.tag.form' ]
			),
			Message::newFromKey( "drawioeditor-ve-drawiosketch-title" )
		);
	}

	public function getHandler( MediaWikiServices $services ): ITagHandler {
		return new DrawioTagHandler( true );
	}
}
