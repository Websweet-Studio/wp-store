<?php
if (!defined('ABSPATH')) {
    exit;
}
$shortcode = new \WpStore\Frontend\Shortcode();
echo $shortcode->render_shop([]);
