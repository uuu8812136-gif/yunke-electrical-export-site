<?php
/**
 * Title: Home Quality
 * Slug: yke-global/home-quality
 * Categories: yke-sections
 * Inserter: no
 */

$lang  = function_exists( 'pll_current_language' ) ? pll_current_language() : 'en';
if ( ! $lang ) { $lang = 'en'; }

$tr = static function ( $key, $fallback = '' ) use ( $lang ) {
	return class_exists( 'YKE_Site_Core' ) ? YKE_Site_Core::translate( $key, $lang ) : $fallback;
};

$section = array(
	'eyebrow' => $tr( 'home.quality.eyebrow', 'Certificates and Standards Compliance' ),
	'title'   => $tr( 'home.quality.title', 'CE, CCC, ISO systems, and IEC 61439 compliance' ),
	'lede'    => $tr( 'home.quality.lede', 'All product lines carry CE, CCC, and ISO three-system certifications. Low-voltage assemblies comply with IEC 61439. Every batch ships with complete third-party test reports.' ),
);

$cert_note = $tr( 'home.quality.cert_note', 'Certificate image to be supplied' );

$items = array(
	array(
		'title'       => 'CE',
		'note'        => $cert_note,
		'description' => $tr( 'home.quality.cert_desc_ce', 'CE certificate image pending customer supply' ),
		'type'        => 'certificate',
	),
	array(
		'title'       => 'CCC',
		'note'        => $cert_note,
		'description' => $tr( 'home.quality.cert_desc_ccc', 'CCC certificate image pending customer supply' ),
		'type'        => 'certificate',
	),
	array(
		'title'       => 'ISO 9001',
		'note'        => $cert_note,
		'description' => $tr( 'home.quality.cert_desc_iso9', 'ISO 9001 certificate image pending customer supply' ),
		'type'        => 'certificate',
	),
	array(
		'title'       => 'ISO 14001',
		'note'        => $cert_note,
		'description' => $tr( 'home.quality.cert_desc_iso14', 'ISO 14001 certificate image pending customer supply' ),
		'type'        => 'certificate',
	),
	array(
		'title'       => 'ISO 45001',
		'note'        => $cert_note,
		'description' => $tr( 'home.quality.cert_desc_iso45', 'ISO 45001 certificate image pending customer supply' ),
		'type'        => 'certificate',
	),
	array(
		'title'       => 'IEC 61439',
		'note'        => $tr( 'home.quality.iec_note', 'Standards compliance slot' ),
		'description' => $tr( 'home.quality.iec_desc', 'Homepage standards compliance slot for low-voltage assemblies' ),
		'type'        => 'standard',
	),
);

ob_start();
?>
<!-- wp:html -->
<section id="home-certifications" class="yke-section yke-section-cloud yke-section--compact">
	<div class="yke-section-inner">
		<div class="yke-section-head">
			<p class="yke-section-eyebrow"><?php echo esc_html( $section['eyebrow'] ); ?></p>
			<h2><?php echo esc_html( $section['title'] ); ?></h2>
			<p><?php echo esc_html( $section['lede'] ); ?></p>
		</div>
		<div class="yke-cert-scroll yke-cert-scroll--grid" role="list" aria-label="<?php echo esc_attr( $section['title'] ); ?>">
			<?php foreach ( $items as $item ) : ?>
				<figure class="yke-cert-item yke-cert-item--placeholder<?php echo 'standard' === $item['type'] ? ' yke-cert-item--standard' : ''; ?>" role="listitem">
					<div class="yke-cert-placeholder" aria-label="<?php echo esc_attr( $item['description'] ); ?>">
						<span class="yke-cert-placeholder__line yke-cert-placeholder__line--short"></span>
						<span class="yke-cert-placeholder__line"></span>
						<span class="yke-cert-placeholder__line"></span>
						<span class="yke-cert-placeholder__seal"><?php echo esc_html( $item['title'] ); ?></span>
					</div>
					<figcaption>
						<strong class="yke-cert-item__title"><?php echo esc_html( $item['title'] ); ?></strong>
						<span class="yke-cert-item__note"><?php echo esc_html( $item['note'] ); ?></span>
					</figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<!-- /wp:html -->
<?php
$content = trim( ob_get_clean() );

register_block_pattern(
	'yke-global/home-quality',
	array(
		'title'      => __( 'Home Quality', 'yke-global' ),
		'categories' => array( 'yke-sections' ),
		'content'    => $content,
	)
);
