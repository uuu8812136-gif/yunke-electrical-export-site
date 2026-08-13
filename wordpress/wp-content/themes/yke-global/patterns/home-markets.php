<?php
/**
 * Title: Home Markets
 * Slug: yke-global/home-markets
 * Categories: yke-sections
 * Inserter: no
 */

register_block_pattern(
	'yke-global/home-markets',
	array(
		'title'      => __( 'Home Markets', 'yke-global' ),
		'categories' => array( 'yke-sections' ),
		'content'    => '<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"40px","bottom":"40px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:40px;padding-bottom:40px"><!-- wp:paragraph {"className":"yke-kicker"} -->
<p class="yke-kicker">Market fit</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2,"fontSize":"xl"} -->
<h2 class="wp-block-heading has-xl-font-size">Configured around target market conditions</h2>
<!-- /wp:heading -->

<!-- wp:columns {"className":"yke-market-grid"} -->
<div class="wp-block-columns yke-market-grid"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"yke-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group yke-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Middle East</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>High-temperature and IP65+ dust-proof options for utility, industrial park, and substation environments where SASO-style compliance matters.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"yke-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group yke-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Southeast Asia</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Fast-delivery supply, anti-condensation designs, and localized technical documents for manufacturing relocation and renewable growth.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"yke-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group yke-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Africa</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Cost-controlled oil-immersed transformers and low-voltage systems aligned with grid upgrades, industrial loads, and utility distribution needs.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"yke-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group yke-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Latin America &amp; South Asia</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>One-stop procurement for solar, storage, and step-up substations with support around local utility acceptance and EPC coordination.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
	)
);
