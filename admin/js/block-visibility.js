/**
 * RefiTune - Block Visibility.
 *
 * Shared "Visibility" panel for device and/or role modules, plus the same
 * controls inside the core "Hide block" modal (Ctrl/Cmd+Shift+H).
 */

( function ( wp ) {
	'use strict';

	var addFilter                  = wp.hooks.addFilter;
	var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
	var InspectorControls          = wp.blockEditor.InspectorControls;
	var PanelBody                  = wp.components.PanelBody;
	var SelectControl              = wp.components.SelectControl;
	var CheckboxControl            = wp.components.CheckboxControl;
	var Fragment                   = wp.element.Fragment;
	var createElement              = wp.element.createElement;
	var useSelect                  = wp.data.useSelect;
	var useDispatch                = wp.data.useDispatch;
	var createRoot                 = wp.element.createRoot;
	var __                         = wp.i18n.__;

	var config = window.refituneBlockVisibility || {};
	var deviceEnabled = !! config.deviceEnabled;
	var rolesEnabled  = !! config.rolesEnabled;
	var availableRoles = Array.isArray( config.roles ) ? config.roles : [];

	if ( ! deviceEnabled && ! rolesEnabled ) {
		return;
	}

	var DEVICE_OPTIONS = [
		{
			label: __( 'Always visible', 'refitune' ),
			value: '',
		},
		{
			label: __( 'Mobile only', 'refitune' ),
			value: 'mobile',
		},
		{
			label: __( 'Desktop only', 'refitune' ),
			value: 'desktop',
		},
	];

	var ROLE_MODE_OPTIONS = [
		{
			label: __( 'Always visible', 'refitune' ),
			value: '',
		},
		{
			label: __( 'Guests only', 'refitune' ),
			value: 'guest',
		},
		{
			label: __( 'Logged-in users', 'refitune' ),
			value: 'logged_in',
		},
		{
			label: __( 'Selected roles', 'refitune' ),
			value: 'roles',
		},
	];

	var DEVICE_HELP = __(
		'The block HTML is omitted entirely on devices where it should not appear.',
		'refitune'
	);

	var ROLE_HELP = __(
		'The block HTML is omitted entirely for visitors who do not match the selected login or role condition.',
		'refitune'
	);

	var MIXED_HELP = __(
		'Selected blocks have different visibility settings. Choosing an option applies it to all selected blocks.',
		'refitune'
	);

	/**
	 * Normalize role visibility attribute.
	 *
	 * @param {*} value Raw attribute.
	 * @return {{mode: string, roles: string[]}} Normalized value.
	 */
	function normalizeRoleVisibility( value ) {
		var mode  = '';
		var roles = [];

		if ( value && typeof value === 'object' ) {
			mode = typeof value.mode === 'string' ? value.mode : '';
			if ( Array.isArray( value.roles ) ) {
				roles = value.roles.map( String ).filter( Boolean );
			}
		}

		return {
			mode: mode,
			roles: roles,
		};
	}

	/**
	 * Stable string key for comparing role visibility across blocks.
	 *
	 * @param {*} value Raw attribute.
	 * @return {string} Comparison key.
	 */
	function roleVisibilityKey( value ) {
		var normalized = normalizeRoleVisibility( value );
		var roles = normalized.roles.slice().sort();
		return normalized.mode + '|' + roles.join( ',' );
	}

	/**
	 * Device visibility select control.
	 *
	 * @param {Object}   props
	 * @param {string}   props.value
	 * @param {Function} props.onChange
	 * @param {string}   [props.help]
	 * @return {Object} Element.
	 */
	function DeviceSelectControl( props ) {
		return createElement( SelectControl, {
			label: __( 'Display by device', 'refitune' ),
			value: props.value || '',
			options: DEVICE_OPTIONS,
			onChange: props.onChange,
			help: props.help || DEVICE_HELP,
			__nextHasNoMarginBottom: true,
			__next40pxDefaultSize: true,
		} );
	}

	/**
	 * Role visibility mode + optional role checkboxes.
	 *
	 * @param {Object}   props
	 * @param {Object}   props.value
	 * @param {Function} props.onChange
	 * @param {string}   [props.help]
	 * @return {Object} Element.
	 */
	function RoleVisibilityControl( props ) {
		var value = normalizeRoleVisibility( props.value );
		var help  = props.help || ROLE_HELP;
		var children = [
			createElement( SelectControl, {
				key: 'mode',
				label: __( 'Display by role', 'refitune' ),
				value: value.mode || '',
				options: ROLE_MODE_OPTIONS,
				onChange: function ( mode ) {
					props.onChange( {
						mode: mode || '',
						roles: value.roles,
					} );
				},
				help: help,
				__nextHasNoMarginBottom: true,
				__next40pxDefaultSize: true,
			} ),
		];

		if ( 'roles' === value.mode && availableRoles.length ) {
			var roleChecks = availableRoles.map( function ( role ) {
				var checked = value.roles.indexOf( role.value ) !== -1;
				return createElement( CheckboxControl, {
					key: role.value,
					label: role.label,
					checked: checked,
					onChange: function ( isChecked ) {
						var nextRoles;
						if ( isChecked ) {
							nextRoles = value.roles.concat( [ role.value ] );
						} else {
							nextRoles = value.roles.filter( function ( slug ) {
								return slug !== role.value;
							} );
						}
						props.onChange( {
							mode: 'roles',
							roles: nextRoles,
						} );
					},
					__nextHasNoMarginBottom: true,
				} );
			} );

			children.push(
				createElement(
					'div',
					{
						key: 'roles',
						className: 'refitune-block-visibility-roles',
					},
					createElement(
						'p',
						{ className: 'refitune-block-visibility-roles__label' },
						__( 'Visible to roles', 'refitune' )
					),
					roleChecks
				)
			);
		}

		return createElement( Fragment, null, children );
	}

	/**
	 * Panel / modal body contents for enabled visibility features.
	 *
	 * @param {Object}   props
	 * @param {string}   props.deviceValue
	 * @param {Function} props.onDeviceChange
	 * @param {string}   [props.deviceHelp]
	 * @param {Object}   props.roleValue
	 * @param {Function} props.onRoleChange
	 * @param {string}   [props.roleHelp]
	 * @return {Object} Element.
	 */
	function VisibilityControls( props ) {
		var children = [];

		if ( deviceEnabled ) {
			children.push(
				createElement( DeviceSelectControl, {
					key: 'device',
					value: props.deviceValue,
					onChange: props.onDeviceChange,
					help: props.deviceHelp,
				} )
			);
		}

		if ( rolesEnabled ) {
			children.push(
				createElement( RoleVisibilityControl, {
					key: 'roles',
					value: props.roleValue,
					onChange: props.onRoleChange,
					help: props.roleHelp,
				} )
			);
		}

		return createElement( Fragment, null, children );
	}

	/**
	 * Register attributes for enabled modules.
	 */
	addFilter(
		'blocks.registerBlockType',
		'refitune/block-visibility-attribute',
		function ( settings ) {
			var nextAttributes = {};

			if ( deviceEnabled ) {
				nextAttributes.refituneVisibility = {
					type: 'string',
					default: '',
				};
			}

			if ( rolesEnabled ) {
				nextAttributes.refituneRoleVisibility = {
					type: 'object',
					default: {
						mode: '',
						roles: [],
					},
				};
			}

			settings.attributes = Object.assign( {}, settings.attributes, nextAttributes );
			return settings;
		}
	);

	/**
	 * Inspector Visibility panel.
	 */
	var withVisibilityControl = createHigherOrderComponent( function ( BlockEdit ) {
		return function ( props ) {
			var attributes    = props.attributes;
			var setAttributes = props.setAttributes;

			return createElement(
				Fragment,
				null,
				createElement( BlockEdit, props ),
				createElement(
					InspectorControls,
					null,
					createElement(
						PanelBody,
						{
							title: __( 'Visibility', 'refitune' ),
							initialOpen: false,
						},
						createElement( VisibilityControls, {
							deviceValue: attributes.refituneVisibility || '',
							onDeviceChange: function ( value ) {
								setAttributes( { refituneVisibility: value } );
							},
							roleValue: attributes.refituneRoleVisibility,
							onRoleChange: function ( value ) {
								setAttributes( {
									refituneRoleVisibility: normalizeRoleVisibility( value ),
								} );
							},
						} )
					)
				)
			);
		};
	}, 'withVisibilityControl' );

	addFilter(
		'editor.BlockEdit',
		'refitune/block-visibility-control',
		withVisibilityControl
	);

	/**
	 * Controls mounted inside the core Hide block modal.
	 *
	 * @return {Object} Element.
	 */
	function ModalVisibilityControls() {
		var clientIds = useSelect( function ( select ) {
			return select( 'core/block-editor' ).getSelectedBlockClientIds();
		}, [] );

		var blocks = useSelect(
			function ( select ) {
				if ( ! clientIds || ! clientIds.length ) {
					return [];
				}
				return select( 'core/block-editor' ).getBlocksByClientId( clientIds ).filter( Boolean );
			},
			[ clientIds ]
		);

		var updateBlockAttributes = useDispatch( 'core/block-editor' ).updateBlockAttributes;

		var sharedDevice = '';
		var deviceMixed  = false;
		var sharedRole   = { mode: '', roles: [] };
		var roleMixed    = false;

		if ( blocks.length > 0 ) {
			sharedDevice = blocks[ 0 ].attributes.refituneVisibility || '';
			sharedRole   = normalizeRoleVisibility( blocks[ 0 ].attributes.refituneRoleVisibility );

			for ( var i = 1; i < blocks.length; i++ ) {
				if ( ( blocks[ i ].attributes.refituneVisibility || '' ) !== sharedDevice ) {
					deviceMixed = true;
				}
				if ( roleVisibilityKey( blocks[ i ].attributes.refituneRoleVisibility ) !== roleVisibilityKey( sharedRole ) ) {
					roleMixed = true;
				}
			}
		}

		return createElement(
			'div',
			{ className: 'refitune-block-visibility-modal' },
			createElement(
				'h3',
				{ className: 'refitune-block-visibility-modal__title' },
				__( 'Visibility', 'refitune' )
			),
			createElement( VisibilityControls, {
				deviceValue: deviceMixed ? '' : sharedDevice,
				deviceHelp: deviceMixed ? MIXED_HELP : DEVICE_HELP,
				onDeviceChange: function ( value ) {
					if ( ! clientIds || ! clientIds.length ) {
						return;
					}
					updateBlockAttributes( clientIds, {
						refituneVisibility: value,
					} );
				},
				roleValue: roleMixed
					? {
						mode: '',
						roles: [],
					}
					: sharedRole,
				roleHelp: roleMixed ? MIXED_HELP : ROLE_HELP,
				onRoleChange: function ( value ) {
					if ( ! clientIds || ! clientIds.length ) {
						return;
					}
					updateBlockAttributes( clientIds, {
						refituneRoleVisibility: normalizeRoleVisibility( value ),
					} );
				},
			} )
		);
	}

	/**
	 * Mount / unmount RefiTune controls inside the core visibility modal.
	 */
	( function setupCoreVisibilityModalIntegration() {
		var activeRoot      = null;
		var activeMountNode = null;
		var syncTimer       = null;

		function unmountControls() {
			if ( activeRoot ) {
				try {
					activeRoot.unmount();
				} catch ( err ) {
					// Ignore unmount errors after React already removed the node.
				}
				activeRoot = null;
			}
			if ( activeMountNode && activeMountNode.parentNode ) {
				activeMountNode.parentNode.removeChild( activeMountNode );
			}
			activeMountNode = null;
		}

		function mountControls( modal ) {
			if ( ! modal || ! createRoot ) {
				return;
			}

			if (
				activeMountNode &&
				modal.contains( activeMountNode ) &&
				activeRoot
			) {
				return;
			}

			if ( modal.querySelector( '.refitune-block-visibility-modal-mount' ) ) {
				return;
			}

			var form    = modal.querySelector( 'form' );
			var actions = modal.querySelector( '.block-editor-block-visibility-modal__actions' );
			var mount   = document.createElement( 'div' );
			mount.className = 'refitune-block-visibility-modal-mount';

			if ( actions && actions.parentNode ) {
				actions.parentNode.insertBefore( mount, actions );
			} else if ( form ) {
				form.appendChild( mount );
			} else {
				modal.appendChild( mount );
			}

			activeMountNode = mount;
			activeRoot      = createRoot( mount );
			activeRoot.render( createElement( ModalVisibilityControls ) );
		}

		function syncModal() {
			var modal = document.querySelector( '.block-editor-block-visibility-modal' );

			if ( ! modal ) {
				unmountControls();
				return;
			}

			// Core React re-renders can detach our injected node; remount when that happens.
			if ( activeMountNode && ! modal.contains( activeMountNode ) ) {
				if ( activeRoot ) {
					try {
						activeRoot.unmount();
					} catch ( err ) {
						// Node already gone.
					}
					activeRoot = null;
				}
				activeMountNode = null;
			}

			mountControls( modal );
		}

		function scheduleSync() {
			if ( null !== syncTimer ) {
				window.clearTimeout( syncTimer );
			}
			syncTimer = window.setTimeout( function () {
				syncTimer = null;
				syncModal();
			}, 50 );
		}

		if ( typeof MutationObserver === 'undefined' ) {
			return;
		}

		var observer = new MutationObserver( scheduleSync );

		observer.observe( document.body, {
			childList: true,
			subtree: true,
		} );
	} )();
} )( window.wp );
