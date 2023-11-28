<?php
namespace NotificationXPro\Core;

use NotificationX\Core\PostType;
use NotificationX\FrontEnd\FrontEnd;
use NotificationX\GetInstance;
use NotificationXPro\Core\REST;
use NotificationX\Core\Rules;

/**
 * Class Shortcode For NotificationX Pro
 * @method static Shortcode get_instance($args = null)
 *
 * @since 1.2.3
 */
class Shortcode {
    /**
     * Instance of Shortcode
     *
     * @var Shortcode
     */
    use GetInstance;

    public $shortcode_nx_ids = [];

    /**
     * __construct__ is for revoke first time to get ready
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'nx_display_fields', array( $this, 'display_fields' ), 11 );
        add_filter( 'nx_add_in_queue', array( $this, 'only_as_shortcode' ), 10, 2 );
        add_action( 'wp_print_footer_scripts', [ $this, 'footer_scripts' ], 999 );
        add_filter( 'nx_frontend_localize_data', [$this, 'add_shortcode_ids'] );
        add_shortcode( 'notificationx', array( $this, 'shortcode' ), 999 );
    }

    /**
     * Customize tab fields added
     *
     * @param array $options
     * @return array
     */
    public function display_fields( $options ) {
        $options['visibility']['fields']['show_on']['options']['only_shortcode'] = [
            'label' => __( 'Use Only as Shortcode', 'notificationx-pro' ),
            'value' => 'only_shortcode',
            'rules' => Rules::is( 'source', 'press_bar', true ),
        ];
        return $options;
    }

    /**
     * Use notification only as shortcode
     *
     * @param string $type
     * @param mixed  $settings
     */
    public function only_as_shortcode( $type, $settings ) {
        if ( isset( $settings->show_on ) && $settings->show_on === 'only_shortcode' ) {
            return false;
        }
        return $type;
    }

    /**
     * this method is responsible for output the shortcode.
     *
     * @param array $atts
     */
    public function shortcode( $atts, $content = null ) {
        $atts = shortcode_atts( array(
            'id' => '',
            ), $atts, 'notificationx'
        );

        if ( empty( $atts['id'] ) ) {
            if ( ! current_user_can( 'administrator' ) ) {
                return;
            }
            return '<p class="nx-shortcode-notice">' . __( 'You have to give an ID to generate notification.', 'notificationx-pro' ) . '</p>';
        }

        if ( ! PostType::get_instance()->is_enabled( $atts['id'] ) ) {
            if ( ! current_user_can( 'administrator' ) ) {
                return;
            }
            return '<p class="nx-shortcode-notice">' . __( 'Make sure you have enabled the notification which ID you have given.', 'notificationx-pro' ) . '</p>';
        }

        $settings        = PostType::get_instance()->get_post( $atts['id'] );
        $logged_in       = is_user_logged_in();
        $show_on_display = $settings['show_on_display'];
        if ( ( $logged_in && 'logged_out_user' == $show_on_display ) || ( ! $logged_in && 'logged_in_user' == $show_on_display ) ) {
            return;
        }

        $this->shortcode_nx_ids[] = $atts['id'];
        $output                   = "<div id='notificationx-shortcode-{$atts['id']}' class='notificationx-shortcode-wrapper nx-shortcode-notice'></div>";
        return $output;
    }

    public function add_shortcode_ids($data){
        if(!empty($this->shortcode_nx_ids)){
            $data['shortcode'] = $this->shortcode_nx_ids;
            $this->shortcode_nx_ids = null;
        }
        return $data;
    }

    public function footer_scripts() {
        if ( ! empty( $this->shortcode_nx_ids ) ) {
            $notificationX = [
                'global'    => [],
                'active'    => [],
                'shortcode' => [],
                'pressbar'  => [],
                'rest'      => REST::get_instance()->rest_data(),
            ];
            $notificationX = apply_filters( 'nx_frontend_localize_data', $notificationX );
            do_action( 'notificationx_scripts', $notificationX );
            wp_print_scripts( 'notificationx-public' );
            wp_print_styles( 'notificationx-public' );
            wp_print_scripts( 'notificationx-pro-public' );
            wp_print_styles( 'notificationx-pro-public' );
            ?>
            <script data-no-optimize="1">
                (function(){
                    window.notificationXArr = window.notificationXArr || [];
                    window.notificationXArr.push(<?php echo json_encode( $notificationX ); ?>);
                })();
            </script>
            <?php
        }
    }

}
