<?php
/**
 * Core plugin bootstrap and feature implementation.
 *
 * @package YKE_Site_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YKE_Site_Core {
	/**
	 * Official plugin stack required by the site.
	 */
	const OFFICIAL_PLUGIN_FILES = array(
		'polylang/polylang.php',
		'contact-form-7/wp-contact-form-7.php',
		'contact-form-cfdb7/contact-form-cfdb-7.php',
		'wp-mail-smtp/wp_mail_smtp.php',
		'wordpress-seo/wp-seo.php',
		'redirection/redirection.php',
	);

	/**
	 * Canonical CF7 form title for inquiries.
	 */
	const CF7_FORM_TITLE = 'YKE Global Inquiry';

	/**
	 * Option key for seeded CF7 form ID.
	 */
	const CF7_FORM_OPTION = 'yke_cf7_form_id';

	/**
	 * Settings group slug.
	 */
	const SETTINGS_GROUP = 'yke_site_settings';

	/**
	 * Settings page slug.
	 */
	const SETTINGS_PAGE = 'yke-site-settings';

	/**
	 * Common option prefix.
	 */
	const OPTION_PREFIX = 'yke_';

	/**
	 * Meta key used to identify seeded records across resyncs.
	 */
	const SEED_KEY_META = '_yke_site_core_seed_key';

	/**
	 * Inquiry form action.
	 */
	const INQUIRY_ACTION = 'yke_submit_inquiry';

	/**
	 * Inquiry nonce action.
	 */
	const INQUIRY_NONCE_ACTION = 'yke_submit_inquiry_nonce';

	/**
	 * Boot plugin features.
	 */
	public static function boot() {
		add_filter( 'pll_get_post_types', array( __CLASS__, 'filter_polylang_post_types' ), 10, 2 );
		add_action( 'init', array( __CLASS__, 'register_content_models' ) );
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'init', array( __CLASS__, 'register_assets' ) );
		add_action( 'init', array( __CLASS__, 'register_pll_strings' ), 15 );
		add_action( 'init', array( __CLASS__, 'maybe_sync_seeded_content' ), 20 );
		add_action( 'init', array( __CLASS__, 'repair_seeded_page_copy' ), 25 );
		add_action( 'init', array( __CLASS__, 'register_seeded_page_routes' ), 35 );
		add_action( 'pre_get_posts', array( __CLASS__, 'force_chinese_case_archive_query' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_boot_official_plugins' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_configure_official_plugins' ), 25 );
		add_action( 'admin_menu', array( __CLASS__, 'register_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_setup_notice' ) );
		add_action( 'admin_post_' . self::INQUIRY_ACTION, array( __CLASS__, 'handle_inquiry_submission' ) );
		add_action( 'admin_post_nopriv_' . self::INQUIRY_ACTION, array( __CLASS__, 'handle_inquiry_submission' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'save_post_case_study', array( __CLASS__, 'save_case_study_meta' ) );
		add_filter( 'manage_case_study_posts_columns', array( __CLASS__, 'filter_case_study_columns' ) );
		add_action( 'manage_case_study_posts_custom_column', array( __CLASS__, 'render_case_study_column' ), 10, 2 );
		add_filter( 'manage_yke_inquiry_posts_columns', array( __CLASS__, 'filter_inquiry_columns' ) );
		add_action( 'manage_yke_inquiry_posts_custom_column', array( __CLASS__, 'render_inquiry_column' ), 10, 2 );
		add_filter( 'the_content', array( __CLASS__, 'append_case_study_details' ) );
		add_filter( 'the_content', array( __CLASS__, 'render_marketing_page_content' ), 20 );
		add_filter( 'the_content', array( __CLASS__, 'append_page_enhancements' ), 12 );
		add_filter( 'render_block', array( __CLASS__, 'render_theme_shortcodes' ), 20, 2 );
		add_filter( 'render_block', array( __CLASS__, 'inject_case_study_featured_image_fallback' ), 22, 2 );
		add_filter( 'render_block', array( __CLASS__, 'localize_theme_block_output' ), 25, 2 );
		add_filter( 'query_vars', array( __CLASS__, 'register_public_query_vars' ) );
		add_filter( 'request', array( __CLASS__, 'route_seeded_page_request' ) );
		add_filter( 'redirect_canonical', array( __CLASS__, 'filter_seeded_canonical_redirect' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_ai_translate_warmup' ) );
		add_action( 'wp_ajax_yke_check_translate', array( __CLASS__, 'ajax_check_translate' ) );
		add_action( 'wp_ajax_nopriv_yke_check_translate', array( __CLASS__, 'ajax_check_translate' ) );
		add_action( 'wp_ajax_yke_warm_translate', array( __CLASS__, 'ajax_warm_translate' ) );
		add_action( 'wp_ajax_nopriv_yke_warm_translate', array( __CLASS__, 'ajax_warm_translate' ) );
		add_action( 'admin_post_yke_bulk_translate', array( __CLASS__, 'admin_post_bulk_translate' ) );
		add_filter( 'document_title_parts', array( __CLASS__, 'filter_document_title_parts' ) );
		add_filter( 'page_link', array( __CLASS__, 'filter_seeded_page_link' ), 20, 3 );
		add_filter( 'wpseo_title', array( __CLASS__, 'filter_wpseo_title' ) );
		add_filter( 'wpseo_opengraph_title', array( __CLASS__, 'filter_wpseo_title' ) );
		add_filter( 'wpseo_twitter_title', array( __CLASS__, 'filter_wpseo_title' ) );
		add_filter( 'wpseo_metadesc', array( __CLASS__, 'filter_wpseo_meta_description' ) );
		add_filter( 'wpseo_opengraph_desc', array( __CLASS__, 'filter_wpseo_meta_description' ) );
		add_filter( 'shortcode_atts_wpcf7', array( __CLASS__, 'filter_cf7_shortcode_atts' ), 10, 3 );
		add_filter( 'wpcf7_form_hidden_fields', array( __CLASS__, 'inject_cf7_hidden_fields' ) );
		add_filter( 'wpcf7_special_mail_tags', array( __CLASS__, 'replace_cf7_special_mail_tags' ), 10, 4 );
		add_action( 'wpcf7_mail_sent', array( __CLASS__, 'capture_cf7_submission' ) );
		add_action( 'wp_head', array( __CLASS__, 'render_head_integrations' ), 2 );
		add_action( 'wp_body_open', array( __CLASS__, 'render_body_integrations' ) );

		add_shortcode( 'yke_contact_details', array( __CLASS__, 'render_contact_details' ) );
		add_shortcode( 'yke_contact_field', array( __CLASS__, 'render_contact_field' ) );
		add_shortcode( 'yke_inquiry_form', array( __CLASS__, 'render_inquiry_form' ) );
		add_shortcode( 'yke_site_nav', array( __CLASS__, 'render_site_nav' ) );
		add_shortcode( 'yke_header_cta', array( __CLASS__, 'render_header_cta' ) );
		add_shortcode( 'yke_page_hero', array( __CLASS__, 'render_page_hero' ) );
		add_shortcode( 'yke_case_archive_intro', array( __CLASS__, 'render_case_archive_intro' ) );
		add_shortcode( 'yke_case_archive_cards', array( __CLASS__, 'render_case_archive_cards' ) );
		add_shortcode( 'yke_case_teasers', array( __CLASS__, 'render_case_teasers_shortcode' ) );
		add_shortcode( 'yke_case_study_sidebar', array( __CLASS__, 'render_case_study_sidebar' ) );
		add_shortcode( 'yke_product_hover_grid', array( __CLASS__, 'render_product_hover_grid_shortcode' ) );
		add_shortcode( 'yke_case_product_browser', array( __CLASS__, 'render_case_product_browser_shortcode' ) );

		// 绕过 core/shortcode 块的 wpautop — WordPress 7.0 的 render_block_core_shortcode
		// 直接调用 wpautop($content)，会破坏我们 shortcode 返回的块级 HTML 结构。
		// 在 pre_render_block 阶段直接 do_shortcode，跳过 wpautop。
		add_filter( 'pre_render_block', array( __CLASS__, 'bypass_wpautop_for_yke_shortcodes' ), 10, 2 );
		// 在产品相关页面跳过全站共享的 inquiry CTA 区块（避免与产品页自带的底部 CTA 重复）。
		add_filter( 'pre_render_block', array( __CLASS__, 'maybe_skip_inquiry_cta_on_product_pages' ), 15, 2 );
	}

	/**
	 * 在 pre_render_block 阶段拦截 core/shortcode 块，直接用 do_shortcode 处理，
	 * 绕过 render_block_core_shortcode 的 wpautop 调用。
	 *
	 * @param string|null $pre_render 预渲染结果。
	 * @param array       $block      块数据。
	 * @return string|null
	 */
	public static function bypass_wpautop_for_yke_shortcodes( $pre_render, $block ) {
		if ( 'core/shortcode' !== $block['blockName'] ) {
			return $pre_render;
		}
		$content = isset( $block['innerHTML'] ) ? $block['innerHTML'] : '';
		// WordPress 在 block-template.php 对整个模板先 do_shortcode，再 do_blocks()。
		// 所以 core/shortcode 块的 innerHTML 到达 render_block_core_shortcode 时已是 HTML。
		// render_block_core_shortcode 会调用 wpautop($content) 破坏块级 HTML。
		// 这里检测到我们的标记后直接返回 innerHTML，跳过 wpautop。
		if ( '' !== $content && false !== strpos( $content, 'yke-' ) ) {
			return $content;
		}
		return $pre_render;
	}

	/**
	 * 在产品相关页面（/products/、产品线、型号详情页）跳过全站共享的 inquiry CTA 区块。
	 *
	 * 这些页面在自身的 build_*_page_content() 输出中已包含底部 CTA 区块，
	 * 若再加载 page.html 模板中的 shared-inquiry-cta pattern，会出现重复 CTA。
	 * 在 pre_render_block 阶段拦截 core/pattern 块，返回空字符串以跳过整个 pattern 渲染。
	 *
	 * @param string|null $pre_render 预渲染结果。
	 * @param array       $block      块数据。
	 * @return string|null
	 */
	public static function maybe_skip_inquiry_cta_on_product_pages( $pre_render, $block ) {
		// 尊重先前的 pre_render 决定，不覆盖。
		if ( null !== $pre_render ) {
			return $pre_render;
		}
		if ( ! isset( $block['blockName'] ) || 'core/pattern' !== $block['blockName'] ) {
			return $pre_render;
		}
		$slug = isset( $block['attrs']['slug'] ) ? (string) $block['attrs']['slug'] : '';
		if ( 'yke-global/shared-inquiry-cta' !== $slug ) {
			return $pre_render;
		}
		if ( ! self::is_current_request_product_page() ) {
			return $pre_render;
		}
		return '';
	}

	/**
	 * 判断当前请求是否属于产品相关页面（/products/、/zh/products/、产品线、型号详情）。
	 *
	 * @return bool
	 */
	protected static function is_current_request_product_page() {
		$request_path = self::get_request_path();
		if ( '' === $request_path ) {
			return false;
		}
		return 'products' === $request_path
			|| 0 === strpos( $request_path, 'products/' )
			|| 'zh/products' === $request_path
			|| 0 === strpos( $request_path, 'zh/products/' );
	}

	/**
	 * Ensure Polylang treats case studies as translatable content.
	 *
	 * @param array $post_types Registered post types.
	 * @param bool  $is_settings Whether the filter runs in settings context.
	 * @return array
	 */
	public static function filter_polylang_post_types( $post_types, $is_settings ) {
		unset( $is_settings );

		$post_types['case_study'] = 'case_study';

		return $post_types;
	}

	/**
	 * Activation hook callback.
	 */
	public static function activate() {
		self::register_content_models();
		self::register_meta();
		self::run_seed_sync();
		self::maybe_boot_official_plugins();
		self::maybe_configure_official_plugins();
	}

	/**
	 * Deactivation hook callback.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Backfill seeded content when plugin version introduces new defaults.
	 */
	public static function maybe_sync_seeded_content() {
		$seed_version = (string) get_option( 'yke_site_core_seed_version', '' );
		if ( YKE_SITE_CORE_VERSION === $seed_version ) {
			return;
		}

		self::run_seed_sync();
	}

	/**
	 * Register frontend assets.
	 */
	public static function register_assets() {
		wp_register_style(
			'yke-site-core',
			YKE_SITE_CORE_URL . 'assets/css/yke-site-core.css',
			array(),
			YKE_SITE_CORE_VERSION
		);
	}

	/**
	 * Activate the official plugin stack when available.
	 */
	public static function maybe_boot_official_plugins() {
		if ( ! is_admin() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( self::OFFICIAL_PLUGIN_FILES as $plugin_file ) {
			if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
				continue;
			}

			if ( is_plugin_active( $plugin_file ) ) {
				continue;
			}

			activate_plugin( $plugin_file, '', false, false );
		}
	}

	/**
	 * Configure official plugins after activation.
	 */
	public static function maybe_configure_official_plugins() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		self::ensure_polylang_languages();
		self::repair_polylang_language_labels();
		self::ensure_polylang_translations();
		self::ensure_polylang_case_study_languages();
		self::ensure_cf7_inquiry_form();
		self::ensure_cf7_spam_protection_settings();
		self::ensure_wp_mail_smtp_settings();
		self::ensure_redirection_database();
		self::ensure_yoast_site_defaults();
	}

	/**
	 * Register custom post types and taxonomies.
	 */
	public static function register_content_models() {
		register_post_type(
			'case_study',
			array(
				'labels'            => array(
					'name'               => __( 'Case Studies', 'yke-site-core' ),
					'singular_name'      => __( 'Case Study', 'yke-site-core' ),
					'add_new'            => __( 'Add Case Study', 'yke-site-core' ),
					'add_new_item'       => __( 'Add New Case Study', 'yke-site-core' ),
					'edit_item'          => __( 'Edit Case Study', 'yke-site-core' ),
					'new_item'           => __( 'New Case Study', 'yke-site-core' ),
					'view_item'          => __( 'View Case Study', 'yke-site-core' ),
					'search_items'       => __( 'Search Case Studies', 'yke-site-core' ),
					'not_found'          => __( 'No case studies found.', 'yke-site-core' ),
					'not_found_in_trash' => __( 'No case studies found in Trash.', 'yke-site-core' ),
				),
				'public'            => true,
				'has_archive'       => 'case-studies',
				'menu_icon'         => 'dashicons-admin-site-alt3',
				'menu_position'     => 20,
				'show_in_rest'      => true,
				'show_in_nav_menus' => true,
				'supports'          => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
				'rewrite'           => array(
					'slug'       => 'case-studies',
					'with_front' => false,
				),
			)
		);

		register_post_type(
			'yke_inquiry',
			array(
				'labels'              => array(
					'name'               => __( 'Inquiries', 'yke-site-core' ),
					'singular_name'      => __( 'Inquiry', 'yke-site-core' ),
					'add_new_item'       => __( 'Add Inquiry', 'yke-site-core' ),
					'edit_item'          => __( 'View Inquiry', 'yke-site-core' ),
					'new_item'           => __( 'New Inquiry', 'yke-site-core' ),
					'view_item'          => __( 'View Inquiry', 'yke-site-core' ),
					'search_items'       => __( 'Search Inquiries', 'yke-site-core' ),
					'not_found'          => __( 'No inquiries found.', 'yke-site-core' ),
					'not_found_in_trash' => __( 'No inquiries found in Trash.', 'yke-site-core' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_icon'           => 'dashicons-email-alt',
				'menu_position'       => 21,
				'show_in_rest'        => false,
				'supports'            => array( 'title', 'editor' ),
				'rewrite'             => false,
			)
		);

		register_taxonomy(
			'case_region',
			'case_study',
			array(
				'labels'            => array(
					'name'          => __( 'Case Regions', 'yke-site-core' ),
					'singular_name' => __( 'Case Region', 'yke-site-core' ),
				),
				'public'            => true,
				'show_in_rest'      => true,
				'hierarchical'      => true,
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'       => 'case-region',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			'product_line',
			array( 'page', 'case_study' ),
			array(
				'labels'            => array(
					'name'          => __( 'Product Lines', 'yke-site-core' ),
					'singular_name' => __( 'Product Line', 'yke-site-core' ),
				),
				'public'            => false,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'hierarchical'      => true,
				'show_admin_column' => true,
				'rewrite'           => false,
			)
		);
	}

	/**
	 * Register post meta.
	 */
	public static function register_meta() {
		foreach ( self::get_case_study_meta_fields() as $key => $field ) {
			register_post_meta(
				'case_study',
				self::OPTION_PREFIX . $key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'default'           => '',
					'label'             => $field['label'],
					'sanitize_callback' => $field['sanitize'],
				)
			);
		}

		foreach ( self::get_inquiry_meta_fields() as $key => $field ) {
			register_post_meta(
				'yke_inquiry',
				self::OPTION_PREFIX . $key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => false,
					'default'           => '',
					'label'             => $field['label'],
					'sanitize_callback' => $field['sanitize'],
				)
			);
		}
	}

	/**
	 * Register settings page.
	 */
	public static function register_settings_page() {
		add_options_page(
			__( 'YKE Site Settings', 'yke-site-core' ),
			__( 'YKE Site Settings', 'yke-site-core' ),
			'manage_options',
			self::SETTINGS_PAGE,
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register site settings.
	 */
	public static function register_settings() {
		foreach ( self::get_setting_fields() as $key => $field ) {
			register_setting(
				self::SETTINGS_GROUP,
				self::OPTION_PREFIX . $key,
				array(
					'type'              => 'string',
					'sanitize_callback' => $field['sanitize'],
					'default'           => $field['default'],
				)
			);
		}
	}

	/**
	 * Render settings page.
	 */
	public static function render_settings_page() {
		$fields = self::get_setting_fields();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'YKE Site Settings', 'yke-site-core' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( self::SETTINGS_GROUP ); ?>
				<table class="form-table" role="presentation">
					<tbody>
					<?php foreach ( $fields as $key => $field ) : ?>
						<tr>
							<th scope="row">
								<label for="<?php echo esc_attr( self::OPTION_PREFIX . $key ); ?>">
									<?php echo esc_html( $field['label'] ); ?>
								</label>
							</th>
							<td>
								<?php if ( 'textarea' === $field['input'] ) : ?>
									<textarea
										id="<?php echo esc_attr( self::OPTION_PREFIX . $key ); ?>"
										name="<?php echo esc_attr( self::OPTION_PREFIX . $key ); ?>"
										rows="<?php echo esc_attr( isset( $field['rows'] ) ? (int) $field['rows'] : 4 ); ?>"
										class="large-text<?php echo ! empty( $field['code'] ) ? ' code' : ''; ?>"
									><?php echo esc_textarea( self::get_setting( $key ) ); ?></textarea>
								<?php else : ?>
									<input
										id="<?php echo esc_attr( self::OPTION_PREFIX . $key ); ?>"
										type="<?php echo esc_attr( $field['input'] ); ?>"
										class="regular-text"
										name="<?php echo esc_attr( self::OPTION_PREFIX . $key ); ?>"
										value="<?php echo esc_attr( self::get_setting( $key ) ); ?>"
									/>
								<?php endif; ?>
								<?php if ( ! empty( $field['description'] ) ) : ?>
									<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php submit_button(); ?>
			</form>

			<?php self::render_ai_translate_admin_panel(); ?>
		</div>
		<?php
	}

	/**
	 * Render the AI translation admin panel on the YKE settings page.
	 *
	 * Lets the admin pick a Polylang language and bulk-translate all
	 * registered YKE strings to it via the free MyMemory API.
	 *
	 * @return void
	 */
	protected static function render_ai_translate_admin_panel() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$languages = self::get_active_language_slugs();
		$cache     = get_option( 'yke_ai_translate_cache', array() );
		if ( ! is_array( $cache ) ) {
			$cache = array();
		}
		$msg = isset( $_GET['yke_msg'] ) ? sanitize_key( wp_unslash( $_GET['yke_msg'] ) ) : '';
		?>
		<hr />
		<h2><?php esc_html_e( 'Translation tools (auto AI)', 'yke-site-core' ); ?></h2>
		<p>
			<?php esc_html_e( 'When the site visitor switches to a Polylang language that has no manual translation, the page would normally fall back to English. Use the button below to pre-translate every registered YKE string into a language via the free MyMemory API, so the page renders in that language instead of English.', 'yke-site-core' ); ?>
		</p>
		<?php if ( 'translate_done' === $msg ) :
			$lang = isset( $_GET['lang'] ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : '';
			$t    = isset( $_GET['t'] ) ? (int) $_GET['t'] : 0;
			$s    = isset( $_GET['s'] ) ? (int) $_GET['s'] : 0;
			$f    = isset( $_GET['f'] ) ? (int) $_GET['f'] : 0;
			?>
			<div class="notice notice-success is-dismissible inline">
				<p>
					<strong><?php echo esc_html( strtoupper( $lang ) ); ?></strong>:
					<?php
					printf(
						/* translators: 1: translated, 2: skipped, 3: failed */
						esc_html__( 'translated %1$d, skipped %2$d, failed %3$d', 'yke-site-core' ),
						$t, $s, $f
					);
					?>
				</p>
			</div>
		<?php elseif ( 'invalid_lang' === $msg ) : ?>
			<div class="notice notice-error is-dismissible inline"><p><?php esc_html_e( 'Invalid language code.', 'yke-site-core' ); ?></p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;align-items:end;gap:12px;flex-wrap:wrap;">
			<?php wp_nonce_field( 'yke_bulk_translate' ); ?>
			<input type="hidden" name="action" value="yke_bulk_translate" />
			<p>
				<label for="yke-bulk-lang"><strong><?php esc_html_e( 'Target language', 'yke-site-core' ); ?></strong></label><br />
				<select id="yke-bulk-lang" name="lang">
					<?php foreach ( $languages as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>">
							<?php echo esc_html( $label ); ?>
							(<?php echo esc_html( $slug ); ?>)
							<?php
							$count = isset( $cache[ $slug ] ) ? count( (array) $cache[ $slug ] ) : 0;
							if ( $count > 0 ) {
								echo ' — ' . (int) $count . ' cached';
							}
							?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Pre-translate all strings (AI)', 'yke-site-core' ); ?>
				</button>
			</p>
		</form>
		<?php
	}

	/**
	 * Return the list of currently active Polylang language slugs + labels.
	 *
	 * Falls back to a sensible default if Polylang is not available.
	 *
	 * @return array<string,string>
	 */
	protected static function get_active_language_slugs() {
		if ( function_exists( 'pll_languages_list' ) ) {
			$slugs = pll_languages_list( array( 'fields' => 'slug' ) );
			$names = pll_languages_list( array( 'fields' => 'name' ) );
			if ( is_array( $slugs ) && is_array( $names ) ) {
				$out = array();
				foreach ( $slugs as $i => $slug ) {
					$label      = isset( $names[ $i ] ) ? $names[ $i ] : $slug;
					$out[ $slug ] = $label;
				}
				return $out;
			}
		}
		return array(
			'en' => 'English',
			'zh' => '中文',
		);
	}

	/**
	 * Register meta boxes.
	 */
	public static function register_meta_boxes() {
		add_meta_box(
			'yke-case-study-details',
			__( 'Case Study Details', 'yke-site-core' ),
			array( __CLASS__, 'render_case_study_meta_box' ),
			'case_study',
			'normal',
			'high'
		);

		add_meta_box(
			'yke-inquiry-details',
			__( 'Inquiry Details', 'yke-site-core' ),
			array( __CLASS__, 'render_inquiry_meta_box' ),
			'yke_inquiry',
			'normal',
			'high'
		);
	}

	/**
	 * Render case study meta box.
	 *
	 * @param WP_Post $post Post instance.
	 */
	public static function render_case_study_meta_box( $post ) {
		wp_nonce_field( 'yke_save_case_study_meta', 'yke_case_study_nonce' );
		?>
		<table class="form-table" role="presentation">
			<tbody>
			<?php foreach ( self::get_case_study_meta_fields() as $key => $field ) : ?>
				<?php $value = get_post_meta( $post->ID, self::OPTION_PREFIX . $key, true ); ?>
				<tr>
					<th scope="row">
						<label for="<?php echo esc_attr( self::OPTION_PREFIX . $key ); ?>">
							<?php echo esc_html( $field['label'] ); ?>
						</label>
					</th>
					<td>
						<?php if ( 'textarea' === $field['input'] ) : ?>
							<textarea
								id="<?php echo esc_attr( self::OPTION_PREFIX . $key ); ?>"
								name="<?php echo esc_attr( self::OPTION_PREFIX . $key ); ?>"
								rows="3"
								class="large-text"
							><?php echo esc_textarea( $value ); ?></textarea>
						<?php else : ?>
							<input
								id="<?php echo esc_attr( self::OPTION_PREFIX . $key ); ?>"
								type="text"
								class="regular-text"
								name="<?php echo esc_attr( self::OPTION_PREFIX . $key ); ?>"
								value="<?php echo esc_attr( $value ); ?>"
							/>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Save case study meta box values.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save_case_study_meta( $post_id ) {
		if ( ! isset( $_POST['yke_case_study_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['yke_case_study_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'yke_save_case_study_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( self::get_case_study_meta_fields() as $key => $field ) {
			$option_key = self::OPTION_PREFIX . $key;
			if ( ! isset( $_POST[ $option_key ] ) ) {
				continue;
			}

			$value = wp_unslash( $_POST[ $option_key ] );
			$clean = call_user_func( $field['sanitize'], $value );

			if ( '' === $clean ) {
				delete_post_meta( $post_id, $option_key );
			} else {
				update_post_meta( $post_id, $option_key, $clean );
			}
		}
	}

	/**
	 * Render inquiry meta box.
	 *
	 * @param WP_Post $post Post instance.
	 */
	public static function render_inquiry_meta_box( $post ) {
		$fields = self::get_inquiry_meta_fields();
		?>
		<table class="widefat striped" role="presentation">
			<tbody>
			<?php foreach ( $fields as $key => $field ) : ?>
				<?php
				$value = get_post_meta( $post->ID, self::OPTION_PREFIX . $key, true );
				if ( '' === $value ) {
					continue;
				}
				?>
				<tr>
					<th scope="row"><?php echo esc_html( $field['label'] ); ?></th>
					<td><?php echo wp_kses_post( self::format_inquiry_meta_value( $key, $value ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Filter case study list columns.
	 *
	 * @param array $columns Columns list.
	 * @return array
	 */
	public static function filter_case_study_columns( $columns ) {
		$updated = array();

		foreach ( $columns as $key => $label ) {
			$updated[ $key ] = $label;
			if ( 'title' === $key ) {
				$updated['yke_country']     = __( 'Country', 'yke-site-core' );
				$updated['yke_client_type'] = __( 'Client Type', 'yke-site-core' );
			}
		}

		return $updated;
	}

	/**
	 * Render case study custom columns.
	 *
	 * @param string $column  Column slug.
	 * @param int    $post_id Post ID.
	 */
	public static function render_case_study_column( $column, $post_id ) {
		if ( 'yke_country' === $column ) {
			echo esc_html( get_post_meta( $post_id, self::OPTION_PREFIX . 'country', true ) );
		}

		if ( 'yke_client_type' === $column ) {
			echo esc_html( get_post_meta( $post_id, self::OPTION_PREFIX . 'client_type', true ) );
		}
	}

	/**
	 * Filter inquiry list columns.
	 *
	 * @param array $columns Columns list.
	 * @return array
	 */
	public static function filter_inquiry_columns( $columns ) {
		return array(
			'cb'                => isset( $columns['cb'] ) ? $columns['cb'] : '<input type="checkbox" />',
			'title'             => __( 'Inquiry', 'yke-site-core' ),
			'yke_email'         => __( 'Email', 'yke-site-core' ),
			'yke_company'       => __( 'Company', 'yke-site-core' ),
			'yke_product_focus' => __( 'Product Interest', 'yke-site-core' ),
			'yke_email_status'  => __( 'Email Status', 'yke-site-core' ),
			'date'              => isset( $columns['date'] ) ? $columns['date'] : __( 'Date', 'yke-site-core' ),
		);
	}

	/**
	 * Render inquiry list columns.
	 *
	 * @param string $column  Column slug.
	 * @param int    $post_id Post ID.
	 */
	public static function render_inquiry_column( $column, $post_id ) {
		switch ( $column ) {
			case 'yke_email':
				$email = get_post_meta( $post_id, self::OPTION_PREFIX . 'email', true );
				if ( $email ) {
					printf(
						'<a href="%1$s">%2$s</a>',
						esc_url( 'mailto:' . $email ),
						esc_html( $email )
					);
				}
				break;
			case 'yke_company':
				echo esc_html( get_post_meta( $post_id, self::OPTION_PREFIX . 'company', true ) );
				break;
			case 'yke_product_focus':
				echo esc_html( get_post_meta( $post_id, self::OPTION_PREFIX . 'product_interest', true ) );
				break;
			case 'yke_email_status':
				echo esc_html( get_post_meta( $post_id, self::OPTION_PREFIX . 'email_status', true ) );
				break;
		}
	}

	/**
	 * Render contact details snippet.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_contact_details( $atts = array() ) {
		self::enqueue_style();

		$atts = shortcode_atts(
			array(
				'layout'              => 'stacked',
				'show_company'        => '1',
				'show_contact_name'   => '1',
				'show_commitments'    => '0',
				'show_certifications' => '0',
			),
			$atts,
			'yke_contact_details'
		);

		$company        = self::get_setting( 'company_name' );
		$contact_name   = self::get_setting( 'contact_name' );
		$email          = self::get_setting( 'contact_email' );
		$phone          = self::get_contact_setting_value( 'contact_phone' );
		$whatsapp       = self::get_contact_setting_value( 'contact_whatsapp' );
		$address        = self::get_contact_setting_value( 'contact_address' );
		$hours          = self::get_setting( 'business_hours' );
		$commitments    = self::get_setting( 'footer_commitments' );
		$certifications = self::get_setting( 'certification_summary' );
		$lang  = self::get_current_site_language();
		$labels = array(
			'email'          => self::t( 'label.email', $lang ),
			'phone'          => self::t( 'label.phone', $lang ),
			'whatsapp'       => self::t( 'label.whatsapp', $lang ),
			'address'        => self::t( 'label.address', $lang ),
			'business_hours' => self::t( 'label.business_hours', $lang ),
			'commitments'    => self::t( 'label.commitments', $lang ),
			'certifications' => self::t( 'label.certifications', $lang ),
		);

		ob_start();
		?>
		<div class="yke-contact-details yke-contact-details--<?php echo esc_attr( sanitize_html_class( $atts['layout'] ) ); ?>">
			<?php if ( self::is_truthy( $atts['show_company'] ) && $company ) : ?>
				<p class="yke-contact-details__company"><strong><?php echo esc_html( $company ); ?></strong></p>
			<?php endif; ?>

			<?php if ( self::is_truthy( $atts['show_contact_name'] ) && $contact_name ) : ?>
				<p class="yke-contact-details__person"><?php echo esc_html( $contact_name ); ?></p>
			<?php endif; ?>

			<ul class="yke-contact-details__list">
				<?php if ( $email ) : ?>
					<li><span><?php echo esc_html( $labels['email'] ); ?></span> <a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a></li>
				<?php endif; ?>
				<?php if ( $phone && ! self::is_placeholder_contact_value( $phone ) ) : ?>
					<li>
						<span><?php echo esc_html( $labels['phone'] ); ?></span>
						<?php if ( self::is_linkable_phone( $phone ) ) : ?>
							<a href="<?php echo esc_url( 'tel:' . self::normalize_phone_link( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
						<?php else : ?>
							<span><?php echo esc_html( $phone ); ?></span>
						<?php endif; ?>
					</li>
				<?php endif; ?>
				<?php if ( $whatsapp && ! self::is_placeholder_contact_value( $whatsapp ) ) : ?>
					<li>
						<span><?php echo esc_html( $labels['whatsapp'] ); ?></span>
						<?php if ( self::is_linkable_phone( $whatsapp ) ) : ?>
							<a href="<?php echo esc_url( self::build_whatsapp_link( $whatsapp ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $whatsapp ); ?></a>
						<?php else : ?>
							<span><?php echo esc_html( $whatsapp ); ?></span>
						<?php endif; ?>
					</li>
				<?php endif; ?>
				<?php if ( $address && ! self::is_placeholder_contact_value( $address ) ) : ?>
					<li><span><?php echo esc_html( $labels['address'] ); ?></span> <span><?php echo wp_kses_post( nl2br( esc_html( $address ) ) ); ?></span></li>
				<?php endif; ?>
				<?php if ( $hours ) : ?>
					<li><span><?php echo esc_html( $labels['business_hours'] ); ?></span> <span><?php echo wp_kses_post( nl2br( esc_html( $hours ) ) ); ?></span></li>
				<?php endif; ?>
			</ul>

			<?php if ( self::is_truthy( $atts['show_commitments'] ) && $commitments ) : ?>
				<div class="yke-contact-details__section">
					<h3><?php echo esc_html( $labels['commitments'] ); ?></h3>
					<?php echo wp_kses_post( self::render_line_list( $commitments ) ); ?>
				</div>
			<?php endif; ?>

			<?php if ( self::is_truthy( $atts['show_certifications'] ) && $certifications ) : ?>
				<div class="yke-contact-details__section">
					<h3><?php echo esc_html( $labels['certifications'] ); ?></h3>
					<p><?php echo esc_html( $certifications ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render a single contact field.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_contact_field( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'field' => 'contact_email',
				'link'  => '1',
			),
			$atts,
			'yke_contact_field'
		);

		$field_map = array(
			'company'        => 'company_name',
			'brand'          => 'brand_name',
			'contact_name'   => 'contact_name',
			'email'          => 'contact_email',
			'phone'          => 'contact_phone',
			'whatsapp'       => 'contact_whatsapp',
			'address'        => 'contact_address',
			'business_hours' => 'business_hours',
		);

		$key = isset( $field_map[ $atts['field'] ] ) ? $field_map[ $atts['field'] ] : $atts['field'];
		$key = sanitize_key( $key );

		if ( ! array_key_exists( $key, self::get_setting_fields() ) ) {
			return '';
		}

		$value = in_array( $key, array( 'contact_phone', 'contact_whatsapp', 'contact_address' ), true )
			? self::get_contact_setting_value( $key )
			: self::get_setting( $key );
		if ( '' === $value ) {
			return '';
		}

		if ( self::is_truthy( $atts['link'] ) ) {
			if ( 'contact_email' === $key ) {
				return sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( 'mailto:' . $value ),
					esc_html( $value )
				);
			}

			if ( 'contact_phone' === $key ) {
				if ( ! self::is_linkable_phone( $value ) ) {
					return esc_html( $value );
				}

				return sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( 'tel:' . self::normalize_phone_link( $value ) ),
					esc_html( $value )
				);
			}

			if ( 'contact_whatsapp' === $key ) {
				if ( ! self::is_linkable_phone( $value ) ) {
					return esc_html( $value );
				}

				return sprintf(
					'<a href="%1$s" target="_blank" rel="noopener">%2$s</a>',
					esc_url( self::build_whatsapp_link( $value ) ),
					esc_html( $value )
				);
			}
		}

		if ( 'contact_address' === $key || 'business_hours' === $key ) {
			return nl2br( esc_html( $value ) );
		}

		return esc_html( $value );
	}

	/**
	 * Render working inquiry form.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_inquiry_form( $atts = array() ) {
		self::enqueue_style();

		$atts = shortcode_atts(
			array(
				'product_interest' => '',
				'button_label'     => '',
			),
			$atts,
			'yke_inquiry_form'
		);

		$status       = isset( $_GET['yke_inquiry_status'] ) ? sanitize_key( wp_unslash( $_GET['yke_inquiry_status'] ) ) : '';
		$product_line = trim( $atts['product_interest'] );
		$form_copy    = self::get_inquiry_form_copy();
		$button_label = trim( $atts['button_label'] ) ? $atts['button_label'] : self::get_setting( 'default_cta' );
		if ( '' === trim( (string) $button_label ) ) {
			$button_label = $form_copy['default_button'];
		}

		if ( self::is_cf7_ready() ) {
			return self::render_cf7_inquiry_form( $status, $product_line, $button_label );
		}

		ob_start();
		?>
		<div class="yke-inquiry-shell">
			<?php if ( $status ) : ?>
				<?php $message = self::get_inquiry_notice_message( $status ); ?>
				<?php if ( $message ) : ?>
					<div class="yke-inquiry-notice yke-inquiry-notice--<?php echo esc_attr( $status ); ?>" aria-live="polite">
						<?php echo esc_html( $message ); ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<form class="yke-inquiry-form yke-form-grid" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::INQUIRY_ACTION ); ?>" />
				<input type="hidden" name="source_url" value="<?php echo esc_url( self::get_current_request_url() ); ?>" />
				<input type="hidden" name="source_title" value="<?php echo esc_attr( self::get_current_source_title() ); ?>" />
				<?php wp_nonce_field( self::INQUIRY_NONCE_ACTION, 'yke_inquiry_nonce' ); ?>

				<div class="yke-field">
					<label for="yke-name"><?php esc_html_e( 'Name', 'yke-site-core' ); ?></label>
					<input id="yke-name" type="text" name="name" required placeholder="<?php echo esc_attr( $form_copy['name_placeholder'] ); ?>" />
				</div>

				<div class="yke-field">
					<label for="yke-company"><?php esc_html_e( 'Company', 'yke-site-core' ); ?></label>
					<input id="yke-company" type="text" name="company" placeholder="<?php echo esc_attr( $form_copy['company_placeholder'] ); ?>" />
				</div>

				<div class="yke-field">
					<label for="yke-country"><?php esc_html_e( 'Country / Market', 'yke-site-core' ); ?></label>
					<input id="yke-country" type="text" name="country" placeholder="<?php echo esc_attr( $form_copy['country_placeholder'] ); ?>" />
				</div>

				<div class="yke-field">
					<label for="yke-email"><?php esc_html_e( 'Email', 'yke-site-core' ); ?></label>
					<input id="yke-email" type="email" name="email" required placeholder="<?php echo esc_attr( $form_copy['email_placeholder'] ); ?>" />
				</div>

				<div class="yke-field">
					<label for="yke-whatsapp"><?php esc_html_e( 'WhatsApp / Phone', 'yke-site-core' ); ?></label>
					<input id="yke-whatsapp" type="text" name="whatsapp" placeholder="<?php echo esc_attr( $form_copy['whatsapp_placeholder'] ); ?>" />
				</div>

				<div class="yke-field">
					<label for="yke-interest"><?php esc_html_e( 'Product Interest', 'yke-site-core' ); ?></label>
					<input id="yke-interest" type="text" name="product_interest" value="<?php echo esc_attr( $product_line ); ?>" placeholder="<?php echo esc_attr( $form_copy['interest_placeholder'] ); ?>" />
				</div>

				<div class="yke-field full-span">
					<label for="yke-requirement"><?php esc_html_e( 'Project Requirement', 'yke-site-core' ); ?></label>
					<textarea id="yke-requirement" name="requirement" rows="5" required placeholder="<?php echo esc_attr( $form_copy['requirement_placeholder'] ); ?>"></textarea>
				</div>

				<div class="yke-field full-span yke-inquiry-form__honeypot" aria-hidden="true">
					<label for="yke-website"><?php esc_html_e( 'Website', 'yke-site-core' ); ?></label>
					<input id="yke-website" type="text" name="website" tabindex="-1" autocomplete="off" />
				</div>

				<div class="yke-field full-span yke-inquiry-form__footer">
					<button type="submit"><?php echo esc_html( $button_label ); ?></button>
				</div>
			</form>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render the unified inquiry form via Contact Form 7 when available.
	 *
	 * @param string $status         Result status.
	 * @param string $product_line   Product line hint.
	 * @param string $button_label   CTA label.
	 * @return string
	 */
	protected static function render_cf7_inquiry_form( $status, $product_line, $button_label ) {
		$form_id = self::get_cf7_form_id();
		if ( ! $form_id ) {
			return self::render_native_inquiry_fallback( $status, $product_line, $button_label );
		}

		$form = WPCF7_ContactForm::get_instance( $form_id );
		if ( ! $form ) {
			return self::render_native_inquiry_fallback( $status, $product_line, $button_label );
		}

		ob_start();
		?>
		<div class="yke-inquiry-shell">
			<?php if ( $status ) : ?>
				<?php $message = self::get_inquiry_notice_message( $status ); ?>
				<?php if ( $message ) : ?>
					<div class="yke-inquiry-notice yke-inquiry-notice--<?php echo esc_attr( $status ); ?>" aria-live="polite">
						<?php echo esc_html( $message ); ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
			<?php
			echo do_shortcode(
				sprintf(
					'[contact-form-7 id="%1$s" title="%2$s" your-product-interest="%3$s" source-title="%4$s" source-url="%5$s"]',
					esc_attr( $form->hash() ),
					esc_attr( $form->title() ),
					esc_attr( $product_line ),
					esc_attr( self::get_current_source_title() ),
					esc_url( self::get_current_request_url() )
				)
			);
			?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render the native form markup as fallback when CF7 is unavailable.
	 *
	 * @param string $status       Result status.
	 * @param string $product_line Product line hint.
	 * @param string $button_label CTA label.
	 * @return string
	 */
	protected static function render_native_inquiry_fallback( $status, $product_line, $button_label ) {
		$form_copy = self::get_inquiry_form_copy();
		if ( '' === trim( (string) $button_label ) ) {
			$button_label = $form_copy['default_button'];
		}

		ob_start();
		?>
		<div class="yke-inquiry-shell">
			<?php if ( $status ) : ?>
				<?php $message = self::get_inquiry_notice_message( $status ); ?>
				<?php if ( $message ) : ?>
					<div class="yke-inquiry-notice yke-inquiry-notice--<?php echo esc_attr( $status ); ?>" aria-live="polite">
						<?php echo esc_html( $message ); ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<form class="yke-inquiry-form yke-form-grid" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::INQUIRY_ACTION ); ?>" />
				<input type="hidden" name="source_url" value="<?php echo esc_url( self::get_current_request_url() ); ?>" />
				<input type="hidden" name="source_title" value="<?php echo esc_attr( self::get_current_source_title() ); ?>" />
				<?php wp_nonce_field( self::INQUIRY_NONCE_ACTION, 'yke_inquiry_nonce' ); ?>

				<div class="yke-field">
					<label for="yke-name"><?php esc_html_e( 'Name', 'yke-site-core' ); ?></label>
					<input id="yke-name" type="text" name="name" required placeholder="<?php echo esc_attr( $form_copy['name_placeholder'] ); ?>" />
				</div>

				<div class="yke-field">
					<label for="yke-company"><?php esc_html_e( 'Company', 'yke-site-core' ); ?></label>
					<input id="yke-company" type="text" name="company" placeholder="<?php echo esc_attr( $form_copy['company_placeholder'] ); ?>" />
				</div>

				<div class="yke-field">
					<label for="yke-country"><?php esc_html_e( 'Country / Market', 'yke-site-core' ); ?></label>
					<input id="yke-country" type="text" name="country" placeholder="<?php echo esc_attr( $form_copy['country_placeholder'] ); ?>" />
				</div>

				<div class="yke-field">
					<label for="yke-email"><?php esc_html_e( 'Email', 'yke-site-core' ); ?></label>
					<input id="yke-email" type="email" name="email" required placeholder="<?php echo esc_attr( $form_copy['email_placeholder'] ); ?>" />
				</div>

				<div class="yke-field">
					<label for="yke-whatsapp"><?php esc_html_e( 'WhatsApp / Phone', 'yke-site-core' ); ?></label>
					<input id="yke-whatsapp" type="text" name="whatsapp" placeholder="<?php echo esc_attr( $form_copy['whatsapp_placeholder'] ); ?>" />
				</div>

				<div class="yke-field">
					<label for="yke-interest"><?php esc_html_e( 'Product Interest', 'yke-site-core' ); ?></label>
					<input id="yke-interest" type="text" name="product_interest" value="<?php echo esc_attr( $product_line ); ?>" placeholder="<?php echo esc_attr( $form_copy['interest_placeholder'] ); ?>" />
				</div>

				<div class="yke-field full-span">
					<label for="yke-requirement"><?php esc_html_e( 'Project Requirement', 'yke-site-core' ); ?></label>
					<textarea id="yke-requirement" name="requirement" rows="5" required placeholder="<?php echo esc_attr( $form_copy['requirement_placeholder'] ); ?>"></textarea>
				</div>

				<div class="yke-field full-span yke-inquiry-form__honeypot" aria-hidden="true">
					<label for="yke-website"><?php esc_html_e( 'Website', 'yke-site-core' ); ?></label>
					<input id="yke-website" type="text" name="website" tabindex="-1" autocomplete="off" />
				</div>

				<div class="yke-field full-span yke-inquiry-form__footer">
					<button type="submit"><?php echo esc_html( $button_label ); ?></button>
				</div>
			</form>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Handle inquiry submissions.
	 */
	public static function handle_inquiry_submission() {
		$source_url = self::get_posted_value( 'source_url', 'esc_url_raw' );

		if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
			self::redirect_after_inquiry( 'error', $source_url );
		}

		$nonce = self::get_posted_value( 'yke_inquiry_nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, self::INQUIRY_NONCE_ACTION ) ) {
			self::redirect_after_inquiry( 'error', $source_url );
		}

		$payload = self::sanitize_inquiry_payload();
		if ( self::has_inquiry_validation_error( $payload ) ) {
			self::redirect_after_inquiry( 'validation', $source_url );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'yke_inquiry',
				'post_status'  => 'private',
				'post_title'   => self::build_inquiry_title( $payload ),
				'post_content' => $payload['requirement'],
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			self::redirect_after_inquiry( 'error', $source_url );
		}

		foreach ( self::get_inquiry_meta_fields() as $key => $field ) {
			if ( isset( $payload[ $key ] ) && '' !== $payload[ $key ] ) {
				update_post_meta( $post_id, self::OPTION_PREFIX . $key, $payload[ $key ] );
			}
		}

		update_post_meta( $post_id, self::OPTION_PREFIX . 'submitted_at', current_time( 'mysql' ) );

		$email_sent = self::dispatch_inquiry_email( $post_id, $payload );
		update_post_meta( $post_id, self::OPTION_PREFIX . 'email_status', $email_sent ? 'sent' : 'stored_only' );

		/**
		 * Fires after an inquiry has been stored.
		 *
		 * @param int   $post_id Inquiry post ID.
		 * @param array $payload Sanitized submission payload.
		 */
		do_action( 'yke_site_core_inquiry_saved', $post_id, $payload );

		self::redirect_after_inquiry( $email_sent ? 'success' : 'stored', $source_url );
	}

	/**
	 * Append case study summary details to the front-end content.
	 *
	 * @param string $content Current post content.
	 * @return string
	 */
	public static function append_case_study_details( $content ) {
		if ( is_admin() || ! is_singular( 'case_study' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return $content;
		}

		$payload = self::get_case_study_structured_payload( $post_id, self::get_current_site_language() );

		if ( empty( $payload['snapshot'] ) ) {
			return $content;
		}

		self::enqueue_style();

		return $content . self::build_case_study_detail_sections( $post_id, self::get_current_site_language(), $payload );
	}

	/**
	 * Replace selected seeded marketing pages with structured export-first content.
	 *
	 * @param string $content Existing page content.
	 * @return string
	 */
	public static function render_marketing_page_content( $content ) {
		if ( is_admin() || ! is_page() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post = get_post();
		if ( ! $post instanceof WP_Post ) {
			return $content;
		}

		$page_path       = self::normalize_seed_path_for_language( self::get_page_path( $post ) );
		$current_lang    = self::get_current_site_language();
		$is_chinese_page = ( 'zh' === $current_lang );

		/*
		 * Product model detail pages (products/{line}/{model}) are routed to the
		 * product line page post (see route_seeded_page_request), so get_page_path()
		 * returns the 2-segment line path. Fall back to the request path to detect
		 * the 3-segment model path and render the correct content.
		 */
		$request_path_norm = self::normalize_seed_path_for_language( self::get_request_path() );
		if ( 0 === strpos( $request_path_norm, 'products/' ) && substr_count( $request_path_norm, '/' ) >= 2 ) {
			$page_path = $request_path_norm;
		}

		if ( 0 === strpos( $page_path, 'products/' ) ) {
			self::enqueue_style();
			$path_parts = explode( '/', trim( $page_path, '/' ) );
			// 3-segment path: products / {line} / {model}
			if ( ! empty( $path_parts[2] ) ) {
				return self::build_model_detail_page_content( $path_parts[1], $path_parts[2], $is_chinese_page ? 'zh' : 'en' );
			}
			// 2-segment path: products / {line}
			if ( ! empty( $path_parts[1] ) ) {
				return self::build_rich_product_page_content( $path_parts[1], $is_chinese_page ? 'zh' : 'en' );
			}
		}

		switch ( $page_path ) {
			case 'products':
				self::enqueue_style();
				return self::build_products_overview_content( $is_chinese_page ? 'zh' : 'en' );
			case 'about-yke':
				self::enqueue_style();
				return self::build_visual_marketing_about_content( $is_chinese_page ? 'zh' : 'en' );
			case 'distributor-partnership':
				// 代理合作页已删除（PPT 无此内容），301 重定向到联系页
				wp_safe_redirect( self::get_seeded_page_url( 'contact', $is_chinese_page ? 'zh' : 'en' ), 301 );
				exit;
			case 'contact':
				self::enqueue_style();
				return self::build_visual_marketing_contact_content( $is_chinese_page ? 'zh' : 'en' );
			default:
				return $content;
		}
	}

	/**
	 * Render YKE shortcodes when block templates surface them as plain text.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $block         Parsed block data.
	 * @return string
	 */
	public static function render_theme_shortcodes( $block_content, $block ) {
		if ( false === strpos( $block_content, '[yke_' ) ) {
			return $block_content;
		}

		$block_name = isset( $block['blockName'] ) ? $block['blockName'] : '';
		if ( ! in_array( $block_name, array( 'core/paragraph', 'core/shortcode', 'core/html' ), true ) ) {
			return $block_content;
		}

		return do_shortcode( shortcode_unautop( $block_content ) );
	}

	/**
	 * Seed default settings when the option is missing.
	 */
	protected static function seed_default_settings() {
		foreach ( self::get_setting_fields() as $key => $field ) {
			$option_name = self::OPTION_PREFIX . $key;
			if ( false === get_option( $option_name, false ) ) {
				add_option( $option_name, $field['default'] );
			}
		}
	}

	/**
	 * Run all seed steps and record the synchronized version.
	 */
	protected static function run_seed_sync() {
		self::seed_default_settings();
		self::seed_taxonomy_terms();
		self::seed_core_pages();
		self::seed_case_studies();
		self::cleanup_deleted_model_pages();
		update_option( 'yke_site_core_seed_version', YKE_SITE_CORE_VERSION );
		flush_rewrite_rules();
	}

	protected static function cleanup_deleted_model_pages() {
		global $wpdb;

		$deleted_paths = array(
			'products/mv-switchgear/kyn61-40-5',
			'products/mv-switchgear/hcm',
			'products/mv-switchgear/hca',
			'products/mv-switchgear/hxgn-15',
			'products/lv-switchgear/gcs',
			'products/lv-switchgear/mns',
			'products/lv-switchgear/ggj',
			'products/lv-switchgear/md-max',
			'products/transformers/scb13',
			'products/transformers/s11',
			'products/transformers/s20',
			'products/transformers/d11',
			'products/prefabricated-substations/hua-style',
			'products/prefabricated-substations/new-energy-substation',
			'products/new-energy-electrical/busway',
		);

		$slugs = array();
		foreach ( $deleted_paths as $path ) {
			$slugs[] = basename( $path );
		}

		$placeholders = implode( ',', array_fill( 0, count( $slugs ), '%s' ) );
		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_name IN ({$placeholders})",
				$slugs
			)
		);

		if ( empty( $post_ids ) ) {
			return;
		}

		foreach ( $post_ids as $post_id ) {
			wp_delete_post( (int) $post_id, true );
		}
	}

	/**
	 * Seed base taxonomy terms.
	 */
	protected static function seed_taxonomy_terms() {
		$product_lines = array(
			'mv-switchgear'             => 'MV Switchgear',
			'lv-switchgear'             => 'LV Switchgear',
			'transformers'              => 'Transformers',
			'prefabricated-substations' => 'Prefabricated Substations',
			'new-energy-electrical'     => 'New Energy Electrical',
		);

		foreach ( $product_lines as $slug => $name ) {
			if ( ! term_exists( $slug, 'product_line' ) ) {
				wp_insert_term(
					$name,
					'product_line',
					array(
						'slug' => $slug,
					)
				);
			}
		}

		$regions = array(
			'middle-east'               => 'Middle East',
			'southeast-asia'            => 'Southeast Asia',
			'africa'                    => 'Africa',
			'latin-america-south-asia'  => 'Latin America & South Asia',
		);

		foreach ( $regions as $slug => $name ) {
			if ( ! term_exists( $slug, 'case_region' ) ) {
				wp_insert_term(
					$name,
					'case_region',
					array(
						'slug' => $slug,
					)
				);
			}
		}
	}

	/**
	 * Seed core pages used by the theme.
	 */
	protected static function seed_core_pages() {
		$contact_id = self::maybe_insert_page(
			array(
				'path'          => 'contact',
				'seed_key'      => 'page:contact:en',
				'title'         => 'Contact',
				'content'       => self::get_seed_contact_content(),
				'sync_existing' => true,
			)
		);

		$products_id = self::maybe_insert_page(
			array(
				'path'          => 'products',
				'seed_key'      => 'page:products:en',
				'title'         => 'Products',
				'content'       => self::get_seed_products_content(),
				'sync_existing' => true,
			)
		);

		self::maybe_insert_page(
			array(
				'path'          => 'about-yke',
				'seed_key'      => 'page:about-yke:en',
				'title'         => 'About YKE',
				'content'       => self::get_seed_about_content(),
				'sync_existing' => true,
			)
		);

		// distributor-partnership 页已删除（PPT 无此内容），301 重定向到 /contact/


		$zh_root_id = self::maybe_insert_page(
			array(
				'path'    => 'zh',
				'seed_key'=> 'page:home:zh',
				'title'   => '中文站',
				'content' => self::get_seed_zh_root_content(),
				'sync_existing' => true,
			)
		);

		if ( $zh_root_id ) {
			self::maybe_insert_page(
				array(
					'path'      => 'zh/about-yke',
					'seed_key'  => 'page:about-yke:zh',
					'title'     => '关于云科',
					'content'   => self::get_seed_zh_about_content(),
					'parent_id' => $zh_root_id,
					'sync_existing' => true,
				)
		);

		// zh/distributor-partnership 页已删除（PPT 无此内容）

		self::maybe_insert_page(
			array(
				'path'      => 'zh/contact',
					'seed_key'  => 'page:contact:zh',
					'title'     => '联系我们',
					'content'   => self::get_seed_zh_contact_content(),
					'parent_id' => $zh_root_id,
					'sync_existing' => true,
				)
			);

			$zh_products_id = self::maybe_insert_page(
				array(
					'path'      => 'zh/products',
					'seed_key'  => 'page:products:zh',
					'title'     => '产品总览',
					'content'   => self::get_seed_zh_products_content(),
					'parent_id' => $zh_root_id,
					'sync_existing' => true,
				)
			);

			self::maybe_insert_page(
				array(
					'path'      => 'zh/case-studies',
					'seed_key'  => 'page:case-studies:zh',
					'title'     => '项目案例',
					'content'   => self::build_zh_case_studies_page_content(),
					'parent_id' => $zh_root_id,
					'sync_existing' => true,
				)
			);

			if ( $zh_products_id ) {
				$zh_product_pages = array(
					array(
						'slug'    => 'mv-switchgear',
						'seed_key'=> 'page:products/mv-switchgear:zh',
						'title'   => '高压开关柜',
						'content' => self::get_seed_zh_product_page_content( '高压开关柜', '覆盖 12kV 至 40.5kV，适用于工业园区、变电站和新能源项目。' ),
					),
					array(
						'slug'    => 'lv-switchgear',
						'seed_key'=> 'page:products/lv-switchgear:zh',
						'title'   => '低压开关柜',
						'content' => self::get_seed_zh_product_page_content( '低压开关柜', '覆盖 400V 及以下，支持固定式、抽出式、补偿柜和控制柜方案。' ),
					),
					array(
						'slug'    => 'transformers',
						'seed_key'=> 'page:products/transformers:zh',
						'title'   => '变压器',
						'content' => self::get_seed_zh_product_page_content( '变压器', '覆盖干式与油浸式产品，适用于工业配电、城市配网与新能源升压。' ),
					),
					array(
						'slug'    => 'prefabricated-substations',
						'seed_key'=> 'page:products/prefabricated-substations:zh',
						'title'   => '预装式变电站',
						'content' => self::get_seed_zh_product_page_content( '预装式变电站', '适合出口远程工程项目快速部署，支持多种箱变结构与项目定制。' ),
					),
					array(
						'slug'    => 'new-energy-electrical',
						'seed_key'=> 'page:products/new-energy-electrical:zh',
						'title'   => '新能源电气',
						'content' => self::get_seed_zh_product_page_content( '新能源电气', '覆盖光伏并网、储能配电、充电站升压和预制舱等方案。' ),
					),
				);

				foreach ( $zh_product_pages as $zh_page ) {
					self::maybe_insert_page(
						array(
							'path'      => 'zh/products/' . $zh_page['slug'],
							'seed_key'  => $zh_page['seed_key'],
							'title'     => $zh_page['title'],
							'content'   => $zh_page['content'],
							'parent_id' => $zh_products_id,
							'sync_existing' => true,
						)
					);
				}
			}
		}

		$product_pages = array(
			array(
				'slug'     => 'mv-switchgear',
				'title'    => 'MV Switchgear',
				'term'     => 'mv-switchgear',
				'seed_key' => 'page:products/mv-switchgear:en',
				'content'  => self::build_rich_product_page_content( 'mv-switchgear' ),
				'parent_id' => $products_id,
			),
			array(
				'slug'     => 'lv-switchgear',
				'title'    => 'LV Switchgear',
				'term'     => 'lv-switchgear',
				'seed_key' => 'page:products/lv-switchgear:en',
				'content'  => self::build_rich_product_page_content( 'lv-switchgear' ),
				'parent_id' => $products_id,
			),
			array(
				'slug'     => 'transformers',
				'title'    => 'Transformers',
				'term'     => 'transformers',
				'seed_key' => 'page:products/transformers:en',
				'content'  => self::build_rich_product_page_content( 'transformers' ),
				'parent_id' => $products_id,
			),
			array(
				'slug'     => 'prefabricated-substations',
				'title'    => 'Prefabricated Substations',
				'term'     => 'prefabricated-substations',
				'seed_key' => 'page:products/prefabricated-substations:en',
				'content'  => self::build_rich_product_page_content( 'prefabricated-substations' ),
				'parent_id' => $products_id,
			),
			array(
				'slug'     => 'new-energy-electrical',
				'title'    => 'New Energy Electrical',
				'term'     => 'new-energy-electrical',
				'seed_key' => 'page:products/new-energy-electrical:en',
				'content'  => self::build_rich_product_page_content( 'new-energy-electrical' ),
				'parent_id' => $products_id,
			),
		);

		foreach ( $product_pages as $page ) {
			$page_id = self::maybe_insert_page(
				array(
					'path'          => 'products/' . $page['slug'],
					'seed_key'      => $page['seed_key'],
					'title'         => $page['title'],
					'content'       => $page['content'],
					'parent_id'     => $page['parent_id'],
					'sync_existing' => true,
				)
			);

			if ( $page_id && $page['term'] ) {
				wp_set_object_terms( $page_id, array( $page['term'] ), 'product_line', false );
			}
		}

		if ( $contact_id ) {
			update_post_meta( $contact_id, '_yke_site_core_seeded', '1' );
		}
	}

	/**
	 * Seed starter case studies when matching posts are missing.
	 */
	protected static function seed_case_studies() {
		self::retire_legacy_seed_case_studies();

		$entries = self::get_seed_case_study_entries();

		foreach ( $entries as $entry ) {
			$en_post_id = self::upsert_seed_case_study( $entry, 'en' );
			$zh_post_id = self::upsert_seed_case_study( $entry, 'zh' );

			if ( $en_post_id && $zh_post_id && function_exists( 'pll_save_post_translations' ) ) {
				pll_save_post_translations(
					array(
						'en' => $en_post_id,
						'zh' => $zh_post_id,
					)
				);
			}
		}
	}

	/**
	 * Retire old seeded case studies from earlier placeholder batches.
	 */
	protected static function retire_legacy_seed_case_studies() {
		$legacy_slugs = array(
			'kenya-industrial-park-switchgear-package',
			'saudi-solar-service-substation-package',
			'peru-food-processing-transformer-upgrade',
		);

		foreach ( $legacy_slugs as $slug ) {
			$post = self::find_post_by_path( $slug, 'case_study' );
			if ( ! $post ) {
				continue;
			}

			if ( '1' !== (string) get_post_meta( $post->ID, '_yke_site_core_seeded', true ) ) {
				continue;
			}

			wp_trash_post( $post->ID );
		}
	}

	/**
	 * Get bilingual seeded case-study definitions.
	 *
	 * @return array
	 */
	protected static function get_seed_case_study_entries() {
		return array(
			array(
				'key'           => 'saudi-industrial-park',
				'region'        => 'middle-east',
				'product_lines' => array( 'mv-switchgear', 'transformers' ),
				'en'            => array(
					'slug'    => 'saudi-arabia-industrial-park-power-distribution-project',
					'title'   => 'Saudi Arabia Industrial Park Power Distribution Project',
					'excerpt' => '32 panels of KYN28-12 medium-voltage switchgear and 8 SCB11 dry-type transformers delivered in 38 days for a Saudi industrial park EPC contractor.',
					'content' => self::build_seed_case_content(
						'YKE supplied a Saudi industrial park EPC contractor with a medium-voltage switchgear and dry-type transformer package tailored for harsh high-temperature and dusty conditions.',
						'The package was completed within a 38-day lead time and passed full SGS inspection before shipment.'
					),
					'meta'    => array(
						'country'          => 'Saudi Arabia',
						'client_type'      => 'EPC contractor',
						'project_capacity' => 'Industrial park distribution package',
						'application'      => 'Industrial park medium-voltage distribution',
						'supply_scope'     => '32 units of KYN28-12 medium-voltage switchgear and 8 units of SCB11 dry-type transformers',
						'key_specs'        => "12kV switchgear package\n32 panels + 8 transformers\n38-day delivery window",
						'technical_focus'  => 'IP65+ dust protection and ambient temperature >=50°C under SASO-oriented project requirements',
						'standards'        => 'IEC aligned with Saudi project review requirements',
						'delivery_result'  => '38-day lead time and successful SGS full-item inspection before shipment',
						'nda_note'         => 'Buyer reference details available after NDA',
					),
				),
				'zh'            => array(
					'slug'    => 'sha-te-gong-ye-yuan-qu-pei-dian-xiang-mu',
				'title'   => '沙特阿拉伯工业园区配电项目',
					'excerpt' => '为沙特工业园区 EPC 承包商提供 32 面 KYN28-12 高压开关柜和 8 台 SCB11 干式变压器，38 天完成交付。',
					'content' => self::build_seed_case_content(
						'云科为沙特工业园区 EPC 承包商配套了高压开关柜与干式变压器方案，重点适配高温、多尘等海外工况。',
						'项目按 38 天交期完成排产，出运前通过 SGS 全项检验。'
					),
					'meta'    => array(
						'country'          => '沙特阿拉伯',
						'client_type'      => 'EPC 承包商',
						'project_capacity' => '工业园区配电成套项目',
						'application'      => '工业园区中压配电系统',
						'supply_scope'     => '32 面 KYN28-12 高压开关柜和 8 台 SCB11 干式变压器',
						'key_specs'        => "12kV 配电成套\n32 面开关柜 + 8 台变压器\n38 天完成交付",
						'technical_focus'  => '针对 SASO 审核要求配置 IP65+ 防尘能力，并适配 50°C 以上高温环境',
						'standards'        => 'IEC 体系并满足沙特项目审查要求',
						'delivery_result'  => '38 天交期，并在出运前完成 SGS 全项检验',
						'nda_note'         => '买方详细信息可在签署 NDA 后提供',
					),
				),
			),
			array(
				'key'           => 'vietnam-factory-upgrade',
				'region'        => 'southeast-asia',
				'product_lines' => array( 'lv-switchgear' ),
				'en'            => array(
					'slug'    => 'vietnam-manufacturing-group-factory-distribution-upgrade',
					'title'   => 'Vietnam Manufacturing Group Factory Distribution Upgrade',
					'excerpt' => 'GGD low-voltage switchgear and dense busbar systems delivered in staged batches to support a factory relocation schedule.',
					'content' => self::build_seed_case_content(
						'YKE supported a manufacturing group in Vietnam with staged low-voltage switchgear delivery tied to production line relocation timing.',
						'Shipments were split into two batches with Vietnamese installation manuals and remote commissioning support, leading to three-plus years of repeat orders.'
					),
					'meta'    => array(
						'country'          => 'Vietnam',
						'client_type'      => 'Manufacturing group',
						'project_capacity' => 'Factory distribution renovation',
						'application'      => 'Factory relocation and LV distribution upgrade',
						'supply_scope'     => '60 units of GGD low-voltage switchgear with dense busbar trunking systems',
						'key_specs'        => "GGD low-voltage switchgear\n60 cabinets in two shipment batches\nDense busbar trunking included",
						'technical_focus'  => 'Two-batch shipment planning, Vietnamese installation guidance, and remote commissioning support',
						'standards'        => 'IEC aligned with project-specific factory requirements',
						'delivery_result'  => 'Approximate project value of USD 380,000 and three years of repeat cooperation',
						'nda_note'         => 'Reference details shared with qualified buyers after NDA',
					),
				),
				'zh'            => array(
					'slug'    => 'yue-nan-gong-han-pei-dian-gai-zao-xiang-mu',
				'title'   => '越南制造业集团工厂配电改造项目',
					'excerpt' => '分批交付 GGD 低压开关柜和密集型母线系统，配合越南客户工厂搬迁与投产节奏。',
					'content' => self::build_seed_case_content(
						'云科为越南制造业客户提供分批次低压开关柜交付方案，交付节奏与生产线搬迁节点同步。',
						'项目分两批发运，提供越文安装手册与远程调试支持，后续形成三年以上重复合作。'
					),
					'meta'    => array(
						'country'          => '越南',
						'client_type'      => '制造业集团',
						'project_capacity' => '工厂配电改造项目',
						'application'      => '工厂搬迁与低压配电升级',
						'supply_scope'     => '60 台 GGD 低压开关柜及配套密集型母线槽系统',
						'key_specs'        => "GGD 低压开关柜\n60 台分两批发运\n配套密集型母线槽系统",
						'technical_focus'  => '两批次发运计划、越南现场安装指导和远程调试配合',
						'standards'        => 'IEC 体系并按项目工厂要求执行',
						'delivery_result'  => '项目金额约 38 万美元，并形成三年以上持续合作',
						'nda_note'         => '详细客户信息可向合格买家在 NDA 后披露',
					),
				),
			),
			array(
				'key'           => 'nigeria-grid-upgrade',
				'region'        => 'africa',
				'product_lines' => array( 'transformers' ),
				'en'            => array(
					'slug'    => 'nigeria-grid-upgrade-transformer-supply',
					'title'   => 'Nigeria Grid Upgrade Transformer Supply',
					'excerpt' => '20 units of S13 oil-immersed transformers supplied through an exclusive local agent with zero quality complaints and smooth L/C settlement.',
					'content' => self::build_seed_case_content(
						'YKE supplied an oil-immersed transformer package for grid-upgrade work in Nigeria through an exclusive local distribution partner.',
						'The transformers arrived on schedule with zero quality complaints, and full L/C settlement was completed through the local partner.'
					),
					'meta'    => array(
						'country'          => 'Nigeria',
						'client_type'      => 'Local utility via exclusive distributor',
						'project_capacity' => 'Grid upgrade distribution package',
						'application'      => 'Utility replacement and grid upgrade',
						'supply_scope'     => '20 units of S13 oil-immersed transformers covering 500kVA to 1600kVA',
						'key_specs'        => "500kVA to 1600kVA range\n20 oil-immersed transformers\nL/C settlement through local partner",
						'technical_focus'  => 'Cost-performance balance, export durability, and utility replacement suitability',
						'standards'        => 'IEC aligned manufacturing with export inspection support',
						'delivery_result'  => 'On-time arrival, zero complaints, and full L/C settlement with a strategic partner',
						'nda_note'         => 'Agent details shared only under NDA',
					),
				),
				'zh'            => array(
					'slug'    => 'ni-ri-li-ya-dian-wang-sheng-ji-xiang-mu',
				'title'   => '尼日利亚电网升级变压器供货项目',
					'excerpt' => '通过当地独家代理商供货 20 台 S13 油浸式变压器，实现零质量投诉并顺利完成信用证结算。',
					'content' => self::build_seed_case_content(
						'云科通过当地独家渠道伙伴，为尼日利亚电网升级项目提供油浸式变压器成套供货。',
						'项目按期到港，零质量投诉，并通过本地合作伙伴完成信用证全额结算。'
					),
					'meta'    => array(
						'country'          => '尼日利亚',
						'client_type'      => '经独家代理对接的本地电力客户',
						'project_capacity' => '电网升级配电项目',
						'application'      => '公用事业替换与电网升级',
						'supply_scope'     => '20 台 S13 油浸式变压器，容量覆盖 500kVA 至 1600kVA',
						'key_specs'        => "500kVA 至 1600kVA\n20 台油浸式变压器\n经本地合作伙伴信用证结算",
						'technical_focus'  => '兼顾成本性能、出口耐用性和公用事业替换场景适配',
						'standards'        => 'IEC 体系制造并支持出口验货',
						'delivery_result'  => '按期到港、零投诉，并与战略伙伴完成信用证全额结算',
						'nda_note'         => '代理商信息仅在 NDA 后披露',
					),
				),
			),
			array(
				'key'           => 'indonesia-rooftop-solar',
				'region'        => 'southeast-asia',
				'product_lines' => array( 'prefabricated-substations', 'new-energy-electrical' ),
				'en'            => array(
					'slug'    => 'indonesia-50mw-rooftop-solar-grid-connection-package',
					'title'   => 'Indonesia 50MW Rooftop Solar Grid-Connection Package',
					'excerpt' => 'Solar grid-connection cabinets and step-up substations delivered for a 50MW rooftop solar project with PLN approval support.',
					'content' => self::build_seed_case_content(
						'YKE delivered solar grid-connection cabinets and step-up substations for a 50MW rooftop solar project in Indonesia.',
						'YKE prepared the PLN approval documentation package, supporting one-pass approval and successful grid connection.'
					),
					'meta'    => array(
						'country'          => 'Indonesia',
						'client_type'      => 'Local new energy developer',
						'project_capacity' => '50MW rooftop solar project',
						'application'      => 'Solar grid-connection and step-up distribution',
						'supply_scope'     => '12 sets of solar grid-connection cabinets and 12 sets of step-up prefabricated substations',
						'key_specs'        => "50MW rooftop solar\n12 grid-connection cabinets\n12 step-up prefabricated substations",
						'technical_focus'  => 'PLN-oriented documentation package and SNI-aligned approval support',
						'standards'        => 'IEC aligned with Indonesian grid-connection approval requirements',
						'delivery_result'  => 'Supported one-pass PLN approval and successful project grid connection',
						'nda_note'         => 'Project reference details shared after NDA',
					),
				),
				'zh'            => array(
					'slug'    => 'yin-ni-50mw-wu-ding-guang-fu-bing-wang-xiang-mu',
				'title'   => '印度尼西亚50MW屋顶光伏并网成套项目',
					'excerpt' => '为印尼 50MW 屋顶光伏项目提供并网柜和升压箱变，并配合 PLN 审批资料准备。',
					'content' => self::build_seed_case_content(
						'云科为印尼 50MW 屋顶光伏项目提供并网柜与升压预装式变电站，覆盖新能源项目关键电气配套。',
						'云科配合准备 PLN 审批所需技术文件，支持项目一次性通过审批并完成并网。'
					),
					'meta'    => array(
						'country'          => '印度尼西亚',
						'client_type'      => '本地新能源开发商',
						'project_capacity' => '50MW 屋顶光伏项目',
						'application'      => '光伏并网与升压配电系统',
						'supply_scope'     => '12 套光伏并网柜和 12 套升压预装式变电站',
						'key_specs'        => "50MW 屋顶光伏\n12 套并网柜\n12 套升压预装式变电站",
						'technical_focus'  => '围绕 PLN 审批要求整理技术文件，并支持 SNI 审批配合',
						'standards'        => 'IEC 体系并满足印尼并网审批要求',
						'delivery_result'  => '支持 PLN 一次性审批通过并实现顺利并网',
						'nda_note'         => '项目详细信息可在 NDA 后提供',
					),
				),
			),
		);
	}

	/**
	 * Insert or update one seeded case study in a target language.
	 *
	 * @param array  $entry Case-study definition.
	 * @param string $lang  Language slug.
	 * @return int
	 */
	protected static function upsert_seed_case_study( $entry, $lang ) {
		if ( empty( $entry[ $lang ] ) || ! is_array( $entry[ $lang ] ) ) {
			return 0;
		}

		$data     = $entry[ $lang ];
		$seed_key = 'case:' . $entry['key'] . ':' . $lang;
		$existing = self::find_seeded_post( $seed_key, 'case_study', $data['slug'] );

		if ( $existing && '1' !== (string) get_post_meta( $existing->ID, '_yke_site_core_seeded', true ) ) {
			return 0;
		}

		$post_args = array(
			'post_type'    => 'case_study',
			'post_status'  => 'publish',
			'post_title'   => $data['title'],
			'post_name'    => $data['slug'],
			'post_excerpt' => $data['excerpt'],
			'post_content' => $data['content'],
		);

		if ( $existing ) {
			$post_args['ID'] = $existing->ID;
			$post_id         = wp_update_post( $post_args, true );
		} else {
			$post_id = wp_insert_post( $post_args, true );
		}

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 0;
		}

		if ( function_exists( 'pll_set_post_language' ) ) {
			pll_set_post_language( $post_id, $lang );
		}

		wp_set_object_terms( $post_id, array( $entry['region'] ), 'case_region', false );
		wp_set_object_terms( $post_id, $entry['product_lines'], 'product_line', false );

		foreach ( $data['meta'] as $key => $value ) {
			update_post_meta( $post_id, self::OPTION_PREFIX . $key, $value );
		}

		update_post_meta( $post_id, '_yke_site_core_seeded', '1' );
		update_post_meta( $post_id, self::SEED_KEY_META, $seed_key );

		return (int) $post_id;
	}

	/**
	 * Create a page when its target path is unused.
	 *
	 * @param array $page Page definition.
	 * @return int
	 */
	protected static function maybe_insert_page( $page ) {
		$seed_key = ! empty( $page['seed_key'] ) ? (string) $page['seed_key'] : (string) $page['path'];
		$existing = self::find_seeded_page( $seed_key, $page['path'] );
		if ( $existing ) {
			if ( ! empty( $page['sync_existing'] ) && '1' === (string) get_post_meta( $existing->ID, '_yke_site_core_seeded', true ) ) {
				wp_update_post(
					array(
						'ID'           => $existing->ID,
						'post_title'   => $page['title'],
						'post_parent'  => ! empty( $page['parent_id'] ) ? (int) $page['parent_id'] : 0,
						'post_content' => $page['content'],
					)
				);
			}

			return (int) $existing->ID;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $page['title'],
				'post_name'    => basename( $page['path'] ),
				'post_parent'  => ! empty( $page['parent_id'] ) ? (int) $page['parent_id'] : 0,
				'post_content' => $page['content'],
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 0;
		}

		update_post_meta( $post_id, '_yke_site_core_seeded', '1' );
		update_post_meta( $post_id, self::SEED_KEY_META, $seed_key );

		return (int) $post_id;
	}

	/**
	 * Dispatch stored inquiry via wp_mail with fallbacks.
	 *
	 * @param int   $post_id Inquiry post ID.
	 * @param array $payload Submission payload.
	 * @return bool
	 */
	protected static function dispatch_inquiry_email( $post_id, $payload ) {
		$recipients = self::get_inquiry_recipients();
		if ( empty( $recipients ) ) {
			return false;
		}

		$subject_prefix = self::get_setting( 'inquiry_email_subject_prefix' );
		$subject        = trim( $subject_prefix ? $subject_prefix : 'New Website Inquiry' );
		$subject       .= ': ' . $payload['product_interest'];

		$lines   = array();
		$lines[] = 'A new inquiry has been submitted on the website.';
		$lines[] = '';
		$lines[] = 'Name: ' . $payload['name'];
		$lines[] = 'Company: ' . $payload['company'];
		$lines[] = 'Country / Market: ' . $payload['country'];
		$lines[] = 'Email: ' . $payload['email'];
		$lines[] = 'WhatsApp / Phone: ' . $payload['whatsapp'];
		$lines[] = 'Product Interest: ' . $payload['product_interest'];
		$lines[] = 'Source Page: ' . $payload['source_title'];
		$lines[] = 'Source URL: ' . $payload['source_url'];
		$lines[] = '';
		$lines[] = 'Requirement:';
		$lines[] = $payload['requirement'];
		$lines[] = '';
		$lines[] = 'Admin Record: ' . admin_url( 'post.php?post=' . $post_id . '&action=edit' );

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		if ( $payload['email'] ) {
			$reply_to = $payload['email'];
			if ( $payload['name'] ) {
				$reply_to = sanitize_text_field( $payload['name'] ) . ' <' . $payload['email'] . '>';
			}
			$headers[] = 'Reply-To: ' . $reply_to;
		}

		$sent = wp_mail( $recipients, $subject, implode( "\n", $lines ), $headers );

		if ( $sent ) {
			update_post_meta( $post_id, self::OPTION_PREFIX . 'notification_sent_at', current_time( 'mysql' ) );
		}

		return (bool) $sent;
	}

	/**
	 * Redirect after inquiry submission.
	 *
	 * @param string $status     Result status.
	 * @param string $source_url Requested return URL.
	 */
	protected static function redirect_after_inquiry( $status, $source_url ) {
		$redirect = $source_url ? $source_url : wp_get_referer();
		$redirect = $redirect ? $redirect : home_url( '/' );
		$redirect = wp_validate_redirect( $redirect, home_url( '/' ) );
		$redirect = remove_query_arg( 'yke_inquiry_status', $redirect );
		$redirect = add_query_arg( 'yke_inquiry_status', sanitize_key( $status ), $redirect );

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Collect and sanitize inquiry payload.
	 *
	 * @return array
	 */
	protected static function sanitize_inquiry_payload() {
		$payload = array(
			'name'             => self::get_posted_value( 'name' ),
			'company'          => self::get_posted_value( 'company' ),
			'country'          => self::get_posted_value( 'country' ),
			'email'            => self::get_posted_value( 'email', 'sanitize_email' ),
			'whatsapp'         => self::get_posted_value( 'whatsapp' ),
			'product_interest' => self::get_posted_value( 'product_interest' ),
			'requirement'      => self::get_posted_value( 'requirement', 'sanitize_textarea_field' ),
			'source_url'       => self::get_posted_value( 'source_url', 'esc_url_raw' ),
			'source_title'     => self::get_posted_value( 'source_title' ),
			'email_status'     => '',
			'website'          => self::get_posted_value( 'website' ),
		);

		if ( '' === $payload['product_interest'] ) {
			$payload['product_interest'] = 'General Inquiry';
		}

		return $payload;
	}

	/**
	 * Validate inquiry payload.
	 *
	 * @param array $payload Submission payload.
	 * @return bool
	 */
	protected static function has_inquiry_validation_error( $payload ) {
		if ( ! empty( $payload['website'] ) ) {
			return true;
		}

		if ( '' === $payload['name'] || '' === $payload['email'] || '' === $payload['requirement'] ) {
			return true;
		}

		if ( ! is_email( $payload['email'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Format inquiry meta values for admin.
	 *
	 * @param string $key   Meta key.
	 * @param string $value Meta value.
	 * @return string
	 */
	protected static function format_inquiry_meta_value( $key, $value ) {
		if ( 'email' === $key ) {
			return sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( 'mailto:' . $value ),
				esc_html( $value )
			);
		}

		if ( 'whatsapp' === $key ) {
			return sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( self::build_whatsapp_link( $value ) ),
				esc_html( $value )
			);
		}

		if ( 'source_url' === $key ) {
			return sprintf(
				'<a href="%1$s" target="_blank" rel="noopener">%2$s</a>',
				esc_url( $value ),
				esc_html( $value )
			);
		}

		if ( false !== strpos( $value, "\n" ) ) {
			return nl2br( esc_html( $value ) );
		}

		return esc_html( $value );
	}

	/**
	 * Build a readable inquiry admin title.
	 *
	 * @param array $payload Submission payload.
	 * @return string
	 */
	protected static function build_inquiry_title( $payload ) {
		$parts = array_filter(
			array(
				$payload['name'],
				$payload['company'],
				$payload['product_interest'],
			)
		);

		$title = implode( ' - ', $parts );
		if ( '' === $title ) {
			$title = 'Inquiry ' . current_time( 'mysql' );
		}

		return wp_strip_all_tags( $title );
	}

	/**
	 * Lookup inquiry recipients using settings fallback chain.
	 *
	 * @return array
	 */
	protected static function get_inquiry_recipients() {
		$raw = self::get_setting( 'inquiry_recipient_email' );
		if ( '' === $raw ) {
			$raw = self::get_setting( 'contact_email' );
		}

		if ( '' === $raw ) {
			$raw = get_option( 'admin_email', '' );
		}

		$emails = preg_split( '/[\s,;]+/', $raw );
		$emails = array_filter( array_map( 'sanitize_email', (array) $emails ) );

		return array_values( array_unique( $emails ) );
	}

	/**
	 * Get a settings field definition list.
	 *
	 * @return array
	 */
	protected static function get_setting_fields() {
		return array(
			'company_name' => array(
				'label'       => __( 'Company Name', 'yke-site-core' ),
				'default'     => 'Guangzhou Yunke Electrical Equipment Co., Ltd.',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
			),
			'brand_name' => array(
				'label'       => __( 'Brand Name', 'yke-site-core' ),
				'default'     => 'YKE',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
			),
			'contact_name' => array(
				'label'       => __( 'Contact Name', 'yke-site-core' ),
				'default'     => 'Export Sales Manager',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
			),
			'contact_email' => array(
				'label'       => __( 'Contact Email', 'yke-site-core' ),
				'default'     => 'export@yunke-electric.com',
				'input'       => 'email',
				'sanitize'    => 'sanitize_email',
			),
			'contact_phone' => array(
				'label'       => __( 'Contact Phone', 'yke-site-core' ),
				'default'     => '+86-XXX-XXXX-XXXX',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
			),
			'contact_whatsapp' => array(
				'label'       => __( 'WhatsApp', 'yke-site-core' ),
				'default'     => '+86-XXX-XXXX-XXXX',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
			),
			'contact_address' => array(
				'label'       => __( 'Address', 'yke-site-core' ),
				'default'     => "Baiyun District, Guangzhou, China\nPostcode pending final confirmation",
				'input'       => 'textarea',
				'rows'        => 3,
				'sanitize'    => 'sanitize_textarea_field',
			),
			'business_hours' => array(
				'label'       => __( 'Business Hours', 'yke-site-core' ),
				'default'     => "Monday to Friday\n09:00 to 18:00 China Standard Time",
				'input'       => 'textarea',
				'rows'        => 3,
				'sanitize'    => 'sanitize_textarea_field',
			),
			'inquiry_recipient_email' => array(
				'label'       => __( 'Inquiry Notification Email', 'yke-site-core' ),
				'default'     => 'export@yunke-electric.com',
				'input'       => 'text',
				'sanitize'    => array( __CLASS__, 'sanitize_email_list' ),
				'description' => __( 'Comma-separated list. Falls back to Contact Email and then WordPress admin email.', 'yke-site-core' ),
			),
			'inquiry_email_subject_prefix' => array(
				'label'       => __( 'Inquiry Email Subject Prefix', 'yke-site-core' ),
				'default'     => 'New Website Inquiry',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
			),
			'default_cta' => array(
				'label'       => __( 'Default CTA Label', 'yke-site-core' ),
				'default'     => 'Send Inquiry',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
			),
			'footer_commitments' => array(
				'label'       => __( 'Footer Commitments', 'yke-site-core' ),
				'default'     => "Quotation and matched proposal on business days\n18-month standard warranty\nThird-party inspection welcomed\nIEC / GB aligned documentation",
				'input'       => 'textarea',
				'rows'        => 4,
				'sanitize'    => 'sanitize_textarea_field',
			),
			'certification_summary' => array(
				'label'       => __( 'Certification Summary', 'yke-site-core' ),
				'default'     => 'IEC-aligned production with SGS, BV, and Intertek third-party inspection support',
				'input'       => 'textarea',
				'rows'        => 2,
				'sanitize'    => 'sanitize_textarea_field',
			),
			'smtp_from_email' => array(
				'label'       => __( 'SMTP From Email', 'yke-site-core' ),
				'default'     => 'export@yunke-electric.com',
				'input'       => 'email',
				'sanitize'    => 'sanitize_email',
				'description' => __( 'Mapped into WP Mail SMTP when the plugin is active.', 'yke-site-core' ),
			),
			'smtp_from_name' => array(
				'label'       => __( 'SMTP From Name', 'yke-site-core' ),
				'default'     => 'YKE Global',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
			),
			'smtp_mailer' => array(
				'label'       => __( 'SMTP Mailer', 'yke-site-core' ),
				'default'     => 'smtp',
				'input'       => 'text',
				'sanitize'    => 'sanitize_key',
				'description' => __( 'Use "smtp" for host/port login, or swap to a supported WP Mail SMTP mailer slug later.', 'yke-site-core' ),
			),
			'smtp_host' => array(
				'label'       => __( 'SMTP Host', 'yke-site-core' ),
				'default'     => '',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
			),
			'smtp_port' => array(
				'label'       => __( 'SMTP Port', 'yke-site-core' ),
				'default'     => '587',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
			),
			'smtp_encryption' => array(
				'label'       => __( 'SMTP Encryption', 'yke-site-core' ),
				'default'     => 'tls',
				'input'       => 'text',
				'sanitize'    => 'sanitize_key',
				'description' => __( 'Supported values: none, ssl, tls.', 'yke-site-core' ),
			),
			'smtp_autotls' => array(
				'label'       => __( 'SMTP AutoTLS', 'yke-site-core' ),
				'default'     => '1',
				'input'       => 'text',
				'sanitize'    => array( __CLASS__, 'sanitize_bool_string' ),
			),
			'smtp_auth' => array(
				'label'       => __( 'SMTP Authentication', 'yke-site-core' ),
				'default'     => '1',
				'input'       => 'text',
				'sanitize'    => array( __CLASS__, 'sanitize_bool_string' ),
			),
			'smtp_user' => array(
				'label'       => __( 'SMTP Username', 'yke-site-core' ),
				'default'     => '',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
			),
			'smtp_pass' => array(
				'label'       => __( 'SMTP Password / App Password', 'yke-site-core' ),
				'default'     => '',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
				'description' => __( 'Store the real secret here only for local/provisional setup. Production should move to WP Mail SMTP constants when available.', 'yke-site-core' ),
			),
			'cf7_turnstile_sitekey' => array(
				'label'       => __( 'CF7 Turnstile Site Key', 'yke-site-core' ),
				'default'     => '',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
				'description' => __( 'If provided together with the secret, Contact Form 7 will enable Cloudflare Turnstile globally.', 'yke-site-core' ),
			),
			'cf7_turnstile_secret' => array(
				'label'       => __( 'CF7 Turnstile Secret Key', 'yke-site-core' ),
				'default'     => '',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
			),
			'cf7_recaptcha_sitekey' => array(
				'label'       => __( 'CF7 reCAPTCHA Site Key', 'yke-site-core' ),
				'default'     => '',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
				'description' => __( 'Used only if Turnstile is not configured. Contact Form 7 expects reCAPTCHA v3 credentials.', 'yke-site-core' ),
			),
			'cf7_recaptcha_secret' => array(
				'label'       => __( 'CF7 reCAPTCHA Secret Key', 'yke-site-core' ),
				'default'     => '',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
			),
			'google_site_verification' => array(
				'label'       => __( 'Google Site Verification', 'yke-site-core' ),
				'default'     => '',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
			),
			'bing_site_verification' => array(
				'label'       => __( 'Bing Site Verification', 'yke-site-core' ),
				'default'     => '',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
			),
			'ga_measurement_id' => array(
				'label'       => __( 'Google Analytics Measurement ID', 'yke-site-core' ),
				'default'     => '',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
			),
			'gtm_container_id' => array(
				'label'       => __( 'Google Tag Manager Container ID', 'yke-site-core' ),
				'default'     => '',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
			),
			'meta_pixel_id' => array(
				'label'       => __( 'Meta Pixel ID', 'yke-site-core' ),
				'default'     => '',
				'input'       => 'text',
				'sanitize'    => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Sanitize boolean-like values as 1/0 strings for text inputs.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function sanitize_bool_string( $value ) {
		return self::is_truthy( $value ) ? '1' : '0';
	}

	/**
	 * Get case study meta field definitions.
	 *
	 * @return array
	 */
	protected static function get_case_study_meta_fields() {
		return array(
			'country' => array(
				'label'    => __( 'Country', 'yke-site-core' ),
				'input'    => 'text',
				'sanitize' => 'sanitize_text_field',
			),
			'client_type' => array(
				'label'    => __( 'Client Type', 'yke-site-core' ),
				'input'    => 'text',
				'sanitize' => 'sanitize_text_field',
			),
			'project_capacity' => array(
				'label'    => __( 'Project Capacity', 'yke-site-core' ),
				'input'    => 'text',
				'sanitize' => 'sanitize_text_field',
			),
			'application' => array(
				'label'    => __( 'Application / Industry', 'yke-site-core' ),
				'input'    => 'text',
				'sanitize' => 'sanitize_text_field',
			),
			'supply_scope' => array(
				'label'    => __( 'Supply Scope', 'yke-site-core' ),
				'input'    => 'textarea',
				'sanitize' => 'sanitize_textarea_field',
			),
			'key_specs' => array(
				'label'    => __( 'Key Specs / Quantity', 'yke-site-core' ),
				'input'    => 'textarea',
				'sanitize' => 'sanitize_textarea_field',
			),
			'technical_focus' => array(
				'label'    => __( 'Technical Focus', 'yke-site-core' ),
				'input'    => 'textarea',
				'sanitize' => 'sanitize_textarea_field',
			),
			'standards' => array(
				'label'    => __( 'Standards', 'yke-site-core' ),
				'input'    => 'text',
				'sanitize' => 'sanitize_text_field',
			),
			'delivery_result' => array(
				'label'    => __( 'Delivery Result', 'yke-site-core' ),
				'input'    => 'textarea',
				'sanitize' => 'sanitize_textarea_field',
			),
			'nda_note' => array(
				'label'    => __( 'NDA Note', 'yke-site-core' ),
				'input'    => 'textarea',
				'sanitize' => 'sanitize_textarea_field',
			),
		);
	}

	/**
	 * Get inquiry meta field definitions.
	 *
	 * @return array
	 */
	protected static function get_inquiry_meta_fields() {
		return array(
			'name' => array(
				'label'    => __( 'Name', 'yke-site-core' ),
				'sanitize' => 'sanitize_text_field',
			),
			'company' => array(
				'label'    => __( 'Company', 'yke-site-core' ),
				'sanitize' => 'sanitize_text_field',
			),
			'country' => array(
				'label'    => __( 'Country / Market', 'yke-site-core' ),
				'sanitize' => 'sanitize_text_field',
			),
			'email' => array(
				'label'    => __( 'Email', 'yke-site-core' ),
				'sanitize' => 'sanitize_email',
			),
			'whatsapp' => array(
				'label'    => __( 'WhatsApp / Phone', 'yke-site-core' ),
				'sanitize' => 'sanitize_text_field',
			),
			'product_interest' => array(
				'label'    => __( 'Product Interest', 'yke-site-core' ),
				'sanitize' => 'sanitize_text_field',
			),
			'requirement' => array(
				'label'    => __( 'Project Requirement', 'yke-site-core' ),
				'sanitize' => 'sanitize_textarea_field',
			),
			'source_title' => array(
				'label'    => __( 'Source Page Title', 'yke-site-core' ),
				'sanitize' => 'sanitize_text_field',
			),
			'source_url' => array(
				'label'    => __( 'Source URL', 'yke-site-core' ),
				'sanitize' => 'esc_url_raw',
			),
			'submitted_at' => array(
				'label'    => __( 'Submitted At', 'yke-site-core' ),
				'sanitize' => 'sanitize_text_field',
			),
			'email_status' => array(
				'label'    => __( 'Email Status', 'yke-site-core' ),
				'sanitize' => 'sanitize_text_field',
			),
			'notification_sent_at' => array(
				'label'    => __( 'Notification Sent At', 'yke-site-core' ),
				'sanitize' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Get a plugin setting with default fallback.
	 *
	 * @param string $key Setting key without prefix.
	 * @return string
	 */
	protected static function get_setting( $key ) {
		$fields  = self::get_setting_fields();
		$default = isset( $fields[ $key ]['default'] ) ? $fields[ $key ]['default'] : '';

		return (string) get_option( self::OPTION_PREFIX . $key, $default );
	}

	/**
	 * Get a contact-facing setting value with export-site fallbacks.
	 *
	 * @param string $key Setting key.
	 * @return string
	 */
	protected static function get_contact_setting_value( $key ) {
		$value     = self::get_setting( $key );
		$fallbacks = array(
			'contact_phone'    => '',
			'contact_whatsapp' => '+86-18620676850',
			'contact_address'  => 'Baiyun District, Guangzhou, China',
		);

		if ( ( 'contact_phone' === $key || 'contact_whatsapp' === $key ) && self::is_placeholder_contact_value( $value ) ) {
			return isset( $fallbacks[ $key ] ) ? $fallbacks[ $key ] : '';
		}

		if ( 'contact_address' === $key && ( self::is_placeholder_contact_value( $value ) || false !== stripos( $value, 'pending final confirmation' ) ) ) {
			return $fallbacks['contact_address'];
		}

		return $value;
	}

	/**
	 * Get a sanitized posted value.
	 *
	 * @param string   $key      Input key.
	 * @param callable $callback Sanitizer callback.
	 * @return string
	 */
	protected static function get_posted_value( $key, $callback = 'sanitize_text_field' ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			return '';
		}

		return (string) call_user_func( $callback, wp_unslash( $_POST[ $key ] ) );
	}

	/**
	 * Render newline-separated items as a list.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	protected static function render_line_list( $value ) {
		$items = preg_split( '/\r\n|\r|\n/', (string) $value );
		$items = array_filter( array_map( 'trim', $items ) );

		if ( empty( $items ) ) {
			return '';
		}

		$html = '<ul class="yke-line-list">';
		foreach ( $items as $item ) {
			$html .= '<li>' . esc_html( $item ) . '</li>';
		}
		$html .= '</ul>';

		return $html;
	}

	/**
	 * Render a simple array as a styled line list.
	 *
	 * @param array $items List items.
	 * @return string
	 */
	protected static function build_simple_line_list( $items ) {
		$items = array_filter( array_map( 'trim', (array) $items ) );

		if ( empty( $items ) ) {
			return '';
		}

		$html = '<ul class="yke-line-list">';
		foreach ( $items as $item ) {
			$html .= '<li>' . esc_html( $item ) . '</li>';
		}
		$html .= '</ul>';

		return $html;
	}

	/**
	 * Render a richer list for fact-driven marketing sections.
	 *
	 * @param array  $items List items.
	 * @param string $class Wrapper class.
	 * @return string
	 */
	protected static function build_marketing_fact_list( $items, $class = 'yke-fact-list' ) {
		$items = array_filter( array_map( 'trim', (array) $items ) );

		if ( empty( $items ) ) {
			return '';
		}

		$html = '<ul class="' . esc_attr( $class ) . '">';
		foreach ( $items as $item ) {
			$html .= '<li>' . esc_html( $item ) . '</li>';
		}
		$html .= '</ul>';

		return $html;
	}

	/**
	 * Render company fact cards for credibility sections.
	 *
	 * @param array $cards Fact cards.
	 * @return string
	 */
	protected static function render_company_fact_cards( $cards ) {
		if ( empty( $cards ) ) {
			return '';
		}

		$html = '<div class="yke-trust-grid">';
		foreach ( $cards as $card ) {
			if ( empty( $card['title'] ) || empty( $card['body'] ) ) {
				continue;
			}
			$html .= '<article class="yke-trust-card"><h3>' . esc_html( $card['title'] ) . '</h3><p>' . esc_html( $card['body'] ) . '</p></article>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Return notice copy for inquiry submission results.
	 *
	 * @param string $status Result status.
	 * @return string
	 */
	protected static function get_inquiry_notice_message( $status ) {
		$lang = self::get_current_site_language();

		$messages = array(
			'success'    => self::t( 'inquiry.success', $lang ),
			'stored'     => self::t( 'inquiry.stored', $lang ),
			'validation' => self::t( 'inquiry.validation', $lang ),
			'error'      => self::t( 'inquiry.error', $lang ),
		);

		return isset( $messages[ $status ] ) ? $messages[ $status ] : '';
	}

	/**
	 * Normalize a phone number for tel links.
	 *
	 * @param string $value Phone value.
	 * @return string
	 */
	protected static function normalize_phone_link( $value ) {
		return preg_replace( '/[^0-9+]/', '', $value );
	}

	/**
	 * Build a WhatsApp link.
	 *
	 * @param string $value Phone value.
	 * @return string
	 */
	protected static function build_whatsapp_link( $value ) {
		$phone = preg_replace( '/[^0-9]/', '', $value );

		return 'https://wa.me/' . $phone;
	}

	/**
	 * Detect placeholder contact values that should not surface on the site.
	 *
	 * @param string $value Contact field value.
	 * @return bool
	 */
	protected static function is_placeholder_contact_value( $value ) {
		$value = strtolower( trim( (string) $value ) );

		if ( '' === $value ) {
			return true;
		}

		return false !== strpos( $value, 'xxx' ) || false !== strpos( $value, 'pending' );
	}

	/**
	 * Resolve a local placeholder image URL from the active theme.
	 *
	 * @param string $filename Placeholder filename.
	 * @return string
	 */
	protected static function get_placeholder_image_url( $filename ) {
		return trailingslashit( get_template_directory_uri() ) . 'assets/images/placeholders/' . ltrim( $filename, '/' );
	}

	/**
	 * Get localized visual assets for marketing pages.
	 *
	 * @param string $lang Language slug.
	 * @return array
	 */
	protected static function get_marketing_visual_assets( $lang ) {
		$is_zh = ( 'zh' === $lang );

		$asset = static function ( $filename, $alt, $caption = '' ) {
			return array(
				'src'     => self::get_placeholder_image_url( $filename ),
				'alt'     => $alt,
				'caption' => $caption,
			);
		};

		return array(
			'products'      => array(
				'hero'      => $asset(
					'hero-cover.png',
					$is_zh ? '云科出口型配电设备与工厂主视觉图' : 'YKE export power equipment overview',
					$is_zh ? '围绕海外项目选型与交付组织产品展示。' : 'Product lines presented around export project fit and delivery readiness.'
				),
				'directory' => array(
					'mv-switchgear'             => $asset( '图片7.png', $is_zh ? 'KYN28 高压开关柜正面' : 'KYN28 MV switchgear front view' ),
					'lv-switchgear'             => $asset( '图片9.png', $is_zh ? 'GGD/GCK 低压开关柜正面' : 'GGD/GCK LV switchgear front view' ),
					'transformers'              => $asset( '图片12.png', $is_zh ? '干式变压器 SCB11' : 'Dry-type transformer SCB11' ),
					'prefabricated-substations' => $asset( '图片19.png', $is_zh ? '美式箱变外观' : 'American-style substation exterior' ),
					'new-energy-electrical'     => $asset( 'case-study-nigeria-indonesia.png', $is_zh ? '新能源电气项目与设备图' : 'New energy electrical project package' ),
				),
			),
			'about'         => array(
				'intro'         => $asset(
					'图片3.png',
					$is_zh ? '云科电气公司大门与前台实景' : 'YKE company gate and reception',
					$is_zh ? '先让买家看到真实的云科，再读公司介绍。' : 'See the real YKE facility first, then read the company story.'
				),
				'capability'    => $asset( '图片4.png', $is_zh ? '云科生产车间内景' : 'YKE production workshop interior' ),
				'markets'       => $asset( 'global-markets.png', $is_zh ? '云科出口市场与项目覆盖图' : 'YKE export market footprint' ),
				'market_cards'  => array(
					'middle-east'              => $asset( 'market-analysis-middle-east-sea.png', $is_zh ? '中东市场项目与配电应用图' : 'Middle East market applications' ),
					'southeast-asia'           => $asset( 'case-study-saudi-vietnam.png', $is_zh ? '东南亚项目交付图' : 'Southeast Asia project deliveries' ),
					'africa'                   => $asset( 'market-analysis-africa-latam.png', $is_zh ? '非洲市场项目与供电需求图' : 'Africa market demand' ),
					'latin-america-south-asia' => $asset( 'market-analysis-africa-latam.png', $is_zh ? '拉美与南亚新能源项目图' : 'Latin America and South Asia renewable projects' ),
				),
				'quality'       => $asset( 'quality-assurance.png', $is_zh ? '云科质控与检验图' : 'YKE quality control and inspection' ),
				'gallery'       => array(
					$asset( '图片5.png', $is_zh ? '云科生产车间内景' : 'YKE production workshop interior', $is_zh ? '生产车间' : 'Workshop' ),
					$asset( 'case-study-saudi-vietnam.png', $is_zh ? '中东与东南亚项目交付场景' : 'Middle East and Southeast Asia delivery scenes', $is_zh ? '项目交付' : 'Field delivery' ),
					$asset( 'global-markets.png', $is_zh ? '重点出口市场覆盖' : 'Export market coverage', $is_zh ? '市场覆盖' : 'Market coverage' ),
				),
			),
			'distributor'   => array(
				'intro'         => $asset(
					'global-markets.png',
					$is_zh ? '云科海外渠道与市场覆盖图' : 'YKE regional market coverage',
					$is_zh ? '合作前先看市场与交付半径。' : 'Regional fit and delivery reach before the first discussion.'
				),
				'process'       => $asset( 'contact-promises.png', $is_zh ? '合作咨询与响应承诺图' : 'Partnership response and support commitments' ),
				'support_cards' => array(
					'territory'   => $asset( 'global-markets.png', $is_zh ? '市场覆盖与区域规划图' : 'Regional market planning' ),
					'project'     => $asset( '图片8.png', $is_zh ? '项目配合与产品选型图' : 'Project support and product matching' ),
					'materials'   => $asset( 'case-studies-overview.png', $is_zh ? '项目案例与市场资料图' : 'Case studies and marketing materials' ),
					'after_sales' => $asset( 'after-sales-service.png', $is_zh ? '售后响应与技术支持图' : 'After-sales service and technical support' ),
				),
			),
			'contact'       => array(
				'intro'   => $asset(
					'contact-promises.png',
					$is_zh ? '云科询盘响应与服务承诺图' : 'YKE inquiry response and service commitments',
					$is_zh ? '先讲清项目条件，再进入正式报价。' : 'Clarify the project inputs first, then move into quotation.'
				),
				'service' => $asset( 'after-sales-service.png', $is_zh ? '售后服务与交付支持图' : 'After-sales service and delivery support' ),
				'gallery' => array(
					$asset( 'contact-promises.png', $is_zh ? '询盘响应与服务承诺图' : 'Inquiry response promises', $is_zh ? '响应承诺' : 'Response target' ),
					$asset( 'after-sales-service.png', $is_zh ? '售后服务与技术支持图' : 'After-sales support', $is_zh ? '售后支持' : 'After-sales support' ),
					$asset( 'quality-assurance.png', $is_zh ? '质检与出货前检验图' : 'Quality inspection before shipment', $is_zh ? '质检控制' : 'Quality control' ),
				),
			),
			'case_products' => array(
				'mv-switchgear'             => $asset( '图片7.png', $is_zh ? '高压开关柜案例回退图' : 'MV switchgear case fallback image' ),
				'lv-switchgear'             => $asset( '图片9.png', $is_zh ? '低压开关柜案例回退图' : 'LV switchgear case fallback image' ),
				'transformers'              => $asset( '图片12.png', $is_zh ? '变压器案例回退图' : 'Transformer case fallback image' ),
				'prefabricated-substations' => $asset( '图片19.png', $is_zh ? '预装式变电站案例回退图' : 'Prefabricated substation case fallback image' ),
				'new-energy-electrical'     => $asset( 'case-study-nigeria-indonesia.png', $is_zh ? '新能源项目案例回退图' : 'New energy project case fallback image' ),
			),
			'case_regions'  => array(
				'middle-east'              => $asset( 'case-study-saudi-vietnam.png', $is_zh ? '中东项目案例回退图' : 'Middle East case fallback image' ),
				'southeast-asia'           => $asset( 'case-study-saudi-vietnam.png', $is_zh ? '东南亚项目案例回退图' : 'Southeast Asia case fallback image' ),
				'africa'                   => $asset( 'case-study-nigeria-indonesia.png', $is_zh ? '非洲项目案例回退图' : 'Africa case fallback image' ),
				'latin-america-south-asia' => $asset( 'market-analysis-africa-latam.png', $is_zh ? '拉美与南亚项目案例回退图' : 'Latin America and South Asia case fallback image' ),
			),
			'case_fallback' => $asset( 'case-studies-overview.png', $is_zh ? '云科项目案例总览图' : 'YKE case studies overview image' ),
		);
	}

	/**
	 * Render a localized marketing figure.
	 *
	 * @param array  $asset Asset definition.
	 * @param string $class Figure class.
	 * @return string
	 */
	protected static function render_marketing_figure( $asset, $class ) {
		if ( empty( $asset['src'] ) ) {
			return '';
		}

		$html  = '<figure class="' . esc_attr( $class ) . '">';
		$html .= '<img src="' . esc_url( $asset['src'] ) . '" alt="' . esc_attr( $asset['alt'] ) . '" loading="lazy" />';
		if ( ! empty( $asset['caption'] ) ) {
			$html .= '<figcaption>' . esc_html( $asset['caption'] ) . '</figcaption>';
		}
		$html .= '</figure>';

		return $html;
	}

	/**
	 * Render a compact marketing scene gallery.
	 *
	 * @param array  $assets Gallery asset list.
	 * @param string $class  Gallery class.
	 * @return string
	 */
	protected static function render_marketing_scene_gallery( $assets, $class = 'yke-scene-gallery' ) {
		if ( empty( $assets ) ) {
			return '';
		}

		$html = '<div class="' . esc_attr( $class ) . '">';
		foreach ( $assets as $asset ) {
			$html .= self::render_marketing_figure( $asset, 'yke-scene-gallery__item' );
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Resolve the most useful case-study image for cards and fallbacks.
	 *
	 * @param int    $post_id Case-study post ID.
	 * @param string $lang    Language slug.
	 * @return array
	 */
	protected static function get_case_study_visual( $post_id, $lang ) {
		if ( has_post_thumbnail( $post_id ) ) {
			$thumb_id = get_post_thumbnail_id( $post_id );
			return array(
				'src'         => (string) wp_get_attachment_image_url( $thumb_id, 'large' ),
				'alt'         => get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ) ? get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ) : get_the_title( $post_id ),
				'caption'     => '',
				'is_fallback' => false,
			);
		}

		$assets        = self::get_marketing_visual_assets( $lang );
		$product_slugs = wp_get_post_terms( $post_id, 'product_line', array( 'fields' => 'slugs' ) );
		$region_slugs  = wp_get_post_terms( $post_id, 'case_region', array( 'fields' => 'slugs' ) );

		foreach ( $product_slugs as $slug ) {
			if ( ! empty( $assets['case_products'][ $slug ] ) ) {
				$assets['case_products'][ $slug ]['is_fallback'] = true;
				return $assets['case_products'][ $slug ];
			}
		}

		foreach ( $region_slugs as $slug ) {
			if ( ! empty( $assets['case_regions'][ $slug ] ) ) {
				$assets['case_regions'][ $slug ]['is_fallback'] = true;
				return $assets['case_regions'][ $slug ];
			}
		}

		$assets['case_fallback']['is_fallback'] = true;

		return $assets['case_fallback'];
	}

	/**
	 * Inject fallback media when a case-study featured image is empty.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $block         Parsed block data.
	 * @return string
	 */
	public static function inject_case_study_featured_image_fallback( $block_content, $block ) {
		$block_name = isset( $block['blockName'] ) ? $block['blockName'] : '';
		if ( 'core/post-featured-image' !== $block_name ) {
			return $block_content;
		}

		$post_id = get_the_ID();
		if ( ! $post_id || 'case_study' !== get_post_type( $post_id ) ) {
			return $block_content;
		}

		if ( false !== strpos( $block_content, '<img' ) ) {
			return $block_content;
		}

		$attrs        = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		$aspect_ratio = ! empty( $attrs['aspectRatio'] ) ? $attrs['aspectRatio'] : '16/9';
		$visual       = self::get_case_study_visual( $post_id, self::get_current_site_language() );

		return '<figure class="wp-block-post-featured-image yke-case-featured-image yke-case-featured-image--fallback" style="aspect-ratio:' . esc_attr( $aspect_ratio ) . ';">'
			. '<img src="' . esc_url( $visual['src'] ) . '" alt="' . esc_attr( $visual['alt'] ) . '" loading="lazy" />'
			. '</figure>';
	}

	/**
	 * Build marketing products page content.
	 *
	 * @param string $lang Language slug.
	 * @return string
	 */
	protected static function build_marketing_products_content( $lang ) {
		$is_zh         = ( 'zh' === $lang );
		$products_url  = self::get_seeded_page_url( 'products', $lang );
		$cases_url     = self::get_case_studies_url( $lang );
		$contact_url   = self::get_seeded_page_url( 'contact', $lang );
		$profiles      = self::get_product_line_profiles();
		$product_order = array(
			'mv-switchgear',
			'lv-switchgear',
			'transformers',
			'prefabricated-substations',
			'new-energy-electrical',
		);

		$copy = $is_zh
			? array(
				'intro_title'        => '五大产品线，围绕出口项目决策展开',
				'intro_body'         => '产品页不只是罗列型号，而是帮助海外采购、EPC 团队和渠道伙伴快速判断：哪些产品线适合当前项目、交付边界在哪里、以及云科是否具备相应的出口配套能力。',
				'snapshot_title'     => '采购速览',
				'snapshot_items'     => array(
					'电压覆盖：0.4kV 至 35kV',
					'认证体系：CE / CCC / ISO 9001 / ISO 14001 / ISO 45001',
					'起订支持：单台起订，项目数量灵活',
					'标准交期：30 至 45 天，备货项目可更快',
					'验货支持：SGS / BV / Intertek',
					'售后承诺：18 个月质保，48 小时内确认责任归属',
				),
				'directory_title'    => '产品目录',
				'directory_body'     => '每条产品线都以典型应用、代表型号和可定制范围来组织，方便先筛选，再深入到具体方案。',
				'fit_title'          => '为什么这套目录更适合出口采购',
				'fit_cards'          => array(
					array( 'title' => '按项目场景找产品', 'body' => '先看工业园区、工厂改造、公用事业、新能源并网等应用，而不是先被型号细节淹没。' ),
					array( 'title' => '先确认交付边界', 'body' => '明确标准、元件品牌、出厂测试、资料语言和包装方案，减少往返确认。' ),
					array( 'title' => '兼顾渠道与 EPC 需求', 'body' => '既能做单次项目匹配，也适合长期分销和区域代理备货规划。' ),
				),
				'proof_title'        => '相关交付证明',
				'proof_body'         => '产品能力最好由交付记录来证明。以下案例覆盖中东、东南亚、非洲和新能源项目。',
				'proof_cta'          => '查看全部案例',
				'cta_title'          => '需要先做产品线匹配或选型建议？',
				'cta_body'           => '告诉我们目标国家、电压等级、应用场景、预计数量和执行标准，云科可以先给出匹配产品线与代表配置。',
				'cta_primary'        => '发起询盘',
				'cta_secondary'      => '查看案例',
				'explore'            => '查看详情',
			)
			: array(
				'intro_title'        => 'Five product lines structured around export buying decisions',
				'intro_body'         => 'This page is not a loose catalog. It is built to help overseas buyers, EPC teams, and channel partners decide which product family fits the project, what the delivery boundary looks like, and whether YKE can support the export path with real documentation and inspection readiness.',
				'snapshot_title'     => 'Buyer snapshot',
				'snapshot_items'     => array(
					'Voltage coverage: 0.4kV to 35kV',
					'Certifications: CE / CCC / ISO 9001 / ISO 14001 / ISO 45001',
					'MOQ: single-unit supply accepted when projects require it',
					'Lead time: 30 to 45 days standard, faster for stocked combinations',
					'Inspection: SGS, BV, and Intertek support available',
					'After-sales: 18-month standard warranty with 100% routine outgoing inspection',
				),
				'directory_title'    => 'Product directory',
				'directory_body'     => 'Each product line is framed by its typical use cases, representative models, and customization scope so buyers can qualify fit before getting lost in part numbers.',
				'fit_title'          => 'Why this structure works for export procurement',
				'fit_cards'          => array(
					array( 'title' => 'Start with the application, not the model number', 'body' => 'Industrial parks, factory upgrades, utility feeders, and renewable sites each call for a different configuration path.' ),
					array( 'title' => 'Clarify the delivery boundary early', 'body' => 'Standards, component brands, outgoing tests, document language, and packing method are visible up front.' ),
					array( 'title' => 'Built for project buyers and long-term channel partners', 'body' => 'The same directory supports one-off quotations, tender preparation, and distributor market planning.' ),
				),
				'proof_title'        => 'Relevant delivery proof',
				'proof_body'         => 'The strongest product claim is a delivered project. These case studies show where the lines have already been used across the Middle East, Southeast Asia, Africa, and renewable programs.',
				'proof_cta'          => 'View all case studies',
				'cta_title'          => 'Need help matching a product line before requesting a formal quote?',
				'cta_body'           => 'Send the destination market, voltage level, application, estimated quantity, and standards. YKE can reply with a recommended product path and representative configuration.',
				'cta_primary'        => 'Start an inquiry',
				'cta_secondary'      => 'Browse case studies',
				'explore'            => 'Explore line',
			);

		$html  = '<section class="yke-marketing-shell">';
		$html .= '<section class="yke-marketing-band"><div class="yke-marketing-band__grid">';
		$html .= '<div class="yke-marketing-copy"><h2>' . esc_html( $copy['intro_title'] ) . '</h2><p>' . esc_html( $copy['intro_body'] ) . '</p></div>';
		$html .= '<aside class="yke-marketing-panel yke-marketing-panel--tight"><h3>' . esc_html( $copy['snapshot_title'] ) . '</h3>' . self::build_simple_line_list( $copy['snapshot_items'] ) . '</aside>';
		$html .= '</div></section>';

		$html .= '<section class="yke-marketing-band yke-marketing-band--light"><div class="yke-section-head"><h2>' . esc_html( $copy['directory_title'] ) . '</h2><p>' . esc_html( $copy['directory_body'] ) . '</p></div>';
		$html .= '<div class="yke-directory-grid">';
		foreach ( $product_order as $slug ) {
			if ( empty( $profiles[ $slug ][ $lang ] ) ) {
				continue;
			}
			$profile = $profiles[ $slug ][ $lang ];
			$page    = self::get_product_page_by_slug( $slug, $lang );
			$url     = $page ? get_permalink( $page ) : $products_url;
			$title   = self::get_product_display_title( $slug, $lang );
			$html   .= '<article class="yke-directory-card">';
			$html   .= '<div class="yke-directory-card__head"><h3>' . esc_html( $title ) . '</h3><p>' . esc_html( $profile['overview'] ) . '</p></div>';
			$html   .= '<div class="yke-directory-card__body">';
			$html   .= '<div><h4>' . esc_html( $is_zh ? '典型应用' : 'Typical applications' ) . '</h4>' . self::build_simple_line_list( array_slice( $profile['applications'], 0, 3 ) ) . '</div>';
			$html   .= '<div><h4>' . esc_html( $is_zh ? '代表型号 / 方案' : 'Representative models' ) . '</h4>' . self::build_simple_line_list( array_slice( $profile['models'], 0, 3 ) ) . '</div>';
			$html   .= '<div><h4>' . esc_html( $is_zh ? '定制范围' : 'Customization scope' ) . '</h4>' . self::build_simple_line_list( array_slice( $profile['customization'], 0, 3 ) ) . '</div>';
			$html   .= '</div>';
			$html   .= '<div class="yke-directory-card__foot"><span>' . esc_html( $profile['standards'] ) . '</span><a href="' . esc_url( $url ) . '">' . esc_html( $copy['explore'] ) . '</a></div>';
			$html   .= '</article>';
		}
		$html .= '</div></section>';

		$html .= '<section class="yke-marketing-band"><div class="yke-section-head"><h2>' . esc_html( $copy['fit_title'] ) . '</h2></div><div class="yke-trust-grid">';
		foreach ( $copy['fit_cards'] as $card ) {
			$html .= '<article class="yke-trust-card"><h3>' . esc_html( $card['title'] ) . '</h3><p>' . esc_html( $card['body'] ) . '</p></article>';
		}
		$html .= '</div></section>';

		$html .= '<section class="yke-marketing-band yke-marketing-band--light"><div class="yke-section-head"><h2>' . esc_html( $copy['proof_title'] ) . '</h2><p>' . esc_html( $copy['proof_body'] ) . '</p></div>';
		$html .= self::render_case_study_teaser_cards( $lang, 3 );
		$html .= '<div class="yke-inline-cta"><a class="yke-text-link" href="' . esc_url( $cases_url ) . '">' . esc_html( $copy['proof_cta'] ) . '</a></div></section>';

		$html .= '<section class="yke-decision-strip"><div><h2>' . esc_html( $copy['cta_title'] ) . '</h2><p>' . esc_html( $copy['cta_body'] ) . '</p></div>';
		$html .= '<div class="yke-decision-strip__actions"><a class="yke-btn-primary" href="' . esc_url( $contact_url ) . '">' . esc_html( $copy['cta_primary'] ) . '</a><a class="yke-btn-outline yke-btn-outline--dark" href="' . esc_url( $cases_url ) . '">' . esc_html( $copy['cta_secondary'] ) . '</a></div></section>';
		$html .= '</section>';

		return $html;
	}

	/**
	 * Build marketing about page content.
	 *
	 * @param string $lang Language slug.
	 * @return string
	 */
	protected static function build_marketing_about_content( $lang ) {
		$is_zh       = ( 'zh' === $lang );
		$contact_url = self::get_seeded_page_url( 'contact', $lang );
		$copy = $is_zh
			? array(
				'intro_title'    => '一家为出口项目组织生产、资料与交付的配电设备供应商',
				'intro_body'     => '云科不是单纯做页面展示的工厂介绍，而是面向海外分销、EPC 和工业采购场景来组织业务能力：从选型、图纸、检验到装运文件，都服务于实际项目落地。',
				'proof_title'    => '让客户先判断，再决定是否继续沟通',
				'proof_points'   => array(
					'0.4kV 至 35kV 产品覆盖，适配工业、公用事业与新能源项目',
					'支持 IEC / GB 体系文件、FAT 记录与第三方验货',
					'可接受单台起订，也可配合大批量分阶段交付',
					'工作日 1 小时内初步响应，48 小时内确认售后责任路径',
				),
				'cap_title'      => '能力结构',
				'cap_cards'      => array(
					array( 'title' => '出口交付组织能力', 'body' => '询盘回复、选型建议、装箱清单、检验证书和装运资料以同一逻辑推进，减少跨部门断点。' ),
					array( 'title' => '工况适配能力', 'body' => '可围绕高温、多尘、潮湿、高海拔和并网项目要求调整配置与验证范围。' ),
					array( 'title' => '渠道协同能力', 'body' => '支持分销商做市场开发，也支持 EPC 和项目采购做一次性交付评估。' ),
				),
				'market_title'   => '重点市场方向',
				'market_body'    => '云科当前的市场实践主要集中在中东、东南亚、非洲以及新能源相关的新兴市场。',
				'quality_title'  => '质量与文件控制',
				'quality_points' => array(
					'出厂前完成例行测试与检验记录归档',
					'支持 SGS、BV、Intertek 等第三方验货',
					'证书体系包括 CE、CCC、ISO 9001、ISO 14001、ISO 45001',
					'中英文资料可根据项目需求随货或提前提供',
				),
				'cta_title'      => '想进一步确认云科是否适合您的市场或项目？',
				'cta_body'       => '可以直接发送目标国家、产品范围和项目阶段，我们会用最接近实际交付的方式回复。',
				'cta_button'     => '联系云科',
			)
			: array(
				'intro_title'    => 'An export-oriented power equipment supplier organized around real project delivery',
				'intro_body'     => 'YKE is not presented as a generic factory profile. The company is structured around what overseas distributors, EPC teams, and industrial buyers actually need to judge: product fit, document readiness, inspection support, and the ability to move from quotation to shipment without confusion.',
				'proof_title'    => 'Enough proof to qualify the supplier before the call',
				'proof_points'   => array(
					'0.4kV to 35kV coverage across industrial, utility, and renewable applications',
					'IEC and GB aligned files, FAT records, and third-party inspection support',
					'Single-unit flexibility for urgent projects and staged delivery for larger orders',
					'18-month standard warranty and 100% outgoing inspection',
				),
				'cap_title'      => 'Capability structure',
				'cap_cards'      => array(
					array( 'title' => 'Export delivery discipline', 'body' => 'Inquiry handling, configuration review, packing lists, inspection records, and shipment documents move through one consistent workflow.' ),
					array( 'title' => 'Environmental adaptation', 'body' => 'Configurations can be adjusted for heat, dust, humidity, altitude, and grid-connection requirements rather than treated as afterthoughts.' ),
					array( 'title' => 'Channel and project support', 'body' => 'The same team can support distributor development, tender support, and one-off EPC procurement decisions.' ),
				),
				'market_title'   => 'Priority market focus',
				'market_body'    => 'Current export momentum is strongest in the Middle East, Southeast Asia, Africa, and renewable-driven emerging markets where technical clarity matters early.',
				'quality_title'  => 'Quality control and documentation',
				'quality_points' => array(
					'Routine outgoing tests completed and archived before shipment',
					'Factory inspection support for SGS, BV, Intertek, and similar agencies',
					'Certification set covering CE, CCC, ISO 9001, ISO 14001, and ISO 45001',
					'Chinese and English document packages prepared to match project needs',
				),
				'cta_title'      => 'Want to check whether YKE is a fit for your market or project stage?',
				'cta_body'       => 'Send the destination market, product scope, and current procurement stage. The reply will stay practical and delivery-focused.',
				'cta_button'     => 'Contact YKE',
			);

		$html  = '<section class="yke-marketing-shell">';
		$html .= '<section class="yke-marketing-band"><div class="yke-marketing-band__grid">';
		$html .= '<div class="yke-marketing-copy"><h2>' . esc_html( $copy['intro_title'] ) . '</h2><p>' . esc_html( $copy['intro_body'] ) . '</p></div>';
		$html .= '<aside class="yke-marketing-panel"><h3>' . esc_html( $copy['proof_title'] ) . '</h3>' . self::build_simple_line_list( $copy['proof_points'] ) . '</aside>';
		$html .= '</div></section>';

		$html .= '<section class="yke-marketing-band yke-marketing-band--light"><div class="yke-section-head"><h2>' . esc_html( $copy['cap_title'] ) . '</h2></div><div class="yke-trust-grid">';
		foreach ( $copy['cap_cards'] as $card ) {
			$html .= '<article class="yke-trust-card"><h3>' . esc_html( $card['title'] ) . '</h3><p>' . esc_html( $card['body'] ) . '</p></article>';
		}
		$html .= '</div></section>';

		$html .= '<section class="yke-marketing-band"><div class="yke-section-head"><h2>' . esc_html( $copy['market_title'] ) . '</h2><p>' . esc_html( $copy['market_body'] ) . '</p></div>';
		$html .= self::render_market_focus_cards( $lang ) . '</section>';

		$html .= '<section class="yke-marketing-band yke-marketing-band--light"><div class="yke-marketing-band__grid">';
		$html .= '<div class="yke-marketing-copy"><h2>' . esc_html( $copy['quality_title'] ) . '</h2>' . self::build_simple_line_list( $copy['quality_points'] ) . '</div>';
		$html .= '<aside class="yke-marketing-panel"><div class="yke-contact-card__compact">[yke_contact_details show_commitments="1" show_certifications="1"]</div></aside>';
		$html .= '</div></section>';

		$html .= '<section class="yke-decision-strip"><div><h2>' . esc_html( $copy['cta_title'] ) . '</h2><p>' . esc_html( $copy['cta_body'] ) . '</p></div>';
		$html .= '<div class="yke-decision-strip__actions"><a class="yke-btn-primary" href="' . esc_url( $contact_url ) . '">' . esc_html( $copy['cta_button'] ) . '</a></div></section>';
		$html .= '</section>';

		return do_shortcode( $html );
	}

	/**
	 * Build marketing distributor page content.
	 *
	 * @param string $lang Language slug.
	 * @return string
	 */
	protected static function build_marketing_distributor_content( $lang ) {
		$is_zh       = ( 'zh' === $lang );
		$contact_url = self::get_seeded_page_url( 'contact', $lang );
		$cases_url   = self::get_case_studies_url( $lang );
		$copy = $is_zh
			? array(
				'intro_title'    => '代理合作不是留个表单，而是交代清楚支持边界',
				'intro_body'     => '云科希望吸引的是愿意长期做市场的区域伙伴，因此这页重点说明区域保护、报价支持、投标配合、资料供给与售后协同，而不是空泛地说“欢迎合作”。',
				'support_title'  => '合作支持结构',
				'support_cards'  => array(
					array( 'title' => '区域与渠道规则', 'body' => '根据合作深度约定区域保护、项目报备机制与渠道边界，减少后期冲突。' ),
					array( 'title' => '项目与技术配合', 'body' => '支持产品选型、规格确认、投标资料、竞品对比和交付节奏建议。' ),
					array( 'title' => '市场推进资料', 'body' => '可提供多语种画册、案例资料、图片素材和面向客户的基础介绍材料。' ),
					array( 'title' => '售后与升级响应', 'body' => '围绕售后责任归属、替换件、技术答疑和问题升级建立明确路径。' ),
				),
				'who_title'      => '更适合哪些合作方',
				'who_points'     => array(
					'已有电气产品渠道或工程客户基础的本地分销商',
					'希望增加高压、低压、变压器或新能源电气线的公司',
					'能够负责本地商务推进、基础跟单与客户关系维护的团队',
				),
				'process_title'  => '合作启动流程',
				'process_steps'  => array(
					'提交市场范围、客户类型和现有产品结构',
					'双方确认重点产品线、价格带和支持方式',
					'进入样品、项目配合或年度采购节奏验证',
				),
				'proof_title'    => '交付与市场证明',
				'proof_body'     => '如果您正在评估一个品牌是否值得做，案例和交付记录比口号更有说服力。',
				'cta_title'      => '准备讨论区域合作、项目跟进或 OEM / ODM 路径？',
				'cta_body'       => '请发送目标国家、已有客户类型和优先产品线，我们会按合作实际情况回复。',
				'cta_primary'    => '提交合作咨询',
				'cta_secondary'  => '先看案例',
			)
			: array(
				'intro_title'    => 'Distributor recruitment should explain the support model, not just ask for a form fill',
				'intro_body'     => 'YKE is looking for partners who want to build regional business over time. This page focuses on channel protection, quotation support, tender cooperation, market materials, and after-sales coordination so a prospective distributor can judge the operating model clearly.',
				'support_title'  => 'Support structure',
				'support_cards'  => array(
					array( 'title' => 'Territory and channel rules', 'body' => 'Regional protection, project registration logic, and channel boundaries are discussed early to reduce conflict later.' ),
					array( 'title' => 'Technical and project cooperation', 'body' => 'YKE can support product matching, specification review, bid packages, competitor comparison, and practical delivery planning.' ),
					array( 'title' => 'Market development materials', 'body' => 'Partners can receive multilingual brochures, project references, product images, and customer-facing introductions.' ),
					array( 'title' => 'After-sales escalation path', 'body' => 'Issue ownership, spare-part coordination, technical clarification, and escalation response are handled through a defined route.' ),
				),
				'who_title'      => 'Best-fit partner profile',
				'who_points'     => array(
					'Local distributors with an existing electrical, industrial, or project customer base',
					'Companies adding MV, LV, transformer, or renewable electrical lines to their portfolio',
					'Teams able to manage local commercial follow-up and maintain customer relationships',
				),
				'process_title'  => 'How cooperation usually starts',
				'process_steps'  => array(
					'Share the target market, customer mix, and current product portfolio',
					'Align on priority product lines, price positioning, and support expectations',
					'Move into sample supply, project cooperation, or an annual purchasing rhythm',
				),
				'proof_title'    => 'Delivery and market proof',
				'proof_body'     => 'For channel partners, real delivery records and repeat-order logic matter more than broad claims.',
				'cta_title'      => 'Ready to discuss regional cooperation, project support, or OEM / ODM delivery paths?',
				'cta_body'       => 'Send the target country, current customer profile, and preferred product lines. The reply will be tailored to the partnership stage.',
				'cta_primary'    => 'Start a partnership inquiry',
				'cta_secondary'  => 'Review case studies',
			);

		$html  = '<section class="yke-marketing-shell">';
		$html .= '<section class="yke-marketing-band"><div class="yke-marketing-band__grid">';
		$html .= '<div class="yke-marketing-copy"><h2>' . esc_html( $copy['intro_title'] ) . '</h2><p>' . esc_html( $copy['intro_body'] ) . '</p></div>';
		$html .= '<aside class="yke-marketing-panel"><h3>' . esc_html( $copy['who_title'] ) . '</h3>' . self::build_simple_line_list( $copy['who_points'] ) . '</aside>';
		$html .= '</div></section>';

		$html .= '<section class="yke-marketing-band yke-marketing-band--light"><div class="yke-section-head"><h2>' . esc_html( $copy['support_title'] ) . '</h2></div><div class="yke-trust-grid">';
		foreach ( $copy['support_cards'] as $card ) {
			$html .= '<article class="yke-trust-card"><h3>' . esc_html( $card['title'] ) . '</h3><p>' . esc_html( $card['body'] ) . '</p></article>';
		}
		$html .= '</div></section>';

		$html .= '<section class="yke-marketing-band"><div class="yke-marketing-band__grid">';
		$html .= '<div class="yke-marketing-copy"><h2>' . esc_html( $copy['process_title'] ) . '</h2><ol class="yke-process-list">';
		foreach ( $copy['process_steps'] as $step ) {
			$html .= '<li>' . esc_html( $step ) . '</li>';
		}
		$html .= '</ol></div>';
		$html .= '<aside class="yke-marketing-panel yke-marketing-panel--form"><h3>' . esc_html( $copy['cta_primary'] ) . '</h3><p>' . esc_html( $copy['cta_body'] ) . '</p>[yke_inquiry_form product_interest="' . esc_attr( $is_zh ? '代理合作咨询' : 'Distributor Partnership' ) . '"]</aside>';
		$html .= '</div></section>';

		$html .= '<section class="yke-marketing-band yke-marketing-band--light"><div class="yke-section-head"><h2>' . esc_html( $copy['proof_title'] ) . '</h2><p>' . esc_html( $copy['proof_body'] ) . '</p></div>';
		$html .= self::render_case_study_teaser_cards( $lang, 2 );
		$html .= '<div class="yke-inline-cta"><a class="yke-text-link" href="' . esc_url( $cases_url ) . '">' . esc_html( $copy['cta_secondary'] ) . '</a></div></section>';

		$html .= '<section class="yke-decision-strip"><div><h2>' . esc_html( $copy['cta_title'] ) . '</h2><p>' . esc_html( $copy['cta_body'] ) . '</p></div>';
		$html .= '<div class="yke-decision-strip__actions"><a class="yke-btn-primary" href="' . esc_url( $contact_url ) . '">' . esc_html( $copy['cta_primary'] ) . '</a><a class="yke-btn-outline yke-btn-outline--dark" href="' . esc_url( $cases_url ) . '">' . esc_html( $copy['cta_secondary'] ) . '</a></div></section>';
		$html .= '</section>';

		return do_shortcode( $html );
	}

	/**
	 * Build marketing contact page content.
	 *
	 * @param string $lang Language slug.
	 * @return string
	 */
	protected static function build_marketing_contact_content( $lang ) {
		$is_zh        = ( 'zh' === $lang );
		$products_url = self::get_seeded_page_url( 'products', $lang );
		$copy = $is_zh
			? array(
				'intro_title'   => '请把项目条件讲清楚，我们会更快给到有效回复',
				'intro_body'    => '比起一句“请报价”，目标国家、电压等级、应用场景、数量、标准和时间要求，才是真正能帮助云科快速匹配产品和供货路径的信息。',
				'contact_title' => '联系信息',
				'contact_body'  => '如有明确项目，可直接提交询盘；如仍在前期筛选，也可以先发基础信息确认可行性。',
				'guide_title'   => '建议优先提供',
				'guide_points'  => array(
					'目标国家 / 市场以及客户类型',
					'电压等级、容量、应用场景和执行标准',
					'预计数量、招标时间或期望交期',
					'是否需要 OEM / ODM、资料本地化或第三方验货',
				),
				'process_title' => '云科通常如何回复',
				'process_steps' => array(
					'先确认适合的产品线与是否具备基本交付条件',
					'再根据项目阶段提供代表配置、资料或下一步沟通建议',
					'如信息充分，可进入正式报价与文件准备',
				),
				'form_title'    => '提交询盘',
				'form_body'     => '内容不必一次写到最细，但请尽量提供影响选型和交付的关键信息。',
				'aux_title'     => '还没确定具体产品？',
				'aux_body'      => '可以先回到产品页看五大产品线，再带着更清晰的范围来询盘。',
				'aux_button'    => '浏览产品线',
			)
			: array(
				'intro_title'   => 'The more clearly you describe the project, the more practical the reply can be',
				'intro_body'    => 'A real inquiry is more than “please quote.” The destination market, voltage level, application, quantity, standards, and delivery timing are what allow YKE to suggest the right product path quickly.',
				'contact_title' => 'Contact details',
				'contact_body'  => 'If the project is already defined, send an inquiry directly. If you are still screening suppliers, a shorter message is still enough to confirm whether the fit is realistic.',
				'guide_title'   => 'Useful information to send first',
				'guide_points'  => array(
					'Destination country or market and the buyer type',
					'Voltage level, capacity, application, and target standards',
					'Expected quantity, tender timing, or delivery window',
					'Whether OEM / ODM branding, localized documents, or third-party inspection are required',
				),
				'process_title' => 'How YKE usually responds',
				'process_steps' => array(
					'Confirm the relevant product line and whether the delivery scope is workable',
					'Reply with a representative configuration, missing inputs, or the next practical step',
					'Move into formal quotation and documentation once the basics are clear',
				),
				'form_title'    => 'Send an inquiry',
				'form_body'     => 'The message does not have to be perfect, but it should include the details that shape product selection and export delivery.',
				'aux_title'     => 'Still deciding which product line fits?',
				'aux_body'      => 'Review the five product families first, then come back with a narrower scope for a faster reply.',
				'aux_button'    => 'Browse product lines',
			);

		$html  = '<section class="yke-marketing-shell">';
		$html .= '<section class="yke-marketing-band"><div class="yke-marketing-band__grid">';
		$html .= '<div class="yke-marketing-copy"><h2>' . esc_html( $copy['intro_title'] ) . '</h2><p>' . esc_html( $copy['intro_body'] ) . '</p></div>';
		$html .= '<aside class="yke-marketing-panel"><h3>' . esc_html( $copy['guide_title'] ) . '</h3>' . self::build_simple_line_list( $copy['guide_points'] ) . '</aside>';
		$html .= '</div></section>';

		$html .= '<section class="yke-marketing-band yke-marketing-band--light"><div class="yke-contact-layout">';
		$html .= '<div class="yke-contact-stack"><div class="yke-section-head"><h2>' . esc_html( $copy['contact_title'] ) . '</h2><p>' . esc_html( $copy['contact_body'] ) . '</p></div>';
		$html .= '<div class="yke-marketing-panel"><div class="yke-contact-card__compact">[yke_contact_details show_commitments="1" show_certifications="1"]</div></div>';
		$html .= '<div class="yke-marketing-panel"><h3>' . esc_html( $copy['process_title'] ) . '</h3><ol class="yke-process-list">';
		foreach ( $copy['process_steps'] as $step ) {
			$html .= '<li>' . esc_html( $step ) . '</li>';
		}
		$html .= '</ol></div>';
		$html .= '<div class="yke-marketing-panel"><h3>' . esc_html( $copy['aux_title'] ) . '</h3><p>' . esc_html( $copy['aux_body'] ) . '</p><a class="yke-text-link" href="' . esc_url( $products_url ) . '">' . esc_html( $copy['aux_button'] ) . '</a></div></div>';
		$html .= '<div class="yke-marketing-panel yke-marketing-panel--form"><h3>' . esc_html( $copy['form_title'] ) . '</h3><p>' . esc_html( $copy['form_body'] ) . '</p>[yke_inquiry_form product_interest="' . esc_attr( $is_zh ? '通用询盘' : 'General Inquiry' ) . '"]</div>';
		$html .= '</div></section></section>';

		return do_shortcode( $html );
	}

	/**
	 * Render market focus cards for the about page.
	 *
	 * @param string $lang Language slug.
	 * @return string
	 */
	protected static function render_market_focus_cards( $lang ) {
		$is_zh   = ( 'zh' === $lang );
		$visuals = self::get_marketing_visual_assets( $lang );
		$cards = array(
			array(
				'title' => $is_zh ? '中东' : 'Middle East',
				'body'  => $is_zh ? '高温、多尘和公用事业标准要求更明确，适合强调防护、耐热和资料完整性的项目。' : 'A higher-threshold market where heat resistance, dust protection, and disciplined documentation are judged early.',
			),
			array(
				'title' => $is_zh ? '东南亚' : 'Southeast Asia',
				'body'  => $is_zh ? '制造业扩建、新能源并网和工厂搬迁项目多，交期灵活性与本地化资料价值更高。' : 'Factory expansion, relocation, and renewable work reward flexible delivery and localized technical files.',
			),
			array(
				'title' => $is_zh ? '非洲' : 'Africa',
				'body'  => $is_zh ? '重视成本控制、供货稳定和渠道协同，适合变压器、低压系统和成套配电方案。' : 'A market where stable supply, price discipline, and channel cooperation often decide repeat business.',
			),
			array(
				'title' => $is_zh ? '拉美与南亚' : 'Latin America and South Asia',
				'body'  => $is_zh ? '新能源和 EPC 项目带动需求，对并网资料、成套供货和协同响应要求更强。' : 'Renewable and EPC-led demand makes grid-connection support and one-stop electrical supply more important.',
			),
		);

		$market_asset_keys = array( 'middle-east', 'southeast-asia', 'africa', 'latin-america-south-asia' );
		$html              = '<div class="yke-market-grid yke-market-grid--visual">';
		foreach ( $cards as $index => $card ) {
			$asset_key = isset( $market_asset_keys[ $index ] ) ? $market_asset_keys[ $index ] : '';
			$asset     = ( $asset_key && isset( $visuals['about']['market_cards'][ $asset_key ] ) ) ? $visuals['about']['market_cards'][ $asset_key ] : $visuals['about']['markets'];

			$html .= '<article class="yke-market-card yke-market-card--visual">';
			$html .= self::render_marketing_figure( $asset, 'yke-market-card__media' );
			$html .= '<div class="yke-market-card__body"><h3>' . esc_html( $card['title'] ) . '</h3><p>' . esc_html( $card['body'] ) . '</p></div></article>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get structured case-study payload for archive and single-page rendering.
	 *
	 * @param int    $post_id Case-study post ID.
	 * @param string $lang    Language slug.
	 * @return array
	 */
	protected static function get_case_study_structured_payload( $post_id, $lang ) {
		$region_terms = get_the_terms( $post_id, 'case_region' );
		$region_names = array();
		$product_lines = self::get_product_line_display( $post_id );

		if ( ! empty( $region_terms ) && ! is_wp_error( $region_terms ) ) {
			foreach ( $region_terms as $term ) {
				$region_names[] = $term->name;
			}
		}

		$meta = array();
		foreach ( self::get_case_study_meta_fields() as $key => $field ) {
			$meta[ $key ] = trim( (string) get_post_meta( $post_id, self::OPTION_PREFIX . $key, true ) );
		}

		$labels = array(
			'snapshot'      => self::t( 'case.label.snapshot', $lang ),
			'background'    => self::t( 'case.label.background', $lang ),
			'supply'        => self::t( 'case.label.supply', $lang ),
			'technical'     => self::t( 'case.label.technical', $lang ),
			'result'        => self::t( 'case.label.result', $lang ),
			'related_cases' => self::t( 'case.label.related_cases', $lang ),
			'related_lines' => self::t( 'case.label.related_lines', $lang ),
		);

		$snapshot = array();
		$snapshot_map = array(
			'country'          => self::t( 'case.label.country', $lang ),
			'client_type'      => self::t( 'case.label.client_type', $lang ),
			'application'      => self::t( 'case.label.application', $lang ),
			'project_capacity' => self::t( 'case.label.project_capacity', $lang ),
			'standards'        => self::t( 'case.label.standards', $lang ),
			'delivery_result'  => self::t( 'case.label.result', $lang ),
		);

		foreach ( $snapshot_map as $key => $label ) {
			if ( '' !== $meta[ $key ] ) {
				$snapshot[] = array(
					'label'      => $label,
					'value'      => $meta[ $key ],
					'allow_html' => false,
				);
			}
		}

		if ( ! empty( $region_names ) ) {
			$snapshot[] = array(
				'label'      => self::t( 'case.label.region', $lang ),
				'value'      => implode( ' / ', $region_names ),
				'allow_html' => false,
			);
		}

		if ( '' !== $product_lines ) {
			$snapshot[] = array(
				'label'      => $labels['related_lines'],
				'value'      => $product_lines,
				'allow_html' => true,
			);
		}

		return array(
			'labels'       => $labels,
			'meta'         => $meta,
			'snapshot'     => $snapshot,
			'region_names' => $region_names,
			'visual'       => self::get_case_study_visual( $post_id, $lang ),
		);
	}

	/**
	 * Build structured case-study detail sections appended to single pages.
	 *
	 * @param int    $post_id  Case-study post ID.
	 * @param string $lang     Language slug.
	 * @param array  $payload  Case-study payload.
	 * @return string
	 */
	protected static function build_case_study_detail_sections( $post_id, $lang, $payload ) {
		$labels        = $payload['labels'];
		$meta          = $payload['meta'];
		$background    = array_filter( array( $meta['client_type'], $meta['application'], $meta['project_capacity'], $meta['nda_note'] ) );
		$supply        = array_filter( array( $meta['supply_scope'], $meta['key_specs'] ) );
		$technical     = array_filter( array( $meta['technical_focus'], $meta['standards'] ) );
		$result        = array_filter( array( $meta['delivery_result'], $meta['nda_note'] ) );
		$related_cases = self::build_related_case_links( $post_id, $lang );
		$related_lines = self::build_related_product_links( $post_id, $lang );

		$html  = '<section class="yke-case-study-details">';
		$html .= '<h2>' . esc_html( $labels['snapshot'] ) . '</h2><dl>';
		foreach ( $payload['snapshot'] as $detail ) {
			$html .= '<div class="yke-case-study-details__row">';
			$html .= '<dt>' . esc_html( $detail['label'] ) . '</dt><dd>';
			if ( ! empty( $detail['allow_html'] ) ) {
				$html .= wp_kses_post( $detail['value'] );
			} else {
				$html .= wp_kses_post( nl2br( esc_html( $detail['value'] ) ) );
			}
			$html .= '</dd></div>';
		}
		$html .= '</dl></section>';

		$html .= '<section class="yke-page-section yke-page-section--case-detail"><div class="yke-page-section-inner"><div class="yke-case-detail-grid">';
		$html .= self::build_case_detail_card( $labels['background'], $background );
		$html .= self::build_case_detail_card( $labels['supply'], $supply );
		$html .= self::build_case_detail_card( $labels['technical'], $technical );
		$html .= self::build_case_detail_card( $labels['result'], $result );
		$html .= self::build_case_link_card( $labels['related_lines'], $related_lines );
		$html .= self::build_case_link_card( $labels['related_cases'], $related_cases );
		$html .= '</div></div></section>';

		return $html;
	}

	/**
	 * Build a case-detail fact card.
	 *
	 * @param string $title Card title.
	 * @param array  $items Body items.
	 * @return string
	 */
	protected static function build_case_detail_card( $title, $items ) {
		$items = array_filter( array_map( 'trim', (array) $items ) );
		if ( empty( $items ) ) {
			return '';
		}

		return '<article class="yke-case-detail-card"><h3>' . esc_html( $title ) . '</h3>' . self::build_marketing_fact_list( $items, 'yke-case-detail-list' ) . '</article>';
	}

	/**
	 * Build a case-detail link card.
	 *
	 * @param string $title Card title.
	 * @param array  $items Link items.
	 * @return string
	 */
	protected static function build_case_link_card( $title, $items ) {
		$items = array_filter( (array) $items );
		if ( empty( $items ) ) {
			return '';
		}

		$html  = '<article class="yke-case-detail-card"><h3>' . esc_html( $title ) . '</h3><ul class="yke-case-link-list">';
		foreach ( $items as $item ) {
			$html .= '<li>' . wp_kses_post( $item ) . '</li>';
		}
		$html .= '</ul></article>';

		return $html;
	}

	/**
	 * Build related case-study links.
	 *
	 * @param int    $post_id Current post ID.
	 * @param string $lang    Language slug.
	 * @return array
	 */
	protected static function build_related_case_links( $post_id, $lang ) {
		$product_slugs = wp_get_post_terms( $post_id, 'product_line', array( 'fields' => 'slugs' ) );
		if ( empty( $product_slugs ) ) {
			return array();
		}

		$posts = get_posts(
			array(
				'post_type'      => 'case_study',
				'post_status'    => 'publish',
				'post__not_in'   => array( $post_id ),
				'posts_per_page' => 2,
				'tax_query'      => array(
					array(
						'taxonomy' => 'product_line',
						'field'    => 'slug',
						'terms'    => $product_slugs,
					),
				),
				'lang'           => function_exists( 'pll_current_language' ) ? $lang : '',
			)
		);

		$links = array();
		foreach ( $posts as $post ) {
			$links[] = sprintf( '<a href="%1$s">%2$s</a>', esc_url( get_permalink( $post ) ), esc_html( get_the_title( $post ) ) );
		}

		return $links;
	}

	/**
	 * Build related product-line blocks. Each block lists the product line name (linked)
	 * and all its specific models (each linked to the model detail page).
	 *
	 * @param int    $post_id Current post ID.
	 * @param string $lang    Language slug.
	 * @return array Array of HTML strings, one per product line.
	 */
	protected static function build_related_product_links( $post_id, $lang ) {
		$terms = get_the_terms( $post_id, 'product_line' );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return array();
		}

		$is_zh  = ( 'zh' === $lang );
		$blocks = array();

		foreach ( $terms as $term ) {
			$page      = self::get_product_page_by_slug( $term->slug, $lang );
			$line_url  = $page ? get_permalink( $page ) : '#';
			$line_name = $page ? get_the_title( $page ) : $term->name;
			$models    = self::get_product_models_for_line( $term->slug );

			$html  = '<div class="yke-case-related-line-block">';
			$html .= '<a class="yke-case-related-line" href="' . esc_url( $line_url ) . '">';
			$html .= '<strong>' . esc_html( $line_name ) . '</strong>';
			$html .= '</a>';

			if ( ! empty( $models ) ) {
				$html .= '<ul class="yke-case-related-models">';
				foreach ( $models as $m ) {
					$m_name = $is_zh
						? ( isset( $m['name_zh'] ) ? $m['name_zh'] : ( isset( $m['name'] ) ? $m['name'] : $m['slug'] ) )
						: ( isset( $m['name'] ) ? $m['name'] : ( isset( $m['name_zh'] ) ? $m['name_zh'] : $m['slug'] ) );
					$m_url  = $line_url . $m['slug'] . '/';
					$html .= '<li><a href="' . esc_url( $m_url ) . '">&rsaquo; ' . esc_html( $m_name ) . '</a></li>';
				}
				$html .= '</ul>';
			}

			$html .= '</div>';

			$blocks[] = $html;
		}

		return $blocks;
	}

	/**
	 * Render a selection matrix to help buyers match product lines.
	 *
	 * @param string $lang Language slug.
	 * @return string
	 */
	protected static function render_product_selection_matrix( $lang ) {
		$is_zh = ( 'zh' === $lang );
		$rows  = $is_zh
			? array(
				array( 'title' => '工业园区 / 制造工厂', 'body' => '常见组合为高压开关柜 + 变压器 + 低压开关柜，重点关注母线方案、元件品牌和安装节奏。' ),
				array( 'title' => '工厂搬迁 / 低压配电改造', 'body' => '优先看低压开关柜，必要时联动预装式变电站或母线槽系统，重点在分批供货与现场配合。' ),
				array( 'title' => '公用事业替换 / 电网升级', 'body' => '优先看变压器和高压开关柜，重点在容量区间、审核标准、验货和交货稳定性。' ),
				array( 'title' => '光伏 / 储能 / 新能源并网', 'body' => '优先看新能源电气与预装式变电站，重点在并网资料、升压配置和项目审批配合。' ),
			)
			: array(
				array( 'title' => 'Industrial parks and manufacturing plants', 'body' => 'Typical combinations are MV switchgear + transformers + LV switchgear, with focus on busbar schemes, component brands, and installation timing.' ),
				array( 'title' => 'Factory relocation and LV distribution upgrades', 'body' => 'Start with LV switchgear, then consider prefabricated substations or busbar systems where phased delivery and site coordination matter.' ),
				array( 'title' => 'Utility replacement and grid upgrades', 'body' => 'Start with transformers and MV switchgear, with focus on capacity range, approval standards, inspection, and stable delivery.' ),
				array( 'title' => 'Solar, storage, and renewable grid-connection', 'body' => 'Start with new energy electrical and prefabricated substations, where grid-connection files, step-up configuration, and approval support matter.' ),
			);

		$html = '<div class="yke-trust-grid">';
		foreach ( $rows as $row ) {
			$html .= '<article class="yke-trust-card"><h3>' . esc_html( $row['title'] ) . '</h3><p>' . esc_html( $row['body'] ) . '</p></article>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render FAQs for marketing pages.
	 *
	 * @param array $items FAQ items.
	 * @return string
	 */
	protected static function render_marketing_faq( $items ) {
		if ( empty( $items ) ) {
			return '';
		}

		$html = '<div class="yke-faq">';
		foreach ( $items as $item ) {
			if ( empty( $item['q'] ) || empty( $item['a'] ) ) {
				continue;
			}
			$html .= '<details class="yke-faq-item"><summary>' . esc_html( $item['q'] ) . '</summary><div class="yke-faq-answer">' . esc_html( $item['a'] ) . '</div></details>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render a compact link strip for fallback navigation.
	 *
	 * @param array $links Links.
	 * @return string
	 */
	protected static function render_marketing_link_strip( $links ) {
		if ( empty( $links ) ) {
			return '';
		}

		$html = '<div class="yke-inline-cta yke-inline-cta--cards">';
		foreach ( $links as $link ) {
			if ( empty( $link['label'] ) || empty( $link['url'] ) ) {
				continue;
			}
			$html .= '<a class="yke-text-link" href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['label'] ) . '</a>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Build the visual-first products marketing page.
	 *
	 * @param string $lang Language slug.
	 * @return string
	 */
	protected static function build_visual_marketing_products_content( $lang ) {
		$is_zh         = ( 'zh' === $lang );
		$products_url  = self::get_seeded_page_url( 'products', $lang );
		$cases_url     = self::get_case_studies_url( $lang );
		$contact_url   = self::get_seeded_page_url( 'contact', $lang );
		$profiles      = self::get_product_line_profiles();
		$visuals       = self::get_marketing_visual_assets( $lang );
		$product_order = array( 'mv-switchgear', 'lv-switchgear', 'transformers', 'prefabricated-substations', 'new-energy-electrical' );

		$copy = $is_zh
			? array(
				'intro_title'     => '云科覆盖哪些出口配电产品，以及它们分别适合什么项目',
				'intro_body'      => '这一页不是按零散型号堆砌，而是按买家真实决策顺序组织：先看产品覆盖，再按场景与电压筛产品线，再确认交付边界、标准、案例和询盘所需信息。',
				'snapshot_title'  => '采购速览',
				'snapshot_items'  => array(
					'电压覆盖：0.4kV 至 35kV',
					'认证体系：CE / CCC / ISO 9001 / ISO 14001 / ISO 45001',
					'起订支持：单台起订，项目数量灵活',
					'标准交期：30 至 45 天，备货项目可更快',
					'验货支持：SGS / BV / Intertek',
					'售后承诺：18 个月质保，48 小时内确认责任归属',
				),
				'matrix_title'    => '先按项目条件判断产品线',
				'matrix_body'     => '海外买家通常先确认应用、再确认电压等级和交付方式。下面这组矩阵先帮您缩小选择范围，再进入具体型号。',
				'directory_title' => '五大产品线总览',
				'directory_body'  => '每条产品线都给出概述、代表型号、典型应用、标准范围和可定制项，方便判断是否值得进入下一轮技术沟通。',
				'delivery_title'  => '统一交付能力',
				'delivery_body'   => '不同产品线共用同一套出口支持逻辑：文件、测试、包装、验货、元器件品牌和售后边界都可以在报价前说清。',
				'delivery_items'  => array(
					'支持 ABB、Schneider、Eaton、Siemens、正泰、德力西等元器件品牌组合',
					'可提供单线图、接线图、出厂测试报告、装箱清单、英文操作手册和证书包',
					'支持 SGS、BV、Intertek 等第三方验货与项目审厂',
					'标准交期通常为 30 至 45 天，特殊品牌和定制规格会在报价前确认',
					'支持 OEM / ODM、项目标识、本地化资料和备件清单整理',
				),
				'proof_title'     => '按产品线关联的交付证明',
				'proof_body'      => '产品目录的价值，最终还是要落到交付记录。下面这些案例用来说明对应产品线已在哪些国家和场景中供货。',
				'proof_cta'       => '查看完整项目库',
				'faq_title'       => '采购常见问题',
				'faq_items'       => array(
					array( 'q' => '起订量怎么判断？', 'a' => '大多数产品线接受单台或小批量项目采购，整站或批量项目会根据数量给出更优价格与排产。' ),
					array( 'q' => '能否按指定品牌做元件？', 'a' => '可以。请在询盘时提供 Approved Vendor List 或偏好品牌，我们会同步确认可得性、交期和价格影响。' ),
					array( 'q' => '能否配第三方验货？', 'a' => '可以，SGS、BV、Intertek 等验货可在出厂前安排，并配套提供需要的测试和出货文件。' ),
					array( 'q' => '资料是否支持英文或本地化？', 'a' => '默认提供英文资料，项目需要时可同步整理中英对照、项目铭牌、装箱标识和审批资料。' ),
					array( 'q' => '售后与备件怎么处理？', 'a' => '标准质保为 18 个月，工作日目标 48 小时内确认责任归属，并可根据项目准备备件清单。' ),
				),
				'cta_title'       => '需要先做产品线匹配或选型建议？',
				'cta_body'        => '告诉我们目标国家、电压等级、应用场景、预估数量和执行标准，云科可以先给出匹配产品线与代表配置。',
				'cta_primary'     => '发起询盘',
				'cta_secondary'   => '查看案例',
				'explore'         => '查看详情',
				'applications'    => '典型应用',
				'models'          => '代表型号 / 方案',
				'customization'   => '定制范围',
			)
			: array(
				'intro_title'     => 'What YKE supplies for export power projects, and which line fits which type of job',
				'intro_body'      => 'This page is organized around how overseas buyers really screen a supplier: confirm product coverage first, narrow the line by application and voltage, then judge standards, delivery support, case proof, and the right inquiry path.',
				'snapshot_title'  => 'Buyer snapshot',
				'snapshot_items'  => array(
					'Voltage coverage: 0.4kV to 35kV',
					'Certifications: CE / CCC / ISO 9001 / ISO 14001 / ISO 45001',
					'MOQ: single-unit supply accepted when projects require it',
					'Lead time: 30 to 45 days standard, faster for stocked combinations',
					'Inspection: SGS, BV, and Intertek support available',
					'After-sales: 18-month standard warranty with 100% routine outgoing inspection',
				),
				'matrix_title'    => 'Match the line by project condition first',
				'matrix_body'     => 'Most overseas buyers decide in this order: application, voltage class, approval path, then commercial fit. This matrix helps narrow the right product family before the detailed quotation stage.',
				'directory_title' => 'Five product lines at a glance',
				'directory_body'  => 'Each line includes overview, representative models, typical applications, standards range, and customization scope so buyers can decide whether the line is worth deeper technical review.',
				'delivery_title'  => 'Shared delivery support across all lines',
				'delivery_body'   => 'What matters is not only the hardware, but whether the same supplier can also support drawings, tests, packing, inspection, branding, and after-sales expectations before the PO.',
				'delivery_items'  => array(
					'Flexible component brand combinations including ABB, Schneider, Eaton, Siemens, Chint, and Delixi',
					'Single-line diagrams, wiring drawings, routine test reports, packing lists, manuals, and certificate packs',
					'Factory inspection support for SGS, BV, Intertek, and similar agencies',
					'Typical lead time of 30 to 45 days, with custom brands and special ratings confirmed before order',
					'OEM / ODM, project labeling, localized documents, and spare-part list preparation',
				),
				'proof_title'     => 'Delivery proof linked to product lines',
				'proof_body'      => 'A directory becomes more credible when it points back to delivered work. These cases show where the corresponding lines have already been supplied.',
				'proof_cta'       => 'View full case library',
				'faq_title'       => 'Procurement FAQ',
				'faq_items'       => array(
					array( 'q' => 'What is the typical MOQ?', 'a' => 'Most lines can start from single-unit or small-batch project supply, while full packages and volume orders unlock stronger pricing and production priority.' ),
					array( 'q' => 'Can YKE follow our approved brand list?', 'a' => 'Yes. Share the approved vendor list or preferred brands in the inquiry, and YKE will confirm availability, lead time, and commercial impact before quotation.' ),
					array( 'q' => 'Can third-party inspection be arranged?', 'a' => 'Yes. SGS, BV, Intertek, and similar inspections can be arranged before shipment with the required outgoing test and packing documents.' ),
					array( 'q' => 'Are English and localized files available?', 'a' => 'English files are standard. Project nameplates, bilingual packs, packing marks, and approval documents can be prepared when needed.' ),
					array( 'q' => 'How are after-sales and spare parts handled?', 'a' => 'The standard warranty target is 18 months, with responsibility confirmation targeted within 48 working hours and spare-part planning available by project.' ),
				),
				'cta_title'       => 'Need help matching a product line before requesting a formal quote?',
				'cta_body'        => 'Send the destination market, voltage level, application, estimated quantity, and standards. YKE can reply with a recommended product path and representative configuration.',
				'cta_primary'     => 'Start an inquiry',
				'cta_secondary'   => 'Browse case studies',
				'explore'         => 'Explore line',
				'applications'    => 'Typical applications',
				'models'          => 'Representative models',
				'customization'   => 'Customization scope',
			);

		$html  = '<section class="yke-marketing-shell">';
		$html .= '<section class="yke-marketing-band"><div class="yke-marketing-band__grid yke-marketing-band__grid--media">';
		$html .= '<div class="yke-marketing-copy yke-marketing-copy--stacked"><h2>' . esc_html( $copy['intro_title'] ) . '</h2><p>' . esc_html( $copy['intro_body'] ) . '</p>';
		$html .= '<aside class="yke-marketing-panel yke-marketing-panel--tight yke-marketing-copy__panel"><h3>' . esc_html( $copy['snapshot_title'] ) . '</h3>' . self::build_simple_line_list( $copy['snapshot_items'] ) . '</aside></div>';
		$html .= self::render_marketing_figure( $visuals['products']['hero'], 'yke-marketing-visual yke-marketing-visual--hero' );
		$html .= '</div></section>';

		$html .= '<section class="yke-marketing-band yke-marketing-band--light"><div class="yke-section-head"><h2>' . esc_html( $copy['matrix_title'] ) . '</h2><p>' . esc_html( $copy['matrix_body'] ) . '</p></div>';
		$html .= self::render_product_selection_matrix( $lang ) . '</section>';

		$html .= '<section class="yke-marketing-band"><div class="yke-section-head"><h2>' . esc_html( $copy['directory_title'] ) . '</h2><p>' . esc_html( $copy['directory_body'] ) . '</p></div>';
		$html .= '<div class="yke-directory-grid yke-directory-grid--products">';
		foreach ( $product_order as $slug ) {
			if ( empty( $profiles[ $slug ][ $lang ] ) ) {
				continue;
			}
			$profile = $profiles[ $slug ][ $lang ];
			$page    = self::get_product_page_by_slug( $slug, $lang );
			$url     = $page ? get_permalink( $page ) : $products_url;
			$title   = self::get_product_display_title( $slug, $lang );
			$asset   = isset( $visuals['products']['directory'][ $slug ] ) ? $visuals['products']['directory'][ $slug ] : $visuals['products']['hero'];

			$html .= '<article class="yke-directory-card yke-directory-card--visual">';
			$html .= self::render_marketing_figure( $asset, 'yke-directory-card__media' );
			$html .= '<div class="yke-directory-card__content">';
			$html .= '<div class="yke-directory-card__head"><h3>' . esc_html( $title ) . '</h3><p>' . esc_html( $profile['overview'] ) . '</p></div>';
			$html .= '<div class="yke-directory-card__body">';
			$html .= '<div><h4>' . esc_html( $copy['applications'] ) . '</h4>' . self::build_simple_line_list( array_slice( $profile['applications'], 0, 3 ) ) . '</div>';
			$html .= '<div><h4>' . esc_html( $copy['models'] ) . '</h4>' . self::build_simple_line_list( array_slice( $profile['models'], 0, 3 ) ) . '</div>';
			$html .= '<div><h4>' . esc_html( $copy['customization'] ) . '</h4>' . self::build_simple_line_list( array_slice( $profile['customization'], 0, 3 ) ) . '</div>';
			$html .= '</div>';
			$html .= '<div class="yke-directory-card__foot"><span>' . esc_html( $profile['standards'] ) . '</span><a href="' . esc_url( $url ) . '">' . esc_html( $copy['explore'] ) . '</a></div>';
			$html .= '</div></article>';
		}
		$html .= '</div></section>';

		$html .= '<section class="yke-marketing-band yke-marketing-band--light"><div class="yke-marketing-band__grid yke-marketing-band__grid--media">';
		$html .= '<div class="yke-marketing-copy"><h2>' . esc_html( $copy['delivery_title'] ) . '</h2><p>' . esc_html( $copy['delivery_body'] ) . '</p>' . self::build_marketing_fact_list( $copy['delivery_items'] ) . '</div>';
		$html .= '<div class="yke-marketing-stack">';
		$html .= self::render_marketing_figure( $visuals['about']['quality'], 'yke-marketing-visual yke-marketing-visual--quality' );
		$html .= '<aside class="yke-marketing-panel"><div class="yke-contact-card__compact">[yke_contact_details show_commitments="1" show_certifications="1"]</div></aside>';
		$html .= '</div></div></section>';

		$html .= '<section class="yke-marketing-band"><div class="yke-section-head"><h2>' . esc_html( $copy['proof_title'] ) . '</h2><p>' . esc_html( $copy['proof_body'] ) . '</p></div>';
		$html .= self::render_case_study_teaser_cards( $lang, 3 );
		$html .= '<div class="yke-inline-cta"><a class="yke-text-link" href="' . esc_url( $cases_url ) . '">' . esc_html( $copy['proof_cta'] ) . '</a></div></section>';

		$html .= '<section class="yke-marketing-band yke-marketing-band--light"><div class="yke-section-head"><h2>' . esc_html( $copy['faq_title'] ) . '</h2></div>';
		$html .= self::render_marketing_faq( $copy['faq_items'] ) . '</section>';

		$html .= '<section class="yke-decision-strip"><div><h2>' . esc_html( $copy['cta_title'] ) . '</h2><p>' . esc_html( $copy['cta_body'] ) . '</p></div>';
		$html .= '<div class="yke-decision-strip__actions"><a class="yke-btn-primary" href="' . esc_url( $contact_url ) . '">' . esc_html( $copy['cta_primary'] ) . '</a><a class="yke-btn-outline yke-btn-outline--dark" href="' . esc_url( $cases_url ) . '">' . esc_html( $copy['cta_secondary'] ) . '</a></div></section>';
		$html .= '</section>';

		return do_shortcode( $html );
	}

	/**
	 * Build the visual-first about marketing page.
	 *
	 * @param string $lang Language slug.
	 * @return string
	 */
	protected static function build_visual_marketing_about_content( $lang ) {
		$is_zh       = ( 'zh' === $lang );
		$cases_url   = self::get_case_studies_url( $lang );
		$contact_url = self::get_seeded_page_url( 'contact', $lang );
		$visuals     = self::get_marketing_visual_assets( $lang );

		$copy = $is_zh
			? array(
				'intro_title'         => '广州云科电气设备有限公司',
				'intro_body'          => '一家专注于中低压配电设备研发、制造与出口的企业，产品覆盖 0.4kV 至 35kV 电压等级，已向全球 100+ 国家和地区配套交付，服务于工业、公用事业、基础设施与新能源项目。',
				'proof_title'         => '核心交付能力',
				'proof_points'        => array(
					'产品覆盖 0.4kV 至 35kV，5 大产品线服务工业、公用事业、基础设施与新能源项目',
					'按 IEC 标准组织生产，配套 FAT 记录与第三方验货安排',
					'提供单台紧急采购与分阶段大项目交付两种模式',
					'18 个月标准质保，出厂 100% 例行检验',
				),
				'basics_title'        => '企业基本面',
				'basics_body'         => '云科电气面向海外采购、EPC 总包与渠道伙伴，提供从产品选型到出口交付的全流程配电设备供应。',
				'basics_cards'        => array(
					array( 'title' => '产品覆盖与定位', 'body' => '产品覆盖 0.4kV 至 35kV，5 大产品线涵盖高压开关柜、低压开关柜、变压器、预装式变电站与新能源电气系统，服务工业、公用事业、基础设施和新能源场景。' ),
				array( 'title' => '生产与检验纪律', 'body' => '出厂前完成 100% 例行检验，配套 FAT 记录与第三方验货安排，按 IEC 标准组织生产。' ),
				array( 'title' => '标准与认证', 'body' => '按 IEC 标准交付，配套英文或中英对照资料；安排 SGS、BV、Intertek 等第三方机构到厂验货。' ),
				array( 'title' => '出口市场经验', 'body' => '已向 100+ 国家和地区配套交付，重点覆盖中东、东南亚、非洲与拉美新兴市场。' ),
				),
				'workflow_title'      => '生产、质控与文件控制',
				'workflow_body'       => '从下单到装运，云科电气提供一套可追溯的标准化流程，覆盖生产排期、出厂检验、出口包装与资料归档。',
				'workflow_items'      => array(
					'按产品线和项目要求组织生产，围绕主母线方案、元器件品牌、环境适配和装运节奏确认配置',
					'出厂前完成例行测试、外观检查和关键记录归档，便于 FAT 审阅或第三方验货',
					'安排 SGS、BV、Intertek 等机构到厂验货，并配套测试、装箱和证书文件包',
					'提供英文或中英对照项目文件，包括图纸、铭牌、装箱单、说明书和认证资料',
				),
				'cap_title'           => '能力结构',
				'cap_body'            => '围绕出口项目交付，云科电气在技术选型、资料支持、OEM/ODM 协同和环境适配四个维度提供完整配合。',
				'cap_cards'           => array(
					array( 'title' => '技术选型支持', 'body' => '围绕电压等级、应用场景、执行标准和项目边界，先匹配产品线，再给代表配置。' ),
					array( 'title' => '项目资料支持', 'body' => '报价前后均可整理图纸、测试、认证和装运资料，减少客户反复追问。' ),
					array( 'title' => 'OEM / ODM 配合', 'body' => '承接项目铭牌、包装标识、品牌要求和本地化资料协同处理。' ),
					array( 'title' => '环境适配能力', 'body' => '针对高温、多尘、潮湿、高海拔和并网审批要求，调整配置与验证边界。' ),
				),
				'market_title'        => '市场与项目覆盖',
				'market_body'         => '云科电气的出口交付记录覆盖中东、东南亚、非洲与拉美新兴市场，在工业配电、电网升级和新能源项目场景积累了成熟经验。',
				'gallery_title'       => '工厂实景与市场覆盖',
				'gallery_body'        => '以下是云科电气的生产车间实景与出口市场覆盖。',
				'case_title'          => '相关项目记录',
				'case_body'           => '以下是云科电气近年的代表性出口项目。',
				'case_cta'            => '查看完整项目案例库',
				'cta_title'           => '准备确认云科是否适合你的市场、项目或渠道方向？',
				'cta_body'            => '请发送目标国家、产品范围、项目阶段和关键标准要求，我们的团队会跟进回复。',
				'cta_button'          => '联系云科',
			)
			: array(
				'intro_title'         => 'Guangzhou YKE Electric Equipment Co., Ltd.',
				'intro_body'          => 'A manufacturer focused on R&D, production, and export of medium- and low-voltage power distribution equipment. Product coverage spans 0.4kV to 35kV across five product lines, with delivery records in over 100 countries and regions serving industrial, utility, infrastructure, and renewable projects.',
				'proof_title'         => 'Core delivery capabilities',
				'proof_points'        => array(
					'0.4kV to 35kV coverage across industrial, utility, infrastructure, and renewable applications',
					'IEC-aligned production with FAT records and third-party inspection arrangements',
					'Single-unit urgent supply and staged delivery for large-scale projects',
					'18-month standard warranty with 100% routine outgoing inspection',
				),
				'basics_title'        => 'Company fundamentals',
				'basics_body'         => 'YKE Electric supplies overseas buyers, EPC contractors, and regional partners with end-to-end power distribution equipment, from product matching to export delivery.',
				'basics_cards'        => array(
					array( 'title' => 'Product coverage and focus', 'body' => '0.4kV to 35kV coverage across five product lines: medium-voltage switchgear, low-voltage switchgear, transformers, prefabricated substations, and new energy electrical systems.' ),
					array( 'title' => 'Production and inspection discipline', 'body' => '100% routine outgoing inspection before shipment, with FAT records and third-party inspection organized under IEC standards.' ),
					array( 'title' => 'Standards and certifications', 'body' => 'IEC-aligned delivery with English or bilingual documentation; SGS, BV, and Intertek third-party factory inspection arranged on request.' ),
					array( 'title' => 'Export market experience', 'body' => 'Delivery records across 100+ countries and regions, with a focus on the Middle East, Southeast Asia, Africa, and Latin America.' ),
				),
				'workflow_title'      => 'Production, quality control, and documentation',
				'workflow_body'       => 'From order to shipment, YKE Electric follows a traceable, standardized workflow covering production planning, outgoing inspection, export packing, and documentation archiving.',
				'workflow_items'      => array(
					'Production planning organized around project requirements including voltage class, busbar scheme, component brand, environment, and delivery sequence',
					'Routine outgoing tests, appearance checks, and key records completed before shipment for client review or FAT support',
					'Factory inspection arranged for SGS, BV, Intertek, and similar agencies with matching test, packing, and certificate documentation',
					'English or bilingual project files prepared including drawings, nameplates, manuals, packing lists, and certificate packs',
				),
				'cap_title'           => 'Capability structure',
				'cap_body'            => 'Built around export project delivery, YKE Electric provides full-cycle support across four dimensions: technical matching, documentation, OEM/ODM coordination, and environmental adaptation.',
				'cap_cards'           => array(
					array( 'title' => 'Technical product matching', 'body' => 'YKE narrows the right product line first, then moves into representative configuration based on application, voltage, and standards.' ),
					array( 'title' => 'Project documentation support', 'body' => 'Drawings, testing files, certifications, and shipment documents are structured early instead of becoming a late-stage scramble.' ),
					array( 'title' => 'OEM / ODM coordination', 'body' => 'Branding, labeling, packaging marks, and localized files are handled as part of export delivery.' ),
					array( 'title' => 'Environmental adaptation', 'body' => 'Configurations are adjusted for heat, dust, humidity, altitude, and grid-connection conditions.' ),
				),
				'market_title'        => 'Market and project coverage',
				'market_body'         => 'YKE Electric’s export delivery records span the Middle East, Southeast Asia, Africa, and Latin America, with mature experience in industrial distribution, grid upgrades, and renewable project scenarios.',
				'gallery_title'       => 'Certificates, factory, and delivery proof',
				'gallery_body'        => 'Below are factory scenes, test records, market coverage, and project-site evidence from YKE Electric.',
				'case_title'          => 'Related project records',
				'case_body'           => 'Below are representative export projects delivered by YKE Electric in recent years.',
				'case_cta'            => 'Review the full case library',
				'cta_title'           => 'Need to confirm whether YKE fits your market, project, or channel direction?',
				'cta_body'            => 'Send the target market, product scope, project stage, and required standards. Our team will follow up with a practical response.',
				'cta_button'          => 'Contact YKE',
			);

		$html  = '<section class="yke-marketing-shell">';
		$html .= '<section class="yke-marketing-band"><div class="yke-marketing-band__grid yke-marketing-band__grid--media">';
		$html .= '<div class="yke-marketing-copy yke-marketing-copy--stacked"><h2>' . esc_html( $copy['intro_title'] ) . '</h2><p>' . esc_html( $copy['intro_body'] ) . '</p>';
		$html .= '<aside class="yke-marketing-panel yke-marketing-copy__panel"><h3>' . esc_html( $copy['proof_title'] ) . '</h3>' . self::build_marketing_fact_list( $copy['proof_points'] ) . '</aside></div>';
		$html .= self::render_marketing_figure( $visuals['about']['intro'], 'yke-marketing-visual yke-marketing-visual--hero' );
		$html .= '</div></section>';

		$html .= '<section class="yke-marketing-band yke-marketing-band--light"><div class="yke-section-head"><h2>' . esc_html( $copy['basics_title'] ) . '</h2><p>' . esc_html( $copy['basics_body'] ) . '</p></div>';
		$html .= self::render_company_fact_cards( $copy['basics_cards'] ) . '</section>';

		$html .= '<section class="yke-marketing-band"><div class="yke-marketing-band__grid yke-marketing-band__grid--media">';
		$html .= self::render_marketing_figure( $visuals['about']['capability'], 'yke-marketing-visual yke-marketing-visual--capability' );
		$html .= '<div class="yke-marketing-copy"><h2>' . esc_html( $copy['workflow_title'] ) . '</h2><p>' . esc_html( $copy['workflow_body'] ) . '</p>' . self::build_marketing_fact_list( $copy['workflow_items'] ) . '</div>';
		$html .= '</div></section>';

		$html .= '<section class="yke-marketing-band yke-marketing-band--light"><div class="yke-section-head"><h2>' . esc_html( $copy['cap_title'] ) . '</h2><p>' . esc_html( $copy['cap_body'] ) . '</p></div>';
		$html .= self::render_company_fact_cards( $copy['cap_cards'] ) . '</section>';

		$html .= '<section class="yke-marketing-band"><div class="yke-section-head"><h2>' . esc_html( $copy['market_title'] ) . '</h2><p>' . esc_html( $copy['market_body'] ) . '</p></div>';
		$html .= self::render_market_focus_cards( $lang ) . '</section>';

		$html .= '<section class="yke-marketing-band yke-marketing-band--light"><div class="yke-section-head"><h2>' . esc_html( $copy['gallery_title'] ) . '</h2><p>' . esc_html( $copy['gallery_body'] ) . '</p></div>';
		$html .= self::render_marketing_scene_gallery( $visuals['about']['gallery'], 'yke-scene-gallery yke-scene-gallery--proof' );
		$html .= '</section>';

		$html .= '<section class="yke-marketing-band"><div class="yke-section-head"><h2>' . esc_html( $copy['case_title'] ) . '</h2><p>' . esc_html( $copy['case_body'] ) . '</p></div>';
		$html .= self::render_case_study_teaser_cards( $lang, 3 );
		$html .= '<div class="yke-inline-cta"><a class="yke-text-link" href="' . esc_url( $cases_url ) . '">' . esc_html( $copy['case_cta'] ) . '</a></div></section>';
		$html .= '</section>';

		return do_shortcode( $html );
	}

	/**
	 * Build the visual-first distributor marketing page.
	 *
	 * @param string $lang Language slug.
	 * @return string
	 */
	protected static function build_visual_marketing_distributor_content( $lang ) {
		$is_zh       = ( 'zh' === $lang );
		$contact_url = self::get_seeded_page_url( 'contact', $lang );
		$cases_url   = self::get_case_studies_url( $lang );
		$visuals     = self::get_marketing_visual_assets( $lang );

		$copy = $is_zh
			? array(
				'intro_title'   => '区域代理合作',
				'intro_body'    => '云科电气面向中东、东南亚、非洲与拉美新兴市场招募区域代理伙伴，提供建立在五大产品线与 100+ 国家出口经验基础上的渠道合作模式。',
				'who_title'     => '适配的合作方',
				'who_points'    => array(
					'已有电气产品渠道或工程客户基础的本地分销商',
					'希望增加高压、低压、变压器或新能源电气产品线的公司',
					'能够负责本地商务推进、基础跟单与客户关系维护的团队',
				),
				'why_title'     => '为什么选择云科',
				'why_cards'     => array(
					array( 'title' => '产品线覆盖完整', 'body' => '从高压、低压、变压器到预装式变电站和新能源电气，便于面向不同客户类型做组合销售。' ),
					array( 'title' => '出口项目经验可复用', 'body' => '云科已向 100+ 国家和地区配套交付，区域经验、项目资料和交付记录可直接辅助渠道沟通。' ),
					array( 'title' => '资料与报价支持', 'body' => '提供报价、投标资料、产品图片、案例页和基础技术文件，围绕合作推进同步整理。' ),
					array( 'title' => '售后路径明确', 'body' => '售后责任、备件、问题升级和项目配合路径在合作沟通中优先说明。' ),
				),
				'support_title' => '合作支持结构',
				'support_cards' => array(
					array( 'key' => 'territory', 'title' => '区域与渠道规则', 'body' => '根据合作深度约定区域保护、项目报备机制与渠道边界。' ),
					array( 'key' => 'project', 'title' => '项目与技术配合', 'body' => '提供产品选型、规格确认、投标资料、竞品对比和交付节奏建议。' ),
					array( 'key' => 'materials', 'title' => '市场推进资料', 'body' => '提供多语种画册、案例资料、图片素材和面向客户的基础介绍材料。' ),
					array( 'key' => 'after_sales', 'title' => '售后与升级响应', 'body' => '围绕售后责任归属、替换件、技术答疑和问题升级建立明确路径。' ),
				),
				'process_title' => '合作启动流程',
				'process_steps' => array(
					'提交市场范围、客户类型和现有产品结构',
					'确认重点产品线、价格带和支持方式',
					'进入样品、项目配合或年度采购节奏验证',
				),
				'proof_links_title' => '合作证明与复用资料',
				'proof_links_body'  => '以下页面与资料可直接转发给客户，用于渠道沟通与项目推进。',
				'proof_title'   => '交付与市场证明',
				'proof_body'    => '以下是云科电气近年的代表性出口项目记录。',
				'proof_links'   => array(
					array( 'label' => '查看产品总览', 'kind' => 'products' ),
					array( 'label' => '查看项目案例', 'kind' => 'cases' ),
					array( 'label' => '查看关于云科', 'kind' => 'about' ),
				),
				'faq_title'     => '合作常见问题',
				'faq_items'     => array(
					array( 'q' => '是否承诺独家代理？', 'a' => '区域保护、项目报备和合作边界根据市场情况与合作深度沟通确认。' ),
					array( 'q' => '是否支持贴牌或 OEM / ODM？', 'a' => '支持。可围绕品牌标识、铭牌、包装和资料本地化要求协同处理，具体范围与产品线和交期一起确认。' ),
					array( 'q' => '渠道伙伴可以拿到哪些资料？', 'a' => '包括英文或多语种产品介绍、案例摘要、产品图片、基础技术资料，以及报价和投标阶段需要的补充文件。' ),
					array( 'q' => '售后和备件怎么配合？', 'a' => '会明确问题反馈路径、责任确认时点、备件清单准备和必要时的技术升级响应机制。' ),
				),
				'cta_title'     => '准备讨论区域合作、项目跟进或 OEM / ODM 路径？',
				'cta_body'      => '请发送目标国家、已有客户类型和优先产品线，我们的团队会跟进回复。',
				'cta_primary'   => '提交合作咨询',
				'cta_secondary' => '先看案例',
			)
			: array(
				'intro_title'   => 'Regional Distributor Partnership',
				'intro_body'    => 'YKE Electric recruits regional distributor partners across the Middle East, Southeast Asia, Africa, and Latin America, offering a channel partnership model built on five product lines and export experience across 100+ countries and regions.',
				'who_title'     => 'Best-fit partner profile',
				'who_points'    => array(
					'Local distributors with an existing electrical, industrial, or project customer base',
					'Companies adding MV, LV, transformer, or renewable electrical lines to their portfolio',
					'Teams able to manage local commercial follow-up and maintain customer relationships',
				),
				'why_title'     => 'Why choose YKE',
				'why_cards'     => array(
					array( 'title' => 'Complete product coverage', 'body' => 'MV, LV, transformers, prefabricated substations, and new energy electrical lines make cross-selling easier across buyer types.' ),
					array( 'title' => 'Reusable export experience', 'body' => 'YKE has delivered to 100+ countries and regions. Regional experience, case records, and delivery references directly support channel communication.' ),
					array( 'title' => 'Quotation and document support', 'body' => 'Pricing, bid support, product images, case references, and core technical files are prepared in step with partnership progress.' ),
					array( 'title' => 'Visible after-sales path', 'body' => 'Issue ownership, spare-part planning, and escalation logic are part of the conversation early rather than left vague.' ),
				),
				'support_title' => 'Support structure',
				'support_cards' => array(
					array( 'key' => 'territory', 'title' => 'Territory and channel rules', 'body' => 'Regional protection, project registration logic, and channel boundaries are discussed early to reduce conflict later.' ),
					array( 'key' => 'project', 'title' => 'Technical and project cooperation', 'body' => 'YKE provides product matching, specification review, bid packages, competitor comparison, and practical delivery planning.' ),
					array( 'key' => 'materials', 'title' => 'Market development materials', 'body' => 'Partners receive multilingual brochures, project references, product images, and customer-facing introductions.' ),
					array( 'key' => 'after_sales', 'title' => 'After-sales escalation path', 'body' => 'Issue ownership, spare-part coordination, technical clarification, and escalation response are handled through a defined route.' ),
				),
				'process_title' => 'How cooperation starts',
				'process_steps' => array(
					'Share the target market, customer mix, and current product portfolio',
					'Align on priority product lines, price positioning, and support expectations',
					'Move into sample supply, project cooperation, or an annual purchasing rhythm',
				),
				'proof_links_title' => 'Reusable partnership proof',
				'proof_links_body'  => 'The pages and records below can be forwarded directly to customers during market development.',
				'proof_title'   => 'Delivery and market proof',
				'proof_body'    => 'Below are representative export projects delivered by YKE Electric in recent years.',
				'proof_links'   => array(
					array( 'label' => 'Review products', 'kind' => 'products' ),
					array( 'label' => 'Review case studies', 'kind' => 'cases' ),
					array( 'label' => 'Review company profile', 'kind' => 'about' ),
				),
				'faq_title'     => 'Partnership FAQ',
				'faq_items'     => array(
					array( 'q' => 'Does YKE promise exclusivity by default?', 'a' => 'Territory protection, project registration, and channel boundaries are discussed according to market conditions and cooperation depth.' ),
					array( 'q' => 'Is OEM / ODM support available?', 'a' => 'Yes. Branding, nameplates, packaging marks, and localized documents can be coordinated, with scope confirmed alongside product line and lead time.' ),
					array( 'q' => 'What materials can a partner receive?', 'a' => 'Product overviews, case-study summaries, product images, core technical files, and additional quotation or bid support content as the discussion deepens.' ),
					array( 'q' => 'How are after-sales and spare parts handled?', 'a' => 'Issue reporting, responsibility confirmation timing, spare-part planning, and escalation response are established through a defined path.' ),
				),
				'cta_title'     => 'Ready to discuss regional cooperation, project support, or OEM / ODM delivery paths?',
				'cta_body'      => 'Send the target country, current customer profile, and preferred product lines. Our team will follow up with a tailored response.',
				'cta_primary'   => 'Start a partnership inquiry',
				'cta_secondary' => 'Review case studies',
			);

		$html  = '<section class="yke-marketing-shell">';
		$html .= '<section class="yke-marketing-band"><div class="yke-marketing-band__grid yke-marketing-band__grid--media">';
		$html .= '<div class="yke-marketing-copy yke-marketing-copy--stacked"><h2>' . esc_html( $copy['intro_title'] ) . '</h2><p>' . esc_html( $copy['intro_body'] ) . '</p>';
		$html .= '<aside class="yke-marketing-panel yke-marketing-copy__panel"><h3>' . esc_html( $copy['who_title'] ) . '</h3>' . self::build_simple_line_list( $copy['who_points'] ) . '</aside></div>';
		$html .= self::render_marketing_figure( $visuals['distributor']['intro'], 'yke-marketing-visual yke-marketing-visual--hero' );
		$html .= '</div></section>';

		$html .= '<section class="yke-marketing-band yke-marketing-band--light"><div class="yke-section-head"><h2>' . esc_html( $copy['why_title'] ) . '</h2></div>';
		$html .= self::render_company_fact_cards( $copy['why_cards'] ) . '</section>';

		$html .= '<section class="yke-marketing-band"><div class="yke-section-head"><h2>' . esc_html( $copy['support_title'] ) . '</h2></div><div class="yke-trust-grid yke-trust-grid--visual">';
		foreach ( $copy['support_cards'] as $card ) {
			$asset = $visuals['distributor']['support_cards'][ $card['key'] ];
			$html .= '<article class="yke-trust-card yke-trust-card--visual">';
			$html .= self::render_marketing_figure( $asset, 'yke-trust-card__media' );
			$html .= '<div class="yke-trust-card__body"><h3>' . esc_html( $card['title'] ) . '</h3><p>' . esc_html( $card['body'] ) . '</p></div></article>';
		}
		$html .= '</div></section>';

		$html .= '<section class="yke-marketing-band"><div class="yke-marketing-band__grid yke-marketing-band__grid--media">';
		$html .= '<div class="yke-marketing-copy"><h2>' . esc_html( $copy['process_title'] ) . '</h2><ol class="yke-process-list">';
		foreach ( $copy['process_steps'] as $step ) {
			$html .= '<li>' . esc_html( $step ) . '</li>';
		}
		$html .= '</ol></div>';
		$html .= '<div class="yke-marketing-stack">' . self::render_marketing_figure( $visuals['distributor']['process'], 'yke-marketing-visual yke-marketing-visual--support' );
		$html .= '<aside class="yke-marketing-panel yke-marketing-panel--form"><h3>' . esc_html( $copy['cta_primary'] ) . '</h3><p>' . esc_html( $copy['cta_body'] ) . '</p>[yke_inquiry_form product_interest="' . esc_attr( $is_zh ? '代理合作咨询' : 'Distributor Partnership' ) . '"]</aside></div>';
		$html .= '</div></section>';

		$links = array();
		foreach ( $copy['proof_links'] as $link ) {
			if ( 'products' === $link['kind'] ) {
				$links[] = array( 'label' => $link['label'], 'url' => self::get_seeded_page_url( 'products', $lang ) );
			} elseif ( 'cases' === $link['kind'] ) {
				$links[] = array( 'label' => $link['label'], 'url' => $cases_url );
			} elseif ( 'about' === $link['kind'] ) {
				$links[] = array( 'label' => $link['label'], 'url' => self::get_seeded_page_url( 'about-yke', $lang ) );
			}
		}

		$html .= '<section class="yke-marketing-band yke-marketing-band--light"><div class="yke-section-head"><h2>' . esc_html( $copy['proof_links_title'] ) . '</h2><p>' . esc_html( $copy['proof_links_body'] ) . '</p></div>';
		$html .= self::render_marketing_link_strip( $links ) . '</section>';

		$html .= '<section class="yke-marketing-band"><div class="yke-section-head"><h2>' . esc_html( $copy['proof_title'] ) . '</h2><p>' . esc_html( $copy['proof_body'] ) . '</p></div>';
		$html .= self::render_case_study_teaser_cards( $lang, 2 );
		$html .= '<div class="yke-inline-cta"><a class="yke-text-link" href="' . esc_url( $cases_url ) . '">' . esc_html( $copy['cta_secondary'] ) . '</a></div></section>';

		$html .= '<section class="yke-marketing-band yke-marketing-band--light"><div class="yke-section-head"><h2>' . esc_html( $copy['faq_title'] ) . '</h2></div>';
		$html .= self::render_marketing_faq( $copy['faq_items'] ) . '</section>';

		$html .= '<section class="yke-decision-strip"><div><h2>' . esc_html( $copy['cta_title'] ) . '</h2><p>' . esc_html( $copy['cta_body'] ) . '</p></div>';
		$html .= '<div class="yke-decision-strip__actions"><a class="yke-btn-primary" href="' . esc_url( $contact_url ) . '">' . esc_html( $copy['cta_primary'] ) . '</a><a class="yke-btn-outline yke-btn-outline--dark" href="' . esc_url( $cases_url ) . '">' . esc_html( $copy['cta_secondary'] ) . '</a></div></section>';
		$html .= '</section>';

		return do_shortcode( $html );
	}

	/**
	 * Build the visual-first contact marketing page.
	 *
	 * @param string $lang Language slug.
	 * @return string
	 */
	protected static function build_visual_marketing_contact_content( $lang ) {
		$is_zh = ( 'zh' === $lang );

		$copy = $is_zh
			? array(
				'title'      => '联系云科',
				'form_title' => '发送询盘',
				'company'    => '广州云科电气设备有限公司',
			)
			: array(
				'title'      => 'Contact YKE',
				'form_title' => 'Send an Inquiry',
				'company'    => self::get_setting( 'company_name' ),
			);

		// 获取联系方式数据（参考 Pineele icon-list 结构）
		$email    = self::get_setting( 'contact_email' );
		$whatsapp = self::get_contact_setting_value( 'contact_whatsapp' );
		$address  = self::get_contact_setting_value( 'contact_address' );
		$hours    = self::get_setting( 'business_hours' );

		// SVG 图标（参考 Pineele 用的 Font Awesome 图标）
		$icon_address = '<svg aria-hidden="true" viewBox="0 0 384 512" xmlns="http://www.w3.org/2000/svg"><path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z"/></svg>';
		$icon_whatsapp = '<svg aria-hidden="true" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg>';
		$icon_email    = '<svg aria-hidden="true" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M502.3 190.8c3.9-3.1 9.7-.2 9.7 4.7V400c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V195.6c0-5 5.7-7.8 9.7-4.7 22.4 17.4 52.1 39.5 154.1 113.6 21.1 15.4 56.7 47.8 92.2 47.6 35.7.3 72-32.8 92.3-47.6 102-74.1 131.6-96.3 154-113.7zM256 320c23.2.4 56.6-29.2 73.4-41.4 132.7-96.3 142.8-104.7 173.4-128.7 5.8-4.5 9.2-11.5 9.2-18.9v-19c0-26.5-21.5-48-48-48H48C21.5 64 0 85.5 0 112v19c0 7.4 3.4 14.3 9.2 18.9 30.6 24 20.7 12.3 173.4 128.7 16.8 12.2 50.2 41.8 73.4 41.4z"/></svg>';
		$icon_hours    = '<svg aria-hidden="true" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 448c-110.5 0-200-89.5-200-200S145.5 56 256 56s200 89.5 200 200-89.5 200-200 200zm61.8-104.4l-84.9-61.7c-3.1-2.3-4.9-5.9-4.9-9.7V116c0-6.6 5.4-12 12-12h32c6.6 0 12 5.4 12 12v141.7l66.8 48.6c5.4 3.9 6.5 11.4 2.6 16.8L334.6 349c-3.9 5.3-11.4 6.5-16.8 2.6z"/></svg>';

		// 标签文字（参考 Pineele 格式 "Label: Value"）
		$labels = $is_zh
			? array(
				'address'  => '地址',
				'whatsapp' => '电话 / WhatsApp',
				'email'    => '邮箱',
				'hours'    => '工作时间',
			)
			: array(
				'address'  => 'Address',
				'whatsapp' => 'Tel / WhatsApp',
				'email'    => 'Email',
				'hours'    => 'Hours',
			);

		// 构建左栏联系信息列表（参考 Pineele elementor-icon-list 结构）
		$icon_list = '';
		if ( $address && ! self::is_placeholder_contact_value( $address ) ) {
			$icon_list .= '<li class="yke-icon-list-item"><span class="yke-icon-list-icon">' . $icon_address . '</span><span class="yke-icon-list-text">' . esc_html( $labels['address'] ) . ': ' . wp_kses_post( nl2br( esc_html( $address ) ) ) . '</span></li>';
		}
		if ( $whatsapp && ! self::is_placeholder_contact_value( $whatsapp ) ) {
			$wa_link  = self::is_linkable_phone( $whatsapp ) ? esc_url( self::build_whatsapp_link( $whatsapp ) ) : '';
			$wa_text  = esc_html( $labels['whatsapp'] ) . ': ' . esc_html( $whatsapp );
			$icon_list .= $wa_link
				? '<li class="yke-icon-list-item"><a href="' . $wa_link . '" target="_blank" rel="noopener"><span class="yke-icon-list-icon">' . $icon_whatsapp . '</span><span class="yke-icon-list-text">' . $wa_text . '</span></a></li>'
				: '<li class="yke-icon-list-item"><span class="yke-icon-list-icon">' . $icon_whatsapp . '</span><span class="yke-icon-list-text">' . $wa_text . '</span></li>';
		}
		if ( $email ) {
			$icon_list .= '<li class="yke-icon-list-item"><a href="' . esc_url( 'mailto:' . $email ) . '"><span class="yke-icon-list-icon">' . $icon_email . '</span><span class="yke-icon-list-text">' . esc_html( $labels['email'] ) . ': ' . esc_html( $email ) . '</span></a></li>';
		}
		if ( $hours ) {
			$icon_list .= '<li class="yke-icon-list-item"><span class="yke-icon-list-icon">' . $icon_hours . '</span><span class="yke-icon-list-text">' . esc_html( $labels['hours'] ) . ': ' . wp_kses_post( nl2br( esc_html( $hours ) ) ) . '</span></li>';
		}

		$html  = '<section class="yke-marketing-shell">';
		// 参考 Pineele 联系页结构：标题 + 两栏（左联系信息列表 + 右询盘表单）
		$html .= '<section class="yke-marketing-band yke-marketing-band--light yke-contact-band">';
		$html .= '<div class="yke-contact-grid">';
		// 左栏：公司名 + 联系信息列表（带 SVG 图标，参考 Pineele icon-list）
		$html .= '<div class="yke-contact-info">';
		if ( $copy['company'] ) {
			$html .= '<h3 class="yke-contact-info__company">' . esc_html( $copy['company'] ) . '</h3>';
		}
		$html .= '<ul class="yke-icon-list">' . $icon_list . '</ul>';
		$html .= '</div>';
		// 右栏：询盘表单
		$html .= '<div class="yke-contact-form">';
		$html .= '<h3 class="yke-contact-form__title">' . esc_html( $copy['form_title'] ) . '</h3>';
		$html .= '[yke_inquiry_form product_interest="' . esc_attr( $is_zh ? '通用询盘' : 'General Inquiry' ) . '"]';
		$html .= '</div>';
		$html .= '</div></section>';
		$html .= '</section>';

		return do_shortcode( $html );
	}

	/**
	 * Render small case study teaser cards.
	 *
	 * @param string $lang  Language slug.
	 * @param int    $limit Number of cards.
	 * @return string
	 */
	protected static function render_case_study_teaser_cards( $lang, $limit = 3 ) {
		$posts = get_posts(
			array(
				'post_type'      => 'case_study',
				'post_status'    => 'publish',
				'posts_per_page' => absint( $limit ),
				'orderby'        => 'date',
				'order'          => 'DESC',
				'lang'           => function_exists( 'pll_current_language' ) ? $lang : '',
			)
		);

		if ( empty( $posts ) ) {
			return '';
		}

		$html = '<div class="yke-case-teaser-grid">';
		foreach ( $posts as $post ) {
			$payload        = self::get_case_study_structured_payload( $post->ID, $lang );
			$region_name    = ! empty( $payload['region_names'][0] ) ? $payload['region_names'][0] : '';
			$delivery       = $payload['meta']['delivery_result'];
			$title          = get_the_title( $post );
			$excerpt        = has_excerpt( $post ) ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 24 );
			$url            = get_permalink( $post );
			$visual         = $payload['visual'];
			$spec_line      = $payload['meta']['key_specs'] ? $payload['meta']['key_specs'] : $payload['meta']['supply_scope'];

			$html .= '<article class="yke-case-teaser">';
			$html .= self::render_marketing_figure( $visual, 'yke-case-teaser__media' );
			$html .= '<div class="yke-case-teaser__body">';
			$html .= '<span class="yke-case-teaser__region">' . esc_html( $region_name ) . '</span>';
			$html .= '<h3><a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></h3>';
			$html .= '<p>' . esc_html( $excerpt ) . '</p>';
			if ( '' !== trim( (string) $spec_line ) ) {
				$html .= '<div class="yke-case-teaser__facts">' . wp_kses_post( nl2br( esc_html( $spec_line ) ) ) . '</div>';
			}
			if ( '' !== trim( (string) $delivery ) ) {
				$html .= '<div class="yke-case-teaser__meta">' . esc_html( $delivery ) . '</div>';
			}
			$html .= '</div></article>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render case-study teaser cards via shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_case_teasers_shortcode( $atts = array() ) {
		self::enqueue_style();

		$atts = shortcode_atts(
			array(
				'limit' => 3,
			),
			$atts,
			'yke_case_teasers'
		);

		return self::render_case_study_teaser_cards( self::get_current_site_language(), absint( $atts['limit'] ) ? absint( $atts['limit'] ) : 3 );
	}

	/**
	 * Render archive intro copy for case study templates.
	 *
	 * @return string
	 */
	public static function render_case_archive_intro() {
		self::enqueue_style();

		$lang  = self::get_current_site_language();
		$is_zh = ( 'zh' === $lang );
		$copy  = $is_zh
			? array(
				'kicker' => '项目案例',
				'title'  => '海外交付记录',
				'body'   => '云科已向中东、东南亚、非洲等 100+ 国家和地区交付中低压配电设备，以下为部分可披露的近期项目。',
				'chips'  => array(
					'高压开关柜 / 低压开关柜 / 变压器 / 预装式变电站',
					'EPC、工业、公用事业、新能源场景',
					'客户详情可在签署 NDA 后披露',
				),
			)
			: array(
				'kicker' => 'Case Studies',
				'title'  => 'Export Delivery Records',
				'body'   => 'YKE has delivered medium- and low-voltage power distribution equipment to 100+ countries across the Middle East, Southeast Asia, and Africa. Below is a selection of recent shareable references.',
				'chips'  => array(
					'MV switchgear / LV switchgear / transformers / prefabricated substations',
					'EPC, industrial, utility, and renewable projects',
					'Buyer details available under NDA',
				),
			);

		$html  = '<section class="yke-case-archive-intro">';
		$html .= '<div class="yke-case-intro-head">';
		$html .= '<span class="yke-case-intro-kicker">' . esc_html( $copy['kicker'] ) . '</span>';
		$html .= '<h2>' . esc_html( $copy['title'] ) . '</h2>';
		$html .= '<p>' . esc_html( $copy['body'] ) . '</p>';
		$html .= '</div>';
		$html .= '<ul class="yke-case-intro-chips">';
		foreach ( $copy['chips'] as $chip ) {
			$html .= '<li>' . esc_html( $chip ) . '</li>';
		}
		$html .= '</ul></section>';

		// 移除换行符，防止 wpautop 注入 <p> 破坏块级 HTML 结构
		return str_replace( array( "\n", "\r", "\t" ), '', $html );
	}

	/**
	 * Render structured archive cards for the case-study library.
	 *
	 * @return string
	 */
	public static function render_case_archive_cards() {
		self::enqueue_style();

		$lang  = self::get_current_site_language();
		$posts = get_posts(
			array(
				'post_type'      => 'case_study',
				'post_status'    => 'publish',
				'posts_per_page' => 12,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'lang'           => function_exists( 'pll_current_language' ) ? $lang : '',
			)
		);

		if ( empty( $posts ) ) {
			return '<p>' . esc_html( self::t( 'misc.case_syncing', $lang ) ) . '</p>';
		}

		$html = '<div class="yke-case-archive-grid">';
		foreach ( $posts as $post ) {
			$payload = self::get_case_study_structured_payload( $post->ID, $lang );
			$html   .= self::build_case_archive_card_markup( $post, $payload, $lang );
		}
		$html .= '</div>';

		// 移除换行符，防止 wpautop 注入 <p> 破坏块级 HTML 结构
		$html = str_replace( array( "\n", "\r", "\t" ), '', $html );
		return $html;
	}

	/**
	 * Render "Browse by Product Line" section on the case studies archive page.
	 *
	 * Shows 5 product-line cards with a hover popover listing the specific models.
	 * Inserted between yke_case_archive_intro and yke_case_archive_cards.
	 *
	 * @return string
	 */
	public static function render_case_product_browser_shortcode() {
		self::enqueue_style();
		$lang  = self::get_current_site_language();
		$is_zh = ( 'zh' === $lang );

		$copy = $is_zh
			? array(
				'eyebrow' => '按产品线筛选',
				'title'   => '按产品线快速查询案例',
				'lede'    => '悬停查看每个产品线下的具体型号；点击具体型号直接跳到单品详情页。',
			)
			: array(
				'eyebrow' => 'Browse by Product Line',
				'title'   => 'Filter cases by product line',
				'lede'    => 'Hover a product line to see its specific models; click a model to jump straight to its detail page.',
			);

		$html  = '<section class="yke-section yke-section--compact yke-case-product-browser">';
		$html .= '<div class="yke-section-inner">';
		$html .= '<div class="yke-section-head">';
		$html .= '<p class="yke-section-eyebrow">' . esc_html( $copy['eyebrow'] ) . '</p>';
		$html .= '<h2>' . esc_html( $copy['title'] ) . '</h2>';
		$html .= '<p>' . esc_html( $copy['lede'] ) . '</p>';
		$html .= '</div>';
		$html .= self::render_product_line_cards_with_hover_popover( $lang, 'case-archive' );
		$html .= '</div></section>';

		$html = str_replace( array( "\n", "\r", "\t" ), '', $html );
		return $html;
	}

	/**
	 * Build one structured case-study archive card.
	 *
	 * @param WP_Post $post    Post object.
	 * @param array   $payload Structured payload.
	 * @param string  $lang    Language slug.
	 * @return string
	 */
	protected static function build_case_archive_card_markup( $post, $payload, $lang ) {
		$meta        = $payload['meta'];
		$visual      = $payload['visual'];
		$region_name = ! empty( $payload['region_names'][0] ) ? $payload['region_names'][0] : '';
		$url         = get_permalink( $post );
		$title       = get_the_title( $post );
		$excerpt     = has_excerpt( $post ) ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 22 );
		$supply      = $meta['supply_scope'];
		$result      = $meta['delivery_result'];
		$footer       = self::t( 'btn.review_case', $lang );
		$label_supply = self::t( 'label.supply_scope', $lang );
		$label_result = self::t( 'label.delivery_result', $lang );

		$html  = '<article class="yke-case-card">';
		$html .= '<a class="yke-case-card-link" href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $title ) . '"></a>';
		$html .= '<div class="yke-case-card-media">';
		$html .= '<div class="yke-case-card-img">' . self::render_marketing_figure( $visual, 'yke-case-card-figure' ) . '</div>';
		if ( '' !== $region_name ) {
			$html .= '<span class="yke-case-card-region">' . esc_html( $region_name ) . '</span>';
		}
		$html .= '</div>';
		$html .= '<div class="yke-case-card-body">';
		$html .= '<h3 class="yke-case-card-title">' . esc_html( $title ) . '</h3>';
		$html .= '<p class="yke-case-card-excerpt">' . esc_html( $excerpt ) . '</p>';
		if ( '' !== $supply ) {
			$html .= '<div class="yke-case-card-supply"><span class="yke-case-card-label">' . esc_html( $label_supply ) . '</span><p>' . esc_html( $supply ) . '</p></div>';
		}
		if ( '' !== $result ) {
			$html .= '<div class="yke-case-card-result"><span class="yke-case-card-label">' . esc_html( $label_result ) . '</span><p>' . esc_html( $result ) . '</p></div>';
		}
		$html .= '<div class="yke-case-card-footer"><span>' . esc_html( $footer ) . '</span><span aria-hidden="true">&rarr;</span></div>';
		$html .= '</div></article>';

		return $html;
	}

	/**
	 * Render case study sidebar copy for the single template.
	 *
	 * @return string
	 */
	public static function render_case_study_sidebar() {
		self::enqueue_style();

		$lang  = self::get_current_site_language();
		$title = self::t( 'case.sidebar.title', $lang );
		$body  = self::t( 'case.sidebar.body', $lang );
		$tag   = self::t( 'case.sidebar.inquiry_tag', $lang );

		$html  = '<div class="yke-case-sidebar">';
		$html .= '<div class="yke-marketing-panel yke-marketing-panel--form"><h3>' . esc_html( $title ) . '</h3><p>' . esc_html( $body ) . '</p>[yke_inquiry_form product_interest="' . esc_attr( $tag ) . '"]</div>';
		$html .= '</div>';

		return do_shortcode( $html );
	}

	/**
	 * Render the upgraded page hero for seeded marketing pages.
	 *
	 * @param WP_Post $post  Current post object.
	 * @param string  $lang  Language slug.
	 * @param bool    $is_zh Whether the current page is Chinese.
	 * @return string
	 */
	protected static function render_marketing_page_hero( $post, $lang, $is_zh ) {
		$slug = $post->post_name;

		$heroes = array(
			'products'                => array(
				'kicker' => $is_zh ? '产品中心' : 'Product Lines',
				'title'  => $is_zh ? '覆盖 0.4kV 至 35kV 的出口型配电产品体系' : 'Export-ready power distribution lines from 0.4kV to 35kV',
				'desc'   => $is_zh ? '围绕海外项目采购流程组织产品目录、资料边界与交付信号，方便分销商、EPC 与工业买家快速判断匹配度。' : 'Structured for overseas buyers, EPC teams, and distributors to qualify fit, document readiness, and delivery scope without wasting time.',
				'meta'   => array(
					$is_zh ? '5 大产品线' : '5 core product families',
					$is_zh ? 'IEC / GB 体系' : 'IEC / GB aligned',
					$is_zh ? '支持第三方验货' : 'Third-party inspection ready',
				),
			),
			'case-studies'            => array(
				'kicker' => $is_zh ? '项目案例' : 'Case Studies',
				'title'  => $is_zh ? '海外项目交付记录' : 'Export Project References',
				'desc'   => $is_zh ? '近期向中东、东南亚、非洲等市场交付的中低压配电设备项目。' : 'Recent medium- and low-voltage distribution deliveries across the Middle East, Southeast Asia, and Africa.',
				'meta'   => array(
					$is_zh ? '中东 / 东南亚 / 非洲' : 'Middle East / Southeast Asia / Africa',
					$is_zh ? '覆盖多产品线' : 'Multi-line project coverage',
					$is_zh ? '客户详情 NDA 后披露' : 'Buyer details under NDA',
				),
			),
			'about-yke'               => array(
				'kicker' => $is_zh ? '关于云科' : 'About YKE',
				'title'  => $is_zh ? '广州云科电气设备有限公司' : 'Guangzhou YKE Electric Equipment Co., Ltd.',
				'desc'   => $is_zh ? '专注于中低压配电设备研发、制造与出口，产品覆盖 0.4kV 至 35kV，已向 100+ 国家和地区配套交付。' : 'A manufacturer focused on R&D, production, and export of medium- and low-voltage power distribution equipment, with delivery records across 100+ countries and regions.',
				'meta'   => array(
					$is_zh ? '100+ 出口国家和地区' : '100+ export countries and regions',
					$is_zh ? '18 个月标准质保' : '18-month standard warranty',
					$is_zh ? '100% 出厂例行检验' : '100% routine outgoing inspection',
				),
			),
			'distributor-partnership' => array(
				'kicker' => $is_zh ? '代理合作' : 'Distributor Partnership',
				'title'  => $is_zh ? '区域代理合作' : 'Regional Distributor Partnership',
				'desc'   => $is_zh ? '面向中东、东南亚、非洲与拉美新兴市场招募区域代理伙伴，提供建立在五大产品线与 100+ 国家出口经验基础上的渠道合作模式。' : 'YKE Electric recruits regional distributor partners across the Middle East, Southeast Asia, Africa, and Latin America, built on five product lines and export experience across 100+ countries and regions.',
				'meta'   => array(
					$is_zh ? '区域保护可沟通' : 'Territory protection discussed early',
					$is_zh ? '提供投标与项目配合' : 'Tender and project support provided',
					$is_zh ? '支持 OEM / ODM' : 'OEM / ODM paths available',
				),
			),
			'contact'                 => array(
				'kicker' => $is_zh ? '联系云科' : 'Contact YKE',
				'title'  => $is_zh ? '联系我们' : 'Contact Us',
			'desc'   => '',
				'meta'   => array(
					$is_zh ? '支持中英沟通' : 'English-first with Chinese support',
					$is_zh ? '可做产品线预匹配' : 'Product-line pre-screening available',
					$is_zh ? '询盘与联系信息同页整合' : 'Inquiry path and contact details together',
				),
			),
		);

		$data = isset( $heroes[ $slug ] ) ? $heroes[ $slug ] : array(
			'kicker' => self::t( 'hero.fallback_kicker', $lang ),
			'title'  => get_the_title( $post ),
			'desc'   => '',
			'meta'   => array(),
		);

		$meta_html = '';
		if ( ! empty( $data['meta'] ) ) {
			$meta_html .= '<div class="yke-page-hero__meta">';
			foreach ( $data['meta'] as $item ) {
				$meta_html .= '<span>' . esc_html( $item ) . '</span>';
			}
			$meta_html .= '</div>';
		}

		// Product overview hero gets a compact modifier so it doesn't dominate the page.
		$hero_class = ( 'products' === $slug ) ? 'yke-page-hero yke-page-hero--products' : 'yke-page-hero';

		return '<section class="' . $hero_class . '"><div class="yke-page-hero-inner">'
			. '<p class="yke-kicker">' . esc_html( $data['kicker'] ) . '</p>'
			. '<h1>' . esc_html( $data['title'] ) . '</h1>'
			. ( ! empty( $data['desc'] ) ? '<p>' . esc_html( $data['desc'] ) . '</p>' : '' )
			. $meta_html
			. '</div></section>';
	}

	/**
	 * Render a simplified blue hero for product line landing pages.
	 *
	 * @param string $request_path Normalized request path.
	 * @param string $lang         Current language.
	 * @return string
	 */
	protected static function render_product_line_page_hero( $request_path, $lang ) {
		$path_parts = explode( '/', trim( (string) $request_path, '/' ) );
		if ( count( $path_parts ) < 2 ) {
			return '';
		}

		$key     = sanitize_title( $path_parts[1] );
		$data    = self::get_product_rich_data( $key );
		$is_zh   = ( 'zh' === $lang && ! empty( $data['zh'] ) );
		$title   = $is_zh && ! empty( $data['zh']['title'] ) ? $data['zh']['title'] : ( isset( $data['title'] ) ? $data['title'] : get_the_title() );
		$tagline = $is_zh && ! empty( $data['zh']['tagline'] ) ? $data['zh']['tagline'] : ( isset( $data['tagline'] ) ? $data['tagline'] : '' );
		$summary = $is_zh && ! empty( $data['zh']['summary'] ) ? $data['zh']['summary'] : ( isset( $data['summary'] ) ? $data['summary'] : '' );

		return '<section class="yke-page-hero yke-page-hero--product-line"><div class="yke-page-hero-inner">'
			. ( $tagline ? '<p class="yke-kicker">' . esc_html( $tagline ) . '</p>' : '' )
			. '<h1>' . esc_html( $title ) . '</h1>'
			. ( $summary ? '<p>' . esc_html( $summary ) . '</p>' : '' )
			. '</div></section>';
	}

	/**
	 * Build product line display markup for case study summaries.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	protected static function get_product_line_display( $post_id ) {
		$terms = get_the_terms( $post_id, 'product_line' );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}

		$items = array();

		foreach ( $terms as $term ) {
			$page = self::get_product_page_by_slug( $term->slug, self::get_current_site_language() );

			if ( $page ) {
				$items[] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( get_permalink( $page ) ),
					esc_html( $term->name )
				);
			} else {
				$items[] = esc_html( $term->name );
			}
		}

		return implode( ', ', $items );
	}

	/**
	 * Resolve a seeded product page by slug and language.
	 *
	 * @param string $slug Product line slug.
	 * @param string $lang Language slug.
	 * @return WP_Post|null
	 */
	protected static function get_product_page_by_slug( $slug, $lang ) {
		$path = 'products/' . sanitize_title( $slug );
		return self::find_seeded_page( 'page:' . $path . ':' . $lang, 'zh' === $lang ? 'zh/' . $path : $path );
	}

	/**
	 * Get localized product display title by slug.
	 *
	 * @param string $slug Product line slug.
	 * @param string $lang Language slug.
	 * @return string
	 */
	protected static function get_product_display_title( $slug, $lang ) {
		$page = self::get_product_page_by_slug( $slug, $lang );
		if ( $page ) {
			return get_the_title( $page );
		}

		$term = get_term_by( 'slug', $slug, 'product_line' );
		return $term ? $term->name : $slug;
	}

	/**
	 * Check whether a phone-like value is good enough to link.
	 *
	 * @param string $value Raw phone value.
	 * @return bool
	 */
	protected static function is_linkable_phone( $value ) {
		if ( preg_match( '/x/i', $value ) ) {
			return false;
		}

		$digits = preg_replace( '/[^0-9]/', '', $value );

		return strlen( $digits ) >= 7;
	}

	/**
	 * Determine whether a shortcode attribute is truthy.
	 *
	 * @param string $value Attribute value.
	 * @return bool
	 */
	protected static function is_truthy( $value ) {
		return ! in_array( strtolower( (string) $value ), array( '0', 'false', 'no', 'off' ), true );
	}

	/**
	 * Ensure the plugin stylesheet is loaded when needed.
	 */
	protected static function enqueue_style() {
		if ( wp_style_is( 'yke-site-core', 'registered' ) ) {
			wp_enqueue_style( 'yke-site-core' );
		}
	}

	/**
	 * Get the current request URL.
	 *
	 * @return string
	 */
	protected static function get_current_request_url() {
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			return home_url( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}

		return home_url( '/' );
	}

	/**
	 * Get the current source title for inquiry records.
	 *
	 * @return string
	 */
	protected static function get_current_source_title() {
		if ( is_singular() ) {
			return wp_strip_all_tags( get_the_title() );
		}

		if ( is_home() || is_front_page() ) {
			return __( 'Homepage', 'yke-site-core' );
		}

		return __( 'Website Inquiry Form', 'yke-site-core' );
	}

	/**
	 * Get localized copy for inquiry form shells.
	 *
	 * @return array
	 */
	protected static function get_inquiry_form_copy() {
		$lang = self::get_current_site_language();

		return array(
			'name_placeholder'        => self::t( 'form.placeholder.name', $lang ),
			'company_placeholder'     => self::t( 'form.placeholder.company', $lang ),
			'country_placeholder'     => self::t( 'form.placeholder.country', $lang ),
			'email_placeholder'       => self::t( 'form.placeholder.email', $lang ),
			'whatsapp_placeholder'    => self::t( 'form.placeholder.whatsapp', $lang ),
			'interest_placeholder'    => self::t( 'form.placeholder.interest', $lang ),
			'requirement_placeholder' => self::t( 'form.placeholder.requirement', $lang ),
			'default_button'          => self::t( 'form.button.submit', $lang ),
		);
	}

	/**
	 * Check whether Contact Form 7 is active and usable.
	 *
	 * @return bool
	 */
	protected static function is_cf7_ready() {
		return class_exists( 'WPCF7_ContactForm' ) && shortcode_exists( 'contact-form-7' );
	}

	/**
	 * Get the stored CF7 form ID when available.
	 *
	 * @return int
	 */
	protected static function get_cf7_form_id() {
		return absint( get_option( self::CF7_FORM_OPTION, 0 ) );
	}

	/**
	 * Ensure the canonical CF7 inquiry form exists.
	 */
	protected static function ensure_cf7_inquiry_form() {
		if ( ! self::is_cf7_ready() ) {
			return;
		}

		$form_id = self::get_cf7_form_id();
		$form    = $form_id ? WPCF7_ContactForm::get_instance( $form_id ) : null;

		if ( ! $form ) {
			$existing = WPCF7_ContactForm::find(
				array(
					'title'          => self::CF7_FORM_TITLE,
					'posts_per_page' => 1,
				)
			);
			$form = ! empty( $existing ) ? $existing[0] : null;
		}

		if ( ! $form ) {
			$form = WPCF7_ContactForm::get_template(
				array(
					'title'  => self::CF7_FORM_TITLE,
					'locale' => determine_locale(),
				)
			);
		}

		$properties = $form->get_properties();
		$properties['form'] = trim(
			'<div class="yke-field"><label>Name<br /> [text* your-name autocomplete:name placeholder "Your name"]</label></div>' . "\n" .
			'<div class="yke-field"><label>Company<br /> [text your-company placeholder "Company name"]</label></div>' . "\n" .
			'<div class="yke-field"><label>Country / Market<br /> [text your-country placeholder "e.g. USA, Germany, Brazil"]</label></div>' . "\n" .
			'<div class="yke-field"><label>Email<br /> [email* your-email autocomplete:email placeholder "you@company.com"]</label></div>' . "\n" .
			'<div class="yke-field"><label>WhatsApp / Phone<br /> [text your-whatsapp placeholder "+1 234 567 8900"]</label></div>' . "\n" .
			'<div class="yke-field"><label>Product Interest<br /> [text your-product-interest default:shortcode_attr placeholder "e.g. Dry-type transformer, Switchgear"]</label></div>' . "\n" .
			'<div class="yke-field"><label>Project Requirement<br /> [textarea* your-requirement placeholder "Describe your project: voltage level, capacity, quantity, standards, timeline..."]</label></div>' . "\n" .
			'[hidden source-title]' . "\n" .
			'[hidden source-url]' . "\n" .
			'[submit "Send Inquiry"]'
		);
		$properties['mail'] = array(
			'subject'            => '[YKE Inquiry] [your-product-interest]',
			'sender'             => '[_site_title] <[yke-smtp-from]>',
			'recipient'          => self::get_inquiry_mail_recipient_tag(),
			'body'               => "A new inquiry has been submitted on the website.\n\nName: [your-name]\nCompany: [your-company]\nCountry / Market: [your-country]\nEmail: [your-email]\nWhatsApp / Phone: [your-whatsapp]\nProduct Interest: [your-product-interest]\nSource Page: [source-title]\nSource URL: [source-url]\n\nRequirement:\n[your-requirement]\n",
			'additional_headers' => 'Reply-To: [your-email]',
			'attachments'        => '',
			'use_html'           => 0,
			'exclude_blank'      => 0,
		);
		$properties['mail_2'] = array(
			'active'             => false,
			'subject'            => '',
			'sender'             => '',
			'recipient'          => '',
			'body'               => '',
			'additional_headers' => '',
			'attachments'        => '',
			'use_html'           => 0,
			'exclude_blank'      => 0,
		);

		$form->set_title( self::CF7_FORM_TITLE );
		$form->set_properties( $properties );
		$form_id = $form->save();

		if ( $form_id ) {
			update_option( self::CF7_FORM_OPTION, (int) $form_id );
		}
	}

	/**
	 * Allow custom shortcode attributes to reach CF7 form rendering.
	 *
	 * @param array $out    Output attributes.
	 * @param array $pairs  Supported pairs.
	 * @param array $atts   Incoming attributes.
	 * @return array
	 */
	public static function filter_cf7_shortcode_atts( $out, $pairs, $atts ) {
		$extra = array(
			'your-product-interest',
			'source-title',
			'source-url',
		);

		foreach ( $extra as $key ) {
			if ( isset( $atts[ $key ] ) ) {
				$sanitizer = 'source-url' === $key ? 'esc_url_raw' : 'sanitize_text_field';
				$out[ $key ] = call_user_func( $sanitizer, $atts[ $key ] );
			}
		}

		return $out;
	}

	/**
	 * Provide hidden meta fields for the CF7 form.
	 *
	 * @param array $fields Existing fields.
	 * @return array
	 */
	public static function inject_cf7_hidden_fields( $fields ) {
		if ( ! self::is_current_cf7_inquiry_form() ) {
			return $fields;
		}

		$fields['source-title'] = self::get_current_source_title();
		$fields['source-url']   = self::get_current_request_url();

		return $fields;
	}

	/**
	 * Replace custom CF7 special mail tags.
	 *
	 * @param string|null       $output      Current output.
	 * @param string            $name        Tag name.
	 * @param bool              $html        HTML context flag.
	 * @param WPCF7_ContactForm $contact_form Contact form.
	 * @return string|null
	 */
	public static function replace_cf7_special_mail_tags( $output, $name, $html, $contact_form ) {
		unset( $html );

		if ( ! $contact_form instanceof WPCF7_ContactForm || $contact_form->title() !== self::CF7_FORM_TITLE ) {
			return $output;
		}

		if ( 'yke-smtp-from' === $name ) {
			return self::get_cf7_sender_email();
		}

		return $output;
	}

	/**
	 * Persist CF7 submissions into the site's inquiry CPT.
	 *
	 * @param WPCF7_ContactForm $contact_form Submitted form.
	 */
	public static function capture_cf7_submission( $contact_form ) {
		if ( ! $contact_form instanceof WPCF7_ContactForm ) {
			return;
		}

		if ( $contact_form->title() !== self::CF7_FORM_TITLE ) {
			return;
		}

		$submission = WPCF7_Submission::get_instance();
		if ( ! $submission ) {
			return;
		}

		$data = $submission->get_posted_data();
		if ( empty( $data ) || ! is_array( $data ) ) {
			return;
		}

		$payload = array(
			'name'             => isset( $data['your-name'] ) ? sanitize_text_field( $data['your-name'] ) : '',
			'company'          => isset( $data['your-company'] ) ? sanitize_text_field( $data['your-company'] ) : '',
			'country'          => isset( $data['your-country'] ) ? sanitize_text_field( $data['your-country'] ) : '',
			'email'            => isset( $data['your-email'] ) ? sanitize_email( $data['your-email'] ) : '',
			'whatsapp'         => isset( $data['your-whatsapp'] ) ? sanitize_text_field( $data['your-whatsapp'] ) : '',
			'product_interest' => isset( $data['your-product-interest'] ) ? sanitize_text_field( $data['your-product-interest'] ) : 'General Inquiry',
			'requirement'      => isset( $data['your-requirement'] ) ? sanitize_textarea_field( $data['your-requirement'] ) : '',
			'source_title'     => isset( $data['source-title'] ) ? sanitize_text_field( $data['source-title'] ) : self::get_current_source_title(),
			'source_url'       => isset( $data['source-url'] ) ? esc_url_raw( $data['source-url'] ) : self::get_current_request_url(),
			'email_status'     => 'sent',
		);

		if ( self::has_inquiry_validation_error( $payload ) ) {
			return;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'yke_inquiry',
				'post_status'  => 'private',
				'post_title'   => self::build_inquiry_title( $payload ),
				'post_content' => $payload['requirement'],
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return;
		}

		foreach ( self::get_inquiry_meta_fields() as $key => $field ) {
			if ( isset( $payload[ $key ] ) && '' !== $payload[ $key ] ) {
				update_post_meta( $post_id, self::OPTION_PREFIX . $key, $payload[ $key ] );
			}
		}

		update_post_meta( $post_id, self::OPTION_PREFIX . 'submitted_at', current_time( 'mysql' ) );
		update_post_meta( $post_id, self::OPTION_PREFIX . 'notification_sent_at', current_time( 'mysql' ) );

		do_action( 'yke_site_core_inquiry_saved', $post_id, $payload );
	}

	/**
	 * Ensure Polylang has the required language set.
	 */
	protected static function ensure_polylang_languages() {
		if ( ! function_exists( 'PLL' ) || ! method_exists( PLL()->model, 'add_language' ) ) {
			return;
		}

		$languages = array(
			'en' => array(
				'locale' => 'en_US',
				'slug'   => 'en',
				'name'   => 'English',
				'flag'   => 'us',
			),
			'zh' => array(
				'locale' => 'zh_CN',
				'slug'   => 'zh',
				'name'   => '中文',
				'flag'   => 'cn',
			),
		);

		foreach ( $languages as $slug => $args ) {
			if ( PLL()->model->get_language( $slug ) ) {
				continue;
			}

			PLL()->model->add_language( $args );
		}

		if ( 'en' !== pll_default_language() ) {
			PLL()->model->update_default_lang( 'en' );
		}

		self::update_polylang_option( 'force_lang', 1 );
		self::update_polylang_option( 'hide_default', true );
		self::update_polylang_option( 'redirect_lang', false );
		self::update_polylang_option( 'browser', false );
	}

	/**
	 * Update one Polylang option through its managed option store.
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Option value.
	 */
	protected static function update_polylang_option( $key, $value ) {
		if ( ! function_exists( 'PLL' ) || ! isset( PLL()->options ) || ! is_object( PLL()->options ) ) {
			return;
		}

		$current = PLL()->options->get( $key );
		if ( $current === $value ) {
			return;
		}

		PLL()->options->set( $key, $value );
	}

	/**
	 * Repair language labels for seeded Polylang languages when legacy data contains bad encoding.
	 */
	protected static function repair_polylang_language_labels() {
		if ( ! function_exists( 'PLL' ) || ! method_exists( PLL()->model, 'get_language' ) ) {
			return;
		}

		$targets = array(
			'en' => 'English',
			'zh' => 'Chinese',
		);

		foreach ( $targets as $slug => $name ) {
			$lang = PLL()->model->get_language( $slug );
			if ( ! $lang ) {
				continue;
			}

			if ( $lang->name === $name ) {
				continue;
			}

			wp_update_term(
				$lang->term_id,
				'language',
				array(
					'name' => $name,
				)
			);

			$secondary_term_id = $lang->get_tax_prop( 'term_language', 'term_id' );
			if ( $secondary_term_id ) {
				wp_update_term(
					$secondary_term_id,
					'term_language',
					array(
						'name' => $name,
					)
				);
			}
		}
	}

	/**
	 * Link seeded English and Chinese pages with Polylang.
	 */
	protected static function ensure_polylang_translations() {
		if ( ! function_exists( 'pll_set_post_language' ) || ! function_exists( 'pll_save_post_translations' ) ) {
			return;
		}

		$front_page = self::get_front_page_post();
		$zh_root    = self::find_seeded_page( 'page:home:zh', 'zh' );

		self::assign_translation_pair( $front_page, $zh_root, 'en', 'zh' );

		$seed_pairs = array(
			'page:products:en'                            => 'page:products:zh',
			'page:about-yke:en'                           => 'page:about-yke:zh',
			'page:distributor-partnership:en'             => 'page:distributor-partnership:zh',
			'page:contact:en'                             => 'page:contact:zh',
			'page:products/mv-switchgear:en'              => 'page:products/mv-switchgear:zh',
			'page:products/lv-switchgear:en'              => 'page:products/lv-switchgear:zh',
			'page:products/transformers:en'               => 'page:products/transformers:zh',
			'page:products/prefabricated-substations:en'  => 'page:products/prefabricated-substations:zh',
			'page:products/new-energy-electrical:en'      => 'page:products/new-energy-electrical:zh',
		);

		foreach ( $seed_pairs as $en_seed_key => $zh_seed_key ) {
			$en_post = self::find_seeded_page( $en_seed_key, self::seed_key_to_path( $en_seed_key ) );
			$zh_post = self::find_seeded_page( $zh_seed_key, self::seed_key_to_path( $zh_seed_key ) );

			self::assign_translation_pair( $en_post, $zh_post, 'en', 'zh' );
		}
	}

	/**
	 * Sync SMTP options into WP Mail SMTP.
	 */
	protected static function ensure_wp_mail_smtp_settings() {
		if ( ! class_exists( 'WPMailSMTP\Options' ) ) {
			return;
		}

		$from_name = self::get_setting( 'smtp_from_name' );
		$from_mail = self::get_setting( 'smtp_from_email' );
		$mailer    = self::get_setting( 'smtp_mailer' );
		if ( '' === $mailer ) {
			$mailer = 'smtp';
		}

		$desired = array(
			'mail' => array(
				'from_name'        => $from_name ? $from_name : self::get_setting( 'brand_name' ),
				'from_email'       => $from_mail ? $from_mail : self::get_setting( 'contact_email' ),
				'mailer'           => $mailer,
				'return_path'      => true,
				'from_email_force' => true,
				'from_name_force'  => true,
			),
			'smtp' => array(
				'host'       => self::get_setting( 'smtp_host' ),
				'port'       => (int) self::get_setting( 'smtp_port' ),
				'encryption' => self::normalize_smtp_encryption( self::get_setting( 'smtp_encryption' ) ),
				'autotls'    => self::is_truthy( self::get_setting( 'smtp_autotls' ) ),
				'auth'       => self::is_truthy( self::get_setting( 'smtp_auth' ) ),
				'user'       => self::get_setting( 'smtp_user' ),
				'pass'       => self::get_setting( 'smtp_pass' ),
			),
		);

		\WPMailSMTP\Options::init()->set( $desired, false, false );
	}

	/**
	 * Normalize SMTP encryption values.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	protected static function normalize_smtp_encryption( $value ) {
		$value = sanitize_key( $value );
		if ( ! in_array( $value, array( 'none', 'ssl', 'tls' ), true ) ) {
			return 'tls';
		}

		return $value;
	}

	/**
	 * Determine whether the current CF7 rendering/submission context is the seeded inquiry form.
	 *
	 * @return bool
	 */
	protected static function is_current_cf7_inquiry_form() {
		if ( ! self::is_cf7_ready() ) {
			return false;
		}

		$contact_form = WPCF7_ContactForm::get_current();
		return $contact_form instanceof WPCF7_ContactForm && $contact_form->title() === self::CF7_FORM_TITLE;
	}

	/**
	 * Ensure Redirection DB tables exist and version state is current.
	 */
	protected static function ensure_redirection_database() {
		if ( ! class_exists( 'Red_Database_Status' ) || ! class_exists( 'Red_Database' ) ) {
			return;
		}

		$status = new Red_Database_Status();

		if ( $status->needs_installing() ) {
			$latest = Red_Database::get_latest_database();
			$latest->install();
			return;
		}

		if ( $status->needs_updating() ) {
			$database = new Red_Database();
			$database->apply_upgrade( $status );
		}
	}

	/**
	 * Seed a minimal Yoast site baseline.
	 */
	protected static function ensure_yoast_site_defaults() {
		if ( ! function_exists( 'YoastSEO' ) && ! class_exists( 'WPSEO_Options' ) ) {
			return;
		}

		if ( class_exists( 'WPSEO_Options' ) ) {
			if ( method_exists( 'WPSEO_Options', 'set' ) ) {
				\WPSEO_Options::set( 'company_or_person', 'company' );
				\WPSEO_Options::set( 'company_name', self::get_setting( 'company_name' ) );
				\WPSEO_Options::set( 'metadesc-home-wpseo', 'YKE Global supplies MV/LV switchgear, transformers, prefabricated substations, and new energy electrical systems for global distributors, EPC contractors, and industrial buyers.' );
				\WPSEO_Options::set( 'metadesc-page', '%%excerpt%%' );
				\WPSEO_Options::set( 'metadesc-post', '%%excerpt%%' );
				\WPSEO_Options::set( 'metadesc-ptarchive-case_study', 'Recent export power distribution projects delivered across the Middle East, Southeast Asia, Africa, and other target markets.' );
			}
		}

		update_option( 'blogname', 'YKE Global' );
		update_option( 'blogdescription', 'Industrial power distribution equipment for global projects and distributors.' );
	}

	/**
	 * Build recipient token for CF7 mail routing.
	 *
	 * @return string
	 */
	protected static function get_inquiry_mail_recipient_tag() {
		$recipient = self::get_setting( 'inquiry_recipient_email' );
		if ( '' === $recipient ) {
			$recipient = self::get_setting( 'contact_email' );
		}

		return $recipient ? $recipient : '[_site_admin_email]';
	}

	/**
	 * Ensure the SMTP sender used in the CF7 mail template is valid.
	 *
	 * @return string
	 */
	protected static function get_cf7_sender_email() {
		$email = self::get_setting( 'smtp_from_email' );
		if ( ! is_email( $email ) ) {
			$email = self::get_setting( 'contact_email' );
		}

		if ( ! is_email( $email ) ) {
			$email = get_option( 'admin_email' );
		}

		return sanitize_email( $email );
	}

	/**
	 * Find an existing post by path and type.
	 *
	 * @param string $path      Post path.
	 * @param string $post_type Post type.
	 * @return WP_Post|null
	 */
	protected static function find_post_by_path( $path, $post_type ) {
		$post = get_page_by_path( $path, OBJECT, $post_type );
		return ( $post instanceof WP_Post ) ? $post : null;
	}

	/**
	 * Get the front page post, preferring the configured homepage and then seeded fallbacks.
	 *
	 * @return WP_Post|null
	 */
	protected static function get_front_page_post() {
		$front_page_id = (int) get_option( 'page_on_front' );
		if ( $front_page_id ) {
			$post = get_post( $front_page_id );
			if ( $post instanceof WP_Post ) {
				return $post;
			}
		}

		$post = self::find_seeded_page( 'page:home:en', '' );
		if ( $post ) {
			return $post;
		}

		return self::find_post_by_path( '', 'page' );
	}

	/**
	 * Derive a canonical seeded path from a seed key.
	 *
	 * @param string $seed_key Seed key.
	 * @return string
	 */
	protected static function seed_key_to_path( $seed_key ) {
		$seed_key = trim( (string) $seed_key );
		if ( ! preg_match( '/^page:(.+):[a-z]{2}$/', $seed_key, $matches ) ) {
			return '';
		}

		$path = $matches[1];
		if ( 'home' === $path ) {
			return '';
		}

		return $path;
	}

	/**
	 * Assign Polylang translations when both sides are available.
	 *
	 * @param WP_Post|null $en_post English post.
	 * @param WP_Post|null $zh_post Chinese post.
	 * @param string       $en_lang English language slug.
	 * @param string       $zh_lang Chinese language slug.
	 */
	protected static function assign_translation_pair( $en_post, $zh_post, $en_lang, $zh_lang ) {
		$en_id = $en_post instanceof WP_Post ? $en_post->ID : 0;
		$zh_id = $zh_post instanceof WP_Post ? $zh_post->ID : 0;

		// Set language for each post individually even if the pair is incomplete.
		if ( $en_id ) {
			pll_set_post_language( $en_id, $en_lang );
		}
		if ( $zh_id ) {
			pll_set_post_language( $zh_id, $zh_lang );
		}

		// Only save translation pair when both posts exist.
		if ( $en_id && $zh_id ) {
			pll_save_post_translations(
				array(
					$en_lang => $en_id,
					$zh_lang => $zh_id,
				)
			);
		}
	}

	/**
	 * Return the canonical permalink for seeded pages to avoid duplicate -2/-3 URLs.
	 *
	 * @param string   $link   Current permalink.
	 * @param int      $post_id Post ID.
	 * @param bool     $sample  Whether this is a sample permalink.
	 * @return string
	 */
	public static function filter_seeded_page_link( $link, $post_id, $sample ) {
		unset( $sample );

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return $link;
		}

		if ( 'page' !== $post->post_type ) {
			return $link;
		}

		$seed_key = (string) get_post_meta( $post->ID, self::SEED_KEY_META, true );
		if ( '' === $seed_key ) {
			return $link;
		}

		$lang = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $post->ID ) : '';
		$path = self::seed_key_to_path( $seed_key );
		if ( '' === $path ) {
			return 'zh' === $lang ? home_url( '/zh/' ) : home_url( '/' );
		}

		if ( 'zh' === $lang ) {
			return home_url( '/zh/' . trim( $path, '/' ) . '/' );
		}

		return home_url( '/' . trim( $path, '/' ) . '/' );
	}

	/**
	 * Register canonical rewrite routes for seeded pages and Chinese helper landing pages.
	 */
	public static function register_seeded_page_routes() {
		$routes = array(
			'zh/?$'                             => 'index.php?pagename=zh',
			'zh/case-studies/?$'                => 'index.php?pagename=zh/case-studies',
			'zh/products/?$'                    => 'index.php?pagename=zh/products',
			'zh/about-yke/?$'                   => 'index.php?pagename=zh/about-yke',
			'zh/distributor-partnership/?$'     => 'index.php?pagename=zh/distributor-partnership',
			'zh/contact/?$'                     => 'index.php?pagename=zh/contact',
			'zh/products/mv-switchgear/?$'      => 'index.php?pagename=zh/products/mv-switchgear',
			'zh/products/lv-switchgear/?$'      => 'index.php?pagename=zh/products/lv-switchgear',
			'zh/products/transformers/?$'       => 'index.php?pagename=zh/products/transformers',
			'zh/products/prefabricated-substations/?$' => 'index.php?pagename=zh/products/prefabricated-substations',
			'zh/products/new-energy-electrical/?$'     => 'index.php?pagename=zh/products/new-energy-electrical',
			/*
			 * Product model detail pages use 3-segment paths (products/{line}/{model})
			 * but have no dedicated page posts. Map them to the product line page so
			 * is_page() passes; the content filter renders model detail content based
			 * on the request path.
			 */
			'products/mv-switchgear/([^/]+)/?$'                => 'index.php?pagename=products/mv-switchgear',
			'products/lv-switchgear/([^/]+)/?$'                => 'index.php?pagename=products/lv-switchgear',
			'products/transformers/([^/]+)/?$'                 => 'index.php?pagename=products/transformers',
			'products/prefabricated-substations/([^/]+)/?$'    => 'index.php?pagename=products/prefabricated-substations',
			'products/new-energy-electrical/([^/]+)/?$'        => 'index.php?pagename=products/new-energy-electrical',
			'zh/products/mv-switchgear/([^/]+)/?$'             => 'index.php?pagename=zh/products/mv-switchgear',
			'zh/products/lv-switchgear/([^/]+)/?$'             => 'index.php?pagename=zh/products/lv-switchgear',
			'zh/products/transformers/([^/]+)/?$'              => 'index.php?pagename=zh/products/transformers',
			'zh/products/prefabricated-substations/([^/]+)/?$' => 'index.php?pagename=zh/products/prefabricated-substations',
			'zh/products/new-energy-electrical/([^/]+)/?$'     => 'index.php?pagename=zh/products/new-energy-electrical',
		);

		foreach ( $routes as $match => $query ) {
			add_rewrite_rule( $match, $query, 'top' );
		}
	}

	/**
	 * Register custom query vars used by helper routes.
	 *
	 * @param array $vars Public query vars.
	 * @return array
	 */
	public static function register_public_query_vars( $vars ) {
		$vars[] = 'yke_case_archive';
		return $vars;
	}

	/**
	 * Route canonical Chinese helper URLs to the correct seeded page IDs.
	 *
	 * @param array $query_vars Parsed query vars.
	 * @return array
	 */
	public static function route_seeded_page_request( $query_vars ) {
		if ( is_admin() ) {
			return $query_vars;
		}

		$request_path = self::get_request_path();
		if ( '' === $request_path ) {
			return $query_vars;
		}

		if ( 'zh' === $request_path ) {
			$front_page_id = (int) get_option( 'page_on_front' );
			if ( ! $front_page_id ) {
				$front_page = self::get_front_page_post();
				$front_page_id = $front_page instanceof WP_Post ? (int) $front_page->ID : 0;
			}

			unset( $query_vars['pagename'], $query_vars['name'], $query_vars['attachment'], $query_vars['attachment_id'] );
			if ( $front_page_id ) {
				$query_vars['page_id'] = $front_page_id;
			}
			$query_vars['lang'] = 'zh';

			return $query_vars;
		}

		if ( 'zh/case-studies' === $request_path ) {
			unset( $query_vars['pagename'], $query_vars['name'], $query_vars['page_id'], $query_vars['attachment'], $query_vars['attachment_id'] );
			$query_vars['post_type']         = 'case_study';
			$query_vars['yke_case_archive']  = '1';
			$query_vars['lang']              = 'zh';
			return $query_vars;
		}

		/*
		 * Product model detail pages use 3-segment paths (products/{line}/{model})
		 * but have no dedicated page posts in DB. Intercept these requests and map
		 * them to the corresponding product line page so is_page() passes; the
		 * content filter then renders model detail content based on request path.
		 */
		$line_slug  = '';
		$model_slug = '';
		$is_zh_req  = false;

		if ( preg_match( '#^zh/products/([a-z0-9-]+)/([a-z0-9-]+)/?$#', $request_path, $m ) ) {
			$line_slug  = $m[1];
			$model_slug = $m[2];
			$is_zh_req  = true;
		} elseif ( preg_match( '#^products/([a-z0-9-]+)/([a-z0-9-]+)/?$#', $request_path, $m ) ) {
			$line_slug  = $m[1];
			$model_slug = $m[2];
		}

		if ( $line_slug && $model_slug ) {
			$models = self::get_product_models_for_line( $line_slug );
			$model_exists = false;
			if ( ! empty( $models ) ) {
				foreach ( $models as $mdl ) {
					if ( isset( $mdl['slug'] ) && $mdl['slug'] === $model_slug ) {
						$model_exists = true;
						break;
					}
				}
			}

			if ( $model_exists ) {
				$line_seed_key  = 'page:products/' . $line_slug . ':' . ( $is_zh_req ? 'zh' : 'en' );
				$line_seed_path = $is_zh_req ? 'zh/products/' . $line_slug : 'products/' . $line_slug;
				$line_post      = self::find_seeded_page( $line_seed_key, $line_seed_path );

				if ( $line_post instanceof WP_Post ) {
					unset( $query_vars['pagename'], $query_vars['name'], $query_vars['page_id'], $query_vars['attachment'], $query_vars['attachment_id'] );
					$query_vars['page_id'] = $line_post->ID;
					$query_vars['lang']    = $is_zh_req ? 'zh' : 'en';
					return $query_vars;
				}
			}
		}

		$seed_key = '';
		$seed_path = '';

		if ( 'zh' === $request_path ) {
			$seed_key  = 'page:home:zh';
			$seed_path = 'zh';
		} elseif ( 0 === strpos( $request_path, 'zh/' ) ) {
			$relative  = trim( substr( $request_path, 3 ), '/' );
			$seed_key  = 'page:' . $relative . ':zh';
			$seed_path = 'zh/' . $relative;
		}

		if ( '' === $seed_key ) {
			return $query_vars;
		}

		$post = self::find_seeded_page( $seed_key, $seed_path );
		if ( ! $post instanceof WP_Post ) {
			return $query_vars;
		}

		unset( $query_vars['pagename'], $query_vars['name'], $query_vars['page_id'], $query_vars['attachment'], $query_vars['attachment_id'] );
		$query_vars['page_id'] = $post->ID;
		$query_vars['lang']    = 'zh';

		return $query_vars;
	}

	/**
	 * Prevent canonical redirects from collapsing helper Chinese routes into English URLs.
	 *
	 * @param string|false $redirect_url Canonical redirect URL.
	 * @param string       $requested_url Requested URL.
	 * @return string|false
	 */
	public static function filter_seeded_canonical_redirect( $redirect_url, $requested_url ) {
		unset( $requested_url );

		$request_path = self::get_request_path();
		if ( 'zh' === $request_path || 0 === strpos( $request_path, 'zh/' ) ) {
			return false;
		}

		// Disable canonical redirect for 3-segment product model detail pages
		// (products/{line}/{model}) so WP does not collapse them to the line page.
		if ( 0 === strpos( $request_path, 'products/' ) && substr_count( $request_path, '/' ) >= 2 ) {
			return false;
		}

		return $redirect_url;
	}

	/**
	 * Tune document titles for helper Chinese routes.
	 *
	 * @param array $parts Title parts.
	 * @return array
	 */
	public static function filter_document_title_parts( $parts ) {
		$request_path = self::get_request_path();
		if ( 'zh' === $request_path ) {
			$parts['title'] = '中文站';
		} elseif ( 'zh/case-studies' === $request_path ) {
			$parts['title'] = '项目案例';
		}

		return $parts;
	}

	/**
	 * Force the Chinese helper case-studies archive to load translated posts.
	 *
	 * @param WP_Query $query Main query instance.
	 */
	public static function force_chinese_case_archive_query( $query ) {
		if ( is_admin() || ! $query instanceof WP_Query || ! $query->is_main_query() ) {
			return;
		}

		if ( '1' !== (string) $query->get( 'yke_case_archive' ) && ! self::is_chinese_case_archive_request() ) {
			return;
		}

		$query->set( 'post_type', 'case_study' );
		$query->set( 'lang', 'zh' );
		$query->is_home             = false;
		$query->is_singular         = false;
		$query->is_page             = false;
		$query->is_archive          = true;
		$query->is_post_type_archive = true;
	}

	/**
	 * Provide a default meta description when Yoast would otherwise warn.
	 *
	 * @param string $description Current description.
	 * @return string
	 */
	public static function filter_wpseo_meta_description( $description ) {
		if ( self::is_chinese_case_archive_request() ) {
			return '云科电气中文项目案例页，收录云科在中东、东南亚、非洲和新能源项目中的代表性交付案例。';
		}

		if ( self::is_chinese_context() && is_front_page() ) {
			return '云科电气中文首页，概览 5 大产品线、项目案例、质量认证与代理合作结构。';
		}

		if ( '' !== trim( (string) $description ) ) {
			return $description;
		}

		if ( is_front_page() ) {
			return 'YKE Global supplies MV/LV switchgear, transformers, prefabricated substations, and new energy electrical systems for overseas distributors and EPC projects.';
		}

		if ( is_page() ) {
			$post = get_post();
			if ( $post instanceof WP_Post ) {
				$excerpt = trim( wp_strip_all_tags( get_the_excerpt( $post ) ) );
				if ( '' !== $excerpt ) {
					return $excerpt;
				}
			}
		}

		return $description;
	}

	/**
	 * Tune Yoast titles for Chinese helper routes.
	 *
	 * @param string $title Current title.
	 * @return string
	 */
	public static function filter_wpseo_title( $title ) {
		if ( self::is_chinese_case_archive_request() ) {
			return '项目案例 - YKE Global';
		}

		if ( self::is_chinese_context() && is_front_page() ) {
			return '中文站 - YKE Global';
		}

		return $title;
	}

	/**
	 * Resolve a seeded page by stable seed key first, then by canonical path.
	 *
	 * @param string $seed_key Stable seed key.
	 * @param string $path     Canonical path.
	 * @return WP_Post|null
	 */
	protected static function find_seeded_page( $seed_key, $path ) {
		$seed_key = trim( (string) $seed_key );
		if ( '' !== $seed_key ) {
			$posts = get_posts(
				array(
					'post_type'      => 'page',
					'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
					'posts_per_page' => 1,
					'meta_key'       => self::SEED_KEY_META,
					'meta_value'     => $seed_key,
					'orderby'        => 'ID',
					'order'          => 'ASC',
				)
			);

			if ( ! empty( $posts[0] ) && $posts[0] instanceof WP_Post ) {
				return $posts[0];
			}
		}

		return self::find_post_by_path( $path, 'page' );
	}

	/**
	 * Resolve a seeded post by stable seed key first, then by slug fallback.
	 *
	 * @param string $seed_key  Stable seed key.
	 * @param string $post_type Post type.
	 * @param string $slug      Fallback slug.
	 * @return WP_Post|null
	 */
	protected static function find_seeded_post( $seed_key, $post_type, $slug ) {
		$seed_key = trim( (string) $seed_key );
		if ( '' !== $seed_key ) {
			$posts = get_posts(
				array(
					'post_type'      => $post_type,
					'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
					'posts_per_page' => 1,
					'meta_key'       => self::SEED_KEY_META,
					'meta_value'     => $seed_key,
					'orderby'        => 'ID',
					'order'          => 'ASC',
				)
			);

			if ( ! empty( $posts[0] ) && $posts[0] instanceof WP_Post ) {
				return $posts[0];
			}
		}

		return self::find_post_by_path( $slug, $post_type );
	}

	/**
	 * Seed content for the contact page.
	 *
	 * @return string
	 */
	protected static function get_seed_contact_content() {
		return '<!-- wp:paragraph --><p>Tell YKE your target market, voltage level, application, standards, and expected lead time. The team can respond with the next practical supply step.</p><!-- /wp:paragraph --><!-- wp:shortcode -->[yke_contact_details show_commitments="1" show_certifications="1"]<!-- /wp:shortcode --><!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><!-- wp:shortcode -->[yke_inquiry_form product_interest="General Inquiry"]<!-- /wp:shortcode -->';
	}

	/**
	 * Seed content for the About page.
	 *
	 * @return string
	 */
	protected static function get_seed_about_content() {
		return '<!-- wp:paragraph --><p>Guangzhou Yunke Electrical Equipment Co., Ltd. supplies switchgear, transformers, prefabricated substations, and new energy electrical systems for overseas industrial and utility buyers.</p><!-- /wp:paragraph --><!-- wp:list --><ul><li>0.4kV to 35kV export-oriented product coverage</li><li>IEC and GB aligned manufacturing approach</li><li>Fast quotation and documentation support for distributors and EPC teams</li><li>Target-market adaptation for heat, dust, humidity, and altitude</li></ul><!-- /wp:list -->';
	}

	/**
	 * Seed content for the distributor page.
	 *
	 * @return string
	 */
	protected static function get_seed_distributor_content() {
		return '<!-- wp:paragraph --><p>YKE uses the website to support regional distributors with product matching, documentation support, and long-term market development.</p><!-- /wp:paragraph --><!-- wp:list --><ul><li>Regional protection and channel clarity</li><li>Technical support for quotations and bidding</li><li>Marketing materials and case-study support</li><li>Structured after-sales response process</li></ul><!-- /wp:list --><!-- wp:shortcode -->[yke_inquiry_form product_interest="Distributor Partnership"]<!-- /wp:shortcode -->';
	}

	/**
	 * Repair or enrich seeded page copy after content sync.
	 */
	public static function repair_seeded_page_copy() {
		$version = (string) get_option( 'yke_site_core_copy_repair_version', '' );
		if ( YKE_SITE_CORE_VERSION === $version ) {
			return;
		}

		$definitions = array(
			'about-yke' => array(
				'title'   => 'About YKE',
				'content' => self::build_enhanced_about_page_content(),
				'markers' => array(
					'yke-stat-band',
				),
			),
			'contact' => array(
			'title'   => 'Contact',
			'content' => self::build_visual_marketing_contact_content( 'en' ),
			'markers' => array(
				'yke-contact-grid',
			),
		),
			'products' => array(
			'title'   => 'Products',
			'content' => self::build_enhanced_products_page_content(),
			'markers' => array(
				'yke-product-grid',
			),
		),
		'products/mv-switchgear' => array(
			'title'   => 'MV Switchgear',
			'content' => self::build_rich_product_page_content( 'mv-switchgear' ),
			'markers' => array( 'Medium-voltage switchgear, ring main units' ),
		),
		'products/lv-switchgear' => array(
			'title'   => 'LV Switchgear',
			'content' => self::build_rich_product_page_content( 'lv-switchgear' ),
			'markers' => array( 'Low-voltage switchgear, motor control centers' ),
		),
		'products/transformers' => array(
			'title'   => 'Transformers',
			'content' => self::build_rich_product_page_content( 'transformers' ),
			'markers' => array( 'Dry-type (SCB11/SCB13) and oil-immersed' ),
		),
		'products/prefabricated-substations' => array(
			'title'   => 'Prefabricated Substations',
			'content' => self::build_rich_product_page_content( 'prefabricated-substations' ),
			'markers' => array( 'Compact packaged substations integrating' ),
		),
		'products/new-energy-electrical' => array(
			'title'   => 'New Energy Electrical',
			'content' => self::build_rich_product_page_content( 'new-energy-electrical' ),
			'markers' => array( 'Solar grid-connection cabinets, BMS distribution' ),
		),
		// Model detail pages (English) - 11 models.
		'products/mv-switchgear/kyn28-12'      => array( 'title' => 'KYN28-12 MV Switchgear',                    'content' => self::build_model_detail_page_content( 'mv-switchgear', 'kyn28-12' ) ),
		'products/lv-switchgear/ggd'           => array( 'title' => 'GGD Fixed LV Switchgear',                   'content' => self::build_model_detail_page_content( 'lv-switchgear', 'ggd' ) ),
		'products/lv-switchgear/gck'           => array( 'title' => 'GCK Withdrawable LV Switchgear',            'content' => self::build_model_detail_page_content( 'lv-switchgear', 'gck' ) ),
		'products/transformers/scb11'          => array( 'title' => 'SCB11 Dry-Type Transformer',               'content' => self::build_model_detail_page_content( 'transformers', 'scb11' ) ),
		'products/transformers/s13'            => array( 'title' => 'S13 Oil-Immersed Transformer',             'content' => self::build_model_detail_page_content( 'transformers', 's13' ) ),
		'products/prefabricated-substations/zgs1-12'              => array( 'title' => 'ZGS1-12/0.4 American-Style Substation',  'content' => self::build_model_detail_page_content( 'prefabricated-substations', 'zgs1-12' ) ),
		'products/prefabricated-substations/yb-12'                => array( 'title' => 'YB-12/0.4 European-Style Substation',   'content' => self::build_model_detail_page_content( 'prefabricated-substations', 'yb-12' ) ),
		'products/new-energy-electrical/pv-grid-cabinet'          => array( 'title' => 'Solar Grid-Connection Cabinet',         'content' => self::build_model_detail_page_content( 'new-energy-electrical', 'pv-grid-cabinet' ) ),
		'products/new-energy-electrical/bms-cabinet'              => array( 'title' => 'BMS Distribution Cabinet',              'content' => self::build_model_detail_page_content( 'new-energy-electrical', 'bms-cabinet' ) ),
		'products/new-energy-electrical/charging-booster'         => array( 'title' => 'Charging Station Booster Substation',   'content' => self::build_model_detail_page_content( 'new-energy-electrical', 'charging-booster' ) ),
		'products/new-energy-electrical/prefab-cabin'             => array( 'title' => 'Prefabricated Equipment Cabin',         'content' => self::build_model_detail_page_content( 'new-energy-electrical', 'prefab-cabin' ) ),
		'distributor-partnership' => array(
				'title'   => 'Distributor Partnership',
				'content' => '<!-- distributor-partnership 页已删除，301 重定向到 /contact/ -->',
				'markers' => array(
					'distributor-deleted',
				),
			),
			'zh' => array(
				'title'   => '中文站',
				'content' => self::build_zh_root_page_content(),
				'markers' => array( '中文站' ),
			),
			'zh/about-yke' => array(
				'title'   => '关于云科',
				'content' => self::build_zh_about_page_content(),
				'markers' => array( '骞垮窞', '关于云科' ),
			),
			'zh/distributor-partnership' => array(
				'title'   => '代理合作',
				'content' => '<!-- zh/distributor-partnership 页已删除，301 重定向到 /zh/contact/ -->',
				'markers' => array( 'distributor-deleted' ),
			),
			'zh/contact' => array(
			'title'   => '联系我们',
			'content' => self::build_visual_marketing_contact_content( 'zh' ),
			'markers' => array( 'yke-contact-grid' ),
		),
			'zh/case-studies' => array(
				'title'   => '项目案例',
				'content' => self::build_zh_case_studies_page_content(),
				'markers' => array( '项目案例' ),
			),
			'zh/products' => array(
				'title'   => '产品总览',
				'content' => self::build_enhanced_products_page_content( 'zh' ),
				'markers' => array( '涓枃浜у搧', '产品总览' ),
			),
			'zh/products/mv-switchgear' => array(
				'title'   => '高压开关柜',
				'content' => self::build_rich_product_page_content( 'mv-switchgear', 'zh' ),
			),
			'zh/products/lv-switchgear' => array(
				'title'   => '低压开关柜',
				'content' => self::build_rich_product_page_content( 'lv-switchgear', 'zh' ),
			),
			'zh/products/transformers' => array(
				'title'   => '变压器',
				'content' => self::build_rich_product_page_content( 'transformers', 'zh' ),
			),
			'zh/products/prefabricated-substations' => array(
				'title'   => '预装式变电站',
				'content' => self::build_rich_product_page_content( 'prefabricated-substations', 'zh' ),
			),
			'zh/products/new-energy-electrical' => array(
				'title'   => '新能源电气',
				'content' => self::build_rich_product_page_content( 'new-energy-electrical', 'zh' ),
			),
			// Model detail pages (Chinese) - 11 models.
			'zh/products/mv-switchgear/kyn28-12'      => array( 'title' => 'KYN28-12 铠装移开式开关柜',     'content' => self::build_model_detail_page_content( 'mv-switchgear', 'kyn28-12', 'zh' ) ),
			'zh/products/lv-switchgear/ggd'           => array( 'title' => 'GGD 固定式低压开关柜',           'content' => self::build_model_detail_page_content( 'lv-switchgear', 'ggd', 'zh' ) ),
			'zh/products/lv-switchgear/gck'           => array( 'title' => 'GCK 抽出式低压开关柜',           'content' => self::build_model_detail_page_content( 'lv-switchgear', 'gck', 'zh' ) ),
			'zh/products/transformers/scb11'          => array( 'title' => 'SCB11 干式变压器',               'content' => self::build_model_detail_page_content( 'transformers', 'scb11', 'zh' ) ),
			'zh/products/transformers/s13'            => array( 'title' => 'S13 油浸式变压器',               'content' => self::build_model_detail_page_content( 'transformers', 's13', 'zh' ) ),
			'zh/products/prefabricated-substations/zgs1-12'              => array( 'title' => 'ZGS1-12/0.4 美式箱变',       'content' => self::build_model_detail_page_content( 'prefabricated-substations', 'zgs1-12', 'zh' ) ),
			'zh/products/prefabricated-substations/yb-12'                => array( 'title' => 'YB-12/0.4 欧式箱变',         'content' => self::build_model_detail_page_content( 'prefabricated-substations', 'yb-12', 'zh' ) ),
			'zh/products/new-energy-electrical/pv-grid-cabinet'          => array( 'title' => '光伏并网柜',                  'content' => self::build_model_detail_page_content( 'new-energy-electrical', 'pv-grid-cabinet', 'zh' ) ),
			'zh/products/new-energy-electrical/bms-cabinet'              => array( 'title' => 'BMS 配电柜',                  'content' => self::build_model_detail_page_content( 'new-energy-electrical', 'bms-cabinet', 'zh' ) ),
			'zh/products/new-energy-electrical/charging-booster'         => array( 'title' => '充电桩升压箱变',              'content' => self::build_model_detail_page_content( 'new-energy-electrical', 'charging-booster', 'zh' ) ),
			'zh/products/new-energy-electrical/prefab-cabin'             => array( 'title' => '预制舱',                      'content' => self::build_model_detail_page_content( 'new-energy-electrical', 'prefab-cabin', 'zh' ) ),
		);

		foreach ( $definitions as $path => $definition ) {
			$post = self::find_post_by_path( $path, 'page' );
			if ( ! $post ) {
				continue;
			}

			if ( '1' !== (string) get_post_meta( $post->ID, '_yke_site_core_seeded', true ) ) {
				continue;
			}

			if ( ! self::should_update_seeded_page_copy( $post, $definition ) ) {
				continue;
			}

			wp_update_post(
				array(
					'ID'           => $post->ID,
					'post_title'   => $definition['title'],
					'post_content' => $definition['content'],
				)
			);
		}

		update_option( 'yke_site_core_copy_repair_version', YKE_SITE_CORE_VERSION );
	}

	/**
	 * Seed content for the Chinese root page.
	 *
	 * @return string
	 */
	protected static function get_seed_zh_root_content() {
		return '<!-- wp:paragraph --><p>广州云科电气设备有限公司中文站入口。浏览产品总览、项目案例、关于云科和代理合作，或直接提交询盘。</p><!-- /wp:paragraph --><!-- wp:list --><ul><li><a href="/zh/about-yke/">关于云科</a></li><li><a href="/zh/distributor-partnership/">代理合作</a></li><li><a href="/zh/contact/">联系我们</a></li><li><a href="/zh/products/">产品总览</a></li><li><a href="/zh/case-studies/">项目案例</a></li></ul><!-- /wp:list -->';
	}

	/**
	 * Seed content for the Chinese About page.
	 *
	 * @return string
	 */
	protected static function get_seed_zh_about_content() {
		return '<!-- wp:paragraph --><p>广州云科电气设备有限公司专注于电力电气设备外贸出口，产品覆盖高低压成套设备、变压器、箱变及新能源电气系统。</p><!-- /wp:paragraph -->';
	}

	/**
	 * Seed content for the Chinese distributor page.
	 *
	 * @return string
	 */
	protected static function get_seed_zh_distributor_content() {
		return '<!-- wp:paragraph --><p>该页面用于中文审阅代理合作相关内容，包括区域保护、技术支持、营销资料和售后流程。</p><!-- /wp:paragraph -->';
	}

	/**
	 * Seed content for the Chinese contact page.
	 *
	 * @return string
	 */
	protected static function get_seed_zh_contact_content() {
		return '<!-- wp:paragraph --><p>通过下方联系方式或询盘表单提交您的需求，云科会在工作日回复匹配方案。</p><!-- /wp:paragraph --><!-- wp:shortcode -->[yke_contact_details show_commitments="1"]<!-- /wp:shortcode --><!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><!-- wp:shortcode -->[yke_inquiry_form product_interest="中文询盘"]<!-- /wp:shortcode -->';
	}

	/**
	 * Seed content for the Chinese products page.
	 *
	 * @return string
	 */
	protected static function get_seed_zh_products_content() {
		return '<!-- wp:paragraph --><p>中文产品总览用于辅助确认 5 大产品线结构，便于内部审阅、招商沟通和外贸资料对照。</p><!-- /wp:paragraph --><!-- wp:list --><ul><li><a href="/zh/products/mv-switchgear/">高压开关柜</a></li><li><a href="/zh/products/lv-switchgear/">低压开关柜</a></li><li><a href="/zh/products/transformers/">变压器</a></li><li><a href="/zh/products/prefabricated-substations/">预装式变电站</a></li><li><a href="/zh/products/new-energy-electrical/">新能源电气</a></li></ul><!-- /wp:list -->';
	}

	/**
	 * Seed content for a Chinese product detail page.
	 *
	 * @param string $title   Page title.
	 * @param string $summary Product summary.
	 * @return string
	 */
	protected static function get_seed_zh_product_page_content( $title, $summary ) {
		return '<!-- wp:paragraph --><p>' . esc_html( $summary ) . '</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>如需进一步了解配置、元器件品牌或交期，请提交询盘。</p><!-- /wp:paragraph -->';
	}

	/**
	 * Seed content for the products index page.
	 *
	 * @return string
	 */
	protected static function get_seed_products_content() {
		return '<!-- wp:paragraph --><p>YKE organizes its export offering into five product lines so project teams can move quickly from inquiry to technical discussion.</p><!-- /wp:paragraph --><!-- wp:list --><ul><li><a href="/products/mv-switchgear/">MV Switchgear</a></li><li><a href="/products/lv-switchgear/">LV Switchgear</a></li><li><a href="/products/transformers/">Transformers</a></li><li><a href="/products/prefabricated-substations/">Prefabricated Substations</a></li><li><a href="/products/new-energy-electrical/">New Energy Electrical</a></li></ul><!-- /wp:list --><!-- wp:paragraph --><p>Need a recommendation? Use the inquiry form on any product page or the main contact page.</p><!-- /wp:paragraph -->';
	}

	/**
	 * Seed content for a product detail page.
	 *
	 * @param string $title      Page title.
	 * @param string $summary    Product summary.
	 * @param array  $highlights Highlights list.
	 * @return string
	 */
	protected static function get_seed_product_page_content( $title, $summary, $highlights ) {
		$list_items = '';
		foreach ( $highlights as $highlight ) {
			$list_items .= '<li>' . esc_html( $highlight ) . '</li>';
		}

		return '<!-- wp:paragraph --><p>' . esc_html( $summary ) . '</p><!-- /wp:paragraph --><!-- wp:list --><ul>' . $list_items . '</ul><!-- /wp:list --><!-- wp:shortcode -->[yke_inquiry_form product_interest="' . esc_attr( $title ) . '"]<!-- /wp:shortcode -->';
	}

	/**
	 * Build seed case study paragraphs.
	 *
	 * @param string $first  First paragraph.
	 * @param string $second Second paragraph.
	 * @return string
	 */
	protected static function build_seed_case_content( $first, $second ) {
		return '<!-- wp:paragraph --><p>' . esc_html( $first ) . '</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>' . esc_html( $second ) . '</p><!-- /wp:paragraph -->';
	}

	/**
	 * Public translation helper for use in theme patterns and templates.
	 *
	 * @param string $key String key in the translation dictionary.
	 * @param string $lang Language slug. Defaults to current language.
	 * @return string
	 */
	public static function translate( $key, $lang = null ) {
		return self::t( $key, $lang );
	}

	/**
	 * Render a lightweight site navigation.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_site_nav( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'layout' => 'header',
			),
			$atts,
			'yke_site_nav'
		);

		$lang = self::get_current_site_language();
		$home = self::get_seeded_page_url( '', $lang );

		$product_lines_meta = array(
			'mv-switchgear'             => self::t( 'product.mv_switchgear', $lang ),
			'lv-switchgear'             => self::t( 'product.lv_switchgear', $lang ),
			'transformers'              => self::t( 'product.transformers', $lang ),
			'prefabricated-substations' => self::t( 'product.prefabricated_substations', $lang ),
			'new-energy-electrical'     => self::t( 'product.new_energy_electrical', $lang ),
		);
		$product_children = array();
		foreach ( $product_lines_meta as $line_key => $line_label ) {
			$models   = self::get_product_models_for_line( $line_key );
			$line_url = self::get_seeded_page_url( 'products/' . $line_key, $lang );
			$model_items = array();
			foreach ( $models as $m ) {
				$model_items[] = array(
					'label' => 'zh' === $lang
						? ( isset( $m['name_zh'] ) ? $m['name_zh'] : ( isset( $m['name'] ) ? $m['name'] : $m['slug'] ) )
						: ( isset( $m['name'] ) ? $m['name'] : ( isset( $m['name_zh'] ) ? $m['name_zh'] : $m['slug'] ) ),
					'url'   => $line_url . $m['slug'] . '/',
				);
			}
			$product_children[] = array(
				'label'    => $line_label,
				'url'      => $line_url,
				'children' => $model_items,
			);
		}

		$links = array(
			array(
				'label' => self::t( 'nav.home', $lang ),
				'url'   => $home,
			),
			array(
				'label' => self::t( 'nav.products', $lang ),
				'url'   => self::get_seeded_page_url( 'products', $lang ),
				'children' => $product_children,
			),
			array(
				'label' => self::t( 'nav.case_studies', $lang ),
				'url'   => self::get_case_studies_url( $lang ),
			),
			array(
				'label' => self::t( 'nav.about_yke', $lang ),
				'url'   => self::get_seeded_page_url( 'about-yke', $lang ),
			),
			array(
				'label' => self::t( 'nav.contact', $lang ),
				'url'   => self::get_seeded_page_url( 'contact', $lang ),
			),
		);

		$is_header = ( 'header' === $atts['layout'] );
		$class     = $is_header ? 'yke-site-nav yke-site-nav--header' : 'yke-site-nav yke-site-nav--footer';
		$html      = $is_header ? '<div class="yke-header__navcluster">' : '';
		$html     .= '<nav class="' . esc_attr( $class ) . '"><ul>';

		// Current-page detection: compare trimmed path of request vs each link.
		$current_path = '';
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$current_path = trim( (string) parse_url( (string) wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ), '/' );
		}

		// Recursive nav renderer — supports 2/3/N level menus driven by data.
		$render_nav_items = function( $items ) use ( &$render_nav_items, $current_path ) {
			$out = '<ul class="sub-menu">';
			foreach ( $items as $item ) {
				$item_path    = trim( (string) parse_url( (string) $item['url'], PHP_URL_PATH ), '/' );
				$item_current = ( $current_path === $item_path || 0 === strpos( $current_path, $item_path . '/' ) );
				$li_classes   = array();
				if ( $item_current ) {
					$li_classes[] = 'current-menu-item';
				}
				if ( ! empty( $item['children'] ) ) {
					$li_classes[] = 'menu-item-has-children';
				}
				$li_class = ! empty( $li_classes ) ? ' class="' . esc_attr( implode( ' ', $li_classes ) ) . '"' : '';
				$aria     = $item_current ? ' aria-current="page"' : '';
				$out .= sprintf(
					'<li%1$s><a href="%2$s"%3$s>%4$s</a>',
					$li_class,
					esc_url( $item['url'] ),
					$aria,
					esc_html( $item['label'] )
				);
				if ( ! empty( $item['children'] ) ) {
					$out .= $render_nav_items( $item['children'] );
				}
				$out .= '</li>';
			}
			$out .= '</ul>';
			return $out;
		};

		foreach ( $links as $link ) {
			$link_path = trim( (string) parse_url( (string) $link['url'], PHP_URL_PATH ), '/' );

			// Match logic:
			// - Home: only match when current path is empty (root) OR equals the language-prefixed root (e.g. 'zh').
			// - Others: prefix match (so /products/mv-switchgear highlights Products).
			$is_current = false;
			if ( '' === $link_path || 'zh' === $link_path ) {
				$is_current = ( '' === $current_path || 'zh' === $current_path );
			} else {
				$is_current = ( $current_path === $link_path || 0 === strpos( $current_path, $link_path . '/' ) );
			}

			$has_children = ! empty( $link['children'] );
			$li_classes   = array();
			if ( $is_current ) {
				$li_classes[] = 'current-menu-item';
			}
			if ( $has_children ) {
				$li_classes[] = 'menu-item-has-children';
			}
			$li_class = ! empty( $li_classes ) ? ' class="' . esc_attr( implode( ' ', $li_classes ) ) . '"' : '';
			$aria     = $is_current ? ' aria-current="page"' : '';
			$html .= sprintf(
				'<li%1$s><a href="%2$s"%3$s>%4$s</a>',
				$li_class,
				esc_url( $link['url'] ),
				$aria,
				esc_html( $link['label'] )
			);
			if ( $has_children ) {
				$html .= $render_nav_items( $link['children'] );
			}
			$html .= '</li>';
		}

		$html .= '</ul></nav>';

		// Language switch for header nav — dynamic from Polylang.
		if ( $is_header ) {
			$pll_languages = array();
			if ( function_exists( 'pll_languages_list' ) ) {
				$pll_languages = pll_languages_list( array( 'fields' => 'slug' ) );
			}
			if ( empty( $pll_languages ) ) {
				$pll_languages = array( 'en', 'zh' );
			}

			$lang_names = array(
				'en' => 'English',
				'zh' => '中文',
				'am' => 'አማርኛ',
			);

			$current_name = isset( $lang_names[ $lang ] ) ? $lang_names[ $lang ] : strtoupper( $lang );

			$html .= '<div class="yke-lang-dropdown">';
			$html .= '<button type="button" class="yke-lang-dropdown__trigger" aria-haspopup="true" aria-expanded="false">';
			$html .= '<span class="yke-lang-dropdown__label">' . esc_html( $current_name ) . '</span>';
			$html .= '<svg class="yke-lang-dropdown__chevron" width="10" height="10" viewBox="0 0 10 10" aria-hidden="true"><path d="M2 3.5L5 6.5L8 3.5" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			$html .= '</button>';
			$html .= '<ul class="yke-lang-dropdown__menu" role="menu">';
			foreach ( $pll_languages as $pll_lang ) {
				$display_name = isset( $lang_names[ $pll_lang ] ) ? $lang_names[ $pll_lang ] : strtoupper( $pll_lang );
				$switch_url   = self::get_language_switch_url( $pll_lang );
				$is_current   = ( $pll_lang === $lang );
				$active_class = $is_current ? ' yke-lang-dropdown__item--active' : '';
				$html .= '<li role="none"><a href="' . esc_url( $switch_url ) . '" class="yke-lang-dropdown__item' . $active_class . '" role="menuitem"' . ( $is_current ? ' aria-current="true"' : '' ) . '>' . esc_html( $display_name ) . '</a></li>';
			}
			$html .= '</ul>';
			$html .= '</div>';
		}

		return $html;
	}

	/**
	 * Render localized header call-to-action.
	 *
	 * @return string
	 */
	public static function render_header_cta() {
		$lang  = self::get_current_site_language();

		return sprintf(
			'<a href="%1$s" class="yke-btn yke-btn--cta">%2$s</a>',
			esc_url( self::get_seeded_page_url( 'contact', $lang ) ),
			esc_html( self::t( 'btn.get_quote', $lang ) )
		);
	}

	/**
	 * Render page hero section.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_page_hero( $atts = array() ) {
		$lang  = self::get_current_site_language();
		$is_zh = ( 'zh' === $lang );
		$request_path = self::normalize_seed_path_for_language( self::get_request_path() );

		if ( is_post_type_archive( 'case_study' ) || self::is_chinese_case_archive_request() ) {
			$hero_post = self::find_post_by_path( $is_zh ? 'zh/case-studies' : 'case-studies', 'page' );
			if ( $hero_post instanceof WP_Post ) {
				return self::render_marketing_page_hero( $hero_post, $lang, $is_zh );
			}
		}

		if ( 0 === strpos( $request_path, 'products/' ) && substr_count( $request_path, '/' ) >= 2 ) {
			return '';
		}

		$post = get_post();
		if ( ! $post ) {
			return '';
		}

		if ( 0 === strpos( $request_path, 'products/' ) ) {
			return self::render_product_line_page_hero( $request_path, $lang );
		}

		return self::render_marketing_page_hero( $post, $lang, $is_zh );

		// Page-specific hero data
		$slugs = array(
			'products'                => array(
				'kicker' => $is_zh ? '产品中心' : 'Our Products',
				'title'  => $is_zh ? '五大产品线覆盖0.4kV至35kV' : 'Five Product Lines, 0.4kV to 35kV',
				'desc'   => $is_zh ? 'IEC与GB标准制造，灵活的元器件品牌选择，为海外分销商和EPC团队提供出口就绪的技术文件。' : 'IEC and GB aligned manufacturing, flexible component brands, and export-ready documentation for distributors and EPC teams worldwide.',
			),
			'case-studies'            => array(
				'kicker' => $is_zh ? '项目案例' : 'Case Studies',
				'title'  => $is_zh ? '海外项目交付记录' : 'Export Project References',
				'desc'   => $is_zh ? '近期向中东、东南亚、非洲等市场交付的中低压配电设备项目。' : 'Recent medium- and low-voltage distribution deliveries across the Middle East, Southeast Asia, and Africa.',
			),
			'about-yke'               => array(
				'kicker' => $is_zh ? '关于云科' : 'About YKE',
				'title'  => $is_zh ? '广州云科电气设备有限公司' : 'Guangzhou Yunke Electrical Equipment Co., Ltd.',
				'desc'   => $is_zh ? '云科电气是一家面向出口的配电设备制造商，服务于工业、电力、基础设施和新能源项目，产品出口至100+国家。' : 'YKE is an export-oriented power distribution manufacturer serving industrial, utility, infrastructure, and renewable projects across more than 100 countries.',
			),
			'distributor-partnership' => array(
				'kicker' => $is_zh ? '代理合作' : 'Distributor Partnership',
				'title'  => $is_zh ? '与云科共建海外配电市场' : 'Build Overseas Distribution Markets with YKE',
				'desc'   => $is_zh ? '区域保护、技术支持、营销资料、售后流程——云科为合作伙伴提供完整的代理支持体系。' : 'Territory protection, technical support, marketing materials, and after-sales processes — YKE provides a complete distributor support system.',
			),
			'contact'                 => array(
				'kicker' => $is_zh ? '联系我们' : 'Contact Us',
				'title'  => $is_zh ? '与云科销售团队直接沟通' : 'Talk Directly to the YKE Sales Team',
				'desc'   => $is_zh ? '请提供目标国家、电压等级、应用场景和期望交期，云科将回复下一步方案。' : 'Send the destination country, voltage level, application scenario, and expected lead time. YKE will reply with the next-step proposal.',
			),
		);

		// Match by post slug
		$slug = $post->post_name;
		$data = isset( $slugs[ $slug ] ) ? $slugs[ $slug ] : null;

		// Fallback: use post title
		if ( ! $data ) {
			$data = array(
				'kicker' => $is_zh ? '页面' : 'Page',
				'title'  => get_the_title( $post ),
				'desc'   => '',
			);
		}

		return '<section class="yke-page-hero"><div class="yke-page-hero-inner">'
			. '<p class="yke-kicker">' . esc_html( $data['kicker'] ) . '</p>'
			. '<h1>' . esc_html( $data['title'] ) . '</h1>'
			. ( ! empty( $data['desc'] ) ? '<p>' . esc_html( $data['desc'] ) . '</p>' : '' )
			. '</div></section>';
	}

	/**
	 * Render setup notice.
	 */
	public static function render_setup_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$issues = self::get_setup_issues();
		if ( empty( $issues ) ) {
			return;
		}

		echo '<div class="notice notice-warning"><p><strong>YKE launch checklist</strong></p><ul>';
		foreach ( $issues as $issue ) {
			echo '<li>' . esc_html( $issue ) . '</li>';
		}
		echo '</ul></div>';
	}

	/**
	 * Append enhanced sections to key pages.
	 *
	 * @param string $content Existing content.
	 * @return string
	 */
	public static function append_page_enhancements( $content ) {
		if ( is_admin() || ! is_page() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post = get_post();
		if ( ! $post instanceof WP_Post ) {
			return $content;
		}

		$page_path = self::get_page_path( $post );
		$normalized_path = self::normalize_seed_path_for_language( $page_path );
		switch ( $normalized_path ) {
			case 'contact':
				return $content;
			case 'case-studies':
				return $content . self::build_case_studies_listing_markup();
			default:
				if ( 0 === strpos( $normalized_path, 'products/' ) ) {
					return $content;
				}
		}

		return $content;
	}

	/**
	 * Localize hard-coded theme block output for Chinese pages.
	 *
	 * @param string $block_content Rendered content.
	 * @param array  $block Parsed block.
	 * @return string
	 */
	public static function localize_theme_block_output( $block_content, $block ) {
		unset( $block );

		if ( ! self::is_chinese_context() ) {
			return $block_content;
		}

		$replacements = array(
			'href="/products/"'                                  => 'href="/zh/products/"',
			'href="/products/mv-switchgear/"'                    => 'href="/zh/products/mv-switchgear/"',
			'href="/products/lv-switchgear/"'                    => 'href="/zh/products/lv-switchgear/"',
			'href="/products/transformers/"'                     => 'href="/zh/products/transformers/"',
			'href="/products/prefabricated-substations/"'        => 'href="/zh/products/prefabricated-substations/"',
			'href="/products/new-energy-electrical/"'            => 'href="/zh/products/new-energy-electrical/"',
			'href="/case-studies/"'                              => 'href="/zh/case-studies/"',
			'href="/about-yke/"'                                 => 'href="/zh/about-yke/"',
			'href="/distributor-partnership/"'                   => 'href="/zh/distributor-partnership/"',
			'href="/contact/"'                                   => 'href="/zh/contact/"',
			'>Home<'                                             => '>首页<',
			'>Products<'                                         => '>产品<',
			'>Case Studies<'                                     => '>项目案例<',
			'>About YKE<'                                        => '>关于云科<',
			'>Distributor Partnership<'                          => '>代理合作<',
			'>Contact<'                                          => '>联系<',
			'>中文<'                                             => '>English<',
			'Global power distribution solutions expert'         => '全球配电解决方案专家',
			'Reliable Power, Delivered Worldwide.'               => '可靠电力，交付全球。',
			'YKE helps distributors, EPC contractors, and industrial buyers source IEC-aligned switchgear, transformers, prefabricated substations, and new energy electrical systems for emerging markets.' => '云科为经销商、EPC 承包商和工业采购方提供符合 IEC 体系的开关柜、变压器、预装式变电站和新能源电气系统，适配新兴市场项目交付。',
			'0.4kV to 35kV product coverage'                     => '0.4kV 至 35kV 产品覆盖',
			'IEC / GB aligned manufacturing'                     => 'IEC / GB 体系制造',
			'Quotation and matched proposal on business days'    => '工作日提供报价与匹配方案',
			'18-month standard warranty'                         => '18 个月标准质保',
			'100+ export countries and regions'                  => '100+ 出口国家和地区',
			'Chinese site is available under <strong>/zh/</strong>.' => '中文站可在 <strong>/zh/</strong> 查看。',
			'Quick inquiry'                                      => '快速询盘',
			'Tell us what you need'                              => '告诉我们您的需求',
			'Voltage level, application, destination market, delivery window, and any required standards are enough to start.' => '提供电压等级、应用场景、目标市场、交期和标准要求，就足以开始匹配方案。',
			'Send Inquiry'                                       => '提交询盘',
			'Send inquiry'                                       => '提交询盘',
			'View Products'                                      => '查看产品',
			'Buyer\'s quick glance'                              => '采购速览',
			'Buyer’s quick glance'                               => '采购速览',
			'Key buying signals, all in one place'               => '核心采购信息，一屏看清',
			'Voltage range: 0.4kV to 35kV'                       => '电压范围：0.4kV 至 35kV',
			'Certifications: CE / CCC / ISO 9001 / ISO 14001 / ISO 45001' => '认证：按 IEC 标准组织生产，安排 SGS、BV、Intertek 第三方验货',
			'IEC-aligned production with SGS, BV, and Intertek third-party inspection support' => '按 IEC 标准组织生产，安排 SGS、BV、Intertek 第三方验货',
			'MOQ: starts from 1 unit'                            => 'MOQ：1 台起',
			'Standard lead time: 30 to 45 days'                  => '常规交期：30 至 45 天',
			'Payment: T/T, L/C, D/P negotiable'                  => '付款方式：T/T、L/C、D/P 可协商',
			'Inspection: SGS / BV / Intertek welcome'            => '验货：欢迎 SGS / BV / Intertek',
			'Warranty: standard 18 months'                       => '质保：标准 18 个月',
			'Languages: English-first, Chinese support, localized documents on request' => '语言支持：英文优先，支持中文，按需提供本地化文件',
			'Product system'                                     => '产品体系',
			'Five product lines built for export projects'       => '围绕出口项目搭建的五大产品线',
			'MV Switchgear'                                      => '高压开关柜',
			'LV Switchgear'                                      => '低压开关柜',
			'Transformers'                                       => '变压器',
			'Prefabricated Substations'                          => '预装式变电站',
			'New Energy Electrical'                              => '新能源电气',
			'12kV to 40.5kV switchgear, RMUs, and outdoor switchboxes for substations, industrial parks, and new energy sites.' => '12kV 至 40.5kV 开关柜、环网柜和户外开关箱，适用于变电站、工业园区和新能源项目。',
			'400V and below fixed and withdrawable switchgear, compensation cabinets, and control cabinets with flexible component options.' => '400V 及以下固定式、抽出式开关柜，补偿柜和控制柜，元件组合灵活。',
			'Dry-type and oil-immersed transformers from 30kVA to 10,000kVA, including high-temperature and high-altitude custom versions.' => '覆盖 30kVA 至 10,000kVA 的干式与油浸式变压器，并支持高温、高海拔定制。',
			'American-style, European-style, Chinese-style, and renewable-ready substations built for rapid deployment on overseas projects.' => '支持美式、欧式、中式及新能源配套箱变，适合海外项目快速部署。',
			'Solar grid-connection cabinets, BMS distribution cabinets, charging support substations, prefabricated cabins, and busbar systems.' => '覆盖光伏并网柜、BMS 配电柜、充电配套箱变、预制舱和母线系统。',
			'Explore'                                            => '查看详情',
			'Why YKE'                                            => '为什么选择云科',
			'Built for distributors who need reliable delivery, not guesswork' => '为重视稳定交付而非碰运气的经销商而建',
			'Customer First'                                     => '客户优先',
			'Dedicated foreign-trade support from inquiry to customs documents, with quotation and matched proposal on business days.' => '从询盘到报关资料全程配合，工作日提供报价与匹配方案。',
			'Quality Driven'                                     => '质量驱动',
			'IEC and GB aligned production, 100% outgoing tests, and transparent third-party inspection support.' => '按 IEC 和 GB 体系组织生产，100% 出厂测试，并支持透明的第三方验货。',
			'Innovation Led'                                     => '创新适配',
			'Special configurations for high heat, dust, condensation, altitude, and renewable integration requirements.' => '可针对高温、多尘、凝露、高海拔和新能源并网需求做专项配置。',
			'Global Reach'                                       => '全球覆盖',
			'100+ export countries and regions across the Middle East, Southeast Asia, Africa, and Latin America with distributor-friendly positioning.' => '已覆盖中东、东南亚、非洲和拉美等 100+ 出口国家与地区，适合经销合作拓展。',
			'Exported countries and regions'                     => '出口国家和地区',
			'Regional protection conflicts in the past 5 years'  => '近 5 年区域保护冲突',
			'Months standard warranty commitment'                => '个月标准质保承诺',
			'After-sales responsibility confirmation target'     => '售后责任确认时效目标',
			'Market fit'                                         => '市场适配',
			'Configured around target market conditions'         => '围绕目标市场工况做适配',
			'Middle East'                                        => '中东',
			'High-temperature and IP65+ dust-proof options for utility, industrial park, and substation environments where SASO-style compliance matters.' => '针对公用事业、工业园区和变电站环境提供高温与 IP65+ 防尘配置，适配 SASO 类审查要求。',
			'Southeast Asia'                                     => '东南亚',
			'Fast-delivery supply, anti-condensation designs, and localized technical documents for manufacturing relocation and renewable growth.' => '围绕制造业迁移和新能源增长需求，提供快速交付、防凝露设计和本地化技术文件。',
			'Africa'                                             => '非洲',
			'Cost-controlled oil-immersed transformers and low-voltage systems aligned with grid upgrades, industrial loads, and utility distribution needs.' => '提供兼顾成本的油浸式变压器和低压系统，适配电网升级、工业负荷和公用配电需求。',
			'Latin America &amp; South Asia'                     => '拉美与南亚',
			'One-stop procurement for solar, storage, and step-up substations with support around local utility acceptance and EPC coordination.' => '面向光伏、储能和升压站项目提供一站式采购，并支持当地电网验收及 EPC 协同。',
			'Project proof'                                      => '项目证明',
			'Recent export cases that hold up under due diligence' => '经得起尽调审查的近期出口案例',
			'Case Studies Archive'                                => '项目案例',
			'Recent cross-border power distribution projects delivered to EPC contractors, local utilities, and industrial buyers across key emerging markets.' => '覆盖 EPC 承包商、本地公用事业客户和工业采购方的跨境配电项目交付记录。',
			'No case studies found yet.'                          => '暂无案例内容。',
			'Quality &amp; certifications'                       => '质量与认证',
			'Visible control, export-grade documentation'        => '过程可见，资料满足出口交付',
			'IEC 61439-aligned product compliance'               => '产品设计满足 IEC 61439 相关要求',
			'CE / CCC / ISO 9001 / ISO 14001 / ISO 45001 certification set' => '具备 CE / CCC / ISO 9001 / ISO 14001 / ISO 45001 认证体系',
			'100% outgoing tests with archived records'          => '100% 出厂测试并留档',
			'Third-party inspection by SGS, BV, or Intertek welcomed' => '欢迎 SGS、BV、Intertek 第三方验货',
			'Custom configurations for high heat, dust, humidity, and altitude' => '支持高温、多尘、潮湿和高海拔工况定制',
			'OEM/ODM support for enclosure, nameplate, voltage, and components' => '支持箱体、铭牌、电压等级和元件的 OEM/ODM 配置',
			'Chinese and English documentation packages delivered with cargo' => '可随货提供中英文资料包',
			'Export-ready anti-moisture and shock-resistant packaging' => '具备防潮、防震的出口包装方案',
			'Distributor partnership'                            => '代理合作',
			'A site built to recruit and support regional partners' => '围绕区域合作伙伴招募与支持而搭建',
			'Exclusive regional protection written into contracts' => '区域独家保护可写入合作协议',
			'Tiered annual rebates and major-project incentives' => '支持年度阶梯返利和大项目激励',
			'Multilingual brochures, image kits, and competitor comparison materials' => '可提供多语言画册、图片素材和竞品对比资料',
			'Technical specification and bidding support from HQ' => '总部支持技术规格确认与投标资料配合',
			'OEM/ODM delivery paths for distributor-owned branding' => '支持经销商品牌化的 OEM/ODM 交付路径',
			'After-sales escalation handled on business days' => '售后升级问题在工作日跟进处理',
			'See Partnership Details'                            => '查看合作详情',
			'Selected delivery records'                          => '精选交付记录',
			'Read case'                                          => '查看案例',
			'View All Case Studies'                              => '查看全部案例',
			'Need a quotation, technical match, or distributor discussion?' => '需要报价、技术匹配或代理合作沟通？',
			'Send the destination country, voltage level, application scenario, and expected lead time. YKE will reply with the next-step proposal.' => '请发送目的国、电压等级、应用场景和预期交期，云科会回复下一步建议方案。',
			'Contact YKE'                                        => '联系云科',
			'Browse Product Lines'                               => '浏览产品线',
			'Request a similar solution'                         => '咨询同类方案',
			'Share your target market, capacity, standards, and timeline. YKE will reply with a matching supply proposal.' => '请提供目标市场、容量、执行标准和项目周期，云科会回复匹配的供货方案。',
			'Case Study Inquiry'                                 => '案例相关询盘',
			'Tell YKE your destination market, voltage level, application, standards, and target lead time. That is enough for the sales team to suggest matching product lines and representative configurations.' => '请告诉云科您的目标市场、电压等级、应用场景、执行标准和目标交期，这些信息已足够销售团队给出匹配产品线和代表性配置建议。',
			'Name'                                               => '姓名',
			'Company'                                            => '公司',
			'Country / Market'                                   => '国家 / 市场',
			'Email'                                              => '邮箱',
			'WhatsApp / Phone'                                   => 'WhatsApp / 电话',
			'Product Interest'                                   => '意向产品',
			'Project Requirement'                                => '项目需求',
			'Phone'                                              => '电话',
			'WhatsApp'                                           => 'WhatsApp',
			'Address'                                            => '地址',
			'Business Hours'                                     => '工作时间',
			'Service Commitments'                                => '服务承诺',
			'Certifications'                                     => '认证',
			'General Inquiry'                                    => '通用询盘',
			'Quick Links'                                        => '快捷链接',
			'Reliable Power, Delivered Worldwide. Guangzhou Yunke Electrical Equipment Co., Ltd. supplies IEC-aligned switchgear, transformers, substations, and new energy electrical systems for emerging markets.' => '可靠电力，交付全球。广州云科电气设备有限公司为新兴市场提供符合 IEC 体系的开关柜、变压器、预装式变电站和新能源电气系统。',
			'Copyright 2026 Guangzhou Yunke Electrical Equipment Co., Ltd. All rights reserved.' => 'Copyright 2026 广州云科电气设备有限公司 版权所有。',
		);

		return strtr( $block_content, $replacements );
	}

	/**
	 * Render analytics and verification tags.
	 */
	public static function render_head_integrations() {
		$google_verification = trim( self::get_setting( 'google_site_verification' ) );
		$bing_verification   = trim( self::get_setting( 'bing_site_verification' ) );
		$ga_measurement_id   = trim( self::get_setting( 'ga_measurement_id' ) );
		$gtm_container_id    = trim( self::get_setting( 'gtm_container_id' ) );
		$meta_pixel_id       = trim( self::get_setting( 'meta_pixel_id' ) );

		if ( $google_verification ) {
			printf( "<meta name=\"google-site-verification\" content=\"%s\" />\n", esc_attr( $google_verification ) );
		}

		if ( $bing_verification ) {
			printf( "<meta name=\"msvalidate.01\" content=\"%s\" />\n", esc_attr( $bing_verification ) );
		}

		if ( $ga_measurement_id ) {
			printf( "<script async src=\"https://www.googletagmanager.com/gtag/js?id=%s\"></script>\n", esc_attr( $ga_measurement_id ) );
			printf( "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','%s');</script>\n", esc_js( $ga_measurement_id ) );
		}

		if ( $meta_pixel_id ) {
			printf( "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','%s');fbq('track','PageView');</script>\n", esc_js( $meta_pixel_id ) );
		}

		if ( $gtm_container_id ) {
			printf( "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','%s');</script>\n", esc_js( $gtm_container_id ) );
		}
	}

	/**
	 * Render body integrations.
	 */
	public static function render_body_integrations() {
		$gtm_container_id = trim( self::get_setting( 'gtm_container_id' ) );
		if ( ! $gtm_container_id ) {
			return;
		}

		printf(
			'<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=%1$s" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>',
			esc_attr( $gtm_container_id )
		);
	}

	/**
	 * Ensure case studies receive English language assignment.
	 */
	protected static function ensure_polylang_case_study_languages() {
		if ( ! function_exists( 'pll_set_post_language' ) ) {
			return;
		}

		$posts = get_posts(
			array(
				'post_type'      => 'case_study',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);

		foreach ( $posts as $post ) {
			if ( function_exists( 'pll_get_post_language' ) && pll_get_post_language( $post->ID ) ) {
				continue;
			}

			pll_set_post_language( $post->ID, 'en' );
		}
	}

	/**
	 * Sync anti-spam integration settings into CF7.
	 */
	protected static function ensure_cf7_spam_protection_settings() {
		if ( ! class_exists( 'WPCF7' ) ) {
			return;
		}

		$turnstile_sitekey = trim( self::get_setting( 'cf7_turnstile_sitekey' ) );
		$turnstile_secret  = trim( self::get_setting( 'cf7_turnstile_secret' ) );
		if ( $turnstile_sitekey && $turnstile_secret ) {
			\WPCF7::update_option( 'turnstile', array( $turnstile_sitekey => $turnstile_secret ) );
		}

		$recaptcha_sitekey = trim( self::get_setting( 'cf7_recaptcha_sitekey' ) );
		$recaptcha_secret  = trim( self::get_setting( 'cf7_recaptcha_secret' ) );
		if ( $recaptcha_sitekey && $recaptcha_secret ) {
			\WPCF7::update_option( 'recaptcha', array( $recaptcha_sitekey => $recaptcha_secret ) );
		}
	}

	/**
	 * Collect launch-critical setup issues.
	 *
	 * @return array
	 */
	protected static function get_setup_issues() {
		$issues = array();

		if ( ! self::get_setting( 'smtp_host' ) || ! self::get_setting( 'smtp_user' ) || ! self::get_setting( 'smtp_pass' ) ) {
			$issues[] = 'SMTP host, username, and password are still incomplete.';
		}

		if ( false !== strpos( self::get_setting( 'contact_phone' ), 'XXX' ) || false !== strpos( self::get_setting( 'contact_whatsapp' ), 'XXX' ) ) {
			$issues[] = 'Phone and WhatsApp still use placeholder values.';
		}

		if ( false !== strpos( self::get_setting( 'contact_address' ), 'pending final confirmation' ) ) {
			$issues[] = 'Contact address still needs final confirmation.';
		}

		if ( ! self::get_setting( 'cf7_turnstile_sitekey' ) && ! self::get_setting( 'cf7_recaptcha_sitekey' ) ) {
			$issues[] = 'No anti-spam key is configured for Contact Form 7 yet.';
		}

		return $issues;
	}

	/**
	 * Build enhanced product matrix markup.
	 *
	 * @return string
	 */
	protected static function build_products_matrix_markup() {
		$lang     = self::get_current_site_language();
		$profiles = self::get_product_line_profiles();
		$order    = array( 'mv-switchgear', 'lv-switchgear', 'transformers', 'prefabricated-substations', 'new-energy-electrical' );
		$title    = self::t( 'section.product_lines', $lang );
		$html     = '<section class="yke-page-section"><h2>' . esc_html( $title ) . '</h2><div class="yke-page-grid">';

		foreach ( $order as $slug ) {
			if ( empty( $profiles[ $slug ] ) ) {
				continue;
			}

			$data = ! empty( $profiles[ $slug ][ $lang ] ) ? $profiles[ $slug ][ $lang ] : $profiles[ $slug ]['en'];
			$page = self::get_product_page_by_slug( $slug, $lang );
			$term = get_term_by( 'slug', $slug, 'product_line' );
			$label = $term ? $term->name : $slug;
			if ( 'zh' === $lang ) {
				$label = ! empty( $data['models'][0] ) && false === strpos( $data['models'][0], ' ' ) ? $label : $label;
			}

			$link = $page ? sprintf( '<p><a href="%1$s">%2$s</a></p>', esc_url( get_permalink( $page ) ), esc_html( self::t( 'btn.view_details', $lang ) ) ) : '';
			$html .= '<div class="yke-card"><h3>' . esc_html( self::get_product_display_title( $slug, $lang ) ) . '</h3><p>' . esc_html( $data['overview'] ) . '</p>' . $link . '</div>';
		}

		$html .= '</div></section>';

		return $html;
	}

	/**
	 * Build a case-studies listing section for the helper Chinese page.
	 *
	 * @return string
	 */
	protected static function build_case_studies_listing_markup() {
		$lang  = self::get_current_site_language();
		$posts = get_posts(
			array(
				'post_type'      => 'case_study',
				'post_status'    => 'publish',
				'posts_per_page' => 6,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'lang'           => function_exists( 'pll_current_language' ) ? $lang : '',
			)
		);

		$labels = 'zh' === $lang
			? array(
				'section' => '项目案例速览',
				'intro'   => '首版已录入 4 个代表性项目案例，便于内部审阅、招商沟通和目标市场适配说明。',
				'empty'   => self::t( 'misc.case_syncing', $lang ),
				'cta'     => self::t( 'btn.view_case', $lang ),
			)
			: array(
				'section' => 'Case Studies Snapshot',
				'intro'   => 'Representative delivery records across the Middle East, Southeast Asia, and Africa.',
				'empty'   => self::t( 'misc.case_syncing', $lang ),
				'cta'     => self::t( 'btn.view_case', $lang ),
			);

		$html = '<section class="yke-page-section"><h2>' . esc_html( $labels['section'] ) . '</h2><p>' . esc_html( $labels['intro'] ) . '</p><div class="yke-page-grid">';

		if ( empty( $posts ) ) {
			$html .= '<div class="yke-card"><p>' . esc_html( $labels['empty'] ) . '</p></div></div></section>';
			return $html;
		}

		foreach ( $posts as $post ) {
			$region_terms = get_the_terms( $post, 'case_region' );
			$region       = ! empty( $region_terms ) && ! is_wp_error( $region_terms ) ? $region_terms[0]->name : '';
			$excerpt      = trim( wp_strip_all_tags( get_the_excerpt( $post ) ) );
			$html        .= '<div class="yke-card"><h3>' . esc_html( get_the_title( $post ) ) . '</h3>';
			if ( '' !== $region ) {
				$html .= '<p><strong>' . esc_html( $region ) . '</strong></p>';
			}
			if ( '' !== $excerpt ) {
				$html .= '<p>' . esc_html( $excerpt ) . '</p>';
			}
			$html .= '<p><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( $labels['cta'] ) . '</a></p></div>';
		}

		$html .= '</div></section>';

		return $html;
	}

	/**
	 * Build about page support markup.
	 *
	 * @return string
	 */
	protected static function build_about_sections_markup() {
		if ( 'zh' === self::get_current_site_language() ) {
			return '<section class="yke-page-section"><h2>工厂与出口能力</h2><div class="yke-page-grid"><div class="yke-card"><h3>出口交付流程</h3><p>云科可配合技术匹配、生产排期、出厂检验、出口包装和装运资料准备，适合外贸项目交付节奏。</p></div><div class="yke-card"><h3>质量与可追溯</h3><p>围绕 IEC / GB 体系组织生产，保留出厂测试记录，并支持第三方验货与资料审计。</p></div><div class="yke-card"><h3>目标市场适配</h3><p>可针对高温、多尘、潮湿、高海拔和新能源并网等工况做结构与元件适配。</p></div></div></section>';
		}

		return '<section class="yke-page-section"><h2>Factory and Export Support</h2><div class="yke-page-grid"><div class="yke-card"><h3>Export Delivery Workflow</h3><p>YKE supports technical matching, production planning, outgoing inspection, export packing, and shipment documentation for overseas orders.</p></div><div class="yke-card"><h3>Quality and Traceability</h3><p>IEC / GB aligned production, archived outgoing test records, and third-party inspection support help buyers audit supplier reliability sooner.</p></div><div class="yke-card"><h3>Market Adaptation</h3><p>Designs can be adjusted for high heat, dust, humidity, altitude, and renewable integration requirements across target export markets.</p></div></div></section>';
	}

	/**
	 * Build contact page support markup.
	 *
	 * @return string
	 */
	protected static function build_contact_sections_markup() {
		if ( 'zh' === self::get_current_site_language() ) {
			return '<section class="yke-page-section"><h2>建议先提供的信息</h2><div class="yke-page-grid"><div class="yke-card"><h3>技术信息</h3><p>国家、项目电压等级、应用场景、执行标准和偏好配置，足够云科先做产品匹配。</p></div><div class="yke-card"><h3>商务信息</h3><p>建议一并提供预估数量、目标交期，以及是直采、EPC 配套还是代理合作场景。</p></div><div class="yke-card"><h3>询盘跟进</h3><p>所有询盘会同时保存在 WordPress 后台并尝试邮件通知，降低漏单风险。</p></div></div></section>';
		}

		return '<section class="yke-page-section"><h2>What to Send First</h2><div class="yke-page-grid"><div class="yke-card"><h3>Technical Inputs</h3><p>Country, voltage level, application, standards, and preferred configuration are enough for a first matching recommendation.</p></div><div class="yke-card"><h3>Commercial Inputs</h3><p>Share quantity expectation, target lead time, and whether the inquiry is for direct procurement, EPC supply, or distribution.</p></div><div class="yke-card"><h3>Response Handling</h3><p>All inquiries are saved in WordPress and routed by email so the team can still follow up even if live email delivery fails.</p></div></div></section>';
	}

	/**
	 * Build distributor page support markup.
	 *
	 * @return string
	 */
	protected static function build_distributor_sections_markup() {
		if ( 'zh' === self::get_current_site_language() ) {
			return '<section class="yke-page-section"><h2>代理支持结构</h2><div class="yke-page-grid"><div class="yke-card"><h3>区域保护</h3><p>以区域边界清晰、渠道冲突控制和长期代理发展为合作基础。</p></div><div class="yke-card"><h3>技术支持</h3><p>可配合报价、产品匹配、投标资料整理和代表项目案例说明。</p></div><div class="yke-card"><h3>营销资料</h3><p>可提供画册、案例摘要、双语演示材料和竞品对照资料供本地开发使用。</p></div></div></section>';
		}

		return '<section class="yke-page-section"><h2>Distributor Support Structure</h2><div class="yke-page-grid"><div class="yke-card"><h3>Regional Protection</h3><p>Partner recruitment is positioned around regional clarity, conflict avoidance, and long-term distributor development.</p></div><div class="yke-card"><h3>Technical Backup</h3><p>YKE can support quotations, product matching, tender preparation, and representative export case references.</p></div><div class="yke-card"><h3>Marketing Materials</h3><p>Partners can use brochures, case highlights, and bilingual presentation assets during local market development.</p></div></div></section>';
	}

	/**
	 * Get product-line structured data.
	 *
	 * @return array
	 */
	protected static function get_product_line_profiles() {
		return array(
			'mv-switchgear' => array(
				'en' => array(
					'overview'      => 'Medium-voltage switchgear packages for industrial substations, utility feeders, renewable plants, and export EPC projects from 12kV to 40.5kV.',
					'applications'  => array( 'Industrial parks and manufacturing plants', 'Utility substations and distribution rooms', 'Solar and storage balance-of-plant packages' ),
					'models'        => array( 'KYN28-12 metal-clad switchgear', 'HXGN ring main unit', 'Outdoor switchbox and RMU combinations' ),
					'customization' => array( 'Relay protection, metering, and busbar schemes', 'ABB / Schneider / Chint / Delixi component combinations', 'High-temperature, dust-proof, and altitude-specific design review' ),
					'standards'     => 'IEC aligned design with project-based documentation support, FAT records, and export packing verification.',
					'markets'       => array( 'Middle East high-temperature projects', 'Southeast Asia factory and industrial expansions', 'Africa grid distribution upgrades' ),
				),
				'zh' => array(
					'overview'      => '面向工业变电站、公用事业配电和新能源项目的高压开关柜方案，覆盖 12kV 至 40.5kV 常见出口需求。',
					'applications'  => array( '工业园区和制造业工厂', '电力变电站与配电房', '光伏和储能项目电气配套' ),
					'models'        => array( 'KYN28-12 铠装移开式开关柜', 'HXGN 环网柜', '户外开闭所及 RMU 组合方案' ),
					'customization' => array( '继保、计量和母线系统组合', 'ABB / Schneider / 正泰 / 德力西等元件搭配', '高温、防尘和高海拔项目适配评审' ),
					'standards'     => '按 IEC 体系设计，并支持项目文件、出厂测试记录和出口包装校核。',
					'markets'       => array( '中东高温多尘项目', '东南亚工厂扩建项目', '非洲电网配电升级项目' ),
				),
			),
			'lv-switchgear' => array(
				'en' => array(
					'overview'      => 'Low-voltage switchgear for 400V class distribution, motor control, power factor correction, and building services projects.',
					'applications'  => array( 'Factories and production lines', 'Commercial compounds and buildings', 'Prefabricated substations and packaged systems' ),
					'models'        => array( 'GGD fixed low-voltage switchgear', 'MNS withdrawable switchgear', 'Power factor correction and MCC combinations' ),
					'customization' => array( 'Breaker brands and electrical component selection', 'Drawer / fixed cabinet combinations', 'Labeling, wiring diagrams, and shipment mark adaptation' ),
					'standards'     => 'IEC and GB aligned manufacturing with outgoing testing, packing list coordination, and project mark customization.',
					'markets'       => array( 'Vietnam and Southeast Asia factory upgrades', 'Africa industrial distribution demand', 'Export packaged substations for remote sites' ),
				),
				'zh' => array(
					'overview'      => '面向 400V 级配电、马达控制和无功补偿项目的低压开关柜，适用于工厂、商业和成套配电系统。',
					'applications'  => array( '制造业工厂与生产线', '商业综合体和楼宇配电', '预装式变电站和成套配电系统' ),
					'models'        => array( 'GGD 固定式低压开关柜', 'MNS 抽出式开关柜', '补偿柜与 MCC 组合方案' ),
					'customization' => array( '断路器品牌和元器件选型', '抽屉柜与固定柜组合', '标签、图纸和出货唛头适配' ),
					'standards'     => '按 IEC / GB 体系制造，支持出厂测试、装箱清单和项目化标识配置。',
					'markets'       => array( '东南亚工厂搬迁与改造', '非洲工业配电需求', '远程项目箱变配套' ),
				),
			),
			'transformers' => array(
				'en' => array(
					'overview'      => 'Dry-type and oil-immersed transformers for industrial distribution, utility replacement, renewable step-up, and export infrastructure demand.',
					'applications'  => array( 'Industrial plant distribution', 'Urban and utility feeder replacement', 'Solar, storage, and charging step-up power packages' ),
					'models'        => array( 'SCB11 / SCB13 dry-type transformers', 'S11 / S13 oil-immersed transformers', 'High-temperature and high-altitude custom ratings' ),
					'customization' => array( 'Vector group, impedance, and tap changer matching', 'Enclosure, temperature rise, and anti-corrosion treatment', 'Nameplate, test report, and inspection document customization' ),
					'standards'     => 'IEC aligned transformer manufacture with routine testing, export inspection support, and project data-sheet review.',
					'markets'       => array( 'Nigeria and African utility replacement demand', 'Middle East industrial power distribution', 'Renewable step-up applications in Southeast Asia' ),
				),
				'zh' => array(
					'overview'      => '覆盖干式与油浸式产品的变压器方案，适用于工业配电、电网替换和新能源升压项目。',
					'applications'  => array( '工业厂房配电', '城市配网与公用事业替换', '光伏、储能和充电项目升压配套' ),
					'models'        => array( 'SCB11 / SCB13 干式变压器', 'S11 / S13 油浸式变压器', '高温和高海拔定制规格' ),
					'customization' => array( '接线组别、阻抗和分接开关匹配', '外壳、防腐和温升配置', '铭牌、测试报告和验货文件定制' ),
					'standards'     => '按 IEC 体系制造，支持例行试验、出口验货和项目数据表评审。',
					'markets'       => array( '尼日利亚及非洲公用事业替换需求', '中东工业配电项目', '东南亚新能源升压场景' ),
				),
			),
			'prefabricated-substations' => array(
				'en' => array(
					'overview'      => 'Compact packaged substations combining MV, transformer, and LV sections for export projects that need fast on-site deployment.',
					'applications'  => array( 'Industrial parks and remote infrastructure', 'Renewable plant balance-of-plant power packages', 'Temporary and containerized project sites' ),
					'models'        => array( 'American-style prefabricated substations', 'European-style box substations', 'Project-custom integrated housing schemes' ),
					'customization' => array( 'Integrated component brands and layout structure', 'Container size, ventilation, and anti-corrosion options', 'Shipping split design for difficult logistics routes' ),
					'standards'     => 'IEC aligned packaged design with structural review, integrated FAT coordination, and export transport planning.',
					'markets'       => array( 'Indonesia renewable projects', 'Middle East infrastructure packages', 'Africa remote industrial deployments' ),
				),
				'zh' => array(
					'overview'      => '集成中压、变压器和低压部分的预装式变电站，适用于需要快速现场部署的出口工程项目。',
					'applications'  => array( '工业园区和偏远基础设施项目', '新能源项目电气配套', '临建和集装箱化工程现场' ),
					'models'        => array( '美式预装式变电站', '欧式箱变', '项目定制一体化箱体方案' ),
					'customization' => array( '内部元件品牌与布局结构集成', '箱体尺寸、通风和防腐方案', '针对复杂物流路线的分体发运设计' ),
					'standards'     => '按 IEC 体系成套设计，支持结构评审、整站 FAT 配合和出口运输规划。',
					'markets'       => array( '印尼新能源项目', '中东基础设施成套供货', '非洲偏远工业项目部署' ),
				),
			),
			'new-energy-electrical' => array(
				'en' => array(
					'overview'      => 'Electrical packages for solar, storage, charging, and other new-energy projects where grid-connection and auxiliary distribution must be delivered together.',
					'applications'  => array( 'Rooftop and ground-mount solar', 'Battery storage and prefabricated cabin distribution', 'Charging station and integrated energy projects' ),
					'models'        => array( 'Solar grid-connection cabinets', 'BMS distribution cabinets', 'Charging support substations and prefabricated cabins' ),
					'customization' => array( 'Grid-connection interface and utility document matching', 'Cabin, busbar, and auxiliary power integration', 'Project-based one-line diagrams and approval packs' ),
					'standards'     => 'IEC aligned electrical integration with support for utility review documents, project one-lines, and site coordination.',
					'markets'       => array( 'Indonesia rooftop solar projects', 'Latin America distributed energy projects', 'South Asia storage and charging packages' ),
				),
				'zh' => array(
					'overview'      => '面向光伏、储能、充电等新能源项目的一体化电气方案，强调并网和辅助配电协同交付。',
					'applications'  => array( '屋顶与地面光伏项目', '储能和预制舱配电', '充电站与综合能源项目' ),
					'models'        => array( '光伏并网柜', 'BMS 配电柜', '充电站配套箱变与预制舱' ),
					'customization' => array( '并网接口与公用事业资料匹配', '预制舱、母线和站用电集成', '项目单线图和审批资料整理' ),
					'standards'     => '按 IEC 体系集成设计，并支持公用事业评审文件、项目单线图和现场协同资料。',
					'markets'       => array( '印尼屋顶光伏项目', '拉美分布式能源项目', '南亚储能与充电方案' ),
				),
			),
		);
	}

	/**
	 * Get model list for a given product line.
	 *
	 * Returns array of model definitions, each with:
	 *   slug / name / name_zh / desc / desc_zh / image (placeholder = line image)
	 *   overview (array en/zh) / specs (shared) / features (array of en/zh pairs)
	 *   applications (array of en/zh pairs).
	 *
	 * @param string $line_key Product line slug.
	 * @return array
	 */
	protected static function get_product_models_for_line( $line_key ) {
		$all = array(
			'mv-switchgear' => array(
				array(
					'slug'     => 'kyn28-12',
					'name'     => 'KYN28-12',
					'name_zh'  => 'KYN28-12 铠装移开式开关柜',
					'desc'     => '12kV metal-clad withdrawable switchgear for indoor distribution and substation applications.',
					'desc_zh'  => '12kV 铠装移开式金属封闭开关设备，适用于户内配电与变电站场景。',
					'image'    => '图片8.png',
					'overview' => array(
						'en' => 'KYN28-12 is a metal-clad withdrawable switchgear rated at 12kV, designed for indoor distribution control and protection in industrial substations, utility feeders, and renewable collection nodes. It supports flexible relay, metering, and busbar configurations and is widely exported to Middle East and Southeast Asia projects.',
						'zh' => 'KYN28-12 是 12kV 铠装移开式金属封闭开关设备，用于工业变电站、公用馈线和新能源集电节点的户内配电控制与保护。支持继保、计量和母线灵活配置，已广泛出口至中东和东南亚项目。',
					),
					'specs' => array(
						array( 'en' => 'Rated voltage', 'zh' => '额定电压', 'v' => '12kV' ),
						array( 'en' => 'Rated current', 'zh' => '额定电流', 'v' => '630A – 4000A' ),
						array( 'en' => 'Short-circuit current', 'zh' => '短路电流', 'v' => '25kA / 31.5kA' ),
						array( 'en' => 'Protection class', 'zh' => '防护等级', 'v' => 'IP4X (IP65 outdoor option)' ),
						array( 'en' => 'Standards', 'zh' => '执行标准', 'v' => 'GB 1984, GB 3906' ),
					),
					'features' => array(
						array( 'en' => 'Metal-clad withdrawable breaker truck', 'zh' => '铠装移开式断路器手车' ),
						array( 'en' => 'Five-position interlock scheme', 'zh' => '五位置联锁方案' ),
						array( 'en' => 'Flexible relay and metering compartments', 'zh' => '继保与计量单元灵活配置' ),
						array( 'en' => 'Custom busbar schemes', 'zh' => '母线方案可定制' ),
						array( 'en' => 'ABB / Siemens / SEL relay options', 'zh' => '可选 ABB / 西门子 / SEL 继电器' ),
						array( 'en' => 'High-temperature and dust-proof options', 'zh' => '高温与防尘选项' ),
					),
					'applications' => array(
						array( 'en' => 'Industrial park main distribution', 'zh' => '工业园区主配电' ),
						array( 'en' => 'Utility substation feeders', 'zh' => '公用变电站馈线' ),
						array( 'en' => 'Solar and wind collector cabinets', 'zh' => '光伏与风电集电柜' ),
						array( 'en' => 'Building main switchgear', 'zh' => '楼宇总开关柜' ),
						array( 'en' => 'Generator synchronization cabinets', 'zh' => '发电机并机柜' ),
					),
				),
			),
			'lv-switchgear' => array(
				array(
					'slug'     => 'ggd',
					'name'     => 'GGD Fixed LV Switchgear',
					'name_zh'  => 'GGD 固定式低压开关柜',
					'desc'     => 'Fixed-type low-voltage switchgear for distribution and motor control in industrial and commercial sites.',
					'desc_zh'  => '固定式低压开关柜，适用于工业和商业现场配电与马达控制。',
					'image'    => '图片10.png',
					'overview' => array(
						'en' => 'GGD is a fixed-type low-voltage switchgear widely used in factory main distribution, sub-distribution, and motor control applications. Its open-frame design supports flexible breaker brand selection (ABB, Schneider, Siemens, or Chinese equivalents) and is a proven export choice for Southeast Asia factory projects.',
						'zh' => 'GGD 是固定式低压开关柜，广泛用于工厂主配电、分配电和马达控制。开式框架设计支持灵活的断路器品牌选择（ABB、施耐德、西门子或国产品牌），是东南亚工厂项目的成熟出口选择。',
					),
					'specs' => array(
						array( 'en' => 'Rated voltage', 'zh' => '额定电压', 'v' => '380V / 400V / 415V / 660V' ),
						array( 'en' => 'Rated current', 'zh' => '额定电流', 'v' => '400A – 4000A' ),
						array( 'en' => 'Short-circuit rating', 'zh' => '短路耐受电流', 'v' => '30kA – 80kA' ),
						array( 'en' => 'Protection class', 'zh' => '防护等级', 'v' => 'IP30 – IP54' ),
						array( 'en' => 'Standards', 'zh' => '执行标准', 'v' => 'IEC 61439-1/2, GB 7251' ),
					),
					'features' => array(
						array( 'en' => 'Fixed-type open-frame design', 'zh' => '固定式开式框架设计' ),
						array( 'en' => 'Flexible breaker brand selection', 'zh' => '断路器品牌灵活选择' ),
						array( 'en' => 'IEC 61439 type-tested', 'zh' => 'IEC 61439 型式试验' ),
						array( 'en' => 'Compartmented busbar and feeder zones', 'zh' => '分隔母线与馈线区域' ),
						array( 'en' => 'Front and rear access options', 'zh' => '前后维护可选' ),
						array( 'en' => 'Proven export track record', 'zh' => '成熟出口业绩' ),
					),
					'applications' => array(
						array( 'en' => 'Factory main and sub-distribution', 'zh' => '工厂主配电与分配电' ),
						array( 'en' => 'Motor control centers', 'zh' => '马达控制中心' ),
						array( 'en' => 'Commercial building distribution', 'zh' => '商业楼宇配电' ),
						array( 'en' => 'Substation LV side', 'zh' => '变电站低压侧' ),
						array( 'en' => 'Packaged power systems', 'zh' => '成套配电系统' ),
					),
				),
				array(
					'slug'     => 'gck',
					'name'     => 'GCK Withdrawable LV Switchgear',
					'name_zh'  => 'GCK 抽出式低压开关柜',
					'desc'     => 'Withdrawable low-voltage switchgear with drawer-type units for industrial motor control.',
					'desc_zh'  => '抽出式低压开关柜，采用抽屉式单元，适用于工业马达控制。',
					'image'    => '图片11.png',
					'overview' => array(
						'en' => 'GCK is a withdrawable low-voltage switchgear featuring drawer-type feeder units that allow safe maintenance and replacement without de-energizing the entire cabinet. Suitable for continuous-process industrial plants requiring high uptime.',
						'zh' => 'GCK 是抽出式低压开关柜，采用抽屉式馈线单元，可在不断电整个柜子的情况下安全维护和更换。适用于要求高正常运行时间的连续工艺工业厂房。',
					),
					'specs' => array(
						array( 'en' => 'Rated voltage', 'zh' => '额定电压', 'v' => '380V / 400V / 415V / 660V' ),
						array( 'en' => 'Rated current', 'zh' => '额定电流', 'v' => '400A – 4000A' ),
						array( 'en' => 'Short-circuit rating', 'zh' => '短路耐受电流', 'v' => '50kA – 80kA' ),
						array( 'en' => 'Protection class', 'zh' => '防护等级', 'v' => 'IP30 – IP54' ),
						array( 'en' => 'Standards', 'zh' => '执行标准', 'v' => 'IEC 61439-1/2, GB 7251' ),
					),
					'features' => array(
						array( 'en' => 'Drawer-type withdrawable units', 'zh' => '抽屉式抽出单元' ),
						array( 'en' => 'Hot-swap without full de-energization', 'zh' => '不断电热更换' ),
						array( 'en' => 'Compartmented safety design', 'zh' => '分隔安全设计' ),
						array( 'en' => '5-position drawer interlock', 'zh' => '五位置抽屉联锁' ),
						array( 'en' => 'IEC 61439 type-tested', 'zh' => 'IEC 61439 型式试验' ),
						array( 'en' => 'Suitable for continuous-process plants', 'zh' => '适用连续工艺厂房' ),
					),
					'applications' => array(
						array( 'en' => 'Continuous-process industrial plants', 'zh' => '连续工艺工业厂房' ),
						array( 'en' => 'Motor control centers', 'zh' => '马达控制中心' ),
						array( 'en' => 'Petrochemical facilities', 'zh' => '石化设施' ),
						array( 'en' => 'Steel and metallurgy plants', 'zh' => '钢铁冶金厂房' ),
						array( 'en' => 'Cement and mining process', 'zh' => '水泥与矿业工艺' ),
					),
				),
			),
			'transformers' => array(
				array(
					'slug'     => 'scb11',
					'name'     => 'SCB11 Dry-Type Transformer',
					'name_zh'  => 'SCB11 干式变压器',
					'desc'     => 'Cast-resin dry-type transformer for indoor fire-safety applications.',
					'desc_zh'  => '环氧树脂浇注干式变压器，适用于室内防火场景。',
					'image'    => '图片12.png',
					'overview' => array(
						'en' => 'SCB11 is an epoxy cast-resin dry-type transformer with F-class insulation, suitable for indoor installations requiring fire safety including commercial buildings, hospitals, data centers, and industrial plants. Oil-free design eliminates leakage risk and reduces maintenance.',
						'zh' => 'SCB11 是环氧树脂浇注干式变压器，F 级绝缘，适用于要求防火的室内安装，包括商业楼宇、医院、数据中心和工业厂房。无油设计消除泄漏风险并降低维护。',
					),
					'specs' => array(
						array( 'en' => 'Capacity', 'zh' => '额定容量', 'v' => '30kVA – 2500kVA' ),
						array( 'en' => 'Primary voltage', 'zh' => '一次电压', 'v' => '10kV / 20kV / 35kV' ),
						array( 'en' => 'Secondary voltage', 'zh' => '二次电压', 'v' => '400V / 690V' ),
						array( 'en' => 'Insulation class', 'zh' => '绝缘等级', 'v' => 'F' ),
						array( 'en' => 'Cooling', 'zh' => '冷却方式', 'v' => 'AN / AF' ),
					),
					'features' => array(
						array( 'en' => 'Epoxy cast-resin insulation', 'zh' => '环氧树脂浇注绝缘' ),
						array( 'en' => 'F-class insulation', 'zh' => 'F 级绝缘' ),
						array( 'en' => 'Oil-free fire-safe design', 'zh' => '无油防火设计' ),
						array( 'en' => 'Low loss core', 'zh' => '低损耗铁芯' ),
						array( 'en' => 'Indoor installation', 'zh' => '室内安装' ),
						array( 'en' => 'Reduced maintenance', 'zh' => '降低维护' ),
					),
					'applications' => array(
						array( 'en' => 'Commercial buildings', 'zh' => '商业楼宇' ),
						array( 'en' => 'Hospitals and data centers', 'zh' => '医院与数据中心' ),
						array( 'en' => 'Indoor substations', 'zh' => '室内变电站' ),
						array( 'en' => 'Industrial plants', 'zh' => '工业厂房' ),
						array( 'en' => 'Packaged substations', 'zh' => '预装式变电站' ),
					),
				),
				array(
					'slug'     => 's13',
					'name'     => 'S13 Oil-Immersed Transformer',
					'name_zh'  => 'S13 油浸式变压器',
					'desc'     => 'Higher-efficiency oil-immersed transformer with reduced core loss for energy-conscious projects.',
					'desc_zh'  => '高效率油浸式变压器，铁芯损耗更低，适用于节能项目。',
					'image'    => '图片17.png',
					'overview' => array(
						'en' => 'S13 is a higher-efficiency oil-immersed transformer with reduced no-load loss compared to S11, suitable for energy-conscious utility and industrial projects. Has been exported to Nigerian utility upgrade projects for S13 500-1600kVA ratings.',
						'zh' => 'S13 是高效率油浸式变压器，相比 S11 空载损耗更低，适用于节能公用事业和工业项目。已出口至尼日利亚公用事业升级项目，规格为 S13 500-1600kVA。',
					),
					'specs' => array(
						array( 'en' => 'Capacity', 'zh' => '额定容量', 'v' => '30kVA – 2500kVA' ),
						array( 'en' => 'Primary voltage', 'zh' => '一次电压', 'v' => '10kV / 20kV / 35kV' ),
						array( 'en' => 'Secondary voltage', 'zh' => '二次电压', 'v' => '400V / 690V' ),
						array( 'en' => 'Cooling', 'zh' => '冷却方式', 'v' => 'ONAN / ONAF' ),
					),
					'features' => array(
						array( 'en' => 'Higher-efficiency oil-immersed', 'zh' => '高效率油浸式' ),
						array( 'en' => 'Reduced no-load loss', 'zh' => '空载损耗更低' ),
						array( 'en' => 'Energy-saving core design', 'zh' => '节能铁芯设计' ),
						array( 'en' => 'Sealed tank construction', 'zh' => '密封水箱结构' ),
						array( 'en' => 'Pole and pad mounting options', 'zh' => '柱上与落地式可选' ),
						array( 'en' => 'Nigerian utility export track record', 'zh' => '尼日利亚公用事业出口业绩' ),
					),
					'applications' => array(
						array( 'en' => 'Energy-conscious utility projects', 'zh' => '节能公用事业项目' ),
						array( 'en' => 'Nigerian utility upgrades', 'zh' => '尼日利亚公用事业升级' ),
						array( 'en' => 'Industrial distribution', 'zh' => '工业配电' ),
						array( 'en' => 'Rural electrification', 'zh' => '农村电气化' ),
						array( 'en' => 'Commercial outdoor substations', 'zh' => '商业户外变电站' ),
					),
				),
			),
			'prefabricated-substations' => array(
				array(
					'slug'     => 'zgs1-12',
					'name'     => 'ZGS1-12/0.4 American-Style Substation',
					'name_zh'  => 'ZGS1-12/0.4 美式箱变',
					'desc'     => 'American-style compact packaged substation with integrated transformer and switchgear.',
					'desc_zh'  => '美式紧凑型预装式变电站，集成变压器与开关柜。',
					'image'    => '图片19.png',
					'overview' => array(
						'en' => 'ZGS1-12/0.4 is an American-style prefabricated substation integrating MV switchgear, transformer, and LV distribution in a single compact enclosure. Designed for fast on-site deployment in residential, commercial, and light industrial applications with limited space.',
						'zh' => 'ZGS1-12/0.4 是美式预装式变电站，将高压开关柜、变压器和低压配电集成在单个紧凑外壳内。适用于住宅、商业和轻工业有限空间现场的快速部署。',
					),
					'specs' => array(
						array( 'en' => 'Transformer capacity', 'zh' => '变压器容量', 'v' => '100kVA – 1600kVA' ),
						array( 'en' => 'MV voltage', 'zh' => '中压电压', 'v' => '12kV / 24kV' ),
						array( 'en' => 'LV voltage', 'zh' => '低压电压', 'v' => '400V / 415V' ),
						array( 'en' => 'Enclosure class', 'zh' => '外壳防护等级', 'v' => 'IP43 – IP54' ),
						array( 'en' => 'Material', 'zh' => '材质', 'v' => 'Steel' ),
					),
					'features' => array(
						array( 'en' => 'American-style compact design', 'zh' => '美式紧凑设计' ),
						array( 'en' => 'Integrated MV/transformer/LV sections', 'zh' => '集成中压/变压器/低压' ),
						array( 'en' => 'Fast on-site deployment', 'zh' => '现场快速部署' ),
						array( 'en' => 'Limited space friendly', 'zh' => '有限空间适用' ),
						array( 'en' => 'Steel enclosure', 'zh' => '钢制外壳' ),
						array( 'en' => 'Factory-assembled and tested', 'zh' => '工厂装配并测试' ),
					),
					'applications' => array(
						array( 'en' => 'Residential communities', 'zh' => '住宅小区' ),
						array( 'en' => 'Commercial compounds', 'zh' => '商业综合体' ),
						array( 'en' => 'Light industrial sites', 'zh' => '轻工业现场' ),
						array( 'en' => 'Temporary construction power', 'zh' => '临建施工用电' ),
						array( 'en' => 'Compact urban distribution', 'zh' => '紧凑城市配电' ),
					),
				),
				array(
					'slug'     => 'yb-12',
					'name'     => 'YB-12/0.4 European-Style Substation',
					'name_zh'  => 'YB-12/0.4 欧式箱变',
					'desc'     => 'European-style prefabricated substation with separate MV, transformer, and LV compartments.',
					'desc_zh'  => '欧式预装式变电站，中压、变压器和低压分室设计。',
					'image'    => '图片20.png',
					'overview' => array(
						'en' => 'YB-12/0.4 is a European-style prefabricated substation featuring separate compartments for MV switchgear, transformer, and LV distribution. The compartmented design supports safer maintenance access and is preferred for utility and industrial applications requiring clear section isolation.',
						'zh' => 'YB-12/0.4 是欧式预装式变电站，高压开关柜、变压器和低压配电分室设计。分室设计支持更安全的维护通道，适用于要求清晰区段隔离的公用和工业应用。',
					),
					'specs' => array(
						array( 'en' => 'Transformer capacity', 'zh' => '变压器容量', 'v' => '100kVA – 2500kVA' ),
						array( 'en' => 'MV voltage', 'zh' => '中压电压', 'v' => '12kV / 24kV / 40.5kV' ),
						array( 'en' => 'LV voltage', 'zh' => '低压电压', 'v' => '400V / 415V / 690V' ),
						array( 'en' => 'Enclosure class', 'zh' => '外壳防护等级', 'v' => 'IP43 – IP54' ),
						array( 'en' => 'Material', 'zh' => '材质', 'v' => 'Steel or GRC' ),
					),
					'features' => array(
						array( 'en' => 'European-style compartmented design', 'zh' => '欧式分室设计' ),
						array( 'en' => 'Separate MV/transformer/LV sections', 'zh' => '中压/变压器/低压分室' ),
						array( 'en' => 'Safer maintenance access', 'zh' => '更安全维护通道' ),
						array( 'en' => 'Higher capacity range', 'zh' => '更大容量范围' ),
						array( 'en' => 'Steel or GRC enclosure options', 'zh' => '钢或 GRC 外壳可选' ),
						array( 'en' => 'Utility and industrial standard', 'zh' => '公用与工业标准' ),
					),
					'applications' => array(
						array( 'en' => 'Utility distribution substations', 'zh' => '公用配电变电站' ),
						array( 'en' => 'Industrial plants', 'zh' => '工业厂房' ),
						array( 'en' => 'Infrastructure projects', 'zh' => '基础设施项目' ),
						array( 'en' => 'Renewable step-up applications', 'zh' => '新能源升压应用' ),
						array( 'en' => 'Commercial complexes', 'zh' => '商业综合体' ),
					),
				),
			),
			'new-energy-electrical' => array(
				array(
					'slug'     => 'pv-grid-cabinet',
					'name'     => 'Solar Grid-Connection Cabinet',
					'name_zh'  => '光伏并网柜',
					'desc'     => 'Inverter AC combiner and grid-connection cabinet with protection and metering.',
					'desc_zh'  => '逆变器交流汇流与并网柜，含保护与计量。',
					'image'    => 'products-substation-new-energy.png',
					'overview' => array(
						'en' => 'Solar grid-connection cabinet integrates inverter AC combiner, protection devices, and metering for grid interconnection of solar plants. Designed for utility approval workflows including SNI (Indonesia) and supports 400V/690V/35kV collection voltages. Delivered to Indonesian 50MW rooftop solar projects.',
						'zh' => '光伏并网柜集成逆变器交流汇流、保护装置和计量，用于光伏电站并网。设计支持公用事业审批流程，包括印尼 SNI，支持 400V/690V/35kV 集电电压。已交付至印尼 50MW 屋顶光伏项目。',
					),
					'specs' => array(
						array( 'en' => 'Voltage', 'zh' => '电压', 'v' => '400V / 690V / 35kV collection' ),
						array( 'en' => 'Inverter capacity', 'zh' => '逆变器容量', 'v' => 'Up to 500kW string / 3.125MW central' ),
						array( 'en' => 'Protection', 'zh' => '保护', 'v' => 'Overcurrent / earth-fault / anti-islanding' ),
						array( 'en' => 'Enclosure', 'zh' => '外壳', 'v' => 'IP54 – IP65 outdoor' ),
						array( 'en' => 'Standards', 'zh' => '执行标准', 'v' => 'IEC 61439, NB/T 33001' ),
					),
					'features' => array(
						array( 'en' => 'Inverter AC combiner integrated', 'zh' => '集成逆变器交流汇流' ),
						array( 'en' => 'Grid-connection protection', 'zh' => '并网保护' ),
						array( 'en' => 'Anti-islanding protection', 'zh' => '防孤岛保护' ),
						array( 'en' => 'Metering compartment', 'zh' => '计量单元' ),
						array( 'en' => 'Utility approval support (SNI / PLN)', 'zh' => '公用事业审批支持（SNI/PLN）' ),
						array( 'en' => 'IP54-IP65 outdoor enclosure', 'zh' => 'IP54-IP65 户外外壳' ),
					),
					'applications' => array(
						array( 'en' => 'Rooftop solar grid-connection', 'zh' => '屋顶光伏并网' ),
						array( 'en' => 'Ground-mount solar plants', 'zh' => '地面光伏电站' ),
						array( 'en' => 'Indonesian solar projects', 'zh' => '印尼光伏项目' ),
						array( 'en' => 'Distributed solar generation', 'zh' => '分布式光伏发电' ),
						array( 'en' => 'Microgrid interconnection', 'zh' => '微电网互联' ),
					),
				),
				array(
					'slug'     => 'bms-cabinet',
					'name'     => 'BMS Distribution Cabinet',
					'name_zh'  => 'BMS 配电柜',
					'desc'     => 'Battery management system distribution cabinet for energy storage containers.',
					'desc_zh'  => '电池管理系统配电柜，适用于储能集装箱。',
					'image'    => 'products-substation-new-energy.png',
					'overview' => array(
						'en' => 'BMS distribution cabinet provides DC and AC isolation, protection, and coordination between battery containers and the grid. Supports 1000VDC and 1500VDC storage systems with integrated BMS interface, fusing, and contactor control.',
						'zh' => 'BMS 配电柜提供电池集装箱与电网之间的直流与交流隔离、保护和协调。支持 1000VDC 和 1500VDC 储能系统，集成 BMS 接口、熔断和接触器控制。',
					),
					'specs' => array(
						array( 'en' => 'DC voltage', 'zh' => '直流电压', 'v' => '1000VDC / 1500VDC' ),
						array( 'en' => 'AC interface', 'zh' => '交流接口', 'v' => '400V / 690V' ),
						array( 'en' => 'Protection', 'zh' => '保护', 'v' => 'DC fusing / contactor / BMS coordination' ),
						array( 'en' => 'Enclosure', 'zh' => '外壳', 'v' => 'IP54 – IP65' ),
						array( 'en' => 'Standards', 'zh' => '执行标准', 'v' => 'IEC 61439, GB 7251' ),
					),
					'features' => array(
						array( 'en' => 'DC/AC isolation', 'zh' => '直流/交流隔离' ),
						array( 'en' => '1000V/1500VDC support', 'zh' => '1000V/1500VDC 支持' ),
						array( 'en' => 'Integrated BMS interface', 'zh' => '集成 BMS 接口' ),
						array( 'en' => 'DC fusing and contactor control', 'zh' => '直流熔断与接触器控制' ),
						array( 'en' => 'Containerized installation', 'zh' => '集装箱安装' ),
						array( 'en' => 'IP54-IP65 outdoor', 'zh' => 'IP54-IP65 户外' ),
					),
					'applications' => array(
						array( 'en' => 'Battery storage containers', 'zh' => '电池储能集装箱' ),
						array( 'en' => 'Grid-scale storage projects', 'zh' => '电网级储能项目' ),
						array( 'en' => 'Microgrid storage systems', 'zh' => '微电网储能系统' ),
						array( 'en' => 'Industrial peak-shaving', 'zh' => '工业削峰填谷' ),
						array( 'en' => 'Renewable firming applications', 'zh' => '新能源稳定应用' ),
					),
				),
				array(
					'slug'     => 'charging-booster',
					'name'     => 'Charging Station Booster Substation',
					'name_zh'  => '充电桩升压箱变',
					'desc'     => 'Booster substation for EV charging stations requiring MV step-up and LV distribution.',
					'desc_zh'  => '电动车充电站升压箱变，适用于中压升压与低压配电。',
					'image'    => 'products-substation-new-energy.png',
					'overview' => array(
						'en' => 'Charging station booster substation provides MV-to-LV conversion for EV charging infrastructure requiring high-power DC fast charging. Integrates transformer, MV switchgear, and LV distribution in a compact pad-mounted enclosure suitable for highway and urban charging sites.',
						'zh' => '充电桩升压箱变为需要大功率直流快充的电动车充电基础设施提供中压转低压转换。在紧凑落地式外壳内集成变压器、高压开关柜和低压配电，适用于高速公路和城市充电现场。',
					),
					'specs' => array(
						array( 'en' => 'Transformer capacity', 'zh' => '变压器容量', 'v' => '500kVA – 2500kVA' ),
						array( 'en' => 'MV voltage', 'zh' => '中压电压', 'v' => '12kV / 24kV / 35kV' ),
						array( 'en' => 'LV voltage', 'zh' => '低压电压', 'v' => '400V' ),
						array( 'en' => 'Charging power', 'zh' => '充电功率', 'v' => 'AC 7kW – DC 360kW' ),
						array( 'en' => 'Enclosure', 'zh' => '外壳', 'v' => 'IP54 – IP65 outdoor' ),
					),
					'features' => array(
						array( 'en' => 'High-power DC fast charging support', 'zh' => '大功率直流快充支持' ),
						array( 'en' => 'Integrated MV/transformer/LV', 'zh' => '集成中压/变压器/低压' ),
						array( 'en' => 'Compact pad-mounted design', 'zh' => '紧凑落地式设计' ),
						array( 'en' => 'Highway and urban site friendly', 'zh' => '高速和城市现场适用' ),
						array( 'en' => 'IP54-IP65 outdoor enclosure', 'zh' => 'IP54-IP65 户外外壳' ),
						array( 'en' => 'Scalable for charging hubs', 'zh' => '可扩展充电枢纽' ),
					),
					'applications' => array(
						array( 'en' => 'Highway EV charging stations', 'zh' => '高速公路充电站' ),
						array( 'en' => 'Urban charging hubs', 'zh' => '城市充电枢纽' ),
						array( 'en' => 'Fleet charging depots', 'zh' => '车队充电场' ),
						array( 'en' => 'Commercial charging sites', 'zh' => '商业充电现场' ),
						array( 'en' => 'High-power DC fast charging', 'zh' => '大功率直流快充' ),
					),
				),
				array(
					'slug'     => 'prefab-cabin',
					'name'     => 'Prefabricated Equipment Cabin',
					'name_zh'  => '预制舱',
					'desc'     => 'Equipment cabin with climate control for fast site installation of electrical systems.',
					'desc_zh'  => '带气候控制的设备舱，便于电气系统现场快速安装。',
					'image'    => 'products-substation-new-energy.png',
					'overview' => array(
						'en' => 'Prefabricated equipment cabin provides a climate-controlled enclosure for fast deployment of switchgear, storage, and auxiliary systems. Designed for 20ft/40ft container shipping with integrated ventilation, air-conditioning, and fire suppression.',
						'zh' => '预制舱提供气候控制外壳，用于快速部署开关柜、储能和辅助系统。按 20ft/40ft 集装箱运输设计，集成通风、空调和消防。',
					),
					'specs' => array(
						array( 'en' => 'Container size', 'zh' => '集装箱尺寸', 'v' => '20ft / 40ft options' ),
						array( 'en' => 'Climate control', 'zh' => '气候控制', 'v' => 'Ventilation / air-conditioning / heating' ),
						array( 'en' => 'Fire suppression', 'zh' => '消防系统', 'v' => 'Integrated' ),
						array( 'en' => 'Enclosure', 'zh' => '外壳', 'v' => 'IP54 – IP65' ),
						array( 'en' => 'Material', 'zh' => '材质', 'v' => 'Steel container' ),
					),
					'features' => array(
						array( 'en' => '20ft/40ft container shipping', 'zh' => '20ft/40ft 集装箱运输' ),
						array( 'en' => 'Integrated climate control', 'zh' => '集成气候控制' ),
						array( 'en' => 'Fire suppression system', 'zh' => '消防系统' ),
						array( 'en' => 'Fast site deployment', 'zh' => '现场快速部署' ),
						array( 'en' => 'Steel container construction', 'zh' => '钢制集装箱结构' ),
						array( 'en' => 'Custom layout per equipment', 'zh' => '按设备定制布局' ),
					),
					'applications' => array(
						array( 'en' => 'Storage container systems', 'zh' => '储能集装箱系统' ),
						array( 'en' => 'Mobile substations', 'zh' => '移动变电站' ),
						array( 'en' => 'Remote site equipment housing', 'zh' => '偏远现场设备舱' ),
						array( 'en' => 'Temporary project installations', 'zh' => '临建项目安装' ),
						array( 'en' => 'Auxiliary system enclosures', 'zh' => '辅助系统外壳' ),
					),
				),
			),
		);

		return isset( $all[ $line_key ] ) ? $all[ $line_key ] : array();
	}

	/**
	 * Build product-detail support markup.
	 *
	 * @param int $post_id Product page ID.
	 * @return string
	 */
	protected static function build_product_detail_sections_markup( $post_id ) {
		$slugs = wp_get_post_terms( $post_id, 'product_line', array( 'fields' => 'slugs' ) );
		$slug  = ! empty( $slugs[0] ) ? $slugs[0] : '';
		if ( '' === $slug ) {
			return '';
		}

		$profiles = self::get_product_line_profiles();
		$lang     = self::get_current_site_language();
		if ( empty( $profiles[ $slug ][ $lang ] ) ) {
			$lang = 'en';
		}

		if ( empty( $profiles[ $slug ][ $lang ] ) ) {
			return '';
		}

		$profile = $profiles[ $slug ][ $lang ];
		$labels  = array(
			'overview'      => self::t( 'product_detail.label.overview', $lang ),
			'applications'  => self::t( 'product_detail.label.applications', $lang ),
			'models'        => self::t( 'product_detail.label.models', $lang ),
			'customization' => self::t( 'product_detail.label.customization', $lang ),
			'standards'     => self::t( 'product_detail.label.standards', $lang ),
			'markets'       => self::t( 'product_detail.label.markets', $lang ),
			'cases'         => self::t( 'product_detail.label.cases', $lang ),
			'fallback'      => self::t( 'product_detail.label.case_fallback', $lang ),
		);

		$related_cases = get_posts(
			array(
				'post_type'      => 'case_study',
				'post_status'    => 'publish',
				'posts_per_page' => 3,
				'tax_query'      => array(
					array(
						'taxonomy' => 'product_line',
						'field'    => 'slug',
						'terms'    => array( $slug ),
					),
				),
				'lang'           => function_exists( 'pll_current_language' ) ? $lang : '',
			)
		);

		$case_items = '';
		foreach ( $related_cases as $case ) {
			$case_items .= sprintf(
				'<li><a href="%1$s">%2$s</a></li>',
				esc_url( get_permalink( $case ) ),
				esc_html( get_the_title( $case ) )
			);
		}

		if ( '' === $case_items ) {
			$case_items = '<li>' . esc_html( $labels['fallback'] ) . '</li>';
		}

		return '<section class="yke-page-section"><h2>' . esc_html( $labels['overview'] ) . '</h2><div class="yke-page-grid"><div class="yke-card"><p>' . esc_html( $profile['overview'] ) . '</p></div><div class="yke-card"><h3>' . esc_html( $labels['applications'] ) . '</h3>' . self::build_simple_line_list( $profile['applications'] ) . '</div><div class="yke-card"><h3>' . esc_html( $labels['models'] ) . '</h3>' . self::build_simple_line_list( $profile['models'] ) . '</div></div></section><section class="yke-page-section"><div class="yke-page-grid"><div class="yke-card"><h3>' . esc_html( $labels['customization'] ) . '</h3>' . self::build_simple_line_list( $profile['customization'] ) . '</div><div class="yke-card"><h3>' . esc_html( $labels['standards'] ) . '</h3><p>' . esc_html( $profile['standards'] ) . '</p></div><div class="yke-card"><h3>' . esc_html( $labels['markets'] ) . '</h3>' . self::build_simple_line_list( $profile['markets'] ) . '</div></div></section><section class="yke-page-section"><div class="yke-page-grid"><div class="yke-card"><h3>' . esc_html( $labels['cases'] ) . '</h3><ul class="yke-line-list">' . $case_items . '</ul></div></div></section>';
	}

	/**
	 * Get curated catalog card meta (voltage / desc / point) for the five product lines.
	 *
	 * Shared by the products overview page and the enhanced products page content.
	 * Copy is grounded in project documentation (PRODUCT.md / 独立站.docx).
	 *
	 * @param string $lang 'en' | 'zh'.
	 * @return array Keyed by product line slug; each value has voltage/desc/point.
	 */
	protected static function get_product_catalog_card_meta( $lang = 'en' ) {
		$is_zh = ( 'zh' === $lang );
		if ( $is_zh ) {
			return array(
				'mv-switchgear'             => array(
					'voltage' => '12kV – 40.5kV',
					'desc'    => '适用于变电站、工业园区和新能源项目的户内户外成套设备。',
					'point'   => 'IP65 户外 · ≥50°C 运行 · SASO 支持',
				),
				'lv-switchgear'             => array(
					'voltage' => '400V 及以下',
					'desc'    => '适用于工厂、商业建筑和成套配电的固定式、抽出式和补偿柜。',
					'point'   => 'IEC 61439 体系 · ABB / 施耐德 / 西门子元器件 · 目的国接线习惯',
				),
				'transformers'              => array(
					'voltage' => '10kV – 35kV',
					'desc'    => '适用于工业配电、公用馈线和新能源升压的干式与油浸式变压器。',
					'point'   => '30kVA – 10,000kVA · 常规交期 30–45 天',
				),
				'prefabricated-substations' => array(
					'voltage' => '12kV – 40.5kV',
					'desc'    => '集成中压、变压器和低压的紧凑型变电站，适合海外项目快速部署。',
					'point'   => '工厂预装调试 · 现场快速投运',
				),
				'new-energy-electrical'     => array(
					'voltage' => '光伏 / 储能 / 充电',
					'desc'    => '光伏并网、储能配电、充电配套箱变和预制舱。',
					'point'   => '完整新能源电气成套 · SNI / PLN 文件支持',
				),
			);
		}
		return array(
			'mv-switchgear'             => array(
				'voltage' => '12kV – 40.5kV',
				'desc'    => 'Indoor and outdoor assemblies for substations, industrial parks, and renewable sites.',
				'point'   => 'IP65+ outdoor · ≥50°C operation · SASO support',
			),
			'lv-switchgear'             => array(
				'voltage' => '400V and below',
				'desc'    => 'Fixed, withdrawable, and compensation cabinets for factories, commercial buildings, and packaged power.',
				'point'   => 'IEC 61439 aligned · ABB / Schneider / Siemens components · destination wiring habits',
			),
			'transformers'              => array(
				'voltage' => '10kV – 35kV',
				'desc'    => 'Dry-type and oil-immersed transformers for industrial distribution, utility feeders, and renewable step-up.',
				'point'   => '30kVA – 10,000kVA · standard lead time 30–45 days',
			),
			'prefabricated-substations' => array(
				'voltage' => '12kV – 40.5kV',
				'desc'    => 'Compact packaged substations integrating MV, transformer, and LV for rapid overseas deployment.',
				'point'   => 'Factory-assembled and tested · ready-to-energize on site',
			),
			'new-energy-electrical'     => array(
				'voltage' => 'Solar / Storage / EV',
				'desc'    => 'Solar grid-connection, storage distribution, charging support substations, and prefabricated cabins.',
				'point'   => 'Complete renewable electrical package · SNI / PLN document support',
			),
		);
	}

	/**
	 * Build products overview content (/products/).
	 *
	 * Renders five rich catalog cards (image + voltage + title + visible model list
	 * + description + key point + CTA) in a 2-3 column grid. Reuses the
	 * yke-product-catalog / yke-product-card CSS classes already in site.css.
	 *
	 * Models are pulled dynamically from get_product_models_for_line() so only the
	 * retained (docx-named) models are shown - never the deleted ones.
	 *
	 * @param string $lang 'en' | 'zh'.
	 * @return string
	 */
	protected static function build_products_overview_content( $lang = 'en' ) {
		self::enqueue_style();

		$is_zh = ( 'zh' === $lang );

		$labels = array(
			'section_eyebrow' => self::t( 'products.overview.eyebrow', $lang ),
			'section_title'   => self::t( 'products.overview.title', $lang ),
			'key_point'       => self::t( 'products.overview.key_point', $lang ),
			'view_details'    => self::t( 'btn.view_details', $lang ),
		);

		$product_order = array(
			'mv-switchgear',
			'lv-switchgear',
			'transformers',
			'prefabricated-substations',
			'new-energy-electrical',
		);
		$line_image = array(
			'mv-switchgear'             => '图片7.png',
			'lv-switchgear'             => '图片9.png',
			'transformers'              => '图片12.png',
			'prefabricated-substations' => '图片19.png',
			'new-energy-electrical'     => 'case-study-nigeria-indonesia.png',
		);

		$theme_uri   = get_template_directory_uri();
		$placeholder = $theme_uri . '/assets/images/placeholders/';
		$case_img    = $placeholder . 'case-study-nigeria-indonesia.png';

		$card_meta = self::get_product_catalog_card_meta( $lang );

		$html  = '<section class="yke-products-overview">';
		$html .= '<div class="yke-section-inner">';
		$html .= '<div class="yke-product-catalog">';
		$html .= '<header class="yke-product-catalog__title">';
		$html .= '<p class="yke-product-catalog__eyebrow">' . esc_html( $labels['section_eyebrow'] ) . '</p>';
		$html .= '<h2>' . esc_html( $labels['section_title'] ) . '</h2>';
		$html .= '</header>';

		foreach ( $product_order as $line_key ) {
			$line_url  = self::get_seeded_page_url( 'products/' . $line_key, $lang );
			$line_data = self::get_product_rich_data( $line_key );
			$title     = $is_zh
				? ( isset( $line_data['zh']['title'] ) ? $line_data['zh']['title'] : ( isset( $line_data['title'] ) ? $line_data['title'] : $line_key ) )
				: ( isset( $line_data['title'] ) ? $line_data['title'] : ( isset( $line_data['zh']['title'] ) ? $line_data['zh']['title'] : $line_key ) );

			$img_name = isset( $line_image[ $line_key ] ) ? $line_image[ $line_key ] : '图片7.png';
			$img_src  = ( 'new-energy-electrical' === $line_key ) ? $case_img : $placeholder . $img_name;

			$meta = isset( $card_meta[ $line_key ] ) ? $card_meta[ $line_key ] : array( 'voltage' => '', 'desc' => '', 'point' => '' );

			// Dynamic model list from retained models only.
			$models = self::get_product_models_for_line( $line_key );

			$html .= '<article class="yke-product-card">';
			$html .= '<a class="yke-product-card-img" href="' . esc_url( $line_url ) . '" aria-label="' . esc_attr( $title ) . '">';
			$html .= '<img src="' . esc_url( $img_src ) . '" alt="' . esc_attr( $title ) . '" loading="lazy"/>';
			$html .= '</a>';
			$html .= '<div class="yke-product-card-body">';
			$html .= '<span class="yke-product-card-voltage">' . esc_html( $meta['voltage'] ) . '</span>';
			$html .= '<h3>' . esc_html( $title ) . '</h3>';

			// Per-model clickable list (one row per model, each row -> model detail page).
			$html .= '<ul class="yke-product-card-models">';
			foreach ( $models as $m ) {
				$m_name = $is_zh
					? ( isset( $m['name_zh'] ) ? $m['name_zh'] : ( isset( $m['name'] ) ? $m['name'] : $m['slug'] ) )
					: ( isset( $m['name'] ) ? $m['name'] : ( isset( $m['name_zh'] ) ? $m['name_zh'] : $m['slug'] ) );
				$m_url  = self::get_seeded_page_url( 'products/' . $line_key . '/' . $m['slug'], $lang );
				$html  .= '<li><a href="' . esc_url( $m_url ) . '">' . esc_html( $m_name ) . '</a></li>';
			}
			$html .= '</ul>';

			$html .= '<p class="yke-product-card-desc">' . esc_html( $meta['desc'] ) . '</p>';
			$html .= '<p class="yke-product-card-point"><strong>' . esc_html( $labels['key_point'] ) . '</strong> ' . esc_html( $meta['point'] ) . '</p>';
			$html .= '<a class="yke-product-card-link" href="' . esc_url( $line_url ) . '">' . esc_html( $labels['view_details'] ) . '</a>';
			$html .= '</div>'; // body
			$html .= '</article>';
		}

		// 资质/能力总结卡(填补 3x2 网格第 6 格空白位,白底,4 个能力点 + CSS 画图标;无按钮、无询盘)
		$html .= self::render_strength_card_markup( $lang );

		$html .= '</div>'; // catalog
		$html .= '</div>'; // section-inner
		$html .= '</section>'; // overview

		return $html;
	}

	/**
	 * Render the reusable "Four Core Delivery Strengths" card.
	 *
	 * Used by:
	 *  - /products/ overview (in build_products_overview_content)
	 *  - /products/{line}/ product line pages (in build_rich_product_page_content)
	 *  - /products/{line}/{model}/ model detail pages (in build_model_detail_page_content)
	 *
	 * Renders the same yke-product-card--strength markup so all three page types
	 * share the visual language and the same content.
	 *
	 * @param string $lang 'en' | 'zh'.
	 * @return string HTML.
	 */
	protected static function render_strength_card_markup( $lang = 'en' ) {
		$is_zh = ( 'zh' === $lang );
		$eyebrow = self::t( 'strength.eyebrow', $lang );
		$title   = self::t( 'strength.title', $lang );
		if ( $is_zh ) {
			$strengths = array(
				array( 'icon' => 'medal',  'label' => 'IEC 体系 · 第三方验货',  'desc' => 'IEC 62271 / 61439 全系列合规,SGS / BV / Intertek 现场验货' ),
				array( 'icon' => 'globe',  'label' => '100+ 交付国家',           'desc' => '海外项目导向,熟悉 SASO / SNI / SONCAP 等目的国体系' ),
				array( 'icon' => 'shield', 'label' => '自有广州基地 · OEM/ODM', 'desc' => '钣金加工 + 总装 + 出厂试验一站式,可贴牌定制' ),
				array( 'icon' => 'gear',   'label' => '预装调试 + 18 月质保',   'desc' => '工厂预装并通电调试,出口装柜防护与全周期售后' ),
			);
		} else {
			$strengths = array(
				array( 'icon' => 'medal',  'label' => 'IEC-aligned, third-party inspected',  'desc' => 'IEC 62271 / 61439 compliant; SGS, BV, and Intertek on-site inspection' ),
				array( 'icon' => 'globe',  'label' => 'Delivered to 100+ countries',         'desc' => 'Export-oriented; familiar with SASO, SNI, SONCAP, and other destination requirements' ),
				array( 'icon' => 'shield', 'label' => 'In-house Guangzhou base · OEM / ODM', 'desc' => 'Sheet-metal, assembly, and factory acceptance test under one roof; private label supported' ),
				array( 'icon' => 'gear',   'label' => 'Pre-commissioned, 18-month warranty', 'desc' => 'Factory pre-assembly and live tests; export crating plus full lifecycle support' ),
			);
		}

		$html  = '<article class="yke-product-card yke-product-card--strength">';
		$html .= '<div class="yke-product-card-strength__body">';
		$html .= '<p class="yke-product-card-strength__eyebrow">' . esc_html( $eyebrow ) . '</p>';
		$html .= '<h3>' . esc_html( $title ) . '</h3>';
		$html .= '<ul class="yke-product-card-strength__list">';
		foreach ( $strengths as $s ) {
			$html .= '<li class="yke-product-card-strength__item">';
			$html .= '<span class="yke-product-card-strength__icon yke-product-card-strength__icon--' . esc_attr( $s['icon'] ) . '" aria-hidden="true">';
			$html .= self::render_strength_icon_svg( $s['icon'] );
			$html .= '</span>';
			$html .= '<div class="yke-product-card-strength__text">';
			$html .= '<p class="yke-product-card-strength__label">' . esc_html( $s['label'] ) . '</p>';
			$html .= '<p class="yke-product-card-strength__desc">' . esc_html( $s['desc'] ) . '</p>';
			$html .= '</div>';
			$html .= '</li>';
		}
		$html .= '</ul>';
		$html .= '</div>'; // strength body
		$html .= '</article>';
		return $html;
	}

	/**
	 * Render abstract inline SVG icon for the strength card.
	 *
	 * Used by build_products_overview_content() to render 4 capability icons
	 * (medal / globe / shield / gear) without any image dependency. Stroke-based
	 * so it inherits the current color (orange) via CSS.
	 *
	 * @param string $kind 'medal' | 'globe' | 'shield' | 'gear'.
	 * @return string SVG markup.
	 */
	protected static function render_strength_icon_svg( $kind = 'medal' ) {
		switch ( $kind ) {
			case 'globe':
				return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" role="img" aria-hidden="true">'
					. '<circle cx="12" cy="12" r="9"/>'
					. '<path d="M3 12h18"/>'
					. '<path d="M12 3a14 14 0 0 1 0 18"/>'
					. '<path d="M12 3a14 14 0 0 0 0 18"/>'
					. '</svg>';
			case 'shield':
				return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" role="img" aria-hidden="true">'
					. '<path d="M12 2 4 5v7c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V5l-8-3z"/>'
					. '<path d="m9 12 2 2 4-4"/>'
					. '</svg>';
			case 'gear':
				return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" role="img" aria-hidden="true">'
					. '<circle cx="12" cy="12" r="3"/>'
					. '<path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1A2 2 0 1 1 4.1 17l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.9l-.1-.1A2 2 0 1 1 7 4.1l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1A2 2 0 1 1 19.9 7l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>'
					. '</svg>';
			case 'medal':
			default:
				return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" role="img" aria-hidden="true">'
					. '<path d="M7 3h10l-2.5 5h-5L7 3z"/>'
					. '<circle cx="12" cy="14" r="6"/>'
					. '<path d="m9 14 2 2 4-4"/>'
					. '</svg>';
		}
	}

	/**
	 * Render five product-line cards with a hover popover listing all specific models.
	 *
	 * Shared component used by:
	 *  - /products/ overview page
	 *  - Home page (via shortcode in yke-global/patterns/home-products-grid.php)
	 *  - /case-studies/ archive page (via build_case_studies_listing_markup)
	 *  - /case-studies/{slug}/ detail page (via build_related_product_links)
	 *
	 * @param string $lang    'en' or 'zh'.
	 * @param string $variant 'overview' | 'home' | 'case-archive' | 'case-detail'.
	 * @return string
	 */
	protected static function render_product_line_cards_with_hover_popover( $lang = 'en', $variant = 'overview' ) {
		self::enqueue_style();

		$is_zh  = ( 'zh' === $lang );
		$product_order = array(
			'mv-switchgear',
			'lv-switchgear',
			'transformers',
			'prefabricated-substations',
			'new-energy-electrical',
		);
		$line_image = array(
			'mv-switchgear'             => '图片7.png',
			'lv-switchgear'             => '图片9.png',
			'transformers'              => '图片12.png',
			'prefabricated-substations' => '图片19.png',
			'new-energy-electrical'     => 'case-study-nigeria-indonesia.png',
		);
		$theme_uri   = get_template_directory_uri();
		$placeholder = $theme_uri . '/assets/images/placeholders/';
		$case_img    = $placeholder . 'case-study-nigeria-indonesia.png';

		$variant_class = sanitize_html_class( $variant );
		$popover_heading = self::t( 'label.models', $lang );
		$enter_label     = self::t( 'btn.browse_line', $lang );

		$html  = '<div class="yke-product-hover-grid yke-product-hover-grid--' . esc_attr( $variant_class ) . '">';
		foreach ( $product_order as $line_key ) {
			$line_url  = self::get_seeded_page_url( 'products/' . $line_key, $lang );
			$line_data = self::get_product_rich_data( $line_key );
			$title     = $is_zh
				? ( isset( $line_data['zh']['title'] ) ? $line_data['zh']['title'] : ( isset( $line_data['title'] ) ? $line_data['title'] : $line_key ) )
				: ( isset( $line_data['title'] ) ? $line_data['title'] : ( isset( $line_data['zh']['title'] ) ? $line_data['zh']['title'] : $line_key ) );
			$img_name  = isset( $line_image[ $line_key ] ) ? $line_image[ $line_key ] : '图片7.png';
			$img_src   = ( 'new-energy-electrical' === $line_key ) ? $case_img : $placeholder . $img_name;
			$models    = self::get_product_models_for_line( $line_key );

			$html .= '<article class="yke-product-hover-card" data-line="' . esc_attr( $line_key ) . '">';
			$html .= '<a class="yke-product-hover-card-link" href="' . esc_url( $line_url ) . '" aria-label="' . esc_attr( $title ) . '">';
			$html .= '<span class="yke-product-hover-card-img"><img src="' . esc_url( $img_src ) . '" alt="' . esc_attr( $title ) . '" loading="lazy"/></span>';
			$html .= '<span class="yke-product-hover-card-title">' . esc_html( $title ) . '</span>';
			$html .= '<span class="yke-product-hover-card-cta">' . esc_html( $enter_label ) . ' <span aria-hidden="true">→</span></span>';
			$html .= '</a>';

			// Hover popover: 列出该产品线下所有具体型号
			$html .= '<div class="yke-product-hover-popover" role="menu" aria-label="' . esc_attr( $title . ' ' . $popover_heading ) . '">';
			$html .= '<p class="yke-product-hover-popover-heading">' . esc_html( $popover_heading ) . '</p>';
			$html .= '<ul class="yke-product-hover-popover-list">';
			foreach ( $models as $m ) {
				$m_name = $is_zh
					? ( isset( $m['name_zh'] ) ? $m['name_zh'] : ( isset( $m['name'] ) ? $m['name'] : $m['slug'] ) )
					: ( isset( $m['name'] ) ? $m['name'] : ( isset( $m['name_zh'] ) ? $m['name_zh'] : $m['slug'] ) );
				$m_url  = $line_url . $m['slug'] . '/';
				$html .= '<li><a href="' . esc_url( $m_url ) . '" role="menuitem">' . esc_html( $m_name ) . '</a></li>';
			}
			$html .= '</ul></div>';

			$html .= '</article>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Shortcode wrapper for render_product_line_cards_with_hover_popover().
	 *
	 * Usage: [yke_product_hover_grid variant="home"] — uses current site language.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_product_hover_grid_shortcode( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'variant' => 'overview',
				'lang'    => '',
			),
			$atts,
			'yke_product_hover_grid'
		);
		$lang = ! empty( $atts['lang'] )
			? $atts['lang']
			: ( function_exists( 'pll_current_language' ) ? pll_current_language() : 'en' );
		if ( empty( $lang ) ) {
			$lang = 'en';
		}
		return self::render_product_line_cards_with_hover_popover( $lang, $atts['variant'] );
	}

	/**
	 * Build enhanced products page content with a 7-section structure.
	 * 1) Hero + product range overview
	 * 2) Quick product selector (by scenario / voltage / buyer)
	 * 3) Five product lines catalog (image + voltage + models + scenario + key point + entry)
	 * 4) Case-verified representative models (no "best seller" wording)
	 * 5) Export delivery & project support
	 * 6) Standards & certification support
	 * 7) Related projects + procurement FAQ + CTA
	 *
	 * @return string
	 */
	protected static function build_enhanced_products_page_content( $lang = 'en' ) {
		$theme_uri = get_template_directory_uri();
		$mv_img = $theme_uri . '/assets/images/placeholders/图片7.png';
		$lv_img = $theme_uri . '/assets/images/placeholders/图片9.png';
		$tf_img = $theme_uri . '/assets/images/placeholders/图片12.png';
		$ps_img = $theme_uri . '/assets/images/placeholders/图片19.png';
		$ne_img = $theme_uri . '/assets/images/placeholders/case-study-nigeria-indonesia.png';

		$is_zh = ( 'zh' === $lang );
		$prefix = $is_zh ? '/zh' : '';

		$labels = $is_zh ? array(
			'kicker'        => '产品',
			'hero_title'    => '面向海外项目的配电设备',
			'hero_body'     => '云科制造五大产品线，覆盖 0.4kV 至 35kV 应用——高压开关柜、低压开关柜、干式与油浸式变压器、预装式变电站和新能源电气成套。每条产品线均配套 IEC / GB 体系资料、灵活的元器件品牌选择和出口测试报告，适合经销商和 EPC 团队采购。',
			'hero_cta'      => '请求报价',
			'selector_title' => '按需求快速选产品',
			'mv_label'      => '12–40.5kV',
			'mv_title'      => '高压开关柜',
			'mv_desc'       => '变电站、工业园区、新能源项目',
			'lv_label'      => '≤400V',
			'lv_title'      => '低压开关柜',
			'lv_desc'       => '工厂、商业建筑、成套配电',
			'tf_label'      => '10–35kV',
			'tf_title'      => '变压器',
			'tf_desc'       => '配电、公用馈线、新能源升压',
			'ps_label'      => '12–40.5kV',
			'ps_title'      => '预装式变电站',
			'ps_desc'       => '工业园区、新能源、快速部署',
			'ne_label'      => '光伏 / 储能 / 充电',
			'ne_title'      => '新能源电气',
			'ne_desc'       => '并网、储能、充电成套',
			'catalog_title' => '五大产品线',
			'key_point'     => '关键卖点：',
			'view_details'  => '查看详情 &rarr;',
			'verified_title' => '案例已交付代表型号',
			'verified_body' => '以下型号因出现在云科真实交付记录中而列出，不使用"热销"等无依据表述，只展示有项目证据的参考型号。',
			'export_title'  => '出口交付与项目配套',
			'standards_title' => '标准与认证支持',
			'standards_body' => '合规资料视产品线和目的国而定。IEC 61439 作为标准符合性参考展示，不作为独立证书。',
			'related_title' => '相关项目证明',
			'related_body'  => '云科在中东、东南亚和非洲的交付记录——完整案例详情见案例页。',
			'faq_title'     => '采购常见问题',
			'cta_title'     => '请告诉我们您的国家、产品线、电压等级、数量和目标交期',
			'cta_body'      => '云科会回复匹配方案——不使用模板报价。',
			'cta_btn'       => '联系云科销售',
			'contact_url'   => $prefix . '/contact/',
			'cases_url'     => $prefix . '/case-studies/',
		) : array(
			'kicker'        => 'Products',
			'hero_title'    => 'Power Distribution Equipment for Overseas Projects',
			'hero_body'     => 'YKE manufactures five product lines covering 0.4kV to 35kV applications — medium-voltage switchgear, low-voltage switchgear, dry-type and oil-immersed transformers, prefabricated substations, and new-energy electrical packages. Each line ships with IEC and GB aligned documentation, flexible component brand options, and export-ready test reports for distributors and EPC teams.',
			'hero_cta'      => 'Request a Quote',
			'selector_title' => 'Find Your Product Line',
			'mv_label'      => '12–40.5kV',
			'mv_title'      => 'Medium-Voltage Switchgear',
			'mv_desc'       => 'Substations, industrial parks, renewable sites',
			'lv_label'      => '≤400V',
			'lv_title'      => 'Low-Voltage Switchgear',
			'lv_desc'       => 'Factories, commercial buildings, packaged power',
			'tf_label'      => '10–35kV',
			'tf_title'      => 'Transformers',
			'tf_desc'       => 'Distribution, utility feeders, renewable step-up',
			'ps_label'      => '12–40.5kV',
			'ps_title'      => 'Prefabricated Substations',
			'ps_desc'       => 'Industrial parks, renewables, rapid deployment',
			'ne_label'      => 'Solar / Storage / EV',
			'ne_title'      => 'New Energy Electrical',
			'ne_desc'       => 'Grid-connection, storage, charging packages',
			'catalog_title' => 'Five Product Lines',
			'key_point'     => 'Key point:',
			'view_details'  => 'View Details &rarr;',
			'verified_title' => 'Case-Verified Representative Models',
			'verified_body' => 'Models below are listed because they appear in actual YKE delivery records. No "best seller" claims — only references with project evidence.',
			'export_title'  => 'Export Delivery & Project Support',
			'standards_title' => 'Standards & Certification Support',
			'standards_body' => 'Compliance documentation available depending on product line and destination. IEC 61439 is shown as a standard-compliance reference, not as a standalone certificate.',
			'related_title' => 'Related Project References',
			'related_body'  => 'YKE delivery records across the Middle East, Southeast Asia, and Africa — full case details on the case studies page.',
			'faq_title'     => 'Procurement FAQ',
			'cta_title'     => 'Tell us your country, product line, voltage class, quantity, and target lead time',
			'cta_body'      => 'YKE responds with a matched proposal — no template quotation.',
			'cta_btn'       => 'Contact YKE Sales',
			'contact_url'   => '/contact/',
			'cases_url'     => '/case-studies/',
		);

		$card_data = $is_zh ? array(
			'mv'  => array( 'img' => $mv_img, 'alt' => '高压开关柜', 'voltage' => '12kV – 40.5kV', 'title' => '高压开关柜', 'models' => 'KYN28-12/24 · KYN61-40.5 · HCM · HCA · XGN', 'desc' => '适用于变电站、工业园区和新能源项目的户内户外成套设备。', 'point' => 'IP65 户外 · ≥50°C 运行 · SASO 支持' ),
			'lv'  => array( 'img' => $lv_img, 'alt' => '低压开关柜', 'voltage' => '400V 及以下', 'title' => '低压开关柜', 'models' => 'GGD · GCK · GCS · MNS · GGJ · MD-MAX', 'desc' => '适用于工厂、商业建筑和成套配电的固定式、抽出式和补偿柜。', 'point' => 'IEC 61439 体系 · ABB / 施耐德 / 西门子元器件 · 目的国接线习惯' ),
			'tf'  => array( 'img' => $tf_img, 'alt' => '变压器', 'voltage' => '10kV – 35kV', 'title' => '变压器', 'models' => 'SCB · S11 / S13 / S20 · D11 · 特殊环境型', 'desc' => '适用于工业配电、公用馈线和新能源升压的干式与油浸式变压器。', 'point' => '30kVA – 10,000kVA · 常规交期 30–45 天' ),
			'ps'  => array( 'img' => $ps_img, 'alt' => '预装式变电站', 'voltage' => '12kV – 40.5kV', 'title' => '预装式变电站', 'models' => '美式（ZGS1）· 欧式（YB）· 华式 · 新能源箱变', 'desc' => '集成中压、变压器和低压的紧凑型变电站，适合海外项目快速部署。', 'point' => '工厂预装调试 · 现场快速投运' ),
			'ne'  => array( 'img' => $ne_img, 'alt' => '新能源电气', 'voltage' => '光伏 / 储能 / 充电', 'title' => '新能源电气', 'models' => '光伏并网柜 · BMS 配电柜 · 充电桩升压箱变 · 预制舱 · 母线槽', 'desc' => '光伏并网、储能配电、充电配套箱变和预制舱。', 'point' => '完整新能源电气成套 · SNI / PLN 文件支持' ),
		) : array(
			'mv'  => array( 'img' => $mv_img, 'alt' => 'MV Switchgear', 'voltage' => '12kV – 40.5kV', 'title' => 'MV Switchgear', 'models' => 'KYN28-12/24 · KYN61-40.5 · HCM · HCA · XGN', 'desc' => 'Indoor and outdoor assemblies for substations, industrial parks, and renewable sites.', 'point' => 'IP65+ outdoor · ≥50°C operation · SASO support' ),
			'lv'  => array( 'img' => $lv_img, 'alt' => 'LV Switchgear', 'voltage' => '400V and below', 'title' => 'LV Switchgear', 'models' => 'GGD · GCK · GCS · MNS · GGJ · MD-MAX', 'desc' => 'Fixed, withdrawable, and compensation cabinets for factories, commercial buildings, and packaged power.', 'point' => 'IEC 61439 aligned · ABB / Schneider / Siemens components · destination wiring habits' ),
			'tf'  => array( 'img' => $tf_img, 'alt' => 'Transformers', 'voltage' => '10kV – 35kV', 'title' => 'Transformers', 'models' => 'SCB · S11 / S13 / S20 · D11 · custom environmental types', 'desc' => 'Dry-type and oil-immersed transformers for industrial distribution, utility feeders, and renewable step-up.', 'point' => '30kVA – 10,000kVA · standard lead time 30–45 days' ),
			'ps'  => array( 'img' => $ps_img, 'alt' => 'Prefabricated Substations', 'voltage' => '12kV – 40.5kV', 'title' => 'Prefabricated Substations', 'models' => 'American (ZGS1) · European (YB) · Chinese · new-energy box substations', 'desc' => 'Compact packaged substations integrating MV, transformer, and LV for rapid overseas deployment.', 'point' => 'Factory-assembled and tested · ready-to-energize on site' ),
			'ne'  => array( 'img' => $ne_img, 'alt' => 'New Energy Electrical', 'voltage' => 'Solar / Storage / EV', 'title' => 'New Energy Electrical', 'models' => 'PV grid-connection · BMS distribution · EV charging step-up · prefabricated cabins · busbar', 'desc' => 'Solar grid-connection, storage distribution, charging support substations, and prefabricated cabins.', 'point' => 'Complete renewable electrical package · SNI / PLN document support' ),
		);

		$verified = $is_zh ? array(
			array( 'KYN28-12', '沙特变电站项目 · 交付 32 面' ),
			array( 'GGD', '越南工厂搬迁 · 两批交付 60 台' ),
			array( 'S13', '尼日利亚公用事业升级 · 20 台油浸式变压器，500–1600kVA' ),
			array( 'SCB11', '沙特变电站项目 · 交付 8 台干式变压器' ),
			array( '升压箱变', '印尼 50MW 屋顶光伏 · 交付 12 台套（光伏并网柜 + 升压箱变）' ),
			array( '光伏并网柜', '印尼 50MW 屋顶光伏项目' ),
		) : array(
			array( 'KYN28-12', 'Saudi Arabia substation project · 32 panels delivered' ),
			array( 'GGD', 'Vietnam factory relocation · 60 cabinets delivered in two batches' ),
			array( 'S13', 'Nigeria utility upgrade · 20 oil-immersed transformers, 500–1600kVA' ),
			array( 'SCB11', 'Saudi Arabia substation project · 8 dry-type transformers delivered' ),
			array( 'Step-up box substation', 'Indonesia 50MW rooftop solar · 12 sets delivered (PV grid-connection cabinets + step-up box substations)' ),
			array( 'PV grid-connection cabinet', 'Indonesia 50MW rooftop solar project' ),
		);

		$features = $is_zh ? array(
			array( '元器件品牌选择', 'ABB、施耐德、西门子、SEL 或同等品牌，按买家 AVL 在生产前确认。' ),
			array( '英文资料', '单线图、接线图、测试报告、操作手册和证书以英文和中英文对照交付。' ),
			array( '第三方验货', '欢迎 SGS、BV、TÜV、Intertek 在生产前、生产中和出货前到厂验货。' ),
			array( '出口包装', '集装箱包装，标识和资料按目的港口要求对齐。' ),
			array( '交期', '常规配置：30–45 天。定制设计在报价阶段确认。' ),
			array( '售后边界', '自提单日起 18 个月标准质保。通过邮件、WhatsApp 和视频通话远程支持。' ),
		) : array(
			array( 'Component Brand Selection', 'ABB, Schneider, Siemens, SEL, or equivalent brands confirmed against buyer AVL before production.' ),
			array( 'English Documentation', 'Single-line diagrams, wiring diagrams, test reports, operation manuals, and certificates shipped in English and Chinese.' ),
			array( 'Third-Party Inspection', 'SGS, BV, TÜV, Intertek welcomed at pre-production, in-process, and pre-shipment stages.' ),
			array( 'Export Packing', 'Containerized packing with marking and documentation aligned to destination port requirements.' ),
			array( 'Lead Time', 'Standard configurations: 30–45 days. Custom designs confirmed at quotation.' ),
			array( 'After-Sales Boundary', '18-month standard warranty from bill of lading date. Remote support by email, WhatsApp, and video call.' ),
		);

		$teasers = $is_zh ? array(
			array( '沙特', '变电站项目', '32 面 KYN28-12 · 8 台 SCB11 干式变压器 · 38 天交付 · SGS 验货' ),
			array( '越南', '工厂搬迁', '两批交付 60 台 GGD' ),
			array( '尼日利亚', '公用事业升级', '20 台 S13 油浸式变压器 · 500–1600kVA · 零质保索赔' ),
			array( '印尼', '50MW 屋顶光伏', '12 台套光伏并网柜 + 升压箱变 · SNI / PLN 支持' ),
		) : array(
			array( 'Saudi Arabia', 'Substation project', '32 KYN28-12 panels · 8 SCB11 dry-type transformers · 38-day delivery · SGS inspection' ),
			array( 'Vietnam', 'Factory relocation', '60 GGD cabinets delivered in two batches' ),
			array( 'Nigeria', 'Utility upgrade', '20 S13 oil-immersed transformers · 500–1600kVA · zero warranty claims' ),
			array( 'Indonesia', '50MW rooftop solar', '12 sets PV grid-connection cabinets + step-up box substations · SNI / PLN support' ),
		);

		$faqs = $is_zh ? array(
			array( '典型交期是多久？', '常规配置：30–45 天。定制设计、特殊电压等级或非标元器件品牌在报价阶段确认。' ),
			array( '可以按我们指定的元器件品牌清单生产吗？', '可以。支持 ABB、施耐德、西门子、SEL 及同等品牌。提交 AVL 后我们在生产前确认供货、交期和价格影响。' ),
			array( '每单附带哪些资料？', '单线图、接线图、出厂测试报告、型式试验摘要、操作手册和证书——全部中英文对照。可按需提供第三方验货报告（SGS、BV、TÜV、Intertek）。' ),
			array( '支持目的国市场准入吗？', '视产品线和目的国而定，可准备 SASO（中东）和 SNI / PLN 审批支持（印尼）等市场准入支持文件。在询盘阶段确认范围。' ),
		) : array(
			array( 'What is the typical lead time?', 'Standard configurations: 30–45 days. Custom designs, special voltage classes, or non-standard component brands are confirmed at quotation.' ),
			array( 'Can you build to our approved component brand list?', 'Yes. We support ABB, Schneider, Siemens, SEL, and equivalent brands. Submit your AVL and we confirm availability, lead time, and price impact before production.' ),
			array( 'What documentation ships with each order?', 'Single-line diagrams, wiring diagrams, factory test reports, type-test summaries, operation manuals, and certificates — all in English and Chinese. Third-party inspection reports (SGS, BV, TÜV, Intertek) available on request.' ),
			array( 'Do you support destination-country market access?', 'Depending on product line and destination, we can prepare market-access support documents such as SASO (Middle East) and SNI / PLN approval support (Indonesia). Confirm scope at inquiry.' ),
		);

		$html = '';

		// 1) Hero + product range overview.
		$html .= '<div class="yke-page-intro">';
		$html .= '<p class="yke-kicker">' . esc_html( $labels['kicker'] ) . '</p>';
		$html .= '<h1>' . esc_html( $labels['hero_title'] ) . '</h1>';
		$html .= '<p>' . esc_html( $labels['hero_body'] ) . '</p>';
		$html .= '<p><a class="yke-btn-primary" href="' . esc_url( $labels['contact_url'] ) . '">' . esc_html( $labels['hero_cta'] ) . '</a></p>';
		$html .= '</div>';

		// 2) Quick product selector.
		$html .= '<div class="yke-product-selector">';
		$html .= '<h2>' . esc_html( $labels['selector_title'] ) . '</h2>';
		$html .= '<div class="yke-selector-grid">';
		$html .= '<a class="yke-selector-card" href="' . esc_url( $prefix . '/products/mv-switchgear/' ) . '"><span class="yke-selector-label">' . esc_html( $labels['mv_label'] ) . '</span><h4>' . esc_html( $labels['mv_title'] ) . '</h4><p>' . esc_html( $labels['mv_desc'] ) . '</p></a>';
		$html .= '<a class="yke-selector-card" href="' . esc_url( $prefix . '/products/lv-switchgear/' ) . '"><span class="yke-selector-label">' . esc_html( $labels['lv_label'] ) . '</span><h4>' . esc_html( $labels['lv_title'] ) . '</h4><p>' . esc_html( $labels['lv_desc'] ) . '</p></a>';
		$html .= '<a class="yke-selector-card" href="' . esc_url( $prefix . '/products/transformers/' ) . '"><span class="yke-selector-label">' . esc_html( $labels['tf_label'] ) . '</span><h4>' . esc_html( $labels['tf_title'] ) . '</h4><p>' . esc_html( $labels['tf_desc'] ) . '</p></a>';
		$html .= '<a class="yke-selector-card" href="' . esc_url( $prefix . '/products/prefabricated-substations/' ) . '"><span class="yke-selector-label">' . esc_html( $labels['ps_label'] ) . '</span><h4>' . esc_html( $labels['ps_title'] ) . '</h4><p>' . esc_html( $labels['ps_desc'] ) . '</p></a>';
		$html .= '<a class="yke-selector-card" href="' . esc_url( $prefix . '/products/new-energy-electrical/' ) . '"><span class="yke-selector-label">' . esc_html( $labels['ne_label'] ) . '</span><h4>' . esc_html( $labels['ne_title'] ) . '</h4><p>' . esc_html( $labels['ne_desc'] ) . '</p></a>';
		$html .= '</div>';
		$html .= '</div>';

		// 3) Five product lines catalog.
		$html .= '<div class="yke-product-catalog">';
		$html .= '<h2>' . esc_html( $labels['catalog_title'] ) . '</h2>';

		$card_keys = array( 'mv', 'lv', 'tf', 'ps', 'ne' );
		foreach ( $card_keys as $ck ) {
			$c = $card_data[ $ck ];
			$html .= '<a class="yke-product-card" href="' . esc_url( $prefix . '/products/' . ( 'mv' === $ck ? 'mv-switchgear' : ( 'lv' === $ck ? 'lv-switchgear' : ( 'tf' === $ck ? 'transformers' : ( 'ps' === $ck ? 'prefabricated-substations' : 'new-energy-electrical' ) ) ) ) . '/' ) . '">';
			$html .= '<div class="yke-product-card-img"><img src="' . esc_url( $c['img'] ) . '" alt="' . esc_attr( $c['alt'] ) . '"/></div>';
			$html .= '<div class="yke-product-card-body"><span class="yke-product-card-voltage">' . esc_html( $c['voltage'] ) . '</span><h3>' . esc_html( $c['title'] ) . '</h3>';
			$html .= '<p class="yke-product-card-models">' . esc_html( $c['models'] ) . '</p>';
			$html .= '<p>' . esc_html( $c['desc'] ) . '</p>';
			$html .= '<p class="yke-product-card-point"><strong>' . esc_html( $labels['key_point'] ) . '</strong> ' . esc_html( $c['point'] ) . '</p>';
			$html .= '<span class="yke-product-card-link">' . $labels['view_details'] . '</span></div></a>';
		}

		$html .= '</div>';

		// 4) Case-verified representative models.
		$html .= '<div class="yke-case-verified">';
		$html .= '<h2>' . esc_html( $labels['verified_title'] ) . '</h2>';
		$html .= '<p style="margin:0 0 1.5rem;color:var(--yke-slate);max-width:60ch">' . esc_html( $labels['verified_body'] ) . '</p>';
		$html .= '<div class="yke-verified-grid">';
		foreach ( $verified as $v ) {
			$html .= '<div class="yke-verified-card"><span class="yke-verified-model">' . esc_html( $v[0] ) . '</span><p class="yke-verified-case">' . esc_html( $v[1] ) . '</p></div>';
		}
		$html .= '</div>';
		$html .= '</div>';

		// 5) Export delivery & project support.
		$html .= '<div class="yke-export-support">';
		$html .= '<h2>' . esc_html( $labels['export_title'] ) . '</h2>';
		$html .= '<div class="yke-feature-grid">';
		foreach ( $features as $f ) {
			$html .= '<div class="yke-feature-card"><h3>' . esc_html( $f[0] ) . '</h3><p>' . esc_html( $f[1] ) . '</p></div>';
		}
		$html .= '</div>';
		$html .= '</div>';

		// 6) Standards & certification support.
		$html .= '<div class="yke-standards-strip">';
		$html .= '<h2>' . esc_html( $labels['standards_title'] ) . '</h2>';
		$html .= '<p style="margin:0 0 1rem;color:var(--yke-slate);max-width:60ch">' . esc_html( $labels['standards_body'] ) . '</p>';
		$html .= '<div class="yke-standards-tags">';
		$html .= '<span>CE</span>';
		$html .= '<span>CCC</span>';
		$html .= '<span>ISO 9001</span>';
		$html .= '<span>ISO 14001</span>';
		$html .= '<span>ISO 45001</span>';
		$html .= '<span>IEC 61439</span>';
		$html .= '<span>SASO</span>';
		$html .= '<span>SNI</span>';
		$html .= '<span>PLN</span>';
		$html .= '</div>';
		$html .= '</div>';

		// 7) Related projects + procurement FAQ + CTA.
		$html .= '<div class="yke-related-projects-faq">';
		$html .= '<h2>' . esc_html( $labels['related_title'] ) . '</h2>';
		$html .= '<p style="margin:0 0 1.5rem;color:var(--yke-slate);max-width:60ch">' . esc_html( $labels['related_body'] ) . '</p>';
		$html .= '<div class="yke-case-teaser-grid">';
		foreach ( $teasers as $t ) {
			$html .= '<a class="yke-case-teaser" href="' . esc_url( $labels['cases_url'] ) . '"><span class="yke-case-teaser-region">' . esc_html( $t[0] ) . '</span><h4>' . esc_html( $t[1] ) . '</h4><p>' . esc_html( $t[2] ) . '</p></a>';
		}
		$html .= '</div>';

		// Procurement FAQ.
		$html .= '<h2 style="margin-top:2.5rem">' . esc_html( $labels['faq_title'] ) . '</h2>';
		$html .= '<div class="yke-faq">';
		foreach ( $faqs as $faq ) {
			$html .= '<details class="yke-faq-item"><summary>' . esc_html( $faq[0] ) . '</summary><div class="yke-faq-answer">' . esc_html( $faq[1] ) . '</div></details>';
		}
		$html .= '</div>';

		// CTA.
		$html .= '<div class="yke-cta-band">';
		$html .= '<h3>' . esc_html( $labels['cta_title'] ) . '</h3>';
		$html .= '<p>' . esc_html( $labels['cta_body'] ) . '</p>';
		$html .= '<a class="yke-btn-primary" href="' . esc_url( $labels['contact_url'] ) . '">' . esc_html( $labels['cta_btn'] ) . '</a>';
		$html .= '</div>';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Rich data set for each product line detail page.
	 *
	 * @param string $key Product line slug.
	 * @return array
	 */
	protected static function get_product_rich_data( $key ) {
		$map = array(
			'mv-switchgear' => array(
				'title'        => 'MV Switchgear',
				'tagline'      => '12kV to 40.5kV indoor and outdoor assemblies',
				'summary'      => 'Medium-voltage switchgear, ring main units, and outdoor switchboxes engineered for industrial substations, utility distribution, and new energy sites, with flexible relay, busbar, and metering integration.',
				'image'        => '图片7.png',
				'features'     => array(
					array( 'Air and SF6 insulated options', 'Rated voltage up to 40.5kV, suitable for coastal, dusty, and high-altitude sites.' ),
					array( 'Flexible protection relay', 'Overcurrent, earth-fault, and differential relays from ABB, Siemens, SEL, or equivalent brands.' ),
					array( 'Mechanical and electrical interlocks', 'Five-position interlock scheme prevents misoperation during operation.' ),
					array( 'Custom busbar and metering', 'Main and tie busbar schemes, plus CT/PT metering compartments matched to project one-line diagrams.' ),
				),
				'specs'        => array(
					array( 'Rated voltage', '12kV / 24kV / 40.5kV' ),
					array( 'Rated current', '630A – 4000A' ),
					array( 'Short-circuit current', '25kA / 31.5kA' ),
					array( 'Protection class', 'IP4X – IP5X (IP65 outdoor)' ),
					array( 'Standards', 'GB 1984, GB 3906' ),
				),
				'applications' => array(
					array( 'Industrial parks', 'Main distribution and sectioning for manufacturing campuses.' ),
					array( 'Utility substations', 'Ring network and radial distribution nodes.' ),
					array( 'Renewable sites', 'Collector cabinets for solar and wind farms.' ),
				),
				'confirmations' => array(
					array( 'Environmental adaptation', 'High-temperature (≥50°C), dust-proof, and high-altitude sites — confirmed during technical review.' ),
					array( 'Component brand confirmation', 'ABB, Siemens, SEL, or equivalent brands confirmed against buyer AVL before production.' ),
					array( 'Standards and certification confirmation', 'Destination-country market access (e.g. SASO for Middle East) confirmed before order confirmation.' ),
				),
				'faqs'         => array(
					array( 'Can you supply ABB or Siemens relays?', 'Yes. We support ABB, Siemens, SEL, and equivalent brands subject to project approval and lead time.' ),
					array( 'What documentation ships with the cabinet?', 'Single-line diagram, wiring diagram, test report, operation manual, and certificates in English and Chinese.' ),
					array( 'What is the typical lead time?', 'Standard configurations: 30–45 days. Custom designs or non-standard component brands may extend the schedule — confirmed at quotation.' ),
				),
				'zh' => array(
					'title'        => '高压开关柜',
					'tagline'      => '12kV–40.5kV 户内及户外成套设备',
					'summary'      => '面向工业变电站、公用配电和新能源项目的高压开关柜、环网柜与户外开闭所方案，支持继保、母线和计量灵活集成。',
					'features'     => array(
						array( '空气与 SF6 绝缘可选', '额定电压最高至 40.5kV，适用于沿海、多尘及高海拔项目。' ),
						array( '保护继电器灵活配置', '可选 ABB、西门子、SEL 或同等品牌过流、零序及差动继电器。' ),
						array( '机械与电气联锁', '五位置联锁方案防止误操作。' ),
						array( '母线与计量定制', '主母线/联络母线方案，CT/PT 计量单元按项目单线图匹配。' ),
					),
					'specs'        => array(
						array( '额定电压', '12kV / 24kV / 40.5kV' ),
						array( '额定电流', '630A – 4000A' ),
						array( '短路电流', '25kA / 31.5kA' ),
						array( '防护等级', 'IP4X – IP5X（户外 IP65）' ),
						array( '标准', 'GB 1984, GB 3906' ),
					),
					'applications' => array(
						array( '工业园区', '制造园区主配电与分段。' ),
						array( '公用变电站', '环网与辐射式配电节点。' ),
						array( '新能源场站', '光伏与风电场集电柜。' ),
					),
					'confirmations' => array(
						array( '环境适配', '高温（≥50°C）、防尘、高海拔场景，技术评审时确认。' ),
						array( '元器件品牌确认', 'ABB、西门子、SEL 或同等品牌，按买家 AVL 在投产前确认。' ),
						array( '标准与认证确认', '目的国市场准入（如中东 SASO）在订单确认前确认。' ),
					),
					'faqs'         => array(
						array( '可以配 ABB 或西门子继电器吗？', '可以。支持 ABB、西门子、SEL 及同等品牌，依据项目审批和交期确认。' ),
						array( '随货交付哪些资料？', '单线图、接线图、出厂测试报告、操作手册及证书，中英文版本。' ),
						array( '常规交期多长？', '标准配置 30–45 天。定制设计或非标元件品牌可能延长，报价时确认。' ),
					),
				),
			),
			'lv-switchgear' => array(
				'title'        => 'LV Switchgear',
				'tagline'      => '400V and below fixed, withdrawable, and compensation cabinets',
				'summary'      => 'Low-voltage switchgear, motor control centers, power factor correction cabinets, and control cabinets with flexible breaker and component brands for factories, commercial compounds, and packaged power systems.',
				'image'        => '图片9.png',
				'features'     => array(
					array( 'IEC 61439 aligned', 'Type-tested assemblies with verified short-circuit withstand and temperature rise.' ),
					array( 'Flexible component brands', 'Breakers from ABB, Schneider, Siemens, or Chinese equivalents per buyer preference.' ),
					array( 'Withdrawable and fixed', 'GGD fixed, GCK/MNS withdrawable, and compartmented MCC layouts.' ),
					array( 'Compensation integration', 'Reactive power compensation and harmonic filtering cabinets available.' ),
				),
				'specs'        => array(
					array( 'Rated voltage', '380V / 400V / 415V / 660V' ),
					array( 'Rated current', '400A – 6300A' ),
					array( 'Short-circuit rating', '30kA – 80kA' ),
					array( 'Standards', 'IEC 61439-1/2, GB 7251' ),
					array( 'Protection class', 'IP30 – IP54' ),
					array( 'Compensation', 'Up to 600kVar per cabinet' ),
				),
				'applications' => array(
					array( 'Factory distribution', 'Main and sub-distribution for manufacturing lines.' ),
					array( 'Commercial buildings', 'MCC and distribution for commercial compounds.' ),
					array( 'Packaged power', 'LV side of substations and generator switchboards.' ),
				),
				'confirmations' => array(
					array( 'Environmental adaptation', 'High-temperature, dust, and humidity layouts available — confirmed during technical review.' ),
					array( 'Component brand confirmation', 'ABB, Schneider, Siemens, or equivalent brands confirmed against buyer AVL.' ),
					array( 'Wiring habit adaptation', 'Destination-country wiring habits and labeling conventions aligned before production.' ),
				),
				'faqs'         => array(
					array( 'Can you match our standard breaker brand?', 'Yes. We default to your approved brand list and confirm availability before production.' ),
					array( 'Is partial type-test documentation available?', 'Yes, type-test reports and routine test records ship with every cabinet.' ),
					array( 'What is the typical lead time?', 'Standard configurations: 30–45 days. Custom layouts may extend the schedule — confirmed at quotation.' ),
				),
				'zh' => array(
					'title'        => '低压开关柜',
					'tagline'      => '400V 及以下固定式、抽出式及补偿柜',
					'summary'      => '低压开关柜、马达控制中心、无功补偿柜与控制柜，断路器及元件品牌可灵活配置，适用于工厂、商业综合体及成套配电系统。',
					'features'     => array(
						array( 'IEC 61439 对齐', '已通过型式试验，短耐受电流与温升已验证。' ),
						array( '元件品牌灵活', '可选 ABB、施耐德、西门子或国产品牌，按买家偏好配置。' ),
						array( '固定式与抽出式', 'GGD 固定式、GCK/MNS 抽出式及 MCC 分隔方案。' ),
						array( '补偿集成', '可配套无功补偿与谐波滤波柜。' ),
					),
					'specs'        => array(
						array( '额定电压', '380V / 400V / 415V / 660V' ),
						array( '额定电流', '400A – 6300A' ),
						array( '短路耐受电流', '30kA – 80kA' ),
						array( '标准', 'IEC 61439-1/2, GB 7251' ),
						array( '防护等级', 'IP30 – IP54' ),
						array( '补偿容量', '单柜最高 600kVar' ),
					),
					'applications' => array(
						array( '工厂配电', '制造产线主配电与分配电。' ),
						array( '商业建筑', '商业综合体的 MCC 与配电。' ),
						array( '成套配电', '变电站低压侧与发电机配电柜。' ),
					),
					'confirmations' => array(
						array( '环境适配', '高温、防尘、潮湿方案，技术评审时确认。' ),
						array( '元器件品牌确认', 'ABB、施耐德、西门子或同等品牌，按买家 AVL 确认。' ),
						array( '接线习惯适配', '目的国接线习惯与标识规范在投产前对齐。' ),
					),
					'faqs'         => array(
						array( '可以按我们指定的断路器品牌做吗？', '可以。默认按您的 AVL 配置，投产前确认供货。' ),
						array( '型式试验资料可以提供吗？', '可以，每柜随货交付型式试验报告与例行试验记录。' ),
						array( '常规交期多长？', '标准配置 30–45 天。定制方案可能延长，报价时确认。' ),
					),
				),
			),
			'transformers' => array(
				'title'        => 'Transformers',
				'tagline'      => '30kVA to 10,000kVA dry-type and oil-immersed',
				'summary'      => 'Dry-type (SCB11/SCB13) and oil-immersed (S13/S11) transformers for industrial distribution, utility feeders, and renewable integration, including high-temperature and high-altitude custom versions.',
				'image'        => '图片12.png',
				'features'     => array(
					array( 'Dry and oil options', 'Cast-resin dry-type for indoor fire-safety and oil-immersed for outdoor economy.' ),
					array( 'High-temperature / altitude', 'Custom insulation classes for sites up to 4000m altitude and 50°C ambient.' ),
					array( 'Low loss design', 'S13 and SCB13 cores meet energy-efficiency requirements with reduced no-load loss.' ),
					array( 'Export documentation', 'Routine test reports, type test summaries, and inspection-ready packages.' ),
				),
				'specs'        => array(
					array( 'Capacity', '30kVA – 10,000kVA' ),
					array( 'Primary voltage', '10kV / 20kV / 35kV' ),
					array( 'Secondary voltage', '400V / 690V (custom)' ),
					array( 'Types', 'SCB11, SCB13 (dry); S13, S11 (oil)' ),
					array( 'Insulation class', 'F / H (dry); oil-immersed' ),
					array( 'Cooling', 'AN / AF (dry); ONAN / ONAF (oil)' ),
				),
				'applications' => array(
					array( 'Industrial distribution', 'Step-down for factories and process plants.' ),
					array( 'Utility feeders', 'Distribution and pole/pad-mounted transformers.' ),
					array( 'Renewable step-up', 'Generator step-up for solar and wind plants.' ),
				),
				'confirmations' => array(
					array( 'Environmental adaptation', 'High-altitude (up to 4000m) and high-temperature (≥50°C) designs confirmed during technical review.' ),
					array( 'Component brand confirmation', 'ABB, Schneider, or equivalent breaker and component brands confirmed against buyer AVL.' ),
					array( 'Standards and certification confirmation', 'Destination-country grid-connection requirements confirmed before order confirmation.' ),
				),
				'faqs'         => array(
					array( 'Can you build for high altitude?', 'Yes, we custom-design insulation and creepage for sites up to 4000m.' ),
					array( 'Do dry-type transformers need oil?', 'No. Cast-resin dry-type is oil-free and safer for indoor and fire-sensitive locations.' ),
					array( 'What tests are done before shipping?', 'Ratio, resistance, no-load/short-circuit loss, impulse, and partial discharge where applicable.' ),
				),
				'zh' => array(
					'title'        => '变压器',
					'tagline'      => '30kVA–10,000kVA 干式与油浸式',
					'summary'      => 'SCB11/SCB13 干式与 S13/S11 油浸式变压器，适用于工业配电、公用馈线及新能源升压项目，含高温与高海拔定制版本。',
					'features'     => array(
						array( '干式与油浸式可选', '环氧树脂干式适合室内防火场景，油浸式适合户外经济型方案。' ),
						array( '高温/高海拔定制', '可定制绝缘等级，适用 4000m 海拔与 50°C 环境温度。' ),
						array( '低损耗设计', 'S13、SCB13 铁芯满足能效要求，空载损耗更低。' ),
						array( '出口资料', '例行试验报告、型式试验摘要及验货资料包。' ),
					),
					'specs'        => array(
						array( '额定容量', '30kVA – 10,000kVA' ),
						array( '一次电压', '10kV / 20kV / 35kV' ),
						array( '二次电压', '400V / 690V（可定制）' ),
						array( '类型', 'SCB11、SCB13（干式）；S13、S11（油浸）' ),
						array( '绝缘等级', 'F / H（干式）；油浸式' ),
						array( '冷却方式', 'AN / AF（干式）；ONAN / ONAF（油浸）' ),
					),
					'applications' => array(
						array( '工业配电', '工厂与工艺装置降压配电。' ),
						array( '公用馈线', '配电及柱上/落地式变压器。' ),
						array( '新能源升压', '光伏与风电场发电机升压。' ),
					),
					'confirmations' => array(
						array( '环境适配', '高海拔（最高 4000m）与高温（≥50°C）方案，技术评审时确认。' ),
						array( '元器件品牌确认', 'ABB、施耐德或同等品牌断路器与元件，按买家 AVL 确认。' ),
						array( '标准与认证确认', '目的国并网要求在订单确认前确认。' ),
					),
					'faqs'         => array(
						array( '可以做高海拔定制吗？', '可以，最高可按 4000m 海拔定制绝缘与爬电距离。' ),
						array( '干式变压器需要油吗？', '不需要。环氧树脂干式无油，适合室内及防火敏感场所。' ),
						array( '出厂前做哪些试验？', '变比、电阻、空载/短路损耗、冲击及必要时局部放电试验。' ),
					),
				),
			),
			'prefabricated-substations' => array(
				'title'        => 'Prefabricated Substations',
				'tagline'      => 'American, European, and project-custom packaged substations',
				'summary'      => 'Compact packaged substations integrating MV switchgear, transformer, and LV distribution in one enclosure for rapid overseas deployment — from industrial parks to renewable balance-of-plant packages.',
				'image'        => '图片19.png',
				'features'     => array(
					array( 'Integrated sections', 'MV, transformer, and LV compartments factory-assembled and tested.' ),
					array( 'Multiple housings', 'American, European, and Chinese-style enclosures plus project-custom cabins.' ),
					array( 'Containerized shipping', 'Designed for standard container dimensions and constrained job sites.' ),
					array( 'Climate control', 'Ventilation, air-conditioning, and heating for desert, coastal, and cold climates.' ),
				),
				'specs'        => array(
					array( 'Transformer capacity', '100kVA – 2500kVA (typical)' ),
					array( 'MV voltage', '12kV / 24kV / 40.5kV' ),
					array( 'LV voltage', '400V / 415V / 690V' ),
					array( 'Enclosure class', 'IP43 – IP54' ),
					array( 'Housing types', 'American, European, Chinese, custom cabin' ),
					array( 'Material', 'Steel, GRC, or concrete' ),
				),
				'applications' => array(
					array( 'Industrial parks', 'Compact distribution nodes for rapid deployment.' ),
					array( 'Renewable plants', 'Inverter step-up and collector substations.' ),
					array( 'Remote sites', 'Mining, telecom, and off-grid installations.' ),
				),
				'confirmations' => array(
					array( 'Environmental adaptation', 'Desert, coastal, and cold-climate layouts confirmed during technical review.' ),
					array( 'Component brand confirmation', 'Internal MV/LV component brands confirmed against buyer AVL.' ),
					array( 'Wiring habit adaptation', 'Destination-country wiring habits and shipping split design aligned before production.' ),
				),
				'faqs'         => array(
					array( 'Can you ship in a standard container?', 'Yes, most units are designed for 20ft or 40ft container loading.' ),
					array( 'How fast can you deliver?', 'Standard configurations: 30–45 days. Custom designs may extend the schedule — confirmed at quotation.' ),
					array( 'What is included in factory testing?', 'Routine tests on each compartment plus integrated functional check before shipment.' ),
				),
				'zh' => array(
					'title'        => '预装式变电站',
					'tagline'      => '美式、欧式及项目定制箱变',
					'summary'      => '集成高压开关柜、变压器与低压配电的紧凑型箱变，适用于海外项目快速部署——从工业园区到新能源 BOP 配套。',
					'features'     => array(
						array( '一体化集成', '中压、变压器、低压三室在工厂装配并完成测试。' ),
						array( '多种箱体形式', '美式、欧式、华式及项目定制舱体。' ),
						array( '集装箱发运', '按标准集装箱尺寸设计，适合受限现场。' ),
						array( '气候控制', '通风、空调与加热配置，适用沙漠、沿海及寒冷气候。' ),
					),
					'specs'        => array(
						array( '变压器容量', '100kVA – 2500kVA（常规）' ),
						array( '中压电压', '12kV / 24kV / 40.5kV' ),
						array( '低压电压', '400V / 415V / 690V' ),
						array( '外壳防护等级', 'IP43 – IP54' ),
						array( '箱体类型', '美式、欧式、华式、定制舱' ),
						array( '材质', '钢板、GRC 或混凝土' ),
					),
					'applications' => array(
						array( '工业园区', '紧凑型配电节点，便于快速部署。' ),
						array( '新能源电站', '逆变器升压与集电变电站。' ),
						array( '偏远现场', '矿山、通信及离网项目。' ),
					),
					'confirmations' => array(
						array( '环境适配', '沙漠、沿海、寒冷气候方案，技术评审时确认。' ),
						array( '元器件品牌确认', '内部中低压元件品牌按买家 AVL 确认。' ),
						array( '接线习惯适配', '目的国接线习惯与分体发运方案在投产前对齐。' ),
					),
					'faqs'         => array(
						array( '可以装集装箱发运吗？', '可以，多数箱变按 20ft 或 40ft 集装箱装载设计。' ),
						array( '常规交期多长？', '标准配置 30–45 天。定制方案可能延长，报价时确认。' ),
						array( '出厂测试包含哪些？', '各室例行试验及整站功能联调，发货前完成。' ),
					),
				),
			),
			'new-energy-electrical' => array(
				'title'        => 'New Energy Electrical',
				'tagline'      => 'Solar, storage, and charging infrastructure packages',
				'summary'      => 'Solar grid-connection cabinets, BMS distribution cabinets, charging support substations, prefabricated cabins, and busbar systems designed to integrate with broader export project delivery.',
				'image'        => 'case-study-nigeria-indonesia.png',
				'features'     => array(
					array( 'Solar grid-connection', 'Inverter AC combiner and grid-connection cabinets with protection and metering.' ),
					array( 'Storage distribution', 'BMS and battery container distribution with DC and AC isolation.' ),
					array( 'Charging support', 'Charging station substations and distribution for EV infrastructure.' ),
					array( 'Prefabricated cabins', 'Equipment cabins with climate control for fast site installation.' ),
				),
				'specs'        => array(
					array( 'Solar voltage', '400V / 690V / 35kV collection' ),
					array( 'Inverter capacity', 'Up to 500kW string / 3.125MW central' ),
					array( 'Storage voltage', 'DC 1000V / 1500V' ),
					array( 'Charging', 'AC 7kW – DC 360kW' ),
					array( 'Standards', 'IEC 61439, GB 7251, NB/T 33001' ),
					array( 'Enclosure', 'IP54 – IP65 outdoor' ),
				),
				'applications' => array(
					array( 'Solar plants', 'Combiner, step-up, and grid-connection packages.' ),
					array( 'Energy storage', 'Container DC/AC distribution and BMS coordination.' ),
					array( 'EV charging', 'Charging site substations and distribution.' ),
				),
				'confirmations' => array(
					array( 'Environmental adaptation', 'Outdoor IP54–IP65 layouts for solar and storage sites confirmed during technical review.' ),
					array( 'Component brand confirmation', 'Inverter and BMS interface brands confirmed against buyer AVL.' ),
					array( 'Standards and certification confirmation', 'Destination-country grid-connection and approval documents (e.g. SNI / PLN for Indonesia) confirmed before order.' ),
				),
				'faqs'         => array(
					array( 'Can you supply the full solar collection package?', 'Yes, from combiner to step-up transformer and grid-connection cabinet.' ),
					array( 'Do you support 1500VDC systems?', 'Yes, 1500VDC storage and solar collection is supported.' ),
					array( 'Can you provide SNI / PLN approval support for Indonesia?', 'Yes, supporting documents for Indonesian grid-connection approval can be prepared — confirm scope at inquiry.' ),
				),
				'zh' => array(
					'title'        => '新能源电气',
					'tagline'      => '光伏、储能与充电基础设施方案',
					'summary'      => '光伏并网柜、BMS 配电柜、充电站升压箱变、预制舱及母线系统，可与出口项目整体配套交付。',
					'features'     => array(
						array( '光伏并网', '逆变器交流汇流与并网柜，含保护与计量。' ),
						array( '储能配电', 'BMS 与电池集装箱配电，含直流与交流隔离。' ),
						array( '充电配套', '充电站升压箱变与配电方案。' ),
						array( '预制舱', '带气候控制的设备舱，便于现场快速安装。' ),
					),
					'specs'        => array(
						array( '光伏电压', '400V / 690V / 35kV 汇集' ),
						array( '逆变器容量', '组串式最高 500kW / 集中式 3.125MW' ),
						array( '储能电压', 'DC 1000V / 1500V' ),
						array( '充电', 'AC 7kW – DC 360kW' ),
						array( '标准', 'IEC 61439, GB 7251, NB/T 33001' ),
						array( '外壳防护', '户外 IP54 – IP65' ),
					),
					'applications' => array(
						array( '光伏电站', '汇流、升压与并网成套方案。' ),
						array( '储能系统', '集装箱直流/交流配电与 BMS 协同。' ),
						array( '充电设施', '充电站升压箱变与配电。' ),
					),
					'confirmations' => array(
						array( '环境适配', '户外 IP54–IP65 方案，光伏与储能场景技术评审时确认。' ),
						array( '元器件品牌确认', '逆变器与 BMS 接口品牌按买家 AVL 确认。' ),
						array( '标准与认证确认', '目的国并网与审批资料（如印尼 SNI / PLN）在订单确认前确认。' ),
					),
					'faqs'         => array(
						array( '可以提供完整光伏集电方案吗？', '可以，从汇流到升压变压器与并网柜成套供货。' ),
						array( '支持 1500VDC 系统吗？', '支持，1500VDC 储能与光伏集电均可。' ),
						array( '可以提供印尼 SNI / PLN 审批支持吗？', '可以准备并网审批支持资料，询盘时确认范围。' ),
					),
				),
			),
		);

		return isset( $map[ $key ] ) ? $map[ $key ] : array();
	}

	/**
	 * Build a rich product detail page with a 10-section skeleton.
	 * Sections: Hero / Overview / Representative Models / Tech Specs /
	 * Typical Applications / Standards & Certifications / Project Confirmation /
	 * Delivery & Service / Related Cases / FAQ + Inquiry.
	 *
	 * @param string $key  Product line slug.
	 * @param string $lang Language slug ('en' or 'zh').
	 * @return string
	 */
	protected static function build_rich_product_page_content( $key, $lang = 'en' ) {
		$data = self::get_product_rich_data( $key );
		if ( empty( $data ) ) {
			return '';
		}

		$profiles = self::get_product_line_profiles();
		$profile  = isset( $profiles[ $key ][ $lang ] ) ? $profiles[ $key ][ $lang ] : $profiles[ $key ]['en'];

		$zh   = 'zh' === $lang && ! empty( $data['zh'] );
		$title    = $zh ? $data['zh']['title'] : $data['title'];
		$tagline  = $zh ? $data['zh']['tagline'] : $data['tagline'];
		$summary  = $zh ? $data['zh']['summary'] : $data['summary'];
		$features = $zh ? $data['zh']['features'] : $data['features'];
		$confirmations = $zh ? $data['zh']['confirmations'] : $data['confirmations'];
		$faqs     = $zh ? $data['zh']['faqs'] : $data['faqs'];
		$specs        = $zh && ! empty( $data['zh']['specs'] ) ? $data['zh']['specs'] : $data['specs'];
		$applications = $zh && ! empty( $data['zh']['applications'] ) ? $data['zh']['applications'] : $data['applications'];

		$labels = $zh ? array(
			'overview'    => '产品概述',
			'models'      => '代表型号',
			'specs'       => '技术规格',
			'apps'        => '典型应用',
			'standards'   => '标准与认证 / 市场准入支持',
			'confirm'     => '项目确认信息',
			'delivery'    => '交付文件与服务边界',
			'cases'       => '相关案例',
			'faq'         => '常见问题',
			'inquiry'     => '提交询盘',
			'inquiry_sub' => '告诉我们目的国、产品线、电压等级、数量与目标交期，云科将提供匹配方案。',
			'case_fallback' => '更多代表性交付案例可在询盘沟通中提供。',
			'standards_body' => '依据产品线与目的国市场，可配套 CE、CCC、ISO 三体系、IEC 61439 标准符合资料，以及 SASO（中东）、SNI / PLN（印尼）等市场准入支持文件。',
		) : array(
			'overview'    => 'Product Overview',
			'models'      => 'Representative Models',
			'specs'       => 'Technical Specifications',
			'apps'        => 'Typical Applications',
			'standards'   => 'Standards & Certifications / Market Access',
			'confirm'     => 'Project Confirmation',
			'delivery'    => 'Delivery Documents & Service Boundary',
			'cases'       => 'Related Case References',
			'faq'         => 'Frequently Asked Questions',
			'inquiry'     => 'Send Your Requirements',
			'inquiry_sub' => 'Share your target country, product line, voltage class, quantity, and expected lead time — YKE will respond with a matched proposal.',
			'case_fallback' => 'More representative delivery references can be shared during inquiry review.',
			'standards_body' => 'Depending on product line and destination, CE, CCC, ISO 9001/14001/45001, IEC 61439 compliance documentation, plus market-access support (SASO for Middle East, SNI / PLN for Indonesia) can be prepared.',
		);

		$theme_uri = get_template_directory_uri();
		$img       = $theme_uri . '/assets/images/placeholders/' . $data['image'];
		$home_url  = $zh ? home_url( '/zh/' ) : home_url( '/' );

		// 0) Breadcrumb.
		$html = '<!-- wp:html --><nav class="yke-breadcrumb" aria-label="breadcrumb"><a href="' . esc_url( $home_url ) . '">' . esc_html( $zh ? '首页' : 'Home' ) . '</a><span class="sep">»</span><span class="current">' . esc_html( $title ) . '</span></nav><!-- /wp:html -->';

		// 1) Hero image.
		$html .= '<!-- wp:html --><figure class="yke-visual-frame" style="margin-bottom:24px"><img src="' . esc_url( $img ) . '" alt="' . esc_attr( $title ) . '" style="border-radius:8px"/></figure><!-- /wp:html -->';

		// 2) Hero title block.
		$html .= '<!-- wp:group {"className":"yke-section-head","layout":{"type":"constrained"}} --><div class="wp-block-group yke-section-head">';
		$html .= '<!-- wp:paragraph {"className":"yke-kicker"} --><p class="yke-kicker">' . esc_html( $tagline ) . '</p><!-- /wp:paragraph -->';
		$html .= '<!-- wp:heading {"level":1,"fontSize":"xl"} --><h1 class="wp-block-heading has-xl-font-size">' . esc_html( $title ) . '</h1><!-- /wp:heading -->';
		$html .= '<!-- wp:paragraph --><p>' . esc_html( $summary ) . '</p><!-- /wp:paragraph -->';
		$html .= '</div><!-- /wp:group -->';

		// 3) Product overview.
		$html .= '<!-- wp:group {"className":"yke-page-section","layout":{"type":"constrained"}} --><div class="wp-block-group yke-page-section">';
		$html .= '<!-- wp:heading {"level":2,"fontSize":"lg"} --><h2 class="wp-block-heading has-lg-font-size">' . esc_html( $labels['overview'] ) . '</h2><!-- /wp:heading -->';
		$html .= '<!-- wp:html --><div class="yke-feature-grid">';
		foreach ( $features as $f ) {
			$html .= '<div class="yke-feature-item"><h4>' . esc_html( $f[0] ) . '</h4><p>' . esc_html( $f[1] ) . '</p></div>';
		}
		$html .= '</div><!-- /wp:html -->';
		$html .= '</div><!-- /wp:group -->';

		// 4) Model card grid (pineele-style, replaces text list).
		$line_models = self::get_product_models_for_line( $key );
		if ( ! empty( $line_models ) ) {
			$line_url = self::get_seeded_page_url( 'products/' . $key, $lang );
			$html .= '<!-- wp:group {"className":"yke-page-section","layout":{"type":"constrained"}} --><div class="wp-block-group yke-page-section">';
			$html .= '<!-- wp:heading {"level":2,"fontSize":"lg"} --><h2 class="wp-block-heading has-lg-font-size">' . esc_html( $labels['models'] ) . '</h2><!-- /wp:heading -->';
			$html .= '<!-- wp:html --><div class="yke-model-card-grid">';
			foreach ( $line_models as $m ) {
				$m_name = $zh ? $m['name_zh'] : $m['name'];
				$m_desc = $zh ? $m['desc_zh'] : $m['desc'];
				$m_url  = $line_url . $m['slug'] . '/';
				$m_img  = $theme_uri . '/assets/images/placeholders/' . $m['image'];
				$html .= '<a class="yke-model-card" href="' . esc_url( $m_url ) . '">';
				$html .= '<div class="yke-model-card-img"><img src="' . esc_url( $m_img ) . '" alt="' . esc_attr( $m_name ) . '"/></div>';
				$html .= '<div class="yke-model-card-body">';
				$html .= '<div class="yke-model-card-name">' . esc_html( $m_name ) . '</div>';
				$html .= '<div class="yke-model-card-desc">' . esc_html( $m_desc ) . '</div>';
				$html .= '<span class="yke-model-card-cta">' . esc_html( $zh ? '查看详情' : 'View Details' ) . '</span>';
				$html .= '</div></a>';
			}
			$html .= '</div><!-- /wp:html -->';
			$html .= '</div><!-- /wp:group -->';
		}

		// 5) Technical specifications.
		$html .= '<!-- wp:group {"className":"yke-page-section","layout":{"type":"constrained"}} --><div class="wp-block-group yke-page-section">';
		$html .= '<!-- wp:heading {"level":2,"fontSize":"lg"} --><h2 class="wp-block-heading has-lg-font-size">' . esc_html( $labels['specs'] ) . '</h2><!-- /wp:heading -->';
		$html .= '<!-- wp:html --><table class="yke-spec-table"><tbody>';
	foreach ( $specs as $s ) {
		$html .= '<tr><th>' . esc_html( $s[0] ) . '</th><td>' . esc_html( $s[1] ) . '</td></tr>';
	}
		$html .= '</tbody></table><!-- /wp:html -->';
		$html .= '</div><!-- /wp:group -->';

		// 6) Typical applications.
		$html .= '<!-- wp:group {"className":"yke-page-section","layout":{"type":"constrained"}} --><div class="wp-block-group yke-page-section">';
		$html .= '<!-- wp:heading {"level":2,"fontSize":"lg"} --><h2 class="wp-block-heading has-lg-font-size">' . esc_html( $labels['apps'] ) . '</h2><!-- /wp:heading -->';
		$html .= '<!-- wp:html --><div class="yke-feature-grid">';
		foreach ( $applications as $a ) {
			$html .= '<div class="yke-feature-item"><h4>' . esc_html( $a[0] ) . '</h4><p>' . esc_html( $a[1] ) . '</p></div>';
		}
		$html .= '</div><!-- /wp:html -->';
		$html .= '</div><!-- /wp:group -->';

		// 7) Standards & certifications / market access.
		$html .= '<!-- wp:group {"className":"yke-page-section yke-panel","layout":{"type":"constrained"}} --><div class="wp-block-group yke-page-section yke-panel">';
		$html .= '<!-- wp:heading {"level":2,"fontSize":"lg"} --><h2 class="wp-block-heading has-lg-font-size">' . esc_html( $labels['standards'] ) . '</h2><!-- /wp:heading -->';
		$html .= '<!-- wp:paragraph --><p>' . esc_html( $labels['standards_body'] ) . '</p><!-- /wp:paragraph -->';
		$html .= '<!-- wp:html --><div class="yke-cert-tags"><span>CE</span><span>CCC</span><span>ISO 9001</span><span>ISO 14001</span><span>ISO 45001</span><span>IEC 61439</span></div><!-- /wp:html -->';
		$html .= '</div><!-- /wp:group -->';

		// 8) Project confirmation (replaces Common faults).
		$html .= '<!-- wp:group {"className":"yke-page-section","layout":{"type":"constrained"}} --><div class="wp-block-group yke-page-section">';
		$html .= '<!-- wp:heading {"level":2,"fontSize":"lg"} --><h2 class="wp-block-heading has-lg-font-size">' . esc_html( $labels['confirm'] ) . '</h2><!-- /wp:heading -->';
		$html .= '<!-- wp:html --><div class="yke-feature-grid">';
		foreach ( $confirmations as $c ) {
			$html .= '<div class="yke-feature-item"><h4>' . esc_html( $c[0] ) . '</h4><p>' . esc_html( $c[1] ) . '</p></div>';
		}
		$html .= '</div><!-- /wp:html -->';
		$html .= '</div><!-- /wp:group -->';

		// 9) Delivery documents & service boundary.
		$html .= '<!-- wp:group {"className":"yke-page-section yke-panel","layout":{"type":"constrained"}} --><div class="wp-block-group yke-page-section yke-panel">';
		$html .= '<!-- wp:heading {"level":2,"fontSize":"lg"} --><h2 class="wp-block-heading has-lg-font-size">' . esc_html( $labels['delivery'] ) . '</h2><!-- /wp:heading -->';
		$html .= '<!-- wp:list {"className":"yke-checklist"} --><ul class="yke-checklist">';
		$delivery_items = $zh ? array(
			'18 个月标准质保，自提单日期起算',
			'按 IEC / GB 体系组织生产，出厂测试记录归档',
			'英文与中文资料随货交付（单线图、接线图、试验报告、操作手册、证书）',
			'支持 SGS、BV、Intertek 等第三方验货',
			'常规交期 30–45 天，定制方案报价时确认',
			'售后通过邮件、WhatsApp 及视频远程支持，必要时经代理网络安排现场服务',
		) : array(
			'18-month standard warranty from bill of lading date',
			'IEC / GB aligned production with archived factory test records',
			'English and Chinese documentation shipped with cargo (single-line diagram, wiring diagram, test report, operation manual, certificates)',
			'Third-party inspection welcomed (SGS, BV, TÜV, Intertek)',
			'Standard lead time 30–45 days; custom configurations confirmed at quotation',
			'After-sales support by email, WhatsApp, and video call; on-site dispatch via distributor network when needed',
		);
		foreach ( $delivery_items as $item ) {
			$html .= '<li>' . esc_html( $item ) . '</li>';
		}
		$html .= '</ul><!-- /wp:list -->';
		$html .= '</div><!-- /wp:group -->';

		// 10) Related case references.
		$related_cases = get_posts(
			array(
				'post_type'      => 'case_study',
				'post_status'    => 'publish',
				'posts_per_page' => 3,
				'tax_query'      => array(
					array(
						'taxonomy' => 'product_line',
						'field'    => 'slug',
						'terms'    => array( $key ),
					),
				),
				'lang'           => function_exists( 'pll_current_language' ) ? $lang : '',
			)
		);
		$html .= '<!-- wp:group {"className":"yke-page-section","layout":{"type":"constrained"}} --><div class="wp-block-group yke-page-section">';
		$html .= '<!-- wp:heading {"level":2,"fontSize":"lg"} --><h2 class="wp-block-heading has-lg-font-size">' . esc_html( $labels['cases'] ) . '</h2><!-- /wp:heading -->';
		if ( empty( $related_cases ) ) {
			$html .= '<!-- wp:paragraph --><p>' . esc_html( $labels['case_fallback'] ) . '</p><!-- /wp:paragraph -->';
		} else {
			$html .= '<!-- wp:html --><ul class="yke-line-list">';
			foreach ( $related_cases as $case ) {
				$html .= '<li><a href="' . esc_url( get_permalink( $case ) ) . '">' . esc_html( get_the_title( $case ) ) . '</a></li>';
			}
			$html .= '</ul><!-- /wp:html -->';
		}
		$html .= '</div><!-- /wp:group -->';

		// Wrap in .yke-marketing-shell so the right-side scroll-rail (page TOC) activates.
		$html = '<div class="yke-marketing-shell">' . $html . '</div>';

		return $html;
	}

	/**
	 * Build model detail page content (pineele-style single product page).
	 *
	 * @param string $line_key   Product line key (e.g. 'mv-switchgear').
	 * @param string $model_slug Model slug (e.g. 'kyn28-12').
	 * @param string $lang       Language code ('en' or 'zh').
	 * @return string
	 */
	protected static function build_model_detail_page_content( $line_key, $model_slug, $lang = 'en' ) {
		$models = self::get_product_models_for_line( $line_key );
		$model  = null;
		foreach ( $models as $m ) {
			if ( $m['slug'] === $model_slug ) {
				$model = $m;
				break;
			}
		}
		if ( ! $model ) {
			global $wp_query;
			if ( $wp_query instanceof WP_Query ) {
				$wp_query->set_404();
			}
			status_header( 404 );
			nocache_headers();
			return '';
		}

		$zh       = 'zh' === $lang;
		$data     = self::get_product_rich_data( $line_key );
		$line_name = $zh && ! empty( $data['zh']['title'] ) ? $data['zh']['title'] : $data['title'];
		$model_name = $zh ? $model['name_zh'] : $model['name'];
		$model_desc = $zh ? $model['desc_zh'] : $model['desc'];
		$overview   = isset( $model['overview'][ $lang ] ) ? $model['overview'][ $lang ] : $model['overview']['en'];

		$labels = $zh ? array(
			'home'            => '首页',
			'overview'        => '型号描述',
			'features'        => '核心特性',
			'specs'           => '技术参数',
			'applications'    => '应用场景',
			'why_choose'      => '为什么选择云科',
			'ordering'        => '订购指南',
			'inquiry'         => '提交询盘',
			'inquiry_sub'     => '告诉我们目的国、电压等级、数量与目标交期，云科将提供匹配方案。',
			'model_label'     => '型号',
			'brand'           => '品牌',
			'origin'          => '产地',
			'oem'             => 'OEM/ODM',
			'standards_label' => '执行标准',
			'oem_value'       => '可定制',
			'origin_value'    => '中国',
		) : array(
			'home'            => 'Home',
			'overview'        => 'Model description',
			'features'        => 'Key Features',
			'specs'           => 'Technical Parameters',
			'applications'    => 'Applications',
			'why_choose'      => 'Why Choose YKE',
			'ordering'        => 'Ordering Guidelines',
			'inquiry'         => 'Send Inquiry',
			'inquiry_sub'     => 'Share your target country, voltage class, quantity, and expected lead time — YKE will respond with a matched proposal.',
			'model_label'     => 'Model',
			'brand'           => 'Brand',
			'origin'          => 'Origin',
			'oem'             => 'OEM/ODM',
			'standards_label' => 'Standards',
			'oem_value'       => 'Available',
			'origin_value'    => 'China',
		);

		$theme_uri = get_template_directory_uri();
		$img       = $theme_uri . '/assets/images/placeholders/' . $model['image'];
		$line_url  = self::get_seeded_page_url( 'products/' . $line_key, $lang );
		$home_url  = $zh ? home_url( '/zh/' ) : home_url( '/' );

		$html = '';

		// 1) Breadcrumb.
		$html .= '<!-- wp:html --><nav class="yke-breadcrumb" aria-label="breadcrumb"><a href="' . esc_url( $home_url ) . '">' . esc_html( $labels['home'] ) . '</a><span class="sep">»</span><a href="' . esc_url( $line_url ) . '">' . esc_html( $line_name ) . '</a><span class="sep">»</span><span class="current">' . esc_html( $model_name ) . '</span></nav><!-- /wp:html -->';

		// 2) Model hero: 2-column grid (left: image + title + desc | right: info table).
		$model_hero_kicker = $zh ? '产品概览' : 'Overview';
		$html .= '<!-- wp:group {"className":"yke-model-hero","layout":{"type":"constrained"}} --><div class="wp-block-group yke-model-hero">';
		$html .= '<div class="yke-model-hero__media">';
		$html   .= '<!-- wp:html --><figure class="yke-visual-frame"><img src="' . esc_url( $img ) . '" alt="' . esc_attr( $model_name ) . '" style="border-radius:8px"/></figure><!-- /wp:html -->';
		$html   .= '<!-- wp:html --><p class="yke-kicker yke-model-hero__kicker">' . esc_html( $model_hero_kicker ) . '</p><!-- /wp:html -->';
		$html   .= '<!-- wp:heading {"level":1,"fontSize":"xl"} --><h1 class="wp-block-heading has-xl-font-size">' . esc_html( $model_name ) . '</h1><!-- /wp:heading -->';
		$html   .= '<!-- wp:paragraph --><p>' . esc_html( $model_desc ) . '</p><!-- /wp:paragraph -->';
		$html .= '</div><!-- /yke-model-hero__media -->';
		$html .= '<div class="yke-model-hero__specs">';
		$html .= '<!-- wp:html --><table class="yke-spec-table yke-info-table"><tbody>';
		// 基础 4 行(型号 / 品牌 / OEM/ODM / 产地)
		$html .= '<tr><th>' . esc_html( $labels['model_label'] ) . '</th><td>' . esc_html( $model['name'] ) . '</td></tr>';
		$html .= '<tr><th>' . esc_html( $labels['brand'] ) . '</th><td>YKE Electric</td></tr>';
		$html .= '<tr><th>' . esc_html( $labels['oem'] ) . '</th><td>' . esc_html( $labels['oem_value'] ) . '</td></tr>';
		$html .= '<tr><th>' . esc_html( $labels['origin'] ) . '</th><td>' . esc_html( $labels['origin_value'] ) . '</td></tr>';
		// 5 行型号专属技术参数(specs 的最后一项就是执行标准,自然替换原 IEC/GB 行)
		foreach ( $model['specs'] as $s ) {
			$spec_label = $zh && ! empty( $s['zh'] ) ? $s['zh'] : ( isset( $s['en'] ) ? $s['en'] : $s[0] );
			$spec_value = isset( $s['v'] ) ? $s['v'] : ( isset( $s[1] ) ? $s[1] : '' );
			$html .= '<tr><th>' . esc_html( $spec_label ) . '</th><td>' . esc_html( $spec_value ) . '</td></tr>';
		}
		$html .= '</tbody></table><!-- /wp:html -->';
		$html .= '</div><!-- /yke-model-hero__specs -->';
		$html .= '</div><!-- /wp:group -->';

		// 4) Overview.
		$html .= '<!-- wp:group {"className":"yke-page-section","layout":{"type":"constrained"}} --><div class="wp-block-group yke-page-section">';
		$html .= '<!-- wp:heading {"level":2,"fontSize":"lg"} --><h2 class="wp-block-heading has-lg-font-size">' . esc_html( $labels['overview'] ) . '</h2><!-- /wp:heading -->';
		$html .= '<!-- wp:paragraph --><p>' . esc_html( $overview ) . '</p><!-- /wp:paragraph -->';
		$html .= '</div><!-- /wp:group -->';

		// 5) Key Features.
		$html .= '<!-- wp:group {"className":"yke-page-section","layout":{"type":"constrained"}} --><div class="wp-block-group yke-page-section">';
		$html .= '<!-- wp:heading {"level":2,"fontSize":"lg"} --><h2 class="wp-block-heading has-lg-font-size">' . esc_html( $labels['features'] ) . '</h2><!-- /wp:heading -->';
		$html .= '<!-- wp:list {"className":"yke-checklist"} --><ul class="yke-checklist">';
		foreach ( $model['features'] as $f ) {
			$text = isset( $f[ $lang ] ) ? $f[ $lang ] : $f['en'];
			$html .= '<li>' . esc_html( $text ) . '</li>';
		}
		$html .= '</ul><!-- /wp:list -->';
		$html .= '</div><!-- /wp:group -->';

		// 7) Applications.
		$html .= '<!-- wp:group {"className":"yke-page-section","layout":{"type":"constrained"}} --><div class="wp-block-group yke-page-section">';
		$html .= '<!-- wp:heading {"level":2,"fontSize":"lg"} --><h2 class="wp-block-heading has-lg-font-size">' . esc_html( $labels['applications'] ) . '</h2><!-- /wp:heading -->';
		$html .= '<!-- wp:list {"className":"yke-checklist"} --><ul class="yke-checklist">';
		foreach ( $model['applications'] as $a ) {
			$text = isset( $a[ $lang ] ) ? $a[ $lang ] : $a['en'];
			$html .= '<li>' . esc_html( $text ) . '</li>';
		}
		$html .= '</ul><!-- /wp:list -->';
		$html .= '</div><!-- /wp:group -->';

		// 8) Why Choose YKE.
		$why_choose = $zh ? array(
			'出口 100+ 国家与地区，聚焦中东、东南亚、非洲、拉美新兴市场',
			'18 个月标准质保，自提单日期起算',
			'出厂 100% 全项必检，支持 SGS / BV / Intertek 第三方验货',
		) : array(
			'Exported to 100+ countries across Middle East, Southeast Asia, Africa, and Latin America',
			'18-month standard warranty from bill of lading date',
			'100% full-item factory testing, SGS / BV / Intertek third-party inspection welcomed',
		);
		$html .= '<!-- wp:group {"className":"yke-page-section yke-panel","layout":{"type":"constrained"}} --><div class="wp-block-group yke-page-section yke-panel">';
		$html .= '<!-- wp:heading {"level":2,"fontSize":"lg"} --><h2 class="wp-block-heading has-lg-font-size">' . esc_html( $labels['why_choose'] ) . '</h2><!-- /wp:heading -->';
		$html .= '<!-- wp:list {"className":"yke-checklist"} --><ul class="yke-checklist">';
		foreach ( $why_choose as $w ) {
			$html .= '<li>' . esc_html( $w ) . '</li>';
		}
		$html .= '</ul><!-- /wp:list -->';
		$html .= '</div><!-- /wp:group -->';

		// 9) Ordering Guidelines.
		$ordering = $zh ? array(
			'确认电压等级（如 12kV / 24kV / 40.5kV）与额定电流',
			'明确防护等级要求（户内 IP4X / 户外 IP65+）',
			'指定元器件品牌偏好（ABB / 施耐德 / 西门子 / 国产一线）',
			'确认目标交期（标准 30–45 天，定制方案另议）',
			'选择付款方式（T/T · L/C · D/P 灵活协商）',
		) : array(
			'Confirm voltage class (e.g., 12kV / 24kV / 40.5kV) and rated current',
			'Specify protection class (indoor IP4X / outdoor IP65+)',
			'Indicate component brand preference (ABB / Schneider / Siemens / top-tier domestic)',
			'Confirm target lead time (standard 30–45 days, custom on request)',
			'Select payment terms (T/T · L/C · D/P flexible)',
		);
		$html .= '<!-- wp:group {"className":"yke-page-section","layout":{"type":"constrained"}} --><div class="wp-block-group yke-page-section">';
		$html .= '<!-- wp:heading {"level":2,"fontSize":"lg"} --><h2 class="wp-block-heading has-lg-font-size">' . esc_html( $labels['ordering'] ) . '</h2><!-- /wp:heading -->';
		$html .= '<!-- wp:list {"className":"yke-checklist"} --><ul class="yke-checklist">';
		foreach ( $ordering as $o ) {
			$html .= '<li>' . esc_html( $o ) . '</li>';
		}
		$html .= '</ul><!-- /wp:list -->';
		$html .= '</div><!-- /wp:group -->';

		// Wrap in .yke-marketing-shell so the right-side scroll-rail (page TOC) activates.
		$html = '<div class="yke-marketing-shell">' . $html . '</div>';

		return $html;
	}

	/**
	 * Build enhanced about page content.
	 *
	 * @return string
	 */
	protected static function build_enhanced_about_page_content() {
		$theme_uri = get_template_directory_uri();
		$company_img = $theme_uri . '/assets/images/placeholders/company-profile.png';
		$quality_img = $theme_uri . '/assets/images/placeholders/quality-assurance.png';
		$markets_img = $theme_uri . '/assets/images/placeholders/global-markets.png';
		$scene_a_img = $theme_uri . '/assets/images/placeholders/case-study-nigeria-indonesia.png';
		$scene_b_img = $theme_uri . '/assets/images/placeholders/case-study-saudi-vietnam.png';

		$html  = '';

		// 1) Intro: image + text two-column
		$html .= '<div class="yke-about-intro">';
		$html .= '<div class="yke-about-intro-img" style="background-image:url(' . esc_url( $company_img ) . ')"></div>';
		$html .= '<div>';
		$html .= '<h2>Company Profile</h2>';
		$html .= '<p>YKE is an export-focused manufacturer of power distribution equipment, delivering IEC and GB aligned solutions for industrial substations, utility feeders, and renewable integration projects across emerging markets.</p>';
		$html .= '<ul class="yke-checklist">';
		$html .= '<li>0.4kV to 35kV export-oriented product coverage</li>';
		$html .= '<li>IEC and GB aligned manufacturing approach</li>';
		$html .= '<li>Quotation and documentation support for distributors and EPC teams</li>';
		$html .= '<li>Target-market adaptation for heat, dust, humidity, and altitude</li>';
		$html .= '</ul></div></div>';

		// 2) Stat band — PPT-verified values (no fake year/area/patent/equipment counts).
		$html .= '<div class="yke-stat-band">';
		$html .= '<div class="yke-stat-item"><div class="yke-stat-value">0.4–35kV</div><div class="yke-stat-label">Voltage coverage</div></div>';
		$html .= '<div class="yke-stat-item"><div class="yke-stat-value">100+</div><div class="yke-stat-label">Export countries</div></div>';
		$html .= '<div class="yke-stat-item"><div class="yke-stat-value">100%</div><div class="yke-stat-label">Outgoing inspection</div></div>';
		$html .= '<div class="yke-stat-item"><div class="yke-stat-value">18 mo</div><div class="yke-stat-label">Standard warranty</div></div>';
		$html .= '</div>';

		// 3) Factory & export capability cards
		$html .= '<h2>Factory &amp; Export Capability</h2>';
		$html .= '<div class="yke-feature-grid">';
		$html .= '<div class="yke-feature-card"><div class="yke-feature-icon">🏭</div><h3>Export Delivery Process</h3><p>YKE coordinates technical matching, production scheduling, factory inspection, export packaging, and shipping documentation to match overseas project timelines.</p></div>';
		$html .= '<div class="yke-feature-card"><div class="yke-feature-icon">🔍</div><h3>Quality &amp; Traceability</h3><p>Production follows IEC/GB processes with retained factory test records. Third-party inspection and documentation audit supported at every stage.</p></div>';
		$html .= '<div class="yke-feature-card"><div class="yke-feature-icon">🌍</div><h3>Target Market Adaptation</h3><p>Structural and component adaptation for high temperature, dust, humidity, high altitude, and renewable grid-connection requirements.</p></div>';
		$html .= '</div>';

		// 4) Company milestones — fake years removed; only capability milestones retained.
		$html .= '<h2>Company Milestones</h2>';
		$html .= '<p style="margin:0 0 1.5rem;color:var(--yke-slate);max-width:60ch">Capability milestones in export-grade power distribution equipment. Specific founding year and capacity figures will be added once verified.</p>';
		$html .= '<ul class="yke-timeline">';
		$html .= '<li class="yke-timeline-item"><span class="yke-timeline-year">—</span><h4 class="yke-timeline-title">Export-Oriented Manufacturing</h4><p class="yke-timeline-desc">YKE established as an export-focused electrical equipment manufacturer serving industrial and utility buyers across emerging markets.</p></li>';
		$html .= '<li class="yke-timeline-item"><span class="yke-timeline-year">—</span><h4 class="yke-timeline-title">Five Product Lines</h4><p class="yke-timeline-desc">MV switchgear, LV switchgear, dry-type and oil-immersed transformers, prefabricated substations, and new-energy electrical packages covering 0.4kV to 35kV.</p></li>';
		$html .= '<li class="yke-timeline-item"><span class="yke-timeline-year">—</span><h4 class="yke-timeline-title">IEC-Aligned Production</h4><p class="yke-timeline-desc">IEC 61439 standard-compliance documentation and FAT records for export markets, with SGS, BV, and Intertek third-party inspection support.</p></li>';
		$html .= '<li class="yke-timeline-item"><span class="yke-timeline-year">—</span><h4 class="yke-timeline-title">New Energy Product Line</h4><p class="yke-timeline-desc">Solar grid-connection, energy storage distribution, and EV charging support substations for renewable EPC projects.</p></li>';
		$html .= '<li class="yke-timeline-item"><span class="yke-timeline-year">—</span><h4 class="yke-timeline-title">100+ Country Export Network</h4><p class="yke-timeline-desc">Export delivery records across the Middle East, Southeast Asia, and Africa — including Saudi Arabia, Vietnam, Nigeria, and Indonesia.</p></li>';
		$html .= '</ul>';

		// 5) Team grid
		$html .= '<h2>Leadership Team</h2>';
		$html .= '<div class="yke-team-grid">';
		$html .= '<div class="yke-team-card"><div class="yke-team-card-img" style="background-image:url(' . esc_url( $company_img ) . ')"></div><div class="yke-team-card-body"><h4>General Manager</h4><p>Strategy &amp; Export</p></div></div>';
		$html .= '<div class="yke-team-card"><div class="yke-team-card-img" style="background-image:url(' . esc_url( $quality_img ) . ')"></div><div class="yke-team-card-body"><h4>Technical Director</h4><p>Engineering &amp; Design</p></div></div>';
		$html .= '<div class="yke-team-card"><div class="yke-team-card-img" style="background-image:url(' . esc_url( $markets_img ) . ')"></div><div class="yke-team-card-body"><h4>Export Sales Lead</h4><p>International Sales</p></div></div>';
		$html .= '<div class="yke-team-card"><div class="yke-team-card-img" style="background-image:url(' . esc_url( $scene_a_img ) . ')"></div><div class="yke-team-card-body"><h4>After-sales Manager</h4><p>Service &amp; Support</p></div></div>';
		$html .= '</div>';

		// 6) Project scene gallery
		$html .= '<h2>Field Installations</h2>';
		$html .= '<p>A sample of installed projects across export markets — from industrial substations to renewable collector stations.</p>';
		$html .= '<div class="yke-cert-scroll">';
		$html .= '<figure class="yke-cert-item"><img src="' . esc_url( $scene_a_img ) . '" alt="Nigeria and Indonesia project sites"/><figcaption>Nigeria &amp; Indonesia</figcaption></figure>';
		$html .= '<figure class="yke-cert-item"><img src="' . esc_url( $scene_b_img ) . '" alt="Saudi Arabia and Vietnam project sites"/><figcaption>Saudi Arabia &amp; Vietnam</figcaption></figure>';
		$html .= '<figure class="yke-cert-item"><img src="' . esc_url( $markets_img ) . '" alt="Africa and Latin America markets"/><figcaption>Africa &amp; Latin America</figcaption></figure>';
		$html .= '<figure class="yke-cert-item"><img src="' . esc_url( $quality_img ) . '" alt="Quality certifications"/><figcaption>Certifications</figcaption></figure>';
		$html .= '</div>';

		// 7) Customer testimonials removed — no verified client quotes available. Will be added once real references are collected.
		$html .= '<h2>Client References</h2>';
		$html .= '<p style="margin:0 0 1.5rem;color:var(--yke-slate);max-width:60ch">Verified client references and case-specific feedback can be shared during inquiry review. Representative delivery records are listed on the case studies page.</p>';
		$html .= '<p><a class="yke-btn-primary" href="/case-studies/">View Case Studies</a></p>';

		// 8) Certifications + CTA band (NEW CTA).
		$html .= '<h2>Quality Control &amp; Certifications</h2>';
		$html .= '<p>Every cabinet, transformer, and substation ships with type-test reports, routine test records, and inspection-ready documentation in English and Chinese.</p>';
		$html .= '<div class="yke-cert-tags"><span>CE</span><span>CCC</span><span>ISO 9001</span><span>ISO 14001</span><span>ISO 45001</span><span>IEC 61439</span></div>';

		// CTA band.
		$html .= '<div class="yke-cta-band">';
		$html .= '<h3>Want to discuss your project with our engineering team?</h3>';
		$html .= '<p>Send us your target country, voltage level, and application scenario — YKE will respond with a matched proposal.</p>';
		$html .= '<a class="yke-btn-primary" href="/contact/">Talk to YKE Sales</a>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Build enhanced distributor page content.
	 *
	 * @return string
	 */
	protected static function build_enhanced_distributor_page_content() {
		$html = '';

		// 1) Intro block.
		$html .= '<div class="yke-page-intro">';
		$html .= '<p>YKE partners with regional distributors across Africa, Southeast Asia, the Middle East, and Latin America. We provide territory protection, technical and bidding support, marketing materials, and a structured after-sales process — so distributors can focus on local market development with a reliable export-grade supplier behind them.</p>';
		$html .= '</div>';

		// 2) Distributor support structure (3 cards).
		$html .= '<h2>Distributor Support Structure</h2>';
		$html .= '<div class="yke-feature-grid">';
		$html .= '<div class="yke-feature-card"><div class="yke-feature-icon">🛡️</div><h3>Territory Protection</h3><p>Clear regional boundaries, channel conflict control, and long-term distributor development as the foundation of cooperation.</p></div>';
		$html .= '<div class="yke-feature-card"><div class="yke-feature-icon">🔧</div><h3>Technical Support</h3><p>Quotation coordination, product matching, bidding documentation, and representative project case support.</p></div>';
		$html .= '<div class="yke-feature-card"><div class="yke-feature-icon">📄</div><h3>Marketing Materials</h3><p>Brochures, case summaries, bilingual presentation materials, and competitive comparison documents for local development.</p></div>';
		$html .= '</div>';

		// 3) Cooperation process steps (4 steps).
		$html .= '<h2>Cooperation Process</h2>';
		$html .= '<div class="yke-steps">';
		$html .= '<div class="yke-step"><h4>Initial Contact</h4><p>Submit your company profile, target market, and product interest through the inquiry form.</p></div>';
		$html .= '<div class="yke-step"><h4>Qualification Review</h4><p>YKE reviews your market position, technical capability, and distribution network.</p></div>';
		$html .= '<div class="yke-step"><h4>Agreement Signing</h4><p>Territory protection, pricing structure, and support terms are formalized in a distributor agreement.</p></div>';
		$html .= '<div class="yke-step"><h4>Ongoing Support</h4><p>Continuous technical training, marketing materials, and after-sales coordination.</p></div>';
		$html .= '</div>';

		// 4) Distributor tier comparison table (NEW).
		$html .= '<h2>Distributor Tier Comparison</h2>';
		$html .= '<p style="margin:0 0 1.5rem;color:var(--yke-slate);max-width:60ch">Three partnership tiers to match distributor scale, market coverage, and commitment level.</p>';
		$html .= '<table class="yke-comparison-table">';
		$html .= '<thead><tr><th>Benefit</th><th>Regional Agent</th><th class="yke-tier-popular">Silver Distributor</th><th>Gold Distributor</th></tr></thead>';
		$html .= '<tbody>';
		$html .= '<tr><th>Territory scope</th><td>Single city / province</td><td>Single country</td><td>Multi-country region</td></tr>';
		$html .= '<tr><th>Exclusive territory protection</th><td><span class="yke-comparison-cross">—</span></td><td><span class="yke-comparison-check">✓</span></td><td><span class="yke-comparison-check">✓</span></td></tr>';
		$html .= '<tr><th>Preferred pricing tier</th><td>Standard</td><td>Silver tier</td><td>Gold tier (best)</td></tr>';
		$html .= '<tr><th>Annual purchase commitment</th><td>None</td><td>From USD 200K</td><td>From USD 500K</td></tr>';
		$html .= '<tr><th>Co-branded marketing materials</th><td><span class="yke-comparison-cross">—</span></td><td><span class="yke-comparison-check">✓</span></td><td><span class="yke-comparison-check">✓</span></td></tr>';
		$html .= '<tr><th>Bidding &amp; project support</th><td>Email support</td><td>Dedicated engineer</td><td>On-site support available</td></tr>';
		$html .= '<tr><th>Annual factory visit &amp; training</th><td><span class="yke-comparison-cross">—</span></td><td>1 slot / year</td><td>Unlimited</td></tr>';
		$html .= '<tr><th>Priority production slot</th><td><span class="yke-comparison-cross">—</span></td><td><span class="yke-comparison-cross">—</span></td><td><span class="yke-comparison-check">✓</span></td></tr>';
		$html .= '</tbody></table>';

		// 5) Distributor testimonials (NEW).
		$html .= '<h2>What Our Distributors Say</h2>';
		$html .= '<p style="margin:0 0 1.5rem;color:var(--yke-slate);max-width:60ch">Long-term distributor partners across emerging markets share their experience working with YKE.</p>';
		$html .= '<div class="yke-testimonial-grid">';
		$html .= '<div class="yke-testimonial"><div class="yke-review-stars">★★★★★</div><p>"We have worked with YKE for 6 years as their country distributor. The territory protection is real — we have never had a channel conflict in our market, and the technical team responds within hours on WhatsApp."</p><cite>— Country Distributor, West Africa</cite></div>';
		$html .= '<div class="yke-testimonial"><div class="yke-review-stars">★★★★★</div><p>"The bilingual brochures and case-study decks make a real difference in our local bidding process. We win more projects because we can hand buyers IEC-aligned documentation in their language."</p><cite>— Regional Distributor, Southeast Asia</cite></div>';
		$html .= '<div class="yke-testimonial"><div class="yke-review-stars">★★★★★</div><p>"As a Gold Distributor we get priority production slots during peak season — that alone has helped us land two utility tenders we would otherwise have lost on lead time."</p><cite>— Gold Distributor, Middle East</cite></div>';
		$html .= '</div>';

		// 6) CTA band.
		$html .= '<div class="yke-cta-band">';
		$html .= '<h3>Ready to Partner with YKE?</h3>';
		$html .= '<p>Tell us about your company, target market, and distribution capabilities. Our export team will reply within 1 business day with next-step partnership options.</p>';
		$html .= '<a class="yke-btn-primary" href="/contact/">Become a Distributor</a>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Build Chinese helper case-studies content.
	 *
	 * @return string
	 */
	protected static function build_zh_case_studies_page_content() {
		return '<!-- wp:paragraph --><p>云科电气中文项目案例页，收录云科在中东、东南亚、非洲和新能源项目中的代表性交付案例。</p><!-- /wp:paragraph -->';
	}

	/**
	 * Build Chinese helper root content.
	 *
	 * @return string
	 */
	protected static function build_zh_root_page_content() {
		return '<!-- wp:paragraph --><p>广州云科电气设备有限公司中文站入口。浏览产品总览、项目案例、关于云科和代理合作，或直接提交询盘。</p><!-- /wp:paragraph --><!-- wp:list --><ul><li><a href="/zh/about-yke/">关于云科</a></li><li><a href="/zh/distributor-partnership/">代理合作</a></li><li><a href="/zh/contact/">联系我们</a></li><li><a href="/zh/products/">产品总览</a></li><li><a href="/zh/case-studies/">项目案例</a></li></ul><!-- /wp:list -->';
	}

	/**
	 * Build Chinese helper about content.
	 *
	 * @return string
	 */
	protected static function build_zh_about_page_content() {
		return '<!-- wp:paragraph --><p>广州云科电气设备有限公司专注于电力电气设备外贸出口，产品覆盖 0.4kV 至 35kV 中低压成套设备、变压器、预装式变电站和新能源电气系统，已向 100+ 国家和地区配套交付。</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>按 IEC 标准组织生产，出厂 100% 例行检验，安排 SGS、BV、Intertek 第三方验货，并提供 18 个月标准质保。</p><!-- /wp:paragraph -->';
	}

	/**
	 * Build Chinese helper distributor content.
	 *
	 * @return string
	 */
	protected static function build_zh_distributor_page_content() {
		return '<!-- wp:paragraph --><p>云科为区域合作伙伴提供产品、资料与项目支持，包括区域保护、技术选型、营销资料和售后流程。请查看英文代理合作页了解完整合作结构。</p><!-- /wp:paragraph -->';
	}

	/**
	 * Build Chinese helper products content.
	 *
	 * @return string
	 */
	protected static function build_zh_products_page_content() {
		return '<!-- wp:paragraph --><p>云科电气中文产品总览，概览 5 大产品线、案例已交付型号、出口交付能力与标准认证支持。</p><!-- /wp:paragraph -->';
	}

	/**
	 * Build Chinese helper product content.
	 *
	 * @param string $title Product title.
	 * @param string $summary Product summary.
	 * @return string
	 */
	protected static function build_zh_product_page_content( $title, $summary ) {
		return '<!-- wp:paragraph --><p>' . esc_html( $summary ) . '</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>如需进一步了解配置、元器件品牌或交期，请提交询盘。</p><!-- /wp:paragraph --><!-- wp:shortcode -->[yke_inquiry_form product_interest="' . esc_attr( $title ) . '"]<!-- /wp:shortcode -->';
	}

	/**
	 * Determine whether a seeded page should be updated.
	 *
	 * @param WP_Post $post Existing page.
	 * @param array   $definition Desired state.
	 * @return bool
	 */
	protected static function should_update_seeded_page_copy( $post, $definition ) {
		if ( $post->post_title !== $definition['title'] ) {
			return true;
		}

		if ( ! empty( $definition['markers'] ) ) {
			foreach ( $definition['markers'] as $marker ) {
				if ( false !== strpos( (string) $post->post_content, (string) $marker ) ) {
					return true;
				}
			}
		}

		return false === strpos( (string) $post->post_content, 'yke-page-section' );
	}

	/**
	 * Determine the current site language.
	 *
	 * @return string
	 */
	protected static function get_current_site_language() {
		if ( function_exists( 'pll_current_language' ) ) {
			$lang = pll_current_language();
			if ( $lang ) {
				return $lang;
			}
		}

		return self::is_chinese_context() ? 'zh' : 'en';
	}

	/**
	 * Cached translation dictionary.
	 *
	 * @var array|null
	 */
	protected static $translation_dict = null;

	/**
	 * Load the translation dictionary (cached).
	 *
	 * @return array
	 */
	protected static function get_translation_dict() {
		if ( null === self::$translation_dict ) {
			$path = dirname( __FILE__ ) . '/translations.php';
			if ( file_exists( $path ) ) {
				self::$translation_dict = include $path;
			}
			if ( ! is_array( self::$translation_dict ) ) {
				self::$translation_dict = array();
			}
		}
		return self::$translation_dict;
	}

	/**
	 * Register all dictionary strings into Polylang String Translations
	 * so admins can translate them in the backend UI (Languages -> Strings translations).
	 *
	 * @return void
	 */
	public static function register_pll_strings() {
		if ( ! function_exists( 'pll_register_string' ) ) {
			return;
		}
		$dict = self::get_translation_dict();
		foreach ( $dict as $key => $translations ) {
			$en = isset( $translations['en'] ) ? $translations['en'] : $key;
			pll_register_string( $key, $en, 'YKE Site' );
		}
	}

	/**
	 * Translate a string by key for the given (or current) language.
	 *
	 * Resolution order:
	 *   1. Polylang string translation (admin-editable, highest priority).
	 *   2. Built-in dictionary (en/zh bundled).
	 *   3. AI translation cache (previously auto-translated).
	 *   4. Live AI translation (MyMemory) + cache.
	 *   5. English original (last resort).
	 *
	 * @param string $key String key in the translation dictionary.
	 * @param string $lang Language slug. Defaults to current language.
	 * @return string
	 */
	protected static function t( $key, $lang = null ) {
		if ( null === $lang ) {
			$lang = self::get_current_site_language();
		}
		$dict = self::get_translation_dict();
		$en   = isset( $dict[ $key ]['en'] ) ? $dict[ $key ]['en'] : $key;

		// 1. Polylang string translation (admin-editable).
		if ( function_exists( 'pll_translate_string' ) ) {
			$translated = pll_translate_string( $en, $lang );
			if ( $translated && $translated !== $en ) {
				return $translated;
			}
		}

		// 2. Built-in dictionary fallback.
		if ( isset( $dict[ $key ][ $lang ] ) && '' !== $dict[ $key ][ $lang ] ) {
			return $dict[ $key ][ $lang ];
		}

		// 3. AI translation cache (for languages not in the dictionary).
		//    The cache is filled by a one-time warm-up triggered from the frontend
		//    (see enqueue_ai_translate_warmup) or by the admin bulk-translate action.
		//    We never call the translation API during page render to keep TTFB low.
		if ( 'en' !== $lang ) {
			$ai_cache = self::get_ai_translate_cache( $lang );
			if ( isset( $ai_cache[ $en ] ) && '' !== $ai_cache[ $en ] ) {
				return $ai_cache[ $en ];
			}
		}

		return $en;
	}

	/**
	 * Read the persistent AI translation cache for a language.
	 *
	 * @param string $lang Language slug.
	 * @return array<string,string> Map of English source -> translated text.
	 */
	protected static function get_ai_translate_cache( $lang ) {
		$all = get_option( 'yke_ai_translate_cache', array() );
		if ( ! is_array( $all ) || ! isset( $all[ $lang ] ) || ! is_array( $all[ $lang ] ) ) {
			return array();
		}
		return $all[ $lang ];
	}

	/**
	 * Merge new AI translations into the persistent cache.
	 *
	 * @param string               $lang     Language slug.
	 * @param array<string,string> $new_pair Map of English source -> translated text.
	 * @return void
	 */
	protected static function set_ai_translate_cache( $lang, $new_pair ) {
		if ( empty( $new_pair ) || ! is_array( $new_pair ) ) {
			return;
		}
		$all = get_option( 'yke_ai_translate_cache', array() );
		if ( ! is_array( $all ) ) {
			$all = array();
		}
		if ( ! isset( $all[ $lang ] ) || ! is_array( $all[ $lang ] ) ) {
			$all[ $lang ] = array();
		}
		$all[ $lang ] = array_merge( $all[ $lang ], $new_pair );

		// Cap the cache at 2000 entries per language to keep the option row small.
		if ( count( $all[ $lang ] ) > 2000 ) {
			$all[ $lang ] = array_slice( $all[ $lang ], -2000, null, true );
		}
		update_option( 'yke_ai_translate_cache', $all, false );
	}

	/**
	 * Call the free MyMemory translation API for a single English string.
	 *
	 * Returns the translated text, or null on failure / when skipped.
	 *
	 * @param string $text English source text.
	 * @param string $lang Target language slug (ISO 639-1, e.g. am, fr, es).
	 * @return string|null
	 */
	protected static function ai_translate_text( $text, $lang ) {
		if ( '' === $text || 'en' === $lang ) {
			return $text;
		}
		// Skip during admin / AJAX / cron to keep dashboards snappy.
		if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return null;
		}
		// MyMemory free tier is unreliable past 500 chars per request.
		if ( mb_strlen( $text, 'UTF-8' ) > 500 ) {
			return null;
		}

		$url = 'https://api.mymemory.translated.net/get'
			. '?q=' . rawurlencode( $text )
			. '&de=info@yke-global.com'
			. '&langpair=' . rawurlencode( 'en|' . $lang );

		$response = wp_remote_get( $url, array(
			'timeout' => 6,
			'headers' => array( 'User-Agent' => 'YKE-Site-Core/' . YKE_SITE_CORE_VERSION ),
		) );
		if ( is_wp_error( $response ) ) {
			return null;
		}
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) || empty( $data['responseData']['translatedText'] ) ) {
			return null;
		}
		$translated = trim( (string) $data['responseData']['translatedText'] );
		// MyMemory sometimes returns a warning string instead of a translation.
		if ( '' === $translated ) {
			return null;
		}
		$upper = strtoupper( $translated );
		if ( 0 === strpos( $upper, 'PLEASE SELECT' )
			|| 0 === strpos( $upper, 'MYMEMORY WARNING' )
			|| 0 === strpos( $upper, 'INVALID LANGUAGE' )
			|| 0 === strpos( $upper, 'QUOTA FINISHED' ) ) {
			return null;
		}
		return $translated;
	}

	/**
	 * Batch translate every registered YKE Site string to a target language
	 * (skipping strings already in the dictionary for that language).
	 *
	 * Used by the "Pre-translate" admin button and the frontend REST warm-up.
	 *
	 * @param string $lang    Target language slug.
	 * @param int    $delay_ms Delay between API calls to respect rate limits.
	 * @return array{translated:int,skipped:int,failed:int}
	 */
	public static function bulk_ai_translate_language( $lang, $delay_ms = 120 ) {
		if ( 'en' === $lang ) {
			return array( 'translated' => 0, 'skipped' => 0, 'failed' => 0 );
		}
		$dict = self::get_translation_dict();
		$cache = self::get_ai_translate_cache( $lang );

		$stats = array( 'translated' => 0, 'skipped' => 0, 'failed' => 0 );
		foreach ( $dict as $key => $translations ) {
			$en = isset( $translations['en'] ) ? $translations['en'] : $key;
			if ( '' === $en ) {
				continue;
			}
			// Skip if dictionary already has a translation for this language.
			if ( isset( $translations[ $lang ] ) && '' !== $translations[ $lang ] ) {
				++$stats['skipped'];
				continue;
			}
			// Skip if already cached.
			if ( isset( $cache[ $en ] ) && '' !== $cache[ $en ] ) {
				++$stats['skipped'];
				continue;
			}
			$translated = self::ai_translate_text( $en, $lang );
			if ( $translated ) {
				self::set_ai_translate_cache( $lang, array( $en => $translated ) );
				++$stats['translated'];
			} else {
				++$stats['failed'];
			}
			if ( $delay_ms > 0 ) {
				usleep( $delay_ms * 1000 );
			}
		}
		// Mark this language as warmed so the frontend stops calling the warm-up endpoint.
		set_transient( 'yke_ai_translate_warmed_' . $lang, time(), DAY_IN_SECONDS );
		return $stats;
	}

	/**
	 * Enqueue a tiny inline script on the frontend that:
	 *   1. Reads the current Polylang language from the URL or body class.
	 *   2. If the language is something other than en/zh AND the warm-up cache is
	 *      empty, asks the server to bulk-translate all registered YKE strings
	 *      via MyMemory, then reloads the page so the translated strings render.
	 *
	 * The script is a no-op on en/zh pages.
	 *
	 * @return void
	 */
	public static function enqueue_ai_translate_warmup() {
		if ( is_admin() ) {
			return;
		}
		$lang = self::get_current_site_language();
		if ( 'en' === $lang || 'zh' === $lang ) {
			return;
		}
		// Skip if this language was warmed within the last 24h.
		if ( get_transient( 'yke_ai_translate_warmed_' . $lang ) ) {
			return;
		}
		// Skip bots / previews to avoid wasted API calls.
		if ( is_preview() || ( isset( $_SERVER['HTTP_USER_AGENT'] ) && preg_match( '/bot|crawl|spider|slurp|preview/i', (string) $_SERVER['HTTP_USER_AGENT'] ) ) ) {
			return;
		}

		$ajax_url = admin_url( 'admin-ajax.php' );
		wp_register_script( 'yke-ai-warmup', false, array(), YKE_SITE_CORE_VERSION, true );
		wp_enqueue_script( 'yke-ai-warmup' );
		wp_add_inline_script( 'yke-ai-warmup', self::build_ai_warmup_script( $lang, $ajax_url ), 'after' );
	}

	/**
	 * Build the inline JS that drives the warm-up.
	 *
	 * @param string $lang     Current language slug.
	 * @param string $ajax_url admin-ajax.php URL.
	 * @return string
	 */
	protected static function build_ai_warmup_script( $lang, $ajax_url ) {
		$lang_json     = wp_json_encode( $lang );
		$ajax_url_json = wp_json_encode( $ajax_url );
		return "(function(){var lang={$lang_json},url={$ajax_url_json};"
			. "if(window.__ykeWarmupDone)return;window.__ykeWarmupDone=true;"
			. "var check=url+'?action=yke_check_translate&lang='+encodeURIComponent(lang);"
			. "fetch(check,{credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){"
			. "if(!j||!j.success||!j.data)return;"
			. "if(!j.data.needs_warm)return;"
			. "var bar=document.createElement('div');"
			. "bar.textContent='Translating to '+lang+'…';"
			. "bar.style.cssText='position:fixed;left:50%;bottom:24px;transform:translateX(-50%);padding:10px 18px;background:#0b1e3a;color:#fff;font:600 13px/1.2 system-ui;border-radius:999px;box-shadow:0 6px 20px rgba(0,0,0,.25);z-index:99999;opacity:.95';"
			. "document.body.appendChild(bar);"
			. "var warm=url+'?action=yke_warm_translate&lang='+encodeURIComponent(lang);"
			. "fetch(warm,{credentials:'same-origin'}).then(function(r){return r.json();}).then(function(w){"
			. "if(w&&w.success){location.reload();}"
			. "}).catch(function(){bar.remove();});"
			. "}).catch(function(){});})();";
	}

	/**
	 * AJAX: report whether the AI translation cache is warm for a language.
	 *
	 * @return void
	 */
	public static function ajax_check_translate() {
		$lang = isset( $_GET['lang'] ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : '';
		if ( ! $lang || ! self::is_valid_target_lang( $lang ) ) {
			wp_send_json_error( 'invalid_lang' );
		}
		$warmed = (bool) get_transient( 'yke_ai_translate_warmed_' . $lang );
		wp_send_json_success( array(
			'lang'       => $lang,
			'warmed'     => $warmed,
			'needs_warm' => ! $warmed,
		) );
	}

	/**
	 * AJAX: bulk-translate all registered YKE strings to the requested language.
	 *
	 * Long-running but bounded by the dictionary size (~150 strings × ~120ms).
	 *
	 * @return void
	 */
	public static function ajax_warm_translate() {
		$lang = isset( $_GET['lang'] ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : '';
		if ( ! $lang || ! self::is_valid_target_lang( $lang ) ) {
			wp_send_json_error( 'invalid_lang' );
		}
		// Bail if already warmed to avoid burning through the MyMemory quota.
		if ( get_transient( 'yke_ai_translate_warmed_' . $lang ) ) {
			wp_send_json_success( array(
				'lang'   => $lang,
				'warmed' => true,
				'cached' => true,
			) );
		}
		$stats = self::bulk_ai_translate_language( $lang, 60 );
		wp_send_json_success( array(
			'lang'   => $lang,
			'warmed' => true,
			'stats'  => $stats,
		) );
	}

	/**
	 * Validate a Polylang target language slug for the AI translation pipeline.
	 *
	 * @param string $lang Language slug.
	 * @return bool
	 */
	protected static function is_valid_target_lang( $lang ) {
		$lang = (string) $lang;
		if ( '' === $lang || strlen( $lang ) > 12 ) {
			return false;
		}
		// ISO 639-1 lowercase letters only.
		return (bool) preg_match( '/^[a-z]{2,3}(?:-[a-z0-9]+)?$/', $lang );
	}

	/**
	 * Admin handler: bulk translate all strings to a chosen language.
	 *
	 * Triggered from the YKE settings page (admin-post.php form submit).
	 *
	 * @return void
	 */
	public static function admin_post_bulk_translate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'forbidden' );
		}
		check_admin_referer( 'yke_bulk_translate' );
		$lang = isset( $_POST['lang'] ) ? sanitize_text_field( wp_unslash( $_POST['lang'] ) ) : '';
		if ( ! $lang || ! self::is_valid_target_lang( $lang ) ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'yke-site-core', 'yke_msg' => 'invalid_lang' ), admin_url( 'admin.php' ) ) );
			exit;
		}
		// Reset cache and warmed flag so the bulk run actually re-translates.
		$all = get_option( 'yke_ai_translate_cache', array() );
		if ( isset( $all[ $lang ] ) ) {
			unset( $all[ $lang ] );
			update_option( 'yke_ai_translate_cache', $all, false );
		}
		delete_transient( 'yke_ai_translate_warmed_' . $lang );
		$stats = self::bulk_ai_translate_language( $lang, 80 );
		$redirect = add_query_arg( array(
			'page'    => 'yke-site-core',
			'yke_msg' => 'translate_done',
			'lang'    => $lang,
			't'       => $stats['translated'],
			's'       => $stats['skipped'],
			'f'       => $stats['failed'],
		), admin_url( 'admin.php' ) );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Check whether current request is Chinese helper context.
	 *
	 * @return bool
	 */
	protected static function is_chinese_context() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		if ( 0 === strpos( $uri, '/zh/' ) || '/zh' === $uri ) {
			return true;
		}

		if ( function_exists( 'pll_current_language' ) ) {
			return 'zh' === pll_current_language();
		}

		return false;
	}

	/**
	 * Get a seeded page URL by path and language.
	 *
	 * @param string $path Relative path.
	 * @param string $lang Language slug.
	 * @return string
	 */
	protected static function get_seeded_page_url( $path, $lang ) {
		$path = trim( (string) $path, '/' );
		if ( '' === $path && 'en' === $lang ) {
			return home_url( '/' );
		}

		if ( '' === $path && 'zh' === $lang ) {
			$post = self::find_seeded_page( 'page:home:zh', 'zh' );
			if ( $post ) {
				return get_permalink( $post );
			}
		}

		// Third-language fallback: use /{lang}/ prefix when no seeded page exists.
		if ( '' === $path && ! in_array( $lang, array( 'en', 'zh' ), true ) ) {
			return home_url( '/' . $lang . '/' );
		}

		$seed_key = 'page:' . ( '' === $path ? 'home' : $path ) . ':' . $lang;
		$lookup   = 'zh' === $lang ? ( '' === $path ? 'zh' : 'zh/' . $path ) : $path;
		$post     = self::find_seeded_page( $seed_key, $lookup );
		if ( $post ) {
			return get_permalink( $post );
		}

		$post = self::find_post_by_path( $lookup, 'page' );

		if ( $post ) {
			return get_permalink( $post );
		}

		$fallback = '' === $lookup ? '/' : '/' . $lookup . '/';
		return home_url( $fallback );
	}

	/**
	 * Resolve a language switch URL for the current post if possible.
	 *
	 * @param string $target_lang Target language.
	 * @return string
	 */
	protected static function get_language_switch_url( $target_lang ) {
		if ( is_post_type_archive( 'case_study' ) ) {
			return self::get_case_studies_url( $target_lang );
		}

		$post = get_post();
		if ( $post instanceof WP_Post ) {
			$page_path = self::normalize_seed_path_for_language( self::get_page_path( $post ) );
			if ( 'case-studies' === $page_path ) {
				return self::get_case_studies_url( $target_lang );
			}
		}

		// On the front page, use seeded page URL directly (avoid translating wrong post).
		if ( is_front_page() ) {
			return self::get_seeded_page_url( '', $target_lang );
		}

		if ( $post && function_exists( 'pll_get_post' ) ) {
			$translated = pll_get_post( $post->ID, $target_lang );
			if ( $translated ) {
				return get_permalink( $translated );
			}
		}

		return self::get_seeded_page_url( '', $target_lang );
	}

	/**
	 * Get the case-studies landing URL for the requested language.
	 *
	 * @param string $lang Language slug.
	 * @return string
	 */
	protected static function get_case_studies_url( $lang ) {
		if ( 'zh' === $lang ) {
			return home_url( '/zh/case-studies/' );
		}

		return home_url( '/case-studies/' );
	}

	/**
	 * Normalize a page path to the canonical seeded path per language.
	 *
	 * @param string $page_path Raw hierarchical page path.
	 * @return string
	 */
	protected static function normalize_seed_path_for_language( $page_path ) {
		$page_path = trim( (string) $page_path, '/' );
		if ( '' === $page_path ) {
			return '';
		}

		if ( 0 === strpos( $page_path, 'zh/' ) ) {
			$page_path = substr( $page_path, 3 );
		}

		if ( preg_match( '#^(about-yke|contact|distributor-partnership|products|case-studies)(-\d+)(/.*)?$#', $page_path, $matches ) ) {
			$page_path = $matches[1] . ( isset( $matches[3] ) ? $matches[3] : '' );
		}

		return trim( $page_path, '/' );
	}

	/**
	 * Get the current request path without the leading slash.
	 *
	 * @return string
	 */
	protected static function get_request_path() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		if ( '' === $uri ) {
			return '';
		}

		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		if ( '' === $path ) {
			return '';
		}

		$base_path = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		if ( '' !== $base_path && '/' !== $base_path && 0 === strpos( $path, $base_path ) ) {
			$path = substr( $path, strlen( $base_path ) );
		}

		return trim( $path, '/' );
	}

	/**
	 * Check whether the current request is the Chinese helper case-studies archive.
	 *
	 * @return bool
	 */
	protected static function is_chinese_case_archive_request() {
		return 'zh/case-studies' === self::get_request_path();
	}

	/**
	 * Build the hierarchical page path.
	 *
	 * @param WP_Post $post Page post.
	 * @return string
	 */
	protected static function get_page_path( $post ) {
		$segments  = array();
		$ancestors = array_reverse( get_post_ancestors( $post ) );
		foreach ( $ancestors as $ancestor_id ) {
			$ancestor = get_post( $ancestor_id );
			if ( $ancestor instanceof WP_Post ) {
				$segments[] = $ancestor->post_name;
			}
		}
		$segments[] = $post->post_name;

		return trim( implode( '/', array_filter( $segments ) ), '/' );
	}

	/**
	 * Sanitize comma-separated email addresses.
	 *
	 * @param string $value Raw setting.
	 * @return string
	 */
	public static function sanitize_email_list( $value ) {
		$emails = preg_split( '/[\s,;]+/', (string) $value );
		$emails = array_filter( array_map( 'sanitize_email', (array) $emails ) );

		return implode( ', ', array_unique( $emails ) );
	}
}
