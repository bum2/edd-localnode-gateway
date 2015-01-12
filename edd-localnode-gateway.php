<?php
/*
Plugin Name: Easy Digital Downloads - LocalNode Gateway
Plugin URL: https://github.com/bum2
Description: A local-nodes gateway for Easy Digital Downloads, forked from Pippin's example.
Version: 0.1
Author: Bumbum
Author URI: https://github.com/bum2
*/


// registers the gateway
function fair_edd_register_gateway($gateways) {
	$gateways['localnode'] = array('admin_label' => 'LocalNode Gateway', 'checkout_label' => __('LocalNode Gateway', 'fair_edd'));
	return $gateways;
}
add_filter('edd_payment_gateways', 'fair_edd_register_gateway');

function fair_edd_localnode_gateway_cc_form() {
	// register the action to remove default CC form
	return;
}
add_action('edd_localnode_cc_form', 'fair_edd_localnode_gateway_cc_form');

// processes the payment
function fair_edd_process_payment($purchase_data) {

	global $edd_options;

	/**********************************
	* set transaction mode
	**********************************/

	if(edd_is_test_mode()) {
		// set test credentials here
	} else {
		// set live credentials here
	}

	/**********************************
	* check for errors here
	**********************************/

	/*
	// errors can be set like this
	if(!isset($_POST['card_number'])) {
		// error code followed by error message
		edd_set_error('empty_card', __('You must enter a card number', 'edd'));
	}
	*/

	// check for any stored errors
	$errors = edd_get_errors();
	if(!$errors) {

		$purchase_summary = edd_get_purchase_summary($purchase_data);

		/**********************************
		* setup the payment details
		**********************************/

		$payment = array(
			'price' => $purchase_data['price'],
			'date' => $purchase_data['date'],
			'user_email' => $purchase_data['user_email'],
			'purchase_key' => $purchase_data['purchase_key'],
			'currency' => $edd_options['currency'],
			'downloads' => $purchase_data['downloads'],
			'cart_details' => $purchase_data['cart_details'],
			'user_info' => $purchase_data['user_info'],
			'status' => 'pending'
		);

		// record the pending payment
		$payment = edd_insert_payment($payment);

		$merchant_payment_confirmed = false;

		/**********************************
		* Process the credit card here.
		* If not using a credit card
		* then redirect to merchant
		* and verify payment with an IPN
		**********************************/

		// if the merchant payment is complete, set a flag
		$merchant_payment_confirmed = true;

		if($merchant_payment_confirmed) { // this is used when processing credit cards on site

			// once a transaction is successful, set the purchase to complete
			edd_update_payment_status($payment, 'complete');

			// go to the success page
			edd_send_to_success_page();

		} else {
			$fail = true; // payment wasn't recorded
		}

	} else {
		$fail = true; // errors were detected
	}

	if( $fail !== false ) {
		// if errors are present, send the user back to the purchase page so they can be corrected
		edd_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['edd-gateway']);
	}
}
add_action('edd_gateway_localnode_gateway', 'fair_edd_process_payment');

// adds the settings to the Payment Gateways section
function fair_edd_add_settings($settings) {

	$localnode_gateway_settings = array(
		array(
			'id' => 'localnode_gateway_settings',
			'name' => '<strong>' . __('LocalNode Gateway Settings', 'fair_edd') . '</strong>',
			'desc' => __('Configure the gateway settings', 'fair_edd'),
			'type' => 'header'
		),
		array(
			'id' => 'live_api_key',
			'name' => __('Live API Key', 'fair_edd'),
			'desc' => __('Enter your live API key, found in your gateway Account Settins', 'fair_edd'),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'test_api_key',
			'name' => __('Test API Key', 'fair_edd'),
			'desc' => __('Enter your test API key, found in your Stripe Account Settins', 'fair_edd'),
			'type' => 'text',
			'size' => 'regular'
		)
	);

	return array_merge($settings, $localnode_gateway_settings);
}
add_filter('edd_settings_gateways', 'fair_edd_add_settings');
