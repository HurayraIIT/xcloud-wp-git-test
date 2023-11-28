<?php
/*
 * Plugin Name: SchedulePress Pro
 * Plugin URI: https://wpdeveloper.com/plugins/wp-scheduled-posts/
 * Description: A complete solution to manage your scheduled posts in WordPress with Auto & Manual Scheduler, Missed Schedule Handler, Republish/Unpublish and many more.
 * Version: 5.0.1
 * Author: WPDeveloper
 * Author URI: https://wpdeveloper.com
 * Text Domain: wp-scheduled-posts-pro
 */


if (!defined('ABSPATH')) exit;


if ( ! version_compare( PHP_VERSION, '7.2', '>=' ) ) {
	add_action( 'admin_notices', 'wpsp_pro_fail_php_version', 52 );
    return;
}
else {
    if (file_exists(ABSPATH . 'wp-admin/includes/plugin.php')) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }

    if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
        require_once dirname(__FILE__) . '/vendor/autoload.php';
    }
    // Plugin Start
    WPSP_PRO_Start();
}


final class WPSP_PRO
{
    private $installer;
    private $basename = 'wp-scheduled-posts/wp-scheduled-posts.php';
    private function __construct()
    {
        $this->define_constants();
        if($this->check_free_compatibility()){
            $this->upgrade_free();
            // checking again, if upgrade isn't successful show notice.
            if($this->check_free_compatibility()){
                add_action( 'admin_notices', array( $this, 'upgrade_failed_notice' ) );
            }
			return;
        }
        add_action('init',  [$this, 'install_core']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        $this->installer = new WPSP_PRO\Installer();
        add_action('plugins_loaded', [$this, 'init_plugin']);
        add_action('wp_loaded', [$this, 'run_migrator']);
        if (!is_plugin_active('wp-scheduled-posts/wp-scheduled-posts.php')) {
            add_action('admin_notices', array($this, 'core_install_notice'));
        }
    }

    public static function init()
    {
        static $instance = false;

        if (!$instance) {
            $instance = new self();
        }

        return $instance;
    }
    public function check_free_compatibility(){
        if (file_exists(ABSPATH . 'wp-admin/includes/plugin.php')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
		$abs_path = WP_PLUGIN_DIR . '/' . $this->basename;

		if (
			$this->is_plugin_installed( $this->basename ) &&
			version_compare( get_plugin_data( $abs_path )['Version'], '5.0.0', '<' )
		) {
			return true;
		}
		return false;
    }

    /**
     * Check if a plugin is installed
     *
     * @since 2.0.0
     */
    public function is_plugin_installed($basename)
    {
        $plugins = get_plugins();
        return isset($plugins[$basename]);
    }

    // Add admin notice
    public function upgrade_failed_notice() {
        ?>
        <div class="notice notice-error is-dismissible" id="upgrade-notice">
            <p><?php _e( 'A new version of ScheduledPress is available. Please upgrade now.', 'wp-scheduled-posts-pro' ); ?></p>
            <!-- <button type="button" class="button button-primary" id="upgrade-button"><?php _e( 'Upgrade Now', 'wp-scheduled-posts-pro' ); ?></button> -->
        </div>
        <?php
    }
    // AJAX call to upgrade function
    public function upgrade_free(){
        $is_skip = get_transient( 'wpsp_skip_upgrade' );
        // skip if upgrade is already in progress
        if($is_skip) return;
        set_transient( 'wpsp_skip_upgrade', true, MINUTE_IN_SECONDS * 2 );
        $tried = get_option( 'wpsp_upgrade_tried', 0 );
        if($tried > 2) return;
        update_option( 'wpsp_upgrade_tried', $tried + 1 );

        require_once ABSPATH . 'wp-load.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-includes/pluggable.php';

        $plugin_path = 'wp-scheduled-posts/wp-scheduled-posts.php';
        $upgrader    = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
        $result      = $upgrader->upgrade( $plugin_path );

        delete_transient( 'wpsp_skip_upgrade' );

        if ($result) {
            // reload the current page.
            wp_safe_redirect( $_SERVER['REQUEST_URI'] );
        }
    }
    public function define_constants()
    {
        /**
         * Defines CONSTANTS for Whole plugins.
         */
        define('WPSP_PRO_VERSION', '5.0.1');
        define('WPSP_PRO_PLUGIN_BASENAME', plugin_basename(__FILE__));
        define('WPSP_PRO_SETTINGS_SLUG', 'schedulepress');
        define('WPSP_PRO_SETTINGS_NAME', 'wpsp_settings_v5');
        define('WPSP_PRO_PLUGIN_URL', plugins_url('/', __FILE__));
        define('WPSP_PRO_ASSETS_URL', WPSP_PRO_PLUGIN_URL . 'assets/');
        define('WPSP_PRO_ROOT_PATH', plugin_dir_path(__FILE__));
        define('WPSP_PRO_VIEW_DIR_PATH', WPSP_PRO_ROOT_PATH . 'views/');
        define('WPSP_PRO_ASSETS_PATH', WPSP_PRO_ROOT_PATH . 'assets/');
        define('WPSP_PRO_PLUGIN_PATH', plugin_dir_path(__FILE__));


        // Licensing
        define('WPSP_PRO_STORE_URL', 'http://api.wpdeveloper.com/');
        define('WPSP_PRO_SL_ITEM_ID', 78593);
        define('WPSP_PRO_SL_ITEM_SLUG', 'wp-scheduled-posts-pro');
        define('WPSP_PRO_SL_ITEM_NAME', 'WP Scheduled Posts');
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init_plugin()
    {
        $this->installer->plugin_updater();
		if(!defined('WPSP_VERSION')){
			return;
		}
        new WPSP_PRO\Assets();
        $this->load_textdomain();
        if (is_admin()) {
            new WPSP_PRO\Admin();
        }
        new WPSP_PRO\Scheduled();
        new WPSP_PRO\API();
    }

    public function load_textdomain()
    {

        load_plugin_textdomain(
            'wp-scheduled-posts-pro',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    public function install_core()
    {
        new WPSP_PRO\Admin\WPDev\InstallCore();
    }

    /**
     * Do stuff upon plugin activation
     *
     * @return void
     */
    public function activate()
    {
        $this->installer->plugin_activation_hook();
        update_option('wpsp_do_activation_redirect', true);
    }
    /**
     * Do stuff upon plugin deactive
     *
     * @return void
     */
    public function deactivate()
    {
        do_action('wpsp_run_deactivate_installer');
    }

    public function run_migrator()
    {
        $this->installer->migrate();
    }
    /**
     * Admin Notices
     */
    public function core_install_notice()
    {
?>
        <div class="error notice is-dismissible">
            <p><strong><?php esc_html_e('SchedulePress Pro', 'wp-scheduled-posts-pro'); ?></strong> <?php esc_html_e('requires', 'wp-scheduled-posts-pro'); ?> <strong><?php esc_html_e('SchedulePress', 'wp-scheduled-posts-pro'); ?></strong> <?php esc_html_e('core plugin to be installed. Please get the plugin now!', 'wp-scheduled-posts-pro'); ?> <button id="wpsp-install-core" class="button button-primary"><?php esc_html_e('Install Now!', 'wp-scheduled-posts-pro') ?></button></p>
        </div>
<?php
    }
}

/**
 * Initializes the main plugin
 *
 * @return \WPSP_PRO
 */
function WPSP_PRO_Start()
{
    return WPSP_PRO::init();
}


function wpsp_pro_fail_php_version() {
	$message = sprintf(
		/* translators: 1: `<h3>` opening tag, 2: `</h3>` closing tag, 3: PHP version. 4: Link opening tag, 5: Link closing tag. */
		esc_html__( '%1$sSchedulePress Pro isn’t running because PHP is outdated.%2$s Update to PHP version %3$s and get back to creating!', 'wp-scheduled-posts-pro' ),
		'<h3>',
		'</h3>',
		'7.2'
	);
	$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
	echo wp_kses_post( $html_message );
}
