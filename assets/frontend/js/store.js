(() => {
  const wpStoreFactory = (perPage) => ({
    loading: false,
    products: [],
    cart: [],
    wishlist: [],
    perPage: perPage || 12,
    page: 1,
    customer: {
      name: "",
      email: "",
      phone: "",
    },
    submitting: false,
    message: "",
    async init() {
      this.loading = true;
      try {
        await this.fetchCart();
        await this.fetchWishlist();
        await this.fetchProducts();
      } catch (e) {
      } finally {
        this.loading = false;
      }
      document.addEventListener("wp-store:cart-updated", (e) => {
        if (e.detail && e.detail.items) {
          this.cart = e.detail.items;
        } else {
          this.fetchCart();
        }
      });
      document.addEventListener("wp-store:wishlist-updated", (e) => {
        if (e.detail && e.detail.items) {
          this.wishlist = e.detail.items;
        } else {
          this.fetchWishlist();
        }
      });
    },
    async fetchCart() {
      try {
        const response = await fetch(wpStoreSettings.restUrl + "cart", {
          credentials: "same-origin",
          headers: {
            "X-WP-Nonce": wpStoreSettings.nonce,
          },
        });
        if (!response.ok) {
          throw new Error("Gagal mengambil keranjang");
        }
        const data = await response.json();
        this.cart = data.items || [];
      } catch (e) {
        this.cart = [];
      }
    },
    async fetchWishlist() {
      try {
        const response = await fetch(wpStoreSettings.restUrl + "wishlist", {
          credentials: "same-origin",
          headers: {
            "X-WP-Nonce": wpStoreSettings.nonce,
          },
        });
        if (!response.ok) {
          throw new Error("Gagal mengambil wishlist");
        }
        const data = await response.json();
        this.wishlist = data.items || [];
      } catch (e) {
        this.wishlist = [];
      }
    },
    async fetchProducts() {
      this.loading = true;
      try {
        const url = new URL(wpStoreSettings.restUrl + "products");
        url.searchParams.set("per_page", this.perPage);
        url.searchParams.set("page", this.page);
        const response = await fetch(url.toString());
        if (!response.ok) {
          throw new Error("Gagal mengambil produk");
        }
        const data = await response.json();
        this.products = data.items || [];
        if (!this.products || this.products.length === 0) {
          await this.fetchProductsFallback();
        }
      } catch (e) {
        await this.fetchProductsFallback();
      } finally {
        this.loading = false;
      }
    },
    async fetchProductsFallback() {
      try {
        const base = String(wpStoreSettings.restUrl).replace(
          /wp-store\/v1\/?$/,
          "",
        );
        const url = new URL(base + "wp/v2/store_product");
        url.searchParams.set("per_page", this.perPage);
        url.searchParams.set("page", this.page);
        url.searchParams.set("_embed", "1");
        const res = await fetch(url.toString(), {
          credentials: "same-origin",
          headers: {
            "X-WP-Nonce": wpStoreSettings.nonce,
          },
        });
        if (!res.ok) {
          return;
        }
        const items = await res.json();
        this.products = (items || []).map((p) => {
          const embedded = p._embedded || {};
          const media = Array.isArray(embedded["wp:featuredmedia"])
            ? embedded["wp:featuredmedia"][0]
            : null;
          const img = media && media.source_url ? media.source_url : null;
          const excerptText =
            p.excerpt && p.excerpt.rendered
              ? p.excerpt.rendered.replace(/<[^>]+>/g, "").trim()
              : "";
          return {
            id: p.id,
            title: p.title && p.title.rendered ? p.title.rendered : "",
            slug: p.slug || "",
            excerpt: excerptText,
            price: null,
            stock: null,
            image: img,
            link: p.link || "",
          };
        });
      } catch (err) {}
    },
    async addToCart(product) {
      const existing = this.cart.find((item) => item.id === product.id);
      const nextQty = existing ? existing.qty + 1 : 1;
      await this.updateCartItem(product.id, nextQty);
    },
    async increment(item) {
      await this.updateCartItem(item.id, item.qty + 1);
    },
    async decrement(item) {
      const nextQty = item.qty > 1 ? item.qty - 1 : 0;
      await this.updateCartItem(item.id, nextQty);
    },
    async remove(item) {
      await this.updateCartItem(item.id, 0);
    },
    get total() {
      return this.cart.reduce((sum, item) => sum + item.price * item.qty, 0);
    },
    async updateCartItem(id, qty) {
      try {
        const response = await fetch(wpStoreSettings.restUrl + "cart", {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": wpStoreSettings.nonce,
          },
          body: JSON.stringify({ id, qty }),
        });
        const data = await response.json();
        if (!response.ok) {
          this.message = data.message || "Gagal update keranjang.";
          return;
        }
        this.cart = data.items || [];
      } catch (e) {
        this.message = "Terjadi kesalahan jaringan.";
      }
    },
    formatPrice(value) {
      if (typeof value !== "number") {
        value = parseFloat(value || 0);
      }
      return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
      }).format(value);
    },
    async checkout() {
      if (!this.customer.name || this.cart.length === 0) {
        this.message = "Isi nama dan keranjang terlebih dahulu.";
        return;
      }
      this.submitting = true;
      this.message = "";
      try {
        const response = await fetch(wpStoreSettings.restUrl + "checkout", {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": wpStoreSettings.nonce,
          },
          body: JSON.stringify({
            name: this.customer.name,
            email: this.customer.email,
            phone: this.customer.phone,
            items: this.cart.map((item) => ({
              id: item.id,
              qty: item.qty,
            })),
          }),
        });
        const data = await response.json();
        if (!response.ok) {
          this.message = data.message || "Gagal mengirim pesanan.";
          return;
        }
        this.message = data.message || "Pesanan berhasil dibuat.";
        this.cart = [];
      } catch (e) {
        this.message = "Terjadi kesalahan jaringan.";
      } finally {
        this.submitting = false;
      }
    },
  });

  window.wpStore = wpStoreFactory;
  window.wpStoreReady = true;
  document.dispatchEvent(new Event("wp-store:ready"));

  if (window.Alpine && typeof window.Alpine.data === "function") {
    window.Alpine.data("wpStore", wpStoreFactory);
    if (typeof window.Alpine.initTree === "function") {
      window.Alpine.initTree(document.body);
    } else if (typeof window.Alpine.start === "function") {
      window.Alpine.start();
    }
  } else {
    document.addEventListener("alpine:init", () => {
      Alpine.data("wpStore", wpStoreFactory);
    });
  }
  const wpsLightbox = (() => {
    let root = null;
    let panel = null;
    let img = null;
    let btnClose = null;
    let btnPrev = null;
    let btnNext = null;
    let activeFlickity = null;
    let activeOnChange = null;
    const isOpen = () => root && root.classList.contains("is-open");
    const setImage = (src, alt) => {
      if (!img) return;
      img.src = src || "";
      img.alt = alt || "";
    };
    const getCellImg = (cellElement) => {
      if (!cellElement || !cellElement.querySelector) return null;
      return cellElement.querySelector("img");
    };
    const updateNavButtons = () => {
      const canNav =
        activeFlickity &&
        Array.isArray(activeFlickity.cells) &&
        activeFlickity.cells.length > 1;
      if (!btnPrev || !btnNext) return;
      btnPrev.style.display = canNav ? "inline-flex" : "none";
      btnNext.style.display = canNav ? "inline-flex" : "none";
    };
    const close = () => {
      if (!root) return;
      root.classList.remove("is-open");
      document.body.classList.remove("wps-lightbox-open");
      if (activeFlickity && activeOnChange) {
        try {
          activeFlickity.off("change", activeOnChange);
        } catch (e) {}
      }
      activeFlickity = null;
      activeOnChange = null;
    };
    const ensure = () => {
      if (root) return;
      root = document.createElement("div");
      root.className = "wps-lightbox";
      root.setAttribute("role", "dialog");
      root.setAttribute("aria-modal", "true");
      panel = document.createElement("div");
      panel.className = "wps-lightbox__panel";
      img = document.createElement("img");
      img.className = "wps-lightbox__img";
      btnClose = document.createElement("button");
      btnClose.type = "button";
      btnClose.className = "wps-lightbox__close";
      btnClose.setAttribute("aria-label", "Tutup");
      btnClose.innerHTML = "×";
      btnPrev = document.createElement("button");
      btnPrev.type = "button";
      btnPrev.className = "wps-lightbox__nav prev";
      btnPrev.setAttribute("aria-label", "Sebelumnya");
      btnPrev.innerHTML = "‹";
      btnNext = document.createElement("button");
      btnNext.type = "button";
      btnNext.className = "wps-lightbox__nav next";
      btnNext.setAttribute("aria-label", "Berikutnya");
      btnNext.innerHTML = "›";
      panel.appendChild(img);
      panel.appendChild(btnClose);
      panel.appendChild(btnPrev);
      panel.appendChild(btnNext);
      root.appendChild(panel);
      document.body.appendChild(root);

      root.addEventListener("click", (e) => {
        if (e.target === root) close();
      });
      img.addEventListener("click", close);
      btnClose.addEventListener("click", close);
      btnPrev.addEventListener("click", () => {
        if (activeFlickity && isOpen()) {
          try {
            activeFlickity.previous(true);
          } catch (e) {}
        }
      });
      btnNext.addEventListener("click", () => {
        if (activeFlickity && isOpen()) {
          try {
            activeFlickity.next(true);
          } catch (e) {}
        }
      });
      document.addEventListener("keydown", (e) => {
        if (!isOpen()) return;
        if (e.key === "Escape") {
          e.preventDefault();
          close();
          return;
        }
        if (!activeFlickity) return;
        if (e.key === "ArrowLeft") {
          e.preventDefault();
          try {
            activeFlickity.previous(true);
          } catch (err) {}
        } else if (e.key === "ArrowRight") {
          e.preventDefault();
          try {
            activeFlickity.next(true);
          } catch (err) {}
        }
      });
    };
    const openFromImg = (el) => {
      if (!el) return;
      ensure();
      activeFlickity = null;
      activeOnChange = null;
      setImage(el.dataset.wpsLightboxSrc || el.currentSrc || el.src, el.alt);
      updateNavButtons();
      root.classList.add("is-open");
      document.body.classList.add("wps-lightbox-open");
    };
    const openFromCell = (flkty, cellElement, cellIndex) => {
      ensure();
      activeFlickity = flkty || null;
      if (activeFlickity) {
        try {
          activeFlickity.select(cellIndex, false, true);
        } catch (e) {}
      }
      const targetImg = getCellImg(cellElement);
      setImage(
        targetImg ? targetImg.currentSrc || targetImg.src : "",
        targetImg ? targetImg.alt : "",
      );
      if (activeFlickity) {
        activeOnChange = (index) => {
          if (!isOpen()) return;
          const cell = activeFlickity.cells && activeFlickity.cells[index];
          const elem = cell && cell.element ? cell.element : null;
          const i = getCellImg(elem);
          setImage(i ? i.currentSrc || i.src : "", i ? i.alt : "");
        };
        try {
          activeFlickity.on("change", activeOnChange);
        } catch (e) {}
      }
      updateNavButtons();
      root.classList.add("is-open");
      document.body.classList.add("wps-lightbox-open");
    };
    return {
      openFromCell,
      openFromImg,
      close,
    };
  })();
  const initCarousels = () => {
    if (!window.Flickity) return;
    const nodes = document.querySelectorAll("[data-wps-carousel]");
    nodes.forEach((node) => {
      const track = node.querySelector(".main-carousel");
      if (!track || track.__flickity) return;
      const d = node.dataset;
      const groupCellsVal = parseInt(d.groupCells || "0", 10);
      const lazyVal = parseInt(d.lazyLoad || "0", 10);
      const autoPlayVal = parseInt(d.autoplay || "0", 10);
      const opts = {
        cellAlign: d.cellAlign || "center",
        contain: d.contain === "false" ? false : true,
        wrapAround: d.wrapAround === "true",
        pageDots: d.pageDots === "false" ? false : true,
        prevNextButtons: d.prevNextButtons === "false" ? false : true,
        groupCells: groupCellsVal > 1 ? groupCellsVal : false,
        lazyLoad: lazyVal > 0 ? lazyVal : false,
        autoPlay: autoPlayVal > 0 ? autoPlayVal : false,
        pauseAutoPlayOnHover: d.pauseOnHover === "false" ? false : true,
        draggable: d.draggable === "false" ? false : true,
      };
      if (d.asNavFor) {
        let target = null;
        try {
          target =
            node.querySelector(d.asNavFor) ||
            document.querySelector(d.asNavFor);
        } catch (e) {}
        opts.asNavFor = target || d.asNavFor;
      }
      track.__flickity = new window.Flickity(track, opts);
      if (
        (d.wpsLightbox === "1" || d.wpsLightbox === "true") &&
        !track.__wpsLightbox
      ) {
        track.__wpsLightbox = true;
        const flkty = track.__flickity;
        flkty.on("staticClick", (event, pointer, cellElement, cellIndex) => {
          if (typeof cellIndex !== "number" || !cellElement) return;
          wpsLightbox.openFromCell(flkty, cellElement, cellIndex);
        });
      }
      if (d.asNavFor) {
        let target = null;
        try {
          target =
            node.querySelector(d.asNavFor) ||
            document.querySelector(d.asNavFor);
        } catch (e) {}
        const mainEl = target;
        const navFlkty = track.__flickity;
        const mainFlkty =
          mainEl && mainEl.__flickity ? mainEl.__flickity : null;
        if (mainFlkty) {
          const updateNavSelected = (index) => {
            const cells = track.querySelectorAll(".carousel-cell");
            for (let i = 0; i < cells.length; i++) {
              const c = cells[i];
              if (i === index) {
                c.classList.add("is-nav-selected");
              } else {
                c.classList.remove("is-nav-selected");
              }
            }
          };
          navFlkty.on(
            "staticClick",
            (event, pointer, cellElement, cellIndex) => {
              if (typeof cellIndex === "number") {
                mainFlkty.select(cellIndex);
              }
            },
          );
          mainFlkty.on("change", (index) => {
            navFlkty.select(index);
            updateNavSelected(index);
          });
          updateNavSelected(mainFlkty.selectedIndex || 0);
        }
      }
    });
  };
  if (document.readyState !== "loading") {
    initCarousels();
  } else {
    document.addEventListener("DOMContentLoaded", initCarousels);
  }
  document.addEventListener("wp-store:ready", initCarousels);
  const initLightboxImages = () => {
    const imgs = document.querySelectorAll("img[data-wps-lightbox-src]");
    imgs.forEach((el) => {
      if (el.__wpsLightbox) return;
      el.__wpsLightbox = true;
      el.addEventListener("click", () => wpsLightbox.openFromImg(el));
    });
  };
  if (document.readyState !== "loading") {
    initLightboxImages();
  } else {
    document.addEventListener("DOMContentLoaded", initLightboxImages);
  }
  document.addEventListener("wp-store:ready", initLightboxImages);
  const setupBeaverBuilderIntegration = () => {
    const content = document.querySelector(".fl-builder-content");
    if (!content) return;
    const trigger = () => setTimeout(initCarousels, 20);
    if (window.jQuery && typeof window.jQuery.fn.on === "function") {
      window.jQuery(content).on("fl-builder.layout-rendered", trigger);
      window.jQuery(content).on("fl-builder.preview-rendered", trigger);
    }
    const mo = new MutationObserver((mutations) => {
      for (let i = 0; i < mutations.length; i++) {
        const m = mutations[i];
        if (m.addedNodes && m.addedNodes.length) {
          for (let j = 0; j < m.addedNodes.length; j++) {
            const n = m.addedNodes[j];
            if (n.nodeType === 1) {
              if (
                (n.matches &&
                  n.matches("[data-wps-carousel], .main-carousel")) ||
                (n.querySelector && n.querySelector("[data-wps-carousel]"))
              ) {
                trigger();
                return;
              }
            }
          }
        }
      }
    });
    mo.observe(content, { childList: true, subtree: true });
  };
  if (
    document.querySelector(".fl-builder-content") ||
    (document.body && document.body.classList.contains("fl-builder-edit"))
  ) {
    setupBeaverBuilderIntegration();
  }
})();
