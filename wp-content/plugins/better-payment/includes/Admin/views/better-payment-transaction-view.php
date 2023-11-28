<?php
/*
 * Transaction details page
 *  All undefined vars comes from 'render_better_payment_admin_pages' method
 *  $bp_admin_all_transactions : contains all values
 */

use Better_Payment\Lite\Classes\Better_Payment_Helper;

?>

<div class="better-payment">

    <?php if (is_object($bp_admin_transaction)) : ?>
        <section class="transaction-details-wrapper content">

            <!-- Hidden Fields Start -->
            <div class="hidden-fields">
                <input type="hidden" name="transaction_details_id" value="<?php echo esc_attr($bp_admin_transaction->id); ?>">
            </div>
            <!-- Hidden Fields End  -->


            <div class="template__wrapper background__grey">
                <header class="pb30">
                    <div class="bp-container">
                        <div class="bp-row">
                            <div class="bp-col">
                                <div class="logo">
                                    <a href="javascript:void(0)"><img src="<?php echo esc_url(BETTER_PAYMENT_ASSETS . '/img/logo.svg'); ?>" alt=""></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <div class="bp-container">
                    <div class="bp-row">
                        <div class="bp-col">
                            <div class="page__title mb30">
                                <span><a class="single-transaction-view-back-btn" href="<?php echo esc_url(admin_url("admin.php?page=better-payment-transactions")); ?>"><i class="bp-icon bp-left-arrow-lite single-transaction-view"></i></a> <?php _e('Back to Transactions', 'better-payment'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bp-container">
                    <div class="bp-row">
                        <div class="bp-col-lg-8 bp-col md-mb30">
                            <div class="transaction__info__wrap">
                                <?php
                                $form_fields_info = maybe_unserialize($bp_admin_transaction->form_fields_info);

                                $td_first_name = (isset($form_fields_info['primary_first_name']) && $form_fields_info['primary_first_name']) ? $form_fields_info['primary_first_name'] : __('', 'better-payment');
                                $td_last_name = (isset($form_fields_info['primary_last_name']) && $form_fields_info['primary_last_name']) ? $form_fields_info['primary_last_name'] : __('', 'better-payment');
                                
                                if(empty($td_first_name)){
                                    $td_first_name = (isset($form_fields_info['first_name']) && $form_fields_info['first_name']) ? $form_fields_info['first_name'] : __('', 'better-payment');
                                }

                                if(empty($td_last_name)){
                                    $td_last_name = (isset($form_fields_info['last_name']) && $form_fields_info['last_name']) ? $form_fields_info['last_name'] : __('', 'better-payment');
                                }

                                $td_email = (isset($form_fields_info['primary_email']) && $form_fields_info['primary_email']) ? $form_fields_info['primary_email'] : __('', 'better-payment');
                                if(empty($td_email)){
                                    $td_email = (isset($form_fields_info['email']) && $form_fields_info['email']) ? $form_fields_info['email'] : __('', 'better-payment');
                                }
                                $td_amount = $bp_admin_transaction->amount . ' ' . $bp_admin_transaction->currency;
                                $td_source_image_url = $bp_admin_transaction->source == 'paypal' ? BETTER_PAYMENT_ASSETS . '/img/paypal.png' : BETTER_PAYMENT_ASSETS . '/img/stripe.svg';
                                
                                $td_status = $bp_admin_transaction->status ? $bp_admin_transaction->status : __('N/A', 'better-payment');

                                $bp_transaction_status_for_color = $bp_admin_transaction->status ? sanitize_text_field($bp_admin_transaction->status) : '';
                                $bp_helper_obj = new Better_Payment_Helper();
                                $td_status_btn_color = $bp_helper_obj->get_color_by_transaction_status($bp_transaction_status_for_color, 'v2');
                                $td_status_btn_text_v2 = $bp_helper_obj->get_type_by_transaction_status($bp_transaction_status_for_color, 'v2');

                                //Additional fields:
                                $td_order_id = $bp_admin_transaction->order_id;
                                $td_payment_date = wp_date(get_option('date_format').' '.get_option('time_format'), strtotime($bp_admin_transaction->payment_date));
                                $td_referer = $bp_admin_transaction->referer;
                                $td_obj_id = $bp_admin_transaction->obj_id;

                                //Show widget name and page url
                                $referer_content_form_name = __('N/A', 'better-payment');
                                $referer_content_page_title = __('N/A', 'better-payment');
                                $referer_content_page_link = __('#', 'better-payment');

                                if(!empty($bp_transaction_referer_content)){
                                    $referer_content_form_name = !empty($bp_transaction_referer_content['form_name']) ? $bp_transaction_referer_content['form_name'] : $referer_content_form_name;
                                    $referer_content_form_name =  $td_referer != 'elementor-form' && !empty($bp_transaction_referer_content['better_payment_form_title']) ? $bp_transaction_referer_content['better_payment_form_title'] : $referer_content_form_name;
                                    
                                    $referer_content_page_title = !empty($referer_page_id) ? get_the_title( $referer_page_id ) : $referer_content_page_title;
                                    $referer_content_page_link = !empty($referer_page_id) ? get_permalink( $referer_page_id ) : $referer_content_page_link;
                                }

                                ?>
                                <div class="transaction__info">
                                    <div class="info__header">
                                        <h4 class="title"><i class="bp-icon bp-info"></i> <?php _e('Basic Information', 'better-payment'); ?></h4>
                                    </div>
                                    <ul class="informations">
                                        <li><span><?php _e('First Name:', 'better-payment'); ?></span> <?php echo esc_html($td_first_name) ?> </li>
                                        <li><span><?php _e('Last Name:', 'better-payment'); ?></span> <?php echo esc_html($td_last_name) ?> </li>
                                        <li><span><?php _e('Email Address:', 'better-payment'); ?></span> <?php echo esc_html($td_email) ?> </li>
                                        <li><span><?php _e('Amount:', 'better-payment'); ?></span> <?php echo esc_html($td_amount) ?> </li>
                                        <li>
                                            <?php 
                                                $bp_transaction_id = sanitize_text_field($bp_admin_transaction->transaction_id);
                                                $bp_txn_counter = 1;  
                                            ?>
                                            <?php if( !empty($bp_transaction_id) ) : ?>
                                                <span><?php _e('Transaction ID:', 'better-payment'); ?></span> <span id="bp_copy_clipboard_input_<?php echo esc_attr($bp_txn_counter); ?>"><?php echo esc_html($bp_transaction_id); ?></span> <span id="bp_copy_clipboard_<?php echo esc_attr($bp_txn_counter); ?>" class="bp-icon bp-copy-square bp-copy-clipboard" title="<?php _e('Copy', 'better-payment'); ?>" data-bp_txn_counter="<?php echo esc_attr($bp_txn_counter); ?>" ></span>
                                            <?php endif; ?>
                                        </li>
                                        <li><span><?php _e('Source:', 'better-payment'); ?></span> <img src="<?php echo esc_url($td_source_image_url) ?>" alt=""></li>
                                        <li><span><?php _e('Status:', 'better-payment'); ?></span> <span style="color:#fff; padding:5px 15px; border-radius: 20px;background: <?php echo esc_attr($td_status_btn_color); ?>"> <?php echo esc_html(ucwords($td_status_btn_text_v2)); //$td_status ?> </span></li>
                                    </ul>
                                </div>
                                <div class="transaction__info">
                                    <div class="info__header">
                                        <h4 class="title"><i class="bp-icon bp-info"></i> <?php _e('Additional Information', 'better-payment'); ?></h4>
                                    </div>
                                    <ul class="informations">
                                        <li><span><?php _e('Order ID:', 'better-payment'); ?></span> <?php echo esc_html($td_order_id) ?></li>
                                        <li><span><?php _e('Payment Date:', 'better-payment'); ?></span> <?php echo esc_html($td_payment_date) ?></li>
                                        <li><span><?php _e('Referer Page:', 'better-payment'); ?></span> <a target="_blank" class="color__themeColor" href="<?php echo esc_url($referer_content_page_link) ?>"><?php echo esc_html($referer_content_page_title); ?></a> </li>
                                        <li class="is-hidden"><span><?php _e('Referer Widget:', 'better-payment'); ?></span> <?php echo esc_html($referer_content_form_name) ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="bp-col-lg-4 bp-col">
                            <div class="payment__info">
                                <div class="payment__getway">
                                    <h4 class="title"><i class="bp-icon bp-card"></i> <?php _e('Payment Gateway', 'better-payment'); ?></h4>
                                    <div class="pay__by">
                                        <?php $paid_via_paypal_selected = $bp_admin_transaction->source == 'paypal' ? 'paid_via_selected' : ''; ?>
                                        <?php $paid_via_stripe_selected = $bp_admin_transaction->source == 'stripe' ? 'paid_via_selected' : ''; ?>
                                        
                                        <?php if($bp_admin_transaction->source == 'paypal'): ?>
                                        <label class="<?php echo esc_attr($paid_via_paypal_selected); ?>">
                                            <?php _e('Payment Method:', 'better-payment'); ?>
                                            <input type="radio" name="payment__getway" <?php echo $bp_admin_transaction->source == 'paypal' ? 'checked' : ''; ?> >
                                            <span><img src="<?php echo esc_url(BETTER_PAYMENT_ASSETS . '/img/paypal.png'); ?>" alt=""></span>
                                        </label>

                                        <?php else : ?>
                                        <label class="<?php echo esc_attr($paid_via_stripe_selected) ?>">
                                            <?php _e('Payment Method:', 'better-payment'); ?>
                                            <input type="radio" name="payment__getway" <?php echo $bp_admin_transaction->source == 'stripe' ? 'checked' : ''; ?>>
                                            <span><img src="<?php echo esc_url(BETTER_PAYMENT_ASSETS . '/img/stripe.svg'); ?>" alt=""></span>
                                        </label>
                                        <?php endif; ?>
                                    </div>
                                    <?php $bp_txn_counter = 2; ?>
                                    <p class="single-transaction-id-copy-wrap"><?php _e('Transaction ID:', 'better-payment'); ?> <span id="bp_copy_clipboard_input_<?php echo esc_attr($bp_txn_counter); ?>" class="bp-text-black"><?php echo esc_html($bp_transaction_id); ?></span><span id="bp_copy_clipboard_<?php echo esc_attr($bp_txn_counter); ?>" class="bp-icon bp-copy-square bp-copy-clipboard has-text-black-fix" title="<?php _e('Copy', 'better-payment'); ?>" data-bp_txn_counter="<?php echo esc_attr($bp_txn_counter); ?>" ></span></p>
                                </div>
                                <div class="email__activity">
                                    <h4 class="title"><i class="bp-icon bp-wave"></i> <?php _e('Email Activity', 'better-payment'); ?></h4>
                                    <ul class="activity__list">
                                        <li>
                                            <div class="content">
                                                <i class="bp-icon bp-mail"></i>
                                                <h5><?php _e('Email sent to', 'better-payment'); ?> <?php echo esc_html($td_email); ?></h5>
                                                <p><?php echo esc_html($td_payment_date) ?></p>
                                            </div>
                                            <div class="action">
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                                <?php do_action('better_payment/admin/transaction_refund_content', $bp_admin_transaction); ?>  
                                <?php do_action('better_payment/admin/transaction_receipt_content', $bp_admin_transaction); ?>  
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    <?php else : ?>
        <p><?php echo __('No records found!', 'better-payment'); ?></p>
    <?php endif; ?>

</div>