<?php
/**
 * Title: Case Card
 * Slug: yke-global/case-card
 * Categories: yke-sections
 */

register_block_pattern(
	'yke-global/case-card',
	array(
		'title'      => __( 'Case Card', 'yke-global' ),
		'categories' => array( 'yke-sections' ),
		'content'    => '<!-- wp:group {"className":"yke-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group yke-card"><!-- wp:paragraph {"className":"yke-kicker"} -->
<p class="yke-kicker">Case region</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Case title placeholder</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Use for country, client type, supply scope, technical focus, and result summary.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#">Read Case</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->',
	)
);
