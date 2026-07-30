(function () {
	'use strict';

	document.querySelectorAll('.pwc-configurator').forEach(initForm);

	function initForm(form) {
		const productId = form.dataset.productId;
		const statusEl = form.querySelector('.pwc-status');
		let debounceTimer = null;

		// field_key -> option_index -> { src, large, srcset, alt }, for gallery swap.
		const imageData = (function () { try { return JSON.parse(form.dataset.pwcImages || '{}'); } catch (e) { return {}; } })();

		form.addEventListener('input', () => scheduleCalc());
		form.addEventListener('change', () => scheduleCalc());

		// Swap the product gallery image when a material/option dropdown changes.
		form.addEventListener('change', function (e) {
			const sel = e.target;
			if (!sel || sel.tagName !== 'SELECT') return;
			const key = sel.name;
			const idx = sel.value;
			if (idx === '') return;
			if (imageData[key] && imageData[key][idx]) {
				swapGalleryImage(imageData[key][idx]);
			}
		});

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			addToCart();
		});

		function scheduleCalc() {
			clearTimeout(debounceTimer);
			debounceTimer = setTimeout(calculate, 300);
		}

		function collectSelections() {
			const selections = {};
			new FormData(form).forEach((value, key) => { selections[key] = value; });
			return selections;
		}

		function requiredFieldsFilled(selections) {
			return !!selections.quantity && !!selections.dimension_id;
		}

		function calculate() {
			const selections = collectSelections();
			if (!requiredFieldsFilled(selections)) return;

			const body = new URLSearchParams();
			body.append('action', 'pwc_calculate_price');
			body.append('nonce', PWC.nonce);
			body.append('product_id', productId);
			Object.keys(selections).forEach(k => body.append('selections[' + k + ']', selections[k]));

			fetch(PWC.ajax_url, { method: 'POST', body })
				.then(r => r.json())
				.then(res => {
					if (!res.success) {
						setStatus(res.data && res.data.message ? res.data.message : 'Could not calculate price.', true);
						return;
					}
					setStatus('', false);
					updateOfferBox(res.data);
				})
				.catch(() => setStatus('Network error while calculating price.', true));
		}

		function updateOfferBox(data) {
			form.querySelectorAll('[data-pwc]').forEach(el => {
				const key = el.dataset.pwc;
				if (data[key + '_fmt']) {
					el.textContent = data[key + '_fmt'];
					el.classList.add('pwc-flash');
					setTimeout(() => el.classList.remove('pwc-flash'), 250);
				}
			});
			const vatLabel = form.querySelector('[data-pwc-vat-label]');
			if (vatLabel && data.vat_rate) {
				vatLabel.textContent = 'VAT ' + Math.round(data.vat_rate * 100) + '%';
			}
		}

		function addToCart() {
			const selections = collectSelections();
			if (!requiredFieldsFilled(selections)) {
				setStatus('Please complete quantity and format before adding to cart.', true);
				return;
			}

			const btn = form.querySelector('.pwc-add-to-cart');
			btn.disabled = true;
			setStatus('Adding to cart…', false);

			const body = new URLSearchParams();
			body.append('action', 'pwc_add_to_cart');
			body.append('nonce', PWC.nonce);
			body.append('product_id', productId);
			Object.keys(selections).forEach(k => body.append('selections[' + k + ']', selections[k]));

			fetch(PWC.ajax_url, { method: 'POST', body })
				.then(r => r.json())
				.then(res => {
					btn.disabled = false;
					if (!res.success) {
						setStatus(res.data && res.data.message ? res.data.message : 'Could not add to cart.', true);
						return;
					}
					setStatus('Added to cart!', false);
					document.body.dispatchEvent(new CustomEvent('wc_fragment_refresh'));
				})
				.catch(() => { btn.disabled = false; setStatus('Network error adding to cart.', true); });
		}

		function setStatus(msg, isError) {
			if (!statusEl) return;
			statusEl.textContent = msg;
			statusEl.classList.toggle('pwc-error', !!isError);
		}

		/**
		 * Swap the product gallery main image when a material/option changes.
		 * Fires a cancelable `pwc:gallery-image-swap` event first, so a
		 * custom theme or Elementor Product Image widget can take over by
		 * calling preventDefault().
		 *
		 * Target order: the configurator's own image column (the left column
		 * rendered inside the widget), then — only if the widget has no image
		 * column — the theme's native WooCommerce gallery.
		 */
		function swapGalleryImage(data) {
			const proceed = document.body.dispatchEvent(
				new CustomEvent('pwc:gallery-image-swap', { detail: data, cancelable: true, bubbles: true })
			);
			if (!proceed) return; // a custom listener took over

			// 1) Prefer the widget's own gallery image (left column).
			const internal = form.querySelector('.pwc-gallery-img');
			if (internal) {
				setMainImage(internal, data);
				return;
			}

			// 2) Fall back to the theme's WooCommerce product gallery.
			const gallery = document.querySelector('.woocommerce-product-gallery');
			if (!gallery) return;

			const main = gallery.querySelector('img.wp-post-image')
				|| gallery.querySelector('.woocommerce-product-gallery__image img')
				|| gallery.querySelector('img');
			if (!main) return;
			setMainImage(main, data);

			// Best-effort refresh of WooCommerce Easy Zoom (harmless if absent).
			const $ = window.jQuery;
			if ($) {
				try {
					const $img = $(main);
					const ez = $img.data('easyZoom') || $img.closest('.woocommerce-product-gallery').data('easyZoom');
					if (ez && typeof ez.swap === 'function') {
						ez.swap(data.src, data.large || data.src);
					}
				} catch (e) { /* zoom refresh is best-effort */ }
			}
		}

		/** Apply a swap payload {src,srcset,large,alt} to an <img> + its link. */
		function setMainImage(img, data) {
			img.setAttribute('src', data.src);
			if (data.srcset) img.setAttribute('srcset', data.srcset); else img.removeAttribute('srcset');
			img.setAttribute('data-src', data.src);
			if (data.large) img.setAttribute('data-large_image', data.large);
			if (data.alt !== undefined && data.alt !== null) img.setAttribute('alt', data.alt);
			// Update the anchor so zoom / lightbox use the new large image.
			const anchor = img.closest('a');
			if (anchor && data.large) anchor.setAttribute('href', data.large);
		}
	}
})();
