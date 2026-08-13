<?php
/**
 * Title: Home Products Grid
 * Slug: yke-global/home-products-grid
 * Categories: yke-sections
 * Inserter: no
 *
 * Renders 5 product-line cards with a hover popover listing each line's specific models.
 * Uses the [yke_product_hover_grid] shortcode (registered in yke-site-core) so the same
 * component is shared with /products/ and /case-studies/.
 */

$lang  = function_exists( 'pll_current_language' ) ? pll_current_language() : 'en';
if ( ! $lang ) { $lang = 'en'; }

$tr = static function ( $key, $fallback = '' ) use ( $lang ) {
	return class_exists( 'YKE_Site_Core' ) ? YKE_Site_Core::translate( $key, $lang ) : $fallback;
};

$heading = array(
	'eyebrow' => $tr( 'home.products_grid.eyebrow', 'Hot Products / Five Product Lines' ),
	'title'   => $tr( 'home.products_grid.title', 'Five product lines, matched to your project scenario' ),
	'lede'    => $tr( 'home.products_grid.lede', 'From MV/LV switchgear to transformers, prefabricated substations, and new energy electrical. Hover a line to see its specific models, click a model to open its detail page.' ),
);

ob_start();
?>
<!-- wp:html -->
<section id="home-products" class="yke-section yke-section--compact">
	<div class="yke-section-inner">
		<div class="yke-section-head">
			<p class="yke-section-eyebrow"><?php echo esc_html( $heading['eyebrow'] ); ?></p>
			<h2><?php echo esc_html( $heading['title'] ); ?></h2>
			<p><?php echo esc_html( $heading['lede'] ); ?></p>
		</div>
		[yke_product_hover_grid variant="home"]
	</div>
</section>
<!-- /wp:html -->
<?php
$content = trim( ob_get_clean() );

register_block_pattern(
	'yke-global/home-products-grid',
	array(
		'title'      => __( 'Home Products Grid', 'yke-global' ),
		'categories' => array( 'yke-sections' ),
		'content'    => $content,
	)
);
