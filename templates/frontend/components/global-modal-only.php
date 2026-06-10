<?php if (! defined('ABSPATH')) exit; ?>

<!-- Modal Global (SATU PER HALAMAN) - disimpan di footer via wp_footer -->
<div x-data="wpsCartModal()">
    <!-- Toast -->
    <div x-show="toastShow" x-transition x-cloak
         style="position:fixed;top:20px;right:20px;padding:12px 16px;background:#fff;box-shadow:0 3px 10px rgba(0,0,0,.1);border-left:4px solid #46b450;border-radius:4px;z-index:99999999;">
        <span x-text="toastMessage"></span>
    </div>

    <!-- Modal Backdrop -->
    <div x-show="open" x-cloak class="wps-modal-backdrop" style="z-index:99999997;"
         @click.self="open = false"></div>

    <!-- Modal Content -->
    <div x-show="open" x-cloak class="wps-modal" style="z-index:99999998;">
        <div class="wps-p-4">
            <div class="wps-mb-4 wps-text-lg wps-font-medium wps-text-gray-900">Pilih Opsi</div>

            <!-- Basic Options -->
            <div class="wps-mb-4" x-show="product?.basic_name && product?.basic_values?.length" x-cloak>
                <label class="wps-label" x-text="product?.basic_name"></label>
                <select class="wps-select" x-model="selectedBasic">
                    <option value="">-- Pilih --</option>
                    <template x-for="opt in product?.basic_values" :key="opt">
                        <option :value="opt" x-text="opt"></option>
                    </template>
                </select>
            </div>

            <!-- Advanced Options -->
            <div class="wps-mb-4" x-show="product?.adv_name && product?.adv_values?.length" x-cloak>
                <label class="wps-label" x-text="product?.adv_name"></label>
                <select class="wps-select" x-model="selectedAdv">
                    <option value="">-- Pilih --</option>
                    <template x-for="opt in product?.adv_values" :key="typeof opt === 'object' ? (opt?.label || '') : opt">
                        <option :value="typeof opt === 'object' ? (opt?.label || '') : opt"
                                x-text="typeof opt === 'object' ? (parseFloat(opt?.price) > 0 ? (opt?.label || '') + ' (' + formatCurrency(opt?.price) + ')' : (opt?.label || '')) : opt">
                        </option>
                    </template>
                </select>
            </div>

            <!-- Total Price -->
            <div class="wps-mb-4 wps-pt-2 wps-border-t wps-flex wps-justify-between wps-items-center">
                <span class="wps-text-sm wps-text-gray-500">Harga Total:</span>
                <span class="wps-text-lg wps-font-bold wps-text-gray-900" x-text="formatCurrency(totalPrice)"></span>
            </div>

            <!-- Buttons -->
            <div class="wps-flex wps-justify-between wps-items-center">
                <button type="button" class="wps-btn wps-btn-secondary wps-btn-sm"
                        @click="open = false">Batal</button>
                <button type="button" class="wps-btn wps-btn-primary wps-btn-sm"
                        @click="confirmAdd()"
                        :disabled="loading || !canSubmit()"
                        :style="loading ? 'opacity:.7;pointer-events:none' : ''">
                    <template x-if="loading">
                        <span><?php echo wps_icon(['name' => 'spinner', 'size' => 16, 'class' => 'wps-mr-2']); ?></span>
                    </template>
                    <span>Tambah</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function wpsCartModal() {
        return {
            open: false,
            loading: false,
            toastShow: false,
            toastType: 'success',
            toastMessage: '',
            currency: '<?php echo esc_js($currency); ?>',
            product: null,
            selectedBasic: '',
            selectedAdv: '',
            nonce: '<?php echo esc_js($nonce); ?>',
            restUrl: '<?php echo esc_js(rest_url('wp-store/v1/')); ?>',
            
            init() {
                document.addEventListener('wpsOpenCartModal', (e) => {
                    this.product = e.detail;
                    this.selectedBasic = '';
                    this.selectedAdv = '';
                    if (this.hasOptions()) {
                        this.open = true;
                    } else {
                        this.confirmAdd();
                    }
                });
            },

            formatCurrency(value) {
                const v = typeof value === 'number' ? value : parseFloat(value || 0);
                if (this.currency === 'USD' || this.currency === '$') {
                    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(v);
                }
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v);
            },

            get totalPrice() {
                const product = this.product || { base_price: 0, adv_values: [] };
                let total = product.base_price || 0;
                if (this.selectedAdv && product.adv_values && product.adv_values.length) {
                    const opt = product.adv_values.find(o => {
                        if (typeof o === 'object' && o !== null) {
                            return o.label === this.selectedAdv;
                        }
                        return o === this.selectedAdv;
                    });
                    if (opt && typeof opt === 'object' && opt.price) {
                        const optPrice = parseFloat(opt.price);
                        if (!isNaN(optPrice) && optPrice > 0) {
                            total = optPrice;
                        }
                    }
                }
                return total;
            },

            hasOptions() {
                const product = this.product || {};
                return ((product.basic_name && product.basic_values && product.basic_values.length) ||
                    (product.adv_name && product.adv_values && product.adv_values.length));
            },

            canSubmit() {
                const product = this.product || {};
                const needBasic = !!(product.basic_name && product.basic_values && product.basic_values.length);
                const needAdv = !!(product.adv_name && product.adv_values && product.adv_values.length);
                return (!needBasic || !!this.selectedBasic) && (!needAdv || !!this.selectedAdv);
            },

            getOptionsPayload() {
                const product = this.product || {};
                const opts = {};
                const bName = (product.basic_name || '').trim();
                const bVal = (this.selectedBasic || '').trim();
                const aName = (product.adv_name || '').trim();
                const aVal = (this.selectedAdv || '').trim();
                if (bName && bVal) {
                    opts[bName] = bVal;
                }
                if (aName && aVal) {
                    opts[aName] = aVal;
                }
                const sorted = {};
                Object.keys(opts).sort().forEach(k => sorted[k] = opts[k]);
                return sorted;
            },

            stringifyOptions(obj) {
                const sorted = {};
                Object.keys(obj || {}).sort().forEach(k => sorted[k] = obj[k]);
                try {
                    return JSON.stringify(sorted);
                } catch (e) {
                    return '{}';
                }
            },

            async confirmAdd() {
                const product = this.product || { id: 0 };
                if (!product.id || product.id <= 0) {
                    this.showToast('Produk tidak valid', 'error');
                    return;
                }
                this.loading = true;
                try {
                    let currentQty = 0;
                    try {
                        const resCart = await fetch(this.restUrl + 'cart', {
                            credentials: 'same-origin',
                            headers: { 'X-WP-Nonce': this.nonce }
                        });
                        const dataCart = await resCart.json();
                        const opts = this.getOptionsPayload();
                        const item = (dataCart.items || []).find(i => {
                            if (i.id !== product.id) return false;
                            return this.stringifyOptions(i.options || {}) === this.stringifyOptions(opts);
                        });
                        currentQty = item ? (item.qty || 0) : 0;
                    } catch (e) {}
                    const nextQty = currentQty + 1;
                    const res = await fetch(this.restUrl + 'cart', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': this.nonce },
                        body: JSON.stringify({ id: product.id, qty: nextQty, options: this.getOptionsPayload() })
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        this.showToast(data.message || 'Gagal menambah', 'error');
                        return;
                    }
                    document.dispatchEvent(new CustomEvent('wp-store:cart-updated', { detail: data }));
                    this.showToast('Ditambahkan ke keranjang', 'success');
                    this.open = false;
                } catch (e) {
                    this.showToast('Kesalahan jaringan', 'error');
                } finally {
                    this.loading = false;
                }
            },

            showToast(msg, type) {
                this.toastMessage = msg || '';
                this.toastType = type === 'error' ? 'error' : 'success';
                this.toastShow = true;
                clearTimeout(this._toastTimer);
                this._toastTimer = setTimeout(() => {
                    this.toastShow = false;
                }, 2000);
            }
        }
    }
</script>