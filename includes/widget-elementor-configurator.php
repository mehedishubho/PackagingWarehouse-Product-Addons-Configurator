<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PWC_Elementor_Widget extends \Elementor\Widget_Base {

	public function get_name() { return 'pwc_configurator'; }
	public function get_title() { return 'PW Product Configurator'; }
	public function get_icon() { return 'eicon-form-horizontal'; }
	public function get_categories() { return [ 'general' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'content_section', [ 'label' => 'Configurator' ] );

		$this->add_control( 'product_id', [
			'label'       => 'Product ID (leave blank to auto-detect on single product page)',
			'type'        => \Elementor\Controls_Manager::NUMBER,
			'default'     => '',
		] );

		$this->add_control( 'show_gallery', [
			'label'        => 'Show product image column',
			'description'  => 'Renders a left-hand image column inside the widget (image | calculator | price). Turn off if your theme already shows the product gallery.',
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label_on'     => 'Yes',
			'label_off'    => 'No',
			'return_value' => '1',
			'default'      => '1',
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$id      = ! empty( $settings['product_id'] ) ? (int) $settings['product_id'] : 0;
		$gallery = empty( $settings['show_gallery'] ) ? '0' : '1';
		echo do_shortcode( '[pwc_configurator id="' . esc_attr( $id ) . '" gallery="' . esc_attr( $gallery ) . '"]' );
	}
}
