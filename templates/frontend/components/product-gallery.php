<div class="wps-product-gallery" data-wps-gallery="1" id="wps-gallery-<?php echo esc_attr($id); ?>">
    <div class="wps-gallery-main">
        <div class="wps-gallery-zoom">
            <img class="wps-gallery-main-img" 
                 src="<?php echo esc_url($items[0]['full']); ?>" 
                 alt="" 
                 data-wps-lightbox-src="<?php echo esc_url($items[0]['full']); ?>"
                 data-gallery-images='<?php echo esc_attr(json_encode(array_column($items, 'full'))); ?>'>
            <div class="wps-gallery-zoom-lens"></div>
        </div>
    </div>
    
    <div class="wps-gallery-thumbs">
        <?php foreach ($items as $idx => $item) : ?>
            <button class="wps-gallery-thumb <?php echo $idx === 0 ? 'active' : ''; ?>"
                    data-main-src="<?php echo esc_url($item['full']); ?>"
                    data-index="<?php echo esc_attr($idx); ?>"
                    type="button">
                <img src="<?php echo esc_url($item['thumb']); ?>" alt="">
            </button>
        <?php endforeach; ?>
    </div>
</div>
