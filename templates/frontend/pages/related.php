<div class="wps-p-4">
    <div class="wps-text-sm wps-text-gray-900 wps-mb-4">Produk Terkait</div>
    <?php if (!empty($items)) : ?>
        <div class="wps-grid wps-grid-cols-2 wps-md-grid-cols-4">
            <?php foreach ($items as $item) : ?>
                <?php echo \WpStore\Frontend\Template::render('components/product-card', ['item' => $item, 'currency' => $currency, 'view_label' => 'Lihat']); ?>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="wps-text-sm wps-text-gray-500">Tidak ada produk terkait.</div>
    <?php endif; ?>
</div>
