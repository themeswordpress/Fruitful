<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for CCAvenue Gateway
 */
return array(
	'enabled' => array(
		'title' => __('Enable/Disable', 'ccave'),
		'type' => 'checkbox',
		'label' => __('Enable CCAvenue Payment Module.', 'ccave'),
		'default' => 'no'
	),
	'title' => array(
		'title' => __('Title:', 'ccave'),
		'type'=> 'text',
		'default' => __('CCAvenue', 'ccave')
	),
	'description' => array(
		'title' => __('Description:', 'ccave'),
		'type' => 'textarea',
		'default' => __('Pay securely by Credit or Debit card or internet banking through CCAvenue Secure Servers.', 'ccave')
	),
	'merchant_id' => array(
		'title' => __('Merchant ID', 'ccave'),
		'type' => 'text',
		
	),
	'access_code' => array(
		'title' => __('Access Code', 'ccave'),
		'type' => 'text',
		
	),
	'working_key' => array(
		'title' => __('Working Key', 'ccave'),
		'type' => 'password',
		
	),
	'testmode' => array(
		'title'       => __( 'CCAvenue Sandbox', 'ccave' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable CCAvenue sandbox', 'ccave' ),
		'default'     => 'no',
		
	),
	'debug' => array(
		'title'       => __( 'Debug Log', 'ccave' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable logging', 'ccave' ),
		'default'     => 'no',
		'description' => sprintf( __( 'Log CCAvenue events, such as requests, inside <code>%s</code>', 'ccave' ), wc_get_log_file_path( 'ccavenue' ) )
	),
);
