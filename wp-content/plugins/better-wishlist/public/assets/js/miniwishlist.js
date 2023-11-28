( function ( $ ) {

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

	$( document ).on(
		"click",
		".betterwishlist-remove-from-wishlist",
		function ( e ) {
			e.preventDefault();
			var pageWrap = $(".betterwishlist-page-wrap");
			var table = $(".wishlist_table");
			var productID = $(this).data("product_id");
			var productRow = $("#wishlist-row-" + productID, table);
			//Remove mini wishlist
			var miniWishlistRow = $(".betterwishlist-mini-cart-drop");
			var removeMiniWishlist = $( "#mini-wishlist-row-" + productID, miniWishlistRow );
			var miniDropContent = $( ".mini-drop-content" );

			var count_wishlist = $('#count_wishlist').text();
			var count_remove_wishlist = Number( count_wishlist ) - 1;
			$('#count_wishlist, #count_wishlist').text( count_remove_wishlist );
			var pageWrap = $( ".betterwishlist-page-wrap" );
			var table = $( ".wishlist_table" );
			var productRow = $( "#wishlist-row-" + productID, table );

			$.ajax({
				type: "POST",
				url: BETTER_WISHLIST.ajax_url,
				data: {
					action: BETTER_WISHLIST.actions.remove_from_wishlist,
					security: BETTER_WISHLIST.nonce,
					product_id: productID,
					remove_count: count_remove_wishlist,
				},

				success: function ( response ) {
					if ( response.success ) {
						if( pageWrap.length > 0 ){
							createNotice(response.data);
						}

						//Remove from wishlist table
						productRow.remove();

						//After remove product from mini wishlist change HTML add to wishlist button
						setTimeout( function() {
							var wishlist_icon_controll = response.data.wishlist_icon_controll == 'yes' ? `<span class="${response.data.add_wishlist_icon}"></span>` : '';
							$( '.betterwishlist-added-to-wishlist[data-product-id="'+response.data.product_id+'"]' ).replaceWith(
								`<a data-product-id="${response.data.product_id}" class="button betterwishlist-add-to-wishlist">
								<div id="loader-${response.data.product_id}" class="betterwishlist-spinner betterwishlist-spinner-none betterwishlist-overlay"></div>
								${wishlist_icon_controll}
								${response.data.add_to_wishlist_text}
								</a>`
							);
						}, 500 );

						//Remove form mini wishlist
						removeMiniWishlist.remove();

						if ( $( ".mini-drop-content" ).length < 1 ) {
							$(".betterwishlist-mini-cart-drop + a").remove();
							$(".betterwishlist-mini-cart-drop").html(
								'<div class="no-product-miniwishlist">' +
									response.data.no_product +
									"</div>"
							);
						}

						if( pageWrap.length > 0 ){
							if ( $( "tr.wishlist-row", table ).length < 1 ) {
								pageWrap.empty();
								pageWrap.html(
									'<div class="no-record-message">' +
										BETTER_WISHLIST.i18n.no_records_found +
										"</div>"
								);
							}
						}
					} else {
						createNotice( response.data );
					}
				},
				error: function ( response ) {
					console.log( response );
				},
			});
		}
	);

})(jQuery);