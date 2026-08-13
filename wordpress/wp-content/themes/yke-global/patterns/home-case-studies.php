<?php
/**
 * Title: Home Case Studies
 * Slug: yke-global/home-case-studies
 * Categories: yke-sections
 * Inserter: no
 */

$lang   = function_exists( 'pll_current_language' ) ? pll_current_language() : 'en';
if ( ! $lang ) { $lang = 'en'; }
$is_zh  = ( 'zh' === $lang );
$prefix = $is_zh ? '/zh' : '';

$tr = static function ( $key, $fallback = '' ) use ( $lang ) {
	return class_exists( 'YKE_Site_Core' ) ? YKE_Site_Core::translate( $key, $lang ) : $fallback;
};

$url = static function ( $path ) use ( $prefix ) {
	return home_url( $prefix . $path );
};

$cards = $is_zh
	? array(
		array(
			'country' => '沙特阿拉伯',
			'title'   => '工业园区配电项目',
			'scope'   => '32 面 KYN28-12 高压开关柜 + 8 台 SCB11 干式变压器',
			'fact'    => '38 天完成交付，并完成 SGS 全项验货。',
			'badge'   => 'SASO',
			'link'    => $url( '/case-studies/sha-te-gong-ye-yuan-qu-pei-dian-xiang-mu/' ),
		),
		array(
			'country' => '越南',
			'title'   => '制造业工厂配电升级项目',
			'scope'   => '60 台 GGD 低压开关柜 + 密集型母线系统',
			'fact'    => '按工厂搬迁节奏分两批交付，便于现场安装配合。',
			'badge'   => 'Factory Upgrade',
			'link'    => $url( '/case-studies/yue-nan-gong-han-pei-dian-gai-zao-xiang-mu/' ),
		),
		array(
			'country' => '尼日利亚',
			'title'   => '电网升级变压器供货项目',
			'scope'   => '20 台 S13 油浸式变压器，容量覆盖 500kVA-1600kVA',
			'fact'    => '通过本地合作伙伴完成信用证结算，并保持零质量投诉。',
			'badge'   => 'Utility Upgrade',
			'link'    => $url( '/case-studies/ni-ri-li-ya-dian-wang-sheng-ji-xiang-mu/' ),
		),
		array(
			'country' => '印尼',
			'title'   => '50MW 屋顶光伏并网项目',
			'scope'   => '12 套并网柜 + 12 套升压预装式变电站',
			'fact'    => '项目资料按 SNI / PLN 审批思路准备，支撑并网推进。',
			'badge'   => 'SNI / PLN',
			'link'    => $url( '/case-studies/yin-ni-50mw-wu-ding-guang-fu-bing-wang-xiang-mu/' ),
		),
	)
	: array(
		array(
			'country' => 'Saudi Arabia',
			'title'   => 'Industrial Park Distribution Project',
			'scope'   => '32 panels of KYN28-12 MV switchgear and 8 SCB11 dry-type transformers',
			'fact'    => 'Delivered in 38 days with full SGS inspection before shipment.',
			'badge'   => 'SASO',
			'link'    => $url( '/case-studies/saudi-arabia-industrial-park-power-distribution-project/' ),
		),
		array(
			'country' => 'Vietnam',
			'title'   => 'Factory Distribution Upgrade',
			'scope'   => '60 GGD LV switchgear cabinets with dense busbar systems',
			'fact'    => 'Shipped in two batches to match the factory relocation and installation schedule.',
			'badge'   => 'Factory Upgrade',
			'link'    => $url( '/case-studies/vietnam-manufacturing-group-factory-distribution-upgrade/' ),
		),
		array(
			'country' => 'Nigeria',
			'title'   => 'Grid Upgrade Transformer Supply',
			'scope'   => '20 S13 oil-immersed transformers covering 500kVA to 1600kVA',
			'fact'    => 'Completed through a local partner with full L/C settlement and zero complaints.',
			'badge'   => 'Utility Upgrade',
			'link'    => $url( '/case-studies/nigeria-grid-upgrade-transformer-supply/' ),
		),
		array(
			'country' => 'Indonesia',
			'title'   => '50MW Rooftop Solar Grid-Connection',
			'scope'   => '12 grid-connection cabinets and 12 step-up prefabricated substations',
			'fact'    => 'Documentation was prepared for SNI / PLN approval coordination and grid connection work.',
			'badge'   => 'SNI / PLN',
			'link'    => $url( '/case-studies/indonesia-50mw-rooftop-solar-grid-connection-package/' ),
		),
	);

$section = array(
	'eyebrow'          => $tr( 'home.case_studies.eyebrow', 'Project Showcase' ),
	'title'            => $tr( 'home.case_studies.title', 'Four overseas projects across the Middle East, Southeast Asia, and Africa' ),
	'lede'             => $tr( 'home.case_studies.lede', 'Saudi Arabia, Vietnam, Nigeria, Indonesia - each card shows supply scope and key delivery facts. Click through for full case details.' ),
	'placeholder_kick' => $tr( 'home.case_studies.placeholder_kick', 'Field photo to be supplied' ),
	'placeholder_body' => $tr( 'home.case_studies.placeholder_body', 'Replace this slot directly when a real installation photo is available.' ),
	'button'           => $tr( 'home.case_studies.button', 'View Case' ),
);

ob_start();
?>
<!-- wp:html -->
<section id="home-project-showcase" class="yke-section yke-section--compact">
	<div class="yke-section-inner">
		<div class="yke-section-head">
			<p class="yke-section-eyebrow"><?php echo esc_html( $section['eyebrow'] ); ?></p>
			<h2><?php echo esc_html( $section['title'] ); ?></h2>
			<p><?php echo esc_html( $section['lede'] ); ?></p>
		</div>
		<div class="yke-project-grid yke-project-grid--homepage">
			<?php foreach ( $cards as $card ) : ?>
				<a class="yke-project-card yke-project-card--homepage" href="<?php echo esc_url( $card['link'] ); ?>">
					<div class="yke-project-card-img yke-project-card-img--placeholder">
						<div class="yke-field-photo-placeholder" aria-label="<?php echo esc_attr( $section['placeholder_kick'] ); ?>">
							<span class="yke-field-photo-placeholder__badge"><?php echo esc_html( $card['badge'] ); ?></span>
							<div class="yke-field-photo-placeholder__copy">
								<strong><?php echo esc_html( $section['placeholder_kick'] ); ?></strong>
								<span><?php echo esc_html( $section['placeholder_body'] ); ?></span>
							</div>
						</div>
					</div>
					<div class="yke-project-card-body">
						<span class="yke-project-card-region"><?php echo esc_html( $card['country'] ); ?></span>
						<h3><?php echo esc_html( $card['title'] ); ?></h3>
						<p class="yke-project-card-scope"><?php echo esc_html( $card['scope'] ); ?></p>
						<p class="yke-project-card-fact"><?php echo esc_html( $card['fact'] ); ?></p>
						<span class="yke-product-card-link"><?php echo esc_html( $section['button'] ); ?></span>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<!-- /wp:html -->
<?php
$content = trim( ob_get_clean() );

register_block_pattern(
	'yke-global/home-case-studies',
	array(
		'title'      => __( 'Home Case Studies', 'yke-global' ),
		'categories' => array( 'yke-sections' ),
		'content'    => $content,
	)
);
