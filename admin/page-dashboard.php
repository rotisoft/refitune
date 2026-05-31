<?php
/**
 * Dashboard oldal tartalma – aktív/inaktív funkciók áttekintése.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$refitune_settings = get_option( 'refitune_settings', array() );
$features        = refitune_get_features();
$active_count    = 0;

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

foreach ( $features as $key => $feature ) {
	$type = isset( $feature['type'] ) ? $feature['type'] : '';

	if ( 'login_customizer' === $type ) {
		$login_custom_keys = array( 'login_logo_source', 'login_logo_custom_url', 'login_logo_width', 'login_logo_height', 'login_bg_color', 'login_primary_color' );
		foreach ( $login_custom_keys as $lck ) {
			if ( isset( $refitune_settings[ $lck ] ) && '' !== $refitune_settings[ $lck ] ) {
				++$active_count;
				break;
			}
		}
	} elseif ( 'role_redirects' === $type ) {
		$login_redirects  = isset( $refitune_settings['role_redirects_login'] ) && is_array( $refitune_settings['role_redirects_login'] ) ? $refitune_settings['role_redirects_login'] : array();
		$logout_redirects = isset( $refitune_settings['role_redirects_logout'] ) && is_array( $refitune_settings['role_redirects_logout'] ) ? $refitune_settings['role_redirects_logout'] : array();
		if ( ! empty( $login_redirects ) || ! empty( $logout_redirects ) ) {
			++$active_count;
		}
	} elseif ( 'email_smtp' === $type ) {
		$email_mode = isset( $refitune_settings['email_mode'] ) ? $refitune_settings['email_mode'] : 'default';
		if ( 'disable_all' === $email_mode || 'smtp' === $email_mode ) {
			++$active_count;
		}
	} elseif ( 'comments_control' === $type ) {
		if ( ! empty( $refitune_settings['disable_comments'] ) ) {
			++$active_count;
		}
	} elseif ( 'number_input' === $type ) {
		$ni_val = isset( $refitune_settings[ $feature['option_key'] ] ) ? $refitune_settings[ $feature['option_key'] ] : '';
		if ( '' !== $ni_val ) {
			++$active_count;
		}
	} elseif ( 'email_controls' === $type ) {
		$email_bool_keys = array( 'email_disable_update', 'email_disable_new_user', 'email_disable_password_reset', 'email_disable_comments', 'email_disable_privacy', 'email_disable_critical' );
		foreach ( $email_bool_keys as $ek ) {
			if ( ! empty( $refitune_settings[ $ek ] ) ) {
				++$active_count;
				break;
			}
		}
	} elseif ( 'role_select' === $type ) {
		$enable_key = isset( $feature['enable_key'] ) ? $feature['enable_key'] : null;
		if ( $enable_key ) {
			if ( ! empty( $refitune_settings[ $enable_key ] ) ) {
				++$active_count;
			}
		} else {
			$roles = isset( $refitune_settings[ $feature['option_key'] ] ) ? (array) $refitune_settings[ $feature['option_key'] ] : array();
			if ( ! empty( $roles ) ) {
				++$active_count;
			}
		}
	} elseif ( 'auto_updates_control' === $type ) {
		if ( refitune_auto_updates_is_configured( $refitune_settings ) ) {
			++$active_count;
		}
	} elseif ( 'login_limit' === $type ) {
		if ( ! empty( $refitune_settings['login_limit_enabled'] ) ) {
			++$active_count;
		}
	} elseif ( 'maintenance_mode' === $type ) {
		if ( ! empty( $refitune_settings['maintenance_mode_enabled'] ) ) {
			++$active_count;
		}
	} elseif ( isset( $feature['sub_options'] ) ) {
		foreach ( array_keys( $feature['sub_options'] ) as $sub_key ) {
			if ( ! empty( $refitune_settings[ $sub_key ] ) ) {
				++$active_count;
				break;
			}
		}
	} elseif ( ! empty( $refitune_settings[ $key ] ) && refitune_is_feature_available( $feature ) ) {
		++$active_count;
	}
}
?>
<?php foreach ( $categories as $cat_key => $cat_label ) : ?>
	<?php if ( ! isset( $features_by_category[ $cat_key ] ) ) {
		continue;
	} ?>

	<h2 id="refitune-dashboard-category-<?php echo esc_attr( $cat_key ); ?>" class="refitune-category-title">
		<?php echo esc_html( $cat_label ); ?>
	</h2>

	<div class="refitune-feature-grid">
		<?php foreach ( $features_by_category[ $cat_key ] as $key => $feature ) : ?>
		<?php
		$type              = isset( $feature['type'] ) ? $feature['type'] : '';
		$feature_available = refitune_is_feature_available( $feature );
		$active            = false;
		$badge_class = 'refitune-badge-inactive';
		$badge_text  = esc_html__( 'Inactive', 'refitune' );

		if ( 'login_customizer' === $type ) {
			$login_custom_keys = array( 'login_logo_source', 'login_logo_custom_url', 'login_logo_width', 'login_logo_height', 'login_bg_color', 'login_primary_color' );
			$custom_count      = 0;
			foreach ( $login_custom_keys as $lck ) {
				if ( isset( $refitune_settings[ $lck ] ) && '' !== $refitune_settings[ $lck ] ) {
					++$custom_count;
				}
			}
			$active = $custom_count > 0;
			if ( $active ) {
				$badge_class = 'refitune-badge-active';
				$badge_text  = esc_html__( 'Customized', 'refitune' );
			}
		} elseif ( 'role_redirects' === $type ) {
			$login_redirects  = isset( $refitune_settings['role_redirects_login'] ) && is_array( $refitune_settings['role_redirects_login'] ) ? $refitune_settings['role_redirects_login'] : array();
			$logout_redirects = isset( $refitune_settings['role_redirects_logout'] ) && is_array( $refitune_settings['role_redirects_logout'] ) ? $refitune_settings['role_redirects_logout'] : array();
			$active           = ! empty( $login_redirects ) || ! empty( $logout_redirects );

			if ( $active ) {
				$login_count  = count( $login_redirects );
				$logout_count = count( $logout_redirects );
				$badge_class  = 'refitune-badge-active';
			$badge_text   = sprintf(
				/* translators: 1: number of login redirects, 2: number of logout redirects */
				esc_html__( '%1$d login / %2$d logout', 'refitune' ),
				$login_count,
				$logout_count
			);
			}
	} elseif ( 'email_smtp' === $type ) {
		$email_mode = isset( $refitune_settings['email_mode'] ) ? $refitune_settings['email_mode'] : 'default';
		$active     = ( 'disable_all' === $email_mode || 'smtp' === $email_mode );

		if ( $active ) {
		$badge_class = 'refitune-badge-active';
		$badge_text  = ( 'disable_all' === $email_mode )
			? esc_html__( 'All emails disabled', 'refitune' )
			: esc_html__( 'SMTP active', 'refitune' );
		}
		} elseif ( 'comments_control' === $type ) {
			$active = ! empty( $refitune_settings['disable_comments'] );
			if ( $active ) {
			$badge_class = 'refitune-badge-active';
			$badge_text  = ( class_exists( 'WooCommerce' ) && ! empty( $refitune_settings['disable_comments_keep_reviews'] ) )
				? esc_html__( 'Active (reviews preserved)', 'refitune' )
				: esc_html__( 'Active', 'refitune' );
			}
		} elseif ( 'number_input' === $type ) {
			$ni_val = isset( $refitune_settings[ $feature['option_key'] ] ) ? $refitune_settings[ $feature['option_key'] ] : '';
			$active = '' !== $ni_val;
			if ( $active ) {
			$badge_class = 'refitune-badge-active';
			$badge_text  = 0 === $ni_val
				? esc_html__( 'Disabled', 'refitune' )
				: sprintf(
					/* translators: %d: maximum number of revisions */
					esc_html__( 'Max %d', 'refitune' ),
					(int) $ni_val
				);
			}
		} elseif ( 'email_controls' === $type ) {
			$email_bool_keys  = array( 'email_disable_update', 'email_disable_new_user', 'email_disable_password_reset', 'email_disable_comments', 'email_disable_privacy', 'email_disable_critical' );
			$email_active_cnt = 0;
			foreach ( $email_bool_keys as $ek ) {
				if ( ! empty( $refitune_settings[ $ek ] ) ) {
					++$email_active_cnt;
				}
			}
			$active = $email_active_cnt > 0;
			if ( $active ) {
				$badge_class = 'refitune-badge-active';
			$badge_text  = sprintf(
				/* translators: %d: number of disabled email notifications */
				esc_html__( '%d disabled', 'refitune' ),
				$email_active_cnt
			);
			}
		} elseif ( 'role_select' === $type ) {
			$enable_key = isset( $feature['enable_key'] ) ? $feature['enable_key'] : null;
			if ( $enable_key ) {
				$active = ! empty( $refitune_settings[ $enable_key ] );
				if ( $active ) {
					$roles       = isset( $refitune_settings[ $feature['option_key'] ] ) ? (array) $refitune_settings[ $feature['option_key'] ] : array();
					$badge_class = 'refitune-badge-active';
				$badge_text  = sprintf(
					/* translators: %d: number of roles */
					esc_html__( '%d roles', 'refitune' ),
					count( $roles )
				);
				}
			} else {
				$roles  = isset( $refitune_settings[ $feature['option_key'] ] ) ? (array) $refitune_settings[ $feature['option_key'] ] : array();
				$active = ! empty( $roles );
				if ( $active ) {
					$badge_class = 'refitune-badge-active';
				$badge_text  = sprintf(
					/* translators: %d: number of roles */
					esc_html__( '%d roles', 'refitune' ),
					count( $roles )
				);
			}
		}
	} elseif ( 'auto_updates_control' === $type ) {
		$active = refitune_auto_updates_is_configured( $refitune_settings );
		if ( $active ) {
			$badge_class = 'refitune-badge-active';
			$badge_text  = esc_html__( 'Configured', 'refitune' );
		}
	} elseif ( 'login_limit' === $type ) {
		$active = ! empty( $refitune_settings['login_limit_enabled'] );
		if ( $active ) {
			$max_attempts = isset( $refitune_settings['login_limit_max_attempts'] ) && $refitune_settings['login_limit_max_attempts'] > 0
				? (int) $refitune_settings['login_limit_max_attempts']
				: 5;
			$lockout = isset( $refitune_settings['login_limit_lockout_duration'] ) && $refitune_settings['login_limit_lockout_duration'] > 0
				? (int) $refitune_settings['login_limit_lockout_duration']
				: 15;

			$badge_class = 'refitune-badge-active';
		$badge_text  = sprintf(
			/* translators: 1: maximum attempts, 2: lockout duration in minutes */
			esc_html__( 'Max %1$d / %2$d min', 'refitune' ),
			$max_attempts,
			$lockout
		);
		}
	} elseif ( 'maintenance_mode' === $type ) {
		$active = ! empty( $refitune_settings['maintenance_mode_enabled'] );
		if ( $active ) {
			$roles       = isset( $refitune_settings[ $feature['option_key'] ] ) ? (array) $refitune_settings[ $feature['option_key'] ] : array();
			$badge_class = 'refitune-badge-active';
			$badge_text  = sprintf(
				/* translators: %d: number of roles */
				esc_html__( '%d roles', 'refitune' ),
				count( $roles )
			);
		}
	} elseif ( isset( $feature['sub_options'] ) ) {
			$sub_count  = count( $feature['sub_options'] );
			$sub_active = 0;
			foreach ( array_keys( $feature['sub_options'] ) as $sub_key ) {
				if ( ! empty( $refitune_settings[ $sub_key ] ) ) {
					++$sub_active;
				}
			}
			$active = $sub_active > 0;
			if ( $active ) {
				$badge_class = 'refitune-badge-active';
			$badge_text  = sprintf(
				/* translators: 1: number of active sub-options, 2: total number of sub-options */
				esc_html__( '%1$d / %2$d active', 'refitune' ),
				$sub_active,
				$sub_count
			);
			}
		} else {
			$active = $feature_available && ! empty( $refitune_settings[ $key ] );
		if ( $active ) {
			$badge_class = 'refitune-badge-active';
			$badge_text  = esc_html__( 'Active', 'refitune' );
		}
		}

		// Feature card class meghatározása
		$card_class = 'refitune-feature-card';
		if ( $active ) {
			if ( 'maintenance_mode' === $type ) {
				$card_class .= ' refitune-feature-warning'; // Piros border maintenance mode esetén
			} else {
				$card_class .= ' refitune-feature-active'; // Zöld border egyébként
			}
		} else {
			$card_class .= ' refitune-feature-inactive'; // Szürke border
		}
		?>
			<div class="<?php echo esc_attr( $card_class ); ?>">
				<div class="refitune-feature-card-header">
					<span class="refitune-feature-card-label"><?php echo esc_html( $feature['label'] ); ?></span>
					<span class="refitune-badge <?php echo esc_attr( $badge_class ); ?>"><?php echo $badge_text; // Already escaped above. ?></span>
				</div>
				<p class="refitune-feature-card-desc"><?php echo esc_html( $feature['description'] ); ?></p>
				<?php if ( ! $feature_available && ! empty( $feature['unavailable_notice'] ) ) : ?>
					<p class="refitune-feature-unavailable-notice"><?php echo esc_html( $feature['unavailable_notice'] ); ?></p>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>

<?php endforeach; ?>

<div class="refitune-dashboard-footer">
	<a href="<?php echo esc_url( admin_url( 'tools.php?page=refitune-settings' ) ); ?>" class="refitune-button">
		<?php esc_html_e( 'Edit Settings', 'refitune' ); ?>
	</a>
	<div class="refitune-dashboard-summary">
		<strong>
			<?php
		printf(
			/* translators: 1: number of active features, 2: total number of features */
			esc_html__( '%1$d / %2$d features active', 'refitune' ),
			(int) $active_count,
			count( $features )
		);
			?>
		</strong>
	</div>
</div>
