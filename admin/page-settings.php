<?php
/**
 * Beállítások oldal tartalma.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$refitune_settings = get_option( 'refitune_settings', array() );
$features     = refitune_get_features();
$all_roles    = wp_roles()->get_names();

// Category definitions.
$categories = array(
	'performance' => __( 'Performance', 'refitune' ),
	'security'    => __( 'Security', 'refitune' ),
	'visual'      => __( 'Visual', 'refitune' ),
	'email'       => __( 'Email', 'refitune' ),
	'misc'        => __( 'Miscellaneous', 'refitune' ),
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

<div class="refitune-settings-nav">
	<?php foreach ( $categories as $cat_key => $cat_label ) : ?>
		<?php if ( isset( $features_by_category[ $cat_key ] ) ) : ?>
			<a href="#refitune-category-<?php echo esc_attr( $cat_key ); ?>" class="refitune-nav-button">
				<?php echo esc_html( $cat_label ); ?>
			</a>
		<?php endif; ?>
	<?php endforeach; ?>
</div>

<form method="post" action="options.php">
	<?php settings_fields( 'refitune_settings_group' ); ?>
	<?php settings_errors( 'refitune_settings' ); ?>

	<?php foreach ( $categories as $cat_key => $cat_label ) : ?>
		<?php if ( ! isset( $features_by_category[ $cat_key ] ) ) {
			continue;
		} ?>

		<h2 id="refitune-category-<?php echo esc_attr( $cat_key ); ?>" class="refitune-category-title">
			<?php echo esc_html( $cat_label ); ?>
		</h2>

		<table class="form-table" role="presentation">
			<?php foreach ( $features_by_category[ $cat_key ] as $key => $feature ) : ?>
			<?php
			$type     = isset( $feature['type'] ) ? $feature['type'] : '';
			$help_id  = str_replace( '_', '-', $key );
			$help_url = admin_url( 'tools.php?page=refitune-help#help-' . $help_id );
			?>
			<tr>
				<th scope="row">
					<?php echo esc_html( $feature['label'] ); ?>
					<a href="<?php echo esc_url( $help_url ); ?>" class="refitune-help-icon" target="_blank" title="<?php esc_attr_e( 'Open Help', 'refitune' ); ?>">
						<span class="dashicons dashicons-info"></span>
					</a>
				</th>
				<td>

				<?php if ( 'email_smtp' === $type ) : ?>
					<?php
					$email_mode = isset( $refitune_settings['email_mode'] ) ? $refitune_settings['email_mode'] : 'default';
					?>

			<div class="refitune-email-smtp-wrapper">

				<div class="refitune-email-mode-selector">
					<label>
						<input
							type="radio"
							name="refitune_settings[email_mode]"
							value="default"
							<?php checked( $email_mode, 'default' ); ?>
						/>
						<strong><?php esc_html_e( 'WordPress default email sending (or other SMTP plugin)', 'refitune' ); ?></strong>
					</label>
					<br>
					<label>
						<input
							type="radio"
							name="refitune_settings[email_mode]"
							value="disable_all"
							<?php checked( $email_mode, 'disable_all' ); ?>
						/>
						<strong><?php esc_html_e( 'Completely disable email sending', 'refitune' ); ?></strong>
					</label>
					<br>
					<label class="refitune-collapsible-trigger">
						<input
							type="radio"
							id="refitune_email_mode_smtp"
							class="refitune-collapsible-checkbox"
							name="refitune_settings[email_mode]"
							value="smtp"
							<?php checked( $email_mode, 'smtp' ); ?>
						/>
						<strong><?php esc_html_e( 'SMTP email sending', 'refitune' ); ?></strong>
					</label>
					<div class="refitune-collapsible-content">
						<div class="refitune-email-smtp-config">
							<h4 class="refitune-smtp-title"><?php esc_html_e( 'SMTP Settings', 'refitune' ); ?></h4>
							<p class="description"><?php esc_html_e( 'The following settings apply when "SMTP email sending" option is selected.', 'refitune' ); ?></p>

							<table class="form-table refitune-smtp-fields">
									<tr>
										<th scope="row"><label for="refitune_email_smtp_host"><?php esc_html_e( 'SMTP Host', 'refitune' ); ?></label></th>
										<td>
											<input
												type="text"
												id="refitune_email_smtp_host"
												name="refitune_settings[email_smtp_host]"
												value="<?php echo esc_attr( $refitune_settings['email_smtp_host'] ?? '' ); ?>"
												class="regular-text"
												placeholder="<?php esc_attr_e( 'e.g. smtp.domain.com', 'refitune' ); ?>"
											/>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="refitune_email_smtp_port"><?php esc_html_e( 'SMTP Port', 'refitune' ); ?></label></th>
										<td>
											<input
												type="number"
												id="refitune_email_smtp_port"
												name="refitune_settings[email_smtp_port]"
												value="<?php echo esc_attr( $refitune_settings['email_smtp_port'] ?? 587 ); ?>"
												class="small-text"
												min="1"
												max="65535"
											/>
											<p class="description"><?php esc_html_e( 'Usually 587 (TLS) or 465 (SSL).', 'refitune' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="refitune_email_smtp_encryption"><?php esc_html_e( 'Encryption', 'refitune' ); ?></label></th>
										<td>
											<?php
											$smtp_encryption = $refitune_settings['email_smtp_encryption'] ?? 'tls';
											if ( 'disable' === $smtp_encryption ) {
												$smtp_encryption = 'none';
											}
											$smtp_test_mode = ! empty( $refitune_settings['email_smtp_disable_for_test'] )
												|| ! empty( $refitune_settings['email_smtp_disable_ssl_verify'] )
												|| 'disable' === ( $refitune_settings['email_smtp_encryption'] ?? '' );
											?>
											<select id="refitune_email_smtp_encryption" name="refitune_settings[email_smtp_encryption]">
												<option value="tls" <?php selected( $smtp_encryption, 'tls' ); ?>>TLS</option>
												<option value="ssl" <?php selected( $smtp_encryption, 'ssl' ); ?>>SSL</option>
												<option value="none" <?php selected( $smtp_encryption, 'none' ); ?>><?php esc_html_e( 'None', 'refitune' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Test environment', 'refitune' ); ?></th>
										<td>
											<label for="refitune_email_smtp_disable_for_test">
												<input
													type="checkbox"
													id="refitune_email_smtp_disable_for_test"
													name="refitune_settings[email_smtp_disable_for_test]"
													value="1"
													<?php checked( $smtp_test_mode ); ?>
												/>
												<?php esc_html_e( 'Disable (only for test)', 'refitune' ); ?>
											</label>
											<p class="description">
												<?php esc_html_e( 'Disables SMTP encryption and SSL certificate verification. Use only in local or staging environments.', 'refitune' ); ?>
											</p>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="refitune_email_smtp_username"><?php esc_html_e( 'SMTP Username', 'refitune' ); ?></label></th>
										<td>
											<input
												type="text"
												id="refitune_email_smtp_username"
												name="refitune_settings[email_smtp_username]"
												value="<?php echo esc_attr( $refitune_settings['email_smtp_username'] ?? '' ); ?>"
												class="regular-text"
												autocomplete="off"
											/>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="refitune_email_smtp_password"><?php esc_html_e( 'SMTP Password', 'refitune' ); ?></label></th>
										<td>
											<input
												type="password"
												id="refitune_email_smtp_password"
												name="refitune_settings[email_smtp_password]"
												value=""
												class="regular-text"
												autocomplete="new-password"
												placeholder="<?php esc_attr_e( 'Leave blank to keep the current password', 'refitune' ); ?>"
											/>
											<p class="description">
												<?php
												if ( ! empty( $refitune_settings['email_smtp_password'] ) ) {
													esc_html_e( 'A password is already stored. Enter a new value only to change it.', 'refitune' );
												} else {
													esc_html_e( 'Password is stored encrypted with Sodium in the database.', 'refitune' );
												}
												?>
											</p>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="refitune_email_smtp_from_email"><?php esc_html_e( 'From Email Address', 'refitune' ); ?></label></th>
										<td>
											<input
												type="email"
												id="refitune_email_smtp_from_email"
												name="refitune_settings[email_smtp_from_email]"
												value="<?php echo esc_attr( $refitune_settings['email_smtp_from_email'] ?? '' ); ?>"
												class="regular-text"
												placeholder="<?php esc_attr_e( 'e.g. info@domain.com', 'refitune' ); ?>"
											/>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="refitune_email_smtp_from_name"><?php esc_html_e( 'From Name', 'refitune' ); ?></label></th>
										<td>
											<input
												type="text"
												id="refitune_email_smtp_from_name"
												name="refitune_settings[email_smtp_from_name]"
												value="<?php echo esc_attr( $refitune_settings['email_smtp_from_name'] ?? '' ); ?>"
												class="regular-text"
												placeholder="<?php esc_attr_e( 'e.g. Website Name', 'refitune' ); ?>"
											/>
										</td>
									</tr>
								</table>

							</div>
					</div>
				</div>

			</div>

				<?php elseif ( 'login_customizer' === $type ) : ?>

					<label class="refitune-collapsible-trigger">
						<input
							type="checkbox"
							id="refitune_login_customizer_enabled"
							class="refitune-collapsible-checkbox"
							name="refitune_settings[login_customizer_enabled]"
							value="1"
							<?php checked( ! empty( $refitune_settings['login_customizer_enabled'] ) ); ?>
						/>
						<strong><?php echo esc_html( $feature['description'] ); ?></strong>
					</label>

					<div class="refitune-collapsible-content">
						<div class="refitune-login-customizer-wrapper">

						<h4 class="refitune-section-title"><?php esc_html_e( 'Logo Settings', 'refitune' ); ?></h4>

						<table class="form-table refitune-login-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Logo Source', 'refitune' ); ?></th>
								<td>
									<label>
										<input
											type="radio"
											name="refitune_settings[login_logo_source]"
											value="site_icon"
											<?php checked( ( $refitune_settings['login_logo_source'] ?? 'site_icon' ), 'site_icon' ); ?>
										/>
										<?php esc_html_e( 'Use Site Icon (Settings → General → Site Icon)', 'refitune' ); ?>
									</label>
									<br>
									<label style="margin-top: 8px; display: inline-block;">
										<input
											type="radio"
											name="refitune_settings[login_logo_source]"
											value="custom"
											<?php checked( ( $refitune_settings['login_logo_source'] ?? 'site_icon' ), 'custom' ); ?>
										/>
										<?php esc_html_e( 'Custom Image URL (relative)', 'refitune' ); ?>
									</label>
									<div class="refitune-url-input-wrapper" style="margin-top: 8px; margin-left: 24px;">
										<span class="refitune-url-prefix"><?php echo esc_html( home_url() ); ?></span>
										<input
											type="text"
											name="refitune_settings[login_logo_custom_url]"
											value="<?php echo esc_attr( $refitune_settings['login_logo_custom_url'] ?? '' ); ?>"
											placeholder="/wp-content/uploads/logo.png"
											class="refitune-url-relative-input"
										/>
									</div>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Logo Size', 'refitune' ); ?></th>
								<td>
									<label>
										<?php esc_html_e( 'Width:', 'refitune' ); ?>
										<input
											type="number"
											name="refitune_settings[login_logo_width]"
											value="<?php echo esc_attr( $refitune_settings['login_logo_width'] ?? 84 ); ?>"
											min="1"
											max="500"
											class="small-text"
										/> px
									</label>
									&nbsp;&nbsp;&nbsp;
									<label>
										<?php esc_html_e( 'Height:', 'refitune' ); ?>
										<input
											type="number"
											name="refitune_settings[login_logo_height]"
											value="<?php echo esc_attr( $refitune_settings['login_logo_height'] ?? 84 ); ?>"
											min="1"
											max="500"
											class="small-text"
										/> px
									</label>
								</td>
							</tr>
						</table>

						<h4 class="refitune-section-title"><?php esc_html_e( 'Color Settings', 'refitune' ); ?></h4>

						<table class="form-table refitune-login-table">
							<tr>
								<th scope="row"><label for="refitune_login_bg_color"><?php esc_html_e( 'Background Color', 'refitune' ); ?></label></th>
								<td>
									<input
										type="text"
										id="refitune_login_bg_color"
										name="refitune_settings[login_bg_color]"
										value="<?php echo esc_attr( $refitune_settings['login_bg_color'] ?? '' ); ?>"
										class="refitune-color-picker"
										data-default-color="#f0f0f1"
									/>
									<p class="description"><?php esc_html_e( 'Login page background color. Default: #f0f0f1', 'refitune' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="refitune_login_primary_color"><?php esc_html_e( 'Primary Color', 'refitune' ); ?></label></th>
								<td>
									<input
										type="text"
										id="refitune_login_primary_color"
										name="refitune_settings[login_primary_color]"
										value="<?php echo esc_attr( $refitune_settings['login_primary_color'] ?? '' ); ?>"
										class="refitune-color-picker"
										data-default-color="#3858e9"
									/>
									<p class="description"><?php esc_html_e( 'Login button color. Default: #3858e9', 'refitune' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Language Switcher', 'refitune' ); ?></th>
								<td>
									<label>
										<input
											type="checkbox"
											name="refitune_settings[login_hide_language_switcher]"
											value="1"
											<?php checked( ! empty( $refitune_settings['login_hide_language_switcher'] ) ); ?>
										/>
										<?php esc_html_e( 'Hide language switcher on login page', 'refitune' ); ?>
									</label>
									<p class="description"><?php esc_html_e( 'Removes the language selector dropdown from the login screen.', 'refitune' ); ?></p>
								</td>
							</tr>
					</table>

					</div>
				</div>

		<?php elseif ( 'role_redirects' === $type ) : ?>

			<label class="refitune-collapsible-trigger">
				<input
					type="checkbox"
					id="refitune_role_redirects_enabled"
					class="refitune-collapsible-checkbox"
					name="refitune_settings[role_redirects_enabled]"
					value="1"
					<?php checked( ! empty( $refitune_settings['role_redirects_enabled'] ) ); ?>
				/>
				<strong><?php echo esc_html( $feature['description'] ); ?></strong>
			</label>

			<div class="refitune-collapsible-content">
				<div class="refitune-role-redirects-wrapper">
					<table class="refitune-role-redirects-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Role', 'refitune' ); ?></th>
								<th><?php esc_html_e( 'Redirect After Login', 'refitune' ); ?></th>
								<th><?php esc_html_e( 'Redirect After Logout', 'refitune' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$login_redirects  = isset( $refitune_settings['role_redirects_login'] ) && is_array( $refitune_settings['role_redirects_login'] ) ? $refitune_settings['role_redirects_login'] : array();
							$logout_redirects = isset( $refitune_settings['role_redirects_logout'] ) && is_array( $refitune_settings['role_redirects_logout'] ) ? $refitune_settings['role_redirects_logout'] : array();

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
									<td class="refitune-role-name">
										<strong><?php echo esc_html( translate_user_role( $role_name ) ); ?></strong>
									</td>
									<td>
										<div class="refitune-url-input-wrapper">
											<span class="refitune-url-prefix"><?php echo esc_html( $site_url ); ?></span>
											<input
												type="text"
												name="refitune_settings[role_redirects_login][<?php echo esc_attr( $role_slug ); ?>]"
												value="<?php echo esc_attr( $login_relative ); ?>"
												placeholder="/afterlogin/"
												class="refitune-url-relative-input"
											/>
										</div>
									</td>
									<td>
										<div class="refitune-url-input-wrapper">
											<span class="refitune-url-prefix"><?php echo esc_html( $site_url ); ?></span>
											<input
												type="text"
												name="refitune_settings[role_redirects_logout][<?php echo esc_attr( $role_slug ); ?>]"
												value="<?php echo esc_attr( $logout_relative ); ?>"
												placeholder="/afterlogout/"
												class="refitune-url-relative-input"
											/>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<p class="description"><?php esc_html_e( 'Leave fields empty where no custom redirect is needed.', 'refitune' ); ?></p>
				</div>
			</div>

				<?php elseif ( 'comments_control' === $type ) : ?>

					<div class="refitune-comments-control">
						<label class="refitune-comments-main-label">
							<input
								type="checkbox"
								name="refitune_settings[disable_comments]"
								id="refitune_disable_comments"
								value="1"
								<?php checked( ! empty( $refitune_settings['disable_comments'] ) ); ?>
							/>
							<strong><?php esc_html_e( 'Completely disable comments', 'refitune' ); ?></strong>
						</label>

						<?php if ( class_exists( 'WooCommerce' ) ) : ?>
							<div class="refitune-sub-options refitune-comments-wc-option">
								<label class="refitune-sub-option-label">
									<input
										type="checkbox"
										name="refitune_settings[disable_comments_keep_reviews]"
										value="1"
										<?php checked( ! empty( $refitune_settings['disable_comments_keep_reviews'] ) ); ?>
									/>
									<?php esc_html_e( 'Keep product reviews (WooCommerce)', 'refitune' ); ?>
								</label>
							</div>
						<?php endif; ?>
					</div>

			<?php elseif ( 'number_input' === $type ) : ?>
				<?php
				$ni_key         = $feature['option_key'];
				$ni_val         = isset( $refitune_settings[ $ni_key ] ) ? $refitune_settings[ $ni_key ] : '';
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
				<div class="refitune-number-input-row">
					<input
						type="number"
						id="refitune_<?php echo esc_attr( $key ); ?>"
						name="refitune_settings[<?php echo esc_attr( $ni_key ); ?>]"
						value="<?php echo esc_attr( $ni_val ); ?>"
						min="<?php echo esc_attr( $ni_min ); ?>"
						placeholder="<?php echo esc_attr( $ni_placeholder ); ?>"
						class="small-text"
					/>
				<p class="description"><?php echo esc_html( $feature['description'] ); ?></p>
			</div>

		<?php elseif ( 'auto_updates_control' === $type ) : ?>

			<label class="refitune-collapsible-trigger">
				<input
					type="checkbox"
					id="refitune_auto_updates_control"
					class="refitune-collapsible-checkbox"
					name="refitune_settings[auto_updates_control]"
					value="1"
					<?php checked( ! empty( $refitune_settings['auto_updates_control'] ) ); ?>
				/>
				<strong><?php echo esc_html( $feature['description'] ); ?></strong>
			</label>

			<div class="refitune-collapsible-content">
				<div class="refitune-auto-updates-wrapper">

					<p class="description" style="margin-bottom: 15px;">
						<?php esc_html_e( 'Enable all applies to every plugin or theme and overrides per-item choices on the Updates screen. WordPress default leaves native behavior unchanged.', 'refitune' ); ?>
					</p>

					<div style="margin-bottom: 15px;">
						<?php
						$legacy_plugins_key    = refitune_legacy_plugins_auto_option_key();
						$refitune_plugins_auto = $refitune_settings['refitune_plugins_auto'] ?? $refitune_settings[ $legacy_plugins_key ] ?? 'default';
						?>
						<label for="refitune_plugins_auto" style="display: inline-block; width: 200px; font-weight: 600;">
							<?php esc_html_e( 'Plugins:', 'refitune' ); ?>
						</label>
						<select id="refitune_plugins_auto" name="refitune_settings[refitune_plugins_auto]" class="regular-text">
							<option value="default" <?php selected( $refitune_plugins_auto, 'default' ); ?>><?php esc_html_e( 'WordPress default', 'refitune' ); ?></option>
							<option value="enable" <?php selected( $refitune_plugins_auto, 'enable' ); ?>><?php esc_html_e( 'Enable all', 'refitune' ); ?></option>
							<option value="disable" <?php selected( $refitune_plugins_auto, 'disable' ); ?>><?php esc_html_e( 'Disable all', 'refitune' ); ?></option>
						</select>
					</div>

					<div style="margin-bottom: 15px;">
						<label for="refitune_auto_update_themes" style="display: inline-block; width: 200px; font-weight: 600;">
							<?php esc_html_e( 'Themes:', 'refitune' ); ?>
						</label>
						<select id="refitune_auto_update_themes" name="refitune_settings[auto_update_themes]" class="regular-text">
							<option value="default" <?php selected( ( $refitune_settings['auto_update_themes'] ?? 'default' ), 'default' ); ?>><?php esc_html_e( 'WordPress default', 'refitune' ); ?></option>
							<option value="enable" <?php selected( ( $refitune_settings['auto_update_themes'] ?? 'default' ), 'enable' ); ?>><?php esc_html_e( 'Enable all', 'refitune' ); ?></option>
							<option value="disable" <?php selected( ( $refitune_settings['auto_update_themes'] ?? 'default' ), 'disable' ); ?>><?php esc_html_e( 'Disable all', 'refitune' ); ?></option>
						</select>
					</div>

					<div style="margin-bottom: 15px;">
						<label for="refitune_auto_update_translations" style="display: inline-block; width: 200px; font-weight: 600;">
							<?php esc_html_e( 'Translations:', 'refitune' ); ?>
						</label>
						<select id="refitune_auto_update_translations" name="refitune_settings[auto_update_translations]" class="regular-text">
							<option value="default" <?php selected( ( $refitune_settings['auto_update_translations'] ?? 'default' ), 'default' ); ?>><?php esc_html_e( 'WordPress default', 'refitune' ); ?></option>
							<option value="enable" <?php selected( ( $refitune_settings['auto_update_translations'] ?? 'default' ), 'enable' ); ?>><?php esc_html_e( 'Enable', 'refitune' ); ?></option>
							<option value="disable" <?php selected( ( $refitune_settings['auto_update_translations'] ?? 'default' ), 'disable' ); ?>><?php esc_html_e( 'Disable', 'refitune' ); ?></option>
						</select>
					</div>

					<hr style="margin: 20px 0;" />

					<div style="margin-bottom: 15px;">
						<label for="refitune_auto_update_core_minor" style="display: inline-block; width: 200px; font-weight: 600;">
							<?php esc_html_e( 'Core minor updates:', 'refitune' ); ?>
						</label>
						<select id="refitune_auto_update_core_minor" name="refitune_settings[auto_update_core_minor]" class="regular-text">
							<option value="default" <?php selected( ( $refitune_settings['auto_update_core_minor'] ?? 'default' ), 'default' ); ?>><?php esc_html_e( 'WordPress default', 'refitune' ); ?></option>
							<option value="enable" <?php selected( ( $refitune_settings['auto_update_core_minor'] ?? 'default' ), 'enable' ); ?>><?php esc_html_e( 'Enable', 'refitune' ); ?></option>
							<option value="disable" <?php selected( ( $refitune_settings['auto_update_core_minor'] ?? 'default' ), 'disable' ); ?>><?php esc_html_e( 'Disable', 'refitune' ); ?></option>
						</select>
					</div>

					<div style="margin-bottom: 15px;">
						<label for="refitune_auto_update_core_major" style="display: inline-block; width: 200px; font-weight: 600;">
							<?php esc_html_e( 'Core major updates:', 'refitune' ); ?>
						</label>
						<select id="refitune_auto_update_core_major" name="refitune_settings[auto_update_core_major]" class="regular-text">
							<option value="default" <?php selected( ( $refitune_settings['auto_update_core_major'] ?? 'default' ), 'default' ); ?>><?php esc_html_e( 'WordPress default', 'refitune' ); ?></option>
							<option value="enable" <?php selected( ( $refitune_settings['auto_update_core_major'] ?? 'default' ), 'enable' ); ?>><?php esc_html_e( 'Enable', 'refitune' ); ?></option>
							<option value="disable" <?php selected( ( $refitune_settings['auto_update_core_major'] ?? 'default' ), 'disable' ); ?>><?php esc_html_e( 'Disable', 'refitune' ); ?></option>
						</select>
					</div>

					<div style="margin-bottom: 15px;">
						<label for="refitune_auto_update_core_dev" style="display: inline-block; width: 200px; font-weight: 600;">
							<?php esc_html_e( 'Core development updates:', 'refitune' ); ?>
						</label>
						<select id="refitune_auto_update_core_dev" name="refitune_settings[auto_update_core_dev]" class="regular-text">
							<option value="default" <?php selected( ( $refitune_settings['auto_update_core_dev'] ?? 'default' ), 'default' ); ?>><?php esc_html_e( 'WordPress default', 'refitune' ); ?></option>
							<option value="enable" <?php selected( ( $refitune_settings['auto_update_core_dev'] ?? 'default' ), 'enable' ); ?>><?php esc_html_e( 'Enable', 'refitune' ); ?></option>
							<option value="disable" <?php selected( ( $refitune_settings['auto_update_core_dev'] ?? 'default' ), 'disable' ); ?>><?php esc_html_e( 'Disable', 'refitune' ); ?></option>
						</select>
					</div>

					<hr style="margin: 20px 0;" />

					<div style="margin-bottom: 15px;">
						<label for="refitune_update_check_interval" style="display: inline-block; width: 200px; font-weight: 600;">
							<?php esc_html_e( 'Check for updates:', 'refitune' ); ?>
						</label>
						<select id="refitune_update_check_interval" name="refitune_settings[update_check_interval]" class="regular-text">
							<option value="default" <?php selected( ( $refitune_settings['update_check_interval'] ?? 'default' ), 'default' ); ?>><?php esc_html_e( 'WordPress default (twice daily)', 'refitune' ); ?></option>
							<option value="daily" <?php selected( ( $refitune_settings['update_check_interval'] ?? 'default' ), 'daily' ); ?>><?php esc_html_e( 'Once daily', 'refitune' ); ?></option>
							<option value="3_days" <?php selected( ( $refitune_settings['update_check_interval'] ?? 'default' ), '3_days' ); ?>><?php esc_html_e( 'Every 3 days', 'refitune' ); ?></option>
							<option value="7_days" <?php selected( ( $refitune_settings['update_check_interval'] ?? 'default' ), '7_days' ); ?>><?php esc_html_e( 'Every 7 days', 'refitune' ); ?></option>
							<option value="14_days" <?php selected( ( $refitune_settings['update_check_interval'] ?? 'default' ), '14_days' ); ?>><?php esc_html_e( 'Every 14 days', 'refitune' ); ?></option>
						</select>
					</div>

				</div>
			</div>

		<?php elseif ( 'heartbeat_control' === $type ) : ?>

			<label class="refitune-collapsible-trigger">
				<input
					type="checkbox"
					id="refitune_heartbeat_control"
					class="refitune-collapsible-checkbox"
					name="refitune_settings[heartbeat_control]"
					value="1"
					<?php checked( ! empty( $refitune_settings['heartbeat_control'] ) ); ?>
				/>
				<strong><?php echo esc_html( $feature['description'] ); ?></strong>
			</label>

			<div class="refitune-collapsible-content">
				<div class="refitune-heartbeat-wrapper">

					<div style="margin-bottom: 15px;">
						<label for="refitune_heartbeat_admin" style="display: inline-block; width: 150px; font-weight: 600;">
							<?php esc_html_e( 'Admin Heartbeat:', 'refitune' ); ?>
						</label>
						<select id="refitune_heartbeat_admin" name="refitune_settings[heartbeat_admin]" class="regular-text">
							<option value="" <?php selected( ( $refitune_settings['heartbeat_admin'] ?? '' ), '' ); ?>><?php esc_html_e( 'WordPress default', 'refitune' ); ?></option>
							<option value="15" <?php selected( ( $refitune_settings['heartbeat_admin'] ?? '' ), '15' ); ?>><?php esc_html_e( '15 seconds, dense', 'refitune' ); ?></option>
							<option value="30" <?php selected( ( $refitune_settings['heartbeat_admin'] ?? '' ), '30' ); ?>><?php esc_html_e( '30 seconds, frequent', 'refitune' ); ?></option>
							<option value="60" <?php selected( ( $refitune_settings['heartbeat_admin'] ?? '' ), '60' ); ?>><?php esc_html_e( '60 seconds, medium - Recommended', 'refitune' ); ?></option>
							<option value="120" <?php selected( ( $refitune_settings['heartbeat_admin'] ?? '' ), '120' ); ?>><?php esc_html_e( '120 seconds, rare', 'refitune' ); ?></option>
							<option value="disable" <?php selected( ( $refitune_settings['heartbeat_admin'] ?? '' ), 'disable' ); ?>><?php esc_html_e( 'Disable', 'refitune' ); ?></option>
						</select>
					</div>

					<div style="margin-bottom: 15px;">
						<label for="refitune_heartbeat_frontend" style="display: inline-block; width: 150px; font-weight: 600;">
							<?php esc_html_e( 'Frontend Heartbeat:', 'refitune' ); ?>
						</label>
						<select id="refitune_heartbeat_frontend" name="refitune_settings[heartbeat_frontend]" class="regular-text">
							<option value="" <?php selected( ( $refitune_settings['heartbeat_frontend'] ?? '' ), '' ); ?>><?php esc_html_e( 'WordPress default', 'refitune' ); ?></option>
							<option value="15" <?php selected( ( $refitune_settings['heartbeat_frontend'] ?? '' ), '15' ); ?>><?php esc_html_e( '15 seconds, dense', 'refitune' ); ?></option>
							<option value="30" <?php selected( ( $refitune_settings['heartbeat_frontend'] ?? '' ), '30' ); ?>><?php esc_html_e( '30 seconds, frequent', 'refitune' ); ?></option>
							<option value="60" <?php selected( ( $refitune_settings['heartbeat_frontend'] ?? '' ), '60' ); ?>><?php esc_html_e( '60 seconds, medium', 'refitune' ); ?></option>
							<option value="120" <?php selected( ( $refitune_settings['heartbeat_frontend'] ?? '' ), '120' ); ?>><?php esc_html_e( '120 seconds, rare', 'refitune' ); ?></option>
							<option value="disable" <?php selected( ( $refitune_settings['heartbeat_frontend'] ?? '' ), 'disable' ); ?>><?php esc_html_e( 'Disable (Recommended, check other plugins)', 'refitune' ); ?></option>
						</select>
					</div>

					<div style="margin-bottom: 15px;">
						<label for="refitune_heartbeat_editor" style="display: inline-block; width: 150px; font-weight: 600;">
							<?php esc_html_e( 'Post Editor Heartbeat:', 'refitune' ); ?>
						</label>
						<select id="refitune_heartbeat_editor" name="refitune_settings[heartbeat_editor]" class="regular-text">
							<option value="" <?php selected( ( $refitune_settings['heartbeat_editor'] ?? '' ), '' ); ?>><?php esc_html_e( 'WordPress default', 'refitune' ); ?></option>
							<option value="15" <?php selected( ( $refitune_settings['heartbeat_editor'] ?? '' ), '15' ); ?>><?php esc_html_e( '15 seconds, dense', 'refitune' ); ?></option>
							<option value="30" <?php selected( ( $refitune_settings['heartbeat_editor'] ?? '' ), '30' ); ?>><?php esc_html_e( '30 seconds, frequent - Recommended', 'refitune' ); ?></option>
							<option value="60" <?php selected( ( $refitune_settings['heartbeat_editor'] ?? '' ), '60' ); ?>><?php esc_html_e( '60 seconds, medium', 'refitune' ); ?></option>
							<option value="120" <?php selected( ( $refitune_settings['heartbeat_editor'] ?? '' ), '120' ); ?>><?php esc_html_e( '120 seconds, rare', 'refitune' ); ?></option>
							<option value="disable" <?php selected( ( $refitune_settings['heartbeat_editor'] ?? '' ), 'disable' ); ?>><?php esc_html_e( "Disable (It's disable autosave and post locking)", 'refitune' ); ?></option>
						</select>
					</div>

				</div>
			</div>

		<?php elseif ( 'email_controls' === $type ) : ?>

			<div class="refitune-email-options">

				<label class="refitune-feature-group-all" style="font-weight: 600; margin-bottom: 12px; display: block;">
					<input
						type="checkbox"
						id="refitune_email_disable_all"
						class="refitune-group-all"
						data-group="email_notifications"
						name="refitune_settings[email_disable_all]"
						value="1"
						<?php checked( ! empty( $refitune_settings['email_disable_all'] ) ); ?>
					/>
					<strong><?php esc_html_e( 'Disable All', 'refitune' ); ?></strong>
				</label>

				<div class="refitune-sub-options">

					<div class="refitune-email-row">
						<label class="refitune-collapsible-trigger">
							<input
								type="checkbox"
								id="refitune_email_disable_update"
								class="refitune-collapsible-checkbox refitune-group-item"
								data-group="email_notifications"
								name="refitune_settings[email_disable_update]"
								value="1"
								<?php checked( ! empty( $refitune_settings['email_disable_update'] ) ); ?>
							/>
							<?php esc_html_e( 'Disable update notifications (core, plugin, theme)', 'refitune' ); ?>
						</label>
						<div class="refitune-collapsible-content">
							<div class="refitune-email-redirect">
								<label class="refitune-email-redirect-label" for="refitune_email_update_address">
									<?php esc_html_e( 'Custom address (if provided, redirects instead of disabling):', 'refitune' ); ?>
								</label>
								<input
									type="email"
									id="refitune_email_update_address"
									name="refitune_settings[email_update_address]"
									value="<?php echo esc_attr( $refitune_settings['email_update_address'] ?? '' ); ?>"
									placeholder="email@example.com"
									class="regular-text"
								/>
							</div>
						</div>
					</div>

					<label class="refitune-email-label">
						<input
							type="checkbox"
							class="refitune-group-item"
							data-group="email_notifications"
							name="refitune_settings[email_disable_new_user]"
							value="1"
							<?php checked( ! empty( $refitune_settings['email_disable_new_user'] ) ); ?>
						/>
						<?php esc_html_e( 'Disable new user registration – admin notification', 'refitune' ); ?>
					</label>

					<label class="refitune-email-label">
						<input
							type="checkbox"
							class="refitune-group-item"
							data-group="email_notifications"
							name="refitune_settings[email_disable_password_reset]"
							value="1"
							<?php checked( ! empty( $refitune_settings['email_disable_password_reset'] ) ); ?>
						/>
						<?php esc_html_e( 'Disable password reset – admin notification', 'refitune' ); ?>
					</label>

					<label class="refitune-email-label">
						<input
							type="checkbox"
							class="refitune-group-item"
							data-group="email_notifications"
							name="refitune_settings[email_disable_comments]"
							value="1"
							<?php checked( ! empty( $refitune_settings['email_disable_comments'] ) ); ?>
						/>
						<?php esc_html_e( 'Disable comment notifications', 'refitune' ); ?>
					</label>

					<label class="refitune-email-label">
						<input
							type="checkbox"
							class="refitune-group-item"
							data-group="email_notifications"
							name="refitune_settings[email_disable_privacy]"
							value="1"
							<?php checked( ! empty( $refitune_settings['email_disable_privacy'] ) ); ?>
						/>
						<?php esc_html_e( 'Disable privacy (GDPR) notifications', 'refitune' ); ?>
					</label>

					<div class="refitune-email-row">
						<label class="refitune-collapsible-trigger">
							<input
								type="checkbox"
								id="refitune_email_disable_critical"
								class="refitune-collapsible-checkbox refitune-group-item"
								data-group="email_notifications"
								name="refitune_settings[email_disable_critical]"
								value="1"
								<?php checked( ! empty( $refitune_settings['email_disable_critical'] ) ); ?>
							/>
							<?php esc_html_e( 'Disable critical error email', 'refitune' ); ?>
						</label>
						<div class="refitune-collapsible-content">
							<div class="refitune-email-redirect">
								<label class="refitune-email-redirect-label" for="refitune_email_critical_address">
									<?php esc_html_e( 'Custom address (if provided, redirects instead of disabling):', 'refitune' ); ?>
								</label>
								<input
									type="email"
									id="refitune_email_critical_address"
									name="refitune_settings[email_critical_address]"
									value="<?php echo esc_attr( $refitune_settings['email_critical_address'] ?? '' ); ?>"
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
						$selected_roles = isset( $refitune_settings[ $option_key ] ) ? (array) $refitune_settings[ $option_key ] : array();
						$required_roles = isset( $feature['required_roles'] ) ? $feature['required_roles'] : array();
						$enable_key     = isset( $feature['enable_key'] ) ? $feature['enable_key'] : null;
						?>

						<?php if ( $enable_key ) : ?>
							<label class="refitune-collapsible-trigger">
								<input
									type="checkbox"
									id="refitune_<?php echo esc_attr( $enable_key ); ?>"
									class="refitune-collapsible-checkbox"
									name="refitune_settings[<?php echo esc_attr( $enable_key ); ?>]"
									value="1"
									<?php checked( ! empty( $refitune_settings[ $enable_key ] ) ); ?>
								/>
								<strong><?php echo esc_html( $feature['description'] ); ?></strong>
							</label>
							<div class="refitune-collapsible-content">
								<div class="refitune-role-list">
									<?php foreach ( $all_roles as $role_slug => $role_name ) : ?>
										<?php
										$is_required = in_array( $role_slug, $required_roles, true );
										$is_checked  = $is_required || in_array( $role_slug, $selected_roles, true );
										?>
										<label class="refitune-role-label">
											<input
												type="checkbox"
												name="refitune_settings[<?php echo esc_attr( $option_key ); ?>][]"
												value="<?php echo esc_attr( $role_slug ); ?>"
												<?php checked( $is_checked ); ?>
												<?php disabled( $is_required ); ?>
											/>
											<?php echo esc_html( translate_user_role( $role_name ) ); ?>
											<?php if ( $is_required ) : ?>
												<span class="refitune-role-required"><?php esc_html_e( '(required)', 'refitune' ); ?></span>
											<?php endif; ?>
										</label>
									<?php endforeach; ?>
								</div>
							</div>
						<?php else : ?>
							<p class="description" style="margin: 0 0 8px;"><?php echo esc_html( $feature['description'] ); ?></p>
							<div class="refitune-role-list">
								<?php foreach ( $all_roles as $role_slug => $role_name ) : ?>
									<?php
									$is_required = in_array( $role_slug, $required_roles, true );
									$is_checked  = $is_required || in_array( $role_slug, $selected_roles, true );
									?>
									<label class="refitune-role-label">
										<input
											type="checkbox"
											name="refitune_settings[<?php echo esc_attr( $option_key ); ?>][]"
											value="<?php echo esc_attr( $role_slug ); ?>"
											<?php checked( $is_checked ); ?>
											<?php disabled( $is_required ); ?>
										/>
										<?php echo esc_html( translate_user_role( $role_name ) ); ?>
										<?php if ( $is_required ) : ?>
											<span class="refitune-role-required"><?php esc_html_e( '(required)', 'refitune' ); ?></span>
										<?php endif; ?>
									</label>
								<?php endforeach; ?>
					</div>
				<?php endif; ?>

		<?php elseif ( 'maintenance_mode' === $type ) : ?>
			<?php
			$option_key     = $feature['option_key'];
			$selected_roles = isset( $refitune_settings[ $option_key ] ) ? (array) $refitune_settings[ $option_key ] : array();
			$required_roles = isset( $feature['required_roles'] ) ? $feature['required_roles'] : array();
			$enable_key     = $feature['enable_key'];
			$message_key    = $feature['message_key'];
			$message_value  = isset( $refitune_settings[ $message_key ] ) ? $refitune_settings[ $message_key ] : '';
			?>

			<label class="refitune-collapsible-trigger">
				<input
					type="checkbox"
					id="refitune_<?php echo esc_attr( $enable_key ); ?>"
					class="refitune-collapsible-checkbox"
					name="refitune_settings[<?php echo esc_attr( $enable_key ); ?>]"
					value="1"
					<?php checked( ! empty( $refitune_settings[ $enable_key ] ) ); ?>
				/>
				<strong><?php echo esc_html( $feature['description'] ); ?></strong>
			</label>

			<div class="refitune-collapsible-content">
				<div class="refitune-maintenance-wrapper">
					
					<!-- Szerepkör lista -->
					<div class="refitune-role-list">
						<?php foreach ( $all_roles as $role_slug => $role_name ) : ?>
							<?php
							$is_required = in_array( $role_slug, $required_roles, true );
							$is_checked  = $is_required || in_array( $role_slug, $selected_roles, true );
							?>
							<label class="refitune-role-label">
								<input
									type="checkbox"
									name="refitune_settings[<?php echo esc_attr( $option_key ); ?>][]"
									value="<?php echo esc_attr( $role_slug ); ?>"
									<?php checked( $is_checked ); ?>
									<?php disabled( $is_required ); ?>
								/>
								<?php echo esc_html( translate_user_role( $role_name ) ); ?>
								<?php if ( $is_required ) : ?>
									<span class="refitune-role-required"><?php esc_html_e( '(required)', 'refitune' ); ?></span>
								<?php endif; ?>
							</label>
						<?php endforeach; ?>
					</div>

					<!-- Üzenet mező -->
					<div class="refitune-maintenance-message">
						<label for="refitune_<?php echo esc_attr( $message_key ); ?>">
							<strong><?php esc_html_e( 'Visitor Message:', 'refitune' ); ?></strong>
						</label>
						<textarea
							id="refitune_<?php echo esc_attr( $message_key ); ?>"
							name="refitune_settings[<?php echo esc_attr( $message_key ); ?>]"
							rows="4"
							class="large-text refitune-maintenance-textarea"
							placeholder="<?php esc_attr_e( 'This site is temporarily under maintenance. Please check back soon!', 'refitune' ); ?>"
						><?php echo esc_textarea( $message_value ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'This message will be displayed to visitors when maintenance mode is active. Leave empty for default message.', 'refitune' ); ?>
						</p>
					</div>

				</div>
			</div>

		<?php elseif ( 'login_limit' === $type ) : ?>

				<label class="refitune-collapsible-trigger">
					<input
						type="checkbox"
						id="refitune_login_limit_enabled"
						class="refitune-collapsible-checkbox"
						name="refitune_settings[login_limit_enabled]"
						value="1"
						<?php checked( ! empty( $refitune_settings['login_limit_enabled'] ) ); ?>
					/>
					<strong><?php echo esc_html( $feature['description'] ); ?></strong>
				</label>

				<div class="refitune-collapsible-content">
					<div class="refitune-login-limit-wrapper">
					<table class="form-table refitune-login-limit-table">
							<tr>
								<th scope="row" colspan="2">
									<label style="display: flex; align-items: center; gap: 8px;">
										<input
											type="checkbox"
											name="refitune_settings[login_limit_block_admin_username]"
											value="1"
											<?php checked( ! empty( $refitune_settings['login_limit_block_admin_username'] ) ); ?>
										/>
										<span><?php esc_html_e( 'Block "admin" Username Instantly', 'refitune' ); ?></span>
									</label>
									<p class="description" style="margin: 8px 0 0 28px;">
										<?php esc_html_e( 'Immediately blocks the IP address for 1 hour on the first login attempt with username "admin". Recommended for extra security.', 'refitune' ); ?>
									</p>
								</th>
							</tr>
							<tr>
								<th scope="row">
									<label for="refitune_login_limit_max_attempts">
										<?php esc_html_e( 'Maximum Attempts', 'refitune' ); ?>
									</label>
								</th>
								<td>
									<input
										type="number"
										id="refitune_login_limit_max_attempts"
										name="refitune_settings[login_limit_max_attempts]"
										value="<?php echo esc_attr( $refitune_settings['login_limit_max_attempts'] ?? 5 ); ?>"
										class="small-text"
										min="1"
										max="100"
									/>
									<p class="description">
										<?php esc_html_e( 'How many failed login attempts allowed per IP address and username. Default: 5', 'refitune' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="refitune_login_limit_lockout_duration">
										<?php esc_html_e( 'Lockout Duration (minutes)', 'refitune' ); ?>
									</label>
								</th>
								<td>
									<input
										type="number"
										id="refitune_login_limit_lockout_duration"
										name="refitune_settings[login_limit_lockout_duration]"
										value="<?php echo esc_attr( $refitune_settings['login_limit_lockout_duration'] ?? 15 ); ?>"
										class="small-text"
										min="1"
										max="1440"
									/>
									<p class="description">
										<?php esc_html_e( 'How long the user should be locked out after reaching the limit (in minutes). Default: 15 minutes', 'refitune' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="refitune_login_limit_whitelist_ips">
										<?php esc_html_e( 'Whitelist IP Addresses', 'refitune' ); ?>
									</label>
								</th>
								<td>
									<textarea
										id="refitune_login_limit_whitelist_ips"
										name="refitune_settings[login_limit_whitelist_ips]"
										rows="5"
										class="large-text code"
										placeholder="<?php esc_attr_e( '192.168.1.1', 'refitune' ); ?>"
									><?php echo esc_textarea( $refitune_settings['login_limit_whitelist_ips'] ?? '' ); ?></textarea>
									<p class="description">
										<?php esc_html_e( 'IP addresses exempt from the limit (one IP per line). For example, if you have a static IP.', 'refitune' ); ?>
									</p>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<?php elseif ( isset( $feature['sub_options'] ) ) : ?>

						<div class="refitune-feature-group">
							<label class="refitune-feature-group-all">
								<input
									type="checkbox"
									id="refitune_<?php echo esc_attr( $key ); ?>_all"
									class="refitune-group-all"
									data-group="<?php echo esc_attr( $key ); ?>"
								/>
								<strong><?php esc_html_e( 'Disable All', 'refitune' ); ?></strong>
							</label>

							<div class="refitune-sub-options">
								<?php foreach ( $feature['sub_options'] as $sub_key => $sub_label ) : ?>
									<label class="refitune-sub-option-label">
										<input
											type="checkbox"
											name="refitune_settings[<?php echo esc_attr( $sub_key ); ?>]"
											value="1"
											class="refitune-group-item"
											data-group="<?php echo esc_attr( $key ); ?>"
											<?php checked( ! empty( $refitune_settings[ $sub_key ] ) ); ?>
										/>
										<?php echo esc_html( $sub_label ); ?>
									</label>
								<?php endforeach; ?>
							</div>
						</div>

					<?php else : ?>

						<?php
						$feature_available = refitune_is_feature_available( $feature );
						?>
						<label for="refitune_<?php echo esc_attr( $key ); ?>">
							<input
								type="checkbox"
								id="refitune_<?php echo esc_attr( $key ); ?>"
								name="refitune_settings[<?php echo esc_attr( $key ); ?>]"
								value="1"
								<?php checked( $feature_available && ! empty( $refitune_settings[ $key ] ) ); ?>
								<?php disabled( ! $feature_available ); ?>
							/>
							<?php echo esc_html( $feature['description'] ); ?>
							<?php if ( ! $feature_available && ! empty( $feature['unavailable_notice'] ) ) : ?>
								<span class="refitune-feature-unavailable-notice"><?php echo esc_html( $feature['unavailable_notice'] ); ?></span>
							<?php endif; ?>
						</label>

					<?php endif; ?>

			</td>
		</tr>
	<?php endforeach; ?>

	</table>

<?php endforeach; ?>

<h2 class="refitune-category-title"><?php esc_html_e( 'Plugin Settings', 'refitune' ); ?></h2>

<table class="form-table" role="presentation">
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Uninstall', 'refitune' ); ?>
		</th>
		<td>
		<label for="refitune_delete_data_on_uninstall" class="danger-label">
			<input
				type="checkbox"
				id="refitune_delete_data_on_uninstall"
				name="refitune_settings[delete_data_on_uninstall]"
				value="1"
				<?php checked( ! empty( $refitune_settings['delete_data_on_uninstall'] ) ); ?>
			/>
			<?php esc_html_e( 'Delete plugin settings and data when uninstalling the plugin.', 'refitune' ); ?>
		</label>
		</td>
	</tr>
</table>

	<p class="submit">
		<button type="submit" class="refitune-button"><?php esc_html_e( 'Save Changes', 'refitune' ); ?></button>
	</p>
</form>
