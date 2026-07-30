(function () {
	'use strict';

	document.querySelectorAll('.pwc-configurator').forEach(initForm);

	function initForm(form) {
		const productId = form.dataset.productId;
		const statusEl = form.querySelector('.pwc-status');
		let debounceTimer = null;

		form.addEventListener('input', () => scheduleCalc());
		form.addEventListener('change', () => scheduleCalc());

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
	}
})();
