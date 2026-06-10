<?php if (! defined('ABSPATH')) exit; ?>

<div x-data="window.wpStoreCartModal">
    <div x-show="toastShow" x-transition x-cloak
        :style="'position:fixed;top:20px;right:20px;bottom:auto;padding:12px 16px;background:#fff;box-shadow:0 3px 10px rgba(0,0,0,.1);border-left:4px solid ' + (toastType === 'success' ? '#46b450' : '#d63638') + ';border-radius:4px;z-index:9999999;'">
        <span x-text="toastMessage" class="wps-text-sm wps-text-gray-900"></span>
    </div>

    <div x-show="open" x-cloak class="wps-modal-backdrop" @click.self="open = false"></div>

    <div x-show="open" x-cloak class="wps-modal">
        <div class="wps-p-4">
            <div class="wps-mb-4 wps-text-lg wps-font-medium wps-text-gray-900">Pilih Opsi</div>

            <div class="wps-mb-4" x-show="product?.basic_name && product?.basic_values?.length" x-cloak>
                <label class="wps-label" x-text="product?.basic_name || ''"></label>
                <select class="wps-select" x-model="selectedBasic">
                    <option value="">-- Pilih --</option>
                    <template x-for="opt in (product?.basic_values || [])" :key="opt">
                        <option :value="opt" x-text="opt"></option>
                    </template>
                </select>
            </div>

            <div class="wps-mb-4" x-show="product?.adv_name && product?.adv_values?.length" x-cloak>
                <label class="wps-label" x-text="product?.adv_name || ''"></label>
                <select class="wps-select" x-model="selectedAdv">
                    <option value="">-- Pilih --</option>
                    <template x-for="opt in (product?.adv_values || [])"
                        :key="typeof opt === 'object' ? (opt?.label || '') : opt">
                        <option :value="typeof opt === 'object' ? (opt?.label || '') : opt"
                            x-text="typeof opt === 'object' ? (parseFloat(opt?.price) > 0 ? (opt?.label || '') + ' (' + formatCurrency(opt?.price) + ')' : (opt?.label || '')) : opt">
                        </option>
                    </template>
                </select>
            </div>

            <div class="wps-mb-4 wps-pt-2 wps-border-t wps-flex wps-justify-between wps-items-center">
                <span class="wps-text-sm wps-text-gray-500">Harga Total:</span>
                <span class="wps-text-lg wps-font-bold wps-text-gray-900" x-text="formatCurrency(totalPrice)"></span>
            </div>

            <div class="wps-flex wps-justify-between wps-items-center">
                <button type="button" class="wps-btn wps-btn-secondary wps-btn-sm" @click="open = false">Batal</button>
                <button type="button" class="wps-btn wps-btn-primary wps-btn-sm" @click="confirmAdd()"
                    :disabled="loading || !canSubmit()" :style="loading ? 'opacity:.7; pointer-events:none;' : ''">
                    <template x-if="loading">
                        <span><?php echo wps_icon(['name' => 'spinner', 'size' => 16, 'class' => 'wps-mr-2']); ?></span>
                    </template>
                    <span>Tambah</span>
                </button>
            </div>
        </div>
    </div>
</div>