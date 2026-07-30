<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin UI for pwc_field_group and pwc_dimension.
 *
 * Field Group meta box lets you:
 *  - assign the group to product categories AND/OR specific individual
 *    products (a group applies if EITHER matches; leave both empty to
 *    apply everywhere)
 *  - turn price calculation for the whole group on/off (fields still show,
 *    but contribute nothing to price while off — handy for rolling out a
 *    new option before prices are finalised)
 *  - set a price multiplier for the group (scale every option's price up
 *    or down without editing each one — handy for testing/promotions)
 *  - build an arbitrary list of fields (dropdown or checkbox), each with
 *    its own list of options and a pricing mode per option:
 *      per_sqm      -> option_price * box_area_m2 (rolled into base price, x qty)
 *      flat_order   -> option_price added once to "Additional options"
 *      none         -> informational only, no price impact
 *
 * Dimension Preset meta box lets you enter Length / Width / Height (mm)
 * and assign it the same way (category and/or individual products).
 */
class PWC_MetaBoxes {

	public static function init() {
		add_action( 'add_meta_boxes', [ __CLASS__, 'register' ] );
		add_action( 'save_post_pwc_field_group', [ __CLASS__, 'save_field_group' ] );
		add_action( 'save_post_pwc_dimension', [ __CLASS__, 'save_dimension' ] );
		add_action( 'add_meta_boxes_product', [ __CLASS__, 'register_product_override' ] );
		add_action( 'save_post_product', [ __CLASS__, 'save_product_override' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
	}

	public static function assets( $hook ) {
		global $post_type;
		if ( in_array( $post_type, [ 'pwc_field_group', 'pwc_dimension', 'product' ], true ) ) {
			wp_enqueue_script( 'pwc-admin', PWC_URL . 'assets/js/pwc-admin.js', [ 'jquery' ], PWC_VERSION, true );
			wp_enqueue_style( 'pwc-admin', PWC_URL . 'assets/css/pwc-admin.css', [], PWC_VERSION );

			// The per-product "Image per Material" picker needs the
			// WordPress media uploader (wp.media) on the product screen.
			if ( $post_type === 'product' ) {
				wp_enqueue_media();
			}

			// Reuse WooCommerce's own searchable product picker (same
			// widget WooCommerce uses for Upsells / Cross-sells) so we
			// don't have to ship + maintain a separate search UI.
			if ( in_array( $post_type, [ 'pwc_field_group', 'pwc_dimension' ], true ) ) {
				wp_enqueue_style( 'select2' );
				wp_enqueue_script( 'select2' );
				wp_enqueue_script( 'wc-enhanced-select' );
			}
		}
	}

	public static function register() {
		add_meta_box( 'pwc_field_group_pricing', 'Price Calculation', [ __CLASS__, 'render_pricing_control_box' ], 'pwc_field_group', 'side', 'high' );
		add_meta_box( 'pwc_field_group_assign', 'Assign to Categories / Products', [ __CLASS__, 'render_assignment_box' ], 'pwc_field_group', 'side' );
		add_meta_box( 'pwc_field_group_fields', 'Fields', [ __CLASS__, 'render_fields_box' ], 'pwc_field_group', 'normal', 'high' );

		add_meta_box( 'pwc_dimension_assign', 'Assign to Categories / Products', [ __CLASS__, 'render_assignment_box' ], 'pwc_dimension', 'side' );
		add_meta_box( 'pwc_dimension_values', 'Dimension (internal size, mm)', [ __CLASS__, 'render_dimension_box' ], 'pwc_dimension', 'normal', 'high' );
	}

	public static function register_product_override( $post ) {
		add_meta_box( 'pwc_product_override', 'PW Configurator — Overrides for this Product', [ __CLASS__, 'render_product_override' ], 'product', 'normal', 'default' );
		add_meta_box( 'pwc_product_images', 'PW Configurator — Image per Material', [ __CLASS__, 'render_product_images_box' ], 'product', 'normal', 'default' );
	}

	/** Field-group only: master on/off switch + price multiplier for the whole group */
	public static function render_pricing_control_box( $post ) {
		wp_nonce_field( 'pwc_save', 'pwc_nonce' );
		$enabled    = get_post_meta( $post->ID, '_pwc_pricing_enabled', true );
		$enabled    = $enabled === '' ? true : (bool) $enabled; // default ON for new groups
		$multiplier = get_post_meta( $post->ID, '_pwc_price_multiplier', true );
		$multiplier = $multiplier === '' ? 1 : $multiplier;
		?>
		<p>
			<label>
				<input type="checkbox" name="pwc_pricing_enabled" value="1" <?php checked( $enabled ); ?>>
				<strong>Include this group's fields in price calculation</strong>
			</label>
		</p>
		<p style="color:#666;font-size:12px;margin-top:-6px;">
			When off, these fields still appear on the product page and customers can still pick options —
			they just won't add or change the price. Useful while you're still filling in real prices.
		</p>
		<p>
			<label style="display:block;">Price multiplier
				<input type="number" step="0.01" name="pwc_price_multiplier" value="<?php echo esc_attr( $multiplier ); ?>" style="width:100%;">
			</label>
		</p>
		<p style="color:#666;font-size:12px;">
			Multiplies every price in this group. Leave at <code>1</code> for normal pricing.
			Use <code>0.9</code> for a temporary 10% discount on these options, <code>0</code> to
			zero them out without unchecking the box above, etc.
		</p>
		<?php
	}

	/** Shared: category checkboxes + individual product search, reused for both CPTs */
	public static function render_assignment_box( $post ) {
		wp_nonce_field( 'pwc_save', 'pwc_nonce' );

		$selected_cats = get_post_meta( $post->ID, '_pwc_categories', true );
		$selected_cats = $selected_cats ? array_map( 'intval', (array) $selected_cats ) : [];
		$terms = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false ] );

		echo '<p style="margin-top:0;font-weight:600;">By category</p>';
		echo '<p style="color:#666;font-size:12px;margin-top:-6px;">Applies to every product in the checked categories.</p>';
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			echo '<div style="max-height:160px;overflow:auto;border:1px solid #dcdcde;padding:6px;margin-bottom:14px;">';
			foreach ( $terms as $term ) {
				$checked = in_array( $term->term_id, $selected_cats, true ) ? 'checked' : '';
				echo '<label style="display:block;margin-bottom:4px;"><input type="checkbox" name="pwc_categories[]" value="' . esc_attr( $term->term_id ) . '" ' . $checked . '> ' . esc_html( $term->name ) . '</label>';
			}
			echo '</div>';
		} else {
			echo '<p>No product categories found yet.</p>';
		}

		$selected_products = get_post_meta( $post->ID, '_pwc_products', true );
		$selected_products = $selected_products ? array_map( 'intval', (array) $selected_products ) : [];

		echo '<p style="font-weight:600;">By individual product</p>';
		echo '<p style="color:#666;font-size:12px;margin-top:-6px;">Search and pick specific products, no matter their category.</p>';
		echo '<select class="wc-product-search" multiple="multiple" style="width:100%;" name="pwc_products[]" data-placeholder="Search for products…" data-action="woocommerce_json_search_products">';
		foreach ( $selected_products as $pid ) {
			$p = wc_get_product( $pid );
			if ( $p ) echo '<option value="' . esc_attr( $pid ) . '" selected="selected">' . esc_html( $p->get_name() ) . '</option>';
		}
		echo '</select>';

		echo '<p style="color:#666;font-size:12px;margin-top:10px;">Leave both empty to apply to <strong>every product</strong> in the shop. If both are filled in, either match is enough (category OR product).</p>';
	}

	public static function render_fields_box( $post ) {
		$fields_json = get_post_meta( $post->ID, '_pwc_fields', true );
		$fields      = $fields_json ? json_decode( $fields_json, true ) : [];
		?>
		<p style="color:#666;">Build each dropdown/checkbox in the configurator form. The <strong>Field key</strong> is used internally (letters, numbers, underscores) and must be unique within this group — e.g. <code>material</code>, <code>printing_outside</code>, <code>artwork_check</code>.</p>
		<div id="pwc-fields-repeater" data-initial='<?php echo esc_attr( wp_json_encode( $fields ) ); ?>'></div>
		<input type="hidden" name="pwc_fields_json" id="pwc_fields_json" value="">
		<?php
	}

	public static function render_dimension_box( $post ) {
		$l = get_post_meta( $post->ID, '_pwc_length_mm', true );
		$w = get_post_meta( $post->ID, '_pwc_width_mm', true );
		$h = get_post_meta( $post->ID, '_pwc_height_mm', true );
		?>
		<table class="form-table">
			<tr>
				<th>Length (mm)</th><td><input type="number" step="0.1" name="pwc_length_mm" value="<?php echo esc_attr( $l ); ?>"></td>
				<th>Width (mm)</th><td><input type="number" step="0.1" name="pwc_width_mm" value="<?php echo esc_attr( $w ); ?>"></td>
				<th>Height (mm)<br><small style="font-weight:normal;color:#666;">blank = flat 2D</small></th><td><input type="number" step="0.1" name="pwc_height_mm" value="<?php echo esc_attr( $h ); ?>"></td>
			</tr>
		</table>
		<p style="color:#666;">The Post Title is what customers see in the dropdown (e.g. "40 x 40 x 100 mm"). Fill all three for a <strong>3D box</strong>; leave <strong>Height blank</strong> for a flat <strong>2D product</strong> (label / card / sheet). The area drives the per-m² price.</p>
		<?php
	}

	public static function render_product_override( $post ) {
		wp_nonce_field( 'pwc_save', 'pwc_nonce' );
		$extra    = get_post_meta( $post->ID, '_pwc_extra_groups', true );
		$extra    = $extra ? (array) $extra : [];
		$excluded = get_post_meta( $post->ID, '_pwc_excluded_groups', true );
		$excluded = $excluded ? (array) $excluded : [];
		$groups   = get_posts( [ 'post_type' => 'pwc_field_group', 'numberposts' => -1 ] );

		echo '<p>Category/product assignment on each Field Group applies automatically. Use this box only for exceptions on this one product.</p>';

		echo '<p style="font-weight:600;">➕ Also include these groups on this product</p>';
		foreach ( $groups as $g ) {
			$checked = in_array( $g->ID, $extra, true ) ? 'checked' : '';
			echo '<label style="display:block;margin-bottom:4px;"><input type="checkbox" name="pwc_extra_groups[]" value="' . esc_attr( $g->ID ) . '" ' . $checked . '> ' . esc_html( $g->post_title ) . '</label>';
		}

		echo '<p style="font-weight:600;margin-top:16px;">➖ Exclude these groups from this product</p>';
		echo '<p style="color:#666;font-size:12px;margin-top:-6px;">Use this if a category-wide group would normally apply here but shouldn\'t for this specific product.</p>';
		foreach ( $groups as $g ) {
			$checked = in_array( $g->ID, $excluded, true ) ? 'checked' : '';
			echo '<label style="display:block;margin-bottom:4px;"><input type="checkbox" name="pwc_excluded_groups[]" value="' . esc_attr( $g->ID ) . '" ' . $checked . '> ' . esc_html( $g->post_title ) . '</label>';
		}
	}

	/**
	 * Per-product image picker: for each dropdown (material-style) field
	 * that applies to this product, choose the image shown when a customer
	 * selects that option. Stored keyed by "field_key::option_label" so the
	 * mapping survives option reordering in the Field Group.
	 *
	 * This is what lets two products share the same Material value but show
	 * different images — the image is looked up per product, not per shared
	 * option. Falls back to the product's featured image when left blank.
	 */
	public static function render_product_images_box( $post ) {
		wp_nonce_field( 'pwc_save', 'pwc_nonce' );

		$saved = get_post_meta( $post->ID, '_pwc_product_images', true );
		$saved = is_array( $saved ) ? $saved : [];

		$groups = PWC_Pricing::get_field_groups_for_product( $post->ID );

		// Only dropdown (select) fields carry swappable materials.
		$fields = [];
		foreach ( $groups as $group ) {
			foreach ( $group['fields'] as $field ) {
				if ( ( $field['type'] ?? 'select' ) === 'select' && ! empty( $field['options'] ) ) {
					$fields[] = $field;
				}
			}
		}

		echo '<p style="color:#666;">Choose the image shown when a customer selects each material/option. Leave blank to fall back to the product\'s featured image.</p>';

		if ( empty( $fields ) ) {
			echo '<p style="color:#666;"><em>No dropdown fields apply to this product yet. Assign a Field Group to this product\'s category (or to this product) first, then reload this page.</em></p>';
			return;
		}

		foreach ( $fields as $field ) {
			$fkey = $field['key'];
			echo '<h4 style="margin:16px 0 6px;">' . esc_html( $field['label'] ) . ' <code style="font-weight:normal;color:#999;">' . esc_html( $fkey ) . '</code></h4>';
			echo '<div class="pwc-prod-img-grid">';
			foreach ( $field['options'] as $opt ) {
				$label  = isset( $opt['label'] ) ? $opt['label'] : '';
				$mkey   = $fkey . '::' . $label;
				$att_id = isset( $saved[ $mkey ] ) ? absint( $saved[ $mkey ] ) : 0;
				$thumb  = $att_id ? wp_get_attachment_image_src( $att_id, 'thumbnail' ) : false;

				echo '<div class="pwc-prod-img-row" data-key="' . esc_attr( $mkey ) . '">';
				echo '<div class="pwc-prod-img-thumb">';
				echo '<img src="' . ( $thumb ? esc_url( $thumb[0] ) : '' ) . '" alt="" style="' . ( $thumb ? 'display:block;' : 'display:none;' ) . '">';
				echo '<span class="pwc-no-img"' . ( $thumb ? ' style="display:none;"' : '' ) . '>no image</span>';
				echo '</div>';
				echo '<div class="pwc-prod-img-meta">';
				echo '<strong>' . esc_html( $label ? $label : '(unlabelled option)' ) . '</strong><br>';
				echo '<input type="hidden" class="pwc-att-id" value="' . esc_attr( $att_id ) . '">';
				echo '<button type="button" class="button pwc-choose-img">Choose image</button> ';
				echo '<button type="button" class="button-link pwc-remove-img"' . ( $att_id ? '' : ' style="display:none;"' ) . '>Remove</button>';
				echo '</div>';
				echo '</div>';
			}
			echo '</div>';
		}

		echo '<input type="hidden" name="pwc_product_images_json" id="pwc_product_images_json" value="">';
	}

	private static function verify( $post_id ) {
		if ( ! isset( $_POST['pwc_nonce'] ) || ! wp_verify_nonce( $_POST['pwc_nonce'], 'pwc_save' ) ) return false;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return false;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return false;
		return true;
	}

	private static function save_assignment( $post_id ) {
		$cats = isset( $_POST['pwc_categories'] ) ? array_map( 'intval', $_POST['pwc_categories'] ) : [];
		update_post_meta( $post_id, '_pwc_categories', $cats );

		$products = isset( $_POST['pwc_products'] ) ? array_map( 'intval', $_POST['pwc_products'] ) : [];
		update_post_meta( $post_id, '_pwc_products', $products );
	}

	public static function save_field_group( $post_id ) {
		if ( ! self::verify( $post_id ) ) return;
		self::save_assignment( $post_id );

		update_post_meta( $post_id, '_pwc_pricing_enabled', isset( $_POST['pwc_pricing_enabled'] ) ? '1' : '0' );
		$multiplier = isset( $_POST['pwc_price_multiplier'] ) && $_POST['pwc_price_multiplier'] !== ''
			? floatval( $_POST['pwc_price_multiplier'] ) : 1;
		update_post_meta( $post_id, '_pwc_price_multiplier', $multiplier );

		if ( isset( $_POST['pwc_fields_json'] ) ) {
			$raw     = wp_unslash( $_POST['pwc_fields_json'] );
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				update_post_meta( $post_id, '_pwc_fields', wp_json_encode( $decoded ) );
			}
		}
	}

	public static function save_dimension( $post_id ) {
		if ( ! self::verify( $post_id ) ) return;
		self::save_assignment( $post_id );
		foreach ( [ 'length_mm', 'width_mm', 'height_mm' ] as $key ) {
			if ( isset( $_POST[ 'pwc_' . $key ] ) ) {
				update_post_meta( $post_id, '_pwc_' . $key, floatval( $_POST[ 'pwc_' . $key ] ) );
			}
		}
	}

	public static function save_product_override( $post_id ) {
		if ( ! self::verify( $post_id ) ) return;
		$extra = isset( $_POST['pwc_extra_groups'] ) ? array_map( 'intval', $_POST['pwc_extra_groups'] ) : [];
		update_post_meta( $post_id, '_pwc_extra_groups', $extra );

		$excluded = isset( $_POST['pwc_excluded_groups'] ) ? array_map( 'intval', $_POST['pwc_excluded_groups'] ) : [];
		update_post_meta( $post_id, '_pwc_excluded_groups', $excluded );

		// Per-product material -> image map: { "field_key::label": attachment_id }.
		if ( isset( $_POST['pwc_product_images_json'] ) ) {
			$raw     = wp_unslash( $_POST['pwc_product_images_json'] );
			$decoded = json_decode( $raw, true );
			$clean   = [];
			if ( is_array( $decoded ) ) {
				foreach ( $decoded as $k => $v ) {
					$k = sanitize_text_field( $k );
					$v = absint( $v );
					if ( $k && $v ) {
						$clean[ $k ] = $v;
					}
				}
			}
			update_post_meta( $post_id, '_pwc_product_images', $clean );
		}
	}
}
