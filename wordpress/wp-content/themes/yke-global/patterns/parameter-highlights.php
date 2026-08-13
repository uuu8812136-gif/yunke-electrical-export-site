<?php
/**
 * Title: Parameter Highlights
 * Slug: yke-global/parameter-highlights
 * Categories: yke-sections
 */

register_block_pattern(
	'yke-global/parameter-highlights',
	array(
		'title'      => __( 'Parameter Highlights', 'yke-global' ),
		'categories' => array( 'yke-sections' ),
		'content'    => '<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"yke-metric","layout":{"type":"constrained"}} -->
<div class="wp-block-group yke-metric"><strong>0.4kV-35kV</strong><p>Voltage coverage placeholder</p></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"yke-metric","layout":{"type":"constrained"}} -->
<div class="wp-block-group yke-metric"><strong>IEC / GB</strong><p>Standards placeholder</p></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"yke-metric","layout":{"type":"constrained"}} -->
<div class="wp-block-group yke-metric"><strong>OEM / ODM</strong><p>Customization placeholder</p></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->',
	)
);
