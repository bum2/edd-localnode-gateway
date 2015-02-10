<?php

function localnode_text_callback ( $args, $post_id ) {
	$value = get_post_meta( $post_id, $args['id'], true );
	if ( $value != "" ) {
		$value = get_post_meta( $post_id, $args['id'], true );
	}else{
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$output = "<tr valign='top'> \n".
		" <th scope='row'> " . $args['name'] . " </th> \n" .
		" <td><input type='text' class='regular-text' id='" . $args['id'] . "'" .
		" name='" . $args['id'] . "' value='" .  $value   . "' />\n" .
		" <label for='" . $name . "'> " . $args['desc'] . "</label>" .
		"</td></tr>";

	return $output;
}

function localnode_rich_editor_callback ( $args, $post_id ) {
	$value = get_post_meta( $post_id, $args['id'], true );
	if ( $value != "" ) {
		$value = get_post_meta( $post_id, $args['id'], true );
	}else{
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}
	$output = "<tr valign='top'> \n".
		" <th scope='row'> " . $args['name'] . " </th> \n" .
		" <td>";
		ob_start();
		wp_editor( stripslashes( $value ) , $args['id'], array( 'textarea_name' => $args['id'] ) );
	$output .= ob_get_clean();

	$output .= " <label for='" . $name . "'> " . $args['desc'] . "</label>" .
		"</td></tr>\n";

	return $output;
}


/**
 * Updates when saving post
 *
 */
function localnode_edd_wp_post_save( $post_id ) {

	if ( ! isset( $_POST['post_type']) || 'download' !== $_POST['post_type'] ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return $post_id;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;

	$fields = localnode_wp_edd_fields();

	foreach ($fields as $field) {
		update_post_meta( $post_id, $field['id'],  $_REQUEST[$field['id']] );
	}
}
add_action( 'save_post', 'localnode_edd_wp_post_save' );


/**
 * Display sidebar metabox in saving post
 *
 */
function localnode_edd_wp_print_meta_box ( $post ) {

	if ( get_post_type( $post->ID ) != 'download' ) return;

	?>
	<div class="wrap">
		<div id="tab_container_local">
			<table class="form-table">
				<?php
					$fields = localnode_wp_edd_fields();
					foreach ($fields as $field) {
						if ( $field['type'] == 'text'){
							echo localnode_text_callback( $field, $post->ID );
						}elseif ( $field['type'] == 'rich_editor' ) {
							echo localnode_rich_editor_callback( $field, $post->ID ) ;
						}
					}
				?>

			</table>
		</div><!-- #tab_container-->
	</div><!-- .wrap -->
	<?php
}

function localnode_edd_wp_show_post_fields ( $post) {
	//print_r($post);
	add_meta_box( 'localnode_'.$post->ID, __( "LocalNodes Settings", 'edd-localnode-gateway'), "localnode_edd_wp_print_meta_box", 'download', 'normal', 'high');

}
add_action( 'submitpost_box', 'localnode_edd_wp_show_post_fields' );

function localnode_wp_edd_fields () {

	$localnode_gateway_settings = array(
		// bumbum
		array(
			'id' => 'localnode_edd_wp_post_receipt',
			'name' => __( 'localnode_gateway_receipt', 'edd-localnode-gateway' ),
			'desc' => __('localnode_gateway_receipt_desc', 'edd-localnode-gateway'),// . '<br/>' . edd_get_emails_tags_list()  ,
			'type' => 'rich_editor',
		),
		//
		array(
			'id' => 'localnode_edd_wp_post_from_email',
			'name' => __( 'from_gateway_email', 'edd-localnode-gateway' ),
			'desc' => __( 'from_gateway_email_desc', 'edd-localnode-gateway' ),
			'type' => 'text',
			'size' => 'regular',
			'std'  => get_bloginfo( 'admin_email' )
		),
		array(
			'id' => 'localnode_edd_wp_post_subject_mail',
			'name' => __( 'subject_gateway_mail', 'edd-localnode-gateway' ),
			'desc' => __( 'subject_gateway_mail_desc', 'edd-localnode-gateway' ),//  . '<br/>' . edd_get_emails_tags_list(),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'localnode_edd_wp_post_body_mail',
			'name' => __( 'body_gateway_mail', 'edd-localnode-gateway' ),
			'desc' => __('body_gateway_mail_desc', 'edd-localnode-gateway') . '<br/>' . edd_get_emails_tags_list()  ,
			'type' => 'rich_editor',
		),
	);

	return $localnode_gateway_settings;
}
