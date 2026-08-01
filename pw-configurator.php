<?php

/**
 * Plugin Name: PW Product Configurator
 * Plugin URI:        https://github.com/mehedishubho/PackagingWarehouse-Product-Addons-Configurator
 * Description:       Turn any WooCommerce product into a fully configurable, live-priced product — dimensions, materials, printing, and add-ons, all managed from wp-admin.
 * Version:           0.0.05
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Elementor tested up to: 4.0
 * Elementor Pro tested up to: 4.0
 * Author:            Devsroom / WPMHS
 * Author URI:        https://wpmhs.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pw-configurator
 * Requires Plugins: woocommerce
 */

if (! defined('ABSPATH')) exit; // no direct access

define('PWC_VERSION', '0.0.05');
define('PWC_PATH', plugin_dir_path(__FILE__));
define('PWC_URL', plugin_dir_url(__FILE__));

// Declare compatibility with WooCommerce High-Performance Order Storage (HPOS).
// This plugin only ever touches order data through WC_Order_Item::add_meta_data(),
// the official CRUD method, so it's safe under both legacy and HPOS storage.
add_action('before_woocommerce_init', function () {
	if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
	}
});

// Bail early with an admin notice if WooCommerce isn't active
add_action('plugins_loaded', function () {
	if (! class_exists('WooCommerce')) {
		add_action('admin_notices', function () {
			echo '<div class="notice notice-error"><p><strong>PW Product Configurator</strong> requires WooCommerce to be active.</p></div>';
		});
		return;
	}
	PWC_Bootstrap::init();
});

/**
 * Core bootstrap — loads all plugin classes and wires up hooks.
 */
class PWC_Bootstrap
{

	public static function init()
	{

		require_once PWC_PATH . 'includes/class-pwc-cpt.php';
		require_once PWC_PATH . 'includes/class-pwc-metaboxes.php';
		require_once PWC_PATH . 'includes/class-pwc-settings.php';
		require_once PWC_PATH . 'includes/class-pwc-pricing.php';
		require_once PWC_PATH . 'includes/class-pwc-ajax.php';
		require_once PWC_PATH . 'includes/class-pwc-frontend.php';
		require_once PWC_PATH . 'includes/class-pwc-cart.php';
		require_once PWC_PATH . 'includes/class-pwc-elementor.php';

		PWC_CPT::init();
		PWC_MetaBoxes::init();
		PWC_Settings::init();
		PWC_Ajax::init();
		PWC_Frontend::init();
		PWC_Cart::init();
		PWC_Elementor::init();
	}
}

// Activation: register CPTs then flush rewrite rules
register_activation_hook(__FILE__, function () {
	require_once PWC_PATH . 'includes/class-pwc-cpt.php';
	PWC_CPT::register_post_types();
	flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
	flush_rewrite_rules();
});
