/**
 * WP Refiner - Admin script.
 *
 * Group "select all" checkboxes, color picker, and resource preload rows.
 */

( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var groupAlls = document.querySelectorAll( '.refitune-group-all' );

		groupAlls.forEach( function ( allCheckbox ) {
			var group = allCheckbox.dataset.group;
			var items = document.querySelectorAll( '.refitune-group-item[data-group="' + group + '"]' );

			/**
			 * Sync the "all" checkbox from individual item states.
			 */
			function updateAllState() {
				var checkedCount = Array.prototype.filter.call( items, function ( cb ) {
					return cb.checked;
				} ).length;

				if ( checkedCount === 0 ) {
					allCheckbox.checked       = false;
					allCheckbox.indeterminate = false;
				} else if ( checkedCount === items.length ) {
					allCheckbox.checked       = true;
					allCheckbox.indeterminate = false;
				} else {
					allCheckbox.checked       = false;
					allCheckbox.indeterminate = true;
				}
			}

			allCheckbox.addEventListener( 'change', function () {
				items.forEach( function ( cb ) {
					cb.checked = allCheckbox.checked;
				} );
			} );

			items.forEach( function ( cb ) {
				cb.addEventListener( 'change', updateAllState );
			} );

			updateAllState();
		} );

		// WordPress Color Picker.
		if ( typeof jQuery !== 'undefined' && jQuery.fn.wpColorPicker ) {
			jQuery( '.refitune-color-picker' ).wpColorPicker();
		}

		setupResourcePreload();
	} );

	/**
	 * Resource Preload: rows, blur URL validation, save button lock.
	 */
	function setupResourcePreload() {
		var wrappers = document.querySelectorAll( '[data-refitune-preload]' );
		var config   = window.refitunePreload || {};
		var homeUrl  = typeof config.homeUrl === 'string' ? config.homeUrl : '';
		var siteUrl  = typeof config.siteUrl === 'string' ? config.siteUrl : '';
		var errorMsgInvalid = typeof config.invalidUrl === 'string'
			? config.invalidUrl
			: 'Invalid URL, please check it.';
		var errorMsgExternal = typeof config.externalUrl === 'string'
			? config.externalUrl
			: 'This URL does not start with your site URL. It must begin with your site URL.';

		if ( ! wrappers.length ) {
			return;
		}

		var form = wrappers[ 0 ].closest( 'form' );
		var submitButtons = form
			? form.querySelectorAll( 'button[type="submit"], input[type="submit"]' )
			: [];

		/**
		 * Error message for a raw preload URL, or empty string when valid.
		 *
		 * @param {string} raw Raw input value.
		 * @return {string} Localized error, or empty when OK.
		 */
		function getPreloadUrlError( raw ) {
			var value = String( raw || '' ).trim();

			if ( '' === value ) {
				return '';
			}

			// One URL only - reject lists separated by whitespace, commas, or semicolons.
			if ( /[\s,;]/.test( value ) ) {
				return errorMsgInvalid;
			}

			if ( ! homeUrl && ! siteUrl ) {
				return errorMsgInvalid;
			}

			if ( homeUrl && 0 === value.indexOf( homeUrl ) ) {
				return '';
			}

			if ( siteUrl && 0 === value.indexOf( siteUrl ) ) {
				return '';
			}

			return errorMsgExternal;
		}

		/**
		 * Whether a raw URL value is allowed (empty OK; otherwise one internal URL).
		 *
		 * @param {string} raw Raw input value.
		 * @return {boolean} True when valid.
		 */
		function isValidPreloadUrl( raw ) {
			return '' === getPreloadUrlError( raw );
		}

		/**
		 * Apply or clear invalid state on a URL field.
		 *
		 * @param {HTMLInputElement} input      URL input.
		 * @param {string}           errorMessage Error text, or empty to clear.
		 */
		function setUrlFieldState( input, errorMessage ) {
			var wrap  = input.closest( '.refitune-preload-url-wrap' );
			var error = wrap
				? wrap.querySelector( '[data-refitune-preload-url-error]' )
				: null;

			if ( errorMessage ) {
				input.classList.add( 'is-invalid' );
				input.setAttribute( 'aria-invalid', 'true' );
				if ( error ) {
					error.textContent = errorMessage;
					error.classList.remove( 'is-hidden' );
				}
			} else {
				input.classList.remove( 'is-invalid' );
				input.removeAttribute( 'aria-invalid' );
				if ( error ) {
					error.textContent = '';
					error.classList.add( 'is-hidden' );
				}
			}
		}

		/**
		 * Validate one URL input and update its UI state.
		 *
		 * @param {HTMLInputElement} input URL input.
		 * @return {boolean} True when valid.
		 */
		function validateUrlInput( input ) {
			var message = getPreloadUrlError( input.value );
			setUrlFieldState( input, message );
			return '' === message;
		}
		/**
		 * Disable Save when any preload URL field is marked invalid.
		 */
		function syncSaveButton() {
			var hasInvalid = !! document.querySelector( '[data-refitune-preload-url].is-invalid' );

			submitButtons.forEach( function ( button ) {
				button.disabled = hasInvalid;
			} );
		}

		wrappers.forEach( function ( wrapper ) {
			var rowsContainer = wrapper.querySelector( '[data-refitune-preload-rows]' );
			var templateEl    = wrapper.querySelector( '[data-refitune-preload-template]' );
			var addButton     = wrapper.querySelector( '[data-refitune-preload-add]' );
			var importFontsButton = wrapper.querySelector( '[data-refitune-preload-import-fonts]' );

			if ( ! rowsContainer || ! templateEl || ! addButton ) {
				return;
			}

			/**
			 * Toggle post ID input for a single row.
			 *
			 * @param {Element} row Row element.
			 */
			function syncPostIdVisibility( row ) {
				var locationSelect = row.querySelector( '[data-refitune-preload-location]' );
				var postIdInput    = row.querySelector( '[data-refitune-preload-post-id]' );

				if ( ! locationSelect || ! postIdInput ) {
					return;
				}

				if ( 'post_id' === locationSelect.value ) {
					postIdInput.classList.remove( 'is-hidden' );
				} else {
					postIdInput.classList.add( 'is-hidden' );
				}
			}

			/**
			 * Reindex name attributes after add/remove.
			 */
			function reindexRows() {
				var rows = rowsContainer.querySelectorAll( '[data-refitune-preload-row]' );

				rows.forEach( function ( row, index ) {
					row.querySelectorAll( '[name]' ).forEach( function ( field ) {
						field.name = field.name.replace(
							/refitune_settings\[resource_preload_items\]\[[^\]]+\]/,
							'refitune_settings[resource_preload_items][' + index + ']'
						);
					} );
				} );
			}

			/**
			 * Apply preload row field values.
			 *
			 * @param {Element} row  Row element.
			 * @param {Object}  data Row values.
			 */
			function fillPreloadRow( row, data ) {
				var urlInput = row.querySelector( '[data-refitune-preload-url]' );
				var locationSelect = row.querySelector( '[data-refitune-preload-location]' );
				var postIdInput = row.querySelector( '[data-refitune-preload-post-id]' );
				var asSelect = row.querySelector( '[data-refitune-preload-as]' );
				var typeSelect = row.querySelector( '[data-refitune-preload-type]' );
				var crossoriginSelect = row.querySelector( '[data-refitune-preload-crossorigin]' );
				var fetchprioritySelect = row.querySelector( '[data-refitune-preload-fetchpriority]' );

				if ( urlInput ) {
					urlInput.value = data.url || '';
					setUrlFieldState( urlInput, '' );
				}
				if ( locationSelect ) {
					locationSelect.value = data.location || 'everywhere';
				}
				if ( postIdInput ) {
					postIdInput.value = data.post_id ? String( data.post_id ) : '';
				}
				if ( asSelect ) {
					asSelect.value = data.as || 'style';
				}
				if ( typeSelect ) {
					typeSelect.value = data.type || '';
				}
				if ( crossoriginSelect ) {
					crossoriginSelect.value = data.crossorigin || '';
				}
				if ( fetchprioritySelect ) {
					fetchprioritySelect.value = data.fetchpriority || '';
				}

				syncPostIdVisibility( row );
			}

			/**
			 * Create a new preload row from the template and append it.
			 *
			 * @param {Object} [data] Optional values to apply.
			 * @return {Element|null} New row element.
			 */
			function appendPreloadRow( data ) {
				var nextIndex = rowsContainer.querySelectorAll( '[data-refitune-preload-row]' ).length;
				var html = templateEl.innerHTML.split( '__INDEX__' ).join( String( nextIndex ) );
				var holder = document.createElement( 'div' );

				holder.innerHTML = html.trim();
				var newRow = holder.firstElementChild;
				if ( ! newRow ) {
					return null;
				}

				rowsContainer.appendChild( newRow );
				if ( data ) {
					fillPreloadRow( newRow, data );
				} else {
					syncPostIdVisibility( newRow );
				}
				reindexRows();
				return newRow;
			}

			/**
			 * Whether a row has an empty URL field.
			 *
			 * @param {Element} row Row element.
			 * @return {boolean} True when URL is empty.
			 */
			function isEmptyUrlRow( row ) {
				var urlInput = row.querySelector( '[data-refitune-preload-url]' );
				return !!( urlInput && '' === String( urlInput.value || '' ).trim() );
			}

			rowsContainer.querySelectorAll( '[data-refitune-preload-row]' ).forEach( function ( row ) {
				syncPostIdVisibility( row );
			} );

			wrapper.addEventListener( 'change', function ( event ) {
				if ( event.target && event.target.matches( '[data-refitune-preload-location]' ) ) {
					syncPostIdVisibility( event.target.closest( '[data-refitune-preload-row]' ) );
				}
			} );

			wrapper.addEventListener( 'focusout', function ( event ) {
				if ( ! event.target || ! event.target.matches( '[data-refitune-preload-url]' ) ) {
					return;
				}
				validateUrlInput( event.target );
				syncSaveButton();
			} );

			wrapper.addEventListener( 'input', function ( event ) {
				if ( ! event.target || ! event.target.matches( '[data-refitune-preload-url]' ) ) {
					return;
				}
				// While typing, clear error as soon as the value becomes valid again.
				if ( event.target.classList.contains( 'is-invalid' ) && isValidPreloadUrl( event.target.value ) ) {
					setUrlFieldState( event.target, '' );
					syncSaveButton();
				}
			} );

			wrapper.addEventListener( 'click', function ( event ) {
				var removeBtn = event.target.closest( '[data-refitune-preload-remove]' );
				if ( ! removeBtn ) {
					return;
				}

				event.preventDefault();
				var row = removeBtn.closest( '[data-refitune-preload-row]' );
				if ( ! row ) {
					return;
				}

				var rows = rowsContainer.querySelectorAll( '[data-refitune-preload-row]' );
				if ( rows.length <= 1 ) {
					row.querySelectorAll( 'input[type="url"], input[type="number"]' ).forEach( function ( input ) {
						input.value = '';
					} );
					var urlInput = row.querySelector( '[data-refitune-preload-url]' );
					if ( urlInput ) {
						setUrlFieldState( urlInput, '' );
					}
					var locationSelect = row.querySelector( '[data-refitune-preload-location]' );
					if ( locationSelect ) {
						locationSelect.value = 'everywhere';
						syncPostIdVisibility( row );
					}
					syncSaveButton();
					return;
				}

				row.parentNode.removeChild( row );
				reindexRows();
				syncSaveButton();
			} );

			addButton.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				appendPreloadRow();
				syncSaveButton();
			} );

			if ( importFontsButton ) {
				importFontsButton.addEventListener( 'click', function ( event ) {
					event.preventDefault();

					if ( importFontsButton.disabled ) {
						return;
					}

					var noFontsMsg = typeof config.noActiveFonts === 'string'
						? config.noActiveFonts
						: 'No active Font Library fonts were found.';
					var alreadyMsg = typeof config.fontsAlreadyAdded === 'string'
						? config.fontsAlreadyAdded
						: 'All active Font Library fonts are already in the list.';
					var errorMsg = typeof config.fontLibraryError === 'string'
						? config.fontLibraryError
						: 'Could not load Font Library fonts.';
					var ajaxUrl = typeof config.ajaxUrl === 'string' ? config.ajaxUrl : '';
					var nonce = typeof config.fontLibraryNonce === 'string' ? config.fontLibraryNonce : '';

					if ( ! ajaxUrl || ! nonce ) {
						window.alert( errorMsg );
						return;
					}

					/**
					 * Append Font Library items that are not already in the list.
					 *
					 * @param {Array} fonts Font preload row objects.
					 */
					function applyFontLibraryItems( fonts ) {
						if ( ! fonts.length ) {
							window.alert( noFontsMsg );
							return;
						}

						var existingUrls = {};
						rowsContainer.querySelectorAll( '[data-refitune-preload-url]' ).forEach( function ( input ) {
							var value = String( input.value || '' ).trim();
							if ( value ) {
								existingUrls[ value ] = true;
							}
						} );

						var added = 0;

						fonts.forEach( function ( item ) {
							if ( ! item || ! item.url || existingUrls[ item.url ] ) {
								return;
							}

							existingUrls[ item.url ] = true;

							var emptyRow = null;
							rowsContainer.querySelectorAll( '[data-refitune-preload-row]' ).forEach( function ( row ) {
								if ( ! emptyRow && isEmptyUrlRow( row ) ) {
									emptyRow = row;
								}
							} );

							if ( emptyRow ) {
								fillPreloadRow( emptyRow, item );
							} else {
								appendPreloadRow( item );
							}
							added += 1;
						} );

						reindexRows();
						syncSaveButton();

						if ( 0 === added ) {
							window.alert( alreadyMsg );
						}
					}

					/**
					 * Show or hide the loading spinner on the import button.
					 *
					 * @param {boolean} isLoading Whether a request is in progress.
					 */
					function setImportFontsLoading( isLoading ) {
						var spinner = importFontsButton.querySelector( '.refitune-preload-import-spin' );

						importFontsButton.disabled = isLoading;
						importFontsButton.setAttribute( 'aria-busy', isLoading ? 'true' : 'false' );

						if ( isLoading ) {
							if ( ! spinner ) {
								spinner = document.createElement( 'span' );
								spinner.className = 'dashicons dashicons-update refitune-preload-import-spin';
								spinner.setAttribute( 'aria-hidden', 'true' );
								importFontsButton.appendChild( spinner );
							}
							return;
						}

						if ( spinner && spinner.parentNode ) {
							spinner.parentNode.removeChild( spinner );
						}
					}

					setImportFontsLoading( true );

					var body = new window.URLSearchParams();
					body.set( 'action', 'refitune_get_font_library_preload_items' );
					body.set( 'nonce', nonce );

					window.fetch( ajaxUrl, {
						method: 'POST',
						credentials: 'same-origin',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
						},
						body: body.toString(),
					} )
						.then( function ( response ) {
							return response.json();
						} )
						.then( function ( payload ) {
							if ( ! payload || ! payload.success || ! payload.data ) {
								window.alert(
									payload && payload.data && payload.data.message
										? payload.data.message
										: errorMsg
								);
								return;
							}

							var fonts = Array.isArray( payload.data.items ) ? payload.data.items : [];
							applyFontLibraryItems( fonts );
						} )
						.catch( function () {
							window.alert( errorMsg );
						} )
						.finally( function () {
							setImportFontsLoading( false );
						} );
				} );
			}
		} );

		if ( form ) {
			form.addEventListener( 'submit', function ( event ) {
				var blocked = false;

				document.querySelectorAll( '[data-refitune-preload-url]' ).forEach( function ( input ) {
					if ( ! validateUrlInput( input ) ) {
						blocked = true;
					}
				} );

				syncSaveButton();

				if ( blocked ) {
					event.preventDefault();
				}
			} );
		}

		syncSaveButton();
	}
}() );
