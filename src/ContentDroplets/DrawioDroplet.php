<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\DrawioEditor\ContentDroplets;

use MediaWiki\Extension\ContentDroplets\Droplet\TagDroplet;
use MediaWiki\Message\Message;

class DrawioDroplet extends TagDroplet {

	/**
	 * @inheritDoc
	 */
	public function getName(): Message {
		return Message::newFromKey( 'drawio-droplet-name' );
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription(): Message {
		return Message::newFromKey( 'drawio-droplet-description' );
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return 'droplet-drawio';
	}

	/**
	 * @inheritDoc
	 */
	public function getRLModules(): array {
		return [ 'ext.drawioconnector.visualEditor.bootstrap' ];
	}

	/**
	 * @return array
	 */
	public function getCategories(): array {
		return [ 'visualization', 'featured' ];
	}

	/**
	 * @return string
	 */
	protected function getTagName(): string {
		return 'drawio';
	}

	/**
	 * @return array
	 */
	protected function getAttributes(): array {
		return [ 'filename' ];
	}

	/**
	 * @return bool
	 */
	protected function hasContent(): bool {
		return false;
	}

	/**
	 * @return string|null
	 */
	public function getVeCommand(): ?string {
		return 'drawioCommand';
	}
}
