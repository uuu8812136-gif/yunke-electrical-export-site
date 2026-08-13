<?php
/**
 * Title: Certification Grid
 * Slug: yke-global/certification-grid
 * Categories: yke-sections
 */

register_block_pattern(
	'yke-global/certification-grid',
	array(
		'title'      => __( 'Certification Grid', 'yke-global' ),
		'categories' => array( 'yke-sections' ),
		'content'    => '<!-- wp:columns {"className":"yke-cert-grid"} -->
<div class="wp-block-columns yke-cert-grid"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"yke-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group yke-card"><!-- wp:heading {"level":4} -->
<h4 class="wp-block-heading">CE</h4>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Certificate or compliance summary placeholder.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"yke-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group yke-card"><!-- wp:heading {"level":4} -->
<h4 class="wp-block-heading">CCC</h4>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Certificate or compliance summary placeholder.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"yke-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group yke-card"><!-- wp:heading {"level":4} -->
<h4 class="wp-block-heading">ISO 9001</h4>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Certificate or compliance summary placeholder.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"yke-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group yke-card"><!-- wp:heading {"level":4} -->
<h4 class="wp-block-heading">ISO 14001 / 45001</h4>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Certificate or compliance summary placeholder.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->',
	)
);
