<?php

namespace WpStore\Admin;

class CouponMetaBoxes
{
    public function register()
    {
        add_action('cmb2_admin_init', [$this, 'register_metaboxes']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts()
    {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'store_coupon' && $screen->base === 'post') {
            $js = "
            (function(){
                function formatDefaultTitle(){
                    var d = new Date();
                    var pad = function(n){ return (n<10?'0':'') + n; };
                    return (
                        '#' +
                        String(d.getFullYear()).slice(-2) +
                        pad(d.getMonth() + 1) +
                        pad(d.getDate()) +
                        pad(d.getHours()) +
                        pad(d.getMinutes()) +
                        pad(d.getSeconds())
                    );

                }
                function applyIfEmpty(){
                    var title = document.getElementById('title');
                    if (!title) return;
                    var val = (title.value || '').trim();
                    if (val === '') {
                        var codeInput = document.querySelector('input[id=\"_store_coupon_code\"], input[name=\"_store_coupon_code\"]');
                        var code = codeInput ? (codeInput.value || '').trim() : '';
                        title.value = code !== '' ? code : formatDefaultTitle();
                    }
                }
                document.addEventListener('DOMContentLoaded', function(){
                    applyIfEmpty();
                    var codeInput = document.querySelector('input[id=\"_store_coupon_code\"], input[name=\"_store_coupon_code\"]');
                    var title = document.getElementById('title');
                    if (codeInput && title) {
                        codeInput.addEventListener('input', function(){
                            var tval = (title.value || '').trim();
                            if (tval === '' || /^Kupon\\s\\d{4}-\\d{2}-\\d{2}\\s\\d{2}:\\d{2}:\\d{2}$/.test(tval)) {
                                title.value = (codeInput.value || '').trim();
                            }
                        });
                    }
                });
            })();
            ";
            wp_add_inline_script('jquery', $js, 'after');
        }
    }

    public function register_metaboxes()
    {
        $box = new_cmb2_box([
            'id'            => 'wp_store_coupon_box',
            'title'         => 'Detail Kupon',
            'object_types'  => ['store_coupon'],
            'context'       => 'normal',
            'priority'      => 'high',
            'show_names'    => true,
        ]);

        $box->add_field([
            'name' => 'Kode Kupon',
            'id'   => '_store_coupon_code',
            'type' => 'text',
            'attributes' => [
                'placeholder' => 'Contoh: HEMAT10',
            ],
        ]);

        $box->add_field([
            'name'    => 'Jenis Potongan',
            'id'      => '_store_coupon_type',
            'type'    => 'select',
            'options' => [
                'percent' => 'Persentase (%)',
                'nominal' => 'Nominal (Rp)',
            ],
            'default' => 'percent',
        ]);

        $box->add_field([
            'name'       => 'Nilai Potongan',
            'id'         => '_store_coupon_value',
            'type'       => 'text',
            'attributes' => [
                'type' => 'number',
                'min'  => '0',
                'step' => '0.01',
                'placeholder' => 'Contoh: 10 untuk 10% atau 25000 untuk nominal',
            ],
        ]);

        $box->add_field([
            'name'       => 'Kadaluarsa',
            'id'         => '_store_coupon_expires_at',
            'type'       => 'text',
            'attributes' => [
                'type' => 'datetime-local',
            ],
            'desc'       => 'Kosongkan jika tidak ada kadaluarsa.',
        ]);
    }
}
