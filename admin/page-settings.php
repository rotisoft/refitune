<?php
/**
 * Beállítások oldal tartalma.
 *
 * @package WP_Refiner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wprefi_settings = get_option( 'wprefi_settings', array() );
$features     = wprefi_get_features();
$all_roles    = wp_roles()->get_names();

// Category definitions.
$categories = array(
	'performance' => __( 'Performance', 'refinerpress' ),
	'security'    => __( 'Security', 'refinerpress' ),
	'visual'      => __( 'Visual', 'refinerpress' ),
	'email'       => __( 'Email', 'refinerpress' ),
	'misc'        => __( 'Miscellaneous', 'refinerpress' ),
);

// Features csoportosítása kategóriák szerint.
$features_by_category = array();
foreach ( $features as $key => $feature ) {
	$cat = isset( $feature['category'] ) ? $feature['category'] : 'misc';
	if ( ! isset( $features_by_category[ $cat ] ) ) {
		$features_by_category[ $cat ] = array();
	}
	$features_by_category[ $cat ][ $key ] = $feature;
}
?>

<div class="wprefi-settings-nav">
	<?php foreach ( $categories as $cat_key => $cat_label ) : ?>
		<?php if ( isset( $features_by_category[ $cat_key ] ) ) : ?>
			<a href="#wprefi-category-<?php echo esc_attr( $cat_key ); ?>" class="wprefi-nav-button">
				<?php echo esc_html( $cat_label ); ?>
			</a>
		<?php endif; ?>
	<?php endforeach; ?>
</div>

<form method="post" action="options.php">
	<?php settings_fields( 'wprefi_settings_group' ); ?>
	<?php settings_errors( 'wprefi_settings' ); ?>

	<?php foreach ( $categories as $cat_key => $cat_label ) : ?>
		<?php if ( ! isset( $features_by_category[ $cat_key ] ) ) {
			continue;
		} ?>

		<h2 id="wprefi-category-<?php echo esc_attr( $cat_key ); ?>" class="wprefi-category-title">
			<?php echo esc_html( $cat_label ); ?>
		</h2>

		<table class="form-table" role="presentation">
			<?php foreach ( $features_by_category[ $cat_key ] as $key => $feature ) : ?>
			<?php
			$type     = isset( $feature['type'] ) ? $feature['type'] : '';
			$help_id  = str_replace( '_', '-', $key );
			$help_url = admin_url( 'tools.php?page=wprefi-help#help-' . $help_id );
			?>
			<tr>
				<th scope="row">
					<?php echo esc_html( $feature['label'] ); ?>
					<a href="<?php echo esc_url( $help_url ); ?>" class="wprefi-help-icon" target="_blank" title="<?php esc_attr_e( 'Open Help', 'refinerpress' ); ?>">
						<span class="dashicons dashicons-info"></span>
					</a>
				</th>
				<td>

				<?php if ( 'email_smtp' === $type ) : ?>
					<?php
					$email_mode = isset( $wprefi_settings['email_mode'] ) ? $wprefi_settings['email_mode'] : 'default';
					?>

			<div class="wprefi-email-smtp-wrapper">

				<div class="wprefi-email-mode-selector">
					<label>
						<input
							type="radio"
							name="wprefi_settings[email_mode]"
							value="default"
							<?php checked( $email_mode, 'default' ); ?>
						/>
						<strong><?php esc_html_e( 'WordPress default email sending (or other SMTP plugin)', 'refinerpress' ); ?></strong>
					</label>
					<br>
					<label>
						<input
							type="radio"
							name="wprefi_settings[email_mode]"
							value="disable_all"
							<?php checked( $email_mode, 'disable_all' ); ?>
						/>
						<strong><?php esc_html_e( 'Completely disable email sending', 'refinerpress' ); ?></strong>
					</label>
					<br>
					<label class="wprefi-collapsible-trigger">
						<input
							type="radio"
							id="wprefi_email_mode_smtp"
							class="wprefi-collapsible-checkbox"
							name="wprefi_settings[email_mode]"
							value="smtp"
							<?php checked( $email_mode, 'smtp' ); ?>
						/>
						<strong><?php esc_html_e( 'SMTP email sending', 'refinerpress' ); ?></strong>
					</label>
					<div class="wprefi-collapsible-content">
						<div class="wprefi-email-smtp-config">
							<h4 class="wprefi-smtp-title"><?php esc_html_e( 'SMTP Settings', 'refinerpress' ); ?></h4>
							<p class="description"><?php esc_html_e( 'The following settings apply when "SMTP email sending" option is selected.', 'refinerpress' ); ?></p>

							<table class="form-table wprefi-smtp-fields">
									<tr>
										<th scope="row"><label for="wprefi_email_smtp_host"><?php esc_html_e( 'SMTP Host', 'refinerpress' ); ?></label></th>
										<td>
											<input
												type="text"
												id="wprefi_email_smtp_host"
												name="wprefi_settings[email_smtp_host]"
												value="<?php echo esc_attr( $wprefi_settings['email_smtp_host'] ?? '' ); ?>"
												class="regular-text"
												placeholder="<?php esc_attr_e( 'e.g. smtp.domain.com', 'refinerpress' ); ?>"
											/>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="wprefi_email_smtp_port"><?php esc_html_e( 'SMTP Port', 'refinerpress' ); ?></label></th>
										<td>
											<input
												type="number"
												id="wprefi_email_smtp_port"
												name="wprefi_settings[email_smtp_port]"
												value="<?php echo esc_attr( $wprefi_settings['email_smtp_port'] ?? 587 ); ?>"
												class="small-text"
												min="1"
												max="65535"
											/>
											<p class="description"><?php esc_html_e( 'Usually 587 (TLS) or 465 (SSL).', 'refinerpress' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="wprefi_email_smtp_encryption"><?php esc_html_e( 'Encryption', 'refinerpress' ); ?></label></th>
										<td>
											<select id="wprefi_email_smtp_encryption" name="wprefi_settings[email_smtp_encryption]">
												<option value="none" <?php selected( ( $wprefi_settings['email_smtp_encryption'] ?? 'tls' ), 'none' ); ?>><?php esc_html_e( 'None', 'refinerpress' ); ?></option>
												<option value="ssl" <?php selected( ( $wprefi_settings['email_smtp_encryption'] ?? 'tls' ), 'ssl' ); ?>>SSL</option>
												<option value="tls" <?php selected( ( $wprefi_settings['email_smtp_encryption'] ?? 'tls' ), 'tls' ); ?>>TLS</option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="wprefi_email_smtp_username"><?php esc_html_e( 'SMTP Username', 'refinerpress' ); ?></label></th>
										<td>
											<input
												type="text"
												id="wprefi_email_smtp_username"
												name="wprefi_settings[email_smtp_username]"
												value="<?php echo esc_attr( $wprefi_settings['email_smtp_username'] ?? '' ); ?>"
												class="regular-text"
												autocomplete="off"
											/>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="wprefi_email_smtp_password"><?php esc_html_e( 'SMTP Password', 'refinerpress' ); ?></label></th>
										<td>
											<input
												type="password"
												id="wprefi_email_smtp_password"
												name="wprefi_settings[email_smtp_password]"
												value="<?php echo esc_attr( $wprefi_settings['email_smtp_password'] ?? '' ); ?>"
												class="regular-text"
												autocomplete="new-password"
											/>
											<p class="description"><?php esc_html_e( 'Password is stored encrypted with Sodium in the database.', 'refinerpress' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="wprefi_email_smtp_from_email"><?php esc_html_e( 'From Email Address', 'refinerpress' ); ?></label></th>
										<td>
											<input
												type="email"
												id="wprefi_email_smtp_from_email"
												name="wprefi_settings[email_smtp_from_email]"
												value="<?php echo esc_attr( $wprefi_settings['email_smtp_from_email'] ?? '' ); ?>"
												class="regular-text"
												placeholder="<?php esc_attr_e( 'e.g. info@domain.com', 'refinerpress' ); ?>"
											/>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="wprefi_email_smtp_from_name"><?php esc_html_e( 'From Name', 'refinerpress' ); ?></label></th>
										<td>
											<input
												type="text"
												id="wprefi_email_smtp_from_name"
												name="wprefi_settings[email_smtp_from_name]"
												value="<?php echo esc_attr( $wprefi_settings['email_smtp_from_name'] ?? '' ); ?>"
												class="regular-text"
												placeholder="<?php esc_attr_e( 'e.g. Website Name', 'refinerpress' ); ?>"
											/>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'SSL Certificate Verification', 'refinerpress' ); ?></th>
										<td>
											<label>
												<input
													type="checkbox"
													name="wprefi_settings[email_smtp_disable_ssl_verify]"
													value="1"
													<?php checked( ! empty( $wprefi_settings['email_smtp_disable_ssl_verify'] ) ); ?>
												/>
												<?php esc_html_e( 'Disable SSL certificate verification', 'refinerpress' ); ?>
											</label>
											<p class="description">
												<?php esc_html_e( 'Enable this if you experience SSL certificate errors in local/development environments. NOT recommended for production sites.', 'refinerpress' ); ?>
											</p>
										</td>
									</tr>
								</table>

							</div>
					</div>
				</div>

			</div>

				<?php elseif ( 'login_customizer' === $type ) : ?>

					<label class="wprefi-collapsible-trigger">
						<input
							type="checkbox"
							id="wprefi_login_customizer_enabled"
							class="wprefi-collapsible-checkbox"
							name="wprefi_settings[login_customizer_enabled]"
							value="1"
							<?php checked( ! empty( $wprefi_settings['login_customizer_enabled'] ) ); ?>
						/>
						<strong><?php echo esc_html( $feature['description'] ); ?></strong>
					</label>

					<div class="wprefi-collapsible-content">
						<div class="wprefi-login-customizer-wrapper">

						<h4 class="wprefi-section-title"><?php esc_html_e( 'Logo Settings', 'refinerpress' ); ?></h4>

						<table class="form-table wprefi-login-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Logo Source', 'refinerpress' ); ?></th>
								<td>
									<label>
										<input
											type="radio"
											name="wprefi_settings[login_logo_source]"
											value="site_icon"
											<?php checked( ( $wprefi_settings['login_logo_source'] ?? 'site_icon' ), 'site_icon' ); ?>
										/>
										<?php esc_html_e( 'Use Site Icon (Settings → General → Site Icon)', 'refinerpress' ); ?>
									</label>
									<br>
									<label style="margin-top: 8px; display: inline-block;">
										<input
											type="radio"
											name="wprefi_settings[login_logo_source]"
											value="custom"
											<?php checked( ( $wprefi_settings['login_logo_source'] ?? 'site_icon' ), 'custom' ); ?>
										/>
										<?php esc_html_e( 'Custom Image URL (relative)', 'refinerpress' ); ?>
									</label>
									<div class="wprefi-url-input-wrapper" style="margin-top: 8px; margin-left: 24px;">
										<span class="wprefi-url-prefix"><?php echo esc_html( home_url() ); ?></span>
										<input
											type="text"
											name="wprefi_settings[login_logo_custom_url]"
											value="<?php echo esc_attr( $wprefi_settings['login_logo_custom_url'] ?? '' ); ?>"
											placeholder="/wp-content/uploads/logo.png"
											class="wprefi-url-relative-input"
										/>
									</div>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Logo Size', 'refinerpress' ); ?></th>
								<td>
									<label>
										<?php esc_html_e( 'Width:', 'refinerpress' ); ?>
										<input
											type="number"
											name="wprefi_settings[login_logo_width]"
											value="<?php echo esc_attr( $wprefi_settings['login_logo_width'] ?? 84 ); ?>"
											min="1"
											max="500"
											class="small-text"
										/> px
									</label>
									&nbsp;&nbsp;&nbsp;
									<label>
										<?php esc_html_e( 'Height:', 'refinerpress' ); ?>
										<input
											type="number"
											name="wprefi_settings[login_logo_height]"
											value="<?php echo esc_attr( $wprefi_settings['login_logo_height'] ?? 84 ); ?>"
											min="1"
											max="500"
											class="small-text"
										/> px
									</label>
								</td>
							</tr>
						</table>

						<h4 class="wprefi-section-title"><?php esc_html_e( 'Color Settings', 'refinerpress' ); ?></h4>

						<table class="form-table wprefi-login-table">
							<tr>
								<th scope="row"><label for="wprefi_login_bg_color"><?php esc_html_e( 'Background Color', 'refinerpress' ); ?></label></th>
								<td>
									<input
										type="text"
										id="wprefi_login_bg_color"
										name="wprefi_settings[login_bg_color]"
										value="<?php echo esc_attr( $wprefi_settings['login_bg_color'] ?? '' ); ?>"
										class="wprefi-color-picker"
										data-default-color="#f0f0f1"
									/>
									<p class="description"><?php esc_html_e( 'Login page background color. Default: #f0f0f1', 'refinerpress' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="wprefi_login_primary_color"><?php esc_html_e( 'Primary Color', 'refinerpress' ); ?></label></th>
								<td>
									<input
										type="text"
										id="wprefi_login_primary_color"
										name="wprefi_settings[login_primary_color]"
										value="<?php echo esc_attr( $wprefi_settings['login_primary_color'] ?? '' ); ?>"
										class="wprefi-color-picker"
										data-default-color="#3858e9"
									/>
									<p class="description"><?php esc_html_e( 'Login button color. Default: #3858e9', 'refinerpress' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Language Switcher', 'refinerpress' ); ?></th>
								<td>
									<label>
										<input
											type="checkbox"
											name="wprefi_settings[login_hide_language_switcher]"
											value="1"
											<?php checked( ! empty( $wprefi_settings['login_hide_language_switcher'] ) ); ?>
										/>
										<?php esc_html_e( 'Hide language switcher on login page', 'refinerpress' ); ?>
									</label>
									<p class="description"><?php esc_html_e( 'Removes the language selector dropdown from the login screen.', 'refinerpress' ); ?></p>
								</td>
							</tr>
					</table>

					</div>
				</div>

		<?php elseif ( 'role_redirects' === $type ) : ?>

			<label class="wprefi-collapsible-trigger">
				<input
					type="checkbox"
					id="wprefi_role_redirects_enabled"
					class="wprefi-collapsible-checkbox"
					name="wprefi_settings[role_redirects_enabled]"
					value="1"
					<?php checked( ! empty( $wprefi_settings['role_redirects_enabled'] ) ); ?>
				/>
				<strong><?php echo esc_html( $feature['description'] ); ?></strong>
			</label>

			<div class="wprefi-collapsible-content">
				<div class="wprefi-role-redirects-wrapper">
					<table class="wprefi-role-redirects-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Role', 'refinerpress' ); ?></th>
								<th><?php esc_html_e( 'Redirect After Login', 'refinerpress' ); ?></th>
								<th><?php esc_html_e( 'Redirect After Logout', 'refinerpress' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$login_redirects  = isset( $wprefi_settings['role_redirects_login'] ) && is_array( $wprefi_settings['role_redirects_login'] ) ? $wprefi_settings['role_redirects_login'] : array();
							$logout_redirects = isset( $wprefi_settings['role_redirects_logout'] ) && is_array( $wprefi_settings['role_redirects_logout'] ) ? $wprefi_settings['role_redirects_logout'] : array();

							$site_url = home_url();

							foreach ( $all_roles as $role_slug => $role_name ) :
								// WooCommerce szerepkörök kiszűrése, ha a WooCommerce nem aktív.
								if ( ! class_exists( 'WooCommerce' ) && in_array( $role_slug, array( 'customer', 'shop_manager' ), true ) ) {
									continue;
								}

								$login_url_full  = isset( $login_redirects[ $role_slug ] ) ? $login_redirects[ $role_slug ] : '';
								$logout_url_full = isset( $logout_redirects[ $role_slug ] ) ? $logout_redirects[ $role_slug ] : '';

								// Teljes URL-ből relatív útvonal kinyerése (ha site_url-lel kezdődik).
								$login_relative  = '' !== $login_url_full ? str_replace( $site_url, '', $login_url_full ) : '';
								$logout_relative = '' !== $logout_url_full ? str_replace( $site_url, '', $logout_url_full ) : '';
								?>
								<tr>
									<td class="wprefi-role-name">
										<strong><?php echo esc_html( translate_user_role( $role_name ) ); ?></strong>
									</td>
									<td>
										<div class="wprefi-url-input-wrapper">
											<span class="wprefi-url-prefix"><?php echo esc_html( $site_url ); ?></span>
											<input
												type="text"
												name="wprefi_settings[role_redirects_login][<?php echo esc_attr( $role_slug ); ?>]"
												value="<?php echo esc_attr( $login_relative ); ?>"
												placeholder="/afterlogin/"
												class="wprefi-url-relative-input"
											/>
										</div>
									</td>
									<td>
										<div class="wprefi-url-input-wrapper">
											<span class="wprefi-url-prefix"><?php echo esc_html( $site_url ); ?></span>
											<input
												type="text"
												name="wprefi_settings[role_redirects_logout][<?php echo esc_attr( $role_slug ); ?>]"
												value="<?php echo esc_attr( $logout_relative ); ?>"
												placeholder="/afterlogout/"
												class="wprefi-url-relative-input"
											/>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<p class="description"><?php esc_html_e( 'Leave fields empty where no custom redirect is needed.', 'refinerpress' ); ?></p>
				</div>
			</div>

				<?php elseif ( 'comments_control' === $type ) : ?>

					<div class="wprefi-comments-control">
						<label class="wprefi-comments-main-label">
							<input
								type="checkbox"
								name="wprefi_settings[disable_comments]"
								id="wprefi_disable_comments"
								value="1"
								<?php checked( ! empty( $wprefi_settings['disable_comments'] ) ); ?>
							/>
							<strong><?php esc_html_e( 'Completely disable comments', 'refinerpress' ); ?></strong>
						</label>

						<?php if ( class_exists( 'WooCommerce' ) ) : ?>
							<div class="wprefi-sub-options wprefi-comments-wc-option">
								<label class="wprefi-sub-option-label">
									<input
										type="checkbox"
										name="wprefi_settings[disable_comments_keep_reviews]"
										value="1"
										<?php checked( ! empty( $wprefi_settings['disable_comments_keep_reviews'] ) ); ?>
									/>
									<?php esc_html_e( 'Keep product reviews (WooCommerce)', 'refinerpress' ); ?>
								</label>
							</div>
						<?php endif; ?>
					</div>

			<?php elseif ( 'number_input' === $type ) : ?>
				<?php
				$ni_key         = $feature['option_key'];
				$ni_val         = isset( $wprefi_settings[ $ni_key ] ) ? $wprefi_settings[ $ni_key ] : '';
				$ni_min         = isset( $feature['min'] ) ? (int) $feature['min'] : 0;
				// Set placeholder based on field type.
				if ( 'autosave_interval' === $ni_key ) {
					$ni_placeholder = '120';
				} elseif ( 'trash_auto_delete_days' === $ni_key ) {
					$ni_placeholder = '30';
				} else {
					$ni_placeholder = '5';
				}
				?>
				<div class="wprefi-number-input-row">
					<input
						type="number"
						id="wprefi_<?php echo esc_attr( $key ); ?>"
						name="wprefi_settings[<?php echo esc_attr( $ni_key ); ?>]"
						value="<?php echo esc_attr( $ni_val ); ?>"
						min="<?php echo esc_attr( $ni_min ); ?>"
						placeholder="<?php echo esc_attr( $ni_placeholder ); ?>"
						class="small-text"
					/>
				<p class="description"><?php echo esc_html( $feature['description'] ); ?></p>
			</div>

		<?php elseif ( 'heartbeat_control' === $type ) : ?>

			<label class="wprefi-collapsible-trigger">
				<input
					type="checkbox"
					id="wprefi_heartbeat_control"
					class="wprefi-collapsible-checkbox"
					name="wprefi_settings[heartbeat_control]"
					value="1"
					<?php checked( ! empty( $wprefi_settings['heartbeat_control'] ) ); ?>
				/>
				<strong><?php echo esc_html( $feature['description'] ); ?></strong>
			</label>

			<div class="wprefi-collapsible-content">
				<div class="wprefi-heartbeat-wrapper">

					<div style="margin-bottom: 15px;">
						<label for="wprefi_heartbeat_admin" style="display: inline-block; width: 150px; font-weight: 600;">
							<?php esc_html_e( 'Admin Heartbeat:', 'refinerpress' ); ?>
						</label>
						<select id="wprefi_heartbeat_admin" name="wprefi_settings[heartbeat_admin]" class="regular-text">
							<option value="" <?php selected( ( $wprefi_settings['heartbeat_admin'] ?? '' ), '' ); ?>><?php esc_html_e( 'WordPress default', 'refinerpress' ); ?></option>
							<option value="15" <?php selected( ( $wprefi_settings['heartbeat_admin'] ?? '' ), '15' ); ?>><?php esc_html_e( '15 seconds, dense', 'refinerpress' ); ?></option>
							<option value="30" <?php selected( ( $wprefi_settings['heartbeat_admin'] ?? '' ), '30' ); ?>><?php esc_html_e( '30 seconds, frequent', 'refinerpress' ); ?></option>
							<option value="60" <?php selected( ( $wprefi_settings['heartbeat_admin'] ?? '' ), '60' ); ?>><?php esc_html_e( '60 seconds, medium - Recommended', 'refinerpress' ); ?></option>
							<option value="120" <?php selected( ( $wprefi_settings['heartbeat_admin'] ?? '' ), '120' ); ?>><?php esc_html_e( '120 seconds, rare', 'refinerpress' ); ?></option>
							<option value="disable" <?php selected( ( $wprefi_settings['heartbeat_admin'] ?? '' ), 'disable' ); ?>><?php esc_html_e( 'Disable', 'refinerpress' ); ?></option>
						</select>
					</div>

					<div style="margin-bottom: 15px;">
						<label for="wprefi_heartbeat_frontend" style="display: inline-block; width: 150px; font-weight: 600;">
							<?php esc_html_e( 'Frontend Heartbeat:', 'refinerpress' ); ?>
						</label>
						<select id="wprefi_heartbeat_frontend" name="wprefi_settings[heartbeat_frontend]" class="regular-text">
							<option value="" <?php selected( ( $wprefi_settings['heartbeat_frontend'] ?? '' ), '' ); ?>><?php esc_html_e( 'WordPress default', 'refinerpress' ); ?></option>
							<option value="15" <?php selected( ( $wprefi_settings['heartbeat_frontend'] ?? '' ), '15' ); ?>><?php esc_html_e( '15 seconds, dense', 'refinerpress' ); ?></option>
							<option value="30" <?php selected( ( $wprefi_settings['heartbeat_frontend'] ?? '' ), '30' ); ?>><?php esc_html_e( '30 seconds, frequent', 'refinerpress' ); ?></option>
							<option value="60" <?php selected( ( $wprefi_settings['heartbeat_frontend'] ?? '' ), '60' ); ?>><?php esc_html_e( '60 seconds, medium', 'refinerpress' ); ?></option>
							<option value="120" <?php selected( ( $wprefi_settings['heartbeat_frontend'] ?? '' ), '120' ); ?>><?php esc_html_e( '120 seconds, rare', 'refinerpress' ); ?></option>
							<option value="disable" <?php selected( ( $wprefi_settings['heartbeat_frontend'] ?? '' ), 'disable' ); ?>><?php esc_html_e( 'Disable (Recommended, check other plugins)', 'refinerpress' ); ?></option>
						</select>
					</div>

					<div style="margin-bottom: 15px;">
						<label for="wprefi_heartbeat_editor" style="display: inline-block; width: 150px; font-weight: 600;">
							<?php esc_html_e( 'Post Editor Heartbeat:', 'refinerpress' ); ?>
						</label>
						<select id="wprefi_heartbeat_editor" name="wprefi_settings[heartbeat_editor]" class="regular-text">
							<option value="" <?php selected( ( $wprefi_settings['heartbeat_editor'] ?? '' ), '' ); ?>><?php esc_html_e( 'WordPress default', 'refinerpress' ); ?></option>
							<option value="15" <?php selected( ( $wprefi_settings['heartbeat_editor'] ?? '' ), '15' ); ?>><?php esc_html_e( '15 seconds, dense', 'refinerpress' ); ?></option>
							<option value="30" <?php selected( ( $wprefi_settings['heartbeat_editor'] ?? '' ), '30' ); ?>><?php esc_html_e( '30 seconds, frequent - Recommended', 'refinerpress' ); ?></option>
							<option value="60" <?php selected( ( $wprefi_settings['heartbeat_editor'] ?? '' ), '60' ); ?>><?php esc_html_e( '60 seconds, medium', 'refinerpress' ); ?></option>
							<option value="120" <?php selected( ( $wprefi_settings['heartbeat_editor'] ?? '' ), '120' ); ?>><?php esc_html_e( '120 seconds, rare', 'refinerpress' ); ?></option>
							<option value="disable" <?php selected( ( $wprefi_settings['heartbeat_editor'] ?? '' ), 'disable' ); ?>><?php esc_html_e( "Disable (It's disable autosave and post locking)", 'refinerpress' ); ?></option>
						</select>
					</div>

				</div>
			</div>

		<?php elseif ( 'email_controls' === $type ) : ?>

			<div class="wprefi-email-options">

				<label class="wprefi-feature-group-all" style="font-weight: 600; margin-bottom: 12px; display: block;">
					<input
						type="checkbox"
						id="wprefi_email_disable_all"
						class="wprefi-group-all"
						data-group="email_notifications"
						name="wprefi_settings[email_disable_all]"
						value="1"
						<?php checked( ! empty( $wprefi_settings['email_disable_all'] ) ); ?>
					/>
					<strong><?php esc_html_e( 'Disable All', 'refinerpress' ); ?></strong>
				</label>

				<div class="wprefi-sub-options">

					<div class="wprefi-email-row">
						<label class="wprefi-collapsible-trigger">
							<input
								type="checkbox"
								id="wprefi_email_disable_update"
								class="wprefi-collapsible-checkbox wprefi-group-item"
								data-group="email_notifications"
								name="wprefi_settings[email_disable_update]"
								value="1"
								<?php checked( ! empty( $wprefi_settings['email_disable_update'] ) ); ?>
							/>
							<?php esc_html_e( 'Disable update notifications (core, plugin, theme)', 'refinerpress' ); ?>
						</label>
						<div class="wprefi-collapsible-content">
							<div class="wprefi-email-redirect">
								<label class="wprefi-email-redirect-label" for="wprefi_email_update_address">
									<?php esc_html_e( 'Custom address (if provided, redirects instead of disabling):', 'refinerpress' ); ?>
								</label>
								<input
									type="email"
									id="wprefi_email_update_address"
									name="wprefi_settings[email_update_address]"
									value="<?php echo esc_attr( $wprefi_settings['email_update_address'] ?? '' ); ?>"
									placeholder="email@example.com"
									class="regular-text"
								/>
							</div>
						</div>
					</div>

					<label class="wprefi-email-label">
						<input
							type="checkbox"
							class="wprefi-group-item"
							data-group="email_notifications"
							name="wprefi_settings[email_disable_new_user]"
							value="1"
							<?php checked( ! empty( $wprefi_settings['email_disable_new_user'] ) ); ?>
						/>
						<?php esc_html_e( 'Disable new user registration – admin notification', 'refinerpress' ); ?>
					</label>

					<label class="wprefi-email-label">
						<input
							type="checkbox"
							class="wprefi-group-item"
							data-group="email_notifications"
							name="wprefi_settings[email_disable_password_reset]"
							value="1"
							<?php checked( ! empty( $wprefi_settings['email_disable_password_reset'] ) ); ?>
						/>
						<?php esc_html_e( 'Disable password reset – admin notification', 'refinerpress' ); ?>
					</label>

					<label class="wprefi-email-label">
						<input
							type="checkbox"
							class="wprefi-group-item"
							data-group="email_notifications"
							name="wprefi_settings[email_disable_comments]"
							value="1"
							<?php checked( ! empty( $wprefi_settings['email_disable_comments'] ) ); ?>
						/>
						<?php esc_html_e( 'Disable comment notifications', 'refinerpress' ); ?>
					</label>

					<label class="wprefi-email-label">
						<input
							type="checkbox"
							class="wprefi-group-item"
							data-group="email_notifications"
							name="wprefi_settings[email_disable_privacy]"
							value="1"
							<?php checked( ! empty( $wprefi_settings['email_disable_privacy'] ) ); ?>
						/>
						<?php esc_html_e( 'Disable privacy (GDPR) notifications', 'refinerpress' ); ?>
					</label>

					<div class="wprefi-email-row">
						<label class="wprefi-collapsible-trigger">
							<input
								type="checkbox"
								id="wprefi_email_disable_critical"
								class="wprefi-collapsible-checkbox wprefi-group-item"
								data-group="email_notifications"
								name="wprefi_settings[email_disable_critical]"
								value="1"
								<?php checked( ! empty( $wprefi_settings['email_disable_critical'] ) ); ?>
							/>
							<?php esc_html_e( 'Disable critical error email', 'refinerpress' ); ?>
						</label>
						<div class="wprefi-collapsible-content">
							<div class="wprefi-email-redirect">
								<label class="wprefi-email-redirect-label" for="wprefi_email_critical_address">
									<?php esc_html_e( 'Custom address (if provided, redirects instead of disabling):', 'refinerpress' ); ?>
								</label>
								<input
									type="email"
									id="wprefi_email_critical_address"
									name="wprefi_settings[email_critical_address]"
									value="<?php echo esc_attr( $wprefi_settings['email_critical_address'] ?? '' ); ?>"
									placeholder="email@example.com"
									class="regular-text"
								/>
							</div>
						</div>
					</div>

				</div>

			</div>

				<?php elseif ( 'role_select' === $type ) : ?>
						<?php
						$option_key     = $feature['option_key'];
						$selected_roles = isset( $wprefi_settings[ $option_key ] ) ? (array) $wprefi_settings[ $option_key ] : array();
						$required_roles = isset( $feature['required_roles'] ) ? $feature['required_roles'] : array();
						$enable_key     = isset( $feature['enable_key'] ) ? $feature['enable_key'] : null;
						?>

						<?php if ( $enable_key ) : ?>
							<label class="wprefi-collapsible-trigger">
								<input
									type="checkbox"
									id="wprefi_<?php echo esc_attr( $enable_key ); ?>"
									class="wprefi-collapsible-checkbox"
									name="wprefi_settings[<?php echo esc_attr( $enable_key ); ?>]"
									value="1"
									<?php checked( ! empty( $wprefi_settings[ $enable_key ] ) ); ?>
								/>
								<strong><?php echo esc_html( $feature['description'] ); ?></strong>
							</label>
							<div class="wprefi-collapsible-content">
								<div class="wprefi-role-list">
									<?php foreach ( $all_roles as $role_slug => $role_name ) : ?>
										<?php
										$is_required = in_array( $role_slug, $required_roles, true );
										$is_checked  = $is_required || in_array( $role_slug, $selected_roles, true );
										?>
										<label class="wprefi-role-label">
											<input
												type="checkbox"
												name="wprefi_settings[<?php echo esc_attr( $option_key ); ?>][]"
												value="<?php echo esc_attr( $role_slug ); ?>"
												<?php checked( $is_checked ); ?>
												<?php disabled( $is_required ); ?>
											/>
											<?php echo esc_html( translate_user_role( $role_name ) ); ?>
											<?php if ( $is_required ) : ?>
												<span class="wprefi-role-required"><?php esc_html_e( '(required)', 'refinerpress' ); ?></span>
											<?php endif; ?>
										</label>
									<?php endforeach; ?>
								</div>
							</div>
						<?php else : ?>
							<p class="description" style="margin: 0 0 8px;"><?php echo esc_html( $feature['description'] ); ?></p>
							<div class="wprefi-role-list">
								<?php foreach ( $all_roles as $role_slug => $role_name ) : ?>
									<?php
									$is_required = in_array( $role_slug, $required_roles, true );
									$is_checked  = $is_required || in_array( $role_slug, $selected_roles, true );
									?>
									<label class="wprefi-role-label">
										<input
											type="checkbox"
											name="wprefi_settings[<?php echo esc_attr( $option_key ); ?>][]"
											value="<?php echo esc_attr( $role_slug ); ?>"
											<?php checked( $is_checked ); ?>
											<?php disabled( $is_required ); ?>
										/>
										<?php echo esc_html( translate_user_role( $role_name ) ); ?>
										<?php if ( $is_required ) : ?>
											<span class="wprefi-role-required"><?php esc_html_e( '(required)', 'refinerpress' ); ?></span>
										<?php endif; ?>
									</label>
								<?php endforeach; ?>
					</div>
				<?php endif; ?>

		<?php elseif ( 'maintenance_mode' === $type ) : ?>
			<?php
			$option_key     = $feature['option_key'];
			$selected_roles = isset( $wprefi_settings[ $option_key ] ) ? (array) $wprefi_settings[ $option_key ] : array();
			$required_roles = isset( $feature['required_roles'] ) ? $feature['required_roles'] : array();
			$enable_key     = $feature['enable_key'];
			$message_key    = $feature['message_key'];
			$message_value  = isset( $wprefi_settings[ $message_key ] ) ? $wprefi_settings[ $message_key ] : '';
			?>

			<label class="wprefi-collapsible-trigger">
				<input
					type="checkbox"
					id="wprefi_<?php echo esc_attr( $enable_key ); ?>"
					class="wprefi-collapsible-checkbox"
					name="wprefi_settings[<?php echo esc_attr( $enable_key ); ?>]"
					value="1"
					<?php checked( ! empty( $wprefi_settings[ $enable_key ] ) ); ?>
				/>
				<strong><?php echo esc_html( $feature['description'] ); ?></strong>
			</label>

			<div class="wprefi-collapsible-content">
				<div class="wprefi-maintenance-wrapper">
					
					<!-- Szerepkör lista -->
					<div class="wprefi-role-list">
						<?php foreach ( $all_roles as $role_slug => $role_name ) : ?>
							<?php
							$is_required = in_array( $role_slug, $required_roles, true );
							$is_checked  = $is_required || in_array( $role_slug, $selected_roles, true );
							?>
							<label class="wprefi-role-label">
								<input
									type="checkbox"
									name="wprefi_settings[<?php echo esc_attr( $option_key ); ?>][]"
									value="<?php echo esc_attr( $role_slug ); ?>"
									<?php checked( $is_checked ); ?>
									<?php disabled( $is_required ); ?>
								/>
								<?php echo esc_html( translate_user_role( $role_name ) ); ?>
								<?php if ( $is_required ) : ?>
									<span class="wprefi-role-required"><?php esc_html_e( '(required)', 'refinerpress' ); ?></span>
								<?php endif; ?>
							</label>
						<?php endforeach; ?>
					</div>

					<!-- Üzenet mező -->
					<div class="wprefi-maintenance-message">
						<label for="wprefi_<?php echo esc_attr( $message_key ); ?>">
							<strong><?php esc_html_e( 'Visitor Message:', 'refinerpress' ); ?></strong>
						</label>
						<textarea
							id="wprefi_<?php echo esc_attr( $message_key ); ?>"
							name="wprefi_settings[<?php echo esc_attr( $message_key ); ?>]"
							rows="4"
							class="large-text wprefi-maintenance-textarea"
							placeholder="<?php esc_attr_e( 'This site is temporarily under maintenance. Please check back soon!', 'refinerpress' ); ?>"
						><?php echo esc_textarea( $message_value ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'This message will be displayed to visitors when maintenance mode is active. Leave empty for default message.', 'refinerpress' ); ?>
						</p>
					</div>

				</div>
			</div>

		<?php elseif ( 'login_limit' === $type ) : ?>

				<label class="wprefi-collapsible-trigger">
					<input
						type="checkbox"
						id="wprefi_login_limit_enabled"
						class="wprefi-collapsible-checkbox"
						name="wprefi_settings[login_limit_enabled]"
						value="1"
						<?php checked( ! empty( $wprefi_settings['login_limit_enabled'] ) ); ?>
					/>
					<strong><?php echo esc_html( $feature['description'] ); ?></strong>
				</label>

				<div class="wprefi-collapsible-content">
					<div class="wprefi-login-limit-wrapper">
					<table class="form-table wprefi-login-limit-table">
							<tr>
								<th scope="row" colspan="2">
									<label style="display: flex; align-items: center; gap: 8px;">
										<input
											type="checkbox"
											name="wprefi_settings[login_limit_block_admin_username]"
											value="1"
											<?php checked( ! empty( $wprefi_settings['login_limit_block_admin_username'] ) ); ?>
										/>
										<span><?php esc_html_e( 'Block "admin" Username Instantly', 'refinerpress' ); ?></span>
									</label>
									<p class="description" style="margin: 8px 0 0 28px;">
										<?php esc_html_e( 'Immediately blocks the IP address for 1 hour on the first login attempt with username "admin". Recommended for extra security.', 'refinerpress' ); ?>
									</p>
								</th>
							</tr>
							<tr>
								<th scope="row">
									<label for="wprefi_login_limit_max_attempts">
										<?php esc_html_e( 'Maximum Attempts', 'refinerpress' ); ?>
									</label>
								</th>
								<td>
									<input
										type="number"
										id="wprefi_login_limit_max_attempts"
										name="wprefi_settings[login_limit_max_attempts]"
										value="<?php echo esc_attr( $wprefi_settings['login_limit_max_attempts'] ?? 5 ); ?>"
										class="small-text"
										min="1"
										max="100"
									/>
									<p class="description">
										<?php esc_html_e( 'How many failed login attempts allowed per IP address and username. Default: 5', 'refinerpress' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="wprefi_login_limit_lockout_duration">
										<?php esc_html_e( 'Lockout Duration (minutes)', 'refinerpress' ); ?>
									</label>
								</th>
								<td>
									<input
										type="number"
										id="wprefi_login_limit_lockout_duration"
										name="wprefi_settings[login_limit_lockout_duration]"
										value="<?php echo esc_attr( $wprefi_settings['login_limit_lockout_duration'] ?? 15 ); ?>"
										class="small-text"
										min="1"
										max="1440"
									/>
									<p class="description">
										<?php esc_html_e( 'How long the user should be locked out after reaching the limit (in minutes). Default: 15 minutes', 'refinerpress' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="wprefi_login_limit_whitelist_ips">
										<?php esc_html_e( 'Whitelist IP Addresses', 'refinerpress' ); ?>
									</label>
								</th>
								<td>
									<textarea
										id="wprefi_login_limit_whitelist_ips"
										name="wprefi_settings[login_limit_whitelist_ips]"
										rows="5"
										class="large-text code"
										placeholder="<?php esc_attr_e( '192.168.1.1', 'refinerpress' ); ?>"
									><?php echo esc_textarea( $wprefi_settings['login_limit_whitelist_ips'] ?? '' ); ?></textarea>
									<p class="description">
										<?php esc_html_e( 'IP addresses exempt from the limit (one IP per line). For example, if you have a static IP.', 'refinerpress' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row" colspan="2" style="background: #f9f9f9; padding: 12px;">
									<label style="font-weight: 600; display: flex; align-items: center; gap: 8px;">
										<input
											type="checkbox"
											name="wprefi_settings[login_limit_global_enabled]"
											value="1"
											<?php checked( ! empty( $wprefi_settings['login_limit_global_enabled'] ) ); ?>
										/>
										<span><?php esc_html_e( 'Global Rate Limiting (DDoS Protection)', 'refinerpress' ); ?></span>
									</label>
									<p class="description" style="margin: 8px 0 0 28px; font-weight: normal;">
										<?php esc_html_e( 'Additional protection against distributed brute-force attacks from multiple IP addresses.', 'refinerpress' ); ?>
									</p>
								</th>
							</tr>
							<tr>
								<th scope="row">
									<label for="wprefi_login_limit_global_attempts">
										<?php esc_html_e( 'Global Attempts Limit', 'refinerpress' ); ?>
									</label>
								</th>
								<td>
									<input
										type="number"
										id="wprefi_login_limit_global_attempts"
										name="wprefi_settings[login_limit_global_attempts]"
										value="<?php echo esc_attr( $wprefi_settings['login_limit_global_attempts'] ?? 50 ); ?>"
										class="small-text"
										min="1"
										max="1000"
									/>
									<p class="description">
										<?php esc_html_e( 'Maximum total failed login attempts from all sources. Default: 50', 'refinerpress' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="wprefi_login_limit_global_time_window">
										<?php esc_html_e( 'Global Time Window (minutes)', 'refinerpress' ); ?>
									</label>
								</th>
								<td>
									<input
										type="number"
										id="wprefi_login_limit_global_time_window"
										name="wprefi_settings[login_limit_global_time_window]"
										value="<?php echo esc_attr( $wprefi_settings['login_limit_global_time_window'] ?? 5 ); ?>"
										class="small-text"
										min="1"
										max="60"
									/>
									<p class="description">
										<?php esc_html_e( 'Time period (in minutes) to count total failed attempts. If exceeded, all login attempts will be blocked. Default: 5 minutes', 'refinerpress' ); ?>
									</p>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<?php elseif ( isset( $feature['sub_options'] ) ) : ?>

						<div class="wprefi-feature-group">
							<label class="wprefi-feature-group-all">
								<input
									type="checkbox"
									id="wprefi_<?php echo esc_attr( $key ); ?>_all"
									class="wprefi-group-all"
									data-group="<?php echo esc_attr( $key ); ?>"
								/>
								<strong><?php esc_html_e( 'Disable All', 'refinerpress' ); ?></strong>
							</label>

							<div class="wprefi-sub-options">
								<?php foreach ( $feature['sub_options'] as $sub_key => $sub_label ) : ?>
									<label class="wprefi-sub-option-label">
										<input
											type="checkbox"
											name="wprefi_settings[<?php echo esc_attr( $sub_key ); ?>]"
											value="1"
											class="wprefi-group-item"
											data-group="<?php echo esc_attr( $key ); ?>"
											<?php checked( ! empty( $wprefi_settings[ $sub_key ] ) ); ?>
										/>
										<?php echo esc_html( $sub_label ); ?>
									</label>
								<?php endforeach; ?>
							</div>
						</div>

					<?php else : ?>

						<label for="wprefi_<?php echo esc_attr( $key ); ?>">
							<input
								type="checkbox"
								id="wprefi_<?php echo esc_attr( $key ); ?>"
								name="wprefi_settings[<?php echo esc_attr( $key ); ?>]"
								value="1"
								<?php checked( ! empty( $wprefi_settings[ $key ] ) ); ?>
							/>
							<?php echo esc_html( $feature['description'] ); ?>
						</label>

					<?php endif; ?>

			</td>
		</tr>
	<?php endforeach; ?>

	</table>

<?php endforeach; ?>

<h2 class="wprefi-category-title"><?php esc_html_e( 'Plugin Settings', 'refinerpress' ); ?></h2>

<table class="form-table" role="presentation">
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Uninstall', 'refinerpress' ); ?>
		</th>
		<td>
		<label for="wprefi_delete_data_on_uninstall" class="danger-label">
			<input
				type="checkbox"
				id="wprefi_delete_data_on_uninstall"
				name="wprefi_settings[delete_data_on_uninstall]"
				value="1"
				<?php checked( ! empty( $wprefi_settings['delete_data_on_uninstall'] ) ); ?>
			/>
			<?php esc_html_e( 'Delete plugin settings and data when uninstalling the plugin.', 'refinerpress' ); ?>
		</label>
		</td>
	</tr>
</table>

	<p class="submit">
		<button type="submit" class="wprefi-button"><?php esc_html_e( 'Save Changes', 'refinerpress' ); ?></button>
	</p>
</form>
