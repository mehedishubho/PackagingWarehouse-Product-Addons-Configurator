<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles adding a configured product to the cart with a server-recalculated
 * price (never trust the price sent from the browser), and displays the
 * chosen options on cart/checkout/order/emails.
 */
class PWC_Cart {

	public static function init() {
		add_action( 'wp_ajax_pwc_add_to_cart', [ __CLASS__, 'add_to_cart' ] );
		add_action( 'wp_ajax_nopriv_pwc_add_to_cart', [ __CLASS__, 'add_to_cart' ] );

		add_filter( 'woocommerce_add_cart_item_data', [ __CLASS__, 'add_cart_item_data' ], 10, 2 );
		add_action( 'woocommerce_before_calculate_totals', [ __CLASS__, 'apply_custom_price' ] );
		add_filter( 'woocommerce_get_item_data', [ __CLASS__, 'display_cart_item_data' ], 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', [ __CLASS__, 'add_order_line_meta' ], 10, 4 );
	}

	public static function add_to_cart() {
		check_ajax_referer( 'pwc_nonce', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? (int) $_POST['product_id'] : 0;
		$selections = isset( $_POST['selections'] ) ? (array) $_POST['selections'] : [];

		$clean = [];
		foreach ( $selections as $k => $v ) {
			$clean[ sanitize_key( $k ) ] = is_array( $v ) ? '' : sanitize_text_field( wp_unslash( $v ) );
		}

		$result = PWC_Pricing::calculate( $product_id, $clean );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		// One box in the cart line = the whole configured quantity/order,
		// so the cart quantity is always 1; the actual unit count lives
		// in the selections and is shown in the line-item meta.
		$cart_item_key = WC()->cart->add_to_cart( $product_id, 1, 0, [], [
			'pwc_selections' => $clean,
			'pwc_price'      => $result['total_net'], // ex-VAT unit price stored on the line
			'pwc_breakdown'  => $result,
		] );

		if ( ! $cart_item_key ) {
			wp_send_json_error( [ 'message' => 'Could not add to cart. Please check your selections.' ] );
		}

		wp_send_json_success( [
			'message'   => 'Added to cart.',
			'cart_url'  => wc_get_cart_url(),
			'breakdown' => $result,
		] );
	}

	public static function add_cart_item_data( $cart_item_data, $product_id ) {
		// data is injected directly in add_to_cart() above via the 6th
		// arg of WC()->cart->add_to_cart(); nothing extra needed here,
		// this filter is kept as an extension point.
		return $cart_item_data;
	}

	/** This is the line that actually makes the custom price stick */
	public static function apply_custom_price( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
		foreach ( $cart->get_cart() as $item ) {
			if ( isset( $item['pwc_price'] ) ) {
				$item['data']->set_price( floatval( $item['pwc_price'] ) );
			}
		}
	}

	public static function display_cart_item_data( $item_data, $cart_item ) {
		if ( empty( $cart_item['pwc_breakdown']['selections_summary'] ) ) return $item_data;

		$b = $cart_item['pwc_breakdown'];
		$item_data[] = [ 'name' => 'Quantity (units)', 'value' => number_format_i18n( $b['quantity'] ) ];
		$item_data[] = [ 'name' => 'Versions', 'value' => $b['versions'] ];
		$item_data[] = [ 'name' => 'Format', 'value' => $b['dimension_label'] ];
		foreach ( $b['selections_summary'] as $label => $value ) {
			$item_data[] = [ 'name' => $label, 'value' => $value ];
		}
		return $item_data;
	}

	public static function add_order_line_meta( $item, $cart_item_key, $values, $order ) {
		if ( empty( $values['pwc_breakdown']['selections_summary'] ) ) return;
		$b = $values['pwc_breakdown'];
		$item->add_meta_data( 'Quantity (units)', number_format_i18n( $b['quantity'] ) );
		$item->add_meta_data( 'Versions', $b['versions'] );
		$item->add_meta_data( 'Format', $b['dimension_label'] );
		foreach ( $b['selections_summary'] as $label => $value ) {
			$item->add_meta_data( $label, $value );
		}
	}
}
