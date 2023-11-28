<?php

namespace Embedpress\Pro\Providers;

use EmbedPress\Plugins\Plugin;
use Embedpress\Pro\Classes\Helper;

( defined( 'ABSPATH' ) && defined( 'EMBEDPRESS_IS_LOADED' ) ) or die( "No direct script access allowed." );

/**
 * Entity that represents an EmbedPress plugin dedicated to Document embeds.
 *
 * @package     EmbedPress-pro\Document
 * @author      EmbedPress<help@embedpress.com>
 * @copyright   Copyright (C) 2021 EmbedPress. All rights reserved.
 * @license     GPLv2 or later
 */
class Document extends Plugin
{

	/**
	 * Plugin's name.
	 *
	 * @since   1.0.0
	 *
	 * @const   NAME
	 */
	const NAME = "Document";

	/**
	 * Plugin's slug.
	 *
	 * @since   1.0.0
	 *
	 * @const   SLUG
	 */
	const SLUG = 'document';

	/**
	 * Return the plugin options schema.
	 *
	 * @return  array
	 * @since   1.0.0
	 * @static
	 *
	 */
	public static function getOptionsSchema()
	{
		return [
			'doc_lc_show' => [
				'type'        => 'string',
				'default'     => ''
			],
			'logo_url' => [
				'type'        => 'url',
			],
			'logo_xpos' => [
				'type'        => 'number',
				'default'     => 10
			],
			'logo_ypos' => [
				'type'        => 'number',
				'default'     => 10
			],
			'cta_url' => [
				'type'        => 'url',
			],
		];
	}


	/**
	 * Callback called right before an url be embedded. If return false, EmbedPress will not embed the url.
	 *
	 * @param \stdClass    An object representing the embed.
	 *
	 * @return  mixed
	 * @since   1.0.0
	 * @static
	 *
	 */
	public static function onBeforeEmbed( $embed )
	{
		if ( empty( $embed ) ) {
			return false;
		}

		return $embed;
	}

	/**
	 * Callback called right after a Document url has been embedded.
	 *
	 * @param \stdClass    An object representing the embed.
	 *
	 * @return  array
	 * @since   1.0.0
	 * @static
	 *
	 */
	public static function onAfterEmbed( $embed )
	{
		$isGoogleDoc = false;
		$temp = (array) $embed;
		if ( !empty( $temp) ) {
			$data = array_shift( $temp);
			$data = (array) $data;
			$isGoogleDoc = isset($data['provider_url']) && $data['provider_url']  === 'https://docs.google.com';
		}



		if ( $isGoogleDoc && isset( $embed->embed ) ) {
			$options = self::getOptions();
			$embed = Helper::apply_cta_markup_for_blockeditor( $embed, $options, 'google-docs');
		}

		return $embed;
	}


	/**
	 * Method to get params
	 *
	 * @since 1.0.0
	 * @static
	 */


	public static function featureExtend()
	{

		add_filter( 'embedpress:onAfterEmbed', [static::class, 'onAfterEmbed'], 90 );
		add_filter( 'embedpress:onBeforeEmbed', [static::class, 'onBeforeEmbed'] );

		add_action( 'after_embedpress_branding_save', [static::class, 'save_custom_logo_settings']);
	}


	public static function save_custom_logo_settings() {
		Helper::save_custom_logo_settings('document', 'doc');
	}

}
