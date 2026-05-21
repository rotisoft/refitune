/**
 * WP Refiner – Block Visibility.
 *
 * Minden Gutenberg blokkhoz hozzáad egy "Láthatóság" panelt
 * az Inspector Controls-ban (mobilon / asztali / mindig látható).
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
	 * 1. wprefiVisibility attribútum hozzáadása minden blokkhoz.
	 */
	addFilter(
		'blocks.registerBlockType',
		'wprefi/block-visibility-attribute',
		function ( settings ) {
			settings.attributes = Object.assign( {}, settings.attributes, {
				wprefiVisibility: {
					type: 'string',
					default: '',
				},
			} );
			return settings;
		}
	);

	/**
	 * 2. InspectorControls panel hozzáadása minden blokk szerkesztőjéhez.
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
							title: __( 'Láthatóság', 'refinerpress' ),
							initialOpen: false,
						},
						createElement( SelectControl, {
							label: __( 'Megjelenítés eszköz szerint', 'refinerpress' ),
							value: attributes.wprefiVisibility || '',
							options: [
								{
									label: __( 'Mindig látható', 'refinerpress' ),
									value: '',
								},
								{
									label: __( 'Csak mobilon', 'refinerpress' ),
									value: 'mobile',
								},
								{
									label: __( 'Csak asztali gépen', 'refinerpress' ),
									value: 'desktop',
								},
							],
							onChange: function ( value ) {
								setAttributes( { wprefiVisibility: value } );
							},
							help: __( 'A blokk teljesen ki lesz zárva a forráskódból a nem megfelelő eszközön.', 'refinerpress' ),
						} )
					)
				)
			);
		};
	}, 'withVisibilityControl' );

	addFilter(
		'editor.BlockEdit',
		'wprefi/block-visibility-control',
		withVisibilityControl
	);
} )( window.wp );
