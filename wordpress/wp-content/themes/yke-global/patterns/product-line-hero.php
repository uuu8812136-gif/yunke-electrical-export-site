<?php
/**
 * Title: Product Line Hero
 * Slug: yke-global/product-line-hero
 * Categories: yke-sections
 */

register_block_pattern(
	'yke-global/product-line-hero',
	array(
		'title'      => __( 'Product Line Hero', 'yke-global' ),
		'categories' => array( 'yke-sections' ),
		'content'    => '<!-- wp:group {"className":"yke-panel","layout":{"type":"constrained"}} -->
<div class="wp-block-group yke-panel"><!-- wp:paragraph {"className":"yke-kicker"} -->
<p class="yke-kicker">Product line</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"fontSize":"xl"} -->
<h1 class="wp-block-heading has-xl-font-size">Replace with product line name</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"md"} -->
<p class="has-md-font-size">Use this reusable section for the product family overview, export fit, and buyer-facing summary.</p>
<!-- /wp:paragraph -->

<!-- wp:columns {"className":"yke-two-column-grid"} -->
<div class="wp-block-columns yke-two-column-grid"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:list {"className":"yke-checklist"} -->
<ul class="yke-checklist"><li>Voltage range placeholder</li><li>Standards and certifications placeholder</li><li>Representative model placeholder</li></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:shortcode -->
[yke_inquiry_form product_interest="Product Line Inquiry"]
<!-- /wp:shortcode --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
	)
);
