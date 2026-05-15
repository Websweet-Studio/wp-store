<?php

namespace WpStore\Api;

use WP_REST_Request;
use WP_REST_Response;

class ToolsController
{
    public function register_routes()
    {
        register_rest_route('wp-store/v1', '/tools/seed-products', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'seed_products'],
                'permission_callback' => [$this, 'check_admin_auth'],
            ],
        ]);
        register_rest_route('wp-store/v1', '/tools/clear-cache', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'clear_cache'],
                'permission_callback' => [$this, 'check_admin_auth'],
            ],
        ]);
        register_rest_route('wp-store/v1', '/tools/cache-stats', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'cache_stats'],
                'permission_callback' => [$this, 'check_admin_auth'],
            ],
        ]);
    }

    public function check_admin_auth()
    {
        return current_user_can('manage_options');
    }

    public function seed_products(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        $count = isset($params['count']) ? (int) $params['count'] : 12;
        if ($count <= 0) {
            $count = 12;
        }
        if ($count > 50) {
            $count = 50;
        }
        $categories = [
            ['name' => 'Elektronik', 'slug' => 'elektronik'],
            ['name' => 'Fashion', 'slug' => 'fashion'],
            ['name' => 'Rumah Tangga', 'slug' => 'rumah-tangga'],
            ['name' => 'Olahraga', 'slug' => 'olahraga'],
            ['name' => 'Makanan & Minuman', 'slug' => 'makanan-minuman'],
        ];
        $term_ids = [];
        foreach ($categories as $cat) {
            $exists = term_exists($cat['slug'], 'store_product_cat');
            if ($exists && isset($exists['term_id'])) {
                $term_ids[] = (int) $exists['term_id'];
            } else {
                $created = wp_insert_term($cat['name'], 'store_product_cat', ['slug' => $cat['slug']]);
                if (!is_wp_error($created) && isset($created['term_id'])) {
                    $term_ids[] = (int) $created['term_id'];
                }
            }
        }
        if (empty($term_ids)) {
            $term = wp_insert_term('Umum', 'store_product_cat', ['slug' => 'umum']);
            if (!is_wp_error($term) && isset($term['term_id'])) {
                $term_ids[] = (int) $term['term_id'];
            }
        }
        $created_ids = [];
        for ($i = 1; $i <= $count; $i++) {
            $title = 'Produk Contoh ' . $i;
            $content = 'Deskripsi produk contoh ' . $i . ' untuk pengujian katalog.';
            $post_id = wp_insert_post([
                'post_title'   => $title,
                'post_content' => $content,
                'post_status'  => 'publish',
                'post_type'    => 'store_product',
            ]);
            if (is_wp_error($post_id) || !$post_id) {
                continue;
            }
            $price = rand(10000, 250000);
            $stock = rand(1, 100);
            $weight = rand(1, 25) / 10;
            update_post_meta($post_id, '_store_price', $price);
            update_post_meta($post_id, '_store_stock', $stock);
            update_post_meta($post_id, '_store_weight_kg', $weight);
            if (!empty($term_ids)) {
                $rand_term = $term_ids[array_rand($term_ids)];
                wp_set_object_terms($post_id, [$rand_term], 'store_product_cat', false);
            }
            $created_ids[] = (int) $post_id;
        }
        $digital_exists = term_exists('digital', 'store_product_cat');
        $digital_term_id = 0;
        if ($digital_exists && isset($digital_exists['term_id'])) {
            $digital_term_id = (int) $digital_exists['term_id'];
        } elseif (is_int($digital_exists)) {
            $digital_term_id = $digital_exists;
        } else {
            $created_term = wp_insert_term('Produk Digital', 'store_product_cat', ['slug' => 'digital']);
            if (!is_wp_error($created_term) && isset($created_term['term_id'])) {
                $digital_term_id = (int) $created_term['term_id'];
            }
        }
        $digital_items = [
            ['title' => 'E-Book Panduan UMKM', 'content' => 'E-book PDF berisi panduan praktis mengembangkan usaha kecil.', 'price' => 25000],
            ['title' => 'Template CV Profesional', 'content' => 'Template CV siap pakai dalam format DOCX dan PDF.', 'price' => 15000],
            ['title' => 'Preset Lightroom Mobile', 'content' => 'Preset foto untuk Lightroom Mobile, cocok untuk feed Instagram.', 'price' => 20000],
            ['title' => 'Musik Royalty Free Pack', 'content' => 'Paket musik bebas royalti untuk konten video Anda.', 'price' => 45000],
            ['title' => 'Kelas Video Editing Dasar', 'content' => 'Kursus video editing dasar, akses streaming 30 hari.', 'price' => 99000],
            ['title' => 'Icon Set UI Minimalis', 'content' => 'Paket icon SVG/PNG untuk UI, lisensi personal.', 'price' => 30000],
        ];
        foreach ($digital_items as $di) {
            $pid = wp_insert_post([
                'post_title'   => $di['title'],
                'post_content' => $di['content'],
                'post_status'  => 'publish',
                'post_type'    => 'store_product',
            ]);
            if (is_wp_error($pid) || !$pid) {
                continue;
            }
            update_post_meta($pid, '_store_price', (float) $di['price']);
            update_post_meta($pid, '_store_weight_kg', 0);
            update_post_meta($pid, '_store_is_digital', 1);
            update_post_meta($pid, '_store_product_type', 'digital');
            update_post_meta($pid, '_store_digital_file', WP_STORE_URL . 'assets/frontend/file/sample.pdf');
            if ($digital_term_id > 0) {
                wp_set_object_terms($pid, [$digital_term_id], 'store_product_cat', false);
            }
            $created_ids[] = (int) $pid;
        }
        do_action('wp_store_tools_products_seeded', $created_ids);
        do_action('wp_store_after_seed_products', $created_ids);
        return new WP_REST_Response([
            'success' => true,
            'message' => 'Seeder berhasil membuat ' . count($created_ids) . ' produk.',
            'created' => $created_ids
        ], 200);
    }

    public function clear_cache(WP_REST_Request $request)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'options';
        $total = 0;
        $total += (int) $wpdb->query("DELETE FROM {$table} WHERE option_name IN ('_transient_wp_store_rajaongkir_provinces','_transient_timeout_wp_store_rajaongkir_provinces')");
        $total += (int) $wpdb->query("DELETE FROM {$table} WHERE option_name LIKE '_transient_wp_store_rajaongkir_cities_%' OR option_name LIKE '_transient_timeout_wp_store_rajaongkir_cities_%'");
        $total += (int) $wpdb->query("DELETE FROM {$table} WHERE option_name LIKE '_transient_wp_store_rajaongkir_subdistricts_%' OR option_name LIKE '_transient_timeout_wp_store_rajaongkir_subdistricts_%'");
        $total += (int) $wpdb->query("DELETE FROM {$table} WHERE option_name LIKE '_transient_wp_store_rajaongkir_cost_%' OR option_name LIKE '_transient_timeout_wp_store_rajaongkir_cost_%'");
        do_action('wp_store_tools_cache_cleared', $total);
        return new WP_REST_Response([
            'success' => true,
            'message' => 'Cache berhasil dibersihkan.',
            'deleted_rows' => $total
        ], 200);
    }

    public function cache_stats(WP_REST_Request $request)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'options';
        $sql = "
            SELECT
                COALESCE(SUM(LENGTH(option_value)),0) AS total_bytes,
                COUNT(*) AS total_entries
            FROM {$table}
            WHERE
                option_name NOT LIKE '_transient_timeout%%' AND (
                    option_name = '_transient_wp_store_rajaongkir_provinces' OR
                    option_name LIKE '_transient_wp_store_rajaongkir_cities_%%' OR
                    option_name LIKE '_transient_wp_store_rajaongkir_subdistricts_%%' OR
                    option_name LIKE '_transient_wp_store_rajaongkir_cost_%%'
                )
        ";
        $row = $wpdb->get_row($sql, ARRAY_A);
        $bytes = isset($row['total_bytes']) ? (int) $row['total_bytes'] : 0;
        $entries = isset($row['total_entries']) ? (int) $row['total_entries'] : 0;
        $mb = round($bytes / 1048576, 2);
        return new WP_REST_Response([
            'success' => true,
            'bytes' => $bytes,
            'entries' => $entries,
            'approx_mb' => $mb
        ], 200);
    }
}
