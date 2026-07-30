<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PWC_Ajax {

	public static function init() {
		add_action( 'wp_ajax_pwc_calculate_price', [ __CLASS__, 'calculate_price' ] );
		add_action( 'wp_ajax_nopriv_pwc_calculate_price', [ __CLASS__, 'calculate_price' ] );
	}

	public static function calculate_price() {
		check_ajax_referer( 'pwc_nonce', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? (int) $_POST['product_id'] : 0;
		$selections = isset( $_POST['selections'] ) ? (array) $_POST['selections'] : [];

		// sanitize: keys are field slugs, values are scalar option indices/strings
		$clean = [];
		foreach ( $selections as $k => $v ) {
			$clean[ sanitize_key( $k ) ] = is_array( $v ) ? '' : sanitize_text_field( wp_unslash( $v ) );
		}

		if ( ! $product_id || ! get_post( $product_id ) ) {
			wp_send_json_error( [ 'message' => 'Invalid product.' ] );
		}

		$result = PWC_Pricing::calculate( $product_id, $clean );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		// pre-format currency strings server-side so JS doesn't need to guess locale
		foreach ( [ 'base_price', 'additional_options', 'total_net', 'vat_amount', 'total_incl_vat' ] as $k ) {
			$result[ $k . '_fmt' ] = html_entity_decode( wp_strip_all_tags( wc_price( $result[ $k ] ) ) );
		}

		wp_send_json_success( $result );
	}
}
