<?php

/**
 * flashing Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\FlashingTab;

use NotificationX\Core\Helper;
use NotificationX\Core\Locations;
use NotificationX\Core\PostType;
use NotificationX\Core\Rest\Analytics;
use NotificationX\Core\Rules;
use NotificationX\Extensions\FlashingTab\FlashingTab as FlashingTabFree;
use NotificationX\Extensions\GlobalFields;
use NotificationX\FrontEnd\FrontEnd;
use UsabilityDynamics\Settings as UsabilityDynamicsSettings;

/**
 * flashing Extension
 * @method static FlashingTab get_instance($args = null)
 */
class FlashingTab extends FlashingTabFree {
	public $iconPrefix = NOTIFICATIONX_PUBLIC_URL . 'image/flashing-tab/';


	/**
	 * Initially Invoked when initialized.
	 */
	public function __construct() {
		parent::__construct();
	}

	public function init() {
		parent::init();
		add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts'], 10);
		add_action('nx_wpml_translate_field', [$this, 'wpml_translate_field'], 10, 2);
	}

	public function init_fields() {
		parent::init_fields();
		add_filter('nx_content_fields', array($this, 'content_fields'), 999);
		add_filter('nx_design_tab_fields', array($this, 'design_fields'), 999);

		add_filter('nx_metabox_tabs', [$this, 'nx_tabs'], 15);
		add_filter('design_error_message', [$this, 'design_error_message'], 15);
		add_filter('nx_quick_builder_tabs', [$this, 'quick_builder_tabs'], 15);
	}

	/**
	 * This functions is hooked
	 *
	 * @hooked nx_public_action
	 * @return void
	 */
	public function admin_actions() {
		parent::admin_actions();
	}

	/**
	 * This functions is hooked
	 *
	 * @hooked nx_public_action
	 * @return void
	 */
	public function public_actions() {
		parent::public_actions();
	}

	public function design_error_message($messages) {
		if (!class_exists('\WooCommerce')) {
			$url = admin_url('plugin-install.php?s=woocommerce&tab=search&type=term');
			$messages[$this->id] = [
				'message' => sprintf(
					'%s <a href="%s" target="_blank">%s</a> %s',
					__('You have to install', 'notificationx-pro'),
					$url,
					__('WooCommerce', 'notificationx-pro'),
					__('plugin first.', 'notification-prox')
				),
				'html'  => true,
				'type'  => 'error',
				'rules' => Rules::includes('themes', ['flashing_tab_theme-4']),
			];
		}
		return $messages;
	}

	public function enqueue_scripts() {

		do_action('nx_inline');

		$posts = PostType::get_instance()->get_posts([
			'source'  => $this->id,
			'enabled' => true,
		]);

		if (!empty($posts) && is_array($posts)) {
			$posts = array_reverse($posts);
			// @todo remove unnecessary values
			foreach ($posts as $key => $post) {
				$settings = new UsabilityDynamicsSettings(['data' => $post]);

				$locations       = $settings->get('ft_all_locations', []);
				$custom_ids      = $settings->get('ft_custom_ids', []);
				$show_on_display = $settings->get('ft_show_on_display');
				$show_on_display = $settings->get('ft_show_on_display');

				if (FrontEnd::get_instance()->is_logged_in($show_on_display)) {
					continue;
				}

				if (FrontEnd::get_instance()->check_show_on($locations, $custom_ids, $settings->get('ft_show_on'))) {
					continue;
				}

				//
				$quantity = '';
				if ('flashing_tab_theme-4' === $settings->get('themes') && strpos($settings->get('ft_theme_four_line_two.default.message'), '{quantity}') !== false) {
					if (function_exists('WC')) {
						$quantity = \WC()->cart->get_cart_contents_count();
					}
					if (empty($quantity) && empty($settings->get('ft_theme_four_line_two.is-show-empty'))) {
						continue;
					}
					else if(!empty($quantity)){
						$settings->set('ft_theme_four_line_two.is-show-empty', false);
					}
					$settings->set('ft_theme_four_line_two.default.message', str_replace('{quantity}', $quantity, $settings->get('ft_theme_four_line_two.default.message', '')));
				}


				$settings->set('ft_theme_one_icons.icon-one', $this->getLocalValue($settings->get('ft_theme_one_icons.icon-one')));
				$settings->set('ft_theme_one_icons.icon-two', $this->getLocalValue($settings->get('ft_theme_one_icons.icon-two')));

				$settings->set('ft_theme_three_line_one.icon', $this->getLocalValue($settings->get('ft_theme_three_line_one.icon')));

				$settings->set('ft_theme_three_line_two.icon', $this->getLocalValue($settings->get('ft_theme_three_line_two.icon')));


				$settings->set('ft_theme_four_line_two.default.icon', $this->getLocalValue($settings->get('ft_theme_four_line_two.default.icon')));
				$settings->set('ft_theme_four_line_two.alternative.icon', $this->getLocalValue($settings->get('ft_theme_four_line_two.alternative.icon')));

				$settings->set('__rest_api_url', Analytics::get_instance()->get_rest_url());

				wp_enqueue_script('notificationx-public-flashing-tab', Helper::file('public/js/flashing-tab.js', true), [], NOTIFICATIONX_VERSION, true);
				wp_localize_script('notificationx-public-flashing-tab', 'nx_flashing_tab', $settings->get());

				// breaking the loop we only need one.
				break;
			}
		}
	}


	public function design_fields($fields) {
		$fields['themes']['fields']['advance_edit'] = Rules::is('source', $this->id, true, $fields['themes']['fields']['advance_edit']);
		return $fields;
	}
	public function content_fields($fields) {
		$content_fields = &$fields['content']['fields'];

		$content_fields['template_adv'] = Rules::is('source', $this->id, true, $content_fields['template_adv']);
		$content_fields['random_order'] = Rules::is('source', $this->id, true, $content_fields['random_order']);

		if (isset($fields['utm_options'])) {
			$fields['utm_options'] = Rules::is('source', $this->id, true, $fields['utm_options']);
		}


		$content_fields['ft_theme_one_icons'] = [
			'label'    => __('Icons', 'notificationx-pro'),
			'name'     => 'ft_theme_one_icons',
			'type'     => 'flashing-theme-one',
			'priority' => 5,
			'rules'    => Rules::logicalRule([
				Rules::is('source', $this->id),
				Rules::includes('themes', ['flashing_tab_theme-1', 'flashing_tab_theme-2']),
			]),
			'iconPrefix' => $this->iconPrefix,
			'default'     => [
				'icon-one' => 'theme-1-icon-1.png',
				'icon-two' => 'theme-1-icon-2.png',
			],
			'icons-one'     => array(
				array(
					'column' => 6,
					'label'  => __('Verified', 'notificationx-pro'),
					'icon'   => 'theme-1-icon-1.png',
				),
				array(
					'column' => 6,
					'label'  => __('Flames', 'notificationx-pro'),
					'icon'   => 'theme-2-icon-1.png',
				),
				array(
					'column' => 6,
					'label'  => __('Flames GIF', 'notificationx-pro'),
					'icon'   => 'theme-3-icon-1.png',
				),
				array(
					'column' => 6,
					'label'  => __('Pink Face', 'notificationx-pro'),
					'icon'   => 'theme-4-icon-1.png',
				),
			),
			'icons-two'     => array(
				array(
					'column' => 6,
					'label'  => __('Verified', 'notificationx-pro'),
					'icon'   => 'theme-1-icon-2.png',
				),
				array(
					'column' => 6,
					'label'  => __('Flames', 'notificationx-pro'),
					'icon'   => 'theme-2-icon-2.png',
				),
				array(
					'column' => 6,
					'label'  => __('Flames GIF', 'notificationx-pro'),
					'icon'   => 'theme-3-icon-2.png',
				),
				array(
					'column' => 6,
					'label'  => __('Pink Face', 'notificationx-pro'),
					'icon'   => 'theme-4-icon-2.png',
				),
			),
		];
		$content_fields['ft_theme_one_message'] = [
			'label'    => __("Message", 'notificationx-pro'),
			'name'     => "ft_theme_one_message",
			'type'     => "text",
			'priority' => 10,
			'default'  => __("Comeback!", "notificationx-pro"),
			'rules'    => Rules::logicalRule([
				Rules::is('source', $this->id),
				Rules::includes('themes', ['flashing_tab_theme-1', 'flashing_tab_theme-2']),
			]),
		];

		$content_fields['ft_theme_three_line_one'] = [
			'label'    => __('Message 1', 'notificationx-pro'),
			'name'     => 'ft_theme_three_line_one',
			'type'     => 'flashing-theme-three',
			'priority' => 15,
			'rules'    => Rules::logicalRule([
				Rules::is('source', $this->id),
				Rules::includes('themes', ['flashing_tab_theme-3', 'flashing_tab_theme-4']),
			]),
			'iconPrefix' => $this->iconPrefix,
			'default'     => [
				'icon'    => 'theme-3-icon-1.png',
				'message' => 'Comeback!',
			],
			'options'     => array(
				array(
					'column' => 6,
					'value'  => 'theme-1-icon-1.png',
					'label'  => __('Verified', 'notificationx-pro'),
					'icon'   => 'theme-1-icon-1.png',
				),
				array(
					'column' => 6,
					'value'  => 'theme-2-icon-1.png',
					'label'  => __('Flames', 'notificationx-pro'),
					'icon'   => 'theme-2-icon-1.png',
				),
				array(
					'column' => 6,
					'value'  => 'theme-3-icon-1.png',
					'label'  => __('Flames GIF', 'notificationx-pro'),
					'icon'   => 'theme-3-icon-1.png',
				),
				array(
					'column' => 6,
					'value'  => 'theme-4-icon-1.png',
					'label'  => __('Pink Face', 'notificationx-pro'),
					'icon'   => 'theme-4-icon-1.png',
				),
			)
		];
		$content_fields['ft_theme_three_line_two'] = [
			'label'    => __('Message 2', 'notificationx-pro'),
			'name'     => 'ft_theme_three_line_two',
			'type'     => 'flashing-theme-three',
			'priority' => 20,
			'rules'    => Rules::logicalRule([
				Rules::is('source', $this->id),
				Rules::includes('themes', ['flashing_tab_theme-3']),
			]),
			'iconPrefix' => $this->iconPrefix,
			'default'     => [
				'icon'    => 'theme-3-icon-2.png',
				'message' => 'You forgot to purchase!',
			],
			'options'     => array(
				array(
					'column' => 6,
					'label'  => __('Verified', 'notificationx-pro'),
					'icon'   => 'theme-1-icon-2.png',
				),
				array(
					'column' => 6,
					'label'  => __('Flames', 'notificationx-pro'),
					'icon'   => 'theme-2-icon-2.png',
				),
				array(
					'column' => 6,
					'label'  => __('Flames GIF', 'notificationx-pro'),
					'icon'   => 'theme-3-icon-2.png',
				),
				array(
					'column' => 6,
					'label'  => __('Pink Face', 'notificationx-pro'),
					'icon'   => 'theme-4-icon-2.png',
				),
			)
		];
		$content_fields['ft_theme_four_line_two'] = [
			'label'    => __('Message 2', 'notificationx-pro'),
			'name'     => 'ft_theme_four_line_two',
			'type'     => 'flashing-theme-four',
			'priority' => 20,
			'rules'    => Rules::logicalRule([
				Rules::is('source', $this->id),
				Rules::includes('themes', ['flashing_tab_theme-4']),
			]),
			'qnt-description' => __("The '{quantity}' tag displays total count of items in shop cart.", "notificationx-pro"),
			'chk-description' => __("Check this box to add a personalized message if cart is empty. <br />If you leave this unchecked, no message will appear on Flashing Tab.-pro", "notificationx"),
			'iconPrefix' => $this->iconPrefix,
			'default'  => [
				'is-show-empty' => false,
				'default' => [
					'icon'    => 'theme-4-icon-2.png',
					'message' => __('{quantity} items in your cart!', 'notificationx'),
				],
				'alternative' => [
					'icon'    => 'theme-4-icon-2.png',
					'message' => '',
				],
			],
			'options'     => array(
				array(
					'column' => 6,
					'label'  => __('Verified', 'notificationx-pro'),
					'icon'   => 'theme-1-icon-2.png',
				),
				array(
					'column' => 6,
					'label'  => __('Flames', 'notificationx-pro'),
					'icon'   => 'theme-2-icon-2.png',
				),
				array(
					'column' => 6,
					'label'  => __('Flames GIF', 'notificationx-pro'),
					'icon'   => 'theme-3-icon-2.png',
				),
				array(
					'column' => 6,
					'label'  => __('Pink Face', 'notificationx-pro'),
					'icon'   => 'theme-4-icon-2.png',
				),
			)
		];
		$content_fields['ft_enable_original_icon_title'] = [
			'label'       => __('Favicon & Site Title', 'notificationx-pro'),
			'name'        => 'ft_enable_original_icon_title',
			'type'        => 'checkbox',
			'priority'    => 25,
			'default'     => true,
			'description' => __('Check/Uncheck this box to show/hide favicon and site title in Flashing Tab.', 'notificationx-pro'),
			'rules'    => Rules::is('source', $this->id),
		];


		$fields['ft_timing'] = [
			'label'    => __("Timing", 'notificationx-pro'),
			'name'     => "ft_timing",
			'type'     => "section",
			'priority' => 200,
			'rules'    => Rules::is('source', $this->id),
			'fields'   => [
				'ft_delay_before' => [
					'label'    => __("Start Blinking After", 'notificationx-pro'),
					'name'     => "ft_delay_before",
					'type'     => "number",
					'priority' => 40,
					'default'  => 1,
					'min'      => 0,
					// 'help'        => __('Initial Delay', 'notificationx-pro'),
					'description' => __('seconds', 'notificationx-pro'),

				],
				'ft_delay_between' => [
					'name'        => "ft_delay_between",
					'type'        => "number",
					'label'       => __("Delay Between", 'notificationx-pro'),
					'description' => __('seconds', 'notificationx-pro'),
					// 'help'        => __('Delay between each notification', 'notificationx-pro'),
					'priority' => 50,
					'min'      => 1,
					'default'  => 2,
				],
				'ft_display_for' => [
					'name'        => "ft_display_for",
					'type'        => "number",
					'label'       => __("Display For", 'notificationx-pro'),
					'description' => __('minutes', 'notificationx-pro'),
					'priority'    => 60,
					'min'         => 0,
					'default'     => 10,
					'help'        => __('Entering ‘0’ displays the message infinitely.', 'notificationx-pro'),
				],
			],
		];

		$fields["ft_visibility"] = [
			'label'  => __("Visibility", 'notificationx-pro'),
			'name'   => "ft_visibility",
			'type'   => "section",
			'rules'  => Rules::is('source', $this->id),
			'fields' => [
				"ft_show_on" => [
					'label'    => __("Show On", 'notificationx-pro'),
					'name'     => "ft_show_on",
					'type'     => "select",
					'default'  => "everywhere",
					'priority' => 5,
					'options'  => apply_filters('nx_ft_show_on_options', GlobalFields::get_instance()->normalize_fields([
						'everywhere'       => __('Show Everywhere', 'notificationx-pro'),
						'on_selected'      => __('Show On Selected', 'notificationx-pro'),
						'hide_on_selected' => __('Hide On Selected', 'notificationx-pro'),
					])),
				],
				"ft_all_locations" => [
					'label'    => __("Locations", 'notificationx-pro'),
					'name'     => "ft_all_locations",
					'type'     => "select",
					'default'  => "",
					'multiple' => true,
					'priority' => 10,
					'rules'    => ['includes', 'ft_show_on', [
						'on_selected',
						'hide_on_selected',
					]],
					'options' => GlobalFields::get_instance()->normalize_fields(Locations::get_instance()->get_locations(false), null, null, [
						'is_custom' => [
							'label' => __('Custom Post or Page IDs', 'notificationx-pro'),
							'value' => 'is_custom',
						]
					]),
				],
				'ft_custom_ids' => [
					'label'       => __('IDs ( Posts or Pages )', 'notificationx-pro'),
					'name'      => 'ft_custom_ids',
					'type'        => 'text',
					'priority'    => 13,
					'description' => __('Comma separated ID of post, page or custom post type posts', 'notificationx-pro'),
					'rules' => Rules::logicalRule([
						Rules::includes('ft_show_on', [
							'on_selected',
							'hide_on_selected',
						]),
						Rules::includes('ft_all_locations', 'is_custom'),
					]),
				],
				"ft_show_on_display" => [
					'label'    => __("Display For", 'notificationx-pro'),
					'name'     => "ft_show_on_display",
					'type'     => "select",
					'default'  => "always",
					'priority' => 15,
					'options'  => GlobalFields::get_instance()->normalize_fields([
						'always'          => __('Everyone', 'notificationx-pro'),
						'logged_out_user' => __('Logged Out User', 'notificationx-pro'),
						'logged_in_user'  => __('Logged In User', 'notificationx-pro'),
					]),
					// 'help' => sprintf('<a target="_blank" rel="nofollow" href="https://notificationx.com/in/pro-display-control">%s</a>', __('More Control in Pro-pro', 'notificationx')),
				],
			],
		];


		return $fields;
	}

	public function quick_builder_tabs($fields) {
		$source_tab        = $fields['tabs']['source_tab']['fields'];
		$content_fields    = $source_tab['content']['fields'];
		$content           = [
			'label'    => __("Content", 'notificationx-pro'),
			'name'     => "quick_content",
			'type'     => "section",
			'priority' => 90,
			'rules'    => Rules::is('source', $this->id),
			'fields'   => [
				'ft_theme_one_icons'      => $content_fields['ft_theme_one_icons'],
				'ft_theme_one_message'    => $content_fields['ft_theme_one_message'],
				'ft_theme_three_line_one' => $content_fields['ft_theme_three_line_one'],
				'ft_theme_three_line_two' => $content_fields['ft_theme_three_line_two'],
				'ft_theme_four_line_two'  => $content_fields['ft_theme_four_line_two'],
			]
		];

		$design_tab        = &$fields['tabs']['design_tab']['fields'];
		$source_tab        = &$fields['tabs']['source_tab']['fields'];
		$content_fields    = &$source_tab['content']['fields'];
		// unset($content_fields['ft_theme_one_icons']);
		// unset($content_fields['ft_theme_one_message']);
		// unset($content_fields['ft_theme_three_line_one']);
		// unset($content_fields['ft_theme_three_line_two']);
		// unset($content_fields['ft_theme_four_line_two']);

		$design_tab['content'] = $content;
		$fields['show'][] = 'quick_content';
		return $fields;
	}

	public function wpml_translate_field($meta, $post) {
		// return $meta;
		if ($post['source'] === $this->id && !empty($post['themes'])) {
			if ('flashing_tab_theme-1' === $post['themes'] || 'flashing_tab_theme-2' === $post['themes']) {
				$meta['Message']     = ['Message', 'LINE', 'ft_theme_one_message'];
			} else if ('flashing_tab_theme-3' === $post['themes']) {
				$meta['message_1']   = ['Message 1', 'LINE', 'ft_theme_three_line_one.message'];
				$meta['message_2']   = ['Message 2', 'LINE', 'ft_theme_three_line_two.message'];
			} else if ('flashing_tab_theme-4' === $post['themes']) {
				$meta['message_1']   = ['Message 1', 'LINE', 'ft_theme_three_line_one.message'];
				$meta['message_2']   = ['Message 2', 'LINE', 'ft_theme_four_line_two.default.message'];
				// @todo if checked
				if ($post['ft_theme_four_line_two']['is-show-empty']) {
					$meta['alternative'] = ['Message 2', 'LINE', 'ft_theme_four_line_two.alternative.message'];
				}
			}
		}

		return $meta;
	}

	// A function that takes a value and an iconPrefix and returns a localValue
	public function getLocalValue($value) {
		// Check if the value is empty
		if (empty($value)) {
		  // Return an empty string
		  return "";
		}
		// Check if the value is a URL or a data URL using regular expressions
		$isUrl = preg_match("/^https?:\/\//", $value); // returns 1 if value starts with http:// or https://
		$isDataUrl = preg_match("/^data:/", $value); // returns 1 if value starts with data:
		// If the value is neither a URL nor a data URL, prepend the iconPrefix
		$localValue = $isUrl || $isDataUrl ? $value : $this->iconPrefix . $value;
		// Return the localValue
		return $localValue;
	}
}
