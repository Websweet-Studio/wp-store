<?php

namespace WpStore\Api;

use WP_REST_Response;

class CaptchaController
{
    public function register_routes()
    {
        register_rest_route('wp-store/v1', '/captcha/new', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'new_captcha'],
                'permission_callback' => '__return_true',
            ],
        ]);
    }

    private function random_code($length = 5)
    {
        $length = (int) apply_filters('wp_store_captcha_code_length', $length);
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $idx = wp_rand(0, strlen($chars) - 1);
            $code .= $chars[$idx];
        }
        return apply_filters('wp_store_captcha_code', $code);
    }

    private function generate_image($code)
    {
        if (!extension_loaded('gd')) {
            $html = '<div style="font-family:monospace; font-size: 24px; letter-spacing: 4px; padding: 8px 16px; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 4px; display: inline-block;">' . esc_html($code) . '</div>';
            return apply_filters('wp_store_captcha_html', $html, $code);
        }

        $w = 160;
        $h = 48;
        $im = imagecreate($w, $h);
        $bg = imagecolorallocate($im, 243, 244, 246);
        $fg = imagecolorallocate($im, 17, 24, 39);
        $line = imagecolorallocate($im, 209, 213, 219);
        $pixel = imagecolorallocate($im, 156, 163, 175);

        for ($i = 0; $i < 8; $i++) {
            imageline($im, wp_rand(0, $w), wp_rand(0, $h), wp_rand(0, $w), wp_rand(0, $h), $line);
        }
        for ($i = 0; $i < 50; $i++) {
            imagesetpixel($im, wp_rand(0, $w), wp_rand(0, $h), $pixel);
        }

        $font = 5;
        $fw = imagefontwidth($font);
        $fh = imagefontheight($font);
        $x = ($w - (strlen($code) * ($fw + 6))) / 2;
        $y = ($h - $fh) / 2;

        $len = strlen($code);
        for ($i = 0; $i < $len; $i++) {
            imagestring($im, $font, (int)$x, (int)$y, $code[$i], $fg);
            $x += $fw + 6;
        }

        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        imagedestroy($im);

        $src = 'data:image/png;base64,' . base64_encode($data);
        $html = '<img src="' . $src . '" width="' . $w . '" height="' . $h . '" style="border-radius:4px;border:1px solid #e5e7eb;display:block;" />';
        return apply_filters('wp_store_captcha_html', $html, $code);
    }

    public function new_captcha()
    {
        $code = $this->random_code(5);
        $id = wp_generate_uuid4();
        set_transient('wp_store_captcha_' . $id, $code, 10 * MINUTE_IN_SECONDS);
        $svg = $this->generate_image($code);
        do_action('wp_store_captcha_created', $id, $code);
        return new WP_REST_Response([
            'success' => true,
            'id' => $id,
            'svg' => $svg
        ], 200);
    }
}
