<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PWC_Frontend {

	public static function init() {
		add_shortcode( 'pwc_configurator', [ __CLASS__, 'render_shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'assets' ] );
	}

	public static function assets() {
		wp_register_script( 'pwc-frontend', PWC_URL . 'assets/js/pwc-frontend.js', [], PWC_VERSION, true );
		wp_register_style( 'pwc-frontend', PWC_URL . 'assets/css/pwc-frontend.css', [], PWC_VERSION );
		wp_localize_script( 'pwc-frontend', 'PWC', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'pwc_nonce' ),
		] );
	}

	/**
	 * Usage inside Elementor: an HTML/Shortcode widget with
	 * [pwc_configurator id="123"]  — or leave id blank on an actual
	 * WooCommerce single product page to auto-detect the current product.
	 */
	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts( [ 'id' => 0 ], $atts );
		$product_id = (int) $atts['id'] ?: get_the_ID();
		$product    = wc_get_product( $product_id );
		if ( ! $product ) return '<p>Configurator: no product found.</p>';

		wp_enqueue_script( 'pwc-frontend' );
		wp_enqueue_style( 'pwc-frontend' );

		$dimensions = PWC_Pricing::get_dimensions_for_product( $product_id );
		$groups     = PWC_Pricing::get_field_groups_for_product( $product_id );
		$qty_tiers  = PWC_Pricing::get_quantity_tiers();
		$versions   = PWC_Pricing::get_versions_options();

		ob_start();
		?>
		<form id="pwc-form-<?php echo esc_attr( $product_id ); ?>" class="pwc-configurator" data-product-id="<?php echo esc_attr( $product_id ); ?>">

			<div class="pwc-col pwc-col-form">

				<div class="pwc-field" data-pwc-field="quantity">
					<label>Quantity</label>
					<select name="quantity" required>
						<option value="">Select quantity</option>
						<?php foreach ( $qty_tiers as $tier ) : ?>
							<option value="<?php echo esc_attr( $tier['qty'] ); ?>"><?php echo esc_html( number_format_i18n( $tier['qty'] ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="pwc-field" data-pwc-field="versions">
					<label>Versions</label>
					<select name="versions">
						<?php foreach ( $versions as $v ) : ?>
							<option value="<?php echo esc_attr( $v ); ?>"><?php echo esc_html( $v ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="pwc-field" data-pwc-field="dimension_id">
					<label>Format in mm</label>
					<select name="dimension_id" required>
						<option value="">Select format</option>
						<?php foreach ( $dimensions as $d ) : ?>
							<option value="<?php echo esc_attr( $d['id'] ); ?>"><?php echo esc_html( $d['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="pwc-hint">Dimensions refer to the internal size of the packaging!</p>
				</div>

				<?php foreach ( $groups as $group ) : ?>
					<?php foreach ( $group['fields'] as $field ) : ?>
						<?php self::render_field( $field ); ?>
					<?php endforeach; ?>
				<?php endforeach; ?>

			</div>

			<div class="pwc-col pwc-col-offer" data-pwc-field="offer_box">
				<div class="pwc-offer-box">
					<?php if ( get_option( 'pwc_master_pricing_enabled', '1' ) !== '1' ) : ?>
						<p class="pwc-pricing-off-notice">Pricing preview mode — prices shown are for testing only.</p>
					<?php endif; ?>
					<h3>Offer</h3>
					<div class="pwc-offer-row" data-pwc-field="offer_base_price"><span>Base price</span><strong data-pwc="base_price">–</strong></div>
					<div class="pwc-offer-row" data-pwc-field="offer_additional_options"><span>Additional options</span><strong data-pwc="additional_options">–</strong></div>
					<div class="pwc-offer-row pwc-offer-total" data-pwc-field="offer_total_net"><span>Total net</span><strong data-pwc="total_net">–</strong></div>
					<div class="pwc-offer-row" data-pwc-field="offer_vat"><span data-pwc-vat-label>VAT</span><strong data-pwc="vat_amount">–</strong></div>
					<div class="pwc-offer-row pwc-offer-grand" data-pwc-field="offer_total_incl_vat"><span>Total inc. VAT</span><strong data-pwc="total_incl_vat">–</strong></div>
					<p class="pwc-transport">Transport included!</p>

					<button type="submit" class="pwc-add-to-cart button alt">Add to cart</button>
					<p class="pwc-status" role="status"></p>
				</div>
			</div>

		</form>
		<?php
		return ob_get_clean();
	}

	private static function render_field( $field ) {
		$key   = esc_attr( $field['key'] );
		$label = esc_html( $field['label'] );

		if ( $field['type'] === 'checkbox' ) {
			$opt = $field['options'][0] ?? [ 'label' => $field['label'], 'price' => 0 ];
			echo '<div class="pwc-field pwc-field-checkbox" data-pwc-field="' . $key . '">';
			echo '<label><input type="checkbox" name="' . $key . '" value="1"> ' . esc_html( $opt['label'] );
			if ( ! empty( $opt['price'] ) ) {
				echo ' <span class="pwc-surcharge">+ ' . esc_html( wc_price( $opt['price'] ) ) . '</span>';
			}
			echo '</label></div>';
			return;
		}

		echo '<div class="pwc-field" data-pwc-field="' . $key . '"><label>' . $label . '</label><select name="' . $key . '">';
		foreach ( $field['options'] as $i => $opt ) {
			echo '<option value="' . esc_attr( $i ) . '">' . esc_html( $opt['label'] ) . '</option>';
		}
		echo '</select></div>';
	}
}
