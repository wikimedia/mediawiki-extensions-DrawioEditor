<?php

namespace MediaWiki\Extension\DrawioEditor\Hook;

use MediaWiki\Config\Config;
use MediaWiki\Extension\DrawioEditor\Tag\DrawioTag;
use MWStake\MediaWiki\Component\GenericTagHandler\Hook\MWStakeGenericTagHandlerInitTagsHook;

class RegisterTags implements MWStakeGenericTagHandlerInitTagsHook {

	/**
	 * @param Config $config
	 */
	public function __construct( private readonly Config $config ) {
	}

	/**
	 * @inheritDoc
	 */
	public function onMWStakeGenericTagHandlerInitTags( array &$tags ): void {
		$defaultEditMode = $this->config->get( 'DrawioEditorDefaultEditmode' );
		$tags[] = new DrawioTag( $defaultEditMode );
	}
}
