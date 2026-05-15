<script>
    if (typeof window.wpStoreSettings === 'undefined') {
        window.wpStoreSettings = {
            restUrl: '<?php echo esc_url_raw(rest_url('wp-store/v1/')); ?>',
            nonce: '<?php echo esc_js($nonce); ?>'
        };
    }
</script>
<div x-data="{
        open: false,
        loading: false,
        updatingKey: '',
        cart: [],
        total: 0,
        currency: '<?php echo esc_js($currency); ?>',
        formatPrice(value) {
            const v = typeof value === 'number' ? value : parseFloat(value || 0);
            if (this.currency === 'USD') {
                return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(v);
            }
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v);
        },
        getItemKey(i) {
            const opts = i && i.options ? i.options : {};
            let s = '';
            try { s = JSON.stringify(opts); } catch (e) { s = ''; }
            return String(i.id) + ':' + s;
        },
        async fetchCart() {
            try {
                const res = await fetch(wpStoreSettings.restUrl + 'cart', { 
                    credentials: 'same-origin',
                    headers: { 'X-WP-Nonce': wpStoreSettings.nonce }
                });
                const data = await res.json();
                this.cart = data.items || [];
                this.total = data.total || 0;
            } catch(e) {
                this.cart = [];
                this.total = 0;
            }
        },
        async updateItem(item, qty) {
            this.loading = true;
            try {
                const res = await fetch(wpStoreSettings.restUrl + 'cart', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': wpStoreSettings.nonce
                    },
                    body: JSON.stringify({ id: item.id, qty, options: (item.options || {}) })
                });
                const data = await res.json();
                if (!res.ok) {
                    return;
                }
                this.cart = data.items || [];
                this.total = data.total || 0;
                document.dispatchEvent(new CustomEvent('wp-store:cart-updated', { detail: data }));
            } catch(e) {
            } finally {
                this.loading = false;
                this.updatingKey = '';
            }
        },
        increment(item) { this.updateItem(item, item.qty + 1); },
        decrement(item) { const q = item.qty > 1 ? item.qty - 1 : 0; this.updateItem(item, q); },
        remove(item) { this.updatingKey = this.getItemKey(item); this.updateItem(item, 0); },
        init() {
            this.fetchCart();
            document.addEventListener('wp-store:cart-updated', (e) => {
                const data = e.detail || {};
                this.cart = data.items || [];
                this.total = data.total || 0;
            });
        }
    }" x-init="init()" class="wps-rel">
    <button type="button" @click="open = true" class="wps-btn-icon wps-cart-button wps-rel">
        <span class="wps-text-2xl wps-pr-6">
            <?php echo wps_icon(['name' => 'cart', 'size' => 16]); ?>
        </span>
        <span x-text="cart.reduce((sum, item) => sum + (item.qty || 0), 0)" class="wps-absolute wps-top--6 wps-right-0 wps-bg-blue-500 wps-text-white wps-text-xs rounded-full wps-px-2.5 wps-py-0.5"></span>
    </button>
    <div class="wps-offcanvas-backdrop" x-show="open" @click="open = false" x-transition.opacity x-cloak></div>
    <div class="wps-offcanvas" x-show="open" x-transition x-cloak>
        <div class="wps-offcanvas-header">
            <strong class="wps-text-gray-900">Keranjang</strong>
            <button type="button" @click="open = false" class="wps-btn-icon">✕</button>
        </div>
        <div class="wps-offcanvas-body">
            <template x-if="cart.length === 0">
                <div class="wps-text-sm wps-text-gray-500 wps-flex wps-items-center wps-gap-2">
                    <span><?php echo wps_icon(['name' => 'cart', 'size' => 16]); ?></span>
                    <span>Keranjang kosong.</span>
                </div>
            </template>
            <template x-for="item in cart" :key="item.id + ':' + (item.options ? JSON.stringify(item.options) : '')">
                <div class="wps-flex wps-items-center wps-gap-2 wps-divider">
                    <img :src="item.image ? item.image : '<?php echo esc_url(WP_STORE_URL . 'assets/frontend/img/noimg.webp'); ?>'" alt="" class="wps-img-40">
                    <div style="flex: 1;">
                        <div x-text="item.title" class="wps-text-sm wps-text-gray-900"></div>
                        <template x-if="item.options && Object.keys(item.options).length">
                            <div class="wps-text-xs wps-text-gray-500">
                                <span x-text="Object.entries(item.options).map(([k,v]) => k + ': ' + v).join(' • ')"></span>
                            </div>
                        </template>
                        <div class="wps-text-xs wps-text-gray-500 wps-mb-1">
                            <span x-text="formatPrice(item.price)"></span>
                            <span> × </span>
                            <span x-text="item.qty"></span>
                            <span> = </span>
                            <span class="wps-text-gray-900" x-text="formatPrice(item.subtotal)"></span>
                        </div>
                        <div class="wps-flex wps-items-center wps-gap-1">
                            <button type="button" @click="decrement(item)" class="wps-btn wps-btn-secondary wps-btn-sm" style="padding: 2px 8px; font-size: 12px; line-height: 1; min-width: 24px; height: 22px;">-</button>
                            <span x-text="item.qty" class="wps-badge wps-badge-sm" style="font-size: 12px; padding: 2px 6px; line-height: 1;"></span>
                            <button type="button" @click="increment(item)" class="wps-btn wps-btn-secondary wps-btn-sm" style="padding: 2px 8px; font-size: 12px; line-height: 1; min-width: 24px; height: 22px;">+</button>
                            <button type="button" @click="remove(item)" :disabled="loading && updatingKey === getItemKey(item)" class="wps-btn wps-btn-danger wps-btn-sm wps-ml-auto" :style="(loading && updatingKey === getItemKey(item)) ? 'opacity:.7; pointer-events:none;' : ''">
                                <template x-if="loading && updatingKey === getItemKey(item)">
                                    <span><?php echo wps_icon(['name' => 'spinner', 'size' => 14]); ?></span>
                                </template>
                                <template x-if="!loading || updatingKey !== getItemKey(item)">
                                    <span><?php echo wps_icon(['name' => 'close', 'size' => 14]); ?></span>
                                </template>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <div class="wps-offcanvas-footer">
            <div class="wps-total-box">
                <div class="wps-total-label">Total</div>
                <div class="wps-total-amount" x-text="formatPrice(total)"></div>
            </div>
            <?php if (!empty($checkout_url)) : ?>
                <a href="<?php echo esc_url($checkout_url); ?>" class="wps-btn wps-btn-primary wps-btn-sm wps-checkout-btn" x-show="cart.length > 0"><?php echo wps_icon(['name' => 'credit-card', 'size' => 16, 'class' => 'wps-mr-2']); ?>Checkout</a>
            <?php endif; ?>
        </div>
    </div>
</div>