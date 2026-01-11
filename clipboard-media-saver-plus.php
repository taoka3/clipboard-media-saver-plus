<?php

/**
 * Plugin Name: Clipboard Media Saver Plus
 * Description: クリップボードから画像・音声・動画を貼り付けるだけでメディアライブラリに保存
 * Version: 1.1.0
 * Author: @toshiaki_taoka
 * Author URI: https://x.com/toshiaki_taoka
 */

if (!defined('ABSPATH')) exit;

class Clipboard_Media_Saver_Plus
{

    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        add_action('wp_ajax_clipboard_media_upload', [$this, 'upload']);
    }

    public function enqueue($hook)
    {
        // 念のため二重ガード
        if ($hook !== 'upload.php') {
            return;
        }

        wp_enqueue_script(
            'clipboard-media-saver-plus',
            plugin_dir_url(__FILE__) . 'clipboard-media-saver-plus.js',
            [],
            '1.1.0',
            true
        );

        wp_localize_script('clipboard-media-saver-plus', 'CMSP', [
            'ajax'  => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('clipboard_media_upload')
        ]);
    }

    public function upload()
    {
        check_ajax_referer('clipboard_media_upload', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error('権限がありません');
        }

        if (empty($_FILES['file'])) {
            wp_send_json_error('ファイルがありません');
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $file = $_FILES['file'];

        // MIMEと拡張子をWP基準で再判定（超重要）
        $check = wp_check_filetype_and_ext(
            $file['tmp_name'],
            $file['name']
        );

        if (!$check['ext'] || !$check['type']) {
            wp_send_json_error('不正なファイル形式');
        }

        $upload = wp_handle_upload($file, [
            'test_form' => false,
            'mimes'     => get_allowed_mime_types()
        ]);

        if (isset($upload['error'])) {
            wp_send_json_error($upload['error']);
        }

        $attachment = [
            'post_mime_type' => $check['type'],
            'post_title'     => sanitize_file_name(pathinfo($file['name'], PATHINFO_FILENAME)),
            'post_status'    => 'inherit'
        ];

        $attach_id = wp_insert_attachment($attachment, $upload['file']);

        // 画像のみメタ生成
        if (strpos($check['type'], 'image/') === 0) {
            $meta = wp_generate_attachment_metadata($attach_id, $upload['file']);
            wp_update_attachment_metadata($attach_id, $meta);
        }

        wp_send_json_success([
            'id'  => $attach_id,
            'url' => wp_get_attachment_url($attach_id),
            'type' => $check['type']
        ]);
    }
}

new Clipboard_Media_Saver_Plus();
