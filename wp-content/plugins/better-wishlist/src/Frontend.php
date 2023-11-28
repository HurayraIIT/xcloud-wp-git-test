<?php
/**
 * Better Wishlist Frontend
 *
 * @since 1.0.0
 * @package better-wishlist
 */

namespace BetterWishlist;

// If this file is called directly,  abort.
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Class Frontend
 */
class Frontend {

	/**
	 * Store settings data from admin settings
	 *
	 * @since 1.0.0
	 * @var array|object $settings
	 */
	protected $settings;

	/**
	 * construct
	 * Init this method when object created __construct
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return;
		}

		$this->settings = wp_parse_args(
			get_option( 'bw_settings' ),
			[
				'position_in_single' => 'after_add_to_cart',
			]
		);

		add_action( 'init', [ $this, 'init' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'woocommerce_account_betterwishlist_endpoint', array( $this, 'menu_content' ) );
		add_action( "woocommerce_{$this->settings['position_in_single']}_button", [ $this, 'single_add_to_wishlist_button' ], 10 );
		add_action( 'woocommerce_loop_add_to_cart_link', [ $this, 'archive_add_to_wishlist_button' ], 10, 3 );

		// ajax

		add_action( 'wp_ajax_added_wishlist_icon', [ $this, 'ajax_wishlist_icon' ] );
		add_action( 'wp_ajax_nopriv_added_wishlist_icon', [ $this, 'ajax_wishlist_icon' ] );

		add_action( 'wp_ajax_add_to_wishlist', [ $this, 'ajax_add_to_wishlist' ] );
		add_action( 'wp_ajax_nopriv_add_to_wishlist', [ $this, 'ajax_add_to_wishlist' ] );

		add_action( 'wp_ajax_add_to_mini_wishlist', [ $this, 'ajax_add_to_mini_wishlist' ] );
		add_action( 'wp_ajax_nopriv_add_to_mini_wishlist', [ $this, 'ajax_add_to_mini_wishlist' ] );

		add_action( 'wp_ajax_restore_wishlist', [ $this, 'ajax_restore_wishlist' ] );
		add_action( 'wp_ajax_nopriv_restore_wishlist', [ $this, 'ajax_restore_wishlist' ] );

		add_action( 'wp_ajax_remove_from_wishlist', [ $this, 'ajax_remove_from_wishlist' ] );
		add_action( 'wp_ajax_nopriv_remove_from_wishlist', [ $this, 'ajax_remove_from_wishlist' ] );

		add_action( 'wp_ajax_add_to_cart_single', [ $this, 'ajax_add_to_cart_single' ] );
		add_action( 'wp_ajax_nopriv_add_to_cart_single', [ $this, 'ajax_add_to_cart_single' ] );

		add_action( 'wp_ajax_add_to_cart_multiple', [ $this, 'ajax_add_to_cart_multiple' ] );
		add_action( 'wp_ajax_nopriv_add_to_cart_multiple', [ $this, 'ajax_add_to_cart_multiple' ] );

		// filter hooks
		add_filter( 'body_class', [ $this, 'add_body_class' ] );
		add_filter( 'woocommerce_account_menu_items', [ $this, 'add_menu' ] );
		add_filter('wp_nav_menu_items', [$this, 'betterwishlist_mini_wishlist'], 10, 2);

		// shortcode
		add_shortcode( 'better_wishlist', [ $this, 'shortcode' ] );
		add_shortcode( 'better_wishlist_mini', [ $this, 'shortcode_miniwishlist' ] );
	}

	/**
	 * init
	 * Initialize wp rewrite rule
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		if ( 'yes' === $this->settings['wishlist_menu'] ) {
			add_rewrite_endpoint( 'betterwishlist', EP_ROOT | EP_PAGES );
		}

		// flush rewrite rules
		if ( get_transient( 'better_wishlist_flush_rewrite_rules' ) === true ) {
			flush_rewrite_rules();
			delete_transient( 'better_wishlist_flush_rewrite_rules' );
		}
	}

	/**
	 * enqueue_scripts
	 * Enqueue All script /css which are responsible for frontend
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		$settings            = get_option( 'bw_settings' );
		$added_wishlist_icon = isset( $settings['added_wishlist_icon'] ) 		? $settings['added_wishlist_icon'] : 'icon-heart-fill';
		$no_records_found    = isset( $settings[ 'no_product_added_text' ] ) 	? html_entity_decode( $settings[ 'no_product_added_text' ] ) : __( 'No Products Added', 'better-wishlist' );

		$localize_scripts = [
			'ajax_url' => admin_url( 'admin-ajax.php', 'relative' ),
			'nonce'    => wp_create_nonce( 'better_wishlist_nonce' ),

			'actions'  => [
				'added_wishlist_icon'  => 'added_wishlist_icon',
				'restore_wishlist'     => 'restore_wishlist',
				'add_to_wishlist'      => 'add_to_wishlist',
				'add_to_mini_wishlist' => 'add_to_mini_wishlist',
				'remove_from_wishlist' => 'remove_from_wishlist',
				'add_to_cart_multiple' => 'add_to_cart_multiple',
				'add_to_cart_single'   => 'add_to_cart_single',
			],
			'settings' => [
				'added_wishlist_icon'  => $added_wishlist_icon,
				'redirect_to_wishlist' => $settings['redirect_to_wishlist'],
				'remove_from_wishlist' => $settings['remove_from_wishlist'],
				'redirect_to_cart'     => $settings['redirect_to_cart'],
				'cart_page_url'        => wc_get_cart_url(),
				'wishlist_page_url'    => esc_url_raw( is_user_logged_in() && 'yes' === $this->settings['wishlist_menu'] ? wc_get_account_endpoint_url( 'betterwishlist' ) : get_the_permalink( $settings['wishlist_page'] ) ),
			],
			'i18n'     => [
				'no_records_found' => $no_records_found,
			],
		];

		//
		$localize_scripts1 = [
			'ajax_url' => admin_url( 'admin-ajax.php', 'relative' ),
			'nonce'    => wp_create_nonce( 'better_wishlist_nonce' ),
			'actions'  => [
				'remove_from_wishlist' => 'remove_from_wishlist',
			]
		];

		// css
		wp_register_style( 'betterwishlist', BETTER_WISHLIST_PLUGIN_URL . 'public/assets/css/betterwishlist.css', null, BETTER_WISHLIST_PLUGIN_VERSION, 'all' );
		wp_enqueue_style( 'miniwishlist', BETTER_WISHLIST_PLUGIN_URL . 'public/assets/css/miniwishlist.css', null, BETTER_WISHLIST_PLUGIN_VERSION, 'all' );
		// js
		wp_register_script( 'betterwishlist', BETTER_WISHLIST_PLUGIN_URL . 'public/assets/js/betterwishlist.js', [ 'jquery' ], BETTER_WISHLIST_PLUGIN_VERSION, true );
		wp_localize_script( 'betterwishlist', 'BETTER_WISHLIST', $localize_scripts );
		//js for mini wishlist load globally
		wp_enqueue_script( 'miniwishlist', BETTER_WISHLIST_PLUGIN_URL . 'public/assets/js/miniwishlist.js', [ 'jquery' ], BETTER_WISHLIST_PLUGIN_VERSION, true );
		wp_localize_script( 'miniwishlist', 'BETTER_WISHLIST', $localize_scripts1 );

		// if woocommerce page, enqueue styles and scripts
		if ( is_woocommerce() || is_account_page() || ( is_page() && has_shortcode( get_the_content(), 'better_wishlist' ) ) ) {
			$css = '';

			$wishlist_table_image_width = isset( $this->settings['wishlist_table_image_width'] ) ? $this->settings['wishlist_table_image_width'] : 120;
			$css .= '.betterwishlist-page-wrap table.wishlist_table img {
				max-width: '.$wishlist_table_image_width.'px
			}';

			if ( 'custom' === $this->settings['wishlist_button_style'] ) {

				$wishlist_font_size                = isset( $this->settings['wishlist_button_fontsize'] ) 		? $this->settings['wishlist_button_fontsize']          	: 'initial';
				$wishlist_button_font_style        = isset( $this->settings['wishlist_button_font_style'] ) 	? $this->settings['wishlist_button_font_style']      	: 'initial';
				$wishlist_button_border_radius     = isset( $this->settings['wishlist_button_border_radius'] ) 	? $this->settings['wishlist_button_border_radius']		: 'initial';
				$wishlist_button_border_radius     = isset( $this->settings['wishlist_button_border_radius'] ) 	? $this->settings['wishlist_button_border_radius']		: 'initial';
				$wishlist_button_border_radius_val = is_numeric( $wishlist_button_border_radius ) 				? $wishlist_button_border_radius . 'px'                	: $wishlist_button_border_radius;
				$wishlisted_button_color           = isset( $this->settings['wishlisted_button_color'] ) 		? $this->settings['wishlisted_button_color']            : 'initial';
				$wishlisted_button_bg              = isset( $this->settings['wishlisted_button_bg'] ) 			? $this->settings['wishlisted_button_bg']             	: 'initial';
				$wishlisted_button_hover_color     = isset( $this->settings['wishlisted_button_hover_color'] ) 	? $this->settings['wishlisted_button_hover_color']		: 'initial';
				$wishlisted_button_hover_bg        = isset( $this->settings['wishlisted_button_hover_bg'] ) 	? $this->settings['wishlisted_button_hover_bg']      	: 'initial';

				$css .= '.betterwishlist-add-to-wishlist {
                    width: ' . $this->settings['wishlist_button_width'] . 'px;
					font-size: ' . $wishlist_font_size . 'px !important;
					font-style: ' . $wishlist_button_font_style . ' !important;
                    color: ' . $this->settings['wishlist_button_color'] . ' !important;
                    background-color: ' . $this->settings['wishlist_button_background'] . ' !important;
                    border-style: ' . $this->settings['wishlist_button_border_style'] . ' !important;
                    border-width: ' . $this->settings['wishlist_button_border_width'] . 'px !important;
                    border-color: ' . $this->settings['wishlist_button_border_color'] . ' !important;
					border-radius: ' . $wishlist_button_border_radius_val . ' !important;
                    padding-top: ' . $this->settings['wishlist_button_padding_top'] . 'px !important;
                    padding-right: ' . $this->settings['wishlist_button_padding_right'] . 'px !important;
                    padding-bottom: ' . $this->settings['wishlist_button_padding_bottom'] . 'px !important;
                    padding-left: ' . $this->settings['wishlist_button_padding_left'] . 'px !important;
                }

				.betterwishlist-added-to-wishlist {
					color: ' . $wishlisted_button_color . ' !important;
					background-color: ' . $wishlisted_button_bg . ' !important;
				}

				.betterwishlist-added-to-wishlist:hover {
					color: ' . $wishlisted_button_hover_color . ' !important;
					background-color: ' . $wishlisted_button_hover_bg . ' !important;
				}

                .betterwishlist-add-to-wishlist:hover {
                    color: ' . $this->settings['wishlist_button_hover_color'] . ' !important;
                    background-color: ' . $this->settings['wishlist_button_hover_background'] . ' !important;
                }';
			}

			if ( 'custom' === $this->settings['cart_button_style'] ) {

				$cart_font_size                = isset( $this->settings['cart_button_fontsize'] ) 		? $this->settings['cart_button_fontsize']      	: 'initial';
				$cart_button_font_style        = isset( $this->settings['cart_button_font_style'] ) 	? $this->settings['cart_button_font_style']     : 'initial';
				$cart_button_border_radius     = isset( $this->settings['cart_button_border_radius'] ) 	? $this->settings['cart_button_border_radius']	: 'initial';
				$cart_button_border_radius_val = is_numeric($cart_button_border_radius) 				? $cart_button_border_radius . 'px'          	: $cart_button_border_radius;

				$css .= '.betterwishlist-add-to-cart {
					font-size: ' . $cart_font_size . 'px !important;
					font-style: ' . $cart_button_font_style . ' !important;
                    color: ' . $this->settings['cart_button_color'] . ' !important;
                    background-color: ' . $this->settings['cart_button_background'] . ' !important;
                    border-style: ' . $this->settings['cart_button_border_style'] . ' !important;
                    border-width: ' . $this->settings['cart_button_border_width'] . 'px !important;
                    border-color: ' . $this->settings['cart_button_border_color'] . ' !important;
					border-radius: ' . $cart_button_border_radius_val . ' !important;
                    padding-top: ' . $this->settings['cart_button_padding_top'] . 'px !important;
                    padding-right: ' . $this->settings['cart_button_padding_right'] . 'px !important;
                    padding-bottom: ' . $this->settings['cart_button_padding_bottom'] . 'px !important;
                    padding-left: ' . $this->settings['cart_button_padding_left'] . 'px !important;
                }
                .betterwishlist-add-to-cart:hover {
                    color: ' . $this->settings['cart_button_hover_color'] . ' !important;
                    background-color: ' . $this->settings['cart_button_hover_background'] . ' !important;
                }';
			}

			// enqueue styles
			wp_enqueue_style( 'betterwishlist' );
			wp_add_inline_style( 'betterwishlist', $css );

			// enqueue scripts
			wp_enqueue_script( 'betterwishlist' );
		}//end if
	}

	/**
	 * add_body_class
	 *
	 * @since 1.0.0
	 * @param mixed $classes hold all css classes
	 * @return array
	 */
	public function add_body_class( $classes ) {
		if ( is_page() && has_shortcode( get_the_content(), 'better_wishlist' ) ) {
			return array_merge( $classes, [ 'woocommerce' ] );
		}

		return $classes;
	}

	/**
	 * add_menu
	 * added menu as a woocommerce sub menu
	 *
	 * @since 1.0.0
	 * @param mixed $items all woocommerce menu list
	 * @return array
	 */
	public function add_menu( $items ) {
		if ( 'yes' !== $this->settings['wishlist_menu'] ) {
			return $items;
		}

		return array_splice( $items, 0, count( $items ) - 1 ) + [ 'betterwishlist' => __( 'Wishlist', 'better-wishlist' ) ] + $items;
	}

	/**
	 * menu_content
	 * print shortcode
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function menu_content() {
		echo do_shortcode( '[better_wishlist]' );
	}

	/**
	 * Get product stock status
	 *
	 * @since 0.0.3
	 * @param [string] $stock_status
	 * @return string
	 */
	public function betterwishlist_get_stock_status( $stock_status ) {
		switch ( $stock_status ) {
			case 'outofstock':
				$stock_status = __( 'Out Of Stock', 'better-wishlist' );
				break;
			case 'instock':
				$stock_status = __( 'In Stock', 'better-wishlist' );
				break;
			case 'onbackorder':
				$stock_status = __( 'On Backorder', 'better-wishlist' );
				break;
		}
		return $stock_status;
	}

	/**
	 * shortcode
	 * Build shortcode with necessary data
	 *
	 * @since 1.0.0
	 * @param array $atts short code attribute
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts(
			[
				'per_page'     => 5,
				'current_page' => 1,
				'pagination'   => 'no',
				'layout'       => '',
			],
			$atts
		);

		$wishlist_table_image_text       = isset( $this->settings[ 'wishlist_table_image_text' ] ) 			? html_entity_decode( $this->settings[ 'wishlist_table_image_text' ] ) 			: __( 'Image', 'better-wishlist' );
		$wishlist_table_name             = isset( $this->settings[ 'wishlist_table_name_text' ] ) 			? html_entity_decode( $this->settings[ 'wishlist_table_name_text' ] ) 			: __( 'Product Name', 'better-wishlist' );
		$wishlist_table_stock_status     = isset( $this->settings[ 'wishlist_table_stock_text' ] ) 			? html_entity_decode( $this->settings[ 'wishlist_table_stock_text' ] ) 			: __( 'Stock Status', 'better-wishlist' );
		$wishlist_table_price_text       = isset( $this->settings[ 'wishlist_table_price_text' ] ) 			? html_entity_decode( $this->settings[ 'wishlist_table_price_text' ] ) 			: __( 'Price', 'better-wishlist' );
		$wishlist_table_add_to_cart_text = isset( $this->settings[ 'wishlist_table_add_to_cart_text' ] )	? html_entity_decode( $this->settings[ 'wishlist_table_add_to_cart_text' ] ) 	: __( 'Add to Cart', 'better-wishlist' );
		$no_records_found                = isset( $this->settings[ 'no_product_added_text' ] ) 				? html_entity_decode( $this->settings[ 'no_product_added_text' ] ) 				: __( 'No Products Added', 'better-wishlist' );
		$wishlist_table_image            = isset( $this->settings[ 'wishlist_table_image' ] ) 				? $this->settings[ 'wishlist_table_image' ] 									: 'yes';
		$wishlist_table_product_name     = isset( $this->settings[ 'wishlist_table_product_name' ] ) 		? $this->settings[ 'wishlist_table_product_name' ] 								: 'yes';
		$wishlist_table_stock            = isset( $this->settings[ 'wishlist_table_stock' ] ) 				? $this->settings[ 'wishlist_table_stock' ] 									: 'yes';
		$wishlist_table_price            = isset( $this->settings[ 'wishlist_table_price' ] ) 				? $this->settings[ 'wishlist_table_price' ] 									: 'yes';
		$wishlist_table_add_to_cart      = isset( $this->settings[ 'wishlist_table_add_to_cart' ] ) 		? $this->settings[ 'wishlist_table_add_to_cart' ] 								: 'yes';

		$i18n     = [
			'table_image_text'        => $wishlist_table_image_text,
			'table_name_text'         => $wishlist_table_name,
			'table_stock_status_text' => $wishlist_table_stock_status,
			'table_price_text'        => $wishlist_table_price_text,
			'table_add_to_cart_text'  => $wishlist_table_add_to_cart_text,
			'table_image'             => $wishlist_table_image,
			'table_product_name'      => $wishlist_table_product_name,
			'table_stock'             => $wishlist_table_stock,
			'table_price'             => $wishlist_table_price,
			'table_add_to_cart'       => $wishlist_table_add_to_cart,
			'add_to_cart'             => html_entity_decode( $this->settings['add_to_cart_text'] ),
			'add_all_to_cart'         => html_entity_decode( $this->settings['add_all_to_cart_text'] ),
			'no_records_found'        => $no_records_found,
			'remove_this_product'     => __( 'Remove this product', 'better-wishlist' ),
			'read_more'     	      => __( 'Read More', 'better-wishlist' ),
			'select_option'     	  => __( 'Select Options', 'better-wishlist' ),
		];
		$items    = Plugin::instance()->model->read_list( Plugin::instance()->model->get_current_user_list() );
		$products = [];

		if ( $items ) {
			foreach ( $items as $item ) {
				$product = wc_get_product( intval( $item->product_id ) );
				if ( ! $product ) {
					continue;
				}

				//Get product stock status
				$stock_status = $product->get_stock_status();
				$stock_status = $this->betterwishlist_get_stock_status( $stock_status );

				//Get product variable price
				$product_price = wc_get_product( $product->get_id() );
				if ( $product_price && $product_price->is_type( 'variable' ) ) {
					$product_variation = $product->get_available_variations();
					if ( !empty($product_variation ) ) {
						$variation_prices = array_column( $product_variation, 'display_price' );
						$variable_min_price = min( $variation_prices );
						$variable_max_price = max( $variation_prices );
					}
				}

				//Get product grouped price
				if ( 'grouped' === $product_price->get_type() ) {
					$group_product 	= new \WC_Product_Grouped( $product->get_id() );
					$group_children = $group_product->get_children();
					$group_price 	= [];
					foreach ( $group_children as $key => $value ) {
						$_product_group = wc_get_product( $value );
						$group_price[] 	= $_product_group->get_price();
					}

					$group_min_price = min( $group_price );
					$group_max_price = max( $group_price );
				}

				$variable_sale_price   = $product_price->get_price() 			? number_format( (float)$product_price->get_price(), 2 )			: '';
				$ragular_price         = $product_price->get_regular_price()	? number_format( (float)$product_price->get_regular_price(), 2 )	: '';
				$sale_price            = $product_price->get_sale_price() 		? number_format( (float)$product_price->get_sale_price(), 2 )      	: '';
				$currency              = get_woocommerce_currency();
				$currency_symbol       = get_woocommerce_currency_symbol();
				$variable_min_price    = isset( $variable_min_price ) 			? number_format( (float) $variable_min_price, 2 ) 	: '';
				$variable_max_price    = isset( $variable_max_price ) 			? number_format( (float) $variable_max_price, 2 ) 	: '';
				$group_min_price       = isset( $group_min_price ) 				? number_format( (float) $group_min_price, 2 ) 		: '';
				$group_max_price       = isset( $group_max_price ) 				? number_format( (float) $group_max_price, 2 ) 		: '';

				if ( $product ) {
					$products[] = [
						'id'                  => $product->get_id(),
						'title'               => $product->get_title(),
						'url'                 => get_permalink( $product->get_id() ),
						'thumbnail_url'       => get_the_post_thumbnail_url( $product->get_id() ),
						'stock_status'        => $stock_status,
						'ragular_price'       => $ragular_price,
						'sale_price'          => $sale_price,
						'currency'            => $currency,
						'currency_symbol'     => $currency_symbol,
						'variable_sale_price' => $variable_sale_price,
						'is_variable_product' => $product_price->is_type( 'variable' ),
						'is_grouped_product'  => $product_price->is_type( 'grouped' ),
						'variable_min_price'  => $variable_min_price,
						'variable_max_price'  => $variable_max_price,
						'group_min_price'     => $group_min_price,
						'group_max_price'     => $group_max_price,
					];
				}
			}//end foreach
		}//end if

		return Plugin::instance()->twig->render(
			'page.twig',
			[
				'i18n'     => $i18n,
				'ids'      => wp_list_pluck( $products, 'id' ),
				'products' => $products,
			]
		);
	}

	/**
	 * Create shortcode for mini wishlist [better_wishlist_mini] use every where
	 * @since 0.0.3
	 * @return void
	 */
	public function shortcode_miniwishlist() {
		global $wpdb;
		$items    = Plugin::instance()->model->read_list( Plugin::instance()->model->get_current_user_list() );
		$products = [];

		$settings           = get_option( 'bw_settings' );
		$view_wishlist_link = get_post( $settings['wishlist_page'] );

		$i18n = [
			'no_product' => __( 'No Product Added', 'better-wishlist' ),
			'view_wishlist' => __( 'View Wishlist', 'better-wishlist' ),
		];

		if ( $items ) {
			foreach ( $items as $item ) {
				$product = wc_get_product( intval( $item->product_id ) );
				$user_id = intval( $item->user_id );

				//Get product variable price
				$product_price = wc_get_product( $product->get_id() );
				if ( $product_price && $product_price->is_type( 'variable' ) ) {
					$product_variation = $product->get_available_variations();
					if ( !empty($product_variation ) ) {
						$variation_prices = array_column( $product_variation, 'display_price' );
						$variable_min_price = min( $variation_prices );
						$variable_max_price = max( $variation_prices );
					}
				}

				//Get product grouped price
				if ( 'grouped' === $product_price->get_type() ) {
					$group_product 	= new \WC_Product_Grouped( $product->get_id() );
					$group_children = $group_product->get_children();
					$group_price 	= [];
					foreach ( $group_children as $key => $value ) {
						$_product_group = wc_get_product( $value );
						$group_price[] 	= $_product_group->get_price();
					}

					$group_min_price = min( $group_price );
					$group_max_price = max( $group_price );
				}

				$currency_symbol       = get_woocommerce_currency_symbol();
				$variable_sale_price   = $product->get_price() ? number_format( (float)$product->get_price(), 2 )                : '';
				$ragular_price         = $product->get_regular_price() ? number_format( (float)$product->get_regular_price(), 2 ): '';
				$sale_price            = $product->get_sale_price() ? number_format( (float)$product->get_sale_price(), 2 )      : '';
				$variable_min_price    = isset( $variable_min_price ) ? number_format( (float) $variable_min_price, 2 ) : '';
				$variable_max_price    = isset( $variable_max_price ) ? number_format( (float) $variable_max_price, 2 ) : '';
				$group_min_price       = isset( $group_min_price ) ? number_format( (float) $group_min_price, 2 ) 		: '';
				$group_max_price       = isset( $group_max_price ) ? number_format( (float) $group_max_price, 2 ) 		: '';

				if( $product ) {
					$products[] = [
						'id' => $product->get_id(),
						'url' => get_permalink( $product->get_id() ),
						'title' => $product->get_title(),
						'thumbnail_url' => get_the_post_thumbnail_url( $product->get_id() ),
						'currency_symbol'  => $currency_symbol,
						'sale_price'  => $sale_price,
						'ragular_price'  => $ragular_price,
						'variable_sale_price'  => $variable_sale_price,
						'is_variable_product' => $product_price->is_type( 'variable' ),
						'variable_min_price'  => $variable_min_price,
						'variable_max_price'  => $variable_max_price,
						'is_grouped_product'  => $product_price->is_type( 'grouped' ),
						'group_min_price'     => $group_min_price,
						'group_max_price'     => $group_max_price,

					];
				}
			}
		}

		if( isset( $user_id ) ){
			$total_wishlist = $wpdb->get_results( "SELECT COUNT( ID ) as count_wishlist FROM {$wpdb->prefix}better_wishlist_items WHERE user_id = $user_id" );
		}

		$total_wishlist = !empty( $total_wishlist[0]->count_wishlist ) ? $total_wishlist[0]->count_wishlist : 0;
		$view_wishlist_link = get_permalink($view_wishlist_link->ID);

		return Plugin::instance()->twig->render(
			'mini-wishlist.twig',
			[
				'i18n'               => $i18n ,
				'products'           => $products,
				'total_wishlist'     => $total_wishlist,
				'view_wishlist_link' => $view_wishlist_link,
			]
		);
	}

	/**
	 * Add mini wishlist icon into default nav menu
	 * betterwishlist_mini_cart
	 *
	 * @since 0.0.3
	 * @param [string] $args Show all current nav menus
	 * @return string
	 *
	 */
	public function betterwishlist_mini_wishlist( $nav, $args ) {
		global $wpdb;
		$items    = Plugin::instance()->model->read_list( Plugin::instance()->model->get_current_user_list() );
		$products = [];

		$settings = get_option( 'bw_settings' );
		$view_wishlist_link     = get_post( $settings['wishlist_page'] );

		$i18n = [
			'no_product'    => __( 'No product added', 'better-wishlist' ),
			'view_wishlist' => __( 'View Wishlist', 'better-wishlist' ),
		];

		if ( $items ) {
			foreach ( $items as $item ) {
				$product = wc_get_product( intval( $item->product_id ) );
				$user_id = intval( $item->user_id );

				//Get product variable price
				$product_price = wc_get_product( $product->get_id() );
				if ( $product_price && $product_price->is_type( 'variable' ) ) {
					$product_variation = $product->get_available_variations();
					if ( !empty($ßproduct_variation ) ) {
						$variation_prices   = array_column( $product_variation, 'display_price' );
						$variable_min_price = min( $variation_prices );
						$variable_max_price = max( $variation_prices );
					}
				}

				//Get product grouped price
				if ( 'grouped' === $product_price->get_type() ) {
					$group_product 	= new \WC_Product_Grouped( $product->get_id() );
					$group_children = $group_product->get_children();
					$group_price 	= [];
					foreach ( $group_children as $key => $value ) {
						$_product_group = wc_get_product( $value );
						$group_price[] 	= $_product_group->get_price();
					}

					$group_min_price = min( $group_price );
					$group_max_price = max( $group_price );
				}

				$currency_symbol       = get_woocommerce_currency_symbol();
				$variable_sale_price   = $product->get_price() ? number_format( (float)$product->get_price(), 2 )                : '';
				$ragular_price         = $product->get_regular_price() ? number_format( (float)$product->get_regular_price(), 2 ): '';
				$sale_price            = $product->get_sale_price() ? number_format( (float)$product->get_sale_price(), 2 )      : '';
				$variable_min_price    = isset( $variable_min_price ) ? number_format( (float) $variable_min_price, 2 ) : '';
				$variable_max_price    = isset( $variable_max_price ) ? number_format( (float) $variable_max_price, 2 ) : '';
				$group_min_price       = isset( $group_min_price ) ? number_format( (float) $group_min_price, 2 ) 		: '';
				$group_max_price       = isset( $group_max_price ) ? number_format( (float) $group_max_price, 2 ) 		: '';

				if( $product ) {
					$products[] = [
						'id'                  => $product->get_id(),
						'url'                 => get_permalink( $product->get_id() ),
						'title'               => $product->get_title(),
						'thumbnail_url'       => get_the_post_thumbnail_url( $product->get_id() ),
						'currency_symbol'     => $currency_symbol,
						'sale_price'          => $sale_price,
						'ragular_price'       => $ragular_price,
						'variable_sale_price' => $variable_sale_price,
						'is_variable_product' => $product_price->is_type( 'variable' ),
						'variable_min_price'  => $variable_min_price,
						'variable_max_price'  => $variable_max_price,
						'is_grouped_product'  => $product_price->is_type( 'grouped' ),
						'group_min_price'     => $group_min_price,
						'group_max_price'     => $group_max_price,

					];
				}
			}
		}

		if( isset( $user_id ) ){
			$total_wishlist = $wpdb->get_results( "SELECT COUNT( ID ) as count_wishlist FROM {$wpdb->prefix}better_wishlist_items WHERE user_id = $user_id" );
		}

		$mini_wishlist_controll = isset( $settings['mini_wishlist_controll'] ) ? $settings['mini_wishlist_controll'] : '';
		if( 'yes' === $mini_wishlist_controll ) {
			if( $args->theme_location === $settings['wishlist_nav'] ){
				$nav .= Plugin::instance()->twig->render(
					'mini-wishlist.twig',
					[
						'i18n'               => $i18n,
						'products'           => $products,
						'total_wishlist'     => !empty( $total_wishlist[0]->count_wishlist ) ? $total_wishlist[0]->count_wishlist: 0,
						'view_wishlist_link' => get_permalink( $view_wishlist_link->ID ),
					]
				);
			}
		}
		return $nav;
	}

	/**
	 * add_to_wishlist_button
	 * Added wishlist button in each woocommerce product
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function add_to_wishlist_button() {
		global $product;

		if ( ! $product ) {
			return null;
		}

		$settings = get_option( 'bw_settings' );
		$view_wishlist_link     = get_post( $settings['wishlist_page'] );
		$wishlist_icon          = isset( $this->settings[ 'wishlist_icon' ] ) 			? $this->settings[ 'wishlist_icon' ]         	: 'icon-heart-stroke';
		$show_wishlist_icon     = isset( $this->settings[ 'added_wishlist_icon' ] ) 	? $this->settings[ 'added_wishlist_icon' ]   	: 'icon-heart-fill';
		$added_to_wishlist_text = isset( $this->settings[ 'added_to_wishlist_text' ] ) 	? $this->settings[ 'added_to_wishlist_text' ]	: __( 'Added to wishlist', 'better-wishlist' );
		$wishlist_icon_controll = isset( $this->settings[ 'wishlist_icon_controll' ] ) 	? $this->settings[ 'wishlist_icon_controll' ]	: 'yes';
		$wishlist_require_login = isset( $this->settings[ 'wishlist_require_login' ] ) 	? $this->settings[ 'wishlist_require_login' ]	: 'no';

		$i18n = [
			'add_to_wishlist'        => $this->settings['add_to_wishlist_text'],
			'wishlist_icon'          => $wishlist_icon,
			'added_wishlist_icon'    => $show_wishlist_icon,
			'show_wishlist_icon'     => $wishlist_icon_controll,
			'wishlist_require_login' => $wishlist_require_login,
			'already_in_wishlist'    => $added_to_wishlist_text,
			'is_user_login'          => is_user_logged_in(),
			'view_wishlist_link'     => get_permalink($view_wishlist_link->ID),
		];

		$product_id          = intval( $product->get_id() );
		$wishlist_id         = Plugin::instance()->model->get_current_user_list() ? Plugin::instance()->model->get_current_user_list() : '';
		$already_in_wishlist = Plugin::instance()->model->item_in_list( $product_id, $wishlist_id );

		return Plugin::instance()->twig->render(
			'button.twig',
			[
				'i18n'                => $i18n,
				'product_id'          => $product->get_id(),
				'already_in_wishlist' => $already_in_wishlist,
			]
		);
	}

	/**
	 * single_add_to_wishlist_button
	 * Added wishlist button in single product page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function single_add_to_wishlist_button() {
		// @codingStandardsIgnoreStart
		echo $this->add_to_wishlist_button();
		// @codingStandardsIgnoreEnd
	}

	/**
	 * archive_add_to_wishlist_button
	 * Show wishlist page link based on admin setting
	 *
	 * @since 1.0.0
	 * @param mixed $add_to_cart_html add to cart button markup
	 * @return string
	 */
	public function archive_add_to_wishlist_button( $add_to_cart_html ) {
		if ( 'no' === $this->settings['show_in_loop'] ) {
			return $add_to_cart_html;
		}

		if ( 'before_add_to_cart' === $this->settings['position_in_loop'] ) {
			return $this->add_to_wishlist_button() . $add_to_cart_html;
		}

		return $add_to_cart_html . $this->add_to_wishlist_button();
	}

	/**
	 * ajax_add_to_wishlist
	 * Product added in wishlist when click add to wishlist button
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_add_to_wishlist() {
		check_ajax_referer( 'better_wishlist_nonce', 'security' );

		if ( empty( $_REQUEST['product_id'] ) ) {
			wp_send_json_error(
				[
					'product_title' => '',
					'message'       => __( 'Product ID is should not be empty.', 'better-wishlist' ),
				]
			);
		}

		$settings           = get_option( 'bw_settings' );
		$view_wishlist_link = get_post( $settings['wishlist_page'] );

		$product_id          = intval( $_POST['product_id'] );
		$wishlist_id         = Plugin::instance()->model->get_current_user_list() ? Plugin::instance()->model->get_current_user_list() : Plugin::instance()->model->create_list();
		$already_in_wishlist = Plugin::instance()->model->item_in_list( $product_id, $wishlist_id );

		if ( $already_in_wishlist ) {
			wp_send_json_error(
				[
					'product_title' => get_the_title( $product_id ),
					'message'       => __( 'already exists in wishlist.', 'better-wishlist' ),
				]
			);
		}

		// add to wishlist
		Plugin::instance()->model->insert_item( $product_id, $wishlist_id );

		$wishlist_icon_controll = isset( $this->settings[ 'wishlist_icon_controll' ] ) 	? $this->settings[ 'wishlist_icon_controll' ]	: 'yes';
		$added_wishlist_icon = isset( $this->settings[ 'added_wishlist_icon' ] ) 		? $this->settings[ 'added_wishlist_icon' ]		: 'icon-heart-fill';
		$added_to_wishlist_text = isset( $this->settings[ 'added_to_wishlist_text' ] ) 	? $this->settings[ 'added_to_wishlist_text' ] 	: __( 'Added to wishlist', 'better-wishlist' );

		wp_send_json_success(
			[
				'product_title'          => get_the_title( $product_id ),
				'message'                => __( 'added to wishlist.', 'better-wishlist' ),
				'view_wishlist_link'     => get_permalink( $view_wishlist_link->ID ),
				'product_id'             => $product_id,
				'added_to_wishlist_text' => $added_to_wishlist_text,
				'wishlist_icon_controll' => $wishlist_icon_controll,
				'added_wishlist_icon'    => $added_wishlist_icon,
			]
		);
	}

	/**
	 * Restore product after remove from wishlist
	 * @since 0.0.2
	 * @return void
	 */
	public function ajax_restore_wishlist() {
		check_ajax_referer( 'better_wishlist_nonce', 'security' );

		$product_id          = intval( $_POST['product_id'] );
		$wishlist_id         = Plugin::instance()->model->get_current_user_list() ? Plugin::instance()->model->get_current_user_list() : Plugin::instance()->model->create_list();
		$already_in_wishlist = Plugin::instance()->model->item_in_list( $product_id, $wishlist_id );

		$product_price = wc_get_product( $product_id );
		$product       = wc_get_product( intval( $product_id ) );

		$stock_status = $product->get_stock_status();
		switch ( $stock_status ) {
			case 'outofstock':
				$stock_status 	= __( 'Out Of Stock', 'better-wishlist' );
				$class_stock 	= 'out_stock';
				break;
			case 'instock':
				$stock_status 	= __( 'In Stock', 'better-wishlist' );
				$class_stock 	= 'in_stock';
				break;
		}

		// add to wishlist
		$restore = Plugin::instance()->model->insert_item( $product_id, $wishlist_id );

		if ( ! $restore ) {
			wp_send_json_error(
				[
					'product_title' => get_the_title( $product_id ),
					'message'       => __( 'couldn\'t be restore.', 'better-wishlist' ),
				]
			);
		}

		wp_send_json_success(
			[
				'product_id' 	=> $product_id,
				'thumbnail_url' => get_the_post_thumbnail_url( $product_id ),
				'product_title' => get_the_title( $product_id ),
				'stock_status' 	=> $stock_status,
				'class_stock' 	=> $class_stock,
				'product_url'   => get_permalink( $product_id ),
				'product_price' => $product_price->get_price(),
				'product_price_html' => $product_price->get_price_html(),
				'add_to_cart'   => html_entity_decode( $this->settings['add_to_cart_text'] ),
				'message'       => __( 'added to wishlist.', 'better-wishlist' ),
			]
		);
	}

	/**
	 * ajax_wishlist_icon
	 * Add icon for the wishlist button
	 *
	 * @return void
	 */
	public function ajax_wishlist_icon(){
		check_ajax_referer( 'better_wishlist_nonce', 'security' );

		$settings = get_option( 'bw_settings' );

		wp_send_json_success(
			[
				'added_wishlist_icon' 	=> isset( $settings['added_wishlist_icon'] ) 	? $settings['added_wishlist_icon'] 		: 'icon-heart-fill',
				'show_wishlist_icon' 	=> isset( $settings['wishlist_icon_controll'] ) ? $settings['wishlist_icon_controll'] 	: 'yes',
				'wishlist_btn_text'     => isset( $settings['added_to_wishlist_text'] ) ? $settings['added_to_wishlist_text'] 	: __( 'Added to wishlist', 'better-wishlist' ),
			]
		);

	}

	/**
	 * Count total wishlist
	 *
	 * @return int
	 */
	public function count_total_wishlist() {
		global $wpdb;
		$items    = Plugin::instance()->model->read_list( Plugin::instance()->model->get_current_user_list() );

		foreach( $items as $item ){
			$user_id = $item->user_id;
		}

		$total_wishlist = $wpdb->get_results( "SELECT COUNT( ID ) as count_wishlist FROM {$wpdb->prefix}better_wishlist_items WHERE user_id = $user_id " );
		return $total_wishlist;
	}

	/**
	 * Add to mini wishlist function use for send data to AJAX
	 * for create new product in mini wishlist card.
	 *
	 * @return void
	 */
	public function ajax_add_to_mini_wishlist() {
		check_ajax_referer( 'better_wishlist_nonce', 'security' );

		$settings            = get_option( 'bw_settings' );
		$view_wishlist_link  = get_post( $settings['wishlist_page'] );
		$product_id          = intval( $_POST['product_id'] );
		$product             = wc_get_product( intval( $product_id ) );
		$wishlist_id         = Plugin::instance()->model->get_current_user_list() ? Plugin::instance()->model->get_current_user_list(): Plugin::instance()->model->create_list();
		$already_in_wishlist = Plugin::instance()->model->item_in_list( $product_id, $wishlist_id );
		$price_html          = $product->get_price_html();

		wp_send_json_success(
			[
				'id'                  => $product_id,
				'url'                 => get_permalink( $product_id ),
				'title'               => get_the_title( $product_id ),
				'thumbnail_url'       => get_the_post_thumbnail_url( $product_id ),
				'already_in_wishlist' => $already_in_wishlist,
				'total_wishlist'      => $this->count_total_wishlist(),
				'price_html'		  => $price_html,
				'view_wishlist_link'  => get_permalink( $view_wishlist_link->ID ),
				'view_wishlist'       => __( 'View Wishlist', 'better-wishlist' ),
			]
		);
	}

	/**
	 * ajax_remove_from_wishlist
	 * Remove Product from wishlist after click remove Button/Icon
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_remove_from_wishlist() {
		check_ajax_referer( 'better_wishlist_nonce', 'security' );

		if ( empty( $_REQUEST['product_id'] ) ) {
			wp_send_json_error(
				[
					'product_title' => '',
					'message'       => __( 'Product ID is should not be empty.', 'better-wishlist' ),
				]
			);
		}

		$settings = get_option( 'bw_settings' );
		$product_id = intval( $_POST['product_id'] );
		$removed    = Plugin::instance()->model->delete_item( $product_id );
		$wishlist_icon_controll = isset( $this->settings[ 'wishlist_icon_controll' ] ) 	? $this->settings[ 'wishlist_icon_controll' ]	: 'yes';
		$add_wishlist_icon = isset( $settings[ 'wishlist_icon' ] ) 		? $settings[ 'wishlist_icon' ]		: 'icon-heart-stroke';
		$add_to_wishlist_text = isset( $this->settings[ 'add_to_wishlist_text' ] ) 	? $this->settings[ 'add_to_wishlist_text' ] 	: __( 'Add to wishlist', 'better-wishlist' );

		if ( ! $removed ) {
			wp_send_json_error(
				[
					'product_title' => get_the_title( $product_id ),
					'message'       => __( 'couldn\'t be removed.', 'better-wishlist' ),
				]
			);
		}

		wp_send_json_success(
			[
				'product_id'             => $product_id,
				'product_title'          => get_the_title( $product_id ),
				'add_wishlist_icon'      => $add_wishlist_icon,
				'add_to_wishlist_text'   => $add_to_wishlist_text,
				'wishlist_icon_controll' => $wishlist_icon_controll,
				'message'                => __( 'removed from wishlist.', 'better-wishlist' ),
				'no_product'             => __( 'No product added', 'better-wishlist' ),
			]
		);
	}

	/**
	 * ajax_add_to_cart_single
	 * All product in wishlist will be added after click Add to cart in wishlist archive page/ card page
	 *
	 * @return void
	 * @throws Exception When the received parameter is not of the expected input type.
	 * @since 1.0.0
	 */
	public function ajax_add_to_cart_single() {
		check_ajax_referer( 'better_wishlist_nonce', 'security' );

		if ( empty( $_REQUEST['product_id'] ) ) {
			wp_send_json_error(
				[
					'product_title' => '',
					'message'       => __( 'Product ID is should not be empty.', 'better-wishlist' ),
				]
			);
		}

		$product_id = intval( $_REQUEST['product_id'] );
		$product    = wc_get_product( $product_id );

		// add to cart
		if ( $product->is_type( 'variable' ) ) {
			$add_to_cart = WC()->cart->add_to_cart( $product_id, 1, $product->get_default_attributes() );
		} else {
			$add_to_cart = WC()->cart->add_to_cart( $product_id, 1 );
		}

		if ( $add_to_cart ) {
			if ( 'yes' === $this->settings['remove_from_wishlist'] ) {
				Plugin::instance()->model->delete_item( $product_id );
			}

			wp_send_json_success(
				[
					'product_title' => get_the_title( $product_id ),
					'message'       => __( 'added to cart.', 'better-wishlist' ),
				]
			);
		}

		wp_send_json_error(
			[
				'product_title' => get_the_title( $product_id ),
				'message'       => __( 'couldn\'t be added to cart.', 'better-wishlist' ),
			]
		);
	}

	/**
	 * ajax_add_to_cart_multiple
	 *
	 * @return void
	 * @throws Exception When the received parameter is not of the expected input type.
	 * @since 1.0.0
	 */
	public function ajax_add_to_cart_multiple() {
		check_ajax_referer( 'better_wishlist_nonce', 'security' );

		if ( empty( $_REQUEST['products'] ) ) {
			wp_send_json_error(
				[
					'product_title' => '',
					'message'       => __( 'Product ID is should not be empty.', 'better-wishlist' ),
				]
			);
		}

		foreach ( $_REQUEST['products'] as $product_id ) {
			WC()->cart->add_to_cart( $product_id, 1 );

			if ( filter_var( $this->settings['remove_from_wishlist'], FILTER_VALIDATE_BOOLEAN ) ) {
				Plugin::instance()->model->delete_item( $product_id );
			}
		}

		wp_send_json_success(
			[
				'product_title' => __( 'All items', 'better-wishlist' ),
				'message'       => __( 'added to cart.', 'better-wishlist' ),
			]
		);
	}
}