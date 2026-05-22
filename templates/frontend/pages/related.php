<div class="wps-p-4">
    <div class="wps-text-sm wps-text-gray-900 wps-mb-4">Produk Terkait</div>
    <?php if (!empty($items)) : ?>
        <div class="wps-products-carousel wps-related-carousel"
            data-wps-carousel
            data-cell-align="left"
            data-contain="true"
            data-wrap-around="true"
            data-page-dots="false"
            data-prev-next-buttons="true"
            data-draggable="true">
            <div class="main-carousel">
                <?php foreach ($items as $item) : ?>
                    <div class="carousel-cell">
                        <?php echo \WpStore\Frontend\Template::render('components/product-card', ['item' => $item, 'currency' => $currency, 'view_label' => 'Lihat']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else : ?>
        <div class="wps-text-sm wps-text-gray-500">Tidak ada produk terkait.</div>
    <?php endif; ?>
</div>
