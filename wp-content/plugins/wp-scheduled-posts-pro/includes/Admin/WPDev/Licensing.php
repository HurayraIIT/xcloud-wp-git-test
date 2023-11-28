<?php

namespace WPSP_PRO\Admin\WPDev;

class Licensing
{
	private $product_slug;
	private $text_domain;
	private $product_name;
	private $item_id;

	/**
	 * Initializes the license manager client.
	 */
	public function __construct($product_slug, $product_name, $text_domain)
	{
		// Store setup data
		$this->product_slug         = $product_slug;
		$this->text_domain          = $text_domain;
		$this->product_name         = $product_name;
		$this->item_id              = WPSP_PRO_SL_ITEM_ID;

		// Init
		$this->add_actions();
	}
	/**
	 * Adds actions required for class functionality
	 */
	public function add_actions()
	{
		if (is_admin()) {
			// Add the menu screen for inserting license information
			add_action('admin_init', array($this, 'register_license_settings'));
			add_action('wp_ajax_wpsp_activate_license', array($this, 'activate_license'));
			add_action('wpsp_activate_license', array($this, 'activate_license'));
			add_action('wp_ajax_wpsp_deactivate_license', array($this, 'deactivate_license'));
			add_action('admin_notices', array($this, 'admin_notices'));
			add_action('wp_ajax_get_license', array($this, 'get_license'));
		}
	}

	/**
	 * @return string   The slug id of the licenses settings page.
	 */
	protected function get_settings_page_slug()
	{
		// return $this->product_slug . '-license';
		return 'schedulepress';
	}

	/**
	 * Creates the settings fields needed for the license settings menu.
	 */
	public function register_license_settings()
	{
		// creates our settings in the options table
		register_setting($this->get_settings_page_slug(), $this->product_slug . '-license-key', 'sanitize_license');
	}

	public function sanitize_license($new)
	{
		$old = get_option($this->product_slug . '-license-key');
		if ($old && $old != $new) {
			delete_option($this->product_slug . '-license-status'); // new license has been entered, so must reactivate
		}
		return $new;
	}

	/**
	 * Handles admin notices for errors and license activation
	 *
	 * @since 0.1.0
	 */

	public function admin_notices()
	{
		$status = $this->get_license_status();

		if (!isset($_POST[$this->product_slug . '_license_activate'])) {
			$license_data = $this->get_license_data();
		}

		if (isset($_POST[$this->product_slug . '_license_deactivate'])) {
			delete_transient($this->product_slug . '-license_data');
		}

		if (isset($license_data->license)) {
			$status = $license_data->license;
		}

		if ($status === 'http_error') {
			return;
		}

		if (($status === false || $status !== 'valid') && $status !== 'expired') {
			$msg = __('Please %1$sactivate your license%2$s key to enable updates for %3$s.',  'wp-scheduled-posts-pro');
			$msg = sprintf($msg, '<a href="' . admin_url('admin.php?page=' . $this->get_settings_page_slug()) . '&tab=license">', '</a>',	'<strong>' . esc_html($this->product_name) . '</strong>');
?>
			<div class="notice notice-error">
				<p><?php echo wp_kses_post($msg); ?></p>
			</div>
		<?php
		}
		if ($status === 'expired') {
			$msg = __('Your license has been expired. Please %1$srenew your license%2$s key to enable updates for %3$s.',	 'wp-scheduled-posts-pro');
			$msg = sprintf($msg, '<a href="https://store.wpdeveloper.com">', '</a>', '<strong>' . esc_html($this->product_name) . '</strong>');
		?>
			<div class="notice notice-error">
				<p><?php echo wp_kses_post($msg); ?></p>
			</div>
			<?php
		}
		if ((isset($_GET['sl_activation']) || isset($_GET['sl_deactivation'])) && !empty($_GET['message'])) {
			$target = isset($_GET['sl_activation']) ? $_GET['sl_activation'] : null;
			$target = is_null($target) ? (isset($_GET['sl_deactivation']) ? $_GET['sl_deactivation'] : null) : null;
			switch ($target) {
				case 'false':
					$message = urldecode($_GET['message']);
			?>
					<div class="error">
						<p><?php echo wp_kses_post($message); ?></p>
					</div>
<?php
					break;
				case 'true':
				default:
					// Developers can put a custom success message here for when activation is successful if they way.
					break;
			}
		}
	}

	/**
	 * Renders the settings page for entering license information.
	 */
	public function get_license()
	{
		// run a quick security check
		// $nonce = $_REQUEST['_wpnonce'];
		// if (!wp_verify_nonce($nonce, WPSP_PRO_SL_ITEM_SLUG . '_license_nonce')) {
		// 	die(__('Security check', 'wp-scheduled-posts-pro'));
		// }
		
		$license_key 	= $this->get_hidden_license_key();
		$title 			= sprintf(__('%s License',  'wp-scheduled-posts-pro'), $this->product_name);
		$status = $this->get_license_status();
		wp_send_json_success(array('title' => $title, 'key' => $license_key, 'status' => $status));
		wp_die();
	}

	/**
	 * Gets the current license status
	 *
	 * @return bool|string   The product license key, or false if not set
	 */
	public function get_license_status()
	{
		$status = get_option($this->product_slug . '-license-status');
		if (!$status) {
			// User hasn't saved the license to settings yet. No use making the call.
			return false;
		}
		return trim($status);
	}

	/**
	 * Gets the currently set license key
	 *
	 * @return bool|string   The product license key, or false if not set
	 */
	public function get_license_key()
	{
		$license = get_option($this->product_slug . '-license-key');
		if (!$license) {
			// User hasn't saved the license to settings yet. No use making the call.
			return false;
		}
		return trim($license);
	}


	/**
	 * Updates the license key option
	 *
	 * @return bool   The product license key, or false if not set
	 */
	public function set_license_key($license_key)
	{
		return update_option($this->product_slug . '-license-key', $license_key);
	}

	private function get_hidden_license_key()
	{
		$input_string = $this->get_license_key();

		$start = 5;
		$length = mb_strlen($input_string) - $start - 5;

		$mask_string = preg_replace('/\S/', '*', $input_string);
		$mask_string = mb_substr($mask_string, $start, $length);
		$input_string = substr_replace($input_string, $mask_string, $start, $length);

		return $input_string;
	}

	/**
	 * @param array $body_args
	 *
	 * @return \stdClass|\WP_Error
	 */
	private function remote_post($body_args = [])
	{
		$api_params = wp_parse_args(
			$body_args,
			[
				'item_id' => urlencode($this->item_id),
				'url'     => home_url(),
			]
		);

		$response = wp_remote_post(WPSP_PRO_STORE_URL, [
			'sslverify' => false,
			'timeout' => 40,
			'body' => $api_params,
		]);

		if (is_wp_error($response)) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code($response);
		if (200 !== (int) $response_code) {
			return new \WP_Error($response_code, __('HTTP Error', 'wp-scheduled-posts-pro'));
		}

		$data = json_decode(wp_remote_retrieve_body($response));
		if (empty($data) || !is_object($data)) {
			return new \WP_Error('no_json', __('An error occurred, please try again', 'wp-scheduled-posts-pro'));
		}

		return $data;
	}

	public function activate_license($params)
	{
		if (!isset($params['key'])) {
			return;
		}
		// run a quick security check
		// $nonce = $params['_wpnonce'];
		// if (!wp_verify_nonce($nonce, WPSP_PRO_SL_ITEM_SLUG . '_license_nonce')) {
		// 	die(__('Security check', 'wp-scheduled-posts-pro'));
		// }

		// retrieve the license from the database
		$license = $params['key'];

		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
		);

		$license_data = $this->remote_post($api_params);

		if (is_wp_error($license_data)) {
			$message = $license_data->get_error_message();
		}

		if (isset($license_data->success) && false === boolval($license_data->success)) {

			switch ($license_data->error) {

				case 'expired':

					$message = sprintf(
						__('Your license key expired on %s.', 'wp-scheduled-posts-pro'),
						date_i18n(get_option('date_format'), strtotime($license_data->expires, current_time('timestamp')))
					);
					break;

				case 'revoked':

					$message = __('Your license key has been disabled.', 'wp-scheduled-posts-pro');
					break;

				case 'missing':

					$message = __('Invalid license key.', 'wp-scheduled-posts-pro');
					break;

				case 'invalid':
				case 'site_inactive':

					$message = __('Your license is not active for this URL.', 'wp-scheduled-posts-pro');
					break;

				case 'item_name_mismatch':

					$message = sprintf(__('This appears to be an invalid license key for %s.', 'wp-scheduled-posts-pro'), WPSP_PRO_SL_ITEM_NAME);
					break;

				case 'no_activations_left':

					$message = __('Your license key has reached its activation limit.', 'wp-scheduled-posts-pro');
					break;

				default:

					$message = __('An error occurred, please try again.', 'wp-scheduled-posts-pro');
					break;
			}
		}


		// Check if anything passed on a message constituting a failure
		// Check if anything passed on a message constituting a failure
		if (!empty($message)) {
			wp_send_json_error($message);
			exit();
		}

		// $license_data->license will be either "valid" or "invalid"
		$this->set_license_key($license);
		$this->set_license_data($license_data);
		$this->set_license_status($license_data->license);


		$license_key 	= $this->get_hidden_license_key();
		$title 			= sprintf(__('%s License', 'wp-scheduled-posts-pro'), $this->product_name);
		$status = $this->get_license_status();
		wp_send_json_success(array('title' => $title, 'key' => $license_key, 'status' => $status));
		wp_die();
	}

	public function set_license_data($license_data, $expiration = null)
	{
		if (null === $expiration) {
			$expiration = 12 * HOUR_IN_SECONDS;
		}
		set_transient($this->product_slug . '-license_data', $license_data, $expiration);
	}

	public function get_license_data($force_request = false)
	{
		$license_data = get_transient($this->product_slug . '-license_data');

		if (false === $license_data || $force_request) {

			$license = $this->get_license_key();

			if (empty($license)) {
				return false;
			}

			$body_args = [
				'edd_action' => 'check_license',
				'license' => $this->get_license_key(),
			];

			$license_data = $this->remote_post($body_args);

			if (is_wp_error($license_data)) {
				$license_data = new \stdClass();
				$license_data->license = 'valid';
				$license_data->payment_id = 0;
				$license_data->license_limit = 0;
				$license_data->site_count = 0;
				$license_data->activations_left = 0;
				$this->set_license_data($license_data, 30 * MINUTE_IN_SECONDS);
				$this->set_license_status($license_data->license);
			} else {
				$this->set_license_data($license_data);
				$this->set_license_status($license_data->license);
			}
		}

		return $license_data;
	}

	public function deactivate_license( $params )
	{
		// run a quick security check
		// $nonce = $_REQUEST['_wpnonce'];
		// if (!wp_verify_nonce($nonce, WPSP_PRO_SL_ITEM_SLUG . '_license_nonce')) {
		// 	die(__('Security check', 'wp-scheduled-posts-pro'));
		// }

		// retrieve the license from the database
		$license = $this->get_license_key();

		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
		);

		$license_data = $this->remote_post($api_params);


		if (is_wp_error($license_data)) {
			$message = $license_data->get_error_message();
		}

		if ($license_data->license != 'deactivated') {
			$message = __('An error occurred, please try again', 'wp-scheduled-posts-pro');
			wp_send_json_success(array('message' => $message));
			wp_die();
		}

		$delete_key = false;
		$delete_license = false;

		if ($license_data->license == 'deactivated') {
			$delete_key = delete_option($this->product_slug . '-license-status');
			$delete_license = delete_option($this->product_slug . '-license-key');
			delete_transient($this->product_slug . '-license_data');
		}

		wp_send_json_success(array('delete_key' => $delete_key, 'delete_license' => $delete_license));
		wp_die();
	}

	/**
	 * Updates the license status option
	 *
	 * @return bool|string   The product license key, or false if not set
	 */
	public function set_license_status($license_status)
	{
		return update_option($this->product_slug . '-license-status', $license_status);
	}
}
