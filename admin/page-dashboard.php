<?php
/**
 * Dashboard oldal tartalma – aktív/inaktív funkciók áttekintése.
 *
 * @package WP_Refiner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wprefi_settings = get_option( 'wprefi_settings', array() );
$features        = wprefi_get_features();
$active_count    = 0;

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

foreach ( $features as $key => $feature ) {
	$type = isset( $feature['type'] ) ? $feature['type'] : '';

	if ( 'login_customizer' === $type ) {
		$login_custom_keys = array( 'login_logo_source', 'login_logo_custom_url', 'login_logo_width', 'login_logo_height', 'login_bg_color', 'login_primary_color' );
		foreach ( $login_custom_keys as $lck ) {
			if ( isset( $wprefi_settings[ $lck ] ) && '' !== $wprefi_settings[ $lck ] ) {
				++$active_count;
				break;
			}
		}
	} elseif ( 'role_redirects' === $type ) {
		$login_redirects  = isset( $wprefi_settings['role_redirects_login'] ) && is_array( $wprefi_settings['role_redirects_login'] ) ? $wprefi_settings['role_redirects_login'] : array();
		$logout_redirects = isset( $wprefi_settings['role_redirects_logout'] ) && is_array( $wprefi_settings['role_redirects_logout'] ) ? $wprefi_settings['role_redirects_logout'] : array();
		if ( ! empty( $login_redirects ) || ! empty( $logout_redirects ) ) {
			++$active_count;
		}
	} elseif ( 'email_smtp' === $type ) {
		$email_mode = isset( $wprefi_settings['email_mode'] ) ? $wprefi_settings['email_mode'] : 'default';
		if ( 'disable_all' === $email_mode || 'smtp' === $email_mode ) {
			++$active_count;
		}
	} elseif ( 'comments_control' === $type ) {
		if ( ! empty( $wprefi_settings['disable_comments'] ) ) {
			++$active_count;
		}
	} elseif ( 'number_input' === $type ) {
		$ni_val = isset( $wprefi_settings[ $feature['option_key'] ] ) ? $wprefi_settings[ $feature['option_key'] ] : '';
		if ( '' !== $ni_val ) {
			++$active_count;
		}
	} elseif ( 'email_controls' === $type ) {
		$email_bool_keys = array( 'email_disable_update', 'email_disable_new_user', 'email_disable_password_reset', 'email_disable_comments', 'email_disable_privacy', 'email_disable_critical' );
		foreach ( $email_bool_keys as $ek ) {
			if ( ! empty( $wprefi_settings[ $ek ] ) ) {
				++$active_count;
				break;
			}
		}
	} elseif ( 'role_select' === $type ) {
		$enable_key = isset( $feature['enable_key'] ) ? $feature['enable_key'] : null;
		if ( $enable_key ) {
			if ( ! empty( $wprefi_settings[ $enable_key ] ) ) {
				++$active_count;
			}
		} else {
			$roles = isset( $wprefi_settings[ $feature['option_key'] ] ) ? (array) $wprefi_settings[ $feature['option_key'] ] : array();
			if ( ! empty( $roles ) ) {
				++$active_count;
			}
		}
	} elseif ( 'login_limit' === $type ) {
		if ( ! empty( $wprefi_settings['login_limit_enabled'] ) ) {
			++$active_count;
		}
	} elseif ( 'maintenance_mode' === $type ) {
		if ( ! empty( $wprefi_settings['maintenance_mode_enabled'] ) ) {
			++$active_count;
		}
	} elseif ( isset( $feature['sub_options'] ) ) {
		foreach ( array_keys( $feature['sub_options'] ) as $sub_key ) {
			if ( ! empty( $wprefi_settings[ $sub_key ] ) ) {
				++$active_count;
				break;
			}
		}
	} elseif ( ! empty( $wprefi_settings[ $key ] ) ) {
		++$active_count;
	}
}
?>
<?php foreach ( $categories as $cat_key => $cat_label ) : ?>
	<?php if ( ! isset( $features_by_category[ $cat_key ] ) ) {
		continue;
	} ?>

	<h2 id="wprefi-dashboard-category-<?php echo esc_attr( $cat_key ); ?>" class="wprefi-category-title">
		<?php echo esc_html( $cat_label ); ?>
	</h2>

	<div class="wprefi-feature-grid">
		<?php foreach ( $features_by_category[ $cat_key ] as $key => $feature ) : ?>
		<?php
		$type        = isset( $feature['type'] ) ? $feature['type'] : '';
		$active      = false;
		$badge_class = 'wprefi-badge-inactive';
		$badge_text  = esc_html__( 'Inactive', 'refinerpress' );

		if ( 'login_customizer' === $type ) {
			$login_custom_keys = array( 'login_logo_source', 'login_logo_custom_url', 'login_logo_width', 'login_logo_height', 'login_bg_color', 'login_primary_color' );
			$custom_count      = 0;
			foreach ( $login_custom_keys as $lck ) {
				if ( isset( $wprefi_settings[ $lck ] ) && '' !== $wprefi_settings[ $lck ] ) {
					++$custom_count;
				}
			}
			$active = $custom_count > 0;
			if ( $active ) {
				$badge_class = 'wprefi-badge-active';
				$badge_text  = esc_html__( 'Customized', 'refinerpress' );
			}
		} elseif ( 'role_redirects' === $type ) {
			$login_redirects  = isset( $wprefi_settings['role_redirects_login'] ) && is_array( $wprefi_settings['role_redirects_login'] ) ? $wprefi_settings['role_redirects_login'] : array();
			$logout_redirects = isset( $wprefi_settings['role_redirects_logout'] ) && is_array( $wprefi_settings['role_redirects_logout'] ) ? $wprefi_settings['role_redirects_logout'] : array();
			$active           = ! empty( $login_redirects ) || ! empty( $logout_redirects );

			if ( $active ) {
				$login_count  = count( $login_redirects );
				$logout_count = count( $logout_redirects );
				$badge_class  = 'wprefi-badge-active';
			$badge_text   = sprintf(
				/* translators: 1: number of login redirects, 2: number of logout redirects */
				esc_html__( '%1$d login / %2$d logout', 'refinerpress' ),
				$login_count,
				$logout_count
			);
			}
	} elseif ( 'email_smtp' === $type ) {
		$email_mode = isset( $wprefi_settings['email_mode'] ) ? $wprefi_settings['email_mode'] : 'default';
		$active     = ( 'disable_all' === $email_mode || 'smtp' === $email_mode );

		if ( $active ) {
		$badge_class = 'wprefi-badge-active';
		$badge_text  = ( 'disable_all' === $email_mode )
			? esc_html__( 'All emails disabled', 'refinerpress' )
			: esc_html__( 'SMTP active', 'refinerpress' );
		}
		} elseif ( 'comments_control' === $type ) {
			$active = ! empty( $wprefi_settings['disable_comments'] );
			if ( $active ) {
			$badge_class = 'wprefi-badge-active';
			$badge_text  = ( class_exists( 'WooCommerce' ) && ! empty( $wprefi_settings['disable_comments_keep_reviews'] ) )
				? esc_html__( 'Active (reviews preserved)', 'refinerpress' )
				: esc_html__( 'Active', 'refinerpress' );
			}
		} elseif ( 'number_input' === $type ) {
			$ni_val = isset( $wprefi_settings[ $feature['option_key'] ] ) ? $wprefi_settings[ $feature['option_key'] ] : '';
			$active = '' !== $ni_val;
			if ( $active ) {
			$badge_class = 'wprefi-badge-active';
			$badge_text  = 0 === $ni_val
				? esc_html__( 'Disabled', 'refinerpress' )
				: sprintf(
					/* translators: %d: maximum number of revisions */
					esc_html__( 'Max %d', 'refinerpress' ),
					(int) $ni_val
				);
			}
		} elseif ( 'email_controls' === $type ) {
			$email_bool_keys  = array( 'email_disable_update', 'email_disable_new_user', 'email_disable_password_reset', 'email_disable_comments', 'email_disable_privacy', 'email_disable_critical' );
			$email_active_cnt = 0;
			foreach ( $email_bool_keys as $ek ) {
				if ( ! empty( $wprefi_settings[ $ek ] ) ) {
					++$email_active_cnt;
				}
			}
			$active = $email_active_cnt > 0;
			if ( $active ) {
				$badge_class = 'wprefi-badge-active';
			$badge_text  = sprintf(
				/* translators: %d: number of disabled email notifications */
				esc_html__( '%d disabled', 'refinerpress' ),
				$email_active_cnt
			);
			}
		} elseif ( 'role_select' === $type ) {
			$enable_key = isset( $feature['enable_key'] ) ? $feature['enable_key'] : null;
			if ( $enable_key ) {
				$active = ! empty( $wprefi_settings[ $enable_key ] );
				if ( $active ) {
					$roles       = isset( $wprefi_settings[ $feature['option_key'] ] ) ? (array) $wprefi_settings[ $feature['option_key'] ] : array();
					$badge_class = 'wprefi-badge-active';
				$badge_text  = sprintf(
					/* translators: %d: number of roles */
					esc_html__( '%d roles', 'refinerpress' ),
					count( $roles )
				);
				}
			} else {
				$roles  = isset( $wprefi_settings[ $feature['option_key'] ] ) ? (array) $wprefi_settings[ $feature['option_key'] ] : array();
				$active = ! empty( $roles );
				if ( $active ) {
					$badge_class = 'wprefi-badge-active';
				$badge_text  = sprintf(
					/* translators: %d: number of roles */
					esc_html__( '%d roles', 'refinerpress' ),
					count( $roles )
				);
			}
		}
	} elseif ( 'login_limit' === $type ) {
		$active = ! empty( $wprefi_settings['login_limit_enabled'] );
		if ( $active ) {
			$max_attempts = isset( $wprefi_settings['login_limit_max_attempts'] ) && $wprefi_settings['login_limit_max_attempts'] > 0
				? (int) $wprefi_settings['login_limit_max_attempts']
				: 5;
			$lockout = isset( $wprefi_settings['login_limit_lockout_duration'] ) && $wprefi_settings['login_limit_lockout_duration'] > 0
				? (int) $wprefi_settings['login_limit_lockout_duration']
				: 15;

			$badge_class = 'wprefi-badge-active';
		$badge_text  = sprintf(
			/* translators: 1: maximum attempts, 2: lockout duration in minutes */
			esc_html__( 'Max %1$d / %2$d min', 'refinerpress' ),
			$max_attempts,
			$lockout
		);
		}
	} elseif ( 'maintenance_mode' === $type ) {
		$active = ! empty( $wprefi_settings['maintenance_mode_enabled'] );
		if ( $active ) {
			$roles       = isset( $wprefi_settings[ $feature['option_key'] ] ) ? (array) $wprefi_settings[ $feature['option_key'] ] : array();
			$badge_class = 'wprefi-badge-active';
			$badge_text  = sprintf(
				/* translators: %d: number of roles */
				esc_html__( '%d roles', 'refinerpress' ),
				count( $roles )
			);
		}
	} elseif ( isset( $feature['sub_options'] ) ) {
			$sub_count  = count( $feature['sub_options'] );
			$sub_active = 0;
			foreach ( array_keys( $feature['sub_options'] ) as $sub_key ) {
				if ( ! empty( $wprefi_settings[ $sub_key ] ) ) {
					++$sub_active;
				}
			}
			$active = $sub_active > 0;
			if ( $active ) {
				$badge_class = 'wprefi-badge-active';
			$badge_text  = sprintf(
				/* translators: 1: number of active sub-options, 2: total number of sub-options */
				esc_html__( '%1$d / %2$d active', 'refinerpress' ),
				$sub_active,
				$sub_count
			);
			}
		} else {
			$active = ! empty( $wprefi_settings[ $key ] );
		if ( $active ) {
			$badge_class = 'wprefi-badge-active';
			$badge_text  = esc_html__( 'Active', 'refinerpress' );
		}
		}

		// Feature card class meghatározása
		$card_class = 'wprefi-feature-card';
		if ( $active ) {
			if ( 'maintenance_mode' === $type ) {
				$card_class .= ' wprefi-feature-warning'; // Piros border maintenance mode esetén
			} else {
				$card_class .= ' wprefi-feature-active'; // Zöld border egyébként
			}
		} else {
			$card_class .= ' wprefi-feature-inactive'; // Szürke border
		}
		?>
			<div class="<?php echo esc_attr( $card_class ); ?>">
				<div class="wprefi-feature-card-header">
					<span class="wprefi-feature-card-label"><?php echo esc_html( $feature['label'] ); ?></span>
					<span class="wprefi-badge <?php echo esc_attr( $badge_class ); ?>"><?php echo $badge_text; // Already escaped above. ?></span>
				</div>
				<p class="wprefi-feature-card-desc"><?php echo esc_html( $feature['description'] ); ?></p>
			</div>
		<?php endforeach; ?>
	</div>

<?php endforeach; ?>

<div class="wprefi-dashboard-footer">
	<a href="<?php echo esc_url( admin_url( 'tools.php?page=wprefi-settings' ) ); ?>" class="wprefi-button">
		<?php esc_html_e( 'Edit Settings', 'refinerpress' ); ?>
	</a>
	<div class="wprefi-dashboard-summary">
		<strong>
			<?php
		printf(
			/* translators: 1: number of active features, 2: total number of features */
			esc_html__( '%1$d / %2$d features active', 'refinerpress' ),
			(int) $active_count,
			count( $features )
		);
			?>
		</strong>
	</div>
</div>
