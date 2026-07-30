<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\DrawioEditor\ContentDroplets;

use MediaWiki\Message\Message;

class DrawioSketchDroplet extends DrawioDroplet {

	/**
	 * @inheritDoc
	 */
	public function getName(): Message {
		return Message::newFromKey( 'drawioeditor-sketch-droplet-name' );
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription(): Message {
		return Message::newFromKey( 'drawioeditor-sketch-droplet-description' );
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return 'droplet-drawio-sketch';
	}

	/**
	 * @return string
	 */
	protected function getTagName(): string {
		return 'drawio-sketch';
	}

	/**
	 * @return string|null
	 */
	public function getVeCommand(): ?string {
		// VisualEditorPlus registers one command per tag name as
		// "<tagname with ':' replaced by '_'>Command" (see TagRegistry.js).
		// For the "drawio-sketch" tag that command is "drawio-sketchCommand".
		return 'drawio-sketchCommand';
	}
}
