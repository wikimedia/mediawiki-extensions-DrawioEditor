<?php

namespace MediaWiki\Extension\DrawioEditor\Hook;

use MediaWiki\Extension\DrawioEditor\Tag\DrawioTag;
use MWStake\MediaWiki\Component\GenericTagHandler\Hook\MWStakeGenericTagHandlerInitTagsHook;

class RegisterTags implements MWStakeGenericTagHandlerInitTagsHook {

	/**
	 * @inheritDoc
	 */
	public function onMWStakeGenericTagHandlerInitTags( array &$tags ): void {
		$tags[] = new DrawioTag();
	}
}
