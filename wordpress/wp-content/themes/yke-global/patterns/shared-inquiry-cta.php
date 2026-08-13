<?php
/**
 * Title: Shared Inquiry CTA
 * Slug: yke-global/shared-inquiry-cta
 * Categories: yke-sections
 * Inserter: no
 */

$lang   = function_exists( 'pll_current_language' ) ? pll_current_language() : 'en';
if ( ! $lang ) { $lang = 'en'; }
$prefix = ( 'zh' === $lang ) ? '/zh' : '';

$tr = static function ( $key, $fallback = '' ) use ( $lang ) {
	return class_exists( 'YKE_Site_Core' ) ? YKE_Site_Core::translate( $key, $lang ) : $fallback;
};

$url = static function ( $path ) use ( $prefix ) {
	return home_url( $prefix . $path );
};

$copy = array(
	'eyebrow' => $tr( 'shared.inquiry_cta.eyebrow', 'Next Step' ),
	'title'   => $tr( 'shared.inquiry_cta.title', 'Submit your project requirements for a tailored solution and quote' ),
	'desc'    => $tr( 'shared.inquiry_cta.desc', 'Share your target market, product line, voltage level, quantity, and delivery timeline - YKE will respond with a preliminary proposal within 24 hours.' ),
	'btn1'    => $tr( 'shared.inquiry_cta.btn1', 'Contact YKE' ),
	'btn2'    => $tr( 'shared.inquiry_cta.btn2', 'Browse Products' ),
);

ob_start();
?>
<!-- wp:html -->
<section class="yke-cta-band yke-cta-band--home">
	<div class="yke-cta-band-inner">
		<div>
			<p class="yke-section-eyebrow"><?php echo esc_html( $copy['eyebrow'] ); ?></p>
			<h2><?php echo esc_html( $copy['title'] ); ?></h2>
			<p><?php echo esc_html( $copy['desc'] ); ?></p>
		</div>
		<div class="yke-cta-band-actions">
			<a class="yke-btn-primary" href="<?php echo esc_url( $url( '/contact/' ) ); ?>"><?php echo esc_html( $copy['btn1'] ); ?></a>
			<a class="yke-btn-outline yke-btn-outline--dark" href="<?php echo esc_url( $url( '/products/' ) ); ?>"><?php echo esc_html( $copy['btn2'] ); ?></a>
		</div>
	</div>
</section>
<!-- /wp:html -->
<?php
$content = trim( ob_get_clean() );

register_block_pattern(
	'yke-global/shared-inquiry-cta',
	array(
		'title'      => __( 'Shared Inquiry CTA', 'yke-global' ),
		'categories' => array( 'yke-sections' ),
		'content'    => $content,
	)
);
