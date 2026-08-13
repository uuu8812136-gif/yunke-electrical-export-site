<?php
/**
 * Title: Home Why YKE
 * Slug: yke-global/home-why-yke
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

$image = get_theme_file_uri( 'assets/images/placeholders/图片3.png' );

$copy = array(
	'eyebrow'    => $tr( 'home.why_yke.eyebrow', 'Company Intro + Advantages' ),
	'title'      => $tr( 'home.why_yke.title', 'Export-oriented power equipment manufacturer, delivering complete supply and project support' ),
	'lede'       => $tr( 'home.why_yke.lede', 'Serving overseas EPC contractors, industrial end users, and regional channel partners - from product selection and documentation to inspection coordination and project delivery, all in one workflow.' ),
	'quote'      => $tr( 'home.why_yke.quote', 'Product scope, documentation, approval coordination, and delivery response - handled within a single supply chain.' ),
	'body'       => $tr( 'home.why_yke.body', 'YKE goes beyond single-device supply, offering overseas buyers an integrated solution across multiple product lines, English documentation, approval support, and cross-market delivery communication.' ),
	'button'     => $tr( 'home.why_yke.button', 'About YKE' ),
	'advantages' => array(
		array(
			'title' => $tr( 'home.why_yke.adv1_title', 'Export standards and documentation support' ),
			'body'  => $tr( 'home.why_yke.adv1_body', 'Drawings, manuals, testing files, packing lists, nameplate information, and core commercial files can be prepared by project.' ),
		),
		array(
			'title' => $tr( 'home.why_yke.adv2_title', 'Flexible component brands and wiring schemes' ),
			'body'  => $tr( 'home.why_yke.adv2_body', 'ABB, Schneider, Siemens, and similar component options can be matched with wiring and structure adjustments for destination-market habits.' ),
		),
		array(
			'title' => $tr( 'home.why_yke.adv3_title', 'Adaptation for heat, dust, and humidity' ),
			'body'  => $tr( 'home.why_yke.adv3_body', 'Middle East, Africa, and coastal project conditions can be supported with targeted high-temperature, dust-protection, moisture-control, and outdoor protection configurations.' ),
		),
		array(
			'title' => $tr( 'home.why_yke.adv4_title', 'Third-party inspection and English files' ),
			'body'  => $tr( 'home.why_yke.adv4_body', 'SGS, BV, and Intertek inspection coordination can be paired with English document packs for procurement, technical review, and approval stages.' ),
		),
		array(
			'title' => $tr( 'home.why_yke.adv5_title', 'Overseas approval support' ),
			'body'  => $tr( 'home.why_yke.adv5_body', 'Homepage products and project cards already surface SASO, SNI, and PLN signals for buyers reviewing Middle East and Southeast Asia opportunities.' ),
		),
	),
);

ob_start();
?>
<!-- wp:html -->
<section id="home-company-intro" class="yke-section yke-section--wide">
	<div class="yke-section-inner">
		<div class="yke-section-head">
			<p class="yke-section-eyebrow"><?php echo esc_html( $copy['eyebrow'] ); ?></p>
			<h2><?php echo esc_html( $copy['title'] ); ?></h2>
			<p><?php echo esc_html( $copy['lede'] ); ?></p>
		</div>
		<div class="yke-capability yke-home-company">
			<div class="yke-capability-right">
				<figure class="yke-marketing-visual yke-marketing-visual--capability">
					<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $tr( 'home.why_yke.img_alt', 'YKE factory and production scene' ) ); ?>" loading="lazy" />
				</figure>
			</div>
			<div class="yke-capability-body">
				<h3 class="yke-quote"><?php echo esc_html( $copy['quote'] ); ?></h3>
				<p class="yke-capability-desc"><?php echo esc_html( $copy['body'] ); ?></p>
				<div class="yke-home-action-row">
					<a class="yke-btn-primary" href="<?php echo esc_url( $url( '/about-yke/' ) ); ?>"><?php echo esc_html( $copy['button'] ); ?></a>
				</div>
			</div>
		</div>
		<div class="yke-trust-grid yke-home-advantages">
			<?php foreach ( $copy['advantages'] as $item ) : ?>
				<article class="yke-trust-card yke-home-advantage-card">
					<h3><?php echo esc_html( $item['title'] ); ?></h3>
					<p><?php echo esc_html( $item['body'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<!-- /wp:html -->
<?php
$content = trim( ob_get_clean() );

register_block_pattern(
	'yke-global/home-why-yke',
	array(
		'title'      => __( 'Home Why YKE', 'yke-global' ),
		'categories' => array( 'yke-sections' ),
		'content'    => $content,
	)
);
