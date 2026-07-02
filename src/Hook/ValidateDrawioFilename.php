<?php

namespace MediaWiki\Extension\DrawioEditor\Hook;

use MediaWiki\Content\Content;
use MediaWiki\Content\TextContent;
use MediaWiki\Context\IContextSource;
use MediaWiki\Hook\EditFilterMergedContentHook;
use MediaWiki\User\User;
use StatusValue;

class ValidateDrawioFilename implements EditFilterMergedContentHook {

	private const FILENAME_PATTERN = '/^[\w,\-.\s:]+$/u';

	/**
	 * @inheritDoc
	 */
	public function onEditFilterMergedContent(
		IContextSource $context,
		Content $content,
		StatusValue $status,
		$summary,
		User $user,
		$minoredit
	) {
		if ( !( $content instanceof TextContent ) ) {
			return;
		}

		$text = $content->getText();
		$invalidNames = $this->findInvalidDrawioFilenames( $text );
		if ( $invalidNames ) {
			$status->fatal(
				'drawioeditor-error-invalid-filename',
				implode( ', ', $invalidNames )
			);
			return false;
		}
	}

	/**
	 * @param string $text
	 * @return string[]
	 */
	private function findInvalidDrawioFilenames( string $text ): array {
		$invalidNames = [];

		// Match <drawio filename="..." /> and <bs:drawio filename="..." />
		if ( preg_match_all(
			'/<(?:bs:)?drawio\b[^>]*?\bfilename\s*=\s*"([^"]*)"[^>]*\/?>/i',
			$text,
			$matches
		) ) {
			foreach ( $matches[1] as $name ) {
				if ( !preg_match( self::FILENAME_PATTERN, $name ) ) {
					$invalidNames[] = $name;
				}
			}
		}

		// Match <drawio filename=value /> without quotes
		if ( preg_match_all(
			'/<(?:bs:)?drawio\b[^>]*?\bfilename\s*=\s*([^\s"\/>][^\s\/>]*)/i',
			$text,
			$matches
		) ) {
			foreach ( $matches[1] as $name ) {
				if ( !preg_match( self::FILENAME_PATTERN, $name ) ) {
					$invalidNames[] = $name;
				}
			}
		}

		// Match {{#drawio:filename|...}} legacy parser function
		if ( preg_match_all(
			'/\{\{#drawio:\s*([^|}]+)/i',
			$text,
			$matches
		) ) {
			foreach ( $matches[1] as $name ) {
				$name = trim( $name );
				if ( $name !== '' && !preg_match( self::FILENAME_PATTERN, $name ) ) {
					$invalidNames[] = $name;
				}
			}
		}

		return array_unique( $invalidNames );
	}
}
