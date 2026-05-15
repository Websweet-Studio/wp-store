<?php
get_header();
$currency = (get_option('wp_store_settings', [])['currency_symbol'] ?? 'Rp');
$items = [];
global $wp_query;
if ($wp_query && $wp_query->have_posts()) {
    while ($wp_query->have_posts()) {
        $wp_query->the_post();
        $id = get_the_ID();
        $price = get_post_meta($id, '_store_price', true);
        $stock = get_post_meta($id, '_store_stock', true);
        $image = get_the_post_thumbnail_url($id, 'medium');
        $items[] = [
            'id' => $id,
            'title' => get_the_title(),
            'link' => get_permalink(),
            'image' => $image ? $image : null,
            'price' => $price !== '' ? (float) $price : null,
            'stock' => $stock !== '' ? (int) $stock : null,
        ];
    }
    wp_reset_postdata();
}
$page = (int) max(1, get_query_var('paged'));
$pages = (int) ($wp_query ? $wp_query->max_num_pages : 0);
$total = (int) ($wp_query ? $wp_query->found_posts : 0);
?>
<div class="wps-container wps-mx-auto wps-my-8">
    <div class="wps-text-lg wps-font-medium wps-text-gray-900 wps-mb-4 wps-pt-4">Produk</div>
    <?php echo do_shortcode('[wp_store_shop_with_filters per_page="12"]'); ?>
</div>
<?php
get_footer();
