<?php
    namespace EssentialBlocks\Pro\blocks;

    use EssentialBlocks\Traits\HasSingletone;

    class PostGrid {
        use HasSingletone;
        public function __construct() {
            add_filter( 'eb_frontend_scripts/post-grid', [$this, 'add_pro_script_args'], 99, 1 );
            add_action( 'wp_enqueue_scripts', [$this, 'add_script'] );
            add_action( "eb_post_grid_search_form", [$this, "add_search_form"] );
        }

        public function add_script() {
            wp_register_script( 'essential-blocks-pro-post-grid-frontend', ESSENTIAL_BLOCKS_PRO_URL . "blocks/post-grid/frontend/index.js", ['essential-blocks-post-grid-frontend'], ESSENTIAL_BLOCKS_PRO_VERSION, true );
        }

        public function add_pro_script_args( $style ) {
            return array_merge( $style, ['essential-blocks-pro-post-grid-frontend'] );
        }

        public function add_search_form( $attributes ) {
            $show_search        = isset( $attributes['showSearch'] ) ? $attributes['showSearch'] : false;
            $enable_ajax_search = isset( $attributes['enableAjaxSearch'] ) ? $attributes['enableAjaxSearch'] : false;
            if ( $show_search ) {
            ?>
			<div class="eb-post-grid-search"
			data-ajax-search="<?php echo $enable_ajax_search; ?>"
			>
				<form
					action=""
					class="eb-post-grid-search-form"
					autocomplete="off"
				>
					<div class="eb-post-grid-search-input-wrap">
						<svg class="eb-post-grid-search-loader" width="38" height="38" viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg" stroke="#444b54">
						<g fill="none" fill-rule="evenodd">
						<g transform="translate(1 1)" stroke-width="2">
							<circle stroke-opacity=".5" cx="18" cy="18" r="18"></circle>
							<path d="M36 18c0-9.94-8.06-18-18-18">
								<animateTransform attributeName="transform" type="rotate" from="0 18 18" to="360 18 18" dur="1s" repeatCount="indefinite"></animateTransform>
							</path>
						</g>
						</g>
						</svg>
						<input type="search" class="eb-post-grid-search-field" name="s" value="" placeholder="Search ...." required="">
					</div>
					<svg class="eb-post-grid-search-icon" xmlns="http://www.w3.org/2000/svg" width="38" viewBox="0 0 50 50">
					<path d="M21 3C11.602 3 4 10.602 4 20s7.602 17 17 17c3.355 0 6.46-.984 9.094-2.656l12.281 12.281 4.25-4.25L34.5 30.281C36.68 27.421 38 23.88 38 20c0-9.398-7.602-17-17-17zm0 4c7.2 0 13 5.8 13 13s-5.8 13-13 13S8 27.2 8 20 13.8 7 21 7z"></path>
					</svg>
				</form>
				<div class="eb-post-grid-search-result">
					<div class="eb-post-grid-search-content"></div>
					<div class="eb-post-grid-search-not-found"><?php esc_html_e( "No Posts Found", "essential-blocks-pro" );?></div>
				</div>
			</div>
	<?php
        }
            }
    }
