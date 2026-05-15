<script>
    if (typeof window.wpStoreSettings === 'undefined') {
        window.wpStoreSettings = {
            restUrl: '<?php echo esc_url_raw(rest_url('wp-store/v1/')); ?>',
            nonce: '<?php echo esc_js($nonce); ?>'
        };
    }
</script>
<div x-data="{
        loading: false,
        message: '',
        toastShow: false,
        toastType: 'success',
        toastMessage: '',
        showModal: false,
        basicName: '<?php echo esc_js($basic_name); ?>',
        basicOptions: JSON.parse('<?php echo esc_js(wp_json_encode($basic_values)); ?>'),
        advName: '<?php echo esc_js($adv_name); ?>',
        advOptions: JSON.parse('<?php echo esc_js(wp_json_encode($adv_values)); ?>'),
        selectedBasic: '',
        selectedAdv: '',
        showToast(msg, type) {
            this.toastMessage = msg || '';
            this.toastType = type === 'error' ? 'error' : 'success';
            this.toastShow = true;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => { this.toastShow = false; }, 2000);
        },
        normalizeOptions(obj) {
            const o = obj || {};
            const sorted = {};
            Object.keys(o).sort().forEach((k) => { sorted[k] = o[k]; });
            return sorted;
        },
        stringifyOptions(obj) {
            const n = this.normalizeOptions(obj || {});
            try { return JSON.stringify(n); } catch(e) { return '{}'; }
        },
        hasOptions() {
            return ((this.basicName && this.basicOptions.length > 0) || (this.advName && this.advOptions.length > 0));
        },
        canSubmit() {
            const needBasic = !!(this.basicName && this.basicOptions.length);
            const needAdv = !!(this.advName && this.advOptions.length);
            return (!needBasic || !!this.selectedBasic) && (!needAdv || !!this.selectedAdv);
        },
        getOptionsPayload() {
            const opts = {};
            const bName = (this.basicName || '').trim();
            const bVal = (this.selectedBasic || '').trim();
            const aName = (this.advName || '').trim();
            const aVal = (this.selectedAdv || '').trim();
            if (bName && bVal) { opts[bName] = bVal; }
            if (aName && aVal) { opts[aName] = aVal; }
            return this.normalizeOptions(opts);
        },
        async add() {
            if (this.hasOptions()) {
                this.showModal = true;
                return;
            }
            await this.confirmAdd();
        },
        async confirmAdd() {
            this.loading = true;
            try {
                let currentQty = 0;
                try {
                    const resCart = await fetch(wpStoreSettings.restUrl + 'cart', { 
                        credentials: 'same-origin',
                        headers: { 'X-WP-Nonce': wpStoreSettings.nonce }
                    });
                    const dataCart = await resCart.json();
                    const opts = this.getOptionsPayload();
                    const item = (dataCart.items || []).find((i) => {
                        if (i.id !== <?php echo (int) $id; ?>) return false;
                        return this.stringifyOptions(i.options || {}) === this.stringifyOptions(opts || {});
                    });
                    currentQty = item ? (item.qty || 0) : 0;
                } catch (e) {}
                const nextQty = currentQty + 1;
                const res = await fetch(wpStoreSettings.restUrl + 'cart', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': wpStoreSettings.nonce
                    },
                    body: JSON.stringify({ id: <?php echo (int) $id; ?>, qty: nextQty, options: this.getOptionsPayload() })
                });
                const data = await res.json();
                if (!res.ok) {
                    this.showToast(data.message || 'Gagal menambah', 'error');
                    return;
                }
                document.dispatchEvent(new CustomEvent('wp-store:cart-updated', { detail: data }));
                this.showToast('Ditambahkan ke keranjang', 'success');
                this.showModal = false;
            } catch (e) {
                this.showToast('Kesalahan jaringan', 'error');
            } finally {
                this.loading = false;
            }
        }
    }">
    <button type="button" @click="add()" :disabled="loading" class="<?php echo esc_attr($btn_class); ?>" :style="loading ? 'opacity:.7; pointer-events:none;' : ''">
        <template x-if="loading">
            <span><?php echo wps_icon(['name' => 'spinner', 'size' => 18, 'class' => 'wps-mr-2']); ?></span>
        </template>
        <template x-if="!loading">
            <span><?php echo wps_icon(['name' => 'cart', 'size' => 20, 'class' => 'wps-icon-20 wps-mr-2']); ?></span>
        </template>
        <?php echo esc_html($label); ?>
    </button>
    <div x-show="toastShow" x-transition x-cloak
        :style="'position:fixed;bottom:30px;right:30px;padding:12px 16px;background:#fff;box-shadow:0 3px 10px rgba(0,0,0,.1);border-left:4px solid ' + (toastType === 'success' ? '#46b450' : '#d63638') + ';border-radius:4px;z-index:9999;'">
        <span x-text="toastMessage" class="wps-text-sm wps-text-gray-900"></span>
    </div>
    <div x-show="showModal" x-cloak class="wps-modal-backdrop" @click.self="showModal = false"></div>
    <div x-show="showModal" x-cloak class="wps-modal">
        <div class="wps-p-4">
            <div class="wps-mb-4 wps-text-lg wps-font-medium wps-text-gray-900">Pilih Opsi</div>
            <div class="wps-mb-4" x-show="basicName && basicOptions.length" x-cloak>
                <label class="wps-label" x-text="basicName"></label>
                <select class="wps-select" x-model="selectedBasic">
                    <option value="">-- Pilih --</option>
                    <template x-for="opt in basicOptions" :key="opt">
                        <option :value="opt" x-text="opt"></option>
                    </template>
                </select>
            </div>
            <div class="wps-mb-4" x-show="advName && advOptions.length" x-cloak>
                <label class="wps-label" x-text="advName"></label>
                <select class="wps-select" x-model="selectedAdv">
                    <option value="">-- Pilih --</option>
                    <template x-for="opt in advOptions" :key="opt.label">
                        <option :value="opt.label" x-text="opt.label"></option>
                    </template>
                </select>
            </div>
            <div class="wps-flex wps-justify-between wps-items-center">
                <button type="button" class="wps-btn wps-btn-secondary wps-btn-sm" @click="showModal = false">Batal</button>
                <button type="button" class="wps-btn wps-btn-primary wps-btn-sm" @click="confirmAdd()" :disabled="loading || !canSubmit()" :style="loading ? 'opacity:.7; pointer-events:none;' : ''">
                    <template x-if="loading">
                        <span><?php echo wps_icon(['name' => 'spinner', 'size' => 16, 'class' => 'wps-mr-2']); ?></span>
                    </template>
                    <span>Tambah</span>
                </button>
            </div>
        </div>
    </div>
</div>
