<?php

namespace MediaWiki\Extension\DrawioEditor\ConfigDefinition;

use BlueSpice\ConfigDefinition\ArraySetting;
use BlueSpice\ConfigDefinition\IOverwriteGlobal;
use MediaWiki\HTMLForm\Field\HTMLSelectField;
use MediaWiki\Message\Message;

class DefaultEditmode extends ArraySetting implements IOverwriteGlobal {

	/**
	 * @return string[]
	 */
	public function getPaths() {
		$feature = static::FEATURE_EDITOR;
		$ext = 'DrawioEditor';
		$package = static::PACKAGE_PRO;

		return [
			static::MAIN_PATH_FEATURE . "/$feature/$ext",
			static::MAIN_PATH_EXTENSION . "/$ext/$feature",
			static::MAIN_PATH_PACKAGE . "/$package/$ext",
		];
	}

	/**
	 * @return HTMLSelectField
	 */
	public function getHtmlFormField() {
		return new HTMLSelectField( $this->makeFormFieldParams() );
	}

	/**
	 * @return string[]
	 */
	public function getOptions() {
		return [
			Message::newFromKey( "drawio-tag-editmode-label-inline" )->text() => 'inline',
			Message::newFromKey( "drawio-tag-editmode-label-fullscreen" )->text() => 'fullscreen',
		];
	}

	/** @inheritDoc */
	public function getGlobalName() {
		return "wgDrawioEditorDefaultEditmode";
	}

	/** @inheritDoc */
	public function getLabelMessageKey() {
		return 'drawioeditor-config-defaulteditmode';
	}

	/** @inheritDoc */
	public function getHelpMessageKey() {
		return 'drawioeditor-config-defaulteditmode-help';
	}
}
