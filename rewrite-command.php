<?php

if ( ! class_exists( 'FIN_CLI' ) ) {
	return;
}

$fincli_rewrite_autoloader = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $fincli_rewrite_autoloader ) ) {
	require_once $fincli_rewrite_autoloader;
}

FIN_CLI::add_command( 'rewrite', 'Rewrite_Command' );
