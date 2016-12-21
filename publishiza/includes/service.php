<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Require parent service
if ( ! class_exists( 'Keyring_Service_Twitter' ) ) {
	require_once dirname( dirname( __FILE__ ) ) . '/keyring/includes/services/extended/twitter.php';
}

/**
 * Twitter service definition for Publishiza. Clean implementation of OAuth1
 */
class Keyring_Service_Publishiza extends Keyring_Service_Twitter {
	const NAME  = 'publishiza';
	const LABEL = 'Publishiza';

	// Shh...
	public $key    = 'l0HUV4SmfYGpGbmdYOEeuYihi';
	public $secret = 'I2ipEkUsTVflVLi7GoDZsoCNGGzf1xjXhu8iy0ceAjkJPPOsq7';

	/**
	 * Connect actions and register the update endpoint
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// UI actions
		if ( ! KEYRING__HEADLESS_MODE ) {
			add_action( 'keyring_publishiza_manage_ui',      array( $this, 'basic_ui'       ) );
			add_filter( 'keyring_publishiza_basic_ui_intro', array( $this, 'basic_ui_intro' ) );
		}

		$this->set_endpoint( 'update', 'https://api.twitter.com/1.1/statuses/update.json', 'POST' );

		parent::__construct();
	}

	/**
	 * Return Publishiza credentials
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function _get_credentials() {
		return array(
			'app_id' => '',
			'key'    => $this->key,
			'secret' => $this->secret,
		);
	}

	/**
	 *
	 * @since 1.0.0
	 */
	function basic_ui() {

		// Bail if no nonce
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'keyring-manage-' . $this->get_name() ) ) {
			Keyring::error( __( 'Invalid/missing management nonce.', 'publishiza' ) );
			exit;
		}

		// Common Header
		echo '<div class="wrap">';
		screen_icon( 'ms-admin' );
		echo '<h2>' . __( 'Service Management', 'publishiza' ) . '</h2>';
		echo '<p><a href="' . Keyring_Util::admin_url( false, array( 'action' => 'services' ) ) . '">' . __( '&larr; Back', 'publishiza' ) . '</a></p>';
		echo '<h3>' . sprintf( __( '%s Connection', 'publishiza' ), esc_html( $this->get_label() ) ) . '</h3>';

		// Handle actually saving credentials
		if ( isset( $_POST['api_key'] ) && isset( $_POST['api_secret'] ) ) {
			// Store credentials against this service
			$this->update_credentials( array(
				'app_id' => ( ! empty( $_POST['app_id']     ) ? stripslashes( $_POST['app_id'] )     : '' ),
				'key'    => ( ! empty( $_POST['api_key']    ) ? stripslashes( $_POST['api_key'] )    : '' ),
				'secret' => ( ! empty( $_POST['api_secret'] ) ? stripslashes( $_POST['api_secret'] ) : '' )
			) );
			echo '<div class="updated"><p>' . __( 'Credentials saved.', 'publishiza' ) . '</p></div>';
		}

		if ( $creds = $this->get_credentials() ) {
			$app_id     = $creds['app_id'];
			$api_key    = $creds['key'];
			$api_secret = $creds['secret'];
		} else {
			$app_id = $api_key = $api_secret = '';
		}

		echo apply_filters( 'keyring_' . $this->get_name() . '_basic_ui_intro', '' );

		// Output basic form for collecting key/secret
		echo '<form method="post" action="">';
		echo '<input type="hidden" name="service" value="' . esc_attr( $this->get_name() ) . '" />';
		echo '<input type="hidden" name="action" value="manage" />';

		wp_nonce_field( 'keyring-manage', 'kr_nonce', false );
		wp_nonce_field( 'keyring-manage-' . $this->get_name(), 'nonce', false );

		$ui_app_id = '<input type="hidden" name="app_id" value="' . esc_attr( $app_id ) . '" id="app_id" class="regular-text">';

		echo apply_filters( 'keyring_' . $this->get_name() . '_basic_ui_app_id', $ui_app_id );

		$ui_api_key = '<input type="hidden" name="api_key" value="' . esc_attr( $api_key ) . '" id="api_key" class="regular-text">';

		echo apply_filters( 'keyring_' . $this->get_name() . '_basic_ui_api_key', $ui_api_key );

		$ui_api_secret = '<input type="hidden" name="api_secret" value="' . esc_attr( $api_secret ) . '" id="api_secret" class="regular-text">';

		echo apply_filters( 'keyring_' . $this->get_name() . '_basic_ui_api_secret', $ui_api_secret );

		echo '<p class="submitbox">';
		echo '<input type="submit" name="submit" value="' . __( 'Connect', 'publishiza' ) . '" id="submit" class="button-primary">';
		echo '<a href="' . esc_url( $_SERVER['HTTP_REFERER'] ) . '" class="submitdelete" style="margin-left:2em;">' . __( 'Cancel', 'publishiza' ) . '</a>';
		echo '</p>';
		echo '</form>';
		?><script type="text/javascript" charset="utf-8">
			jQuery( document ).ready( function() {
				jQuery( '#app_id' ).focus();
			} );
		</script><?php
		echo '</div>';
	}

	public function basic_ui_intro() {
		echo '<p>' . __( 'In a separate browser tab, login to Twitter with the account you would like to connect to Publishiza, then click "Connect"', 'publishiza' ) . '</p>';
	}
}

//
remove_action( 'keyring_load_services', array( 'Keyring_Service_Twitter',    'init' ) );
add_action(    'keyring_load_services', array( 'Keyring_Service_Publishiza', 'init' ) );
