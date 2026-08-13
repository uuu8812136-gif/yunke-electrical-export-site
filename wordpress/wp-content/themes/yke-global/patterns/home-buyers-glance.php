<?php
/**
 * Title: Home Strength Band
 * Slug: yke-global/home-buyers-glance
 * Categories: yke-sections
 * Inserter: no
 */

$lang  = function_exists( 'pll_current_language' ) ? pll_current_language() : 'en';
if ( ! $lang ) { $lang = 'en'; }

$tr = static function ( $key, $fallback = '' ) use ( $lang ) {
	return class_exists( 'YKE_Site_Core' ) ? YKE_Site_Core::translate( $key, $lang ) : $fallback;
};

$section_title = $tr( 'home.buyers_glance.title', 'Company Strength' );

$stats = array(
	array(
		'value' => '20XX',
		'label' => $tr( 'home.buyers_glance.stat1', 'Year Founded' ),
	),
	array(
		'value' => '1XXXX m²',
		'label' => $tr( 'home.buyers_glance.stat2', 'Factory Area' ),
	),
	array(
		'value' => '1XX+',
		'label' => $tr( 'home.buyers_glance.stat3', 'Patents' ),
	),
	array(
		'value' => '1XX+',
		'label' => $tr( 'home.buyers_glance.stat4', 'Equipment' ),
	),
);

ob_start();
?>
<!-- wp:html -->
<section id="home-strength-band" class="yke-section yke-section-cloud yke-section--compact yke-home-strength">
	<div class="yke-section-inner">
		<div class="yke-home-strength-band">
			<p class="screen-reader-text"><?php echo esc_html( $section_title ); ?></p>
			<div class="yke-stat-band" role="list" aria-label="<?php echo esc_attr( $section_title ); ?>">
				<?php foreach ( $stats as $stat ) : ?>
					<div class="yke-stat-item" role="listitem">
						<div class="yke-stat-value"><?php echo esc_html( $stat['value'] ); ?></div>
						<div class="yke-stat-label"><?php echo esc_html( $stat['label'] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
<!-- /wp:html -->
<?php
$content = trim( ob_get_clean() );

register_block_pattern(
	'yke-global/home-buyers-glance',
	array(
		'title'      => __( 'Home Strength Band', 'yke-global' ),
		'categories' => array( 'yke-sections' ),
		'content'    => $content,
	)
);
