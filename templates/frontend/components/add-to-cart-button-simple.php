<?php if (! defined('ABSPATH')) exit; ?>

<?php
// Script pembuka modal hanya dimuat satu kali
static $script_once = false;
if (!$script_once) :
    $script_once = true;
?>
    <script>
        function wpsOpenCartModal(btnEl) {
            const productJson = btnEl.getAttribute('data-product');
            try {
                const product = JSON.parse(productJson);
                document.dispatchEvent(new CustomEvent('wpsOpenCartModal', {
                    detail: product
                }));
            } catch (e) {
                console.error('Error parsing product data:', e);
            }
        }
    </script>
<?php endif; ?>

<!-- Tombol Add to Cart -->
<div>
    <button type="button"
        class="<?php echo esc_attr($btn_class); ?>"
        data-product='<?php echo esc_attr(wp_json_encode([
                            'id' => $id,
                            'title' => get_the_title($id),
                            'base_price' => $base_price,
                            'basic_name' => $basic_name,
                            'basic_values' => $basic_values,
                            'adv_name' => $adv_name,
                            'adv_values' => $adv_values,
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)); ?>'
        onclick="wpsOpenCartModal(this)">
        <span style="width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;margin-right:8px;">
            <?php echo wps_icon(['name' => 'cart', 'size' => 20, 'class' => 'wps-icon-20 wps-mr-2']); ?>
        </span>
        <?php echo esc_html($label); ?>
    </button>
</div>