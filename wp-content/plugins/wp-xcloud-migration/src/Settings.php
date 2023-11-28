<?php

namespace xCloud\MigrationAssistant;

use Exception;

class Settings
{
    function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_xcloud_migration_assistant_settings', array($this, 'save_settings'));
        add_action('admin_post_xcloud_migration_assistant_clear', array($this, 'clear_settings'));
        add_action('admin_enqueue_scripts', array($this, 'register_styles'));
    }

    public function register_styles($page)
    {
        if (str_contains($page, 'xcloud-migration-assistant')) {
            wp_enqueue_style('xcloud-migration-assistant',
                plugins_url('assets/public/css/style.css', __DIR__)
            );
            wp_enqueue_style('xcloud-migration-assistant-font',
                plugins_url('assets/public/x-cloud-icon/style.css', __DIR__)
            );
            wp_enqueue_script('xcloud-migration-assistant-progress-loader',
                plugins_url('assets/public/js/jquery.classyloader.min.js', __DIR__),
                array('jquery'), '1.0.0', true
            );
            wp_enqueue_script('xcloud-migration-assistant',
                plugins_url('assets/public/js/migration_request.js', __DIR__),
                array('jquery'), '1.0.0', true
            );
            wp_enqueue_script('xcloud-migration-assistant-dot-animation',
                plugins_url('assets/public/js/jquery.dotanimation.min.js', __DIR__),
                array('jquery'), '1.0.0', true
            );
            wp_localize_script('xcloud-migration-assistant', 'migration', array_merge(
                ['data' => xCloudOption::get('migration')],
                xCloudOption::requiredDataForProgress()
            ));
        }
    }

    public function add_admin_menu()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        add_menu_page(
            'xCloud Migration',
            'xCloud Migration',
            'manage_options',
            'xcloud-migration-assistant',
            array($this, 'xcloud_migration_assistant_settings_section_html'),
            'dashicons-cloud'
        );
    }

    function save_settings()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!isset($_POST['_nonce']) || !wp_verify_nonce($_POST['_nonce'], 'xcloud_migration_assistant_settings')) {
            wp_nonce_ays('');
        }

        $token = xcloud_migration()->get($_POST, 'xcloud_migration_token');

        if (!$token) {
            xCloudOption::set('settings', [
                'token' => null
            ]);
            $this->back();
        }

        $key = substr($token, 0, 32);
        $actualToken = substr($token, 32);

        try {
            $decodedToken = json_decode((new Encrypter($key))->decrypt($actualToken), true);

            xCloudOption::set('settings', [
                'token' => $token,
                'auth_token' => xcloud_migration()->get($decodedToken, 'auth_token'),
                'encryption_key' => xcloud_migration()->get($decodedToken, 'encryption_key'),
                'site_id' => xcloud_migration()->get($decodedToken, 'site_id'),
            ]);

        } catch (Exception $e) {
            $this->back([
                'error' => $e->getMessage()
            ]);
        }

        $this->back();
    }

    function clear_settings()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!isset($_POST['_nonce']) || !wp_verify_nonce($_POST['_nonce'], 'xcloud_migration_assistant_clear')) {
            wp_nonce_ays('');
        }

        xCloudOption::set('settings', [
            'token' => null,
            'migration' => null
        ]);

        $this->back();
    }

    public function xcloud_migration_assistant_settings_section_html()
    {
        if (!xCloudOption::checkLastUpdateProcessedInTwoHours()) {
            xCloudOption::set('migration', null);
        }

        if (!current_user_can('manage_options')) {
            return;
        } ?>

            <?php if (in_array(xCloudOption::get('migration.state'), ['init', 'migrating', 'filling'])): ?>

            <section class="xc-migrate xc-migrate-2">
                <div class="xc-heading">
                    <canvas class="loader">
                        <img class="xc-heading-logo"
                             src="<?= plugins_url('assets/public/img/progress.svg', __DIR__)?>"
                             alt="xCloud Logo"/>
                    </canvas>
                    <h2 class="xc-section-title"><?= xCloudOption::get('migration.title')?></h2>
                </div>
                <div class="xc-wrapper">
                    <div class="xc-wrapper-container">
                        <div class="xc-wrapper-item">
                            <div class="xc-started-wrapper">
                                <div class="wrapper-details">
                                    <h3 class="sub-heading">
                                        Migration#<?=xCloudOption::get('settings.site_id')?>
                                    </h3>
                                    <div id="started_item" class="started-item">
                                        <?php foreach (xCloudOption::lists() as $key => $list):?>
                                            <h4 data-id="<?= $key ?>"
                                                class="started-heading
                                                <?= $key < xCloudOption::runningTask()['task_index_id'] ? 'done' : null;?>
                                                <?= $key == xCloudOption::runningTask()['task_index_id'] ? 'started' : null;?>">
                                                <?= $list ?>
                                            </h4>
                                            <span data-task="<?= $key ?>" style="font-weight: 300; display: inline; margin-top: 5px; margin-bottom: 10px;">
                                                <span class="status">
                                                    <?php if($key == xCloudOption::runningTask()['task_index_id']):?>
                                                        <?= xCloudOption::runningTask()['tasks'][xCloudOption::get('migration.status')];?>
                                                    <?php endif;?>
                                                </span>
                                                <span data-blinkdot="<?= $key ?>" class="blinkdot" style="display: none"></span>
                                            </span>
                                        <?php endforeach;?>
                                    </div>
                                </div>
                                <div class="wrapper-img">
                                    <img src="<?= plugins_url('assets/public/img/migrate-2.svg', __DIR__)?>"
                                         alt="Migrate IMG"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <?php elseif(xCloudOption::get('migration.state') == 'finished'):?>

            <section class="xc-migrate xc-migrate-3">
                <div class="xc-heading">
                    <h2 class="xc-section-title">Migration Is Successful</h2>
                    <img class="xc-heading-logo"
                         src="<?= plugins_url('assets/public/img/success.svg', __DIR__)?>"
                         alt="xCloud Logo" />
                </div>
                <div class="xc-wrapper">
                    <div class="xc-wrapper-container">
                        <div class="xc-wrapper-item xc-wrapper-item-ex">
                            <h4 class="success-heading">
                                Congratulations! You have successfully migrated your WordPress website.
                            </h4>
                            <p class="success-content">Please check your new website
                                <a href="#">using this temporary URL</a>
                                that will expire in 48 hours. If everything looks good, you can point your domain to our servers.
                            </p>
                            <div class="success-button">
                                <a href="<?= xCloudOption::get('migration.migration_to')?>" class="button button-1">
                                    Check Your Website
                                </a>
                                <form method="post"
                                      action="<?= admin_url('admin-post.php'); ?>?action=xcloud_migration_assistant_clear">
                                    <?php wp_nonce_field('xcloud_migration_assistant_clear', '_nonce'); ?>
                                    <button class="button button-2">
                                        Clear Migration Data
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <?php else: ?>

            <section class="xc-migrate xc-migrate-1">
                <div class="xc-heading">
                    <h2 class="xc-section-title">Migrate With</h2>
                    <img class="xc-heading-logo"
                         src="<?= plugins_url('assets/public/img/logo.svg', __DIR__)?>"
                         alt="xcloud_logo" />
                </div>
                <div class="xc-wrapper">
                    <div class="xc-wrapper-container">
                        <form method="post"
                              action="<?= admin_url('admin-post.php'); ?>?action=xcloud_migration_assistant_settings"
                              class="xc-wrapper-item">
                            <?php wp_nonce_field('xcloud_migration_assistant_settings', '_nonce'); ?>
                            <label class="xc-label">
                                Hi, there! To migrate your website, add your migration token below.
                            </label>
                            <label for="xcloud_migration_token">
                                <textarea
                                    name="xcloud_migration_token"
                                    id="xcloud_migration_token"
                                    class="xc-text-area <?= isset($_GET['error']) ? 'xc-text-area-error' : 'xc-text-area-normal' ?>"
                                    rows="6"
                                    placeholder="Enter your xCloud Token here"
                                    spellcheck="false"><?php echo xCloudOption::get('settings.token')?></textarea>
                            </label>
                            <?php if (xCloudOption::get('settings.token') && !isset($_GET['error'])): ?>
                                <p>
                                    Token has been verified
                                    <?= xCloudOption::get('settings.site_id') ?
                                        ' for <a href="#" target="_blank" 
                                    style="font-weight: bold; cursor: pointer; text-decoration: none">
                                    Migration#'. xCloudOption::get('settings.site_id').'</a>' : null ?>. Please visit xCloud
                                    dashboard to start the Migration process.
                                </p>
                            <?php endif;?>
                            <?php if(isset($_GET['error'])):?>
                                <p style="color: red">
                                    <?= $_GET['error']?>
                                </p>
                            <?php endif;?>
                            <input
                                type="submit"
                                name="submit"
                                id="submit"
                                class="xc-button"
                                value="<?= xCloudOption::get('settings.token') ? 'Update token' : 'Verify token'?>">
                        </form>
                    </div>
                </div>
            </section>
        <?php endif;
    }

    function back(array $params = [])
    {
        $backUrl = add_query_arg($params, admin_url('admin.php?page=xcloud-migration-assistant'));
        wp_redirect($backUrl);
        die;
    }
}