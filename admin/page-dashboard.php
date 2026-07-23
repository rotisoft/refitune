<?php
/**
 * Dashboard page content - overview of active/inactive features.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$refitune_settings = refitune_get_settings();
$refitune_features        = refitune_get_features();
$refitune_active_count    = 0;

// Category definitions.
$refitune_categories = array(
	'performance' => __( 'Performance', 'refitune' ),
	'security'    => __( 'Security', 'refitune' ),
	'visual'      => __( 'Visual', 'refitune' ),
	'email'       => __( 'Email', 'refitune' ),
	'misc'        => __( 'Miscellaneous', 'refitune' ),
);

// Group features by category.
$refitune_features_by_category = array();
foreach ( $refitune_features as $refitune_key => $refitune_feature ) {
	$refitune_cat = isset( $refitune_feature['category'] ) ? $refitune_feature['category'] : 'misc';
	if ( ! isset( $refitune_features_by_category[ $refitune_cat ] ) ) {
		$refitune_features_by_category[ $refitune_cat ] = array();
	}
	$refitune_features_by_category[ $refitune_cat ][ $refitune_key ] = $refitune_feature;
}

foreach ( $refitune_features as $refitune_key => $refitune_feature ) {
	$refitune_type = isset( $refitune_feature['type'] ) ? $refitune_feature['type'] : '';

	if ( 'login_customizer' === $refitune_type ) {
		$refitune_login_custom_keys = array( 'login_logo_source', 'login_logo_custom_url', 'login_logo_width', 'login_logo_height', 'login_bg_color', 'login_primary_color' );
		foreach ( $refitune_login_custom_keys as $refitune_lck ) {
			if ( isset( $refitune_settings[ $refitune_lck ] ) && '' !== $refitune_settings[ $refitune_lck ] ) {
				++$refitune_active_count;
				break;
			}
		}
	} elseif ( 'role_redirects' === $refitune_type ) {
		$refitune_login_redirects  = isset( $refitune_settings['role_redirects_login'] ) && is_array( $refitune_settings['role_redirects_login'] ) ? $refitune_settings['role_redirects_login'] : array();
		$refitune_logout_redirects = isset( $refitune_settings['role_redirects_logout'] ) && is_array( $refitune_settings['role_redirects_logout'] ) ? $refitune_settings['role_redirects_logout'] : array();
		if ( ! empty( $refitune_login_redirects ) || ! empty( $refitune_logout_redirects ) ) {
			++$refitune_active_count;
		}
	} elseif ( 'email_smtp' === $refitune_type ) {
		$refitune_email_mode = isset( $refitune_settings['email_mode'] ) ? $refitune_settings['email_mode'] : 'default';
		if ( 'disable_all' === $refitune_email_mode || 'smtp' === $refitune_email_mode ) {
			++$refitune_active_count;
		}
	} elseif ( 'comments_control' === $refitune_type ) {
		if ( ! empty( $refitune_settings['disable_comments'] ) ) {
			++$refitune_active_count;
		}
	} elseif ( 'number_input' === $refitune_type ) {
		$refitune_ni_val = isset( $refitune_settings[ $refitune_feature['option_key'] ] ) ? $refitune_settings[ $refitune_feature['option_key'] ] : '';
		if ( '' !== $refitune_ni_val ) {
			++$refitune_active_count;
		}
	} elseif ( 'email_controls' === $refitune_type ) {
		$refitune_email_bool_keys = array( 'email_disable_update', 'email_disable_new_user', 'email_disable_password_reset', 'email_disable_comments', 'email_disable_privacy', 'email_disable_critical' );
		foreach ( $refitune_email_bool_keys as $refitune_ek ) {
			if ( ! empty( $refitune_settings[ $refitune_ek ] ) ) {
				++$refitune_active_count;
				break;
			}
		}
	} elseif ( 'role_select' === $refitune_type ) {
		$refitune_enable_key = isset( $refitune_feature['enable_key'] ) ? $refitune_feature['enable_key'] : null;
		if ( $refitune_enable_key ) {
			if ( ! empty( $refitune_settings[ $refitune_enable_key ] ) ) {
				++$refitune_active_count;
			}
		} else {
			$refitune_roles = isset( $refitune_settings[ $refitune_feature['option_key'] ] ) ? (array) $refitune_settings[ $refitune_feature['option_key'] ] : array();
			if ( ! empty( $refitune_roles ) ) {
				++$refitune_active_count;
			}
		}
	} elseif ( 'auto_updates_control' === $refitune_type ) {
		if ( refitune_auto_updates_is_configured( $refitune_settings ) ) {
			++$refitune_active_count;
		}
	} elseif ( 'login_limit' === $refitune_type ) {
		if ( ! empty( $refitune_settings['login_limit_enabled'] ) ) {
			++$refitune_active_count;
		}
	} elseif ( 'maintenance_mode' === $refitune_type ) {
		if ( ! empty( $refitune_settings['maintenance_mode_enabled'] ) ) {
			++$refitune_active_count;
		}
	} elseif ( isset( $refitune_feature['sub_options'] ) ) {
		foreach ( array_keys( $refitune_feature['sub_options'] ) as $refitune_sub_key ) {
			if ( ! empty( $refitune_settings[ $refitune_sub_key ] ) ) {
				++$refitune_active_count;
				break;
			}
		}
	} elseif ( ! empty( $refitune_settings[ $refitune_key ] ) && refitune_is_feature_available( $refitune_feature ) ) {
		++$refitune_active_count;
	}
}
?>
<?php foreach ( $refitune_categories as $refitune_cat_key => $refitune_cat_label ) : ?>
	<?php if ( ! isset( $refitune_features_by_category[ $refitune_cat_key ] ) ) {
		continue;
	} ?>

	<h2 id="refitune-dashboard-category-<?php echo esc_attr( $refitune_cat_key ); ?>" class="refitune-category-title">
		<?php echo esc_html( $refitune_cat_label ); ?>
	</h2>

	<div class="refitune-feature-grid">
		<?php foreach ( $refitune_features_by_category[ $refitune_cat_key ] as $refitune_key => $refitune_feature ) : ?>
		<?php
		$refitune_type              = isset( $refitune_feature['type'] ) ? $refitune_feature['type'] : '';
		$refitune_feature_available = refitune_is_feature_available( $refitune_feature );
		$refitune_active            = false;
		$refitune_badge_class = 'refitune-badge-inactive';
		$refitune_badge_text  = esc_html__( 'Inactive', 'refitune' );

		if ( 'login_customizer' === $refitune_type ) {
			$refitune_login_custom_keys = array( 'login_logo_source', 'login_logo_custom_url', 'login_logo_width', 'login_logo_height', 'login_bg_color', 'login_primary_color' );
			$refitune_custom_count      = 0;
			foreach ( $refitune_login_custom_keys as $refitune_lck ) {
				if ( isset( $refitune_settings[ $refitune_lck ] ) && '' !== $refitune_settings[ $refitune_lck ] ) {
					++$refitune_custom_count;
				}
			}
			$refitune_active = $refitune_custom_count > 0;
			if ( $refitune_active ) {
				$refitune_badge_class = 'refitune-badge-active';
				$refitune_badge_text  = esc_html__( 'Customized', 'refitune' );
			}
		} elseif ( 'role_redirects' === $refitune_type ) {
			$refitune_login_redirects  = isset( $refitune_settings['role_redirects_login'] ) && is_array( $refitune_settings['role_redirects_login'] ) ? $refitune_settings['role_redirects_login'] : array();
			$refitune_logout_redirects = isset( $refitune_settings['role_redirects_logout'] ) && is_array( $refitune_settings['role_redirects_logout'] ) ? $refitune_settings['role_redirects_logout'] : array();
			$refitune_active           = ! empty( $refitune_login_redirects ) || ! empty( $refitune_logout_redirects );

			if ( $refitune_active ) {
				$refitune_login_count  = count( $refitune_login_redirects );
				$refitune_logout_count = count( $refitune_logout_redirects );
				$refitune_badge_class  = 'refitune-badge-active';
			$refitune_badge_text   = sprintf(
				/* translators: 1: number of login redirects, 2: number of logout redirects */
				esc_html__( '%1$d login / %2$d logout', 'refitune' ),
				$refitune_login_count,
				$refitune_logout_count
			);
			}
	} elseif ( 'email_smtp' === $refitune_type ) {
		$refitune_email_mode = isset( $refitune_settings['email_mode'] ) ? $refitune_settings['email_mode'] : 'default';
		$refitune_active     = ( 'disable_all' === $refitune_email_mode || 'smtp' === $refitune_email_mode );

		if ( $refitune_active ) {
		$refitune_badge_class = 'refitune-badge-active';
		$refitune_badge_text  = ( 'disable_all' === $refitune_email_mode )
			? esc_html__( 'All emails disabled', 'refitune' )
			: esc_html__( 'SMTP active', 'refitune' );
		}
		} elseif ( 'comments_control' === $refitune_type ) {
			$refitune_active = ! empty( $refitune_settings['disable_comments'] );
			if ( $refitune_active ) {
			$refitune_badge_class = 'refitune-badge-active';
			$refitune_badge_text  = ( class_exists( 'WooCommerce' ) && ! empty( $refitune_settings['disable_comments_keep_reviews'] ) )
				? esc_html__( 'Active (reviews preserved)', 'refitune' )
				: esc_html__( 'Active', 'refitune' );
			}
		} elseif ( 'number_input' === $refitune_type ) {
			$refitune_ni_val = isset( $refitune_settings[ $refitune_feature['option_key'] ] ) ? $refitune_settings[ $refitune_feature['option_key'] ] : '';
			$refitune_active = '' !== $refitune_ni_val;
			if ( $refitune_active ) {
			$refitune_badge_class = 'refitune-badge-active';
			$refitune_badge_text  = 0 === $refitune_ni_val
				? esc_html__( 'Disabled', 'refitune' )
				: sprintf(
					/* translators: %d: maximum number of revisions */
					esc_html__( 'Max %d', 'refitune' ),
					(int) $refitune_ni_val
				);
			}
		} elseif ( 'email_controls' === $refitune_type ) {
			$refitune_email_bool_keys  = array( 'email_disable_update', 'email_disable_new_user', 'email_disable_password_reset', 'email_disable_comments', 'email_disable_privacy', 'email_disable_critical' );
			$refitune_email_active_cnt = 0;
			foreach ( $refitune_email_bool_keys as $refitune_ek ) {
				if ( ! empty( $refitune_settings[ $refitune_ek ] ) ) {
					++$refitune_email_active_cnt;
				}
			}
			$refitune_active = $refitune_email_active_cnt > 0;
			if ( $refitune_active ) {
				$refitune_badge_class = 'refitune-badge-active';
			$refitune_badge_text  = sprintf(
				/* translators: %d: number of disabled email notifications */
				esc_html__( '%d disabled', 'refitune' ),
				$refitune_email_active_cnt
			);
			}
		} elseif ( 'role_select' === $refitune_type ) {
			$refitune_enable_key = isset( $refitune_feature['enable_key'] ) ? $refitune_feature['enable_key'] : null;
			if ( $refitune_enable_key ) {
				$refitune_active = ! empty( $refitune_settings[ $refitune_enable_key ] );
				if ( $refitune_active ) {
					$refitune_roles       = isset( $refitune_settings[ $refitune_feature['option_key'] ] ) ? (array) $refitune_settings[ $refitune_feature['option_key'] ] : array();
					$refitune_badge_class = 'refitune-badge-active';
				$refitune_badge_text  = sprintf(
					/* translators: %d: number of roles */
					esc_html__( '%d roles', 'refitune' ),
					count( $refitune_roles )
				);
				}
			} else {
				$refitune_roles  = isset( $refitune_settings[ $refitune_feature['option_key'] ] ) ? (array) $refitune_settings[ $refitune_feature['option_key'] ] : array();
				$refitune_active = ! empty( $refitune_roles );
				if ( $refitune_active ) {
					$refitune_badge_class = 'refitune-badge-active';
				$refitune_badge_text  = sprintf(
					/* translators: %d: number of roles */
					esc_html__( '%d roles', 'refitune' ),
					count( $refitune_roles )
				);
			}
		}
	} elseif ( 'auto_updates_control' === $refitune_type ) {
		$refitune_active = refitune_auto_updates_is_configured( $refitune_settings );
		if ( $refitune_active ) {
			$refitune_badge_class = 'refitune-badge-active';
			$refitune_badge_text  = esc_html__( 'Configured', 'refitune' );
		}
	} elseif ( 'login_limit' === $refitune_type ) {
		$refitune_active = ! empty( $refitune_settings['login_limit_enabled'] );
		if ( $refitune_active ) {
			$refitune_max_attempts = isset( $refitune_settings['login_limit_max_attempts'] ) && $refitune_settings['login_limit_max_attempts'] > 0
				? (int) $refitune_settings['login_limit_max_attempts']
				: 5;
			$refitune_lockout = isset( $refitune_settings['login_limit_lockout_duration'] ) && $refitune_settings['login_limit_lockout_duration'] > 0
				? (int) $refitune_settings['login_limit_lockout_duration']
				: 15;

			$refitune_badge_class = 'refitune-badge-active';
		$refitune_badge_text  = sprintf(
			/* translators: 1: maximum attempts, 2: lockout duration in minutes */
			esc_html__( 'Max %1$d / %2$d min', 'refitune' ),
			$refitune_max_attempts,
			$refitune_lockout
		);
		}
	} elseif ( 'maintenance_mode' === $refitune_type ) {
		$refitune_active = ! empty( $refitune_settings['maintenance_mode_enabled'] );
		if ( $refitune_active ) {
			$refitune_roles       = isset( $refitune_settings[ $refitune_feature['option_key'] ] ) ? (array) $refitune_settings[ $refitune_feature['option_key'] ] : array();
			$refitune_badge_class = 'refitune-badge-active';
			$refitune_badge_text  = sprintf(
				/* translators: %d: number of roles */
				esc_html__( '%d roles', 'refitune' ),
				count( $refitune_roles )
			);
		}
	} elseif ( isset( $refitune_feature['sub_options'] ) ) {
			$refitune_sub_count  = count( $refitune_feature['sub_options'] );
			$refitune_sub_active = 0;
			foreach ( array_keys( $refitune_feature['sub_options'] ) as $refitune_sub_key ) {
				if ( ! empty( $refitune_settings[ $refitune_sub_key ] ) ) {
					++$refitune_sub_active;
				}
			}
			$refitune_active = $refitune_sub_active > 0;
			if ( $refitune_active ) {
				$refitune_badge_class = 'refitune-badge-active';
			$refitune_badge_text  = sprintf(
				/* translators: 1: number of active sub-options, 2: total number of sub-options */
				esc_html__( '%1$d / %2$d active', 'refitune' ),
				$refitune_sub_active,
				$refitune_sub_count
			);
			}
		} else {
			$refitune_active = $refitune_feature_available && ! empty( $refitune_settings[ $refitune_key ] );
		if ( $refitune_active ) {
			$refitune_badge_class = 'refitune-badge-active';
			$refitune_badge_text  = esc_html__( 'Active', 'refitune' );
		}
		}

		// Determine the feature card class.
		$refitune_card_class = 'refitune-feature-card';
		if ( $refitune_active ) {
			if ( 'maintenance_mode' === $refitune_type ) {
				$refitune_card_class .= ' refitune-feature-warning'; // Red border for maintenance mode.
			} else {
				$refitune_card_class .= ' refitune-feature-active'; // Green border otherwise.
			}
		} else {
			$refitune_card_class .= ' refitune-feature-inactive'; // Gray border.
		}
		?>
			<div class="<?php echo esc_attr( $refitune_card_class ); ?>">
				<div class="refitune-feature-card-header">
					<span class="refitune-feature-card-label"><?php echo esc_html( $refitune_feature['label'] ); ?></span>
					<span class="refitune-badge <?php echo esc_attr( $refitune_badge_class ); ?>"><?php echo esc_html( $refitune_badge_text ); ?></span>
				</div>
				<p class="refitune-feature-card-desc"><?php echo esc_html( $refitune_feature['description'] ); ?></p>
				<?php if ( ! $refitune_feature_available && ! empty( $refitune_feature['unavailable_notice'] ) ) : ?>
					<p class="refitune-feature-unavailable-notice"><?php echo esc_html( $refitune_feature['unavailable_notice'] ); ?></p>
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
			(int) $refitune_active_count,
			count( $refitune_features )
		);
			?>
		</strong>
	</div>
</div>
