<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Register the postmeta key
 *
 * @since 1.0.0
 */
function publishiza_register_post_meta() {
	register_meta( 'post', 'publishiza', array(
		'type'              => 'array',
		'description'       => esc_html__( 'Publishiza response information from Twitter', 'publishiza' ),
		'single'            => true,
		'sanitize_callback' => 'publishiza_sanitize_post_meta',
		'auth_callback'     => 'publishiza_auth_post_meta',
		'show_in_rest'      => true
	) );
}

/**
 * Publishiza to Twitter
 *
 * @since 1.0.0
 *
 * @param int $post_id
 * @param WP_Post $post
 */
function publishiza_publish_post( $post_id = 0, $post = null ) {

	// Bail if already Publishizaed
	if ( get_post_meta( $post_id, 'publishiza' ) ) {
		//return;
	}

	// Publishiza to twitter
	$response = publishiza_post_to_twitter( $post );

	// Get response from Twitter

	// Save response to prevent
	add_post_meta( $post_id, 'publishiza', array(
		'time' => time()
	) );
}

/**
 * Publishiza a WordPress post to Twitter
 *
 * @since 1.0.0
 *
 * @param WP_Post $post
 */
function publishiza_post_to_twitter( $post = null ) {

	/**
	 * @var Keyring_Service_Twitter $service
	 */
	$service = Keyring::get_service_by_name( 'publishiza' );

	// Bail if no connection to Twitter
	if ( ! $service->is_connected() ) {
		return;
	}

	//$token = $service->build_token_meta();
	$tokens = Keyring::get_token_store()->get_tokens( array(
		'service' => 'publishiza',
		'user_id' => $post->post_author
	) );

	// Not authed
	if ( empty( $tokens ) ) {
		return;
	}

	// Set the Twitter token
	$service->token = reset( $tokens );


	// Storm settings
	$length  = apply_filters( 'publishiza_storm_length',   120 );
	$control = apply_filters( 'publishiza_storm_control',  html_entity_decode( '&#x1f4a9;&#x1f329;', 0, 'UTF-8') );
	$ndash   = apply_filters( 'publishiza_storm_divider',  html_entity_decode( '&ndash;',  0, 'UTF-8' ) );
	$ellip   = apply_filters( 'publishiza_storm_ellipsis', html_entity_decode( '&hellip;', 0, 'UTF-8' ) );

	// Process post content
	$original_poo = $post->post_content;
	$stripped     = strip_tags( $original_poo );
	$split        = wordwrap( $stripped, $length );
	$tweets       = explode( "\n", $split );
	$count        = count( $tweets );
	$responses    = array();

	// Storm
	foreach ( $tweets as $index => $tweet ) {
		$position = (int) $index + 1;
		$prefix   = apply_filters( 'publishiza_storm_prefix', "{$control} {$position}/{$count} {$ndash} " );
		$text     = "{$prefix}{$tweet}";

		// Maybe append an ellipsis
		if ( $position !== $count ) {
			$text = "{$text}{$ellip}";
		}

		// Push to Twitter
		$responses[] = $service->request( 'https://api.twitter.com/1.1/statuses/update.json', array(
			'method' => 'POST',
			'body'   => array(
				'status' => $text
			)
		) );

		// Wait, to avoid being throttled
		sleep( 2 );
	}

	return true;
}
