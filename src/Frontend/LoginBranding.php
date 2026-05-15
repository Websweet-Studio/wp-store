<?php

namespace WpStore\Frontend;

class LoginBranding
{
    public function register()
    {
        add_action('login_head', [$this, 'inject_favicon']);
        add_filter('login_title', [$this, 'filter_title'], 10, 2);
        add_filter('login_headertext', [$this, 'filter_headertext']);
        add_filter('login_headerurl', [$this, 'filter_headerurl']);
        add_action('login_enqueue_scripts', [$this, 'enqueue_logo_styles']);
    }

    public function inject_favicon()
    {
        $icon = function_exists('get_site_icon_url') ? get_site_icon_url(192) : '';
        if ($icon) {
            echo '<link rel="icon" href="' . esc_url($icon) . '" sizes="192x192">' . "\n";
            echo '<link rel="apple-touch-icon" href="' . esc_url($icon) . '">' . "\n";
        }
    }

    public function filter_title($login_title, $title)
    {
        $settings = get_option('wp_store_settings', []);
        $store = isset($settings['store_name']) && is_string($settings['store_name']) && $settings['store_name'] !== '' ? $settings['store_name'] : get_bloginfo('name');
        return $store . ' – Masuk';
    }

    public function filter_headertext($text)
    {
        $settings = get_option('wp_store_settings', []);
        $store = isset($settings['store_name']) && is_string($settings['store_name']) && $settings['store_name'] !== '' ? $settings['store_name'] : get_bloginfo('name');
        return $store;
    }

    public function filter_headerurl($url)
    {
        return home_url('/');
    }

    public function enqueue_logo_styles()
    {
        $settings = get_option('wp_store_settings', []);
        $primary = isset($settings['theme_primary']) ? sanitize_hex_color($settings['theme_primary']) : '#2563eb';
        $primary_hover = isset($settings['theme_primary_hover']) ? sanitize_hex_color($settings['theme_primary_hover']) : '#1d4ed8';
        $bg_color = isset($settings['login_bg_color']) ? sanitize_hex_color($settings['login_bg_color']) : '#f5f7fb';
        $icon = function_exists('get_site_icon_url') ? get_site_icon_url(192) : '';

        $css = '';
        if ($icon) {
            $css .= '
            .login h1 a {
                background-image: url(' . esc_url($icon) . ');
                background-size: contain;
                width: 84px;
                height: 84px;
            }';
        }

        $css .= '
        body.login {
            background: ' . esc_html($bg_color) . ';
        }
        .login form {
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,.1), 0 1px 2px rgba(0,0,0,.06);
            border-radius: 8px;
        }
        .login form .input, .login input[type=text], .login input[type=password] {
            border-radius: 6px;
            border: 1px solid #d1d5db;
            box-shadow: none;
        }
        .login .button.button-primary, .login .button-primary {
            background: ' . esc_html($primary) . ';
            border-color: ' . esc_html($primary) . ';
            color: #fff;
            text-shadow: none;
            border-radius: 4px;
            box-shadow: none;
        }
        .login .button.button-primary:hover, .login .button-primary:hover {
            background: ' . esc_html($primary_hover) . ';
            border-color: ' . esc_html($primary_hover) . ';
        }
        ';

        wp_add_inline_style('login', $css);
    }
}
