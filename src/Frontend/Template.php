<?php

namespace WpStore\Frontend;

class Template
{
    public static function render(string $template, array $data = []): string
    {
        $path = WP_STORE_PATH . 'templates/frontend/' . str_replace(['\\', '..'], ['/', ''], $template) . '.php';
        if (!file_exists($path)) {
            return '';
        }
        if (!empty($data)) {
            extract($data, EXTR_SKIP);
        }
        ob_start();
        require $path;
        return ob_get_clean();
    }
}
