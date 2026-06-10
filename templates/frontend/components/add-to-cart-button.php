<?php if (! defined('ABSPATH')) exit; ?>

<?php static $script_initialized = false;
if (!$script_initialized) :
    $script_initialized = true;
?>
    <script>
        if (typeof window.wpStoreCartModal === 'undefined') {
            window.wpStoreCartModal = {
                open: false,
                loading: false,
                toastShow: false,
                toastType: 'success',
                toastMessage: '',
                currency: '<?php echo esc_js($product_data['currency'] ?? 'Rp'); ?>',
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
                nonce: '<?php echo esc_js(wp_create_nonce('wp_rest')); ?>',
                restUrl: '<?php echo esc_js(rest_url('wp-store/v1/')); ?>',

                formatCurrency(value) {
                    const v = typeof value === 'number' ? value : parseFloat(value || 0);
                    if (this.currency === 'USD' || this.currency === '$') {
                        return new Intl.NumberFormat('en-US', {
                            style: 'currency',
                            currency: 'USD',
                            minimumFractionDigits: 0
                        }).format(v);
                    }
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0
                    }).format(v);
                },

                get totalPrice() {
                    const product = this.product || {
                        base_price: 0,
                        adv_values: []
                    };
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

                openModal(product) {
                    this.product = product || {
                        id: 0,
                        title: '',
                        base_price: 0,
                        basic_name: '',
                        basic_values: [],
                        adv_name: '',
                        adv_values: []
                    };
                    this.selectedBasic = '';
                    this.selectedAdv = '';

                    if (this.hasOptions()) {
                        this.open = true;
                    } else {
                        this.confirmAdd();
                    }
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
                    return this.normalizeOptions(opts);
                },

                normalizeOptions(obj) {
                    const o = obj || {};
                    const sorted = {};
                    Object.keys(o).sort().forEach((k) => {
                        sorted[k] = o[k];
                    });
                    return sorted;
                },

                stringifyOptions(obj) {
                    const n = this.normalizeOptions(obj || {});
                    try {
                        return JSON.stringify(n);
                    } catch (e) {
                        return '{}';
                    }
                },

                async add() {
                    if (this.hasOptions()) {
                        this.open = true;
                        return;
                    }
                    await this.confirmAdd();
                },

                async confirmAdd() {
                    const product = this.product || {
                        id: 0
                    };
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
                                headers: {
                                    'X-WP-Nonce': this.nonce
                                }
                            });
                            const dataCart = await resCart.json();
                            const opts = this.getOptionsPayload();
                            const item = (dataCart.items || []).find((i) => {
                                if (i.id !== product.id) return false;
                                return this.stringifyOptions(i.options || {}) === this.stringifyOptions(opts || {});
                            });
                            currentQty = item ? (item.qty || 0) : 0;
                        } catch (e) {}
                        const nextQty = currentQty + 1;
                        const res = await fetch(this.restUrl + 'cart', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': this.nonce
                            },
                            body: JSON.stringify({
                                id: product.id,
                                qty: nextQty,
                                options: this.getOptionsPayload()
                            })
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            this.showToast(data.message || 'Gagal menambah', 'error');
                            return;
                        }
                        document.dispatchEvent(new CustomEvent('wp-store:cart-updated', {
                            detail: data
                        }));
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
            };
        }
    </script>
<?php endif; ?>

<div>
    <button type="button"
        class="<?php echo esc_attr($btn_class); ?>"
        onclick='window.wpStoreCartModal.openModal(<?php echo wp_json_encode($product_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)'>
        <span class="wps-icon-cart" style="width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;margin-right:8px;">
            <?php echo wps_icon(['name' => 'cart', 'size' => 20, 'class' => 'wps-icon-20 wps-mr-2']); ?>
        </span>
        <?php echo esc_html($label); ?>
    </button>
</div>