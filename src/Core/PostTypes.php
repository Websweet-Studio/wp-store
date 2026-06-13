<?php

namespace WpStore\Core;

class PostTypes
{
    public function register()
    {
        add_action('init', [$this, 'register_product_type']);
        add_action('init', [$this, 'register_order_type']);
        add_action('init', [$this, 'register_coupon_type']);
        add_action('init', [$this, 'register_brand_taxonomy']);
        add_action('admin_init', [$this, 'register_all_product_taxonomy_term_hooks']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_brand_admin_assets']);
    }

    public function register_product_type()
    {
        $archive_slug = 'produk';
        $produk_page = function_exists('get_page_by_path') ? get_page_by_path($archive_slug) : null;
        if ($produk_page && is_a($produk_page, '\WP_Post')) {
            $archive_slug = 'produk-list';
        }
        $labels_cat = [
            'name' => 'Kategori Produk',
            'singular_name' => 'Kategori Produk',
            'search_items' => 'Cari Kategori',
            'all_items' => 'Semua Kategori',
            'parent_item' => 'Induk Kategori',
            'parent_item_colon' => 'Induk Kategori:',
            'edit_item' => 'Edit Kategori',
            'update_item' => 'Update Kategori',
            'add_new_item' => 'Tambah Kategori Baru',
            'new_item_name' => 'Nama Kategori Baru',
            'menu_name' => 'Kategori',
        ];

        register_taxonomy('store_product_cat', ['store_product'], [
            'hierarchical' => true,
            'labels' => $labels_cat,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'kategori-produk'],
            'show_in_rest' => true,
        ]);

        $labels = [
            'name' => 'Produk',
            'singular_name' => 'Produk',
            'menu_name' => 'Produk',
            'name_admin_bar' => 'Produk',
            'add_new' => 'Tambah Baru',
            'add_new_item' => 'Tambah Produk Baru',
            'new_item' => 'Produk Baru',
            'edit_item' => 'Edit Produk',
            'view_item' => 'Lihat Produk',
            'all_items' => 'Semua Produk',
            'search_items' => 'Cari Produk',
            'parent_item_colon' => 'Induk Produk:',
            'not_found' => 'Tidak ditemukan produk.',
            'not_found_in_trash' => 'Tidak ditemukan di tempat sampah.',
            'featured_image' => 'Gambar Produk',
            'set_featured_image' => 'Atur gambar produk',
            'remove_featured_image' => 'Hapus gambar produk',
            'use_featured_image' => 'Gunakan sebagai gambar produk',
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'wp-store',
            'query_var' => true,
            'rewrite' => ['slug' => $archive_slug],
            'capability_type' => 'post',
            'has_archive' => $archive_slug,
            'hierarchical' => false,
            'menu_position' => 7,
            'menu_icon' => 'dashicons-cart',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'show_in_rest' => true,
        ];

        register_post_type('store_product', $args);
        add_rewrite_rule('^' . $archive_slug . '/?$', 'index.php?post_type=store_product', 'top');
        add_rewrite_rule('^' . $archive_slug . '/page/([0-9]+)/?$', 'index.php?post_type=store_product&paged=$matches[1]', 'top');
    }

    public function register_brand_taxonomy()
    {
        $labels = [
            'name' => 'Brand',
            'singular_name' => 'Brand',
            'search_items' => 'Cari Brand',
            'all_items' => 'Semua Brand',
            'edit_item' => 'Edit Brand',
            'update_item' => 'Update Brand',
            'add_new_item' => 'Tambah Brand Baru',
            'new_item_name' => 'Nama Brand Baru',
            'menu_name' => 'Brand',
        ];

        register_taxonomy('store_product_brand', ['store_product'], [
            'hierarchical' => false,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'brand'],
            'show_in_rest' => true,
        ]);
    }

    public function register_all_product_taxonomy_term_hooks()
    {
        $taxonomies = get_object_taxonomies('store_product', 'names');
        foreach ($taxonomies as $taxonomy) {
            add_action("{$taxonomy}_add_form_fields", [$this, 'render_taxonomy_add_fields']);
            add_action("{$taxonomy}_edit_form_fields", [$this, 'render_taxonomy_edit_fields'], 10, 2);
            add_action("created_{$taxonomy}", [$this, 'save_taxonomy_fields'], 10, 2);
            add_action("edited_{$taxonomy}", [$this, 'save_taxonomy_fields'], 10, 2);
            add_filter("manage_edit-{$taxonomy}_columns", [$this, 'add_taxonomy_image_column']);
            add_filter("manage_{$taxonomy}_custom_column", [$this, 'render_taxonomy_image_column'], 10, 3);
        }
    }

    public function enqueue_brand_admin_assets()
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $tax = $screen ? ($screen->taxonomy ?? '') : '';
        $taxonomies = get_object_taxonomies('store_product', 'names');
        if (!$screen || !in_array($tax, $taxonomies, true)) {
            return;
        }

        if (function_exists('wp_enqueue_media')) {
            wp_enqueue_media();
        }

        wp_enqueue_script(
            'wp-store-admin-js',
            WP_STORE_URL . 'assets/admin/js/store-admin.js',
            ['jquery'],
            WP_STORE_VERSION,
            true
        );
    }

    public function render_taxonomy_add_fields($taxonomy)
    {
        $is_brand = $taxonomy === 'store_product_brand';
        $label = $is_brand ? 'Logo Brand' : 'Gambar';
        $field_class = $is_brand ? 'wp-store-brand-logo-field' : 'wp-store-cat-image-field';
        $input_name = $is_brand ? 'wp_store_brand_logo_id' : 'wp_store_cat_image_id';
        $nonce_action = $is_brand ? 'wp_store_brand_logo' : 'wp_store_cat_image';
        $nonce_name = $is_brand ? 'wp_store_brand_logo_nonce' : 'wp_store_cat_image_nonce';
        $upload_class = $is_brand ? 'wp-store-brand-logo-upload' : 'wp-store-cat-image-upload';
        $remove_class = $is_brand ? 'wp-store-brand-logo-remove' : 'wp-store-cat-image-remove';
        $description = $is_brand ? 'Upload logo brand.' : 'Upload gambar.';

        wp_nonce_field($nonce_action, $nonce_name);
        echo '<div class="form-field">';
        echo '<label>' . esc_html($label) . '</label>';
        echo '<div class="' . esc_attr($field_class) . '">';
        echo '<img src="" alt="" style="max-width:80px;height:auto;display:none;margin:0 0 8px;" />';
        echo '<input type="hidden" id="' . esc_attr($input_name) . '" name="' . esc_attr($input_name) . '" value="" />';
        echo '<button type="button" class="button ' . esc_attr($upload_class) . '">Pilih ' . esc_html($label) . '</button> ';
        echo '<button type="button" class="button ' . esc_attr($remove_class) . '" style="display:none;">Hapus</button>';
        echo '</div>';
        echo '<p class="description">' . esc_html($description) . '</p>';
        echo '</div>';
    }

    public function render_taxonomy_edit_fields($term, $taxonomy)
    {
        $is_brand = $taxonomy === 'store_product_brand';
        $label = $is_brand ? 'Logo Brand' : 'Gambar';
        $field_class = $is_brand ? 'wp-store-brand-logo-field' : 'wp-store-cat-image-field';
        $input_name = $is_brand ? 'wp_store_brand_logo_id' : 'wp_store_cat_image_id';
        $meta_key = $is_brand ? '_store_brand_logo_id' : '_store_cat_image_id';
        $nonce_action = $is_brand ? 'wp_store_brand_logo' : 'wp_store_cat_image';
        $nonce_name = $is_brand ? 'wp_store_brand_logo_nonce' : 'wp_store_cat_image_nonce';
        $upload_class = $is_brand ? 'wp-store-brand-logo-upload' : 'wp-store-cat-image-upload';
        $remove_class = $is_brand ? 'wp-store-brand-logo-remove' : 'wp-store-cat-image-remove';
        $description = $is_brand ? 'Upload logo brand.' : 'Upload gambar.';

        $term_id = isset($term->term_id) ? (int) $term->term_id : 0;
        $image_id = (int) get_term_meta($term_id, $meta_key, true);
        $src = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';

        wp_nonce_field($nonce_action, $nonce_name);
        echo '<tr class="form-field">';
        echo '<th scope="row"><label>' . esc_html($label) . '</label></th>';
        echo '<td>';
        echo '<div class="' . esc_attr($field_class) . '">';
        echo '<img src="' . esc_url($src ?: '') . '" alt="" style="max-width:80px;height:auto;' . ($src ? 'display:block' : 'display:none') . ';margin:0 0 8px;" />';
        echo '<input type="hidden" id="' . esc_attr($input_name) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr((string) $image_id) . '" />';
        echo '<button type="button" class="button ' . esc_attr($upload_class) . '">Pilih ' . esc_html($label) . '</button> ';
        echo '<button type="button" class="button ' . esc_attr($remove_class) . '" style="' . ($image_id ? 'display:inline-block' : 'display:none') . ';">Hapus</button>';
        echo '</div>';
        echo '<p class="description">' . esc_html($description) . '</p>';
        echo '</td>';
        echo '</tr>';
    }

    public function save_taxonomy_fields($term_id, $tt_id)
    {
        if (!current_user_can('manage_categories')) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $taxonomy = $screen ? ($screen->taxonomy ?? '') : '';
        $is_brand = $taxonomy === 'store_product_brand';
        $nonce_action = $is_brand ? 'wp_store_brand_logo' : 'wp_store_cat_image';
        $nonce_name = $is_brand ? 'wp_store_brand_logo_nonce' : 'wp_store_cat_image_nonce';
        $input_name = $is_brand ? 'wp_store_brand_logo_id' : 'wp_store_cat_image_id';
        $meta_key = $is_brand ? '_store_brand_logo_id' : '_store_cat_image_id';

        if (!isset($_POST[$nonce_name]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$nonce_name])), $nonce_action)) {
            return;
        }

        $image_id = isset($_POST[$input_name]) ? absint(wp_unslash($_POST[$input_name])) : 0;
        if ($image_id > 0) {
            update_term_meta((int) $term_id, $meta_key, $image_id);
        } else {
            delete_term_meta((int) $term_id, $meta_key);
        }
    }

    public function add_taxonomy_image_column($columns)
    {
        $new_columns = [];
        foreach ($columns as $key => $label) {
            if ($key === 'name') {
                $screen = function_exists('get_current_screen') ? get_current_screen() : null;
                $taxonomy = $screen ? ($screen->taxonomy ?? '') : '';
                $column_label = $taxonomy === 'store_product_brand' ? 'Logo' : 'Gambar';
                $new_columns['image'] = $column_label;
            }
            $new_columns[$key] = $label;
        }
        return $new_columns;
    }

    public function render_taxonomy_image_column($content, $column_name, $term_id)
    {
        if ($column_name !== 'image' && $column_name !== 'logo') {
            return $content;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $taxonomy = $screen ? ($screen->taxonomy ?? '') : '';
        $meta_key = $taxonomy === 'store_product_brand' ? '_store_brand_logo_id' : '_store_cat_image_id';
        $object_fit = $taxonomy === 'store_product_brand' ? 'contain' : 'cover';

        $image_id = (int) get_term_meta((int) $term_id, $meta_key, true);
        if ($image_id <= 0) {
            return '—';
        }
        $src = wp_get_attachment_image_url($image_id, 'thumbnail');
        if (!$src) {
            return '—';
        }
        return '<img src="' . esc_url($src) . '" alt="" style="width:32px;height:32px;object-fit:' . esc_attr($object_fit) . ';border-radius:4px;" />';
    }



    public function register_order_type()
    {
        $labels = [
            'name' => 'Pesanan',
            'singular_name' => 'Pesanan',
            'menu_name' => 'Pesanan',
            'name_admin_bar' => 'Pesanan',
            'add_new' => 'Tambah Baru',
            'add_new_item' => 'Tambah Pesanan Baru',
            'new_item' => 'Pesanan Baru',
            'edit_item' => 'Edit Pesanan',
            'view_item' => 'Lihat Pesanan',
            'all_items' => 'Semua Pesanan',
            'search_items' => 'Cari Pesanan',
            'parent_item_colon' => 'Induk Pesanan:',
            'not_found' => 'Tidak ditemukan pesanan.',
            'not_found_in_trash' => 'Tidak ditemukan di tempat sampah.',
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => 'wp-store',
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 8,
            'menu_icon' => 'dashicons-clipboard',
            'supports' => ['title'],
            'show_in_rest' => false,
        ];

        register_post_type('store_order', $args);
    }

    public function register_coupon_type()
    {
        $labels = [
            'name' => 'Kupon',
            'singular_name' => 'Kupon',
            'menu_name' => 'Kupon',
            'name_admin_bar' => 'Kupon',
            'add_new' => 'Tambah Baru',
            'add_new_item' => 'Tambah Kupon Baru',
            'new_item' => 'Kupon Baru',
            'edit_item' => 'Edit Kupon',
            'view_item' => 'Lihat Kupon',
            'all_items' => 'Semua Kupon',
            'search_items' => 'Cari Kupon',
            'parent_item_colon' => 'Induk Kupon:',
            'not_found' => 'Tidak ditemukan kupon.',
            'not_found_in_trash' => 'Tidak ditemukan di tempat sampah.',
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => 'wp-store',
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 9,
            'menu_icon' => 'dashicons-tickets-alt',
            'supports' => ['title'],
            'show_in_rest' => false,
        ];

        register_post_type('store_coupon', $args);
    }
}
