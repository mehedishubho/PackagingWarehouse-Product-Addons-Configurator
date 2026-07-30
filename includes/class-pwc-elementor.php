<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PWC_Elementor {

	public static function init() {
		add_action( 'elementor/widgets/register', [ __CLASS__, 'register_widget' ] );
	}

	public static function register_widget( $widgets_manager ) {
		if ( ! did_action( 'elementor/loaded' ) ) return;
		require_once PWC_PATH . 'includes/widget-elementor-configurator.php';
		$widgets_manager->register( new \PWC_Elementor_Widget() );
	}
}
