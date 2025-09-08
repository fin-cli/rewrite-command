<?php

if ( ! class_exists( 'FP_CLI' ) ) {
	return;
}

$fpcli_rewrite_autoloader = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $fpcli_rewrite_autoloader ) ) {
	require_once $fpcli_rewrite_autoloader;
}

FP_CLI::add_command( 'rewrite', 'Rewrite_Command' );
