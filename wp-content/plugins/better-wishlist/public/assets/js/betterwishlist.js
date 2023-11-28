(function ($) {
	var checkMark = `<?xml version="1.0" encoding="utf-8"?>
	<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
		viewBox="0 0 352.6 352.6" style="enable-background:new 0 0 352.6 352.6;" xml:space="preserve">
	<style type="text/css">
		.st0{fill:#FFFFFF;}
	</style>
	<g>
		<path class="st0" d="M337.2,23c-15.9-8.6-33.7,8-44.1,17.7c-23.9,23.3-44.1,50.2-66.7,74.7c-25.1,26.9-48.3,53.9-74.1,80.2
			c-14.7,14.7-30.6,30.6-40.4,49c-22-21.4-41-44.7-65.5-63.6C28.8,167.4-0.6,157.6,0,190c1.2,42.2,38.6,87.5,66.1,116.3
			c11.6,12.2,26.9,25.1,44.7,25.7c21.4,1.2,43.5-24.5,56.3-38.6c22.6-24.5,41-52,61.8-77.1c26.9-33,54.5-65.5,80.8-99.1
			C326.2,96.4,378.2,45,337.2,23z M26.9,187.6c-0.6,0-1.2,0-2.4,0.6c-2.4-0.6-4.3-1.2-6.7-2.4l0,0C19.6,184.5,22.7,185.1,26.9,187.6z
			"/>
	</g>
	</svg>
	`;

	// create notification
	function createNotification( cssClass, response ) {
		var uid = Math.random().toString( 36 ).substr( 2, 9 );
		var message = response.product_title
			? `<strong>${response.product_title}</strong> ${response.message}`
			: response.message;
		var template = `<div class="betterwishlist-notification notification-${uid} ${cssClass}">
			${cssClass == "success" ? checkMark : ""}
			<p class="message">${message}</p>
		</div>`;

		// insert
		if (
			document.querySelector( ".betterwishlist-notification-wrap" ) === null
		) {
			document.body.insertAdjacentHTML(
				"beforeend",
				`<div class="betterwishlist-notification-wrap"></div>`
			);
		}

		document
			.querySelector( ".betterwishlist-notification-wrap" )
			.insertAdjacentHTML( "beforeend", template );

		// remove
		setTimeout( () => {
			document.querySelector( ".notification-" + uid ).remove();
		}, 3500 );
	}

	// create notice
	function createNotice( response ) {
		var pageWrap = document.querySelector( ".betterwishlist-page-wrap" );
		var noticeWrap = document.querySelector( ".woocommerce-notices-wrapper" );
		var message = response.product_title
			? `<strong>${response.product_title}</strong> ${response.message}`
			: response.message;
		var template = `<div class="woocommerce-notices-wrapper">
			<div class="woocommerce-message" role="alert">
				${message} <a href="#" data-product_id="${response.product_id}" class="betterwishlist-undo">Undo?</a>
			</div>
		</div>`;

		if ( noticeWrap ) {
			noticeWrap.remove();
		}

		pageWrap.insertAdjacentHTML( "beforebegin", template );
	}

	//Add mini wishlist
	function addMiniWishlist( response ) {
		var pageWrap = document.querySelector(".betterwishlist-mini-cart-drop");

		var template = `<div class="mini-drop-content" id="mini-wishlist-row-${response.id}" data-row-id="${response.id}">
			<a href="${response.url}" target="_blank"><img src="${response.thumbnail_url}"></a>
				<div class="mini-cart-title">
					<div class="betterwishlist-title">
						<a href="${response.url}" target="_blank">${response.title}</a>
							${response.price_html}
					</div>
					<button class="close-button remove betterwishlist-remove-from-wishlist" data-product_id="${response.id}">
						<svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><path d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c-9.4 9.4-9.4 24.6 0 33.9l47 47-47 47c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l47-47 47 47c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-47-47 47-47c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-47 47-47-47c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>
					</button>
				</div>
			</div>
		</div>`;

		$( pageWrap ).append( template );
	}

	function restore_wishlist_row( response ) {
		var response = response.data;

		var wishlist_row = `
		<tr id="wishlist-row-${response.product_id}" data-row-id="${response.product_id}" class="wishlist-row">
            <td class="product-remove">
            	<div>
					<a href="#" data-product_id="${response.product_id}" class="remove betterwishlist-remove-from-wishlist">
					×
					</a>
            	</div>
            </td>
            <td class="product-thumbnail">
            	<a href="${response.product_url}">
					<img class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail"
						src="${response.thumbnail_url}"
						sizes="(max-width: 450px) 100vw, 450px"
						width="450"
						height="450" />
            	</a>
            </td>
            <td class="product-name">
            	<a href="${response.product_url}">${response.product_title}</a>
            </td>
			<td class="stock-status">
				<span class="${response.class_stock}">${response.stock_status}</span>
			</td>
            <td class="product-price">
            	<span>${response.product_price}</span>
            </td>
            <td class="add-to-cart">
				<a class="button betterwishlist-add-to-cart betterwishlist-add-to-cart-single add_to_cart_button"
				data-product_id="${response.product_id}"
				href="#">
				${response.add_to_cart}
				</a>
            </td>
		</tr>`;

		var template = `<div class="mini-drop-content" id="mini-wishlist-row-${response.product_id}" data-row-id="${response.product_id}">
			<a href="${response.product_url}" target="_blank"><img src="${response.thumbnail_url}"></a>
				<div class="mini-cart-title">
					<div class="betterwishlist-title">
						<a href="${response.product_url}" target="_blank">${response.product_title}</a>
							${response.product_price_html}
					</div>
					<button class="close-button remove betterwishlist-remove-from-wishlist" data-product_id="${response.product_id}">
						<svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><path d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c-9.4 9.4-9.4 24.6 0 33.9l47 47-47 47c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l47-47 47 47c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-47-47 47-47c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-47 47-47-47c-9.4-9.4-24.6-9.4-33.9 0z"/></svg>
					</button>
				</div>
			</div>
		</div>`;

		$( ".betterwishlist-mini-cart-drop" ).append( template );

		if( ! ( $( '.wishlist-items-wrapper' ).length ) ) {
			setTimeout(function () {
				$( ".wishlist-items-wrapper" ).append( wishlist_row );
				window.location.reload();
			}, 1000 );
		}else{
			$( ".wishlist-items-wrapper" ).append( wishlist_row );
			window.location.reload();
		}
	}

	$( document ).ready(function () {

		//Wishlist Undo
		$( document ).on(
			"click",
			".betterwishlist-undo",
			function( e ){
				e.preventDefault();

				var pageWrap = $( ".betterwishlist-page-wrap" );
				var table = $( ".wishlist_table" );
				var productID = $( this ).data( "product_id" );
				var productRow = $( "#wishlist-row-" + productID, table );

				$.ajax({
					type: "POST",
					url: BETTER_WISHLIST.ajax_url,
					data: {
						action: BETTER_WISHLIST.actions.restore_wishlist,
						security: BETTER_WISHLIST.nonce,
						product_id: productID,
					},
					success: function ( response ) {
						if ( response.success ) {

							createNotice( response.data );

							setTimeout( function() {
								$( ".woocommerce-notices-wrapper" ).remove();
							}, 500 );

							restore_wishlist_row( response );
						}
						else {
							createNotification( "error", response.data );
						}
					}
				});
			}
		);

		// add to wishlist
		$( document ).on(
			"click",
			".betterwishlist-add-to-wishlist",
			function ( e ) {
				e.preventDefault();

				var productID = $( this ).data( "product-id" );
				var getThis = $( this );

				$.ajax({
					type: "POST",
					url: BETTER_WISHLIST.ajax_url,
					data: {
						action: BETTER_WISHLIST.actions.add_to_wishlist,
						security: BETTER_WISHLIST.nonce,
						product_id: productID,
					},
					beforeSend: function () {
						$( '#loader-'+productID ).removeClass( 'betterwishlist-spinner-none' );
						$( getThis ).attr( "disabled", true );
					},
					success: function ( response ) {
						if ( response.success ) {
							setTimeout( function() {
								var wishlist_icon_controll = response.data.wishlist_icon_controll == 'yes' ? `<span class="${response.data.added_wishlist_icon}"></span>` : '';
								$( '.betterwishlist-add-to-wishlist[data-product-id="'+response.data.product_id+'"]' ).replaceWith(
									`<a data-product-id="${response.data.product_id}" class="button betterwishlist-added-to-wishlist" href="${response.data.view_wishlist_link}">
									${wishlist_icon_controll}
									${response.data.added_to_wishlist_text}
									</a>`
									);
							}, 800 );

							if (
								BETTER_WISHLIST.settings.redirect_to_wishlist ==
								"yes"
							) {
								window.location.replace(
									BETTER_WISHLIST.settings.wishlist_page_url
								);
							} else {
								createNotification( "success", response.data );
							}
						} else {
							createNotification( "error", response.data );
						}
					},
					error: function ( response ) {
						console.log( response );
					},
					complete: function () {
						setTimeout( function() {
							$( '#loader' ).addClass( 'betterwishlist-spinner-none' );
						}, 800 );
					}
				});
			}
		);

		//Add mini wishlist
		$( document ).on(
			"click",
			".betterwishlist-add-to-wishlist",
			function( e ) {
				e.preventDefault();
				var productID = $(this).data("product-id");

				$.ajax({
					type: "POST",
					url: BETTER_WISHLIST.ajax_url,
					data: {
						action: BETTER_WISHLIST.actions.add_to_mini_wishlist,
						security: BETTER_WISHLIST.nonce,
						product_id: productID,
					},
					success: function (response) {
						if( response.success ) {
							setTimeout( function(){
								var count_wishlist = $('#count_wishlist').text();
								var count_add_wishlist = Number(count_wishlist) + 1;
								$('#count_wishlist').text( count_add_wishlist );
								$( ".no-product-miniwishlist" ).replaceWith( addMiniWishlist( response.data ) );
								if ( $( ".btn-wishlist-view" ).length < 1 ) {
									if ( count_wishlist ) {
										$( ".betterwishlist-mini-cart-drop" ).after( `<a href="${response.data.view_wishlist_link}" class="btn-wishlist-view">${response.data.view_wishlist}</a>` );
									}
								}
							}, 800 );
						}
					},
				});
			}
		);

		// add to cart
		$( document ).on(
			"click",
			".betterwishlist-add-to-cart-single",
			function ( e ) {
				e.preventDefault();

				var pageWrap = $( ".betterwishlist-page-wrap" );
				var table = $( ".wishlist_table" );
				var productID = $( this ).data( "product_id" );
				var productRow = $( "#wishlist-row-" + productID, table );

				var count_wishlist = $('#count_wishlist').text();
				var count_remove_wishlist = Number( count_wishlist ) - 1;
				$('#count_wishlist').text( count_remove_wishlist );

				$.ajax({
					type: "POST",
					url: BETTER_WISHLIST.ajax_url,
					data: {
						action: BETTER_WISHLIST.actions.add_to_cart_single,
						security: BETTER_WISHLIST.nonce,
						product_id: productID,
					},
					success: function ( response ) {
						if ( response.success ) {
							if (
								BETTER_WISHLIST.settings.redirect_to_cart ==
								"yes"
							) {
								window.location.replace(
									BETTER_WISHLIST.settings.cart_page_url
								);
							} else {
								createNotification( "success", response.data );

								if (
									BETTER_WISHLIST.settings
										.remove_from_wishlist
								) {
									productRow.remove();

									if (
										$( "tr.wishlist-row", table ).length < 1
									) {
										pageWrap.empty();
										pageWrap.html(
											'<div class="no-record-message">' +
												BETTER_WISHLIST.i18n
												.no_records_found +
											"</div>"
										);
									}
								}
							}
						} else {
							createNotification( "error", response.data );
						}
					},
					error: function ( response ) {
						console.log( response );
					},
				});
			}
		);

		// add to cart - multiple
		$( document ).on(
			"click",
			".betterwishlist-add-to-cart-multiple",
			function ( e ) {
				e.preventDefault();

				var pageWrap = $( ".betterwishlist-page-wrap" );
				var products = $( this ).data( "products" ).toString().split( "," );

				$.ajax({
					type: "POST",
					url: BETTER_WISHLIST.ajax_url,
					data: {
						action: BETTER_WISHLIST.actions.add_to_cart_multiple,
						security: BETTER_WISHLIST.nonce,
						products: products,
					},
					success: function ( response ) {
						if ( response.success ) {
							if (
								BETTER_WISHLIST.settings.redirect_to_cart ==
								"yes"
							) {
								window.location.replace(
									BETTER_WISHLIST.settings.cart_page_url
								);
							} else {
								createNotification( "success", response.data );

								if (
									"yes" ==
									BETTER_WISHLIST.settings
										.remove_from_wishlist
								) {
									pageWrap.empty();
									pageWrap.html(
										'<div class="no-record-message">' +
											BETTER_WISHLIST.i18n
												.no_records_found +
											"</div>"
									);
								}
							}
						} else {
							createNotification( "error", response.data );
						}
					},
					error: function ( response ) {
						console.log( response );
					},
				});
			}
		);

		//Add to wishlist icon
		$( document ).on(
			"click",
			".betterwishlist-add-to-wishlist",
			function ( e ) {
				e.preventDefault();

				var iconWrap = $( this );

				$.ajax({
					type: "POST",
					url: BETTER_WISHLIST.ajax_url,
					data: {
						action: BETTER_WISHLIST.actions.added_wishlist_icon,
						security: BETTER_WISHLIST.nonce,
					},

					success: function (response) {
						if ( response.success ) {
							setTimeout( function() {
								iconWrap.css( 'cssText', 'background-color: red !important' );
								iconWrap.text( response.data.wishlist_btn_text );

								if( response.data.show_wishlist_icon == 'yes' ){
									iconWrap.prepend(
										'<span class="'+response.data.added_wishlist_icon+'"></span>'
									);
								}

							}, 800 );

						}
					}
				});
			}
		);
	});
})( jQuery );