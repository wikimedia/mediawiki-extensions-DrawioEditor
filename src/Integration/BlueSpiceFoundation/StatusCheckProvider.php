<?php

namespace MediaWiki\Extension\DrawioEditor\Integration\BlueSpiceFoundation;

use BlueSpice\InstanceStatus\IStatusProvider;
use GuzzleHttp\Client;
use MediaWiki\Config\Config;
use Throwable;

class StatusCheckProvider implements IStatusProvider {

	/** @var Config */
	private $config;

	/**
	 * @param Config $config
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * @return string
	 */
	public function getLabel(): string {
		return 'ext-drawioeditor-backend-connectivity';
	}

	/**
	 * @return string
	 */
	public function getValue(): string {
		try {
			$guzzle = new Client();
			$backendUrl = $this->config->get( 'DrawioEditorBackendUrl' );

			$response = $guzzle->request( 'GET', $backendUrl, [
				'http_errors' => false,
				'timeout' => 3.0,
			] );

			$statusCode = $response->getStatusCode();

			return $statusCode === 200 ? 'OK' : 'Backend unreachable';
		} catch ( Throwable $e ) {
			return 'Exception: ' . $e->getMessage();
		}
	}

	/**
	 * @return string
	 */
	public function getIcon(): string {
		return 'check';
	}

	/**
	 * @return int
	 */
	public function getPriority(): int {
		return 100;
	}
}
