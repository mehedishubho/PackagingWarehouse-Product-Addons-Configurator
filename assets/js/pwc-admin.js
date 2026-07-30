(function ($) {
	'use strict';

	const container = document.getElementById('pwc-fields-repeater');
	if (!container) return;

	let fields = [];
	try {
		fields = JSON.parse(container.dataset.initial || '[]');
	} catch (e) { fields = []; }

	function uid() { return 'f' + Math.random().toString(36).slice(2, 9); }

	function render() {
		container.innerHTML = '';

		fields.forEach((field, fi) => {
			const wrap = document.createElement('div');
			wrap.className = 'pwc-admin-field';

			wrap.innerHTML = `
				<div class="pwc-admin-field-head">
					<input type="text" class="pwc-f-label" placeholder="Field label (e.g. Material)" value="${escAttr(field.label || '')}">
					<input type="text" class="pwc-f-key" placeholder="field_key" value="${escAttr(field.key || '')}">
					<select class="pwc-f-type">
						<option value="select" ${field.type === 'select' ? 'selected' : ''}>Dropdown</option>
						<option value="checkbox" ${field.type === 'checkbox' ? 'selected' : ''}>Checkbox</option>
					</select>
					<button type="button" class="button pwc-remove-field">Remove field</button>
				</div>
				<div class="pwc-admin-options"></div>
				${field.type === 'checkbox' ? '' : '<button type="button" class="button pwc-add-option">+ Add option</button>'}
			`;

			const optionsWrap = wrap.querySelector('.pwc-admin-options');
			(field.options || []).forEach((opt, oi) => {
				optionsWrap.appendChild(renderOption(fi, oi, opt, field.type));
			});

			// events
			wrap.querySelector('.pwc-f-label').addEventListener('input', e => fields[fi].label = e.target.value);
			wrap.querySelector('.pwc-f-key').addEventListener('input', e => fields[fi].key = e.target.value.replace(/[^a-z0-9_]/gi, '_').toLowerCase());
			wrap.querySelector('.pwc-f-type').addEventListener('change', e => {
				fields[fi].type = e.target.value;
				if (e.target.value === 'checkbox') {
					fields[fi].options = [fields[fi].options?.[0] || { label: '', price: 0 }];
				}
				render();
			});
			wrap.querySelector('.pwc-remove-field').addEventListener('click', () => { fields.splice(fi, 1); render(); });
			const addOptBtn = wrap.querySelector('.pwc-add-option');
			if (addOptBtn) addOptBtn.addEventListener('click', () => {
				fields[fi].options = fields[fi].options || [];
				fields[fi].options.push({ label: '', price: 0, pricing_mode: 'none' });
				render();
			});

			container.appendChild(wrap);
		});

		const addFieldBtn = document.createElement('button');
		addFieldBtn.type = 'button';
		addFieldBtn.className = 'button button-primary';
		addFieldBtn.textContent = '+ Add Field';
		addFieldBtn.addEventListener('click', () => {
			fields.push({ key: uid(), label: '', type: 'select', options: [{ label: '', price: 0, pricing_mode: 'none' }] });
			render();
		});
		container.appendChild(addFieldBtn);

		document.getElementById('pwc_fields_json').value = JSON.stringify(fields);
	}

	function renderOption(fi, oi, opt, type) {
		const row = document.createElement('div');
		row.className = 'pwc-admin-option-row';
		row.innerHTML = `
			<input type="text" class="pwc-o-label" placeholder="Option label" value="${escAttr(opt.label || '')}">
			${type === 'checkbox'
				? `<input type="number" step="0.01" class="pwc-o-price" placeholder="Flat price (EUR)" value="${escAttr(opt.price || 0)}">`
				: `<select class="pwc-o-mode">
					<option value="none" ${opt.pricing_mode === 'none' || !opt.pricing_mode ? 'selected' : ''}>No price impact</option>
					<option value="per_sqm" ${opt.pricing_mode === 'per_sqm' ? 'selected' : ''}>€ per m² (x box area x qty)</option>
					<option value="flat_order" ${opt.pricing_mode === 'flat_order' ? 'selected' : ''}>Flat, once per order</option>
				   </select>
				   <input type="number" step="0.0001" class="pwc-o-price" placeholder="Price value" value="${escAttr(opt.price || 0)}">`
			}
			<button type="button" class="button-link-delete pwc-remove-option">Remove</button>
		`;
		row.querySelector('.pwc-o-label').addEventListener('input', e => fields[fi].options[oi].label = e.target.value);
		row.querySelector('.pwc-o-price').addEventListener('input', e => fields[fi].options[oi].price = parseFloat(e.target.value || 0));
		const modeSel = row.querySelector('.pwc-o-mode');
		if (modeSel) modeSel.addEventListener('change', e => fields[fi].options[oi].pricing_mode = e.target.value);
		row.querySelector('.pwc-remove-option').addEventListener('click', () => { fields[fi].options.splice(oi, 1); render(); });
		return row;
	}

	function escAttr(str) {
		return String(str).replace(/"/g, '&quot;');
	}

	// keep hidden input in sync on every render + before submit
	const origRender = render;
	render = function () { origRender(); document.getElementById('pwc_fields_json').value = JSON.stringify(fields); };

	document.addEventListener('submit', function (e) {
		if (document.getElementById('pwc_fields_json')) {
			document.getElementById('pwc_fields_json').value = JSON.stringify(fields);
		}
	}, true);

	render();

	// ------------------------------------------------------------------
	// Per-product "Image per Material" picker (product edit screen only).
	// Runs only when the #pwc-product-images box is present.
	// ------------------------------------------------------------------
	(function () {
		const box = document.getElementById('pwc-product-images');
		if (!box) return;

		const imgHidden = document.getElementById('pwc_product_images_json');
		const optHidden = document.getElementById('pwc_product_field_options_json');

		function sync() {
			const imgMap = {};
			const optMap = {};
			box.querySelectorAll('.pwc-prod-img-row').forEach(function (row) {
				const fullKey = row.getAttribute('data-key');   // field_key::label
				const field = row.getAttribute('data-field');    // field_key
				const label = row.getAttribute('data-label');
				const id = parseInt(row.querySelector('.pwc-att-id').value || '0', 10);
				const enabled = row.querySelector('.pwc-opt-enabled').checked;

				if (fullKey && id) imgMap[fullKey] = id;
				// Always register the field key (even with no labels) so an
				// all-unticked field is stored as [] => hidden on the frontend.
				if (field) {
					optMap[field] = optMap[field] || [];
					if (enabled && label) optMap[field].push(label);
					row.classList.toggle('pwc-prod-img-row-off', !enabled);
				}
			});
			if (imgHidden) imgHidden.value = JSON.stringify(imgMap);
			if (optHidden) optHidden.value = JSON.stringify(optMap);
		}

		let frame = null;
		let activeRow = null;

		function setImage(row, id, thumbUrl) {
			row.querySelector('.pwc-att-id').value = id;
			const img = row.querySelector('.pwc-prod-img-thumb img');
			const none = row.querySelector('.pwc-no-img');
			if (img) { img.src = thumbUrl; img.style.display = 'block'; }
			if (none) none.style.display = 'none';
			const removeBtn = row.querySelector('.pwc-remove-img');
			if (removeBtn) removeBtn.style.display = '';
			sync();
		}

		function clearImage(row) {
			row.querySelector('.pwc-att-id').value = '';
			const img = row.querySelector('.pwc-prod-img-thumb img');
			const none = row.querySelector('.pwc-no-img');
			if (img) { img.removeAttribute('src'); img.style.display = 'none'; }
			if (none) none.style.display = '';
			const removeBtn = row.querySelector('.pwc-remove-img');
			if (removeBtn) removeBtn.style.display = 'none';
			sync();
		}

		box.addEventListener('click', function (e) {
			const target = e.target;
			if (target.classList.contains('pwc-choose-img')) {
				activeRow = target.closest('.pwc-prod-img-row');
				if (!activeRow) return;
				if (!frame) {
					frame = wp.media({
						title: 'Select material image',
						multiple: false,
						library: { type: 'image' },
						button: { text: 'Use this image' }
					});
					frame.on('select', function () {
						const att = frame.state().get('selection').first().toJSON();
						if (activeRow) {
							setImage(activeRow, att.id, (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url);
						}
					});
				}
				frame.open();
			} else if (target.classList.contains('pwc-remove-img')) {
				const row = target.closest('.pwc-prod-img-row');
				if (row) clearImage(row);
			}
		});

		// keep the enabled-options map in sync when a Show checkbox toggles
		box.addEventListener('change', function (e) {
			if (e.target && e.target.classList.contains('pwc-opt-enabled')) sync();
		});

		// keep hidden inputs in sync before save
		document.addEventListener('submit', function () { sync(); }, true);
		sync();
	})();
})(jQuery);
