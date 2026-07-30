<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Resolves which field groups / dimension presets apply to a product,
 * and turns a set of customer selections into a full price breakdown.
 */
class PWC_Pricing {

	/** Fixed quantity tiers - edit values/multipliers on the Settings screen */
	public static function get_quantity_tiers() {
		$saved = get_option( 'pwc_quantity_tiers' );
		if ( is_array( $saved ) && ! empty( $saved ) ) return $saved;
		// sensible defaults matching the requested tier list
		return [
			[ 'qty' => 500,    'multiplier' => 1.00 ],
			[ 'qty' => 1000,   'multiplier' => 0.90 ],
			[ 'qty' => 5000,   'multiplier' => 0.78 ],
			[ 'qty' => 10000,  'multiplier' => 0.68 ],
			[ 'qty' => 20000,  'multiplier' => 0.60 ],
			[ 'qty' => 40000,  'multiplier' => 0.53 ],
			[ 'qty' => 50000,  'multiplier' => 0.50 ],
			[ 'qty' => 75000,  'multiplier' => 0.46 ],
			[ 'qty' => 100000, 'multiplier' => 0.43 ],
		];
	}

	public static function get_versions_options() {
		return [ 1, 2, 3 ];
	}

	/**
	 * Shared matching rule used by both Dimension Presets and Field Groups:
	 * a post applies to a product if EITHER its assigned categories overlap
	 * the product's categories, OR the product itself is in its assigned
	 * products list. If neither categories nor products were set on the
	 * post, it's global and applies to everything.
	 */
	private static function is_assigned( $assigned_cat_ids, $assigned_product_ids, $product_id, $product_cat_ids ) {
		if ( empty( $assigned_cat_ids ) && empty( $assigned_product_ids ) ) return true; // global
		if ( ! empty( $assigned_product_ids ) && in_array( (int) $product_id, $assigned_product_ids, true ) ) return true;
		if ( ! empty( $assigned_cat_ids ) && array_intersect( $assigned_cat_ids, $product_cat_ids ) ) return true;
		return false;
	}

	/** All dimension presets assigned to this product (by category and/or individually) */
	public static function get_dimensions_for_product( $product_id ) {
		$cat_ids = wc_get_product_term_ids( $product_id, 'product_cat' );
		$all     = get_posts( [ 'post_type' => 'pwc_dimension', 'numberposts' => -1 ] );
		$out     = [];
		foreach ( $all as $dim ) {
			$assigned_cats     = get_post_meta( $dim->ID, '_pwc_categories', true );
			$assigned_cats     = $assigned_cats ? array_map( 'intval', (array) $assigned_cats ) : [];
			$assigned_products = get_post_meta( $dim->ID, '_pwc_products', true );
			$assigned_products = $assigned_products ? array_map( 'intval', (array) $assigned_products ) : [];

			if ( ! self::is_assigned( $assigned_cats, $assigned_products, $product_id, $cat_ids ) ) continue;

			$out[] = [
				'id'     => $dim->ID,
				'label'  => $dim->post_title,
				'length' => (float) get_post_meta( $dim->ID, '_pwc_length_mm', true ),
				'width'  => (float) get_post_meta( $dim->ID, '_pwc_width_mm', true ),
				'height' => (float) get_post_meta( $dim->ID, '_pwc_height_mm', true ),
			];
		}
		return $out;
	}

	/**
	 * All field groups that apply to this product:
	 *   category/product match (default assignment on the group itself)
	 *   + any groups explicitly added via the product's "extra groups"
	 *   - minus any groups explicitly excluded on the product
	 */
	public static function get_field_groups_for_product( $product_id ) {
		$cat_ids     = wc_get_product_term_ids( $product_id, 'product_cat' );
		$extra_ids   = get_post_meta( $product_id, '_pwc_extra_groups', true );
		$extra_ids   = $extra_ids ? array_map( 'intval', (array) $extra_ids ) : [];
		$excluded_ids = get_post_meta( $product_id, '_pwc_excluded_groups', true );
		$excluded_ids = $excluded_ids ? array_map( 'intval', (array) $excluded_ids ) : [];

		$all     = get_posts( [ 'post_type' => 'pwc_field_group', 'numberposts' => -1 ] );
		$matched = [];

		foreach ( $all as $group ) {
			if ( in_array( $group->ID, $excluded_ids, true ) ) continue;

			$is_extra = in_array( $group->ID, $extra_ids, true );

			if ( ! $is_extra ) {
				$assigned_cats     = get_post_meta( $group->ID, '_pwc_categories', true );
				$assigned_cats     = $assigned_cats ? array_map( 'intval', (array) $assigned_cats ) : [];
				$assigned_products = get_post_meta( $group->ID, '_pwc_products', true );
				$assigned_products = $assigned_products ? array_map( 'intval', (array) $assigned_products ) : [];

				if ( ! self::is_assigned( $assigned_cats, $assigned_products, $product_id, $cat_ids ) ) continue;
			}

			$fields_json = get_post_meta( $group->ID, '_pwc_fields', true );
			$fields      = $fields_json ? json_decode( $fields_json, true ) : [];
			if ( empty( $fields ) ) continue;

			$pricing_enabled = get_post_meta( $group->ID, '_pwc_pricing_enabled', true );
			$pricing_enabled = $pricing_enabled === '' ? true : (bool) intval( $pricing_enabled );
			$multiplier      = get_post_meta( $group->ID, '_pwc_price_multiplier', true );
			$multiplier      = $multiplier === '' ? 1.0 : (float) $multiplier;

			$matched[] = [
				'id'              => $group->ID,
				'label'           => $group->post_title,
				'fields'          => $fields,
				'pricing_enabled' => $pricing_enabled,
				'multiplier'      => $multiplier,
			];
		}
		return $matched;
	}

	/**
	 * Flattened lookup: field_key => field definition, across all groups
	 * for this product. Each field carries its parent group's pricing
	 * enabled/multiplier so calculate() can apply group-level control
	 * without a second lookup.
	 */
	public static function get_flat_fields( $product_id ) {
		$flat = [];
		foreach ( self::get_field_groups_for_product( $product_id ) as $group ) {
			foreach ( $group['fields'] as $field ) {
				$field['_group_pricing_enabled'] = $group['pricing_enabled'];
				$field['_group_multiplier']      = $group['multiplier'];
				$flat[ $field['key'] ] = $field;
			}
		}
		return $flat;
	}

	/**
	 * Box surface-area formula (m²) used for per_sqm priced fields.
	 * This is a generic "total board consumed per box" approximation
	 * (front+back+sides+top/bottom flaps). Swap this out per box style
	 * via the `pwc_box_area_m2` filter if a style needs an exact die-line.
	 */
	public static function box_area_m2( $length_mm, $width_mm, $height_mm ) {
		$l = $length_mm / 1000; $w = $width_mm / 1000; $h = $height_mm / 1000;
		$area = 2 * ( ( $l * $h ) + ( $w * $h ) ) + 1.6 * ( $l * $w ); // body + top/bottom flap allowance
		return apply_filters( 'pwc_box_area_m2', $area, $length_mm, $width_mm, $height_mm );
	}

	private static function find_tier_multiplier( $qty ) {
		foreach ( self::get_quantity_tiers() as $tier ) {
			if ( (int) $tier['qty'] === (int) $qty ) return (float) $tier['multiplier'];
		}
		return 1.0;
	}

	/**
	 * Main entry point. $selections is an assoc array of field_key => option_value
	 * (option_value = the option's index/id within that field), plus reserved
	 * keys: quantity, versions, dimension_id.
	 *
	 * Returns the full breakdown matching the "Offer" panel: base_price,
	 * additional_options, total_net, vat, total_incl_vat, plus a human
	 * readable list of what was selected (for cart/order display).
	 */
	public static function calculate( $product_id, $selections ) {

		$qty      = isset( $selections['quantity'] ) ? (int) $selections['quantity'] : 0;
		$versions = isset( $selections['versions'] ) ? max( 1, (int) $selections['versions'] ) : 1;
		$dim_id   = isset( $selections['dimension_id'] ) ? (int) $selections['dimension_id'] : 0;

		$valid_qtys = wp_list_pluck( self::get_quantity_tiers(), 'qty' );
		if ( ! in_array( $qty, $valid_qtys, true ) ) {
			return new WP_Error( 'invalid_qty', 'Please choose a valid quantity.' );
		}

		$dims = null;
		foreach ( self::get_dimensions_for_product( $product_id ) as $d ) {
			if ( $d['id'] === $dim_id ) { $dims = $d; break; }
		}
		if ( ! $dims ) {
			return new WP_Error( 'invalid_dimension', 'Please choose a valid format.' );
		}

		// Global master switch: when off, every price comes back as zero
		// while selections are still validated and recorded. Useful for a
		// staging period where you want the form live but not charging yet.
		$pricing_master_enabled = get_option( 'pwc_master_pricing_enabled', '1' ) === '1';
		$global_multiplier      = (float) get_option( 'pwc_global_multiplier', 1 );

		$area_m2     = self::box_area_m2( $dims['length'], $dims['width'], $dims['height'] );
		$flat_fields = self::get_flat_fields( $product_id );

		$per_sqm_unit_total = 0.0;  // €/box, before qty
		$flat_order_total   = 0.0;  // one-off "additional options"
		$chosen_summary     = [];

		foreach ( $flat_fields as $key => $field ) {
			if ( ! isset( $selections[ $key ] ) || $selections[ $key ] === '' ) continue;

			// A field whose group has pricing calculation switched off
			// still records the customer's choice, it just contributes €0.
			$group_multiplier = $pricing_master_enabled && $field['_group_pricing_enabled'] ? (float) $field['_group_multiplier'] : 0.0;

			if ( $field['type'] === 'checkbox' ) {
				if ( $selections[ $key ] != '1' ) continue;
				$opt = $field['options'][0] ?? null;
				if ( ! $opt ) continue;
				$flat_order_total += (float) $opt['price'] * $group_multiplier;
				$chosen_summary[ $field['label'] ] = $opt['label'];
				continue;
			}

			// select field
			$opt_index = $selections[ $key ];
			$opt = $field['options'][ $opt_index ] ?? null;
			if ( ! $opt ) continue;

			$price_mode = $opt['pricing_mode'] ?? 'none';
			$price_val  = isset( $opt['price'] ) ? (float) $opt['price'] : 0.0;

			if ( $price_mode === 'per_sqm' ) {
				$per_sqm_unit_total += $price_val * $area_m2 * $group_multiplier;
			} elseif ( $price_mode === 'flat_order' ) {
				$flat_order_total += $price_val * $group_multiplier;
			}
			$chosen_summary[ $field['label'] ] = $opt['label'];
		}

		$tier_multiplier = self::find_tier_multiplier( $qty );
		$unit_price      = $per_sqm_unit_total; // per single box
		$base_price      = $unit_price * $qty * $tier_multiplier * $versions * $global_multiplier;

		$additional_options = $flat_order_total * $global_multiplier;
		$total_net           = $base_price + $additional_options;

		$vat_rate      = self::get_vat_rate( $selections['delivery_country'] ?? null );
		$vat_amount    = $total_net * $vat_rate;
		$total_incl    = $total_net + $vat_amount;

		return [
			'unit_price_net'      => round( $unit_price, 4 ),
			'base_price'          => round( $base_price, 2 ),
			'additional_options'  => round( $additional_options, 2 ),
			'total_net'           => round( $total_net, 2 ),
			'vat_rate'            => $vat_rate,
			'vat_amount'          => round( $vat_amount, 2 ),
			'total_incl_vat'      => round( $total_incl, 2 ),
			'area_m2'             => round( $area_m2, 4 ),
			'quantity'            => $qty,
			'versions'            => $versions,
			'dimension_label'     => $dims['label'],
			'selections_summary'  => $chosen_summary,
			'pricing_active'      => $pricing_master_enabled,
		];
	}

	/** Simple country -> VAT rate map; extend via the `pwc_vat_rate` filter or wp-admin settings */
	public static function get_vat_rate( $country_code = null ) {
		$default = (float) get_option( 'pwc_default_vat_rate', 0.20 );
		$rate    = $default;
		if ( $country_code ) {
			$map = get_option( 'pwc_country_vat_map', [] );
			if ( isset( $map[ $country_code ] ) ) $rate = (float) $map[ $country_code ];
		}
		return apply_filters( 'pwc_vat_rate', $rate, $country_code );
	}
}
