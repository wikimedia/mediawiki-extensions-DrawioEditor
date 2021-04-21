<?php

// DEPRECATED, use wfLoadExtension( 'DrawioEditor' );
if ( !ExtensionRegistry::getInstance()->isLoaded( 'DrawioEditor' ) ) {
	wfLoadExtension( 'DrawioEditor' );
}
