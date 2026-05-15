<?php

namespace WpStore\Admin;

class CouponColumns
{
    public function register()
    {
        add_filter('manage_store_coupon_posts_columns', [$this, 'add_columns']);
        add_action('manage_store_coupon_posts_custom_column', [$this, 'render_columns'], 10, 2);
    }

    public function add_columns($columns)
    {
        $new = [];
        foreach ($columns as $key => $label) {
            if ($key === 'date') {
                continue;
            }
            $new[$key] = $label;
            if ($key === 'title') {
                $new['coupon_code'] = 'Kode';
                $new['coupon_type'] = 'Jenis';
                $new['coupon_value'] = 'Nilai';
                $new['coupon_expires'] = 'Kadaluarsa';
            }
        }
        // Append date at the end for consistency
        $new['date'] = $columns['date'] ?? 'Date';
        return $new;
    }

    public function render_columns($column, $post_id)
    {
        switch ($column) {
            case 'coupon_code':
                $code = get_post_meta($post_id, '_store_coupon_code', true);
                echo esc_html($code ?: '-');
                break;
            case 'coupon_type':
                $type = get_post_meta($post_id, '_store_coupon_type', true);
                $label = ($type === 'nominal') ? 'Nominal' : 'Persentase';
                echo esc_html($label);
                break;
            case 'coupon_value':
                $type = get_post_meta($post_id, '_store_coupon_type', true);
                $val = get_post_meta($post_id, '_store_coupon_value', true);
                $num = is_numeric($val) ? (float) $val : 0;
                if ($type === 'percent') {
                    echo esc_html(number_format_i18n($num, 0)) . '%';
                } else {
                    echo 'Rp ' . esc_html(number_format_i18n($num, 0));
                }
                break;
            case 'coupon_expires':
                $raw = (string) get_post_meta($post_id, '_store_coupon_expires_at', true);
                if ($raw === '') {
                    echo '-';
                    break;
                }
                $ts = strtotime($raw);
                if ($ts) {
                    $now = current_time('timestamp');
                    $expired = ($ts <= $now);
                    $text = date_i18n('Y-m-d H:i', $ts);
                    if ($expired) {
                        echo '<span style="color:#d63638;">' . esc_html($text) . ' (kadaluarsa)</span>';
                    } else {
                        echo esc_html($text);
                    }
                } else {
                    echo esc_html($raw);
                }
                break;
        }
    }
}

