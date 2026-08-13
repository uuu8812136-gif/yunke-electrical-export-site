<?php
/**
 * Title: Home Distributor
 * Slug: yke-global/home-distributor
 * Categories: yke-sections
 * Inserter: no
 */

$lang   = function_exists( 'pll_current_language' ) ? pll_current_language() : 'en';
if ( ! $lang ) { $lang = 'en'; }
$prefix = ( 'zh' === $lang ) ? '/zh' : '';

$tr = static function ( $key, $fallback = '' ) use ( $lang ) {
	return class_exists( 'YKE_Site_Core' ) ? YKE_Site_Core::translate( $key, $lang ) : $fallback;
};

$t = array(
	'eyebrow' => $tr( 'home.distributor.eyebrow', 'Service & Cooperation Paths' ),
	'title'   => $tr( 'home.distributor.title', 'Choose the next step by your buying goal' ),
	'lede'    => $tr( 'home.distributor.lede', 'Different visitors arrive with different priorities. Some are already matching a project, some are evaluating partnership options, and some are still screening suppliers at a high level.' ),
	'cards'   => array(
		array( 'title' => $tr( 'home.distributor.card1_title', 'Project Procurement' ), 'body' => $tr( 'home.distributor.card1_body', 'Best for buyers who already know the voltage class, application, and delivery window and want to move into product fit and supply feasibility.' ), 'url' => $prefix . '/contact/', 'label' => $tr( 'btn.start_inquiry', 'Start Project Inquiry' ) ),
		array( 'title' => $tr( 'home.distributor.card2_title', 'Distributor Partnership' ), 'body' => $tr( 'home.distributor.card2_body', 'Best for channel partners with an existing local customer base who want to add MV, LV, transformer, or renewable electrical lines.' ), 'url' => $prefix . '/distributor-partnership/', 'label' => $tr( 'btn.review_partnership', 'Review Partnership Model' ) ),
		array( 'title' => $tr( 'home.distributor.card3_title', 'Supplier Screening' ), 'body' => $tr( 'home.distributor.card3_body', 'If you are still comparing product range, quality control, project proof, and export support, start with a broader review first.' ), 'url' => $prefix . '/about-yke/', 'label' => $tr( 'btn.review_company', 'Review Company Capability' ) ),
	),
);

$cards_markup = '';
foreach ( $t['cards'] as $card ) {
	$cards_markup .= '<article class="yke-trust-card"><h3>' . esc_html( $card['title'] ) . '</h3><p>' . esc_html( $card['body'] ) . '</p><a class="yke-text-link" href="' . esc_url( $card['url'] ) . '">' . esc_html( $card['label'] ) . '</a></article>';
}

register_block_pattern(
	'yke-global/home-distributor',
	array(
		'title'      => __( 'Home Distributor', 'yke-global' ),
		'categories' => array( 'yke-sections' ),
		'content'    => '<!-- wp:html -->
<section class="yke-section yke-section-cloud yke-section--compact yke-home-next-step">
	<div class="yke-section-inner">
		<p class="yke-section-eyebrow">' . esc_html( $t['eyebrow'] ) . '</p>
		<h2 class="yke-section-title">' . esc_html( $t['title'] ) . '</h2>
		<p class="yke-section-lede">' . esc_html( $t['lede'] ) . '</p>
		<div class="yke-trust-grid">' . $cards_markup . '</div>
	</div>
</section>
<!-- /wp:html -->',
	)
);
