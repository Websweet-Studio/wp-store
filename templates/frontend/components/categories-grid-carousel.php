<?php if (!empty($items)) : ?>
  <?php
  $view = isset($view) ? sanitize_key($view) : 'grid';
  if (!in_array($view, ['grid', 'carousel', 'list', 'circle', 'overlay'], true)) {
    $view = 'grid';
  }
  // Shortcut: view=circle => grid style=circle, view=overlay => grid style=overlay
  if ($view === 'circle') {
    $style = 'circle';
    $view = 'grid';
  } elseif ($view === 'overlay') {
    $style = 'overlay';
    $view = 'grid';
  }
  $is_carousel = ($view === 'carousel');
  $is_list = ($view === 'list');

  $style = isset($style) ? sanitize_key($style) : 'card';
  if (!in_array($style, ['card', 'circle', 'overlay', 'clean'], true)) {
    $style = 'card';
  }

  $columns = isset($columns) ? (int) $columns : 4;
  if ($columns <= 0) $columns = 4;
  $show_count = isset($show_count) ? (bool) $show_count : false;
  $show_image = isset($show_image) ? (bool) $show_image : true;
  $show_description = isset($show_description) ? (bool) $show_description : false;
  $w = isset($img_width) ? (int) $img_width : 300;
  if ($w <= 0) $w = 300;
  $h = isset($img_height) ? (int) $img_height : 200;
  if ($h <= 0) $h = 200;
  $crop = isset($crop) ? (bool) $crop : true;
  $size = [$w, $h];
  $style_img = 'width:100%; height:100%; object-fit:' . ($crop ? 'cover' : 'contain') . ';';
  $aspect_ratio = (int) $w . ' / ' . (int) $h;
  $noimg = WP_STORE_URL . 'assets/frontend/img/noimg.webp';
  $opt = isset($opts) && is_array($opts) ? $opts : [];
  $cell_align = sanitize_key($opt['cell_align'] ?? 'center');
  $contain = !empty($opt['contain']);
  $wrap = !empty($opt['wrap_around']);
  $dots = !empty($opt['page_dots']);
  $buttons = !empty($opt['prev_next_buttons']);
  $lazy = isset($opt['lazy_load']) ? (int) $opt['lazy_load'] : 0;
  $autoplay = isset($opt['autoplay']) ? (int) $opt['autoplay'] : 0;
  $pause_hover = !empty($opt['pause_on_hover']);
  $draggable = !empty($opt['draggable']);
  $group_cells = $is_carousel && $columns > 1 ? (int) $columns : 0;

  // Style class for item
  $item_class = 'wps-cat-item';
  $wrapper_class = '';
  if ($style === 'circle') {
    $item_class .= ' wps-cat-circle';
    // circle uses square aspect ratio for the image
    $aspect_ratio = '1 / 1';
    $style_img = 'width:100%; height:100%; object-fit:cover; border-radius:50%;';
  } elseif ($style === 'overlay') {
    $item_class .= ' wps-cat-overlay';
  } elseif ($style === 'clean') {
    $item_class .= ' wps-cat-clean';
  }
  ?>

  <?php
  // Renders a single category item
  $render_item = function ($item) use ($show_image, $show_count, $show_description, $aspect_ratio, $style_img, $noimg, $style) {
    $name = (string) ($item['name'] ?? '');
    $link = (string) ($item['link'] ?? '');
    $src = (string) ($item['image'] ?? '');
    $count = (int) ($item['count'] ?? 0);
    $desc = (string) ($item['description'] ?? '');
    ob_start();
  ?>
    <?php if ($style === 'overlay' && $show_image) : ?>
      <div class="wps-cat-image-wrap" style="width:100%; aspect-ratio: <?php echo esc_attr($aspect_ratio); ?>;">
        <img class="wps-cat-image" src="<?php echo esc_url($src ?: $noimg); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover;display:block;">
        <div class="wps-cat-overlay-info">
          <div class="wps-cat-name"><?php echo esc_html($name); ?></div>
          <?php if ($show_count) : ?>
            <div class="wps-cat-count"><?php echo esc_html($count); ?> Produk</div>
          <?php endif; ?>
          <?php if ($show_description && $desc !== '') : ?>
            <div class="wps-cat-desc"><?php echo esc_html($desc); ?></div>
          <?php endif; ?>
        </div>
      </div>
    <?php else : ?>
      <?php if ($show_image) : ?>
        <div class="wps-cat-image-wrap" style="width:100%; aspect-ratio: <?php echo esc_attr($aspect_ratio); ?>;">
          <img class="wps-cat-image" src="<?php echo esc_url($src ?: $noimg); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy" style="<?php echo esc_attr($style_img); ?>">
        </div>
      <?php endif; ?>
      <div class="wps-cat-info">
        <div class="wps-cat-name"><?php echo esc_html($name); ?></div>
        <?php if ($show_count) : ?>
          <div class="wps-cat-count"><?php echo esc_html($count); ?> Produk</div>
        <?php endif; ?>
        <?php if ($show_description && $desc !== '') : ?>
          <div class="wps-cat-desc"><?php echo esc_html($desc); ?></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php
    return ob_get_clean();
  };
  ?>

  <?php if ($is_carousel) : ?>
    <div class="wps-products-carousel wps-categories-carousel"
      data-wps-carousel="1"
      data-cell-align="<?php echo esc_attr($cell_align); ?>"
      data-contain="<?php echo $contain ? 'true' : 'false'; ?>"
      data-wrap-around="<?php echo $wrap ? 'true' : 'false'; ?>"
      data-page-dots="<?php echo $dots ? 'true' : 'false'; ?>"
      data-prev-next-buttons="<?php echo $buttons ? 'true' : 'false'; ?>"
      data-lazy-load="<?php echo (int) $lazy; ?>"
      data-autoplay="<?php echo (int) $autoplay; ?>"
      data-pause-on-hover="<?php echo $pause_hover ? 'true' : 'false'; ?>"
      data-draggable="<?php echo $draggable ? 'true' : 'false'; ?>"
      data-group-cells="<?php echo (int) $group_cells; ?>">
      <?php if (isset($label) && is_string($label) && $label !== '') : ?>
        <div class="wps-text-sm wps-text-gray-900 wps-mb-3"><?php echo esc_html($label); ?></div>
      <?php endif; ?>
      <div>
        <div class="main-carousel" style="width:100%;">
          <?php foreach ($items as $item) : ?>
            <a href="<?php echo esc_url($item['link'] ?: '#'); ?>" class="carousel-cell wps-cat-cell"
              style="width:calc(100% / <?php echo (int) $columns; ?>); margin-right:8px; display:block;">
              <div class="<?php echo esc_attr($item_class); ?>">
                <?php echo $render_item($item); ?>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  <?php elseif ($is_list) : ?>
    <div class="wps-categories-list">
      <?php if (isset($label) && is_string($label) && $label !== '') : ?>
        <div class="wps-text-sm wps-text-gray-900 wps-mb-3"><?php echo esc_html($label); ?></div>
      <?php endif; ?>
      <?php foreach ($items as $item) : ?>
        <?php
        $name = (string) ($item['name'] ?? '');
        $link = (string) ($item['link'] ?? '');
        $src = (string) ($item['image'] ?? '');
        $count = (int) ($item['count'] ?? 0);
        $desc = (string) ($item['description'] ?? '');
        ?>
        <a href="<?php echo esc_url($link ?: '#'); ?>" class="wps-cat-list-item">
          <?php if ($show_image) : ?>
            <div class="wps-cat-list-image" style="flex:0 0 80px; width:80px; aspect-ratio: 1 / 1; overflow:hidden; border-radius:8px;">
              <img src="<?php echo esc_url($src ?: $noimg); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover;display:block;">
            </div>
          <?php endif; ?>
          <div class="wps-cat-list-body">
            <div class="wps-cat-name"><?php echo esc_html($name); ?></div>
            <?php if ($show_count) : ?>
              <div class="wps-cat-count"><?php echo esc_html($count); ?> Produk</div>
            <?php endif; ?>
            <?php if ($show_description && $desc !== '') : ?>
              <div class="wps-cat-desc"><?php echo esc_html($desc); ?></div>
            <?php endif; ?>
          </div>
          <span class="wps-cat-list-arrow">&rsaquo;</span>
        </a>
      <?php endforeach; ?>
    </div>

  <?php else : ?>
    <div class="wps-categories-grid" style="--wps-cols: <?php echo (int) $columns; ?>;">
      <?php if (isset($label) && is_string($label) && $label !== '') : ?>
        <div class="wps-text-sm wps-text-gray-900 wps-mb-3 wps-col-full"><?php echo esc_html($label); ?></div>
      <?php endif; ?>
      <?php foreach ($items as $item) : ?>
        <a href="<?php echo esc_url($item['link'] ?: '#'); ?>" class="wps-cat-grid-item">
          <div class="<?php echo esc_attr($item_class); ?>">
            <?php echo $render_item($item); ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php else : ?>
  <div class="wps-text-sm wps-text-gray-500">Tidak ada kategori.</div>
<?php endif; ?>
