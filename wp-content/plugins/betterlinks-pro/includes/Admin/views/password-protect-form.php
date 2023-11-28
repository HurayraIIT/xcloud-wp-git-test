<?php
    /*
    * Template Name: Password Protection Template
    * Template Post Type: page
    */
    $short_url = !empty( $_GET['short_url'] ) ? sanitize_text_field( $_GET['short_url'] ) : null;

    if( !$short_url ) {
        wp_redirect(site_url(). '/404.php');
        exit();
    }

    if( defined('BETTERLINKS_ASSETS_URI') ){
        wp_enqueue_style( 'betterlinks-quil-editor', BETTERLINKS_ASSETS_URI . 'css/ql-editor.css', false );
    }
    wp_enqueue_style( 'betterlinks-password-form', BETTERLINKS_PRO_ASSETS_URI . 'css/betterlinks-pass-protected-form.css', false );

    
    $error_message = '';
    $is_visitor_allowed_for_contact = 0;
    $allow_contact_text = '';
    
    // 👇 fetching specific link information
    $Utils = new BetterLinks\Link\Utils;
    $data = $Utils->get_slug_raw($short_url);
    $id = isset( $data['ID'] ) ? $data['ID'] : null;

    if( !$id ) {
        wp_redirect(site_url(). '/404.php');
        exit();
    }

    if (empty($data['target_url']) || !apply_filters('betterlinks/pre_before_redirect', $data)) {
        return false;
    }
    $data = apply_filters('betterlinks/link/before_dispatch_redirect', $data);


    $form_title = '';
    $show_protected_url = 0;
    $button_text = 'Submit';
    $button_bg_color = '';
    $form_template = 'one';

    if( class_exists( '\BetterLinksPro\Link' ) ) {
        $password_protection_status = \BetterLinksPro\Link::get_password_protection_status();

        
        if( !empty( $password_protection_status['enable_advanced_password_form_style'] ) ) {
            if( !empty( $password_protection_status['allow_visitor_contact'] ) ) {
                $is_visitor_allowed_for_contact = \BetterLinksPro\Helper::check_is_visitor_allowed_in_password_protection($id);
                $allow_contact_text = isset( $password_protection_status['allow_contact_text'] ) ? $password_protection_status['allow_contact_text'] : '';
            }
    
            $form_title = (!empty( $password_protection_status['enable_form_title'] ) && !empty($password_protection_status['form_title'])) ? $password_protection_status['form_title'] : '';
            $show_protected_url = !empty( $password_protection_status['show_protected_url'] ) ? '1' == $password_protection_status['show_protected_url'] : 0;
            $button_text = (!empty($password_protection_status['button_text'])) ? sanitize_text_field($password_protection_status['button_text']) : 'Submit';
            $button_bg_color = (!empty($password_protection_status['button_bg_color'])) ? sanitize_text_field($password_protection_status['button_bg_color']) : '';
            $form_template = (!empty($password_protection_status['form_template'])) ? sanitize_text_field($password_protection_status['form_template']) : 'one';
        }
        
        $cookie_name = "betterlinks_pass_protect_{$id}";
        if( !empty( $password_protection_status['remember_password_cookies'] ) && isset( $_COOKIE[$cookie_name] ) && class_exists('\BetterLinksPro\Helper')) {
            $result = \BetterLinksPro\Helper::check_password($_COOKIE[$cookie_name], $id);

            if( $result ) {
                $Utils->dispatch_redirect($data, $short_url);
                return;
            }
        }
    }
    
    if( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
        $password = isset( $_POST['password'] ) ? sanitize_text_field($_POST['password']) : '';
        
        if( class_exists('\BetterLinksPro\Helper')  ) {
            $result = \BetterLinksPro\Helper::check_password($password, $id);
            
            if( $result ) {
                // 👇 set cookies 
                if( !empty( $password_protection_status['remember_password_cookies'] ) ) {
                    $cookie_expiration_time = isset($password_protection_status['cookie_expiration_time']) ? (int)$password_protection_status['cookie_expiration_time'] : 0;
                    $cookie_value = $password;
                    setcookie($cookie_name, $cookie_value, time() + $cookie_expiration_time, "/"); 
                }

                $Utils->dispatch_redirect($data, $short_url);
            }else {
                $error_message = esc_html__('Password doesn\'t match.', 'betterlinks-pro');
            }
        }
    }
    

?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <?php wp_head(); ?>
    <style>
        .password-protection-form-container .btl-password-container .submit-button{
            background-color: <?php echo esc_attr($button_bg_color); ?>;
        }
        .password-protection-form-container .btl-password-container .input-container input[type='password'] {
            border-color: <?php echo esc_attr($button_bg_color); ?>;
        }
    </style>
</head>
<body>
    <div class="password-protection-form-container btl-layout-<?php esc_html_e($form_template);?>">
        <div class="btl-form-header ql-editor">
            <h4><?php echo $form_title ?></h4>
            <?php
                if( $show_protected_url ) {
                    ?>
                        <a href="<?php echo site_url(). '/' . $data['short_url'] ?>" class="btl-protected-url">
                            <?php echo site_url(). '/' . $data['short_url'] ?>
                        </a>
                    <?php
                }
            ?>
        </div>
    <div class="btl-password-container">
        <form id="password-form" method="post">
            <div class="input-container">
                <input type="password" id="password" name="password" placeholder="<?php echo esc_html__('Password', 'betterlinks-pro') ?>" required autofocus>
            </div>
            <button type="submit" class="submit-button"><?php echo esc_html__($button_text, 'betterlinks-pro'); ?></button>
        </form>
        <div class="error-message" id="error-message" style="display: <?php echo !empty($error_message) ? 'block' : 'none' ?>">
            <?php echo $error_message; ?>
        </div>
    </div>
    <?php 
        if( !empty($is_visitor_allowed_for_contact) ) {
            ?>
                <div class="btl-form-instruction ql-editor">
                    <p><?php echo $allow_contact_text; ?></p>
                </div>
            <?php
        }
    ?>
    </div>


    <script type="text/javascript">
        const passwordField = document.getElementById('password'),
                errorMessage = document.getElementById('error-message');
        
        passwordField.addEventListener('keyup', function(e) {
            if( e.target.value.length == 1 ) {
                errorMessage.style.display = 'none';
            }
        });
    </script>
</body>
</html>
