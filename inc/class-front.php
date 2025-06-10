<?php

class SimpleMailStandFront {
    private $settings = array();

    function __construct() {
        $default_options = array(
            'sender_name' => 'sender',
            'sender_mail' => 'sender@study.local',
            'signature'   => ''
        );

        $this->settings = get_option(SIMPLE_MAIL_STAND_PREFIX . 'settings', $default_options);
    }

    function front_enqueue() {
        // if (!is_singular() || !has_shortcode(get_post()->post_content, 'my_subscribe_form')) {
        //     return;
        // }

        $version  = (defined('SIMPLE_MAIL_STAND_DEVELOP') && true === SIMPLE_MAIL_STAND_DEVELOP) ? time() : SIMPLE_MAIL_STAND_VERSION;
        $strategy = array('in_footer' => true, 'strategy'  => 'defer');

        wp_register_style(SIMPLE_MAIL_STAND_SLUG . '-front',  SIMPLE_MAIL_STAND_URL . '/css/front.css', array(), $version);
        wp_register_script(SIMPLE_MAIL_STAND_SLUG . '-front', SIMPLE_MAIL_STAND_URL . '/js/front.js', array('jquery'), $version, $strategy);

        if (is_singular() && (has_shortcode(get_post()->post_content, 'my_subscribe_form') ||
            has_shortcode(get_post()->post_content, 'my_unsubscribe_form'))) {
            wp_enqueue_style(SIMPLE_MAIL_STAND_SLUG . '-front');
            wp_enqueue_script(SIMPLE_MAIL_STAND_SLUG . '-front');

            $front = array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce(SIMPLE_MAIL_STAND_SLUG)
            );
            wp_localize_script(SIMPLE_MAIL_STAND_SLUG . '-front', 'front', $front);
        }
    }

    function my_subscribe_form() {
?>
        <div class="my-subscribe-wrapper">
            <h3>ニュースレター購読</h3>
            <p>登録すると、お得な情報がメール配信されます。</p>
            <div class="my-subscribe-form">
                <input type="email" name="email" placeholder="メールアドレスを入力" required="">
                <button type="submit" class="register_email">登録</button>
            </div>
            <div class="my-subscribe-message" style="display: none;"></div>
        </div>
    <?php
    }

    function my_unsubscribe_form() {
        if (!isset($_GET['unsubscribe'])) {
            return "<p>アクセス方法に誤りがあります。</p>";
        }

        $email = sanitize_email($_GET['unsubscribe']);

        if (!is_email($email)) {
            return '<div class="my-subscribe-wrapper"><p>無効なメールアドレスです。</p></div>';
        }

        global $wpdb;
        $table = $wpdb->prefix . SIMPLE_MAIL_STAND_PREFIX . 'main';
        $query = "SELECT * FROM $table WHERE email = %s";
        $user = $wpdb->get_row($wpdb->prepare($query, $email));

        if (!$user) {
            return '<div class="my-subscribe-wrapper"><p>このメールアドレスは登録されていません。</p></div>';
        }

        if ($user->status == 1) {
            return '<div class="my-subscribe-wrapper"><p>このメールアドレスは既に解除済みです。</p></div>';
        }

    ?>
        <div class="my-subscribe-wrapper">
            <h3>配信解除</h3>
            <p>以下のメールアドレスの配信を解除しますか？</p>
            <p><strong><?php echo esc_html($email); ?></strong></p>
            <div class="my-subscribe-form">
                <input type="hidden" name="email" value="<?php echo esc_attr($email); ?>">
                <button type="submit" class="unsubscribe_email button">配信解除する</button>
            </div>
            <div class="my-subscribe-message" style="display: none;"></div>
        </div>
<?php
    }

    function subscribe_email() {
        if (!check_ajax_referer(SIMPLE_MAIL_STAND_SLUG, 'nonce', false)) {
            wp_send_json_error(array('message' => '不正なリクエストです。'));
        }

        $email = sanitize_email($_POST['email']);

        if ($email && !is_email($email)) {
            wp_send_json_error(array('message' => '正しいメールアドレスを入力してください。'));
        }

        global $wpdb;
        $table  = $wpdb->prefix . SIMPLE_MAIL_STAND_PREFIX . 'main';
        $status = $wpdb->insert(
            $table,
            array(
                'email' => $email
            ),
            array(
                '%s'
            )
        );

        if (!$status) {
            if (strpos($wpdb->last_error, 'Duplicate') !== false) {
                wp_send_json_error(array('message' => 'このメールアドレスはすでに登録されています。'));
            } else {
                wp_send_json_error(array('message' => '登録に失敗しました。'));
            }
        }

        $this->send_email($email);

        do_action('simple_mail_stand_after_subscribe', $email);

        $default_message = "登録が完了しました。";
        $success_message = apply_filters('simple_mail_stand_success_subscribe', $default_message, $email, date_i18n('Y-m-d'));

        wp_send_json_success(array('message' => $success_message));
    }

    function unsubscribe_email() {
        if (!check_ajax_referer(SIMPLE_MAIL_STAND_SLUG, 'nonce', false)) {
            wp_send_json_error(array('message' => '不正なリクエストです。'));
        }

        $email = sanitize_email($_POST['email']);

        if (!is_email($email)) {
            wp_send_json_error(array('message' => '正しいメールアドレスを入力してください。'));
        }

        global $wpdb;
        $table = $wpdb->prefix . SIMPLE_MAIL_STAND_PREFIX . 'main';

        $result = $wpdb->update(
            $table,
            array('status' => 1),
            array('email' => $email),
            array('%d'),
            array('%s')
        );

        if ($result) {
            do_action('simple_mail_stand_after_unsubscribe', $email);
            wp_send_json_success(array('message' => '配信解除が完了しました。'));
        } else {
            wp_send_json_error(array('message' => '解除処理に失敗しました。'));
        }
    }


    function send_email($email) {

        $sender_name  = $this->settings['sender_name'];
        $sender_email = $this->settings['sender_mail'];
        $signature    = $this->settings['signature'];

        $headers = array(
            "From: {$sender_name} <{$sender_email}>",
        );

        $default_title = "メルマガ登録完了のお知らせ";
        $title = apply_filters('simple_mail_stand_welcome_title', $default_title, $email);

        $default_content = <<<_CONTENT_
この度は、メルマガにご登録いただきありがとうございます。

登録が正常に完了いたしました。
今後、お得な情報やお知らせをお送りさせていただきます。

※このメールは自動送信されています。

何かご不明な点がございましたら、お気軽にお問い合わせください。

今後ともよろしくお願いいたします。

_CONTENT_;

        $content = apply_filters('simple_mail_stand_welcome_content', $default_content, $email);

        if ($signature) {
            $content .= "\n\n" . str_repeat('-', 40) . "\n" . $signature;
        }

        wp_mail($email, $title, $content, $headers);
    }
}
