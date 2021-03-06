<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Register post meta
add_action( 'plugins_loaded', 'publishiza_register_post_meta' );

// Publishiza posts
add_action( 'publish_post', 'publishiza_publish_post', 10, 2 );

// Publishiza submit-box UI
add_action( 'post_submitbox_misc_actions', 'publishiza_post_submitbox_start' );

// Admin styling
add_action( 'admin_head', 'publishiza_admin_assets' );

// Maybe append short-link
add_filter( 'publishiza_storm_text', 'publishiza_append_short_link', 10, 3 );
