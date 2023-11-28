<?php
/*
 * Transactions list page
 *  All undefined vars comes from 'render_better_payment_admin_pages' method
 *  $bp_admin_all_transactions : contains all values
 */

use Better_Payment\Lite\Admin\Better_Payment_DB;
use Better_Payment\Lite\Classes\Better_Payment_Helper;

?>

<?php
$bp_admin_settings_total_transactions_count = isset($bp_admin_all_transactions_analytics['total_transactions']) ? $bp_admin_all_transactions_analytics['total_transactions'] : 0;
$bp_admin_settings_completed_transactions_count = isset($bp_admin_all_transactions_analytics['completed_transactions']) ? $bp_admin_all_transactions_analytics['completed_transactions'] : 0;
$bp_admin_settings_incomplete_transactions_count = isset($bp_admin_all_transactions_analytics['incomplete_transactions']) ? $bp_admin_all_transactions_analytics['incomplete_transactions'] : 0;

$better_payment_db_obj = new Better_Payment_DB();
$better_payment_helper_obj = new Better_Payment_Helper();

$better_payment_allowed_statuses = (new Better_Payment_DB())->allowed_statuses('all','v2');
$search_text = $args['search_text'] ? $args['search_text'] : '';
?>

<div class="better-payment">
    <div class="template__wrapper background__grey better-payment-admin-transactions-page">
        <header class="pb30">
            <div class="bp-container">
                <div class="bp-row">
                    <div class="bp-col-9">
                        <div class="logo">
                            <a href="javascript:void(0)"><img src="<?php echo esc_url(BETTER_PAYMENT_ASSETS . '/img/logo.svg'); ?>" alt=""></a>
                        </div>
                    </div>

                    <div class="bp-col-3">
                        <p class="has-text-right">
                            <button class="button better-payment-transaction-export button__active"> <?php _e('Export All', 'better-payment'); ?> </button>
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <div class="bp-container">
            <div class="bp-row">
                <div class="bp-col-lg-4 bp-col-sm-6 bp-col">
                    <div class="statistic">
                        <div class="icon">
                            <i class="bp-icon bp-swap"></i>
                        </div>
                        <div class="statistic__body">
                            <h3 class="bp-stat-all-transactions-count"><?php _e($bp_admin_all_transactions_count, 'better-payment'); ?></h3>
                            <p><?php _e('Total Transactions', 'better-payment'); ?></p>
                            <p class="is-hidden" title="<?php esc_attr_e(sprintf('Transaction statuses: %s', $better_payment_helper_obj->arrayToString( $better_payment_db_obj->allowed_statuses('all', 'v2'), ', ', ', NULL' )), 'better-payment');  ?>"></p>
                        </div>
                    </div>
                </div>
                <div class="bp-col-lg-4 bp-col-sm-6 bp-col">
                    <div class="statistic">
                        <div class="icon">
                            <i class="bp-icon bp-list-check"></i>
                        </div>
                        <div class="statistic__body">
                            <h3 class="bp-stat-completed-transactions-count"><?php _e($bp_admin_completed_transactions_count, 'better-payment'); ?></h3>
                            <p><?php _e('Completed Transactions', 'better-payment'); ?></p>
                            <p class="is-hidden" title="<?php esc_attr_e(sprintf('Transaction statuses: %s', $better_payment_helper_obj->arrayToString( $better_payment_db_obj->allowed_statuses('completed', 'v2') )), 'better-payment');  ?>" ></p>
                        </div>
                    </div>
                </div>
                <div class="bp-col-lg-4 bp-col-sm-6 bp-col">
                    <div class="statistic">
                        <div class="icon">
                            <i class="bp-icon bp-server"></i>
                        </div>
                        <div class="statistic__body">
                            <h3 class="bp-stat-incomplete-transactions-count"><?php _e($bp_admin_incomplete_transactions_count, 'better-payment'); ?></h3>
                            <p ><?php _e('Incomplete Transactions', 'better-payment'); ?></p>
                            <p class="is-hidden" title="<?php esc_attr_e(sprintf('Transaction statuses: %s', $better_payment_helper_obj->arrayToString( $better_payment_db_obj->allowed_statuses('incomplete', 'v2'), ', ', ', NULL' )), 'better-payment');  ?>" ></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bp-row">
                <div class="bp-col">
                    <div class="transaction-filter-wrapper transactions fix-style">
                        <div class="transaction__filter">
                            <form action="#" id="better-payment-admin-settings-form">
                                <div class="mb20">
                                    <label class="is-hidden" for="#"><?php _e('Search', 'better-payment'); ?></label>
                                    <div class="form__group">
                                        <input name="search_text" type="text" class="form__control serch-text" placeholder="<?php _e('Search', 'better-payment'); ?>" value="<?php echo isset($search_text) ? esc_attr($search_text) : '' ?>" title="<?php _e('Search by email, amount, transaction id, source', 'better-payment'); ?>" >
                                    </div>
                                </div>
                                <div class="mb20">
                                    <label class="is-hidden" for="#"> <?php _e('From Date', 'better-payment'); ?> </label>
                                    <div class="form__group">
                                        <input name="payment_date_from1" type="text" class="form__control payment_date_from bp-datepicker1 bp_time_period-custom-range" placeholder="<?php _e('Date Range', 'better-payment'); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb20 is-hidden">
                                    <label class="is-hidden" for="#"> <?php _e('To Date', 'better-payment'); ?> </label>
                                    <div class="form__group is-hidden">
                                        <input name="payment_date_to1" type="text" class="form__control payment_date_to bp-datepicker1" placeholder="<?php _e('To Date', 'better-payment'); ?>">
                                    </div>
                                </div>

                                <div class="mb20">
                                    <label class="is-hidden" for="#"> <?php _e('Status', 'better-payment'); ?> </label>
                                    <div class="bp-select fix-style">
                                        <select name="status1" class="status1 is-hidden">
                                            <option value="all" <?php echo $transaction_pagination_status === 'all' ? 'selected' : ''; ?> > <?php _e('All', 'better-payment'); ?> </option>
                                            <?php foreach($better_payment_allowed_statuses as $better_payment_allowed_status): ?>
                                                <?php 
                                                $better_payment_allowed_status = NULL === $better_payment_allowed_status ? 'null' : $better_payment_allowed_status;
                                                if(
                                                    'completed' === $better_payment_allowed_status ||
                                                    'pending' === $better_payment_allowed_status
                                                ){
                                                    continue;
                                                }
                                                ?>
                                                <option value="<?php esc_attr_e($better_payment_allowed_status); ?>" <?php echo $transaction_pagination_status === $better_payment_allowed_status ? 'selected' : ''; ?> > <?php _e( ('null' === $better_payment_allowed_status) ? 'N/A' : strtoupper($better_payment_allowed_status), 'better-payment'); ?> </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <select name="status1" class="status1 is-hidden">
                                            <option value="" selected disabled> <?php _e('Status', 'better-payment'); ?> </option>
                                            <option value="all" <?php echo $transaction_pagination_status === 'all' ? 'selected' : ''; ?> > <?php _e('All', 'better-payment'); ?> </option>
                                            <option value="completed" <?php echo $transaction_pagination_status === 'completed' ? 'selected' : ''; ?> > <?php _e('Completed', 'better-payment'); ?> </option>
                                            <option value="incomplete" <?php echo $transaction_pagination_status === 'incomplete' ? 'selected' : ''; ?> > <?php _e('Incomplete', 'better-payment'); ?> </option>
                                            <option value="refunded" <?php echo $transaction_pagination_status === 'refunded' ? 'selected' : ''; ?> > <?php _e('Refunded', 'better-payment'); ?> </option>
                                            
                                        </select>
                                        <?php 
                                            $data = array();
                                            $data['inputFieldName'] = 'status';
                                            $data['inputFieldLabel'] = 'Status';
                                            $data['dropdownDefaultValue'] = 'all';
                                            $data['dropdownItems'] = array(
                                                    'all' => 'All',
                                                    'completed' => 'Completed',
                                                    'incomplete' => 'Incomplete',
                                                    'refunded' => 'Refunded',
                                                );
                                        ?>
                                        <?php $better_payment_helper_obj->bp_template_render(BETTER_PAYMENT_ADMIN_VIEWS_PATH . "/partials/custom-dropdown.php", $data); ?>

                                    </div>
                                </div>
                                
                                <div class="mb20">
                                    <label class="is-hidden" for="#"><?php _e('Sort By', 'better-payment'); ?></label>
                                    <div class="bp-select">
                                        <select name="order_by1" class="order-by1 is-hidden">
                                            <option value="" selected disabled> <?php _e('Sort By', 'better-payment'); ?> </option>
                                            <option value="payment_date" <?php ( $transaction_pagination_orderby === 'payment_date' || $transaction_pagination_orderby === 'id' ) ? 'selected' : ''; ?> > <?php _e('Payment Date', 'better-payment'); ?> </option>
                                            <option value="email" <?php $transaction_pagination_orderby === 'email' ? 'selected' : ''; ?> > <?php _e('Email Address', 'better-payment'); ?> </option>
                                            <option value="amount" <?php $transaction_pagination_orderby === 'amount' ? 'selected' : ''; ?> > <?php _e('Amount', 'better-payment'); ?> </option>
                                            <!-- <option value="status" <?php //$transaction_pagination_orderby === 'status' ? 'selected' : ''; ?> > <?php //_e('Status', 'better-payment'); ?> </option> -->
                                        </select>

                                        <?php 
                                            $data = array();
                                            $data['inputFieldName'] = 'order_by';
                                            $data['inputFieldLabel'] = 'Order By';
                                            $data['dropdownDefaultValue'] = '';
                                            $data['dropdownItems'] = array(
                                                    'payment_date' => 'Payment Date',
                                                    'email' => 'Email',
                                                    'amount' => 'Amount',
                                                );
                                        ?>
                                        <?php $better_payment_helper_obj->bp_template_render(BETTER_PAYMENT_ADMIN_VIEWS_PATH . "/partials/custom-dropdown.php", $data); ?>
                                    </div>
                                </div>
                                <div class="mb20">
                                    <label class="is-hidden" for="#"> <?php _e('Sort Order', 'better-payment'); ?> </label>
                                    <div class="bp-select">
                                        <select name="order1" class="order1 is-hidden">
                                            <option value="" selected disabled> <?php _e('Sort Order', 'better-payment'); ?> </option>
                                            <option value="DESC" <?php echo $transaction_pagination_order === 'DESC' ? 'selected' : ''; ?> > <?php _e('Descending', 'better-payment'); ?> </option>
                                            <option value="ASC" <?php echo $transaction_pagination_order === 'ASC' ? 'selected' : ''; ?> > <?php _e('Ascending', 'better-payment'); ?> </option>
                                        </select>

                                        <?php 
                                            $data = array();
                                            $data['inputFieldName'] = 'order';
                                            $data['inputFieldLabel'] = 'Order';
                                            $data['dropdownDefaultValue'] = '';
                                            $data['dropdownItems'] = array(
                                                    'DESC' => 'Descending',
                                                    'ASC' => 'Ascending',
                                                );
                                        ?>
                                        <?php $better_payment_helper_obj->bp_template_render(BETTER_PAYMENT_ADMIN_VIEWS_PATH . "/partials/custom-dropdown.php", $data); ?>
                                    </div>
                                </div>
                                <div class="mb20">
                                    <label class="is-hidden" for="#"> <?php _e('Source', 'better-payment'); ?> </label>
                                    <div class="bp-select">
                                        <select name="source1" class="source1 is-hidden">
                                            <option value="" selected disabled> <?php _e('Source', 'better-payment'); ?> </option>
                                            <option value="paypal" <?php echo $transaction_pagination_source === 'paypal' ? 'selected' : ''; ?> > <?php _e('PayPal', 'better-payment'); ?> </option>
                                            <option value="stripe" <?php echo $transaction_pagination_source === 'stripe' ? 'selected' : ''; ?> > <?php _e('Stripe', 'better-payment'); ?> </option>
                                        </select>

                                        <?php 
                                            $data = array();
                                            $data['inputFieldName'] = 'source';
                                            $data['inputFieldLabel'] = 'Source';
                                            $data['dropdownDefaultValue'] = '';
                                            $data['dropdownItems'] = array(
                                                    'paypal' => 'PayPal',
                                                    'stripe' => 'Stripe',
                                                );
                                        ?>
                                        <?php $better_payment_helper_obj->bp_template_render(BETTER_PAYMENT_ADMIN_VIEWS_PATH . "/partials/custom-dropdown.php", $data); ?>
                                    </div>
                                </div>
                                <button class="button button__active better-payment-transaction-filter"> <?php _e('Filter', 'better-payment'); ?> </button>
                                <button class="button better-payment-transaction-reset"> <?php _e('Reset', 'better-payment'); ?> </button>

                                <div class="bp-modal">
                                    <div class="modal bp-custom-time-period">
                                        <div class="modal-background"></div>
                                        <div class="modal-card">
                                            <header class="modal-card-head">
                                                <p class="modal-card-title is-size-5"><?php esc_html_e('Custom Date Range', 'better-payment'); ?></p>
                                                <button class="delete" aria-label="close"></button>
                                            </header>
                                            <section class="modal-card-body">
                                                <div class="columns">
                                                    <div class="column">
                                                        <div class="columns">
                                                            <div class="column">
                                                                <p class="is-size-6"><?php esc_html_e('Start Date', 'better-payment'); ?></p>
                                                            </div>

                                                            <div class="column is-three-quarters">
                                                                <input class="form__control email-recipient-field bp-datepicker bp-time-period-start-date-custom" type="text" name="payment_date_from" value="">
                                                                <small class="email-recipient-field-note"><?php esc_html_e('Select start date of desired time period to see the analytics.', 'better-payment'); ?></small>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="column">
                                                        <div class="columns">
                                                            <div class="column">
                                                                <p class="is-size-6"><?php esc_html_e('End Date', 'better-payment'); ?></p>
                                                            </div>

                                                            <div class="column is-three-quarters">
                                                                <input class="form__control email-recipient-field bp-datepicker bp-time-period-end-date-custom" type="text" name="payment_date_to" value="">
                                                                <small class="email-recipient-field-note"><?php esc_html_e('Select end date of desired time period to see the analytics.', 'better-payment'); ?></small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </section>
                                            <footer class="modal-card-foot">
                                                <button class="button cancel-button mr-1 fix-common mb-0"><?php esc_html_e('Confirm', 'better-payment'); ?></button>
                                                <button class="button cancel-button fix-common mb-0"><?php esc_html_e('Cancel', 'better-payment'); ?></button>
                                            </footer>
                                        </div>
                                    </div>
                                </div>
                                
                            </form>
                        </div>
                    </div>

		            <?php include_once BETTER_PAYMENT_ADMIN_VIEWS_PATH . "/template-transaction-list.php"; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>