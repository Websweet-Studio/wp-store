<?php
$pid = isset($post_id) ? (int) $post_id : 0;
if ($pid <= 0) {
  $loop_id = get_the_ID();
  if ($loop_id && is_numeric($loop_id)) {
    $pid = (int) $loop_id;
  }
}
$home_url = home_url('/');
$archive_url = get_post_type_archive_link('store_product');
$chain = [];
$terms = get_the_terms($pid, 'store_product_cat');
if (is_array($terms) && !empty($terms)) {
  $primary = array_values($terms)[0];
  if ($primary && !is_wp_error($primary)) {
    $ancestors = get_ancestors((int) $primary->term_id, 'store_product_cat', 'taxonomy');
    $ancestors = array_reverse($ancestors);
    foreach ($ancestors as $tid) {
      $t = get_term((int) $tid, 'store_product_cat');
      if ($t && !is_wp_error($t)) {
        $chain[] = [
          'name' => (string) $t->name,
          'url' => get_term_link($t)
        ];
      }
    }
    $chain[] = [
      'name' => (string) $primary->name,
      'url' => get_term_link($primary)
    ];
  }
}
?>
<nav class="wps-text-sm wps-text-gray-500 wps-mb-2" aria-label="Breadcrumb">
  <a href="<?php echo esc_url($home_url); ?>" class="wps-text-primary-700">Beranda</a>
  <span class="wps-text-gray-400"> / </span>
  <?php if ($archive_url) : ?>
    <a href="<?php echo esc_url($archive_url); ?>" class="wps-text-primary-700">Produk</a>
    <span class="wps-text-gray-400"> / </span>
  <?php endif; ?>
  <?php if (!empty($chain)) : ?>
    <?php foreach ($chain as $i => $c) : ?>
      <a href="<?php echo esc_url($c['url']); ?>" class="wps-text-primary-700"><?php echo esc_html($c['name']); ?></a>
      <span class="wps-text-gray-400"> / </span>
    <?php endforeach; ?>
  <?php endif; ?>
  <span class="wps-text-gray-700"><?php echo esc_html(get_the_title($pid)); ?></span>
</nav>