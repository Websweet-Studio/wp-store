<script>
    if (typeof window.wpStoreSettings === 'undefined') {
        window.wpStoreSettings = {
            restUrl: '<?php echo esc_url_raw(rest_url('wp-store/v1/')); ?>',
            nonce: '<?php echo esc_js($nonce); ?>'
        };
    }
</script>
<script>
    window.wpStoreCheckout = function() {
        return {
            loading: true,
            submitting: false,
            allowSubmit: false,
            warnShow: false,
            warnMessage: '',
            _warnTimer: null,
            loggedIn: <?php echo is_user_logged_in() ? 'true' : 'false'; ?>,
            cart: [],
            total: 0,
            totalSelected() {
                if (!Array.isArray(this.cart)) return 0;
                return this.cart.reduce((sum, i) => {
                    const sel = i.selected !== false;
                    const sub = typeof i.subtotal === 'number' ? i.subtotal : parseFloat(i.subtotal || 0);
                    return sel ? (sum + (isNaN(sub) ? 0 : sub)) : sum;
                }, 0);
            },
            paymentMethod: 'transfer_bank',
            name: '',
            email: '',
            phone: '',
            address: '',
            profile: {
                first_name: '',
                last_name: '',
                email: '',
                phone: ''
            },
            addresses: [],
            selectedAddressId: '',
            provinces: [],
            cities: [],
            subdistricts: [],
            selectedProvince: '',
            selectedCity: '',
            selectedSubdistrict: '',
            postalCode: '',
            notes: '',
            isLoadingProvinces: false,
            isLoadingCities: false,
            isLoadingSubdistricts: false,
            message: '',
            toastShow: false,
            toastType: 'error',
            toastMessage: '',
            showToast(msg, type) {
                this.toastMessage = msg || '';
                this.toastType = type === 'success' ? 'success' : 'error';
                this.toastShow = true;
                clearTimeout(this._toastTimer);
                this._toastTimer = setTimeout(() => {
                    this.toastShow = false;
                }, 2000);
            },
            captchaRequired: <?php echo is_user_logged_in() ? 'false' : 'true'; ?>,
            isCaptchaReady() {
                if (!this.captchaRequired) return true;
                const root = document.getElementById('checkout-captcha');
                if (!root) return false;
                const val = root.querySelector('input[name="captcha_value"]');
                const idf = root.querySelector('input[name="captcha_id"]');
                const v = String(val && val.value ? val.value : '').trim();
                const i = String(idf && idf.value ? idf.value : '').trim();
                const vf = root.querySelector('input[name="captcha_verified"]');
                const vv = String(vf && vf.value ? vf.value : '').trim();
                return v !== '' && i !== '' && vv === '1';
            },
            currency: '<?php echo esc_js($currency); ?>',
            originSubdistrict: '<?php echo esc_js($origin_subdistrict); ?>',
            shippingCouriers: <?php echo json_encode($active_couriers); ?>,
            shippingCourier: '',
            shippingService: '',
            shippingCost: 0,
            shippingOptions: [],
            selectedShippingKey: '',
            couponCode: '',
            discountAmount: 0,
            discountLabel: '',
            formatPrice(value) {
                const v = typeof value === 'number' ? value : parseFloat(value || 0);
                if (this.currency === 'USD') {
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
            async calculateAllShipping() {
                if (!this.selectedSubdistrict || !Array.isArray(this.shippingCouriers) || this.shippingCouriers.length === 0) {
                    this.recomputeAllow();
                    return;
                }
                this.shippingOptions = [];
                this.selectedShippingKey = '';
                this.shippingCourier = '';
                this.shippingService = '';
                this.shippingCost = 0;
                try {
                    const res = await fetch(wpStoreSettings.restUrl + 'rajaongkir/calculate', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': wpStoreSettings.nonce
                        },
                        body: JSON.stringify({
                            destination_province: this.selectedProvince,
                            destination_city: this.selectedCity,
                            destination_subdistrict: this.selectedSubdistrict,
                            courier: this.shippingCouriers.join(':'),
                            items: (Array.isArray(this.cart) ? this.cart.filter(i => i.selected !== false).map(i => ({
                                id: i.id,
                                qty: i.qty,
                                options: i.options || {}
                            })) : [])
                        })
                    });
                    const data = await res.json();
                    if (res.ok && data && data.success && Array.isArray(data.services)) {
                        this.shippingOptions = data.services.map(s => ({
                            courier: s.courier || '',
                            service: s.service || '',
                            description: s.description || '',
                            cost: s.cost || 0,
                            etd: s.etd || ''
                        }));
                    }
                } catch (e) {}
                const first = this.shippingOptions.find(s => s.cost > 0) || this.shippingOptions[0] || null;
                if (first) {
                    this.selectedShippingKey = first.courier + ':' + (first.service || '');
                    this.shippingCourier = first.courier || '';
                    this.shippingService = first.service || '';
                    this.shippingCost = first.cost || 0;
                }
                this.recomputeAllow();
            },
            onSelectService() {
                const parts = String(this.selectedShippingKey || '').split(':');
                const c = parts[0] || '';
                const svc = parts[1] || '';
                const opt = this.shippingOptions.find(s => String(s.courier) === String(c) && String(s.service) === String(svc));
                if (opt) {
                    this.shippingCourier = opt.courier || '';
                    this.shippingService = opt.service || '';
                    this.shippingCost = opt.cost || 0;
                }
            },
            totalWithShipping() {
                const t = this.totalSelected();
                const s = typeof this.shippingCost === 'number' ? this.shippingCost : parseFloat(this.shippingCost || 0);
                const d = typeof this.discountAmount === 'number' ? this.discountAmount : parseFloat(this.discountAmount || 0);
                return Math.max(0, t - (isNaN(d) ? 0 : d)) + (isNaN(s) ? 0 : s);
            },
            getValidationError() {
                if (!this.name) return 'Nama wajib diisi.';
                if (!Array.isArray(this.cart) || this.cart.length === 0) return 'Keranjang kosong.';
                if (!this.cart.find(i => i.selected !== false)) return 'Pilih setidaknya satu produk.';
                if (!this.selectedProvince) return 'Provinsi wajib dipilih.';
                if (!this.selectedCity) return 'Kota/Kabupaten wajib dipilih.';
                if (!this.selectedSubdistrict) return 'Kecamatan wajib dipilih.';
                if (!this.address || String(this.address).trim() === '') return 'Alamat wajib diisi.';
                if (Array.isArray(this.shippingOptions) && this.shippingOptions.length > 0) {
                    if (!this.selectedShippingKey) return 'Wajib pilih ongkir.';
                    const parts = String(this.selectedShippingKey || '').split(':');
                    const c = parts[0] || '';
                    const svc = parts[1] || '';
                    const opt = this.shippingOptions.find(s => String(s.courier) === String(c) && String(s.service || '') === String(svc));
                    const costNum = opt ? (typeof opt.cost === 'number' ? opt.cost : parseFloat(opt.cost || 0)) : 0;
                    if (!opt || isNaN(costNum) || costNum <= 0) return 'Wajib pilih ongkir.';
                }
                return '';
            },
            getBlockingReasons() {
                const reasons = [];
                if (!Array.isArray(this.cart) || this.cart.length === 0) {
                    return ['Keranjang kosong.'];
                }
                if (!this.cart.find(i => i.selected !== false)) {
                    return ['Tidak ada produk yang dipilih.'];
                }
                if (!this.name) reasons.push('Nama wajib diisi.');
                if (!this.email) reasons.push('Email wajib diisi.');
                if (!this.phone) reasons.push('Telepon wajib diisi.');
                if (!this.selectedProvince) reasons.push('Provinsi wajib dipilih.');
                if (!this.selectedCity) reasons.push('Kota/Kabupaten wajib dipilih.');
                if (!this.selectedSubdistrict) reasons.push('Kecamatan wajib dipilih.');
                if (!this.address || String(this.address).trim() === '') reasons.push('Alamat wajib diisi.');
                if (Array.isArray(this.shippingOptions) && this.shippingOptions.length > 0) {
                    if (!this.selectedShippingKey) {
                        reasons.push('Wajib pilih ongkir.');
                    } else {
                        const parts = String(this.selectedShippingKey || '').split(':');
                        const c = parts[0] || '';
                        const svc = parts[1] || '';
                        const opt = this.shippingOptions.find(s => String(s.courier) === String(c) && String(s.service || '') === String(svc));
                        const costNum = opt ? (typeof opt.cost === 'number' ? opt.cost : parseFloat(opt.cost || 0)) : 0;
                        if (!opt || isNaN(costNum) || costNum <= 0) reasons.push('Ongkir tidak valid.');
                    }
                }
                return reasons;
            },
            canSubmit() {
                return this.getValidationError() === '';
            },
            recomputeAllow() {
                this.allowSubmit = this.getBlockingReasons().length === 0;
                if (this.captchaRequired && !this.isCaptchaReady()) {
                    this.allowSubmit = false;
                }
            },
            trySubmit() {
                if (this.submitting) return;
                if (this.captchaRequired && !this.isCaptchaReady()) {
                    this.warnMessage = 'Verifikasi captcha terlebih dahulu.';
                    this.warnShow = true;
                    return;
                }
                if (!this.allowSubmit) {
                    const r = this.getBlockingReasons()[0] || 'Tidak dapat melanjutkan';
                    this.warnMessage = r;
                    this.warnShow = true;
                    if (this._warnTimer) {
                        clearTimeout(this._warnTimer);
                        this._warnTimer = null;
                    }
                    this._warnTimer = setTimeout(() => {
                        this.warnShow = false;
                        this._warnTimer = null;
                    }, 3000);
                    return;
                }
                this.submit();
            },
            async fetchProfile() {
                try {
                    const res = await fetch(wpStoreSettings.restUrl + 'customer/profile', {
                        credentials: 'same-origin',
                        headers: {
                            'X-WP-Nonce': wpStoreSettings.nonce
                        }
                    });
                    if (!res.ok) return;
                    this.profile = await res.json();
                } catch (e) {}
            },
            async fetchAddresses() {
                try {
                    const res = await fetch(wpStoreSettings.restUrl + 'customer/addresses', {
                        credentials: 'same-origin',
                        headers: {
                            'X-WP-Nonce': wpStoreSettings.nonce
                        }
                    });
                    if (!res.ok) return;
                    this.addresses = await res.json();
                } catch (e) {}
            },
            useProfile() {
                if (!this.profile) return;
                const fullName = [this.profile.first_name || '', this.profile.last_name || ''].filter(Boolean).join(' ').trim();
                this.name = fullName || this.name;
                this.email = this.profile.email || this.email;
                this.phone = this.profile.phone || this.phone;
            },
            async useAddressById() {
                const addr = this.addresses.find(a => String(a.id) === String(this.selectedAddressId));
                if (!addr) return;
                await this.applyAddress(addr);
            },
            async applyAddress(addr) {
                this.address = addr.address || '';
                this.selectedProvince = addr.province_id ? String(addr.province_id) : '';
                await this.loadCities();
                this.selectedCity = addr.city_id ? String(addr.city_id) : '';
                this.postalCode = addr.postal_code || this.postalCode;
                await this.loadSubdistricts();
                this.selectedSubdistrict = addr.subdistrict_id ? String(addr.subdistrict_id) : '';
                await this.calculateAllShipping();
            },
            async loadProvinces() {
                this.isLoadingProvinces = true;
                try {
                    const res = await fetch(wpStoreSettings.restUrl + 'rajaongkir/provinces', {
                        credentials: 'same-origin',
                        headers: {
                            'X-WP-Nonce': wpStoreSettings.nonce
                        }
                    });
                    const data = await res.json();
                    this.provinces = data.data || [];
                } catch (e) {
                    this.provinces = [];
                } finally {
                    this.isLoadingProvinces = false;
                }
            },
            async loadCities() {
                if (!this.selectedProvince) {
                    this.cities = [];
                    return;
                }
                this.isLoadingCities = true;
                try {
                    const res = await fetch(wpStoreSettings.restUrl + 'rajaongkir/cities?province=' + encodeURIComponent(this.selectedProvince), {
                        credentials: 'same-origin',
                        headers: {
                            'X-WP-Nonce': wpStoreSettings.nonce
                        }
                    });
                    const data = await res.json();
                    this.cities = data.data || [];
                } catch (e) {
                    this.cities = [];
                } finally {
                    this.isLoadingCities = false;
                }
            },
            async loadSubdistricts() {
                if (!this.selectedCity) {
                    this.subdistricts = [];
                    return;
                }
                this.isLoadingSubdistricts = true;
                try {
                    const res = await fetch(wpStoreSettings.restUrl + 'rajaongkir/subdistricts?city=' + encodeURIComponent(this.selectedCity), {
                        credentials: 'same-origin',
                        headers: {
                            'X-WP-Nonce': wpStoreSettings.nonce
                        }
                    });
                    const data = await res.json();
                    this.subdistricts = data.data || [];
                } catch (e) {
                    this.subdistricts = [];
                } finally {
                    this.isLoadingSubdistricts = false;
                }
            },
            async fetchCart() {
                this.loading = true;
                try {
                    const res = await fetch(wpStoreSettings.restUrl + 'cart', {
                        credentials: 'same-origin',
                        headers: {
                            'X-WP-Nonce': wpStoreSettings.nonce
                        }
                    });
                    const data = await res.json();
                    this.cart = (Array.isArray(data.items) ? data.items.map(i => Object.assign({}, i, {
                        selected: true
                    })) : []);
                    this.total = data.total || 0;
                } catch (e) {
                    this.cart = [];
                    this.total = 0;
                } finally {
                    this.loading = false;
                    this.recomputeAllow();
                }
            },
            async applyCoupon() {
                const code = String(this.couponCode || '').trim();
                if (code === '') {
                    this.discountAmount = 0;
                    this.discountLabel = '';
                    return;
                }
                try {
                    const res = await fetch(wpStoreSettings.restUrl + 'coupons/validate', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': wpStoreSettings.nonce
                        },
                        body: JSON.stringify({
                            code,
                            items: this.cart.filter(i => i.selected !== false).map(i => ({
                                id: i.id,
                                qty: i.qty,
                                options: i.options || {}
                            }))
                        })
                    });
                    const data = await res.json();
                    if (res.ok && data && data.success) {
                        this.discountAmount = data.discount || 0;
                        if (data.type === 'percent') {
                            this.discountLabel = `Diskon Kupon (${data.value || 0}%)`;
                        } else {
                            this.discountLabel = 'Diskon Kupon';
                        }
                        this.showToast('Kupon berhasil diterapkan.', 'success');
                    } else {
                        this.discountAmount = 0;
                        this.discountLabel = '';
                        this.showToast(data && data.message ? data.message : 'Kupon tidak valid.', 'error');
                    }
                } catch (e) {
                    this.discountAmount = 0;
                    this.discountLabel = '';
                    this.showToast('Gagal memproses kupon.', 'error');
                }
            },
            async importFromProfile() {
                await this.fetchProfile();
                if (!this.profile || !this.profile.email) {
                    window.location.href = '<?php echo esc_js(site_url('/profil-saya/?tab=profile')); ?>';
                    return;
                }
                this.useProfile();
            },
            async submit() {
                const err = this.getValidationError();
                if (err) {
                    this.message = err;
                    this.showToast(err, 'error');
                    return;
                }
                this.submitting = true;
                this.message = '';
                try {
                    const res = await fetch(wpStoreSettings.restUrl + 'checkout', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': wpStoreSettings.nonce
                        },
                        body: JSON.stringify({
                            name: this.name,
                            email: this.email,
                            phone: this.phone,
                            address: this.address,
                            province_id: this.selectedProvince || '',
                            province_name: (this.provinces.find(p => String(p.province_id) === String(this.selectedProvince)) || {}).province || '',
                            city_id: this.selectedCity || '',
                            city_name: (this.cities.find(c => String(c.city_id) === String(this.selectedCity)) || {}).city_name || '',
                            subdistrict_id: this.selectedSubdistrict || '',
                            subdistrict_name: (this.subdistricts.find(s => String(s.subdistrict_id) === String(this.selectedSubdistrict)) || {}).subdistrict_name || '',
                            postal_code: this.postalCode || '',
                            notes: this.notes || '',
                            shipping_courier: this.shippingCourier || '',
                            shipping_service: this.shippingService || '',
                            shipping_cost: this.shippingCost || 0,
                            payment_method: this.paymentMethod || 'transfer_bank',
                            coupon_code: this.couponCode || '',
                            items: this.cart.filter(i => i.selected !== false).map(i => ({
                                id: i.id,
                                qty: i.qty,
                                options: i.options || {}
                            }))
                        })
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        this.message = data.message || 'Gagal membuat pesanan.';
                        return;
                    }
                    this.message = data.message || 'Pesanan berhasil dibuat.';
                    try {
                        const selected = this.cart.filter(i => i.selected !== false);
                        for (const i of selected) {
                            await fetch(wpStoreSettings.restUrl + 'cart', {
                                method: 'POST',
                                credentials: 'include',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-WP-Nonce': wpStoreSettings.nonce
                                },
                                body: JSON.stringify({
                                    id: i.id,
                                    qty: 0,
                                    options: i.options || {}
                                })
                            });
                        }
                    } catch (_) {}
                    this.cart = [];
                    this.total = 0;
                    document.dispatchEvent(new CustomEvent('wp-store:cart-updated', {
                        detail: {
                            items: [],
                            total: 0
                        }
                    }));
                    try {
                        const base = typeof wpStoreSettings !== 'undefined' && wpStoreSettings.thanksUrl ? wpStoreSettings.thanksUrl : '<?php echo esc_js(site_url('/thanks/')); ?>';
                        const url = new URL(base, window.location.origin);
                        if (data && data.order_number) {
                            url.searchParams.set('order', String(data.order_number));
                        } else if (data && data.id) {
                            url.searchParams.set('order', String(data.id));
                        }
                        window.location.href = url.toString();
                    } catch (e) {}
                } catch (e) {
                    this.message = 'Terjadi kesalahan jaringan.';
                } finally {
                    this.submitting = false;
                }
            },
            async init() {
                this.loading = true;
                try {
                    await this.fetchCart();
                    if (this.loggedIn) {
                        await this.fetchProfile();
                        await this.fetchAddresses();
                    }
                    await this.loadProvinces();
                } catch (e) {
                    console.error(e);
                } finally {
                    this.loading = false;
                    this.recomputeAllow();
                }
            }
        };
    };
</script>
<div class="">
    <div x-data="wpStoreCheckout()" x-init="init()" x-effect="recomputeAllow()">
        <div x-show="loading" class="wps-flex wps-justify-center wps-items-center wps-p-12 wps-text-gray-500">
            <svg class="wps-animate-spin wps-mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="width: 24px; height: 24px;">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Memuat data...</span>
        </div>
        <div x-show="!loading" style="display: none;">
            <template x-if="cart.length === 0">
                <div class="wps-card">
                    <div class="wps-p-6 wps-text-center">
                        <div class="wps-flex wps-justify-center wps-items-center wps-mb-3">
                            <?php echo wps_icon(['name' => 'cart', 'size' => 64]); ?>
                        </div>
                        <div class="wps-text-sm wps-text-gray-700 wps-mb-3 wps-mt-3">Keranjang kosong. Silakan kembali berbelanja.</div>
                        <div class="wps-mt-3">
                            <?php
                            $shop_archive = function_exists('get_post_type_archive_link') ? get_post_type_archive_link('store_product') : '';
                            $shop_url = $shop_archive ?: site_url('/produk/');
                            ?>
                            <a href="<?php echo esc_url($shop_url); ?>" class="wps-btn wps-btn-primary">Belanja</a>
                        </div>
                    </div>
                </div>
            </template>
            <div class="wps-grid wps-grid-cols-1 wps-md-grid-cols-2 wps-gap-4" x-show="cart.length > 0">
                <div>
                    <div class="wps-card wps-mb-4">
                        <div class="wps-p-4">
                            <div class="wps-text-lg wps-font-medium wps-mb-4 wps-text-bold">Informasi Pemesan</div>
                            <?php if (is_user_logged_in()) : ?>
                                <div class="wps-flex wps-items-center wps-gap-2 wps-mb-4">
                                    <button type="button" class="wps-btn wps-btn-sm wps-btn-primary" @click="importFromProfile()"><?php echo wps_icon(['name' => 'cloud-arrow-down', 'size' => 16, 'class' => 'wps-mr-2']); ?>Impor Profil</button>
                                    <a href="<?php echo esc_url(site_url('/profil-saya/?tab=profile')); ?>" class="wps-btn wps-btn-sm wps-btn-secondary"><?php echo wps_icon(['name' => 'sliders2', 'size' => 16, 'class' => 'wps-mr-2']); ?>Kelola</a>
                                </div>
                            <?php endif; ?>
                            <div class="wps-form-group">
                                <label class="wps-label">Nama</label>
                                <input class="wps-input" type="text" x-model="name" placeholder="Nama lengkap">
                            </div>
                            <div class="wps-form-group">
                                <label class="wps-label">Email</label>
                                <input class="wps-input" type="email" x-model="email" placeholder="email@contoh.com">
                            </div>
                            <div class="wps-form-group">
                                <label class="wps-label">Telepon/WA</label>
                                <input class="wps-input" type="text" x-model="phone" placeholder="08xxxxxxxxxx">
                            </div>
                        </div>
                    </div>
                    <div class="wps-card">
                        <div class="wps-p-4">
                            <div class="wps-text-lg wps-font-medium wps-mb-4 wps-text-bold">Alamat Penerima</div>
                            <div class="wps-form-group" x-show="addresses && addresses.length">
                                <div class="wps-flex wps-flex-wrap" style="gap:8px;">
                                    <template x-for="addr in addresses" :key="addr.id">
                                        <button type="button"
                                            @click="selectedAddressId = addr.id; useAddressById()"
                                            class="wps-btn wps-btn-sm"
                                            :class="String(selectedAddressId) === String(addr.id) ? 'wps-btn-dark' : 'wps-btn-primary'">
                                            <?php echo wps_icon(['name' => 'map-pin', 'size' => 16, 'class' => 'wps-mr-2', 'border-color' => '#fff']); ?>
                                            <span
                                                :style="String(selectedAddressId) === String(addr.id) ? 'color:#fff;' : 'color:#1f2937;'"
                                                x-text="(addr.label ? addr.label + ' - ' : '') + (addr.city_name || '')"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <div class="wps-form-group">
                                <label class="wps-label">Provinsi</label>
                                <select class="wps-select" x-model="selectedProvince" @change="selectedCity=''; selectedSubdistrict=''; postalCode=''; loadCities()">
                                    <option value="">-- Pilih Provinsi --</option>
                                    <template x-for="prov in provinces" :key="prov.province_id">
                                        <option :value="prov.province_id" x-text="prov.province"></option>
                                    </template>
                                </select>
                                <div class="wps-text-xs wps-text-gray-500" x-show="isLoadingProvinces">Memuat provinsi...</div>
                            </div>
                            <div class="wps-form-group">
                                <label class="wps-label">Kota/Kabupaten</label>
                                <select class="wps-select" x-model="selectedCity" @change="selectedSubdistrict=''; postalCode=(cities.find(c => String(c.city_id) === String(selectedCity)) || {}).postal_code || ''; loadSubdistricts()" :disabled="!selectedProvince">
                                    <option value="">-- Pilih Kota/Kabupaten --</option>
                                    <template x-for="c in cities" :key="c.city_id">
                                        <option :value="c.city_id" x-text="c.city_name"></option>
                                    </template>
                                </select>
                                <div class="wps-text-xs wps-text-gray-500" x-show="isLoadingCities">Memuat kota...</div>
                            </div>
                            <div class="wps-form-group">
                                <label class="wps-label">Kecamatan</label>
                                <select class="wps-select" x-model="selectedSubdistrict" :disabled="!selectedCity" @change="calculateAllShipping()">
                                    <option value="">-- Pilih Kecamatan --</option>
                                    <template x-for="s in subdistricts" :key="s.subdistrict_id">
                                        <option :value="s.subdistrict_id" x-text="s.subdistrict_name"></option>
                                    </template>
                                </select>
                                <div class="wps-text-xs wps-text-gray-500" x-show="isLoadingSubdistricts">Memuat kecamatan...</div>
                            </div>
                            <div class="wps-form-group">
                                <label class="wps-label">Alamat Lengkap</label>
                                <textarea class="wps-textarea" rows="3" x-model="address"></textarea>
                            </div>
                            <div class="wps-form-group">
                                <label class="wps-label">Kode Pos</label>
                                <input class="wps-input" type="text" x-model="postalCode" placeholder="">
                            </div>
                            <div class="wps-form-group">
                                <label class="wps-label">Catatan</label>
                                <textarea class="wps-textarea" rows="3" x-model="notes" placeholder="Catatan tambahan untuk pesanan"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="wps-card wps-mb-4">
                        <div class="wps-p-4">
                            <div class="wps-text-lg wps-font-medium wps-mb-4 wps-text-bold">Metode Pengiriman</div>
                            <div class="wps-mb-2">
                                <div class="wps-mt-2" x-show="selectedSubdistrict && shippingOptions.length > 0">
                                    <template x-for="opt in shippingOptions" :key="opt.courier + ':' + opt.service">
                                        <button type="button" class="wps-w-full wps-btn wps-d-block wps-btn-secondary wps-btn-sm wps-mb-2"
                                            :style="(selectedShippingKey === (opt.courier + ':' + opt.service)) ? 'border-left:4px solid #3b82f6;background:#f0f9ff;' : ''"
                                            @click="selectedShippingKey = opt.courier + ':' + opt.service; onSelectService()">
                                            <div class="wps-flex wps-justify-between wps-items-center wps-w-full">
                                                <span x-text="opt.courier.toUpperCase() + ' ' + opt.service"></span>
                                                <span class="wps-flex wps-items-center wps-gap-2">
                                                    <span x-text="formatPrice(opt.cost)"></span>
                                                </span>
                                            </div>
                                            <div class="wps-flex wps-justify-between wps-items-center wps-w-full wps-text-xxs wps-text-gray-500">
                                                <span x-text="(opt.description || '')"></span>
                                                <span x-text="(opt.etd || '')"></span>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                                <div class="wps-text-xs wps-text-gray-500 wps-mt-2" x-show="!originSubdistrict">Asal pengiriman belum diatur di pengaturan.</div>
                                <div class="wps-text-xs wps-text-gray-500 wps-mt-1" x-show="Array.isArray(shippingCouriers) && shippingCouriers.length === 0">Tidak ada kurir aktif di pengaturan.</div>
                                <div class="wps-text-xxs wps-text-gray-500 wps-mt-1" x-show="!selectedSubdistrict">Lengkapi alamat pengiriman untuk menampilkan opsi pengiriman.</div>
                                <div class="wps-text-xs wps-text-gray-500 wps-mt-1" x-show="selectedSubdistrict && shippingOptions.length === 0">Tidak ada layanan tersedia, ubah kurir atau kecamatan.</div>
                            </div>
                        </div>
                    </div>
                    <div class="wps-card">
                        <div class="wps-p-4">
                            <div class="wps-text-lg wps-font-medium wps-mb-4 wps-text-bold">Ringkasan Order</div>
                            <template x-if="cart.length === 0">
                                <div class="wps-text-sm wps-text-gray-500">Keranjang kosong.</div>
                            </template>
                            <template x-for="item in cart" :key="item.id + ':' + (item.options ? JSON.stringify(item.options) : '')">
                                <div class="wps-flex wps-items-center wps-gap-2 wps-divider">
                                    <input type="checkbox" x-model="item.selected" class="wps-checkbox" @change="calculateAllShipping(); if (couponCode) applyCoupon();">
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
                                    </div>
                                </div>
                            </template>
                            <div class="wps-mt-4">
                                <div class="wps-flex wps-justify-between wps-items-center">
                                    <span class="wps-text-sm wps-text-gray-500">Total Produk</span>
                                    <span class="wps-text-sm wps-text-gray-900" x-text="formatPrice(totalSelected())"></span>
                                </div>
                                <div class="wps-flex wps-items-center wps-gap-2 wps-mt-2">
                                    <input class="wps-input wps-flex-1" type="text" x-model="couponCode" placeholder="Masukkan kode kupon">
                                    <button type="button" class="wps-btn wps-btn-secondary wps-btn-sm" @click="applyCoupon()">
                                        <?php echo wps_icon(['name' => 'coupon', 'size' => 16, 'class' => 'wps-mr-2']); ?>
                                        Terapkan
                                    </button>
                                </div>
                                <template x-if="discountAmount">
                                    <div class="wps-flex wps-justify-between wps-items-center wps-mt-2">
                                        <span class="wps-text-sm wps-text-gray-500" x-text="discountLabel || 'Diskon Kupon'"></span>
                                        <span class="wps-text-sm wps-text-green-700" x-text="'- ' + formatPrice(discountAmount)"></span>
                                    </div>
                                </template>
                                <template x-if="shippingCost">
                                    <div class="wps-flex wps-justify-between wps-items-center wps-mt-2">
                                        <span class="wps-text-sm wps-text-gray-500">
                                            Ongkir (
                                            <template x-if="shippingCourier">
                                                <span x-text="shippingCourier.toUpperCase() + ' '"></span>
                                            </template>
                                            <span x-text="shippingService"></span>
                                            )
                                        </span>
                                        <span class="wps-text-sm wps-text-gray-900" x-text="formatPrice(shippingCost)"></span>
                                    </div>
                                </template>
                                <div class="wps-flex wps-justify-between wps-items-center wps-mt-2">
                                    <span class="wps-text-sm wps-text-gray-900 wps-font-medium">Total Tagihan</span>
                                    <span class="wps-text-sm wps-text-gray-900 wps-font-medium" x-text="formatPrice(totalWithShipping())"></span>
                                </div>
                            </div>
                            <div class="wps-mt-4">
                                <div class="wps-text-lg wps-font-medium wps-mb-2 wps-text-bold">Metode Pembayaran</div>
                                <div class="wps-flex wps-items-center wps-gap-2 wps-mb-2 wps-flex-wrap">
                                    <button type="button" class="wps-btn wps-btn-secondary wps-btn-sm"
                                        :style="paymentMethod === 'transfer_bank' ? 'border-left:4px solid #3b82f6;background:#f0f9ff;' : ''"
                                        @click="paymentMethod = 'transfer_bank'">
                                        <span class="wps-text-sm wps-text-gray-900">Transfer Bank</span>
                                    </button>
                                    <button type="button" class="wps-btn wps-btn-secondary wps-btn-sm"
                                        :style="paymentMethod === 'qris' ? 'border-left:4px solid #3b82f6;background:#f0f9ff;' : ''"
                                        @click="paymentMethod = 'qris'">
                                        <span class="wps-text-sm wps-text-gray-900">QRIS</span>
                                    </button>
                                </div>

                                <div class="wps-mt-2" x-show="!loggedIn" id="checkout-captcha" @input="recomputeAllow()" @change="recomputeAllow()">
                                    <?php echo \WpStore\Frontend\Template::render('components/captcha'); ?>
                                </div>

                                <div class="wps-mt-4 wps-flex wps-justify-end">
                                    <button type="button" class="wps-btn wps-btn-primary" :disabled="submitting || !allowSubmit" @click="trySubmit()">
                                        <?php echo wps_icon(['name' => 'cart', 'size' => 16, 'class' => 'wps-mr-2']); ?>
                                        <span x-show="submitting">Memproses...</span>
                                        <span x-show="!submitting">Buat Pesanan</span>
                                    </button>
                                </div>
                                <div class="wps-text-sm wps-text-red-700 wps-mt-2 p-2 wps-bg-red-100 rounded-md wps-mt-2" style="border-left:4px solid #ef4444;" x-show="warnShow" x-text="warnMessage">
                                </div>
                                <div class="wps-text-sm wps-text-gray-900 wps-mt-2" x-text="message"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div x-show="toastShow" x-transition x-cloak
            :style="'position:fixed;bottom:30px;right:30px;padding:12px 16px;background:#fff;box-shadow:0 3px 10px rgba(0,0,0,.1);border-left:4px solid ' + (toastType === 'success' ? '#46b450' : '#d63638') + ';border-radius:4px;z-index:9999;'">
            <span x-text="toastMessage" class="wps-text-sm wps-text-gray-900"></span>
        </div>
    </div>
</div>