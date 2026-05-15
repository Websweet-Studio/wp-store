<?php

namespace WpStore\Frontend;

class Assets
{
    public function register()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue()
    {
        wp_register_script(
            'alpinejs',
            'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js',
            [],
            null,
            true
        );
        wp_add_inline_script('alpinejs', 'window.deferLoadingAlpineJs = true;', 'before');

        wp_register_script(
            'wp-store-vendor',
            WP_STORE_URL . 'assets/frontend/js/vendor.bundle.js',
            [],
            WP_STORE_VERSION,
            true
        );

        wp_register_script(
            'wp-store-frontend',
            WP_STORE_URL . 'assets/frontend/js/store.js',
            ['alpinejs', 'wp-store-vendor'],
            WP_STORE_VERSION,
            true
        );

        wp_register_style(
            'wp-store-frontend-css',
            WP_STORE_URL . 'assets/frontend/css/style.css',
            [],
            WP_STORE_VERSION
        );
        wp_register_style(
            'wp-store-flickity',
            WP_STORE_URL . 'assets/frontend/css/vendor/flickity.css',
            [],
            WP_STORE_VERSION
        );

        wp_enqueue_style('wp-store-frontend-css');
        $settings = get_option('wp_store_settings', []);
        $css = '';
        $primary = isset($settings['theme_primary']) ? sanitize_hex_color($settings['theme_primary']) : '';
        $primary_hover = isset($settings['theme_primary_hover']) ? sanitize_hex_color($settings['theme_primary_hover']) : '';
        $secondary_text = isset($settings['theme_secondary_text']) ? sanitize_hex_color($settings['theme_secondary_text']) : '';
        $secondary_border = isset($settings['theme_secondary_border']) ? sanitize_hex_color($settings['theme_secondary_border']) : '';
        $callout_bg = isset($settings['theme_callout_bg']) ? sanitize_hex_color($settings['theme_callout_bg']) : '';
        $callout_border = isset($settings['theme_callout_border']) ? sanitize_hex_color($settings['theme_callout_border']) : '';
        $callout_title = isset($settings['theme_callout_title']) ? sanitize_hex_color($settings['theme_callout_title']) : '';
        $danger_text = isset($settings['theme_danger_text']) ? sanitize_hex_color($settings['theme_danger_text']) : '';
        $danger_border = isset($settings['theme_danger_border']) ? sanitize_hex_color($settings['theme_danger_border']) : '';

        if ($primary) {
            $css .= ".wps-btn-primary,button.wps-btn-primary{background-color:{$primary};}\n";
            $css .= ".wps-tab.active{color:{$primary};border-bottom-color:{$primary};}\n";
            $css .= ".wps-callout-title{color:{$primary};}\n";
        }
        if ($primary_hover) {
            $css .= ".wps-btn-primary:hover{background-color:{$primary_hover};}\n";
        }
        if ($secondary_text || $secondary_border) {
            $css .= ".wps-btn-secondary{";
            if ($secondary_border) $css .= "border-color:{$secondary_border};";
            if ($secondary_text) $css .= "color:{$secondary_text};";
            $css .= "}\n";
            if ($secondary_border) {
                $css .= ".wps-btn-secondary:hover{border-color:{$secondary_border};}\n";
            }
        }
        if ($callout_bg || $callout_border) {
            $css .= ".wps-callout{";
            if ($callout_bg) $css .= "background-color:{$callout_bg};";
            if ($callout_border) $css .= "border-color:{$callout_border};";
            $css .= "}\n";
        }
        if ($callout_title) {
            $css .= ".wps-callout-title{color:{$callout_title};}\n";
        }
        if ($danger_text || $danger_border) {
            $css .= ".wps-btn-danger{";
            if ($danger_border) $css .= "border-color:{$danger_border};";
            if ($danger_text) $css .= "color:{$danger_text};";
            $css .= "}\n";
        }
        $container_max = isset($settings['container_max_width']) ? (int) $settings['container_max_width'] : 1100;
        if ($container_max > 0) {
            $css .= ".wps-container{max-width:{$container_max}px;margin-left:auto;margin-right:auto;}\n";
        }
        if (!empty($css)) {
            wp_add_inline_style('wp-store-frontend-css', $css);
        }

        wp_localize_script(
            'wp-store-frontend',
            'wpStoreSettings',
            [
                'restUrl' => esc_url_raw(rest_url('wp-store/v1/')),
                'nonce' => wp_create_nonce('wp_rest'),
                'thanksUrl' => (function () {
                    $settings = get_option('wp_store_settings', []);
                    $pid = isset($settings['page_thanks']) ? absint($settings['page_thanks']) : 0;
                    if ($pid) {
                        $url = get_permalink($pid);
                        if ($url) return esc_url_raw($url);
                    }
                    return esc_url_raw(site_url('/thanks/'));
                })(),
                'trackingUrl' => (function () {
                    $settings = get_option('wp_store_settings', []);
                    $pid = isset($settings['page_tracking']) ? absint($settings['page_tracking']) : 0;
                    if ($pid) {
                        $url = get_permalink($pid);
                        if ($url) return esc_url_raw($url);
                    }
                    return esc_url_raw(site_url('/tracking-order/'));
                })(),
            ]
        );
        wp_enqueue_style('wp-store-flickity');
        wp_enqueue_script('wp-store-vendor');
        wp_enqueue_script('wp-store-frontend');
    }
}
