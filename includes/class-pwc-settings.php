<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PWC_Settings {

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ], 30 );
		add_action( 'admin_post_pwc_save_settings', [ __CLASS__, 'save' ] );
	}

	public static function menu() {
		add_submenu_page( 'pwc-configurator', 'Settings', 'Settings', 'manage_woocommerce', 'pwc-settings', [ __CLASS__, 'render' ] );
	}

	public static function render() {
		$tiers              = PWC_Pricing::get_quantity_tiers();
		$vat_rate           = get_option( 'pwc_default_vat_rate', 0.20 );
		$master_enabled     = get_option( 'pwc_master_pricing_enabled', '1' ) === '1';
		$global_multiplier  = get_option( 'pwc_global_multiplier', 1 );
		?>
		<div class="wrap">
			<h1>PW Configurator Settings</h1>
			<?php if ( isset( $_GET['saved'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="pwc_save_settings">
				<?php wp_nonce_field( 'pwc_settings_save', 'pwc_settings_nonce' ); ?>

				<h2>Master Pricing Switch</h2>
				<p style="color:#666;">
					Turn ALL price calculation off site-wide, for every product and every field group,
					in one click — useful while you're still setting up categories and prices, or if you
					just want the configurator collecting selections without charging anyone yet.
				</p>
				<p>
					<label>
						<input type="checkbox" name="master_pricing_enabled" value="1" <?php checked( $master_enabled ); ?>>
						<strong>Price calculation is active site-wide</strong>
					</label>
				</p>
				<p>
					<label>Global price multiplier
						<input type="number" step="0.01" name="global_multiplier" value="<?php echo esc_attr( $global_multiplier ); ?>" style="width:100px;">
					</label>
					<br><span style="color:#666;font-size:12px;">Applied on top of every calculated price, everywhere. Leave at <code>1</code> normally; use e.g. <code>1.1</code> for a temporary site-wide 10% surcharge.</span>
				</p>

				<h2 style="margin-top:28px;">Quantity Tiers</h2>
				<p style="color:#666;">Only these exact quantities are selectable in the configurator's Quantity dropdown. The multiplier is your digressive-discount curve: it multiplies unit price × quantity, so smaller numbers = cheaper per box at that tier (0.90 = effectively 10% off list price per box at that tier).</p>
				<table class="widefat" style="max-width:500px;">
					<thead><tr><th>Quantity</th><th>Multiplier</th></tr></thead>
					<tbody>
					<?php foreach ( $tiers as $i => $tier ) : ?>
						<tr>
							<td><input type="number" name="qty[]" value="<?php echo esc_attr( $tier['qty'] ); ?>" style="width:100%"></td>
							<td><input type="number" step="0.01" name="mult[]" value="<?php echo esc_attr( $tier['multiplier'] ); ?>" style="width:100%"></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<p><button type="button" class="button" id="pwc-add-tier">+ Add another tier row</button></p>
				<script>
				document.getElementById('pwc-add-tier').addEventListener('click', function(){
					var tbody = this.closest('div').querySelector ? null : null; // no-op safeguard
					var table = document.querySelector('table.widefat tbody');
					var tr = document.createElement('tr');
					tr.innerHTML = '<td><input type="number" name="qty[]" style="width:100%"></td><td><input type="number" step="0.01" name="mult[]" value="1" style="width:100%"></td>';
					table.appendChild(tr);
				});
				</script>

				<h2 style="margin-top:24px;">VAT</h2>
				<p><label>Default VAT rate (decimal, e.g. 0.20 for 20%)
					<input type="number" step="0.01" name="default_vat_rate" value="<?php echo esc_attr( $vat_rate ); ?>">
				</label></p>

				<h2 style="margin-top:28px;">Uninstall</h2>
				<p>
					<label>
						<input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked( get_option( 'pwc_delete_data_on_uninstall', '0' ) === '1' ); ?>>
						<strong>Delete all Field Groups, Dimension Presets, and settings when this plugin is uninstalled</strong>
					</label>
					<br><span style="color:#666;font-size:12px;">Off by default, so a normal Delete-Plugin click never loses your setup by accident. Only enable this if you're intentionally decommissioning the configurator and want a clean database afterwards. This never deletes WooCommerce products, orders, or the prices already stored on placed orders — only this plugin's own configuration.</span>
				</p>

				<?php submit_button( 'Save Settings' ); ?>
			</form>
		</div>
		<?php
	}

	public static function save() {
		if ( ! isset( $_POST['pwc_settings_nonce'] ) || ! wp_verify_nonce( $_POST['pwc_settings_nonce'], 'pwc_settings_save' ) ) wp_die( 'Invalid request' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( 'Not allowed' );

		$qtys  = array_map( 'intval', $_POST['qty'] ?? [] );
		$mults = array_map( 'floatval', $_POST['mult'] ?? [] );
		$tiers = [];
		foreach ( $qtys as $i => $q ) {
			if ( $q > 0 ) $tiers[] = [ 'qty' => $q, 'multiplier' => $mults[ $i ] ?? 1.0 ];
		}
		update_option( 'pwc_quantity_tiers', $tiers );
		update_option( 'pwc_default_vat_rate', floatval( $_POST['default_vat_rate'] ?? 0.20 ) );
		update_option( 'pwc_master_pricing_enabled', isset( $_POST['master_pricing_enabled'] ) ? '1' : '0' );
		update_option( 'pwc_global_multiplier', floatval( $_POST['global_multiplier'] ?? 1 ) );
		update_option( 'pwc_delete_data_on_uninstall', isset( $_POST['delete_data_on_uninstall'] ) ? '1' : '0' );

		wp_safe_redirect( add_query_arg( [ 'page' => 'pwc-settings', 'saved' => 1 ], admin_url( 'admin.php' ) ) );
		exit;
	}
}
