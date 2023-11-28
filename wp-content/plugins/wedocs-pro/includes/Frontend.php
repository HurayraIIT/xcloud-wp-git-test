<?php

namespace WeDevs\WeDocsPro;

/**
 * Frontend handler class
 */
class Frontend {

    /**
     * Frontend constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );

        add_filter( 'template_include', array( $this, 'template_loader' ), 30 );
        add_filter( 'wedocs_breadcrumbs', array( $this, 'update_breadcrumbs_styles' ) );
        add_filter( 'wedocs_breadcrumbs_html', array( $this, 'update_breadcrumbs_markup' ), 10, 2 );
        add_filter( 'wedocs_breadcrumbs_items', array( $this, 'update_breadcrumbs_item_styles' ) );
        add_filter( 'wedocs_get_doc_listing_template_args', array( $this, 'handle_documentation_listing_template_args' ), 15 );
    }

    /**
     * Register frontend styles.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_scripts() {
        wp_enqueue_script( 'wedocs-store-js' );
        wp_enqueue_script( 'wedocs-cloudflare-turnstile' );
        wp_enqueue_script( 'wedocs-pro-frontend-js' );

        wp_enqueue_style( 'wedocs-pro-frontend-css' );
    }

	/**
	 * If the theme doesn't have any single doc handler, load that from
	 * the plugin.
	 *
	 * @param string $template
	 *
	 * @return string
	 */
	public function template_loader( $template ) {
		$find = array( 'docs.php' );
		$file = '';

		if ( is_single() && get_post_type() == 'docs' ) {
			$file   = 'single-docs.php';
			$find[] = $file;
			$find[] = wedocs_pro()->theme_dir_path() . $file;
		}

		if ( $file ) {
			$template = locate_template( $find );

			if ( !$template ) {
				$template = wedocs_pro()->template_path() . $file;
			}
		}

		return $template;
	}

	/**
	 * Update documentation breadcrumbs style as template.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function update_breadcrumbs_styles( $args ) {
		return array(
			'delimiter' => '<li class="mx-1 text-[#999]">/</li>',
			'home'      => __( 'Home', 'wedocs' ),
			'before'    => '<li><span class="current">',
			'after'     => '</span></li>',
		);
	}

	/**
	 * Update documentation breadcrumbs markup as template.
	 *
	 * @since 1.0.0
	 *
	 * @param string $markup
	 * @param array  $args
	 *
	 * @return string
	 */
	public function update_breadcrumbs_markup( $markup, $args ) {
		global $post;

		$markup              = '';
		$breadcrumb_position = 1;

		$markup .= '<ol class="list-none m-0 mb-8 p-0 flex items-center" itemscope itemtype="http://schema.org/BreadcrumbList">';
		$markup .= '<li class="mr-2 mt-2.5"><i class="wedocs-icon wedocs-icon-home"></i></li>';
		$markup .= wedocs_get_breadcrumb_item( $args['home'], home_url( '/' ), $breadcrumb_position );
		$markup .= $args['delimiter'];

		// Collect documentation home page settings.
		$docs_home = wedocs_get_general_settings( 'docs_home' );
        $doc_title = wedocs_apply_short_content( get_the_title(), 25 );

		if ( $docs_home ) {
			++$breadcrumb_position;

			$markup .= wedocs_get_breadcrumb_item( __( 'Docs', 'wedocs-pro' ), get_permalink( $docs_home ), $breadcrumb_position );
			$markup .= $args['delimiter'];
		}

		if ( 'docs' == $post->post_type && $post->post_parent ) {
			$parent_id   = $post->post_parent;
			$breadcrumbs = [];

			while ( $parent_id ) {
				++$breadcrumb_position;

				$page          = get_post( $parent_id );
				$breadcrumbs[] = wedocs_get_breadcrumb_item( get_the_title( $page->ID ), get_permalink( $page->ID ), $breadcrumb_position );
				$parent_id     = $page->post_parent;
			}

			$breadcrumbs = array_reverse( $breadcrumbs );

			for ( $i = 0; $i < count( $breadcrumbs ); ++$i ) {
				$markup .= $breadcrumbs[ $i ];
				$markup .= ' ' . $args['delimiter'] . ' ';
			}
		}

		$markup .= ' ' . $args['before'] . $doc_title . $args['after'];
		$markup .= '</ol>';
		return $markup;
	}

	/**
	 * Update documentation breadcrumbs item styles.
	 *
	 * @since 1.0.0
	 *
	 * @param string $markup
	 *
	 * @return array
	 */
	public function update_breadcrumbs_item_styles( $markup ) {
		return str_replace(
            array(
                '<li',
                '<a'
            ),
            array(
                '<li class=""',
                '<a class="no-underline"'
            ),
            $markup
        );
	}

	/**
	 * Handle documentation single page arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array $template_args
	 *
	 * @return array
	 */
	public function handle_documentation_listing_template_args( $template_args ) {
		$layout_settings                  = wedocs_get_option( 'layout', 'wedocs_settings', [] );
        $template_args['pro']             = true;
        $template_args['col']             = ! empty( $layout_settings['column'] ) ? intval( $layout_settings['column'] ) : 2;;
        $template_args['icon']            = ! empty( $layout_settings['nav_icon'] ) ? esc_html( $layout_settings['nav_icon'] ) : 'on';
        $template_args['layout']          = ! empty( $layout_settings['template'] ) ? esc_html( $layout_settings['template'] ) : '';
        $template_args['active_text']     = ! empty( $layout_settings[ 'active_text' ] ) ? esc_html( wedocs_get_array_to_hex( $layout_settings[ 'active_text' ] ) ) : '#3B82F6';
        $template_args['active_nav_bg']   = ! empty( $layout_settings[ 'active_nav_bg' ] ) ? esc_html( wedocs_get_array_to_hex( $layout_settings[ 'active_nav_bg' ] ) ) : '#3B82F6';
        $template_args['active_nav_text'] = ! empty( $layout_settings[ 'active_nav_text' ] ) ? esc_html( wedocs_get_array_to_hex( $layout_settings[ 'active_nav_text' ] ) ) : '#fff';

		return $template_args;
	}
}
