# Baraa Pharmacy

Responsive, component-based WooCommerce storefront theme.

## Setup

1. Make sure WooCommerce is installed and active.
2. In WordPress, open **Appearance → Themes** and activate **Baraa Pharmacy**.
3. Set the desired site language in **Settings → General**. The theme includes Arabic and US English translation catalogs; WordPress uses the selected locale to choose translated text and text direction.
4. Add products, sale prices, and product categories in WooCommerce. The homepage reads these records dynamically.

Visitors can switch between Arabic and English in the header; the choice is saved for later pages in the visit. The color-theme control toggles light/dark mode and remembers the selection. Without a saved choice, the theme follows the device color preference.

For a standalone, WordPress-admin-free design preview with an Arabic/English switch, open `preview.html` in this theme folder (for example, `http://baraa.local/wp-content/themes/baraa-pharmacy/preview.html`). The preview uses sample products and prices; shopping and search do not submit orders.

The homepage is assembled from the files in `template-parts/homepage/`. The search, product links, category links, and cart use WooCommerce and WordPress URLs; no sample prices or fake cart interactions are included.

The WooCommerce shop and product-category archives use a responsive product catalog with live category and price filters, sorting, product counts, and pagination.

WooCommerce single-product pages use the product's actual gallery, description, attributes, price, rating, stock status, purchasable options, reviews, and related products.

The cart page uses WooCommerce cart data and actions for quantities, coupons, shipping, totals, and checkout. Its responsive layout places cart items beside the order summary, with an empty-cart state when no items are present.

Product names, descriptions, and category names are store content rather than theme interface text. To maintain separate Arabic and English catalog content, configure a multilingual content plugin such as Polylang or WPML.

Prescription intake and pharmacy-specific dispensing rules are not implemented by this presentation theme. Configure and review those workflows before publishing prescription products for online purchase.
