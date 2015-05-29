<?php
/*
Plugin Name: Easy Digital Downloads - LocalNode Gateway
Plugin URL: https://github.com/bum2/edd-localnode-gateway
Description: A local-nodes gateway for Easy Digital Downloads, forked from Pippin's example and Aleph's manual gateway
Version: 0.2
Author: Bumbum
Author URI: https://github.com/bum2
*/

### Version
define( 'EDD_LOCALNODE_VERSION', 0.2 );

// Plugin constants
if ( ! defined( 'EDD_LOCALNODE' ) )
	define( 'EDD_LOCALNODE', '0.2' );

if ( ! defined( 'EDD_LOCALNODE_URL' ) )
	define( 'EDD_LOCALNODE_URL', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'EDD_LOCALNODE_DIR' ) )
	define( 'EDD_LOCALNODE_DIR', plugin_dir_path( __FILE__ ) );



### Create Text Domain For Translations
add_action( 'plugins_loaded', 'localnode_textdomain' );
function localnode_textdomain() {
  load_plugin_textdomain( 'edd-localnode-gateway', false, dirname( plugin_basename( __FILE__ ) ) );
}


//Load post fields management
require_once ( __DIR__ . '/localnode_edd_wp_post.php');



function edd_localnodes_admin_scripts( $hook ) {

	global $post;
	if ( is_object( $post ) && $post->post_type != 'download' ) {
		return;
	}

  wp_register_script( 'localnode-admin-script', plugins_url('edd-localnode-gateway/localnode-js.dev.js'), array( 'jquery', 'jquery-ui-menu' ), EDD_LOCALNODE, true );
	wp_enqueue_script( 'localnode-admin-script' );
}
add_action( 'admin_enqueue_scripts', 'edd_localnodes_admin_scripts' );


### Function: Enqueue JavaScripts/CSS
add_action('wp_enqueue_scripts', 'localnodes_scripts');
function localnodes_scripts() {

  wp_enqueue_script('edd-localnode-gateway', plugins_url('edd-localnode-gateway/localnode-js.dev.js'), array('jquery'), EDD_LOCALNODE_VERSION, true); // bumbum .dev.

	if(@file_exists(get_stylesheet_directory().'/localnode-css.css')) {

		wp_enqueue_style('edd-localnode-gateway', get_stylesheet_directory_uri().'/localnode-css.css', false, EDD_LOCALNODE_VERSION, 'all');

	} else {

		wp_enqueue_style('edd-localnode-gateway', plugins_url('edd-localnode-gateway/localnode-css.css'), false, EDD_LOCALNODE_VERSION, 'all');

	}
	/*if( is_rtl() ) {
		if(@file_exists(get_stylesheet_directory().'/localnode-css-rtl.css')) {
			wp_enqueue_style('edd-localnode-gateway-rtl', get_stylesheet_directory_uri().'/localnode-css-rtl.css', false,EDD_LOCALNODE_VERSION, 'all');
		} else {
			wp_enqueue_style('edd-localnode-gateway-rtl', plugins_url('edd-localnode-gateway/localnode-css-rtl.css'), false,EDD_LOCALNODE_VERSION, 'all');
		}
	}*/

  // <script src="//code.jquery.com/ui/1.11.2/jquery-ui.js"></script>

}


////  C U S T O M   P O S T   L O C A L N O D E  ////

// Add a custom Post-Type
add_action( 'init', 'create_posttype' );
function create_posttype() {
  register_post_type( 'gfc_localnode',
    array(
      'labels' => array(
        'name' => __( 'LocalNodes', 'edd-localnode-gateway' ),
        'singular_name' => __( 'LocalNode', 'edd-localnode-gateway' )
      ),
      'public' => true,
      'has_archive' => true,
      'rewrite' => array('slug' => 'localnodes'),
      'hierarchical' => true,
      'supports' => array('title', 'editor', 'thumbnail', 'page-attributes', 'custom-fields', 'author'),
    )
  );
}


////  G A T E W A Y   S P E C I F I C  ////

// registers the gateway
function fair_edd_register_gateway($gateways) {
	$gateways['localnode'] = array('admin_label' => 'LocalNode Gateway', 'checkout_label' => __('LocalNode Gateway', 'edd-localnode-gateway'));
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


	// errors can be set like this
	//if(!isset($_POST['edd_localnode']) || $_POST['edd_localnode'] == 'false' || !$_POST['edd_localnode']) {
		// error code followed by error message
	//	edd_set_error('empty_localnode', __('Please choose yout nearest local node', 'edd-localnode-gateway'));
	//}


	// check for any stored errors
	$errors = edd_get_errors();
	if ( ! $errors ) {

		$purchase_summary = edd_get_purchase_summary( $purchase_data );

		$payment = array(
			'price'        => $purchase_data['price'],
			'date'         => $purchase_data['date'],
			'user_email'   => $purchase_data['user_email'],
			'purchase_key' => $purchase_data['purchase_key'],
			'currency'     => $edd_options['currency'],
			'downloads'    => $purchase_data['downloads'],
			'cart_details' => $purchase_data['cart_details'],
			'user_info'    => $purchase_data['user_info'],
			'status'       => 'pending'
		);

		// record the pending payment
		$payment = edd_insert_payment( $payment );

		// send email with payment info
		localnode_email_purchase_order( $payment );

		edd_send_to_success_page();

	} else {
		$fail = true; // errors were detected
	}

	if ( $fail !== false ) {
		// if errors are present, send the user back to the purchase page so they can be corrected
		//edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
		edd_send_back_to_checkout( '/checkout' );
	}
}
add_action('edd_gateway_localnode', 'fair_edd_process_payment');

// adds the settings to the Payment Gateways section
function fair_edd_add_settings($settings) {

	$localnode_gateway_settings = array(
		array(
			'id' => 'localnode_gateway_settings',
			'name' => '<strong>' . __('LocalNode Gateway Settings', 'edd-localnode-gateway') . '</strong>',
			'desc' => __('Configure the localnodes gateway, adding a custom field to each LocalNode custom post (can be nested): localnode_email', 'edd-localnode-gateway'),
			'type' => 'header'
		),
		array(
			'id' => 'localnode_email_from',
			'name' => __('From Email Source', 'edd-localnode-gateway'),
			'desc' => __('Choose the remitent email From address on user notification, if use the general one setted in the GetMethod (download) or the one setted in the choosed LocalNode (custom field: localnode_email)', 'edd-localnode-gateway'),
			'type' => 'select',
			'options' => array(1 => 'ONE', 2 => 'LOCALNODE'),
			'std'  => 1
		),
		array(
			'id' => 'localnode_notify_node',
			'name' => __('LocalNode Email Notification', 'edd-localnode-gateway'),
			'desc' => __('Check if you want to notify also the localnode on user purchase (general admin addresses are always notified)', 'edd-localnode-gateway'),
			'type' => 'checkbox'
		),
	);

	return array_merge($settings, $localnode_gateway_settings);
}
add_filter('edd_settings_gateways', 'fair_edd_add_settings');


////   R E C E I P T    ////

function edd_localnode_payment_receipt_after($payment){ // TODO
  if( edd_get_payment_gateway( $payment->ID ) == 'localnode'){
    $payment_data = edd_get_payment_meta( $payment->ID );
    $downloads = edd_get_payment_meta_cart_details( $payment->ID );
    $post_id = $downloads[0]['id'];
    $message = stripslashes ( get_post_meta( $post_id, 'localnode_edd_wp_post_receipt', true ));
    $message = edd_do_email_tags( $message, $payment->ID );
    //$message = edd_get_payment_gateway( $payment->ID );
    echo $message;
  }
}
add_action('edd_payment_receipt_after_table', 'edd_localnode_payment_receipt_after');


////   E M A I L   T O   U S E R   ////

//Sent transfer instructions
function localnode_email_purchase_order ( $payment_id, $admin_notice = true ) {

	global $edd_options;

	$payment_data = edd_get_payment_meta( $payment_id );
	$user_id      = edd_get_payment_user_id( $payment_id );
	$user_info    = maybe_unserialize( $payment_data['user_info'] );
	$to           = edd_get_payment_user_email( $payment_id );

	if ( isset( $user_id ) && $user_id > 0 ) {
		$user_data = get_userdata($user_id);
		$name = $user_data->display_name;
	} elseif ( isset( $user_info['first_name'] ) && isset( $user_info['last_name'] ) ) {
		$name = $user_info['first_name'] . ' ' . $user_info['last_name'];
	} else {
		$name = $email;
	}

	$message = edd_get_email_body_header();


  $downloads = edd_get_payment_meta_cart_details( $payment_id );
  $post_id = $downloads[0]['id'];
  $email = stripslashes (get_post_meta( $post_id, 'localnode_edd_wp_post_body_mail', true ));
  $subject = wp_strip_all_tags(get_post_meta( $post_id, 'localnode_edd_wp_post_subject_mail', true ));

	if ( $edd_options['localnode_email_from'] == 1 ) { // general Email From set in the 'download' (get method) post
    $from_email = get_post_meta( $post_id, 'localnode_edd_wp_post_from_email', true );

  } else { // local Email From set in the LocalNode's custom field 'localnode_email'
    $paymeta = edd_get_payment_meta($payment_id);
    $nodeid = $paymeta['localnode_id'];
    $from_email = get_post_meta($nodeid, 'localnode_email', true);
  }


	$message .= edd_do_email_tags( $email, $payment_id );
	$message .= edd_get_email_body_footer();

	$from_name = get_bloginfo('name');

	$subject = edd_do_email_tags( $subject, $payment_id );

	$headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
	$headers .= "Reply-To: ". $from_email . "\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=utf-8\r\n";
	$headers = apply_filters( 'edd_receipt_headers', $headers, $payment_id, $payment_data );

	if ( apply_filters( 'edd_email_purchase_receipt', true ) ) {
		wp_mail( $to, $subject, $message, $headers );//, $attachments );
	}

	if ( $admin_notice && ! edd_admin_notices_disabled( $payment_id ) ) {
		do_action( 'localnode_admin_sale_notice', $payment_id, $payment_data );
	}
}

////   E M A I L   T O   A D M I N S   ////

/**
 * Sends the Admin Sale Notification Email
 *
 * @since 1.4.2
 * @param int $payment_id Payment ID (default: 0)
 * @param array $payment_data Payment Meta and Data
 * @return void
 */
function localnode_admin_email_notice( $payment_id = 0, $payment_data = array() ) {
	global $edd_options;

	/* Send an email notification to the admin */
	$admin_email = localnode_get_admin_notice_emails( $payment_id ); // bumbum
	$user_id     = edd_get_payment_user_id( $payment_id );
	$user_info   = maybe_unserialize( $payment_data['user_info'] );

	if ( isset( $user_id ) && $user_id > 0 ) {
		$user_data = get_userdata($user_id);
		$name = $user_data->display_name;
	} elseif ( isset( $user_info['first_name'] ) && isset( $user_info['last_name'] ) ) {
		$name = $user_info['first_name'] . ' ' . $user_info['last_name'];
	} else {
		$name = $user_info['email'];
	}

	$admin_message = edd_get_email_body_header();
	$admin_message .= edd_get_sale_notification_body_content( $payment_id, $payment_data );
	$admin_message .= edd_get_email_body_footer();

	if( ! empty( $edd_options['sale_notification_subject'] ) ) {
		$admin_subject = wp_strip_all_tags( $edd_options['sale_notification_subject'], true );
	} else {
		$admin_subject = sprintf( __( 'New download purchase - Order #%1$s', 'edd' ), $payment_id );
	}

	$admin_subject = edd_do_email_tags( $admin_subject, $payment_id );
	$admin_subject = apply_filters( 'edd_admin_sale_notification_subject', $admin_subject, $payment_id, $payment_data );

	$from_name  = isset( $edd_options['from_name'] )  ? $edd_options['from_name']  : get_bloginfo('name');
	$from_email = isset( $edd_options['from_email'] ) ? $edd_options['from_email'] : get_option('admin_email');

	$admin_headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
	$admin_headers .= "Reply-To: ". $from_email . "\r\n";
	//$admin_headers .= "MIME-Version: 1.0\r\n";
	$admin_headers .= "Content-Type: text/html; charset=utf-8\r\n";
	$admin_headers .= apply_filters( 'edd_admin_sale_notification_headers', $admin_headers, $payment_id, $payment_data );

	$admin_attachments = apply_filters( 'edd_admin_sale_notification_attachments', array(), $payment_id, $payment_data );

	wp_mail( $admin_email, $admin_subject, $admin_message, $admin_headers, $admin_attachments );
}
add_action( 'localnode_admin_sale_notice', 'localnode_admin_email_notice', 10, 2 );

/**
 * Retrieves the emails for which admin notifications are sent to (these can be
 * changed in the EDD Settings)
 *
 * @since 1.0
 * @global $edd_options Array of all the EDD Options
 * @return void
 */
function localnode_get_admin_notice_emails( $payment_id ) {
	global $edd_options;

	$emails = isset( $edd_options['admin_notice_emails'] ) && strlen( trim( $edd_options['admin_notice_emails'] ) ) > 0 ? $edd_options['admin_notice_emails'] : get_bloginfo( 'admin_email' );

	if(! empty( $edd_options['localnode_notify_node'] ) ) {
		$emails .= "\n".localnode_email_tag_NodeEmail( $payment_id ); //edd_do_email_tags( $emails, $payment_id ); // bumbum
	}

	$emails = array_map( 'trim', explode( "\n", $emails ) );

	return apply_filters( 'edd_admin_notice_emails', $emails, $payment_id );
}



////  E M A I L  T A G S  ////

/**
* Add Tags for use in either the purchase receipt email or admin notification emails
*/
function localnode_edd_setup_email_tags() {
	// Setup default tags array
	$email_tags = array(
		array(
			'tag'         => 'NodeEmail',
			'description' => __( 'LocalNode_from_email', 'edd-localnode-gateway' ),
			'function'    => 'localnode_email_tag_NodeEmail'
		),
		array(
			'tag'         => 'NodeContent',
			'description' => __( 'LocalNode_content', 'edd-localnode-gateway' ),
			'function'    => 'localnode_email_tag_NodeContent'
		),
    array(
      'tag'         => 'LocalNode',
      'description' => __( 'Customer\'s Choosed LocalNode Name', 'edd-localnode-gateway' ),
      'function'    => 'localnode_email_tag_LocalNode'
    ),
    array(
      'tag'         => 'LocalPOE',
      'description' => __( 'Customer\'s Choosed LocalNode Point Of Exchange', 'edd-localnode-gateway' ),
      'function'    => 'localnode_email_tag_LocalPOE'
    ),
    array(
      'tag'         => 'LocalNodeContent',
      'description' => __( 'Customer\'s Choosed LocalNode Point Of Exchange Message', 'edd-localnode-gateway' ),
      'function'    => 'localnode_email_tag_LocalNodeContent'
    )
	);
	// Apply edd_email_tags filter
	$email_tags = apply_filters( 'edd_email_tags', $email_tags );

  if ( function_exists( 'edd_add_email_tag' ) ) {
  	// Add email tags
  	foreach ( $email_tags as $email_tag ) {
  		edd_add_email_tag( $email_tag['tag'], $email_tag['description'], $email_tag['function'] );
  	}
  }
}
add_action( 'edd_add_email_tags', 'localnode_edd_setup_email_tags' );

/**
* The {NodeEmail} email tag
*/
function localnode_email_tag_NodeEmail( $payment_id ) {
	global $edd_options;
	if ( $edd_options['localnode_email_from'] == 1 ) {
		$downloads = edd_get_payment_meta_cart_details( $payment_id );
		$post_id = $downloads[0]['id'];
		$NodeEmail = get_post_meta( $post_id, 'localnode_edd_wp_post_from_email', true );
	} else {
    // get from choosed LocalNode custom field 'node_email' TODO
    $paymeta = edd_get_payment_meta($payment_id);
    $nodeid = $paymeta['localnode_id'];
    $NodeEmail = get_post_meta($nodeid, 'localnode_email', true); //$nodename.'::'.$nodeid.'::'
  }
	return $NodeEmail;
}

/**
* The {NodeContent} email tag
*/
function localnode_email_tag_NodeContent( $payment_id ) {
  //global $edd_options;
  $paymeta = edd_get_payment_meta($payment_id);
  //$nodename = $paymeta['localnode'];
  $nodeid = $paymeta['localnode_id'];
  if($nodeid){
    $node = get_post($nodeid);
    $NodeContent = $node->post_content;
  } else {
    $NodeContent = '';
  }
  return $NodeContent;
}

/**
* The {LocalPOE} email tag
*/
function localnode_email_tag_LocalPOE( $payment_id ) {
  $payment_data = edd_get_payment_meta( $payment_id );
  return $payment_data['localnode'];
}

/**
* The {LocalNode} email tag
*/
function localnode_email_tag_LocalNode( $payment_id ) {
  $payment_data = edd_get_payment_meta( $payment_id );
  $parentid = wp_get_post_parent_id( $payment_data['localnode_id'] );
  if($parentid){
    return get_the_title($parentid);
  } else {
    return $payment_data['localnode'];
  }
}

/**
* The {LocalNodeContent} email tag
*/
function localnode_email_tag_LocalNodeContent( $payment_id ) {
  $payment_data = edd_get_payment_meta( $payment_id );
  $parentid = wp_get_post_parent_id( $payment_data['localnode_id'] );
  if($parentid){
    $node = get_post($parentid);
    $NodeContent = $node->post_content;
    return $NodeContent;
  } else {
    return '';
  }
}

////


function localnode_display_form_select( $localnode, $nodeid ){
  if(!$localnode) $localnode = '';
  if(!$nodeid) $nodeid = '0';

  $placeholder = __('Use the menu below to choose a local node', 'edd-localnode-gateway');

  echo '<input type="text" name="edd_localnode" id="edd_localnode" class="edd-input" value="'.$localnode.'" readonly="readonly" placeholder="'.$placeholder.'" />';
  echo '<input type="hidden" name="edd_localnode_id" id="edd_localnode_id" value="'.$nodeid.'" readonly="readonly" />';

  $args = array(
  	//'authors'      => '',
  	//'child_of'     => 0,
  	//'date_format'  => get_option('date_format'),
  	'depth'        => 0,
  	//'echo'         => 1,
  	//'exclude'      => '',
  	//'include'      => '',
  	'link_after'   => '',
  	'link_before'  => '',
  	'post_type'    => 'gfc_localnode',
  	//'post_status'  => 'publish',
  	//'show_date'    => '',
  	//'sort_column'  => 'menu_order, post_title',
    //'sort_order'   => '',
  	'title_li'     => __('Choose...', 'edd-localnode-gateway'),
  	//'walker'       => ''
  );
  echo '<ul id="localnode-menu">';
  wp_list_pages( $args );
  echo '</ul>';

}


////  N E W   F I E L D S  ////

/**
* Display localnode field at checkout
*
*/
function localnode_edd_display_checkout_fields() { // get user's localnode if they already have one stored
  $download_ids = edd_get_cart_contents();
  $download_ids = wp_list_pluck( $download_ids, 'id' );
  if ( get_post_meta( $download_ids[0], '_edd_gateway', true) == 'localnode' ) {  // only one item on cart!
  //if( has_term( 'LocalNode', 'download_category', $download_ids[0] ) ){ // only one item on cart!
    if ( is_user_logged_in() ) {
      $user_id = get_current_user_id();
      $localnode = get_the_author_meta( '_edd_user_localnode', $user_id );
      $localnode_id = get_the_author_meta( '_edd_user_localnode_id', $user_id);
    }
    $localnode = isset( $localnode ) ? esc_attr( $localnode ) : '';
    $localnode_id = isset( $localnode_id ) ? esc_attr( $localnode_id ) : '';
    ?>
    <p id="edd-localnode-wrap">
      <label class="edd-label" for="edd_localnode">
        <?php echo _e('Local Node Selection', 'edd-localnode-gateway'); ?>
      </label>
      <span class="edd-description">
        <?php echo _e('Please choose your nearest local-node and you\'ll receive its contact details', 'edd-localnode-gateway'); ?>
      </span>
    <?php
          localnode_display_form_select($localnode, $localnode_id);
    ?>
    </p>
    <?php
  } else {
    return;
  }
}
add_action( 'edd_purchase_form_user_info', 'localnode_edd_display_checkout_fields' );


/**
* Make localnode required
* Add more required fields here if you need to
*/
function localnode_edd_required_checkout_fields( $required_fields ) {
  $download_ids = edd_get_cart_contents();
  $download_ids = wp_list_pluck( $download_ids, 'id' );
  if( has_term( 'LocalNode', 'download_category', $download_ids[0] ) ){ // only one item on cart!
    $user_id = get_current_user_id();
    $required_fields['edd_localnode'] = array(
            'error_id' => 'invalid_localnode',
            'error_message' => __('Please enter a valid LocalNode', 'edd-localnode-gateway')
    );
  }
  return $required_fields;
}
add_filter( 'edd_purchase_form_required_fields', 'localnode_edd_required_checkout_fields' );


/**
* Set error if localnode field is empty
* You can do additional error checking here if required
*/
function localnode_edd_validate_checkout_fields( $valid_data, $data ) {
  $download_ids = edd_get_cart_contents();
  $download_ids = wp_list_pluck( $download_ids, 'id' );
  if( has_term( 'LocalNode', 'download_category', $download_ids[0] ) ){ // only one item on cart!
    $user_id = get_current_user_id();
		$args = array(
			'post_parent' => $data['edd_localnode_id'],
			'post_type'   => 'gfc_localnode',
			'posts_per_page' => -1,
			'post_status' => 'published' );
		$poes = get_children($args);
    if ( empty( $data['edd_localnode'] ) || $data['edd_localnode'] == '' || empty( $data['edd_localnode_id'] ) || $data['edd_localnode_id'] == '0') {
      edd_set_error( 'invalid_localnode', __('Please choose your nearest LocalNode.', 'edd-localnode-gateway') );
    } else if (count($poes) > 0){
			edd_set_error( 'invalid_localnode', __('Please choose your nearest Point Of Exchange inside this LocalNode', 'edd-localnode-gateway') );
		}
  }
}
add_action( 'edd_checkout_error_checks', 'localnode_edd_validate_checkout_fields', 10, 2 );


/**
* Store the custom field data into EDD's payment meta
*/
function localnode_edd_store_custom_fields( $payment_meta ) {
  $payment_meta['localnode'] = isset( $_POST['edd_localnode'] ) ? sanitize_text_field( $_POST['edd_localnode'] ) : '';
  $payment_meta['localnode_id'] = isset( $_POST['edd_localnode_id'] ) ? sanitize_text_field( $_POST['edd_localnode_id'] ) : '';
  return $payment_meta;
}
add_filter( 'edd_payment_meta', 'localnode_edd_store_custom_fields');


/**
* Add the localnode to the "View Order Details" page
*/
function localnode_edd_view_order_details( $payment_meta, $user_info ) {
  $localnode = isset( $payment_meta['localnode'] ) ? $payment_meta['localnode'] : '';
  $localnode_id = isset( $payment_meta['localnode_id'] ) ? $payment_meta['localnode_id'] : '';

  ?>
  <div class="column-container">
    <div class="column">
      <strong><?php echo _e('LocalNode: ', 'edd-localnode-gateway'); ?></strong>
      <?php localnode_display_form_select($localnode, $localnode_id); ?>
      <!-- <input type="text" name="edd_localnode" value="<?php esc_attr_e( $localnode ); ?>" class="medium-text" /> -->
      <p class="description"><?php _e( 'Customer choosed LocalNode', 'edd-localnode-gateway' ); ?></p>
    </div>
  </div>
  <?php
}
add_action( 'edd_payment_personal_details_list', 'localnode_edd_view_order_details', 10, 2 );


/**
* Save the localnode field when it's modified via view order details
*/
function localnode_edd_updated_edited_purchase( $payment_id ) {
  // get the payment meta
  $payment_meta = edd_get_payment_meta( $payment_id );
  // update our localnode number
  $payment_meta['localnode'] = isset( $_POST['edd_localnode'] ) ? $_POST['edd_localnode'] : '';
  $payment_meta['localnode_id'] = isset( $_POST['edd_localnode_id'] ) ? $_POST['edd_localnode_id'] : '';
  // update the payment meta with the new array
  update_post_meta( $payment_id, '_edd_payment_meta', $payment_meta );
}
add_action( 'edd_updated_edited_purchase', 'localnode_edd_updated_edited_purchase' );




// email tags grouped above new field section



/**
* Update user's localnode in the wp_usermeta table
* This localnode number will be shown on the user's edit profile screen in the admin
*/
function localnode_edd_store_usermeta( $payment_id ) {
  // return if user is not logged in
  if ( ! is_user_logged_in() )
    return;
  // get the user's ID
  $user_id = get_current_user_id();
  // update localnode number
  update_user_meta( $user_id, '_edd_user_localnode', $_POST['edd_localnode'] );
  update_user_meta( $user_id, '_edd_user_localnode_id', $_POST['edd_localnode_id'] );
}
add_action( 'edd_complete_purchase', 'localnode_edd_store_usermeta' );


/**
* Save the field when the values are changed on the user's WP profile page
*/
function localnode_edd_save_extra_profile_fields( $user_id ) {
  if ( ! current_user_can( 'edit_user', $user_id ) )
    return false;
  update_user_meta( $user_id, '_edd_user_localnode', $_POST['_edd_user_localnode'] );
  update_user_meta( $user_id, '_edd_user_localnode_id', $_POST['_edd_user_localnode_id'] );
}
add_action( 'personal_options_update', 'localnode_edd_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'localnode_edd_save_extra_profile_fields' );


/**
* Save the field when the value is changed on the EDD profile editor
*/
function localnode_edd_pre_update_user_profile( $user_id, $userdata ) {
  $localnode = isset( $_POST['edd_localnode'] ) ? $_POST['edd_localnode'] : '';
  $localnode_id = isset( $_POST['edd_localnode_id'] ) ? $_POST['edd_localnode_id'] : '';
  // Make sure user enters a localnode number
  if ( ! $localnode ) {
    edd_set_error( 'localnode_required', __( 'Please choose your nearest LocalNode', 'edd-localnode-gateway' ) );
  }
  // update localnode number
  update_user_meta( $user_id, '_edd_user_localnode', $localnode );
  update_user_meta( $user_id, '_edd_user_localnode_id', $localnode_id );
}
add_action( 'edd_pre_update_user_profile', 'localnode_edd_pre_update_user_profile', 10, 2 );


/**
* Add the localnode to the "Contact Info" section on the user's WP profile page
*/
function localnode_user_contactmethods( $methods, $user ) {
  $methods['_edd_user_localnode'] = 'Choosed LocalNode';
  $methods['_edd_user_localnode_id'] = 'Choosed LocalNode ID';
  return $methods;
}
add_filter( 'user_contactmethods', 'localnode_user_contactmethods', 10, 2 );
