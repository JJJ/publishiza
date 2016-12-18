<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Register the post-meta key
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

	// Bail if not Publishiza'ing
	if ( empty( $_POST['publishiza'] ) ) {
		return;
	}

	// Bail if already Publishizaed
	if ( get_post_meta( $post_id, 'publishiza', true ) ) {
		return;
	}

	// Bail if nonce fails
	if ( ! wp_verify_nonce( 'publishiza-nonce', 'publishiza-select' ) ) {
		//return;
	}

	// Publishiza & get response
	$response = publishiza_maybe_post_to_twitter( $post );

	// Save response to prevent
	add_post_meta( $post_id, 'publishiza', array(
		'time'  => time(),
		'start' => $response
	) );
}

/**
 * Maybe Publishiza a WordPress post to Twitter
 *
 * Checks for valid service, tokens, and tweets first, and bails if there is a
 * problem.
 *
 * @since 1.0.0
 *
 * @param WP_Post $post
 */
function publishiza_maybe_post_to_twitter( $post = null ) {

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

	// Bail if not authed
	if ( empty( $tokens ) ) {
		return false;
	}

	// Process post content into Tweets
	$tweets = publishiza_get_tweets_from_text( $post->post_content );

	// Bail if no tweets
	if ( empty( $tweets ) ) {
		return false;
	}

	// Try to post tweets to Twitter
	return publishiza_post_tweets_to_twitter( array(
		'tweets'  => $tweets,
		'service' => $service,
		'tokens'  => $tokens
	) );
}

/**
 * Return array of strings for more predictable fitment into Twitter's current
 * 140 character restriction.
 *
 * @since 1.1.0
 *
 * @param string $text
 * @return array
 */
function publishiza_get_tweets_from_text( $text = '' ) {

	/**
	 * Filter length of individual tweets
	 *
	 * @since 1.0.0
	 *
	 * @param int 119 Maximum length of each tweet
	 * @return int
	 */
	$length = (int) apply_filters( 'publishiza_storm_length', 119 );

	// Format text blob for word wrapping
	$decoded  = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
	$stripped = wp_strip_all_tags( $decoded, true );

	/**
	 * Filter the plain-text, so it can be stormed
	 *
	 * @Since 1.1.0
	 *
	 * @param string $stripped Stripped text
	 * @param string $text     Original text
	 * @return string
	 */
	$filtered = (string) apply_filters( 'publishiza_storm_text', $stripped, $text );

	// Wrap text blob into a managable array
	$split   = wordwrap( $filtered, $length, "\n", false );
	$trimmed = array_map( 'trim', explode( "\n", $split ) );
	$tweets  = array_filter( $trimmed );

	/**
	 * Filter array of tweets, processed from $text
	 *
	 * @since 1.1.0
	 *
	 * @param array  $tweets Array of all tweets
	 * @param string $text   The original text
	 * @param int    $length The maximum length of each tweet
	 * @return array
	 */
	return (array) apply_filters( 'publishiza_format_post_content_for_twitter', $tweets, $text, $length );
}

/**
 * Sends multiple POST requests to the Twitter API, one for each tweet in the
 * array of tweets passed
 *
 * @since 1.1.0
 *
 * @param array $tweets
 * @param Keyring_Service $service
 *
 * @return boolean
 */
function publishiza_post_tweets_to_twitter( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'tweets'  => array(),
		'service' => null,
		'tokens'  => null
	) );

	// Bail if nothing to do
	if ( empty( $r['tweets'] ) || empty( $r['tokens'] ) || empty( $r['service'] ) ) {
		return false;
	}

	// Storm settings
	$sleep     = apply_filters( 'publishiza_storm_sleep',    2 );
	$control   = apply_filters( 'publishiza_storm_control',  html_entity_decode( '&#x1f4a9;&#x1f329; ', 0, 'UTF-8' ) );
	$ndash     = apply_filters( 'publishiza_storm_divider',  html_entity_decode( '&ndash;',             0, 'UTF-8' ) );
	$ellip     = apply_filters( 'publishiza_storm_ellipsis', html_entity_decode( '&hellip;',            0, 'UTF-8' ) );
	$count     = count( $r['tweets'] );
	$responses = array();
	$first     = false;

	// Loop through connections (multiple accounts can storm simultaneously)
	foreach ( $r['tokens'] as $token ) {

		// Set the token
		$r['service']->token = $token;
		$previous_status_id  = false;

		// Storm
		foreach ( $r['tweets'] as $index => $tweet ) {

			// Build the prefix
			$position = (int) $index + 1;
			$prefix   = apply_filters( 'publishiza_storm_prefix', "{$control}{$position}/{$count} {$ndash} " );
			$text     = "{$prefix}{$tweet}";

			// Maybe append an ellipsis
			if ( ! empty( $ellip ) && ( $position !== $count ) ) {
				$text = "{$text}{$ellip}";
			}

			// Get request body
			$body = array(
				'status'    => $text,
				'trim_user' => 1
			);

			// Maybe in response to the first ID
			if ( ( $position > 1 ) && ! empty( $previous_status_id ) ) {
				$body['in_reply_to_status_id'] = (int) $previous_status_id;
			}

			// Send update to Twitter
			$response = $r['service']->request( $r['service']->update_url, array(
				'method' => $r['service']->update_method,
				'body'   => $body
			) );

			// Error!
			if ( is_a( $response, 'Keyring_Error' ) ) {
				return false;
			}

			// Setup storm
			if ( isset( $response->id ) ) {
				$previous_status_id = $response->id;

				// Set first response ID
				if ( empty( $first ) && ( 1 === $position ) ) {
					$first = $response->id;
				}
			}

			// Add response to responses array (@todo: save for debug?)
			$responses[] = $response;

			// Wait to avoid being throttled
			sleep( $sleep );
		}
	}

	// Return ID of first tweet
	return $first;
}
