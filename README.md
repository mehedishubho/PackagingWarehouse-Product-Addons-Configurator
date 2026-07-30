# PW Product Configurator — Setup Guide

A plain-language walkthrough for setting this up with no coding. Everything
described here is done by clicking around in wp-admin.

---

## 1. What this plugin actually does

On any WooCommerce product page, it shows a form with dropdowns and
checkboxes (Quantity, Format, Material, Printing, etc.). As a customer
picks options, the price on the right updates live, and "Add to cart"
adds the product at exactly that calculated price — not the normal
WooCommerce price.

You control everything from three admin screens under the **PW
Configurator** menu in the left-hand wp-admin sidebar:

| Screen | What it's for |
|---|---|
| **Field Groups** | The dropdowns/checkboxes themselves (Material, Printing, etc.) and their prices |
| **Dimension Presets** | The list of box sizes in the "Format" dropdown |
| **Settings** | Quantity tiers, VAT rate, and a master on/off switch for pricing |

---

## 2. Install it

1. Download the zip.
2. In wp-admin go to **Plugins → Add New → Upload Plugin**, choose the
   zip, click **Install Now**, then **Activate**.
3. You'll see a new **PW Configurator** icon in the left sidebar. If you
   don't, make sure WooCommerce is active first — this plugin requires it.

---

## 3. The two ways to assign things (category vs. individual product)

Both **Field Groups** and **Dimension Presets** have an **"Assign to
Categories / Products"** box on the right when you edit one. You get two
independent ways to control where it shows up, and you can use either or
both together:

- **By category** — tick one or more product categories (e.g. "Folding
  Boxes"). It will then apply to *every* product in that category
  automatically. New products you add to that category later are
  automatically included too — nothing more to do.
- **By individual product** — type into the search box and pick specific
  products by name, regardless of category. Good for a one-off product
  that needs its own options, or for adding something to a handful of
  products that don't share a category.
- **Leave both empty** — it applies to your *entire shop*, every product.
- **If you fill in both** — a product only needs to match ONE of the two
  (category OR individual pick) to get it. You don't need to do both for
  the same product.

**Extra fine control on the product itself:** open any WooCommerce
product for editing and scroll to the **"PW Configurator — Overrides for
this Product"** box near the bottom. From here you can:
- **Add** a field group to just this one product, even if its category
  doesn't match.
- **Exclude** a field group that would normally apply here (e.g. it's
  assigned to the whole "Folding Boxes" category, but this one specific
  product shouldn't have it).

---

## 4. Setting up Dimension Presets (the "Format" dropdown)

Go to **PW Configurator → Dimension Presets → Add New**.

1. **Title** — this is exactly what the customer sees in the dropdown.
   Type it the way you want it displayed, e.g. `40 x 40 x 100 mm`.
2. **Dimension (internal size, mm)** box — enter the real Length, Width,
   and Height in millimetres. These numbers are invisible to the customer
   but are what the price calculation is based on (bigger box = more
   material = higher price), so they need to be accurate.
3. **Assign to Categories / Products** box — tick the category (or
   search for individual products) this size should be offered for.
4. Click **Publish**.

Repeat for every size you sell. A product will show every Dimension
Preset that matches its category or is individually assigned to it —
so if "Folding Boxes" has 8 sizes assigned, every folding box product
shows all 8 in its Format dropdown automatically.

---

## 5. Setting up a Field Group (the actual dropdowns/checkboxes)

Go to **PW Configurator → Field Groups → Add New**. Each Field Group is a
bundle of related fields — you might make one per category (e.g.
"Folding Box Options"), or split into smaller reusable bundles (e.g. a
"Finishing Options" group with Varnish/Laminate/Braille that you reuse
across several categories).

### Step 1 — name it
The **Title** is only for you in wp-admin, customers never see it.
Something like `Folding Box Options` is fine.

### Step 2 — Price Calculation box (top right)
- **"Include this group's fields in price calculation"** — leave this
  ticked normally. Untick it if you want these fields to appear on the
  site (so customers can browse/select them) but **not affect price
  yet** — handy while you're still working out real prices for a new
  category. You can flip this back on later with one click, no need to
  touch the individual field prices.
- **Price multiplier** — leave at `1` normally. This scales every price
  in this group up or down. Examples: `0.9` = a temporary 10% discount
  on everything in this group; `1.15` = a 15% surcharge; `0` = same as
  unticking the box above, prices from this group become zero.

### Step 3 — Assign to Categories / Products box
Same as described in section 3 above.

### Step 4 — build the Fields
Click **+ Add Field** for each dropdown or checkbox you need.

For each field you fill in:
- **Field label** — what the customer sees, e.g. `Material`
- **Field key** — an internal short name, only lowercase letters/numbers/
  underscores, must be unique within this group (e.g. `material`). It
  auto-cleans as you type. Don't worry about getting this perfect —
  it's never shown to customers.
- **Dropdown or Checkbox** — pick the field type.

**If it's a Dropdown:** click **+ Add option** for each choice
(e.g. "275 g/m² GC1 chromoboard", "350 g/m² GZ solid bleached board").
For every option you set:
- **Option label** — what's shown in the dropdown
- **Pricing** — pick one:
  - *No price impact* — purely informational (e.g. "Country of
    delivery" might just affect VAT, not price)
  - *€ per m² (× box area × qty)* — the option's price is multiplied by
    the box's surface area and by the quantity being ordered. Use this
    for anything proportional to box size: material, printing, varnish,
    laminate.
  - *Flat, once per order* — a fixed amount added once regardless of box
    size or quantity. Use this for things like an express-production
    surcharge.
- **Price value** — the actual number (in your shop's currency)

**If it's a Checkbox** (like "Professional artwork check — €20"):
you get a simpler single option — just an **Option label** and a **Flat
price**. It's a one-time flat charge, added only when the box is ticked.
This is exactly the setup you need for the "Other: Professional artwork
check – 20 EUR" field.

### Step 5 — Publish
Click **Publish**. It's now live on every matching product.

---

## 6. Setting up Settings (Quantity, VAT, and the master switch)

Go to **PW Configurator → Settings**.

- **Price calculation is active site-wide** — this is the big kill
  switch. Untick it and *every* product's calculated price becomes €0
  everywhere, instantly, without touching a single Field Group. Use this
  while you're first building out the site so customers browsing don't
  see half-finished prices; tick it back on when you're ready to go live.
  While it's off, the offer box shows a small "Pricing preview mode"
  notice so nobody on your team mistakes it for a real price.
- **Global price multiplier** — scales absolutely everything, site-wide,
  on top of whatever each Field Group already calculates. Leave at `1`.
  Only touch this for a temporary store-wide promotion or surcharge.
- **Quantity Tiers table** — the exact numbers customers can choose in
  the Quantity dropdown (500, 1000, 5000 … 100000 by default) and the
  discount multiplier for each. A multiplier of `0.68` means boxes at
  that quantity tier are priced as if they're 68% of the per-box price
  at tier 1 — i.e., bulk orders get progressively cheaper per box, the
  way real print-shop pricing works. Click **+ Add another tier row** if
  you need more tiers, or just change the numbers in the existing rows.
- **Default VAT rate** — enter as a decimal, so 20% is `0.20`, 19% is
  `0.19`, and so on.

Click **Save Settings** at the bottom when done.

---

## 7. Putting the configurator on the product page

You're using Elementor Pro, so:

1. Edit your **Single Product** template in **Elementor → Theme Builder**
   (or edit a specific product page directly with Elementor if you're
   not using a shared template yet).
2. Drag in the **PW Product Configurator** widget (search for "PW" in
   the widget panel).
3. Leave the **Product ID** field blank — it automatically shows the
   right form for whichever product page it's on.
4. Style the surrounding section/columns however you like — the widget's
   own layout (form on the left, sticky offer box on the right) will sit
   inside it.

If you'd rather use a shortcode instead of the widget, `[pwc_configurator]`
does the same thing.

---

## 8. Rearranging the fields visually with CSS (no code changes needed per-product)

By default, every field prints in one fixed order: Quantity → Versions →
Format → then each Field Group's fields in the order you built them in
wp-admin. That's the order a brand-new template shows out of the box.

**If your custom design wants a different visual arrangement** — e.g. two
fields side by side, Material given a full-width row with more visual
weight, or the checkbox pulled up near the top instead of sitting at the
bottom — you don't need to touch this plugin's code or rebuild anything
in wp-admin. Every field wrapper carries a `data-pwc-field="..."`
attribute you can target directly from your theme's Custom CSS (in
Elementor: the template's Advanced tab, or your child theme's
stylesheet).

**Field keys to target** (the `key` value is whatever you typed in the
Field Group's Field key box, e.g. `material`, `printing_outside`,
`artwork_check`). The three built-in fields always use these keys:
`quantity`, `versions`, `dimension_id`. The offer box and its five rows
are also targetable: `offer_box`, `offer_base_price`,
`offer_additional_options`, `offer_total_net`, `offer_vat`,
`offer_total_incl_vat`.

### Example use case: two-column layout with Material given its own full-width row

Say your design wants Quantity and Format side by side, Material as a
prominent full-width row underneath, then Printing and Varnish paired up:

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

### Example use case: simple reordering without changing columns

If you just want a different top-to-bottom order but the same single-column
layout, `order` on a flex container is simpler than Grid:

```css
.pwc-col-form { display: flex; flex-direction: column; }
.pwc-field[data-pwc-field="artwork_check"] { order: -1; } /* move to the very top */
.pwc-field[data-pwc-field="material"]      { order: 1; }
```

### Things worth knowing before you rely on this

- **Screen-reader/keyboard tab order does not change** — it still follows
  the original HTML order, only the visual position moves. Fine for most
  storefronts, but worth knowing if accessibility compliance matters to you.
- **New fields default to source order until you add a rule for them.**
  If you build a brand-new Field Group next month, its fields will appear
  in the default position until you add a matching `[data-pwc-field="..."]`
  rule for the new key — nothing breaks, it just won't be exactly where
  you want visually until that one extra CSS line is added.
- **Hiding a field with `display: none` does not remove it from
  pricing.** Only hide fields that are genuinely optional or that you're
  comfortable defaulting silently — a hidden required field (like
  Quantity or Format) will block "Add to cart" since nothing gets
  selected.

---

## 9. A full worked example

Say you're setting up **Folding Boxes**:

1. **Dimension Presets**: create `40 x 40 x 100 mm`, `60 x 60 x 150 mm`,
   etc. Tick "Folding Boxes" category on each.
2. **Field Group** "Folding Box Options", tick "Folding Boxes" category:
   - Dropdown `Material` (key `material`): options "275g GC1
     chromoboard" (€ per m², 4.20), "350g GZ board" (€ per m², 6.10)
   - Dropdown `Printing – Outside` (key `printing_outside`): "1 colour,
     black" (€ per m², 0.35), "Full colour" (€ per m², 0.90)
   - Dropdown `Varnish` (key `varnish`): "No varnish" (no price impact,
     0), "Matt varnish" (€ per m², 0.15)
   - Dropdown `Production time` (key `production_time`): "Economy" (flat,
     once per order, 0), "Express" (flat, once per order, 45)
   - Checkbox `Other` (key `artwork_check`): "Professional artwork
     check" — flat price 20
3. Publish everything.
4. Open any product in the "Folding Boxes" category — the configurator
   widget on its page now shows Quantity, Versions, Format, Material,
   Printing – Outside, Varnish, Production time, and the artwork-check
   checkbox, all pulled in automatically. No per-product setup needed
   unless you want an exception (see section 3).

---

## 10. Things that still need your real data before launch

- **The box surface-area formula.** Right now it's a generic placeholder
  formula (`box_area_m2()` inside `includes/class-pwc-pricing.php`) that
  estimates how much board a box uses from its L/W/H. This drives every
  "€ per m²" price, so it's the single most important number to get
  right — swap it for your actual die-cut/board-consumption formula
  (ask whoever does your production calculations, or a developer can
  hook `pwc_box_area_m2` to plug in the real one per box style).
- **Real prices** for every option you enter above — the plugin has no
  opinion on what things should cost, it just multiplies what you type in.
- **VAT rates per delivery country**, if you sell across borders — right
  now there's one default rate; ask a developer to extend the
  `pwc_country_vat_map` option or the `pwc_vat_rate` filter for
  per-country rates.

---

## 11. Not built yet

The "Want an individual sample?", "Online Designer" toggle, "Offer by
email," "Send contours by email," and "Print offer" actions from the
reference site aren't included in this version — this covers the core
configure → live price → add to cart flow only. Let your developer know
if/when you want these added; they'd build on top of the same pricing
engine described here.

---

## 12. WooCommerce compatibility & standards

- **HPOS (High-Performance Order Storage)** — declared compatible. All order
  data this plugin writes goes through WooCommerce's official
  `WC_Order_Item::add_meta_data()` method, which works correctly whether your
  store uses legacy post-based orders or the newer HPOS tables.
- **Currency** — every price shown uses WooCommerce's own `wc_price()`
  formatting, so it automatically follows whatever you've set in
  **WooCommerce → Settings → General** (currency, symbol position, decimal
  and thousand separators). Prices you type into a Field Group option are
  assumed to already be in your store's base currency, the same way
  WooCommerce's own product prices work — this plugin doesn't do currency
  conversion, and neither does core WooCommerce without a separate
  multi-currency extension.
- **Uninstall** — deactivating the plugin (the normal Plugins-screen toggle)
  never deletes anything. A full **Delete** only removes data if you've
  explicitly ticked **Settings → "Delete all data on uninstall"** first —
  off by default, so nobody loses their setup from a routine update or
  deactivate/reactivate cycle. Even with it on, past order data is never
  touched — only this plugin's own Field Groups, Dimension Presets, and
  settings.
- **Security/data handling** — all admin form submissions are nonce-verified
  and capability-checked (`manage_woocommerce` / `edit_post`), and all output
  is escaped (`esc_html`, `esc_attr`) per WordPress/WooCommerce coding
  standards.

One thing to flag honestly: field labels and admin text in this plugin
aren't yet wrapped for translation (`__()`/`_e()`), so it isn't
translation-ready out of the box. Not a functional issue if your site is
single-language, but worth knowing if you'll need other languages later.

---

## 13. Troubleshooting

- **Form shows but prices don't update** — check **Settings → Price
  calculation is active site-wide** is ticked, and check the Field
  Group's own **"Include this group's fields in price calculation"** box
  is ticked too.
- **A dropdown option shows but doesn't change the price** — its Pricing
  mode is probably set to "No price impact." Edit the Field Group and
  change that option's pricing mode.
- **A category's options aren't showing on a product** — check the
  product is actually in that category (Product → Product categories,
  in the sidebar), and check the Field Group wasn't accidentally added
  to that product's "Exclude these groups" list.
- **"Add to cart" fails** — Quantity and Format are required fields;
  make sure both are selected before submitting.
