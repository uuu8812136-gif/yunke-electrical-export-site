<?php
/**
 * Title: Home Hero
 * Slug: yke-global/home-hero
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

$slides = array(
	array(
		'image'   => get_theme_file_uri( 'assets/images/placeholders/图片1.png' ),
		'eyebrow' => $tr( 'home.hero.slide1.eyebrow', 'In-house Manufacturing Base' ),
		'title'   => $tr( 'home.hero.slide1.title', 'In-house Guangzhou production base with full-process visibility' ),
		'sub'     => $tr( 'home.hero.slide1.sub', 'YKE operates its own Guangzhou manufacturing base, covering everything from sheet metal processing to final assembly and testing. Equipped with CNC machinery and a temperature-controlled assembly workshop, every critical process is supervised by senior engineers to ensure consistency and reliability across every unit shipped.' ),
		'signals' => array(
			$tr( 'home.hero.slide1.signal1', 'CNC sheet metal line' ),
			$tr( 'home.hero.slide1.signal2', 'Temperature-controlled assembly' ),
			$tr( 'home.hero.slide1.signal3', 'Senior engineer supervision' ),
		),
		'primary' => array(
			'label' => $tr( 'home.hero.slide1.primary_label', 'See Company Strength' ),
			'url'   => '#home-strength-band',
		),
		'secondary' => array(
			'label' => $tr( 'home.hero.slide1.secondary_label', 'Contact YKE' ),
			'url'   => $url( '/contact/' ),
		),
	),
	array(
		'image'   => get_theme_file_uri( 'assets/images/placeholders/图片2.png' ),
		'eyebrow' => $tr( 'home.hero.slide2.eyebrow', 'Project-ready Export Loading' ),
		'title'   => $tr( 'home.hero.slide2.title', 'Skid-mounted export loading, on time and on spec' ),
		'sub'     => $tr( 'home.hero.slide2.sub', 'From final assembly line to export container, YKE follows standardized loading procedures tailored to destination and certification requirements. Wooden crate fixing, moisture protection, dangerous goods declaration, and on-site photo archiving ensure the equipment arrives at overseas sites ready to install and energize.' ),
		'signals' => array(
			$tr( 'home.hero.slide2.signal1', 'Standardized loading' ),
			$tr( 'home.hero.slide2.signal2', 'Moisture & impact protection' ),
			$tr( 'home.hero.slide2.signal3', 'On-site photo archive' ),
		),
		'primary' => array(
			'label' => $tr( 'home.hero.slide2.primary_label', 'View Project Cases' ),
			'url'   => $url( '/case-studies/' ),
		),
		'secondary' => array(
			'label' => $tr( 'home.hero.slide2.secondary_label', 'Send Inquiry' ),
			'url'   => $url( '/contact/' ),
		),
	),
);

ob_start();
?>
<!-- wp:html -->
<section class="yke-hero" aria-label="<?php echo esc_attr( $tr( 'home.hero.aria_section', 'YKE homepage hero' ) ); ?>" data-yke-hero="homepage">
	<div class="yke-hero-stage">
		<?php foreach ( $slides as $index => $slide ) : ?>
			<div class="yke-hero-slide<?php echo 0 === $index ? ' yke-hero-slide--active' : ''; ?>" style="background-image:url(<?php echo esc_url( $slide['image'] ); ?>)"></div>
		<?php endforeach; ?>
		<div class="yke-hero-veil"></div>
	</div>
	<div class="yke-hero-progress" aria-hidden="true"><span class="yke-hero-progress__bar"></span></div>
	<div class="yke-hero-inner">
		<?php foreach ( $slides as $index => $slide ) : ?>
			<div class="yke-hero-slide-content<?php echo 0 === $index ? ' yke-hero-slide-content--active' : ''; ?>" aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>">
				<p class="yke-hero-eyebrow"><?php echo esc_html( $slide['eyebrow'] ); ?></p>
				<h1 class="yke-hero-title"><?php echo esc_html( $slide['title'] ); ?></h1>
				<p class="yke-hero-sub"><?php echo esc_html( $slide['sub'] ); ?></p>
				<ul class="yke-hero-signal-list" aria-label="<?php echo esc_attr( $tr( 'home.hero.aria_signals', 'Hero key signals' ) ); ?>">
					<?php foreach ( $slide['signals'] as $signal ) : ?>
						<li><?php echo esc_html( $signal ); ?></li>
					<?php endforeach; ?>
				</ul>
				<div class="yke-hero-actions">
					<a class="yke-btn-primary" href="<?php echo esc_url( $slide['primary']['url'] ); ?>"><?php echo esc_html( $slide['primary']['label'] ); ?></a>
					<a class="yke-btn-outline" href="<?php echo esc_url( $slide['secondary']['url'] ); ?>"><?php echo esc_html( $slide['secondary']['label'] ); ?></a>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<div class="yke-hero-arrows">
		<button type="button" class="yke-hero-arrow yke-hero-arrow--prev" aria-label="<?php echo esc_attr( $tr( 'home.hero.aria_prev', 'Previous slide' ) ); ?>">&#8249;</button>
		<button type="button" class="yke-hero-arrow yke-hero-arrow--next" aria-label="<?php echo esc_attr( $tr( 'home.hero.aria_next', 'Next slide' ) ); ?>">&#8250;</button>
	</div>
	<div class="yke-hero-dots" aria-label="<?php echo esc_attr( $tr( 'home.hero.aria_nav', 'Hero slide navigation' ) ); ?>">
		<?php foreach ( $slides as $index => $slide ) : ?>
			<button type="button" class="yke-hero-dot<?php echo 0 === $index ? ' yke-hero-dot--active' : ''; ?>" data-index="<?php echo esc_attr( (string) $index ); ?>" aria-label="<?php echo esc_attr( 'zh' === $lang ? sprintf( '切换到第 %d 张', $index + 1 ) : sprintf( 'Go to slide %d', $index + 1 ) ); ?>"></button>
		<?php endforeach; ?>
	</div>
</section>
<!-- /wp:html -->
<?php
$content = trim( ob_get_clean() );

register_block_pattern(
	'yke-global/home-hero',
	array(
		'title'      => __( 'Home Hero', 'yke-global' ),
		'categories' => array( 'yke-sections' ),
		'content'    => $content,
	)
);
