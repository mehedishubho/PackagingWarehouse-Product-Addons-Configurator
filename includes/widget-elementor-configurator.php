<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PWC_Elementor_Widget extends \Elementor\Widget_Base {

	public function get_name() { return 'pwc_configurator'; }
	public function get_title() { return 'PW Product Configurator'; }
	public function get_icon() { return 'eicon-form-horizontal'; }
	public function get_categories() { return [ 'general' ]; }

	protected function register_controls() {

		// ----------------------- Content -----------------------
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

		// ----------------------- Style: Colors -----------------------
		$this->start_controls_section( 'section_style_colors', [
			'label' => 'Colors',
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'label_color', [
			'label'     => 'Label color',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .pwc-field label' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'value_color', [
			'label'     => 'Option / value color',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .pwc-field select, {{WRAPPER}} .pwc-field input[type="number"]' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'input_bg', [
			'label'     => 'Input background',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .pwc-field select, {{WRAPPER}} .pwc-field input[type="number"]' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_control( 'input_border', [
			'label'     => 'Input border',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .pwc-field select, {{WRAPPER}} .pwc-field input[type="number"]' => 'border-color: {{VALUE}};' ],
		] );
		$this->add_control( 'accent_color', [
			'label'     => 'Accent (totals / surcharge)',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .pwc-offer-total' => 'color: {{VALUE}};',
				'{{WRAPPER}} .pwc-surcharge'   => 'color: {{VALUE}};',
				'{{WRAPPER}} .pwc-transport'   => 'color: {{VALUE}};',
			],
		] );
		$this->add_control( 'offer_bg', [
			'label'     => 'Offer box background',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .pwc-offer-box' => 'background-color: {{VALUE}};' ],
		] );

		$this->end_controls_section();

		// --------------------- Style: Typography ---------------------
		$this->start_controls_section( 'section_style_typo', [
			'label' => 'Typography',
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'label_typo',
				'label'    => 'Labels',
				'selector' => '{{WRAPPER}} .pwc-field label',
			]
		);
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'value_typo',
				'label'    => 'Options & values',
				'selector' => '{{WRAPPER}} .pwc-field select, {{WRAPPER}} .pwc-offer-row, {{WRAPPER}} .pwc-offer-box h3',
			]
		);

		$this->end_controls_section();

		// ---------------------- Style: Spacing ----------------------
		$this->start_controls_section( 'section_style_spacing', [
			'label' => 'Spacing',
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'column_gap', [
			'label'      => 'Column gap',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
			'selectors'  => [ '{{WRAPPER}} .pwc-configurator' => 'gap: {{SIZE}}{{UNIT}};' ],
		] );
		$this->add_control( 'field_gap', [
			'label'      => 'Field row spacing',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 30 ] ],
			'selectors'  => [ '{{WRAPPER}} .pwc-field' => 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};' ],
		] );
		$this->add_control( 'input_pad', [
			'label'      => 'Input padding (vertical)',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 20 ] ],
			'selectors'  => [ '{{WRAPPER}} .pwc-field select, {{WRAPPER}} .pwc-field input[type="number"]' => 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};' ],
		] );
		$this->add_control( 'offer_pad', [
			'label'      => 'Offer box padding',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
			'selectors'  => [ '{{WRAPPER}} .pwc-offer-box' => 'padding: {{SIZE}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ---------------------- Style: Inputs -----------------------
		$this->start_controls_section( 'section_style_inputs', [
			'label' => 'Inputs',
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'input_radius', [
			'label'      => 'Corner radius',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 24 ] ],
			'selectors'  => [ '{{WRAPPER}} .pwc-field select, {{WRAPPER}} .pwc-field input[type="number"]' => 'border-radius: {{SIZE}}{{UNIT}};' ],
		] );
		$this->add_control( 'input_border_width', [
			'label'      => 'Border width',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 6 ] ],
			'selectors'  => [ '{{WRAPPER}} .pwc-field select, {{WRAPPER}} .pwc-field input[type="number"]' => 'border-width: {{SIZE}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ------------- Style: Add to cart button -------------
		$this->start_controls_section( 'section_style_button', [
			'label' => 'Add to cart button',
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'btn_bg', [
			'label'     => 'Background',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .pwc-add-to-cart' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_control( 'btn_color', [
			'label'     => 'Text color',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .pwc-add-to-cart' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'btn_radius', [
			'label'      => 'Corner radius',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 30 ] ],
			'selectors'  => [ '{{WRAPPER}} .pwc-add-to-cart' => 'border-radius: {{SIZE}}{{UNIT}};' ],
		] );
		$this->add_control( 'btn_hover', [
			'label'     => 'Hover background',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .pwc-add-to-cart:hover' => 'background-color: {{VALUE}};' ],
		] );

		$this->end_controls_section();

		// --------------- Style: Image column (gallery) ---------------
		$this->start_controls_section( 'section_style_gallery', [
			'label' => 'Image column',
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'gallery_bg', [
			'label'     => 'Gallery background',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .pwc-gallery' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_control( 'gallery_pad', [
			'label'      => 'Gallery padding',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
			'selectors'  => [ '{{WRAPPER}} .pwc-gallery' => 'padding: {{SIZE}}{{UNIT}};' ],
		] );
		$this->add_control( 'gallery_radius', [
			'label'      => 'Image corner radius',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 30 ] ],
			'selectors'  => [ '{{WRAPPER}} .pwc-gallery-img' => 'border-radius: {{SIZE}}{{UNIT}};' ],
		] );
		$this->add_control( 'gallery_maxw', [
			'label'      => 'Column max width',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ '%' ],
			'range'      => [ '%' => [ 'min' => 10, 'max' => 100 ] ],
			'selectors'  => [ '{{WRAPPER}} .pwc-col-gallery' => 'max-width: {{SIZE}}%;' ],
		] );
		$this->add_control( 'gallery_img_height', [
			'label'      => 'Image max height',
			'description' => 'Crop large images to this height (uses object-fit: cover). Leave empty for natural height.',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 120, 'max' => 900 ] ],
			'selectors'  => [ '{{WRAPPER}} .pwc-gallery-img' => 'max-height: {{SIZE}}{{UNIT}}; object-fit: cover; width: 100%;' ],
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
