<style>
    * {
        padding: 0;
        margin: 0;
        box-sizing: border-box;
    }

    .bp__email__template__footer {
        max-width: 640px;
        margin: 30px auto;
        text-align: center;
    }

    .bp__email__template__footer ul {
        padding: 0;
        margin: 0;
        margin-bottom: 20px;
        list-style: none;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .bp__email__template__footer ul li:not(:last-child) {
        margin-right: 10px;
    }

    .bp__email__template__footer ul li a {
        height: 40px;
        width: 40px;
        border-radius: 50%;
        background: #5f5f5f;
        color: #fff;
        line-height: 42px;
        text-align: center;
        display: inline-block;
        text-decoration: none;
    }

    .bp__email__template__footer ul li a.icon__facebook {
        background: #1877f2;
    }

    .bp__email__template__footer ul li a.icon__twitter {
        background: #1da1f2;
    }

    .bp__email__template__footer ul li a.icon__youtube {
        background: #ff0000;
    }

    .bp__email__template__footer ul li a.icon__wordpress {
        background: #0087be;
    }

    .bp__email__template__footer ul li a.icon__github {
        background: #4078c0;
    }
</style>
<div class="bp__email__template" style="width: 60%;margin:auto;
        background-color: #f3f3f3;
        padding: 50px;
        min-height: 100vh;
        font-family: 'DM Sans', sans-serif;
">
    <div class="bp__email__template__body" style="
        max-width: 640px;
        margin: 0 auto 30px;
        background: #fff;
        border-radius: 5px;
        box-shadow: 0 0 0 20px rgba(0, 0, 0 0, 1);
        padding: 30px;
    ">
        <table style="width: 100%;background: #fff;">
            <tr>
                <td>
                    <p style="text-align: center;padding-top: 10px;
                    color: #9095A2;
                    margin-bottom: 10px;
                    font-size: 14px;
                    line-height: 1.7;
                    ">
                        <br><img src="<?php echo esc_url(BETTER_PAYMENT_ASSETS . '/img/logo.svg'); ?>" alt="Better Payment">
                    </p>
                </td>
                <td>

                </td>
            </tr>
            <tr>
                <td>
                    <?php if($type === 'customer') : ?>
                    <p style="color: #222;"> <?php printf('Dear %s,', $customer_name) ?> </p>
                    <p> <?php printf('This is to acknowledge that we have received the payment of %s %s on %s <br><br>Here\'s your transaction ID: <b>%s</b>', esc_html($amount), esc_html($currency), esc_html($payment_date), esc_html($transaction_id) ); ?></p>
                    <?php else : ?>
                    <p style="color: #222;"> <?php _e('Howdy there,', 'better-payment') ?> <span class="username" style="display: none;"> <?php _e('Admin', 'better-payment') ?> </span></p>
                    <p> <?php _e('Great News! You have received a new transaction through Better Payment', 'better-payment') ?> 🎉.</p>
                    <?php endif; ?>    

                    <?php if( !$is_empty_email_body && $type !== 'customer' ) : ?>
                    <h4 style="margin-bottom: 15px;font-weight:normal;"> <?php _e('Below is the Transaction Summary:', 'better-payment') ?> </h4>
                    <?php endif; ?>   

                    <?php if($type === 'admin') : ?>
                    <?php echo wp_kses( $bp_form_fields_html_content, wp_kses_allowed_html( 'post' ) ); ?>
                    <?php endif; ?>    
                </td>
            </tr>
        </table>
        <div style="text-align: center;display:none;">
            <p style="margin-bottom: 0;"> <?php _e('You can also find the transaction details visiting below link.', 'better-payment') ?> </p>
            <a href="<?php echo esc_url($referer_content_page_link); ?>" class="button"
            style="text-align: center;
        padding: 12px 30px;
        line-height: 1;
        color: #fff;
        border: 1px solid transparent;
        background: #6E58F7;
        font-size: 14px;
        font-weight: 500;
        font-family: 'DM Sans', sans-serif;
        overflow: hidden;
        z-index: 1;
        border-radius: 10px;
        display: inline-block;
        text-decoration: none;
        margin-top: 15px;"
            > <?php _e('View Transaction', 'better-payment') ?> </a>
        </div>
    </div>
    <div class="bp__email__template__footer">
        <p style="margin-bottom: 0;
        color: #9095A2;
        font-size: 14px;
        line-height: 1.7;
        display:none;
        "> <?php _e('@Better Payment. Pay Easy, Pay Safe!', 'better-payment') ?> </p>
        <p style="margin-bottom: 20px;
        color: #9095A2;
        font-size: 14px;
        line-height: 1.7;
        display:none;
        "> <?php _e('Feel free to suggest us and make Better Payment even better', 'better-payment') ?> &#128525;</p>
        <p style="text-align: center;padding-top: 10px;
        color: #9095A2;
        margin-bottom: 10px;
        font-size: 14px;
        line-height: 1.7;
        display:none;
        "> <?php _e('Powered By', 'better-payment') ?>
            <br><img src="<?php echo esc_url(BETTER_PAYMENT_ASSETS . '/img/logo.svg'); ?>" alt="Better Payment">
        </p>
    </div>
</div>