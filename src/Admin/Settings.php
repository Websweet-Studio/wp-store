<?php

namespace WpStore\Admin;

class Settings
{
    public function register()
    {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function add_menu_page()
    {
        add_submenu_page(
            'wp-store',
            'Pengaturan Toko',
            'Pengaturan',
            'manage_options',
            'wp-store-settings',
            [$this, 'render_page']
        );
    }

    public function enqueue_scripts($hook)
    {
        // Only load on our settings page
        // The hook for submenu page is usually 'store_product_page_wp-store-settings'
        // or we can check get_current_screen()
        $screen = get_current_screen();

        if ($screen && strpos($screen->id, 'wp-store-settings') !== false) {
            // Register Alpine.js if not already registered (it might be by other plugins or parts)
            if (!wp_script_is('alpinejs', 'registered')) {
                wp_register_script(
                    'alpinejs',
                    'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js',
                    [],
                    null,
                    true
                );
            }
            wp_enqueue_script('alpinejs');
            wp_enqueue_media();
            wp_enqueue_style(
                'wp-store-admin',
                WP_STORE_URL . 'assets/admin/css/admin.css',
                [],
                WP_STORE_VERSION
            );
        }
    }

    public function render_page()
    {
        require WP_STORE_PATH . 'templates/admin/settings.php';
    }
}
