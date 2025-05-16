<?php

namespace MediaWiki\Extension\DrawioEditor\Integration;

use BlueSpice\InstanceStatus\InstanceStatusProvider\UrlReachableProvider;
use MediaWiki\Config\Config;
use MediaWiki\Http\HttpRequestFactory;

class StatusCheckProvider extends UrlReachableProvider {

	/**
	 * @param Config $config
	 * @param HttpRequestFactory $httpRequestFactory
	 */
	public function __construct(
		private readonly Config $config,
		private readonly HttpRequestFactory $httpRequestFactory
	) {
		parent::__construct( $httpRequestFactory );
	}

	/**
	 * @return string
	 */
	public function getKeyForApi(): string {
		return 'ext-drawioeditor-backend-connectivity';
	}

	/**
	 * @return string
	 */
	protected function getUrl(): string {
		return $this->config->get( 'DrawioEditorBackendUrl' );
	}
}
