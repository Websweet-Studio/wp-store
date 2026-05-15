<?php if (!empty($items)) : ?>
    <div id="wps-shop" class="">
        <div class="wps-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));<?php echo (count($items) === 1) ? ' max-width: 360px;' : ''; ?>">
            <?php foreach ($items as $item) : ?>
                <?php echo \WpStore\Frontend\Template::render('components/product-card', ['item' => $item, 'currency' => $currency, 'view_label' => 'Detail']); ?>
            <?php endforeach; ?>
        </div>
        <?php if (isset($pages) && (int) $pages > 1) : ?>
            <div class="wps-flex wps-items-center wps-gap-2 wps-mt-4" style="justify-content: center;">
                <?php
                $is_archive = (is_post_type_archive('store_product') || (get_query_var('post_type') === 'store_product' && !is_singular()));
                if ($is_archive) {
                    $prev_link = (int) $page > 1 ? add_query_arg($_GET, get_pagenum_link((int) $page - 1)) : '';
                    $next_link = (int) $page < (int) $pages ? add_query_arg($_GET, get_pagenum_link((int) $page + 1)) : '';
                } else {
                    $base = get_permalink();
                    $prev_link = (int) $page > 1 ? add_query_arg('shop_page', (int) $page - 1, $base) : '';
                    $next_link = (int) $page < (int) $pages ? add_query_arg('shop_page', (int) $page + 1, $base) : '';
                }
                ?>
                <?php if ($prev_link) : ?>
                    <a href="<?php echo esc_url($prev_link); ?>" class="wps-btn wps-btn-secondary wps-btn-sm">Sebelumnya</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= (int) $pages; $i++) : ?>
                    <?php
                    $page_link = $is_archive ? add_query_arg($_GET, get_pagenum_link($i)) : add_query_arg('shop_page', $i, get_permalink());
                    ?>
                    <a href="<?php echo esc_url($page_link); ?>" class="wps-btn <?php echo ($i === (int) $page) ? 'wps-btn-primary' : 'wps-btn-secondary'; ?> wps-btn-sm"><?php echo esc_html($i); ?></a>
                <?php endfor; ?>
                <?php if ($next_link) : ?>
                    <a href="<?php echo esc_url($next_link); ?>" class="wps-btn wps-btn-secondary wps-btn-sm">Berikutnya</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
<?php else : ?>
    <div id="wps-shop" class="">
        <div class="wps-text-sm wps-text-gray-500">Belum ada produk.</div>
    </div>
<?php endif; ?>
