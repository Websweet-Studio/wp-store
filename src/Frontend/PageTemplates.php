<?php

namespace WpStore\Frontend;

class PageTemplates
{
    private $templates = [
        'wp-store-shop.php' => 'WP Store: Shop',
        'wp-store-checkout.php' => 'WP Store: Checkout',
        'wp-store-cart.php' => 'WP Store: Keranjang',
        'wp-store-wishlist.php' => 'WP Store: Wishlist',
        'wp-store-profile.php' => 'WP Store: Profil',
    ];

    public function register()
    {
        add_filter('theme_page_templates', [$this, 'register_templates'], 10, 4);
        add_filter('template_include', [$this, 'load_template']);
    }

    public function register_templates($post_templates, $wp_theme, $post, $post_type)
    {
        foreach ($this->templates as $file => $label) {
            $post_templates[$file] = $label;
        }
        return $post_templates;
    }

    public function load_template($template)
    {
        if (is_singular('page')) {
            $page_id = get_queried_object_id();
            $assigned = get_page_template_slug($page_id);
            if ($assigned && array_key_exists($assigned, $this->templates)) {
                $path = WP_STORE_PATH . 'templates/page-templates/' . $assigned;
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        return $template;
    }
}
