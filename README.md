<div align="center">

# PW Product Configurator

**Turn any WooCommerce product into a fully configurable, live-priced product.**

Dimensions · Materials · Printing · Add-ons — all managed from wp-admin, priced in real time, and added to cart at the exact calculated price.

[![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-blue?logo=wordpress&logoColor=white)](#requirements)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4?logo=php&logoColor=white)](#requirements)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-required-7f54b3?logo=woocommerce&logoColor=white)](#requirements)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-success)](#license)
[![Version](https://img.shields.io/badge/version-0.0.01-orange)](#changelog)
[![HPOS](https://img.shields.io/badge/WooCommerce-HPOS%20compatible-7f54b3)](#woocommerce-compatibility)

</div>

---

## Table of contents

- [Overview](#overview)
- [Key features](#key-features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [How it works](#how-it-works)
- [Displaying the configurator](#displaying-the-configurator)
- [The pricing model](#the-pricing-model)
- [Customizing the layout with CSS](#customizing-the-layout-with-css)
- [Developer reference](#developer-reference)
- [Worked example](#worked-example)
- [WooCommerce compatibility](#woocommerce-compatibility)
- [FAQ](#faq)
- [Troubleshooting](#troubleshooting)
- [Roadmap](#roadmap)
- [Changelog](#changelog)
- [License](#license)

---

## Overview

PW Product Configurator adds a live-pricing configuration form to any WooCommerce product page. Customers pick options from dropdowns and checkboxes — **Quantity, Format, Material, Printing, Varnish,** and so on — and the price updates instantly on the right. **Add to cart** then places the product in the cart at that exact calculated price, not WooCommerce's regular price.

It is purpose-built for made-to-measure products such as printed packaging (folding boxes, labels), where the price depends on **box dimensions × material × quantity tier**, plus one-off surcharges and finishing options.

Everything is controlled from three admin screens under the **PW Configurator** menu in wp-admin — no code required for day-to-day operation.

| Screen | Purpose |
|---|---|
| **Field Groups** | The dropdowns/checkboxes (Material, Printing, etc.) and their prices |
| **Dimension Presets** | The box sizes shown in the **Format** dropdown |
| **Settings** | Quantity tiers, VAT rate, global multipliers, master on/off switch |

---

## Key features

- **Live price calculation** — the offer panel recomputes on every selection, before add to cart.
- **Size-based pricing** — options priced **per m² × box surface area × quantity**, just like a real print shop.
- **Quantity-tier discounts** — digressive per-unit pricing (e.g. 500 units cost more per box than 100,000).
- **Flexible field types** — dropdowns and checkboxes, each with its own pricing rule.
- **Per-category *and* per-product targeting** — assign options by product category, by individual product, or globally.
- **Per-product overrides** — add or exclude specific field groups on a single product.
- **Master kill switch** — disable all pricing site-wide in one click (staging/preview mode).
- **Elementor widget + shortcode** — drop the configurator anywhere.
- **WooCommerce-native** — uses `wc_price()` formatting and `WC_Order_Item` meta, fully HPOS-compatible.
- **Secure by default** — nonce-verified, capability-checked, escaped output throughout.

---

## Requirements

| Requirement | Version |
|---|---|
| WordPress | 6.4 or higher |
| PHP | 8.1 or higher |
| **WooCommerce** | **Required** (latest) |
| Elementor / Elementor Pro | Optional, tested up to 4.0 (recommended for the widget) |

> The plugin activates only when WooCommerce is present. If WooCommerce is missing, you'll see an admin notice and nothing else loads.

---

## Installation

1. Download the plugin `.zip`.
2. In wp-admin, go to **Plugins → Add New → Upload Plugin**, choose the zip, and click **Install Now**.
3. Click **Activate**.
4. A new **PW Configurator** item appears in the left-hand wp-admin sidebar. If it doesn't, confirm WooCommerce is active first.

---

## Quick start

The fastest path to a working configurator:

1. **Settings** → confirm the **master pricing switch** is on, set your **quantity tiers** and **default VAT rate**.
2. **Dimension Presets → Add New** → enter a label (`40 x 40 x 100 mm`) and the real L/W/H in mm → assign to a category → **Publish**. Repeat for each size.
3. **Field Groups → Add New** → name it → add dropdowns/checkboxes with pricing → assign to a category → **Publish**.
4. Drop the **PW Product Configurator** widget onto your single-product Elementor template (or use `[pwc_configurator]`).

Open any matching product page — the form, live pricing, and add-to-cart are live.

---

## How it works

The configurator is built from three building blocks. Understanding how they relate makes everything else obvious.

### 1. Dimension Presets (the *Format* dropdown)

Each preset is one entry in the customer-visible Format dropdown.

- **Title** — exactly what the customer sees, e.g. `40 x 40 x 100 mm`.
- **Length / Width / Height (mm)** — the *internal* dimensions. Invisible to customers, but they drive every per-m² price, so they must be accurate.
- **Assignment** — which categories/products this size is offered for.

A product shows *every* preset that matches its category or is individually assigned to it. Give "Folding Boxes" eight sizes and every folding-box product automatically offers all eight.

### 2. Field Groups (the dropdowns & checkboxes)

A Field Group is a reusable bundle of fields — typically one per product category (e.g. *Folding Box Options*), or a smaller cross-category bundle (e.g. *Finishing Options* with Varnish/Laminate).

- **Title** — internal only; customers never see it.
- **Include in price calculation** — leave on. Turn off to show the fields (so customers can browse them) without affecting price yet — useful while finalizing prices for a new category. Flip it back on later; no need to touch individual field prices.
- **Price multiplier** — scales every price in the group. `0.9` = 10% discount on the group, `1.15` = 15% surcharge, `0` = same as disabling the group.
- **Fields** — built with **+ Add Field**:

  | Setting | Notes |
  |---|---|
  | **Field label** | What the customer sees, e.g. `Material` |
  | **Field key** | Internal slug, lowercase/`_` only, unique within the group (e.g. `material`). Auto-cleaned as you type. Never shown to customers. |
  | **Type** | **Dropdown** or **Checkbox** |

  **Dropdown options** (added with **+ Add option**) each carry one pricing rule (see [The pricing model](#the-pricing-model)). **Checkbox** options are a single one-off flat charge, added only when ticked (ideal for *“Professional artwork check — €20”*).

### 3. Settings

- **Master pricing switch** — the big kill switch. Off = *every* price returns €0 across the whole site, instantly, without editing any Field Group. While off, the offer panel shows a *Pricing preview Mode* notice so your team never mistakes it for a real price. Use it during build-out; switch on for go-live.
- **Global price multiplier** — scales everything, site-wide, on top of each group's own calculation. Leave at `1` unless you're running a temporary store-wide promo/surcharge.
- **Quantity tiers** — the exact quantities selectable in the Quantity dropdown and each tier's discount multiplier (see below).
- **Default VAT rate** — as a decimal (`0.20` = 20%).
- **Delete data on uninstall** — off by default so a routine deactivate/reactivate never loses your setup.

### The assignment model (categories vs. products)

Both **Field Groups** and **Dimension Presets** use the same assignment logic on their **Assign to Categories / Products** box:

| You set… | Result |
|---|---|
| **Nothing** | Applies to your **entire shop** (global). |
| **One or more categories** | Applies to every product in those categories, including products added later. |
| **Specific products** | Applies only to those products, regardless of category. |
| **Both** | A product matches if it hits **either** rule (category **OR** product). |

**Per-product fine control:** on any WooCommerce product, the **PW Configurator — Overrides for this Product** box lets you:

- **Add** a field group to a single product even if its category doesn't match.
- **Exclude** a field group that would otherwise apply (e.g. assigned to the whole category, but not wanted on this one product).

---

## Displaying the configurator

Two equivalent ways to place the form on a product page.

### Elementor widget (recommended)

Using Elementor Pro:

1. Edit your **Single Product** template under **Elementor → Theme Builder** (or edit a product page directly).
2. Drag in the **PW Product Configurator** widget (search "PW").
3. Leave the **Product ID** field blank — the form auto-detects the current product.
4. Style the surrounding section as you like; the widget's own layout (form left, sticky offer box right) sits inside it.

### Shortcode

```text
[pwc_configurator]          // auto-detects the current product
[pwc_configurator id="123"] // force a specific product ID
```

---

## The pricing model

Every option uses one of three pricing rules. The engine combines them into the final offer.

| Rule | What it does | Typical use |
|---|---|---|
| **No price impact** | Informational only; doesn't change the price | *Country of delivery* (affects VAT only) |
| **€ per m²** *(× box area × qty)* | Option price × box surface area × quantity | Material, printing, varnish, laminate |
| **Flat, once per order** | A fixed amount added once, regardless of size or quantity | Express-production surcharge, artwork check |

**The calculation, end to end:**

```
per-box unit price   = Σ ( per-m² option prices × box surface area m² )
base price           = unit price × quantity × tier multiplier × versions × global multiplier
additional options   = Σ flat one-off charges × global multiplier
total (net)          = base price + additional options
total (incl. VAT)    = total net × (1 + VAT rate)
```

- **Box surface area** is computed by `box_area_m2()` from the preset's L/W/H. Override it per box style via the [`pwc_box_area_m2`](#filters) filter (e.g. with an exact die-line).
- **Tier multiplier** is the digressive-discount curve: a multiplier of `0.68` at a tier means boxes at that quantity are priced at 68% of the per-box list price — bulk orders get cheaper per unit.
- **Versions** multiplies the base price for multi-version print runs.

---

## Customizing the layout with CSS

By default, fields render in one fixed column: **Quantity → Versions → Format**, then each Field Group's fields in admin order.

For a custom visual arrangement (two columns, a full-width row, reordering) you don't touch plugin code or rebuild anything — every field wrapper exposes a `data-pwc-field="…"` attribute you can target from your theme's Custom CSS (Elementor **Advanced** tab, or your child theme stylesheet).

### Available `data-pwc-field` keys

| Built-in field | Key |
|---|---|
| Quantity | `quantity` |
| Versions | `versions` |
| Format | `dimension_id` |
| Any custom field | the **Field key** you set (e.g. `material`, `printing_outside`) |

| Offer-panel row | Key |
|---|---|
| Offer box | `offer_box` |
| Base price | `offer_base_price` |
| Additional options | `offer_additional_options` |
| Total net | `offer_total_net` |
| VAT | `offer_vat` |
| Total incl. VAT | `offer_total_incl_vat` |

### Example: two columns, Material full-width, Printing + Varnish paired

```css
.pwc-col-form {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
.pwc-field[data-pwc-field="quantity"]         { grid-column: 1; }
.pwc-field[data-pwc-field="dimension_id"]     { grid-column: 2; }
.pwc-field[data-pwc-field="material"]         { grid-column: 1 / -1; } /* full width */
.pwc-field[data-pwc-field="printing_outside"] { grid-column: 1; }
.pwc-field[data-pwc-field="varnish"]          { grid-column: 2; }
```

### Example: simple reorder (single column)

```css
.pwc-col-form { display: flex; flex-direction: column; }
.pwc-field[data-pwc-field="artwork_check"] { order: -1; } /* move to the top */
.pwc-field[data-pwc-field="material"]      { order: 1; }
```

### Things to know before relying on this

- **Visual order only.** Screen-reader and keyboard tab order still follow the source HTML, not the visual position.
- **New fields default to source order** until you add a matching `[data-pwc-field="…"]` rule. Nothing breaks; they just sit in the default spot until you add that one line.
- **`display:none` hides a field but doesn't remove it from pricing.** Hiding a *required* field (Quantity/Format) will block add-to-cart since nothing gets selected. Only hide genuinely optional fields.

---

## Developer reference

### Custom Post Types

| Post type | Admin name |
|---|---|
| `pwc_field_group` | Field Groups |
| `pwc_dimension` | Dimension Presets |

### Shortcode

```php
[pwc_configurator]          // current product
[pwc_configurator id="123"] // explicit product
```

### Filters

| Filter | Args | Purpose |
|---|---|---|
| `pwc_box_area_m2` | `$area_m2`, `$length_mm`, `$width_mm`, `$height_mm` | Override the box surface-area formula per style (e.g. exact die-line). |
| `pwc_vat_rate` | `$rate`, `$country_code` | Override/extend the resolved VAT rate per country. |

Example — exact die-line for a specific box style:

```php
add_filter( 'pwc_box_area_m2', function ( $area, $l, $w, $h ) {
    return my_exact_die_line_area( $l, $w, $h ); // return m² as a float
}, 10, 4 );
```

### Options (wp_options keys)

| Key | Type | Default |
|---|---|---|
| `pwc_master_pricing_enabled` | `'1'`/`'0'` | `'1'` |
| `pwc_global_multiplier` | float | `1` |
| `pwc_quantity_tiers` | array of `['qty','multiplier']` | 9 tiers (500 → 100,000) |
| `pwc_default_vat_rate` | float (decimal) | `0.20` |
| `pwc_country_vat_map` | array `country => rate` | `[]` |
| `pwc_delete_data_on_uninstall` | `'1'`/`'0'` | `'0'` |

### Field/option data shape

Field Groups store their fields as JSON in post meta `_pwc_fields`. Each option carries a `pricing_mode` of `none`, `per_sqm`, or `flat_order`, plus a numeric `price`. See `PWC_Pricing::calculate()` in [includes/class-pwc-pricing.php](includes/class-pwc-pricing.php) for the full breakdown logic.

---

## Worked example

Setting up **Folding Boxes** end to end:

1. **Dimension Presets** — create `40 x 40 x 100 mm`, `60 x 60 x 150 mm`, … tick the **Folding Boxes** category on each.
2. **Field Group** *"Folding Box Options"*, tick **Folding Boxes**:
   - Dropdown **Material** (`material`): `275g GC1 chromoboard` (€/m², 4.20), `350g GZ board` (€/m², 6.10)
   - Dropdown **Printing – Outside** (`printing_outside`): `1 colour, black` (€/m², 0.35), `Full colour` (€/m², 0.90)
   - Dropdown **Varnish** (`varnish`): `No varnish` (no impact), `Matt varnish` (€/m², 0.15)
   - Dropdown **Production time** (`production_time`): `Economy` (flat, 0), `Express` (flat, 45)
   - Checkbox **Other** (`artwork_check`): `Professional artwork check` — flat 20
3. **Publish** everything.
4. Open any Folding Boxes product — the widget now shows Quantity, Versions, Format, Material, Printing – Outside, Varnish, Production time, and the artwork-check checkbox, all pulled in automatically. No per-product setup unless you want an exception (see [How it works](#the-assignment-model-categories-vs-products)).

---

## WooCommerce compatibility

- **HPOS (High-Performance Order Storage)** — declared compatible. All order data is written through the official `WC_Order_Item::add_meta_data()` method, so it works under both legacy post-based storage and the new HPOS tables.
- **Currency** — every displayed price uses WooCommerce's own `wc_price()` formatting, so it follows your **WooCommerce → Settings → General** configuration (currency, symbol position, decimal/thousand separators). Option prices you enter are assumed to be in your store's base currency, exactly like core product prices — no currency conversion is performed.
- **Uninstall** — deactivating the plugin never deletes anything. A full **Delete** only removes data when you've first ticked **Settings → Delete all data on uninstall** (off by default). Even then, only this plugin's own Field Groups, Dimension Presets, and settings are removed — never WooCommerce products, orders, or prices already stored on placed orders.
- **Security** — all admin submissions are nonce-verified and capability-checked (`manage_woocommerce` / `edit_post`), and all output is escaped (`esc_html`, `esc_attr`) per WordPress/WooCommerce coding standards.

> **Translation:** field labels and admin text are not yet wrapped in `__()`/`_e()`, so the plugin is not translation-ready out of the box. Not a functional issue for single-language sites, but worth knowing if you need other languages later.

---

## FAQ

**Does it replace the normal WooCommerce price?**
Yes — on any product the configurator applies to, the calculated price overrides the regular product price in the cart.

**Can I show options without affecting price?**
Yes. Turn off *Include this group's fields in price calculation* on the Field Group, or set individual options to *No price impact*. Selections are still recorded; they just contribute €0.

**Do I have to configure each product?**
No. Assign Field Groups and Dimension Presets to a category once and every product in that category inherits them automatically. Per-product setup is only for exceptions.

**Does it work without Elementor?**
Yes — use the `[pwc_configurator]` shortcode. Elementor/Elementor Pro is only needed for the drag-and-drop widget.

---

## Troubleshooting

| Symptom | Fix |
|---|---|
| Form shows but prices don't update | Confirm **Settings → master pricing switch** is **on**, and the Field Group's *Include in price calculation* is ticked. |
| A dropdown option shows but doesn't change price | Its pricing mode is probably *No price impact*. Edit the Field Group and change it. |
| A category's options aren't showing on a product | Confirm the product is actually in that category, and that the Field Group isn't on the product's *Exclude* list. |
| *Add to cart* fails | Quantity and Format are required — make sure both are selected. |
| No **PW Configurator** menu after activating | WooCommerce isn't active. Activate WooCommerce first. |

---

## Roadmap

The core **configure → live price → add to cart** flow is complete. The following reference-site actions are **not yet built** and can be layered onto the same pricing engine on request:

- *Want an individual sample?*
- *Online Designer* toggle
- *Offer by email*
- *Send contours by email*
- *Print offer*

If you need these, ask your developer — they extend the existing engine rather than replace it.

---

## Changelog

### 0.0.01
- Initial release.
- Core configurator: Field Groups, Dimension Presets, quantity tiers, live per-m²/flat pricing, VAT, Elementor widget + shortcode.
- WooCommerce HPOS compatibility, nonce/capability hardening, escaped output.
- Master pricing switch, global/group multipliers, per-product add/exclude overrides.

---

## License

Licensed under the **GNU General Public License v2.0 or later** — [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).

## Credits

- **Author:** Devsroom / WPMHS
- **Homepage:** [https://wpmhs.com](https://wpmhs.com)
- **Repository:** [PackagingWarehouse-Product-Addons-Configurator](https://github.com/mehedishubho/PackagingWarehouse-Product-Addons-Configurator)
