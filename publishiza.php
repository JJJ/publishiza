<?php

/**
 * Plugin Name: Publishiza
 * Plugin URI:  https://wordpress.org/plugins/publishiza/
 * Author:      John James Jacoby
 * Author URI:  https://profiles.wordpress.org/johnjamesjacoby/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Description: Publish your blog posts to Twitter via Tweetstorm
 * Version:     1.0.0
 * Text Domain: publishiza
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Include the Keyring files early files
 *
 * @since 1.0.0
 */
function _publishiza() {

	// Get the plugin path
	$plugin_path = publishiza_get_plugin_path();

	// Keyring
	if ( ! defined( 'KEYRING__VERSION' ) ) {
		require_once $plugin_path . 'keyring/keyring.php';
		require_once $plugin_path . 'includes/keyring.php';
	}
}
_publishiza();

/**
 * Include the rest of the files on plugins_loaded
 *
 * @since 1.0.0
 */
function publishiza_plugins_loaded() {

	// Get the plugin path
	$plugin_path = publishiza_get_plugin_path();

	// Includes
	require_once $plugin_path . 'includes/admin.php';
	require_once $plugin_path . 'includes/hooks.php';
	require_once $plugin_path . 'includes/twitter.php';

	// Service
	if ( ! defined( 'KEYRING__VERSION' ) ) {
		require_once $plugin_path . 'keyring/includes/services/core/http-basic.php';
		require_once $plugin_path . 'keyring/includes/services/core/oauth1.php';
		require_once $plugin_path . 'keyring/includes/services/core/oauth2.php';
		require_once $plugin_path . 'keyring/includes/services/extended/twitter.php';
	}

	// Included after above services
	require_once $plugin_path . 'includes/service.php';

	// Load translations
	load_plugin_textdomain( 'publishiza', false, $plugin_path . 'assets/lang/' );
}
add_action( 'plugins_loaded', 'publishiza_plugins_loaded' );

/**
 * Return the plugin path
 *
 * @since 1.0.0
 *
 * @return string
 */
function publishiza_get_plugin_path() {
	return plugin_dir_path( __FILE__ ) . 'publishiza/';
}

/**
 * Return the plugin URL
 *
 * @since 1.0.0
 *
 * @return string
 */
function publishiza_get_plugin_url() {
	return plugin_dir_url( __FILE__ ) . 'publishiza/';
}

/**
 * Return the asset version
 *
 * @since 1.0.0
 *
 * @return int
 */
function publishiza_get_asset_version() {
	return 201612150001;
}

/**
 * Redirect on activation
 *
 * @since 1.0.0
 */
function publishiza_activation_redirect( $plugin = '' ) {

	// Bail if not this plugin
	if ( $plugin !== plugin_basename( __FILE__ ) ) {
		return;
	}

	// Do the redirect
	$redirect_to = add_query_arg( array( 'page' => 'keyring', 'action' => 'services' ), admin_url( 'tools.php' ) );
	wp_safe_redirect( $redirect_to );
	exit;
}
add_action( 'activated_plugin', 'publishiza_activation_redirect' );
