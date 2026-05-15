<?php

namespace WpStore\Api;

use WP_REST_Request;
use WP_REST_Response;

class WishlistController
{
    private $cookie_key = 'wp_store_cart_key';

    public function register_routes()
    {
        register_rest_route('wp-store/v1', '/wishlist', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_wishlist'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'add_item'],
                'permission_callback' => [$this, 'require_rest_nonce'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'remove_item_or_clear'],
                'permission_callback' => [$this, 'require_rest_nonce'],
            ],
        ]);
    }

    public function require_rest_nonce(WP_REST_Request $request)
    {
        $nonce = $request->get_header('x_wp_nonce');
        if (!$nonce) {
            $nonce = $request->get_header('x-wp-nonce');
        }
        return is_string($nonce) && wp_verify_nonce($nonce, 'wp_rest');
    }

    public function get_wishlist(WP_REST_Request $request)
    {
        $wish = $this->read_wishlist();
        return new WP_REST_Response($this->format_wishlist($wish), 200);
    }

    public function add_item(WP_REST_Request $request)
    {
        $data = $request->get_json_params();
        if (!is_array($data)) {
            $data = [];
        }
        $product_id = isset($data['id']) ? (int) $data['id'] : 0;
        $options = isset($data['options']) && is_array($data['options']) ? $this->normalize_options($data['options']) : [];

        if ($product_id <= 0 || get_post_type($product_id) !== 'store_product') {
            return new WP_REST_Response(['message' => 'Produk tidak valid'], 400);
        }

        $wishlist = $this->read_wishlist();
        $wishlist = $this->apply_add($wishlist, $product_id, $options);
        $this->write_wishlist($wishlist);

        return new WP_REST_Response($this->format_wishlist($wishlist), 200);
    }

    public function remove_item_or_clear(WP_REST_Request $request)
    {
        $data = $request->get_json_params();
        if (!is_array($data)) {
            $data = [];
        }
        $product_id = isset($data['id']) ? (int) $data['id'] : 0;
        $options = isset($data['options']) && is_array($data['options']) ? $this->normalize_options($data['options']) : [];

        $wishlist = $this->read_wishlist();
        if ($product_id > 0) {
            $wishlist = $this->apply_remove($wishlist, $product_id, $options);
        } else {
            $wishlist = [];
        }
        $this->write_wishlist($wishlist);
        return new WP_REST_Response($this->format_wishlist($wishlist), 200);
    }

    private function apply_add($wishlist, $product_id, $options = [])
    {
        $wishlist = is_array($wishlist) ? $wishlist : [];
        foreach ($wishlist as $row) {
            $id = isset($row['id']) ? (int) $row['id'] : 0;
            $row_opts = isset($row['opts']) && is_array($row['opts']) ? $this->normalize_options($row['opts']) : [];
            if ($id === (int) $product_id && $this->options_equal($row_opts, $options)) {
                return $wishlist; // already exists
            }
        }
        $wishlist[] = ['id' => (int) $product_id, 'opts' => $options];
        return $wishlist;
    }

    private function apply_remove($wishlist, $product_id, $options = [])
    {
        $wishlist = is_array($wishlist) ? $wishlist : [];
        $next = [];
        foreach ($wishlist as $row) {
            $id = isset($row['id']) ? (int) $row['id'] : 0;
            $row_opts = isset($row['opts']) && is_array($row['opts']) ? $this->normalize_options($row['opts']) : [];
            if ($id === (int) $product_id && $this->options_equal($row_opts, $options)) {
                continue; // skip remove
            }
            if ($id > 0) {
                $next[] = ['id' => $id, 'opts' => $row_opts];
            }
        }
        return $next;
    }

    private function read_wishlist()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'store_wishlists';
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $row = $wpdb->get_row($wpdb->prepare("SELECT wishlist FROM {$table} WHERE user_id = %d LIMIT 1", $user_id));
            if ($row && isset($row->wishlist)) {
                $data = json_decode($row->wishlist, true);
                return is_array($data) ? $data : [];
            }
            $key = $this->get_or_set_guest_key();
            $row = $wpdb->get_row($wpdb->prepare("SELECT wishlist FROM {$table} WHERE guest_key = %s LIMIT 1", $key));
            if ($row && isset($row->wishlist)) {
                $data = json_decode($row->wishlist, true);
                if (is_array($data)) {
                    $this->write_wishlist($data);
                }
                return is_array($data) ? $data : [];
            }
            return [];
        }
        $key = $this->get_or_set_guest_key();
        $row = $wpdb->get_row($wpdb->prepare("SELECT wishlist FROM {$table} WHERE guest_key = %s LIMIT 1", $key));
        if ($row && isset($row->wishlist)) {
            $data = json_decode($row->wishlist, true);
            return is_array($data) ? $data : [];
        }
        return [];
    }

    private function write_wishlist($wishlist)
    {
        global $wpdb;
        $wishlist = is_array($wishlist) ? $wishlist : [];
        $table = $wpdb->prefix . 'store_wishlists';
        $json = wp_json_encode($wishlist);
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE user_id = %d LIMIT 1", $user_id));
            if ($exists) {
                $wpdb->update($table, ['wishlist' => $json], ['user_id' => $user_id], ['%s'], ['%d']);
            } else {
                $wpdb->insert($table, ['user_id' => $user_id, 'wishlist' => $json], ['%d', '%s']);
            }
            return;
        }
        $key = $this->get_or_set_guest_key();
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE guest_key = %s LIMIT 1", $key));
        if ($exists) {
            $wpdb->update($table, ['wishlist' => $json], ['guest_key' => $key], ['%s'], ['%s']);
        } else {
            $wpdb->insert($table, ['guest_key' => $key, 'wishlist' => $json], ['%s', '%s']);
        }
    }

    private function get_or_set_guest_key()
    {
        if (isset($_COOKIE[$this->cookie_key]) && is_string($_COOKIE[$this->cookie_key]) && $_COOKIE[$this->cookie_key] !== '') {
            $key = sanitize_key($_COOKIE[$this->cookie_key]);
            return $key;
        }
        $key = sanitize_key(wp_generate_uuid4());
        $secure = is_ssl();
        $path = '/';
        $domain = '';
        setcookie($this->cookie_key, $key, time() + (DAY_IN_SECONDS * 30), $path, $domain, $secure, true);
        $_COOKIE[$this->cookie_key] = $key;
        return $key;
    }

    private function format_wishlist($wishlist)
    {
        $items = [];
        foreach ($wishlist as $row) {
            $product_id = isset($row['id']) ? (int) $row['id'] : 0;
            $opts = isset($row['opts']) && is_array($row['opts']) ? $row['opts'] : [];
            if ($product_id <= 0 || get_post_type($product_id) !== 'store_product') {
                continue;
            }
            $price = $this->resolve_price_with_options($product_id, $opts);
            $items[] = [
                'id' => $product_id,
                'title' => get_the_title($product_id),
                'price' => $price,
                'image' => get_the_post_thumbnail_url($product_id, 'thumbnail') ?: null,
                'link' => get_permalink($product_id),
                'options' => $opts,
            ];
        }
        return [
            'items' => $items,
            'count' => count($items),
        ];
    }

    private function normalize_options($options)
    {
        $normalized = [];
        foreach ($options as $k => $v) {
            $key = trim(sanitize_text_field($k));
            if (is_array($v)) {
                $normalized[$key] = array_map(function ($x) {
                    return trim(sanitize_text_field($x));
                }, $v);
            } else {
                $normalized[$key] = trim(sanitize_text_field((string) $v));
            }
        }
        ksort($normalized);
        return $normalized;
    }

    private function options_equal($a, $b)
    {
        $a = $this->normalize_options(is_array($a) ? $a : []);
        $b = $this->normalize_options(is_array($b) ? $b : []);
        return wp_json_encode($a) === wp_json_encode($b);
    }

    private function resolve_price_with_options($product_id, $opts)
    {
        $base = (float) get_post_meta($product_id, '_store_price', true);
        $adv_name = get_post_meta($product_id, '_store_option2_name', true);
        $adv = get_post_meta($product_id, '_store_advanced_options', true);
        if (is_array($adv) && $adv_name && isset($opts[$adv_name])) {
            $label = (string) $opts[$adv_name];
            foreach ($adv as $row) {
                $rlabel = isset($row['label']) ? (string) $row['label'] : '';
                $rprice = isset($row['price']) ? (float) $row['price'] : 0;
                if ($rlabel !== '' && $rlabel === $label && $rprice > 0) {
                    return $rprice;
                }
            }
        }
        return $base;
    }
}

