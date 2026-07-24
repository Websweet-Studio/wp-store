<div class="wps-product-gallery">
    <?php if (count($items) > 1) : ?>
        <div class="wps-position-relative wps-w-full wps-products-carousel" data-wps-carousel data-wps-lightbox="1" data-cell-align="center" data-contain="true" data-wrap-around="true" data-page-dots="true" data-prev-next-buttons="true" data-draggable="true">
            <div class="main-carousel carousel-main" id="wps-gallery-main-<?php echo esc_attr($id); ?>">
                <?php foreach ($items as $idx => $gi) : ?>
                    <div class="carousel-cell wps-mx-0">
                        <div class="wps-gallery-zoom">
                            <img class="wps-w-full wps-rounded" src="<?php echo esc_url($gi['full']); ?>" alt="">
                            <div class="wps-gallery-zoom-lens"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="wps-mt-2 wps-products-carousel" data-wps-carousel data-as-nav-for="#wps-gallery-main-<?php echo esc_attr($id); ?>" data-cell-align="left" data-contain="true" data-wrap-around="false" data-page-dots="false" data-prev-next-buttons="false" data-draggable="true">
            <div class="main-carousel carousel-nav">
                <?php foreach ($items as $idx => $gi) : ?>
                    <div class="carousel-cell" style="width:80px;">
                        <img class="wps-img-80 wps-rounded" src="<?php echo esc_url($gi['thumb']); ?>" alt="">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else : ?>
        <div style="position:relative;display:block;">
            <div class="wps-gallery-zoom">
                <img class="wps-w-full wps-rounded" src="<?php echo esc_url($items[0]['full']); ?>" alt="" data-wps-lightbox-src="<?php echo esc_url($items[0]['full']); ?>">
                <div class="wps-gallery-zoom-lens"></div>
            </div>
        </div>
    <?php endif; ?>
</div>
