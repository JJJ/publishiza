<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Redirect to Keyring on activation
add_action( 'activated_plugin', 'publishiza_activation_redirect' );

// Register post meta
add_action( 'plugins_loaded', 'publishiza_register_post_meta' );

// Publishiza posts
add_action( 'publish_post', 'publishiza_publish_post', 10, 2 );

// Publishiza submit-box UI
add_action( 'post_submitbox_misc_actions', 'publishiza_post_submitbox_start' );

// Admin styling
add_action( 'admin_head', 'publishiza_admin_assets' );
