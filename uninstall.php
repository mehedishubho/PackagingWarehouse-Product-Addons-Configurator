<?php
/**
 * Fires only when the plugin is deleted from the Plugins screen (never on
 * simple deactivation). By default this does nothing — it only removes
 * data if the site owner explicitly ticked "Delete all data on uninstall"
 * on the Settings screen, so nobody loses their configuration by accident
 * from a routine deactivate/reactivate or update.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( get_option( 'pwc_delete_data_on_uninstall' ) !== '1' ) {
	return; // opt-in only — leave everything in place
}

global $wpdb;

// 1. Delete all Field Group and Dimension Preset posts (and their postmeta,
//    via wp_delete_post's own cleanup).
foreach ( [ 'pwc_field_group', 'pwc_dimension' ] as $post_type ) {
	$ids = get_posts( [
		'post_type'      => $post_type,
		'post_status'    => 'any',
		'numberposts'    => -1,
		'fields'         => 'ids',
	] );
	foreach ( $ids as $id ) {
		wp_delete_post( $id, true ); // true = force, skip trash
	}
}

// 2. Delete this plugin's own options.
$options = [
	'pwc_quantity_tiers',
	'pwc_default_vat_rate',
	'pwc_master_pricing_enabled',
	'pwc_global_multiplier',
	'pwc_country_vat_map',
	'pwc_delete_data_on_uninstall',
];
foreach ( $options as $option ) {
	delete_option( $option );
}

// 3. Delete the plugin's per-product override meta from every WooCommerce
//    product (extra groups, excluded groups). This does NOT touch
//    products, orders, or any WooCommerce data itself — only the meta
//    keys this plugin created.
$meta_keys = [ '_pwc_extra_groups', '_pwc_excluded_groups', '_pwc_hide_default_groups' ];
foreach ( $meta_keys as $meta_key ) {
	$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => $meta_key ] ); // phpcs:ignore WordPress.DB.SlowDBQuery
}

// Note: prices and selected options already stored on PAST orders are
// intentionally left untouched — those belong to WooCommerce's order
// records, not to this plugin, and removing them would corrupt order
// history / accounting.
