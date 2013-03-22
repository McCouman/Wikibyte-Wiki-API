<?php
/**
 * @file Wikibyte API
 * @developer Michael McCouman jr.
 * @copyright Michael McCouman for Wikibyte 
 * @lizenz Copyright(c) 2012-13
 * @contact support@wikibyte.org
 *
 * Open Source on Github:
 * https://McCouman
 *
 * No Supported!!!
 */

// Wikibyte API Mode
define( 'WB_API', true );
if ( !function_exists( 'version_compare' ) || version_compare( phpversion(), '5.4' ) < 0 ) {
	// We need to use dirname( __FILE__ )
	require( dirname( __FILE__ ) . '/includes/PHPVersionError.php' );
	wfPHPVersionError( 'WBapi.php' );
}

// Initialise common code.
if ( isset( $_SERVER['MW_COMPILED'] ) ) {
	require ( 'wbcore/includes/WBSapi.php' );
} else {
	require ( __DIR__ . '/includes/WBSapi.php' );
}

wfProfileIn( 'WBapi.php' );
$starttime = microtime( true );

// URL safety checks
if ( !$wgRequest->checkUrlExtension() ) {
	return;
}

// Verify that the API has not been disabled
if ( !$wgEnableAPI ) {
	header( $_SERVER['SERVER_PROTOCOL'] . ' 500 MediaWiki configuration Error', true, 500 );
	echo( 'Wikibyte API is not enabled.'
		. '<pre><b>$wgEnableAPI=true;</b></pre>' );
	die(1);
}

$wgTitle = Title::makeTitle( NS_MAIN, 'API' );
$processor = new ApiMain( RequestContext::getMain(), $wgEnableWriteAPI );
$processor->execute();

// Execute any deferred updates
DeferredUpdates::doUpdates();
$endtime = microtime( true );
wfProfileOut( 'api.php' );
wfLogProfilingData();

// Log the request
if ( $wgAPIRequestLog ) {
	$items = array(
			wfTimestamp( TS_MW ),
			$endtime - $starttime,
			$wgRequest->getIP(),
			$_SERVER['HTTP_USER_AGENT']
	);
	$items[] = $wgRequest->wasPosted() ? 'POST' : 'GET';
	$module = $processor->getModule();
	if ( $module->mustBePosted() ) {
		$items[] = "action=" . $wgRequest->getVal( 'action' );
	} else {
		$items[] = wfArrayToCGI( $wgRequest->getValues() );
	}
	wfErrorLog( implode( ',', $items ) . "\n", $wgAPIRequestLog );
	wfDebug( "Logged WBAPI request to $wgAPIRequestLog\n" );
}
$lb = wfGetLBFactory();
$lb->shutdown();

