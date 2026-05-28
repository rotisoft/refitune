/**
 * RefiTune – Block Visibility.
 *
 * Adds a "Visibility" panel to every Gutenberg block in the Inspector Controls
 * (mobile / desktop / always visible).
 */

( function ( wp ) {
	'use strict';

	var addFilter                  = wp.hooks.addFilter;
	var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
	var InspectorControls          = wp.blockEditor.InspectorControls;
	var PanelBody                  = wp.components.PanelBody;
	var SelectControl              = wp.components.SelectControl;
	var Fragment                   = wp.element.Fragment;
	var createElement              = wp.element.createElement;
	var __                         = wp.i18n.__;

	/**
	 * 1. Add refituneVisibility attribute to every block.
	 */
	addFilter(
		'blocks.registerBlockType',
		'refitune/block-visibility-attribute',
		function ( settings ) {
			settings.attributes = Object.assign( {}, settings.attributes, {
				refituneVisibility: {
					type: 'string',
					default: '',
				},
			} );
			return settings;
		}
	);

	/**
	 * 2. Add InspectorControls panel to every block editor.
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
						createElement( SelectControl, {
							label: __( 'Display by device', 'refitune' ),
							value: attributes.refituneVisibility || '',
							options: [
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
							],
							onChange: function ( value ) {
								setAttributes( { refituneVisibility: value } );
							},
							help: __(
								'The block HTML is omitted entirely on devices where it should not appear.',
								'refitune'
							),
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
} )( window.wp );
