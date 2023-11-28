<?php
/**
 * Blocks Initializer
 *
 * Enqueue CSS/JS of all the blocks.
 *
 * @since   1.0.0
 * @package CGB
 */


use \Embedpress\Pro\Providers\Vimeo as VimeoPlugin;
use \Embedpress\Pro\Providers\Youtube as YoutubePlugin;
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue Gutenberg block assets for both frontend + backend.
 *
 * @uses {wp-editor} for WP editor styles.
 * @since 1.0.0
 */
function embedpress_pro_blocks_cgb_block_assets() { // phpcs:ignore
	wp_enqueue_style(
		'embedpress_pro-cgb-style-css',
		EMBEDPRESS_PRO_PLUGIN_URL.'Gutenberg/dist/blocks.style.build.css',
		array( 'wp-editor' ),
		null
	);
}

// Hook: Frontend assets.
add_action( 'enqueue_block_assets', 'embedpress_pro_blocks_cgb_block_assets' );

/**
 * Enqueue Gutenberg block assets for backend editor.
 *
 * @uses {wp-blocks} for block type registration & related functions.
 * @uses {wp-element} for WP Element abstraction — structure of blocks.
 * @uses {wp-i18n} to internationalize the block's text.
 * @uses {wp-editor} for WP editor styles.
 * @since 1.0.0
 */
function embedpress_pro_blocks_cgb_editor_assets() { // phpcs:ignore
	// Register block editor script for backend.
	wp_enqueue_script(
		'embedpress_pro-cgb-block-js',
		EMBEDPRESS_PRO_PLUGIN_URL.'Gutenberg/dist/blocks.build.js',
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
		null,
		true
	);

	// Register block editor styles for backend.
	wp_enqueue_style(
		'embedpress_pro-cgb-block-editor-css',
		EMBEDPRESS_PRO_PLUGIN_URL.'Gutenberg/dist/blocks.editor.build.css',
		array( 'wp-edit-blocks' ),
		null
	);

	$youtube_options = YoutubePlugin::getOptions();
	$youtube_params = YoutubePlugin::getParams($youtube_options);
	$vimeo_options  = VimeoPlugin::getOptions();
	$vimeo_params = VimeoPlugin::getParams($vimeo_options);
	$elements = (array) get_option( EMBEDPRESS_PLG_NAME.":elements", []);
	$active_blocks = isset( $elements['gutenberg']) ? (array) $elements['gutenberg'] : [];
	wp_localize_script(
		'embedpress_pro-cgb-block-js',
		'embedpressProObj', // Array containing dynamic data for a JS Global.
		[
			'pluginDirPath' => plugin_dir_path( __DIR__ ),
			'pluginDirUrl'  => plugin_dir_url( __DIR__ ),
			'youtubeParams'	=> $youtube_params,
			'vimeoParams'	=> $vimeo_params,
			'active_blocks' => $active_blocks,
		]
	);
}

// Hook: Editor assets.
add_action( 'enqueue_block_editor_assets', 'embedpress_pro_blocks_cgb_editor_assets' );

add_filter('embedpress_document_block_powered_by',function (){
	return false;
});
