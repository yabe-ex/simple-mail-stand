<?php

/**
 * Plugin Name: シンプルメールスタンド
 * Plugin URI:
 * Description: シンプルなメール配信プラグインです。
 * Version: 1.0.2
 * Author: yabea
 * Author URI:
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) exit();

$info = get_file_data(__FILE__, array('plugin_name' => 'Plugin Name', 'version' => 'Version'));

define('SIMPLE_MAIL_STAND_URL', plugins_url('', __FILE__));  // http(s)://〜/wp-content/plugins/simple-mail-stand（URL）
define('SIMPLE_MAIL_STAND_PATH', dirname(__FILE__));         // /home/〜/wp-content/plugins/simple-mail-stand (パス)
define('SIMPLE_MAIL_STAND_NAME', $info['plugin_name']);
define('SIMPLE_MAIL_STAND_SLUG', 'simple-mail-stand');
define('SIMPLE_MAIL_STAND_PREFIX', 'simple_mail_stand_');
define('SIMPLE_MAIL_STAND_VERSION', $info['version']);
define('SIMPLE_MAIL_STAND_DEVELOP', true);

class SimpleMailStand {
    public function init() {
        // 管理画面側の処理
        require_once SIMPLE_MAIL_STAND_PATH . '/inc/class-admin.php';
        $admin = new SimpleMailStandAdmin();

        add_action('admin_menu', array($admin, 'create_menu'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($admin, 'plugin_action_links'));
        add_action('admin_enqueue_scripts', array($admin, 'admin_enqueue'));
        add_action('init', array($admin, 'register_post_type'));
        // add_action('admin_menu', array($admin, 'my_mail_magazine_add_settings_page'));
        register_activation_hook(__FILE__, array('SimpleMailStandAdmin', 'create_table'));
        add_action('wp_ajax_change_subscribe_status', array($admin, 'change_subscribe_status'));

        add_action('add_meta_boxes', array($admin, 'add_meta_boxes'));
        add_action('save_post', array($admin, 'save_delivery_meta'));
        add_action('hook_newsletter_delivery', array($admin, 'exec_newsletter_delivery'));



        // フロントエンドの処理
        require_once SIMPLE_MAIL_STAND_PATH . '/inc/class-front.php';
        $front = new SimpleMailStandFront();

        add_action('wp_enqueue_scripts', array($front, 'front_enqueue'));
        add_shortcode('my_subscribe_form', array($front, 'my_subscribe_form'));
        add_action('wp_ajax_subscribe_email', array($front, 'subscribe_email'));
        add_action('wp_ajax_nopriv_subscribe_email', array($front, 'subscribe_email'));
        add_shortcode('my_unsubscribe_form', array($front, 'my_unsubscribe_form'));
        add_action('wp_ajax_unsubscribe_email', array($front, 'unsubscribe_email'));
        add_action('wp_ajax_nopriv_unsubscribe_email', array($front, 'unsubscribe_email'));
    }
}

$instance = new SimpleMailStand();
$instance->init();
