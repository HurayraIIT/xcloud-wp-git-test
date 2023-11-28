<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://easy.jobs
 * @since      1.0.0
 *
 * @package    Easyjobs
 * @subpackage Easyjobs/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Easyjobs
 * @subpackage Easyjobs/public
 * @author     EasyJobs <support@easy.jobs>
 */
class Easyjobs_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets and scripts for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_assets() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Easyjobs_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Easyjobs_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
        /**
         * Styles
         */
        wp_enqueue_style( $this->plugin_name . 'owl', EASYJOBS_PUBLIC_URL . 'assets/vendor/owl.carousel.min.css', array(), $this->version, 'all' );
        wp_enqueue_style( $this->plugin_name, EASYJOBS_PUBLIC_URL . 'assets/dist/css/easyjobs-public.min.css', array(), $this->version, 'all' );

        /**
         * Scripts
         */

        wp_enqueue_script( $this->plugin_name . 'owl', EASYJOBS_PUBLIC_URL . 'assets/vendor/owl.carousel.min.js', array( 'jquery' ), $this->version, true );
        wp_enqueue_script( $this->plugin_name, EASYJOBS_PUBLIC_URL . 'assets/dist/js/easyjobs-public.min.js', array( 'jquery', $this->plugin_name . 'owl' ), $this->version, true );
	}

    /**
     * Initialize public functions
     *
     * @since 1.0.0
     * @return void
     */
    public function init() {
		if ( ! $this->is_api_key_set() ) {
            return;
		}
		add_action('easyjobs_job_filter', [$this, 'job_filter'], 10, 1);
        new Easyjobs_Shortcode();
    }

    /**
     * Check if api key is set in database
     *
     * @since 1.0.0
     * @return bool
     */
    private function is_api_key_set() {
         $settings = EasyJobs_DB::get_settings();
        if ( ! empty( $settings['easyjobs_api_key'] ) ) {
            return true;
        }
        return false;
    }

	/**
	 * Register elementor category
	 *
	 * @param $elements_manager
	 *
	 * @return void
	 * @since 1.0.4
	 */
	public function register_widget_categories( $elements_manager ) {
		$elements_manager->add_category(
			'easyjobs',
			array(
				'title' => 'EasyJobs',
				'icon'  => 'font',
            ),
            1
        );
	}
	/**
	 * Register Elementor widget
	 *
	 * @param $widgets_manager
	 *
	 * @return void
	 * @since 1.0.4
	 */
	public function register_widget( $widgets_manager ) {
		// require file
		require_once EASYJOBS_PUBLIC_PATH . '../includes/elementor/trait-easyjobs-elementor-template.php';
		require_once EASYJOBS_PUBLIC_PATH . '../includes/elementor/class-easyjobs-elementor-landingpage.php';
		require_once EASYJOBS_PUBLIC_PATH . '../includes/elementor/class-easyjobs-elementor-job-list.php';

		$widgets_manager->register_widget_type( new Easyjobs_Elementor_Landingpage() );
		$widgets_manager->register_widget_type( new Easyjobs_Elementor_Job_List() );
	}
    /**
     * Job filter and search
     * @param $jobs array
     * @return false|string
	 */
	public function job_filter($jobs){
		ob_start();
		?>
        <form class="ej-job-filter-form job-filter d-flex">
            <div class="search-bar">
                <input type="text" name="job_title" value="" class="form-control" placeholder="Job Title">
            </div>
            <div class="select-option">
                <select name="job_category">
                    <option value="0" disabled selected>Select Category</option>
                    <?php foreach ($this->get_categories($jobs) as $category): ?>
                        <option value="<?php echo esc_html($category['id'])?>"><?php echo esc_html($category['name']); ?></option>
                    <?php endforeach;?>
                </select>
            </div>
            <button class="ej-btn ej-info-btn-light" type="submit"><?php _e("Submit",'easyjobs');?></button>
            <a href="/" class="ej-btn ej-danger-btn"><?php _e("Reset",'easyjobs');?></a>
        </form>
		<?php
		return ob_get_flush();
	}

	public function get_categories($jobs)
	{
        $categories = [];
        $inserted = [];
        foreach ($jobs as $job){
            if(!in_array($job->category->id, $inserted)){
				$categories[] = [
					'id' => $job->category->id,
					'name' => $job->category->name
				];
                $inserted[] = $job->category->id;
			}

		}
        return $categories;
    }
}
