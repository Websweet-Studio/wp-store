<?php

namespace WpStore\Admin;

class ProductMetaBoxes
{
    public function register()
    {
        add_action('cmb2_admin_init', [$this, 'register_metaboxes']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    public static function get_schema()
    {
        $schema = [
            [
                'id' => 'general',
                'title' => 'General',
                'fields' => [
                    [
                        'id' => '_store_product_type',
                        'label' => 'Tipe Produk',
                        'type' => 'select',
                        'options' => [
                            'physical' => 'Produk Fisik (Basic)',
                            'digital' => 'Produk Digital',
                        ],
                        'default' => 'physical',
                    ],
                    [
                        'id' => '_store_price',
                        'label' => 'Harga Regular (Rp)',
                        'type' => 'number',
                        'attributes' => ['min' => 0, 'step' => 0.01],
                    ],
                    [
                        'id' => '_store_sale_price',
                        'label' => 'Harga Promo (Rp)',
                        'type' => 'number',
                        'attributes' => ['min' => 0, 'step' => 0.01],
                    ],
                    [
                        'id' => '_store_flashsale_until',
                        'label' => 'Diskon Sampai',
                        'type' => 'datetime-local',
                    ],
                    [
                        'id' => '_store_digital_file',
                        'label' => 'File Produk (Digital)',
                        'type' => 'file',
                    ],
                ],
            ],
            [
                'id' => 'inventory',
                'title' => 'Inventory',
                'fields' => [
                    ['id' => '_store_sku', 'label' => 'Kode Produk (SKU)', 'type' => 'text'],
                    ['id' => '_store_stock', 'label' => 'Stok', 'type' => 'number', 'attributes' => ['min' => 0, 'step' => 1]],
                    ['id' => '_store_min_order', 'label' => 'Minimal Order', 'type' => 'number', 'attributes' => ['min' => 1, 'step' => 1]],
                    ['id' => '_store_weight_kg', 'label' => 'Berat Produk (Kg)', 'type' => 'number', 'attributes' => ['min' => 0, 'step' => 0.01]],
                ],
            ],
            [
                'id' => 'attributes',
                'title' => 'Attributes',
                'fields' => [
                    [
                        'id' => '_store_label',
                        'label' => 'Label Produk',
                        'type' => 'select',
                        'options' => [
                            '' => '-',
                            'label-best' => 'Best Seller',
                            'label-limited' => 'Limited',
                            'label-new' => 'New',
                        ],
                    ],
                    ['id' => '_store_option_name', 'label' => 'Nama Opsi (Basic)', 'type' => 'text'],
                    ['id' => '_store_options', 'label' => 'Opsi Basic', 'type' => 'repeatable_text'],
                    ['id' => '_store_option2_name', 'label' => 'Nama Opsi (Advance)', 'type' => 'text'],
                    ['id' => '_store_advanced_options', 'label' => 'Opsi Advance', 'type' => 'group_advanced_options'],
                ],
            ],
            [
                'id' => 'gallery',
                'title' => 'Gallery',
                'fields' => [
                    ['id' => '_store_gallery_ids', 'label' => 'Gambar Produk', 'type' => 'file_list'],
                ],
            ],
        ];
        return $schema;
    }

    public function enqueue_styles()
    {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'store_product') {
            wp_enqueue_style(
                'wp-store-admin-cmb2',
                WP_STORE_URL . 'assets/admin/css/xmb2.css',
                [],
                WP_STORE_VERSION
            );

            wp_enqueue_script(
                'wp-store-admin-js',
                WP_STORE_URL . 'assets/admin/js/store-admin.js',
                ['jquery'],
                WP_STORE_VERSION,
                true
            );
        }
    }

    public function register_metaboxes()
    {
        $schema = self::get_schema();
        foreach ($schema as $tab) {
            $box = new_cmb2_box([
                'id'            => 'wp_store_product_' . $tab['id'],
                'title'         => $tab['title'],
                'object_types'  => ['store_product'],
                'context'       => 'normal',
                'priority'      => 'high',
                'show_names'    => true,
                'vertical_tabs' => true,
            ]);
            foreach ($tab['fields'] as $field) {
                $args = [
                    'name' => $field['label'],
                    'id'   => $field['id'],
                ];
                if ($field['type'] === 'select') {
                    $args['type'] = 'select';
                    $args['options'] = $field['options'];
                    if (!empty($field['default'])) {
                        $args['default'] = $field['default'];
                    }
                } elseif ($field['type'] === 'number') {
                    $args['type'] = 'text';
                    $args['attributes'] = [
                        'type' => 'number',
                        'min'  => (string) ($field['attributes']['min'] ?? ''),
                        'step' => (string) ($field['attributes']['step'] ?? ''),
                    ];
                } elseif ($field['type'] === 'datetime-local') {
                    $args['type'] = 'text';
                    $args['attributes'] = ['type' => 'datetime-local'];
                } elseif ($field['type'] === 'file') {
                    $args['type'] = 'file';
                    $args['text'] = ['add_upload_files_text' => 'Upload File'];
                } elseif ($field['type'] === 'repeatable_text') {
                    $args['type'] = 'text';
                    $args['repeatable'] = true;
                    $args['text'] = ['add_row_text' => 'Tambah Opsi'];
                } elseif ($field['type'] === 'group_advanced_options') {
                    $group_field_id = $box->add_field([
                        'name'    => $field['label'],
                        'id'      => $field['id'],
                        'type'    => 'group',
                        'options' => [
                            'group_title'   => 'Opsi {#}',
                            'add_button'    => 'Tambah Opsi',
                            'remove_button' => 'Hapus Opsi',
                            'sortable'      => true,
                        ],
                    ]);
                    $box->add_group_field($group_field_id, [
                        'name' => 'Label',
                        'id'   => 'label',
                        'type' => 'text',
                    ]);
                    $box->add_group_field($group_field_id, [
                        'name'       => 'Harga',
                        'id'         => 'price',
                        'type'       => 'text',
                        'attributes' => ['type' => 'number'],
                    ]);
                    continue;
                } elseif ($field['type'] === 'file_list') {
                    $args['type'] = 'file_list';
                    $args['text'] = [
                        'add_upload_files_text' => 'Tambah Gambar',
                        'remove_image_text'     => 'Hapus',
                        'file_text'             => 'Gambar',
                        'file_download_text'    => 'Download',
                        'remove_text'           => 'Hapus',
                    ];
                } else {
                    $args['type'] = 'text';
                }
                $box->add_field($args);
            }
        }
    }
}
