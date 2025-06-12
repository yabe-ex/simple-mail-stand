<?php

class SimpleMailStandAdmin {
    private $settings = array();

    /**
     * コンストラクタ
     * プラグイン設定をプロパティに保持
     */
    function __construct() {
        $default_options = array(
            'sender_name'     => '',
            'sender_email'    => '',
            'signature'       => '',
            'unsubscribe_url' => ''
        );
        $this->settings = get_option(SIMPLE_MAIL_STAND_PREFIX . 'settings', $default_options);
    }

    function create_menu() {
        add_submenu_page(
            'edit.php?post_type=my-mail-magazine',
            'メルマガ設定',
            'メルマガ設定',
            'manage_options',
            'my-mail-magazine-settings',
            array($this, 'show_setting_page'),
            5
        );

        add_submenu_page(
            'edit.php?post_type=my-mail-magazine',
            '登録者一覧',
            '登録者一覧',
            'manage_options',
            'my-mail-magazine-user-list',
            array($this, 'show_user_list_page'),
            4
        );
    }

    function admin_enqueue($hook) {
        if (strpos($hook, 'my-mail-magazine') === false) {
            return;
        }

        $version = (defined('SIMPLE_MAIL_STAND_DEVELOP') && true === SIMPLE_MAIL_STAND_DEVELOP) ? time() : SIMPLE_MAIL_STAND_VERSION;

        wp_register_style(SIMPLE_MAIL_STAND_SLUG . '-admin',  SIMPLE_MAIL_STAND_URL . '/css/admin.css', array(), $version);
        wp_register_script(SIMPLE_MAIL_STAND_SLUG . '-admin', SIMPLE_MAIL_STAND_URL . '/js/admin.js', array('jquery'), $version);

        wp_enqueue_style(SIMPLE_MAIL_STAND_SLUG . '-admin');
        wp_enqueue_script(SIMPLE_MAIL_STAND_SLUG . '-admin');

        $params = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce(SIMPLE_MAIL_STAND_PREFIX)
        );
        wp_localize_script(SIMPLE_MAIL_STAND_SLUG . '-admin', 'admin', $params);
    }

    function plugin_action_links($links) {
        $url = '<a href="' . esc_url(admin_url("/edit.php?post_type=my-mail-magazine&page=my-mail-magazine-settings")) . '">設定</a>';
        array_unshift($links, $url);
        return $links;
    }

    function register_post_type() {
        $args = array(
            'labels' => array(
                'name'          => 'メルマガ',
                'singular_name' => 'メルマガ',
                'add_new_item'  => '新規メルマガを作成',
                'edit_item'     => 'メルマガを編集',
            ),
            'public' => false,
            'show_ui' => true,
            'has_archive' => false,
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-email-alt',
            'show_in_rest' => false, // ブロックエディタを無効化
        );

        register_post_type('my-mail-magazine', $args);
    }

    function show_setting_page() {
        if (isset($_POST['save_settings']) && check_admin_referer(SIMPLE_MAIL_STAND_PREFIX . 'save_settings')) {
            $options = array(
                'sender_name'     => sanitize_text_field($_POST['sender_name']),
                'sender_email'    => sanitize_email($_POST['sender_email']),
                'signature'       => sanitize_textarea_field($_POST['signature']),
                'unsubscribe_url' => sanitize_url($_POST['unsubscribe_url'])
            );

            update_option(SIMPLE_MAIL_STAND_PREFIX . 'settings', $options);

            // プロパティを更新後の設定で上書き
            $this->settings = $options;
            echo '<div class="notice notice-success"><p>設定を保存しました。</p></div>';
        }

        $default_options = array(
            'sender_name'     => '',
            'sender_email'    => '',
            'signature'       => '',
            'unsubscribe_url' => ''
        );

        // $settings = get_option(SIMPLE_MAIL_STAND_PREFIX . 'settings', $default_options);

        $settings = wp_parse_args(
            get_option(SIMPLE_MAIL_STAND_PREFIX . 'settings', array()),
            $default_options
        );


?>
        <div class="wrap">
            <h1>メルマガ設定</h1>
            <form method="POST">
                <?php wp_nonce_field(SIMPLE_MAIL_STAND_PREFIX . 'save_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>差出人名</th>
                        <td><input type="text" name="sender_name" class="regular-text" value="<?php echo esc_attr($settings['sender_name']); ?>" placeholder="例: 山田太郎"></td>
                    </tr>
                    <tr>
                        <th>差出人メールアドレス</th>
                        <td><input type="email" name="sender_email" class="regular-text" value="<?php echo esc_attr($settings['sender_email']); ?>" placeholder="例: info@example.com"></td>
                    </tr>
                    <tr>
                        <th>署名</th>
                        <td>
                            <textarea name="signature" class="large-text" rows="5"><?php echo esc_textarea($settings['signature']); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th>メルマガ解約ページ</th>
                        <td><input type="text" name="unsubscribe_url" class="large-text" value="<?php echo esc_attr($settings['unsubscribe_url']); ?>"></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="save_settings" class="button button-primary" value="設定を保存">
                </p>
            </form>
        </div>
    <?php
    }

    function show_user_list_page() {
        global $wpdb;
        $table   = $wpdb->prefix . SIMPLE_MAIL_STAND_PREFIX . 'main';
        $sql     = "SELECT * FROM $table";
        $results = $wpdb->get_results($sql);
    ?>
        <div class="wrap">
            <h1>メルマガ登録一覧</h1>
            <table class="wp-list-table widefat striped mail-list">
                <tr>
                    <th>メールアドレス</th>
                    <th>登録日時</th>
                    <th>状態</th>
                </tr>
                <?php foreach ($results as $r) {
                    if ($r->status == 1) {
                        $status = "停止";
                        $class  = "stopped";
                    } else {
                        $status = "有効";
                        $class  = "";
                    }
                ?>
                    <tr data-id="<?php echo esc_attr($r->id); ?>">
                        <td><?php echo esc_html($r->email); ?></td>
                        <td><?php echo esc_html($r->created_at); ?></td>
                        <td><button class="button button-secondary mail-action <?php echo esc_attr($class); ?>"><?php echo esc_html($status); ?></button></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php
    }

    function change_subscribe_status() {
        $mail_id = absint($_POST['mail_id']);
        $status  = absint($_POST['status']);
        $new_status = ($status) ? 0 : 1;

        global $wpdb;
        $table  = $wpdb->prefix . SIMPLE_MAIL_STAND_PREFIX . 'main';
        $result = $wpdb->update(
            $table,
            array('status' => $new_status),
            array('id' => $mail_id),
            array('%d'),
            array('%d')
        );

        if ($result) {
            wp_send_json_success('成功');
        }
        wp_send_json_error('失敗');
    }

    static function create_table() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        global $wpdb;
        $table = $wpdb->prefix . SIMPLE_MAIL_STAND_PREFIX . 'main';
        $sql = "CREATE TABLE $table (
            id int(11) NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL UNIQUE,
            status int(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        dbDelta($sql);
    }

    function add_meta_boxes() {
        add_meta_box(
            'mail-delivery-box',
            'メール配信',
            array($this, 'show_delivery_meta_box'),
            'my-mail-magazine',
            'side',
            'high'
        );
    }

    function show_delivery_meta_box($post) {
        $mail_format = get_post_meta($post->ID, SIMPLE_MAIL_STAND_PREFIX . 'mail_format', true);
        if (empty($mail_format)) {
            $mail_format = 'text';
        }

        $post_status = get_post_status($post->ID);
        $is_published = ($post_status === 'publish');

        if (get_transient('mail_delivery_message_' . $post->ID)) {
            $message = get_transient('mail_delivery_message_' . $post->ID);
            echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
            delete_transient('mail_delivery_message_' . $post->ID);
        }
    ?>

        <p><strong>メール形式</strong></p>
        <fieldset>
            <label>
                <input type="radio" name="mail_format" value="text" <?php checked($mail_format, 'text'); ?>>
                テキスト
            </label>
            <label style="margin-left: 50px;">
                <input type="radio" name="mail_format" value="html" <?php checked($mail_format, 'html'); ?>>
                HTML
            </label>
        </fieldset>

        <p class="submit">
            <input type="submit" name="send_newsletter" class="button button-primary" value="メール配信実行" onclick="return confirm('登録者全員にメールを配信しますか？');">
        </p>

<?php
    }

    /**
     * メタデータ保存・メール配信処理
     */
    function save_delivery_meta($post_id) {
        // メール形式を保存
        if (isset($_POST['mail_format'])) {
            $mail_format = sanitize_text_field($_POST['mail_format']);
            update_post_meta($post_id, SIMPLE_MAIL_STAND_PREFIX . 'mail_format', $mail_format);
        }

        // メール配信が要求された場合
        if (isset($_POST['send_newsletter']) && get_post_status($post_id) === 'publish') {
            $hook_name = 'hook_newsletter_delivery';

            // 既存のスケジュールをクリア
            wp_clear_scheduled_hook($hook_name, array($post_id));

            // 即座に実行するためのスケジュール
            wp_schedule_single_event(time(), $hook_name, array($post_id));

            // 成功メッセージを一時保存
            set_transient('mail_delivery_message_' . $post_id, 'メール配信を開始しました。', 30);
        }
    }

    /**
     * Cronイベント: メール配信実行
     */
    function exec_newsletter_delivery($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'my-mail-magazine' || $post->post_status !== 'publish') {
            return;
        }

        // 登録者一覧を取得
        global $wpdb;
        $table = $wpdb->prefix . SIMPLE_MAIL_STAND_PREFIX . 'main';
        $sql = "SELECT email FROM $table WHERE status = 0";
        $subscribers = $wpdb->get_results($sql);

        if (empty($subscribers)) {
            return;
        }

        // メール形式を取得
        $mail_format = get_post_meta($post_id, SIMPLE_MAIL_STAND_PREFIX . 'mail_format', true);
        if (empty($mail_format)) {
            $mail_format = 'text';
        }

        // メール配信実行
        $success_count = 0;
        foreach ($subscribers as $subscriber) {
            // プロパティに保持した設定を渡す
            if ($this->send_single_mail($subscriber->email, $post, $mail_format, $this->settings)) {
                $success_count++;
            }
        }
    }

    function send_single_mail($email, $post, $mail_format, $settings) {
        $sender_name = $settings['sender_name'];
        $sender_email = $settings['sender_email'];
        $signature = $settings['signature'];

        $headers = array(
            "From: {$sender_name} <{$sender_email}>",
        );

        if ($mail_format === 'html') {
            $headers[] = 'Content-Type: text/html';
        }

        $subject = $post->post_title;
        $content = $post->post_content;

        $content = $this->insert_unsubscribe_link($content, $email, $mail_format);

        if ($mail_format === 'html') {
            $content = wpautop($content);
            if ($signature) {
                $content .= '<hr><div>' . nl2br(esc_html($signature)) . '</div>';
            }
        } else {
            $content = strip_tags($content);
            if ($signature) {
                $content .= "\n\n" . str_repeat('-', 40) . "\n" . $signature;
            }
        }

        return wp_mail($email, $subject, $content, $headers);
    }

    function insert_unsubscribe_link($content, $email, $mail_format) {
        // プロパティに保持した設定を利用
        if (!wp_http_validate_url($this->settings['unsubscribe_url'])) {
            return $content;
        }

        $delimiter = (strpos($this->settings['unsubscribe_url'], '?') !== false) ? '&' : '?';

        $unsubscribe_url = $this->settings['unsubscribe_url'] . "{$delimiter}unsubscribe=" . urlencode($email);

        // デフォルトテキスト
        $default_text = 'このメールの配信を停止したい場合は、こちらのリンクをクリックしてください: ';

        // フィルターフックでテキストをカスタマイズ可能
        $unsubscribe_text = apply_filters('simple_mail_stand_unsubscribe_text', $default_text, $email);

        // HTML形式かテキスト形式かで挿入方法を変える
        if ($mail_format === 'html') {
            $unsubscribe_link = '<p><a href="' . esc_url($unsubscribe_url) . '">' . esc_html($unsubscribe_text) . '</a></p>';
        } else {
            $unsubscribe_link = "\n\n" . $unsubscribe_text . $unsubscribe_url;
        }

        return $content . $unsubscribe_link;
    }
}
