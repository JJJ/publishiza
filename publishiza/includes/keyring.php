<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Limit to Twitter, but only if Keyring wasn't included already
 *
 * @since 1.0.0
 *
 * @param array $services
 *
 * @return array
 */
function publishiza_keyring_services( $services = array() ) {
	$plugin_path      = publishiza_get_plugin_path();
	$keyring_services = glob( $plugin_path . 'keyring/includes/services/core/*.php' );
	$keyring_services[] = $plugin_path . 'includes/service.php';

	return $keyring_services;
}
add_filter( 'keyring_services', 'publishiza_keyring_services' );
