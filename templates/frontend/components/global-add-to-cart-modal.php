<?php if (! defined('ABSPATH')) exit; ?>

<div x-data="{
    get data() {
        return window.wpStoreCartModal || {
            open: false,
            loading: false,
            toastShow: false,
            toastType: 'success',
            toastMessage: '',
            currency: 'Rp',
            product: {
                id: 0,
                title: '',
                base_price: 0,
                basic_name: '',
                basic_values: [],
                adv_name: '',
                adv_values: []
            },
            selectedBasic: '',
            selectedAdv: '',
            formatCurrency: function(value) {
                const v = typeof value === 'number' ? value : parseFloat(value || 0);
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v);
            },
            hasOptions: function() {
                const product = this.product || {};
                return ((product.basic_name && product.basic_values && product.basic_values.length) ||
                    (product.adv_name && product.adv_values && product.adv_values.length));
            },
            canSubmit: function() {
                const product = this.product || {};
                const needBasic = !!(product.basic_name && product.basic_values && product.basic_values.length);
                const needAdv = !!(product.adv_name && product.adv_values && product.adv_values.length);
                return (!needBasic || !!this.selectedBasic) && (!needAdv || !!this.selectedAdv);
            },
            confirmAdd: function() {
                if (window.wpStoreCartModal) {
                    window.wpStoreCartModal.confirmAdd();
                }
            }
        };
    }
}">

    <div x-show="data.toastShow" x-transition x-cloak
        style="position:fixed;top:20px;right:20px;bottom:auto;padding:12px 16px;background:#fff;box-shadow:0 3px 10px rgba(0,0,0,.1);border-left:4px solid #46b450;border-radius:4px;z-index:9999999;">
        <span x-text="data.toastMessage" class="wps-text-sm wps-text-gray-900"></span>
    </div>

    <div x-show="data.open" x-cloak class="wps-modal-backdrop" @click.self="window.wpStoreCartModal.open = false"></div>

    <div x-show="data.open" x-cloak class="wps-modal">
        <div class="wps-p-4">
            <div class="wps-mb-4 wps-text-lg wps-font-medium wps-text-gray-900">Pilih Opsi</div>

            <div class="wps-mb-4" x-show="data.product?.basic_name && data.product?.basic_values?.length" x-cloak>
                <label class="wps-label" x-text="data.product?.basic_name || ''"></label>
                <select class="wps-select" x-model="data.selectedBasic">
                    <option value="">-- Pilih --</option>
                    <template x-for="opt in (data.product?.basic_values || [])" :key="opt">
                        <option :value="opt" x-text="opt"></option>
                    </template>
                </select>
            </div>

            <div class="wps-mb-4" x-show="data.product?.adv_name && data.product?.adv_values?.length" x-cloak>
                <label class="wps-label" x-text="data.product?.adv_name || ''"></label>
                <select class="wps-select" x-model="data.selectedAdv">
                    <option value="">-- Pilih --</option>
                    <template x-for="opt in (data.product?.adv_values || [])"
                        :key="typeof opt === 'object' ? (opt?.label || '') : opt">
                        <option :value="typeof opt === 'object' ? (opt?.label || '') : opt"
                            x-text="typeof opt === 'object' ? (parseFloat(opt?.price) > 0 ? (opt?.label || '') + ' (' + data.formatCurrency(opt?.price) + ')' : (opt?.label || '')) : opt">
                        </option>
                    </template>
                </select>
            </div>

            <div class="wps-mb-4 wps-pt-2 wps-border-t wps-flex wps-justify-between wps-items-center">
                <span class="wps-text-sm wps-text-gray-500">Harga Total:</span>
                <span class="wps-text-lg wps-font-bold wps-text-gray-900" x-text="data.formatCurrency((() => {
                    const product = data.product || { base_price: 0, adv_values: [] };
                    let total = product.base_price || 0;
                    if (data.selectedAdv && product.adv_values && product.adv_values.length) {
                        const opt = product.adv_values.find(o => {
                            if (typeof o === 'object' && o !== null) {
                                return o.label === data.selectedAdv;
                            }
                            return o === data.selectedAdv;
                        });
                        if (opt && typeof opt === 'object' && opt.price) {
                            const optPrice = parseFloat(opt.price);
                            if (!isNaN(optPrice) && optPrice > 0) {
                                total = optPrice;
                            }
                        }
                    }
                    return total;
                })())"></span>
            </div>

            <div class="wps-flex wps-justify-between wps-items-center">
                <button type="button" class="wps-btn wps-btn-secondary wps-btn-sm"
                    @click="window.wpStoreCartModal.open = false">Batal</button>
                <button type="button" class="wps-btn wps-btn-primary wps-btn-sm" @click="data.confirmAdd()"
                    :disabled="data.loading || !data.canSubmit()"
                    :style="data.loading ? 'opacity:.7; pointer-events:none;' : ''">
                    <template x-if="data.loading">
                        <span><?php echo wps_icon(['name' => 'spinner', 'size' => 16, 'class' => 'wps-mr-2']); ?></span>
                    </template>
                    <span>Tambah</span>
                </button>
            </div>
        </div>
    </div>
</div>