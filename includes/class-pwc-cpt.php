<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers the two data-entry post types used to manage the configurator
 * from wp-admin without touching code:
 *
 *  - pwc_field_group  : a reusable set of dropdown/checkbox fields
 *                       (e.g. "Folding Box Options") assigned to one or
 *                       more product categories.
 *  - pwc_dimension     : a single L x W x H preset (e.g. "40 x 40 x 100 mm")
 *                       assigned to one or more product categories, used
 *                       to populate the "Format in mm" dropdown and to
 *                       feed the surface-area calculation.
 */
class PWC_CPT {

	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_post_types' ] );
	}

	public static function register_post_types() {

		register_post_type( 'pwc_field_group', [
			'label'        => 'Configurator Field Groups',
			'labels'       => [
				'name'          => 'Field Groups',
				'singular_name' => 'Field Group',
				'add_new_item'  => 'Add New Field Group',
				'edit_item'     => 'Edit Field Group',
			],
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'pwc-configurator',
			'supports'     => [ 'title' ],
			'menu_icon'    => 'dashicons-forms',
		] );

		register_post_type( 'pwc_dimension', [
			'label'        => 'Dimension Presets',
			'labels'       => [
				'name'          => 'Dimension Presets',
				'singular_name' => 'Dimension Preset',
				'add_new_item'  => 'Add New Dimension Preset',
				'edit_item'     => 'Edit Dimension Preset',
			],
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'pwc-configurator',
			'supports'     => [ 'title' ],
		] );

		// Parent menu page (both CPTs + settings nest under this)
		add_action( 'admin_menu', function () {
			add_menu_page(
				'PW Configurator',
				'PW Configurator',
				'manage_woocommerce',
				'pwc-configurator',
				'__return_null',
				'dashicons-screenoptions',
				56
			);
			// WordPress auto-nests CPTs registered with show_in_menu = 'pwc-configurator'
			// Remove the placeholder submenu WP adds for the parent itself
			remove_submenu_page( 'pwc-configurator', 'pwc-configurator' );
		}, 20 );
	}
}
