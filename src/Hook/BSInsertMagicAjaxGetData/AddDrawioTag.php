<?php

namespace MediaWiki\Extension\DrawioEditor\Hook\BSInsertMagicAjaxGetData;

use BlueSpice\InsertMagic\Hook\BSInsertMagicAjaxGetData;

class AddDrawioTag extends BSInsertMagicAjaxGetData {

	protected function skipProcessing() {
		return $this->type !== 'tags';
	}

	protected function doProcess() {
		$this->response->result[] = (object)[
			'id' => 'drawio',
			'type' => 'tag',
			'name' => 'drawio',
			'desc' => wfMessage( 'drawioconnector-tag-drawio-desc' )->escaped(),
			'code' => '{{#drawio:filename="Diagram.png" }}',
			'mwvecommand' => 'drawioCommand',
			'previewable' => false,
			'examples' => [ [ 'code' => '{{#drawio:filename="Diagram.png" }}' ] ],
			'helplink' => "https://en.wiki.bluespice.com/wiki/Reference:BlueSpiceDrawioConnector"
		];
		return true;
	}

}
