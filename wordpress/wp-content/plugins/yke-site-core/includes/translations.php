<?php
/**
 * YKE translation dictionary.
 *
 * Keys are logical string identifiers. Each key maps to an array of
 * locale => translation. When a locale is missing, t() falls back to English.
 *
 * To add a new language: simply add its slug (e.g. 'am') with translated
 * values to the keys below. No code changes needed elsewhere.
 *
 * @package YKE_Site_Core
 */

return array(

	// ===== Language names (for switcher display) =====
	'lang.name.en' => array( 'en' => 'English', 'zh' => 'English', 'am' => 'English' ),
	'lang.name.zh' => array( 'en' => '中文', 'zh' => '中文', 'am' => '中文' ),
	'lang.name.am' => array( 'en' => 'አማርኛ', 'zh' => 'አማርኛ', 'am' => 'አማርኛ' ),

	// ===== Navigation =====
	'nav.home'              => array( 'en' => 'Home', 'zh' => '首页' ),
	'nav.products'          => array( 'en' => 'Products', 'zh' => '产品' ),
	'nav.case_studies'      => array( 'en' => 'Case Studies', 'zh' => '项目案例' ),
	'nav.about_yke'         => array( 'en' => 'About YKE', 'zh' => '关于云科' ),
	'nav.contact'           => array( 'en' => 'Contact', 'zh' => '联系' ),
	'nav.breadcrumb_home'   => array( 'en' => 'Home', 'zh' => '首页' ),

	// ===== Product line names =====
	'product.mv_switchgear'             => array( 'en' => 'MV Switchgear', 'zh' => '高压开关柜' ),
	'product.lv_switchgear'             => array( 'en' => 'LV Switchgear', 'zh' => '低压开关柜' ),
	'product.transformers'              => array( 'en' => 'Transformers', 'zh' => '变压器' ),
	'product.prefabricated_substations' => array( 'en' => 'Prefabricated Substations', 'zh' => '预装式变电站' ),
	'product.new_energy_electrical'     => array( 'en' => 'New Energy Electrical', 'zh' => '新能源电气' ),

	// ===== Common buttons / labels =====
	'btn.view_details'      => array( 'en' => 'View Details', 'zh' => '查看详情' ),
	'btn.review_case'       => array( 'en' => 'Review case', 'zh' => '查看案例' ),
	'btn.view_case'         => array( 'en' => 'View Case', 'zh' => '查看案例' ),
	'btn.get_quote'         => array( 'en' => 'Get a Quote', 'zh' => '获取报价' ),
	'btn.send_inquiry'      => array( 'en' => 'Send Inquiry', 'zh' => '发送询盘' ),
	'btn.explore_line'      => array( 'en' => 'Explore line', 'zh' => '查看详情' ),
	'btn.browse_line'       => array( 'en' => 'Browse Line', 'zh' => '查看产品线' ),
	'btn.contact_yke'       => array( 'en' => 'Contact YKE', 'zh' => '联系云科' ),
	'btn.browse_products'   => array( 'en' => 'Browse Products', 'zh' => '浏览产品' ),
	'btn.start_inquiry'     => array( 'en' => 'Start Project Inquiry', 'zh' => '发起项目询盘' ),
	'btn.review_partnership'=> array( 'en' => 'Review Partnership Model', 'zh' => '查看合作模式' ),
	'btn.review_company'    => array( 'en' => 'Review Company Capability', 'zh' => '查看企业能力' ),

	// ===== Form labels =====
	'label.supply_scope'        => array( 'en' => 'Supply Scope', 'zh' => '供货范围' ),
	'label.delivery_result'     => array( 'en' => 'Delivery Result', 'zh' => '交付结果' ),
	'label.typical_applications'=> array( 'en' => 'Typical applications', 'zh' => '典型应用' ),
	'label.representative_models'=> array( 'en' => 'Representative models', 'zh' => '代表型号 / 方案' ),
	'label.customization_scope' => array( 'en' => 'Customization scope', 'zh' => '定制范围' ),
	'label.email'               => array( 'en' => 'Email', 'zh' => '邮箱' ),
	'label.phone'               => array( 'en' => 'Phone', 'zh' => '电话' ),
	'label.whatsapp'            => array( 'en' => 'WhatsApp', 'zh' => 'WhatsApp' ),
	'label.address'             => array( 'en' => 'Address', 'zh' => '地址' ),
	'label.business_hours'      => array( 'en' => 'Business Hours', 'zh' => '工作时间' ),
	'label.commitments'         => array( 'en' => 'Service Commitments', 'zh' => '服务承诺' ),
	'label.certifications'      => array( 'en' => 'Certifications', 'zh' => '认证' ),
	'label.tel_whatsapp'        => array( 'en' => 'Phone / WhatsApp', 'zh' => '电话 / WhatsApp' ),
	'label.models'              => array( 'en' => 'Models', 'zh' => '具体型号' ),

	// ===== Form fields =====
	'form.name'             => array( 'en' => 'Name', 'zh' => '姓名' ),
	'form.company'          => array( 'en' => 'Company', 'zh' => '公司' ),
	'form.country'          => array( 'en' => 'Country / Market', 'zh' => '国家 / 市场' ),
	'form.email'            => array( 'en' => 'Email', 'zh' => '邮箱' ),
	'form.whatsapp'         => array( 'en' => 'WhatsApp / Phone', 'zh' => 'WhatsApp / 电话' ),
	'form.product_interest' => array( 'en' => 'Product Interest', 'zh' => '产品兴趣' ),
	'form.requirement'      => array( 'en' => 'Project Requirement', 'zh' => '项目需求' ),
	'form.placeholder.name'         => array( 'en' => 'Your name', 'zh' => '您的姓名' ),
	'form.placeholder.company'      => array( 'en' => 'Company name', 'zh' => '公司名称' ),
	'form.placeholder.country'      => array( 'en' => 'e.g. USA, Germany, Brazil', 'zh' => '例如：沙特、越南、尼日利亚' ),
	'form.placeholder.email'        => array( 'en' => 'you@company.com', 'zh' => 'you@company.com' ),
	'form.placeholder.whatsapp'     => array( 'en' => '+1 234 567 8900', 'zh' => '+86 138 0000 0000' ),
	'form.placeholder.interest'     => array( 'en' => 'e.g. Dry-type transformer, Switchgear', 'zh' => '例如：干式变压器、高压开关柜' ),
	'form.placeholder.requirement'  => array( 'en' => 'Describe your project: voltage level, capacity, quantity, standards, timeline...', 'zh' => '请描述项目需求：电压等级、容量、数量、标准、交期等。' ),
	'form.button.submit'    => array( 'en' => 'Send Inquiry', 'zh' => '提交询盘' ),

	// ===== Inquiry notices =====
	'inquiry.success'       => array( 'en' => 'Your inquiry has been submitted successfully and saved.', 'zh' => '已提交成功，询盘已发送并保存到后台。' ),
	'inquiry.stored'        => array( 'en' => 'Your inquiry has been saved. Email delivery is pending confirmation.', 'zh' => '询盘已保存到后台，但邮件发送结果暂未确认，团队仍可在后台查看。' ),
	'inquiry.validation'    => array( 'en' => 'Please fill in all required fields with a valid email address.', 'zh' => '请完整填写必填项，并确认邮箱格式正确后再提交。' ),
	'inquiry.error'         => array( 'en' => 'Sorry, we could not process your inquiry. Please try again or use the contact details below.', 'zh' => '抱歉，当前未能处理这条询盘，请稍后重试或直接使用下方联系方式。' ),

	// ===== Section titles =====
	'section.product_lines' => array( 'en' => 'Product Lines at a Glance', 'zh' => '五大产品线' ),

	// ===== Misc =====
	'misc.case_syncing'     => array( 'en' => 'Case studies are being synchronized.', 'zh' => '案例内容同步中。' ),
	'misc.page'             => array( 'en' => 'Page', 'zh' => '页面' ),

	// ===== Home Hero =====
	'home.hero.aria_section'   => array( 'en' => 'YKE homepage hero', 'zh' => '云科首页主视觉' ),
	'home.hero.aria_signals'   => array( 'en' => 'Hero key signals', 'zh' => '首页关键信号' ),
	'home.hero.aria_prev'      => array( 'en' => 'Previous slide', 'zh' => '上一张轮播' ),
	'home.hero.aria_next'      => array( 'en' => 'Next slide', 'zh' => '下一张轮播' ),
	'home.hero.aria_nav'       => array( 'en' => 'Hero slide navigation', 'zh' => '首页轮播导航' ),

	// Hero slide 1
	'home.hero.slide1.eyebrow'       => array( 'en' => 'In-house Manufacturing Base', 'zh' => '自有生产基地' ),
	'home.hero.slide1.title'         => array( 'en' => 'In-house Guangzhou production base with full-process visibility', 'zh' => '自有广州生产基地，全流程可视化生产' ),
	'home.hero.slide1.sub'           => array( 'en' => 'YKE operates its own Guangzhou manufacturing base, covering everything from sheet metal processing to final assembly and testing. Equipped with CNC machinery and a temperature-controlled assembly workshop, every critical process is supervised by senior engineers to ensure consistency and reliability across every unit shipped.', 'zh' => '云科电气自有广州生产基地，从钣金加工到总装调试一站式完成。生产线配备数控机床与恒温装配车间，关键工序由资深工程师全程把控，保障每一台出厂设备的一致性与可靠性。' ),
	'home.hero.slide1.signal1'       => array( 'en' => 'CNC sheet metal line', 'zh' => '数控钣金加工线' ),
	'home.hero.slide1.signal2'       => array( 'en' => 'Temperature-controlled assembly', 'zh' => '恒温总装车间' ),
	'home.hero.slide1.signal3'       => array( 'en' => 'Senior engineer supervision', 'zh' => '资深工程师全程把控' ),
	'home.hero.slide1.primary_label' => array( 'en' => 'See Company Strength', 'zh' => '查看公司实力' ),
	'home.hero.slide1.secondary_label'=> array( 'en' => 'Contact YKE', 'zh' => '联系云科' ),

	// Hero slide 2
	'home.hero.slide2.eyebrow'       => array( 'en' => 'Project-ready Export Loading', 'zh' => '整柜出海交付' ),
	'home.hero.slide2.title'         => array( 'en' => 'Skid-mounted export loading, on time and on spec', 'zh' => '成套设备整柜出海，按时按规装运' ),
	'home.hero.slide2.sub'           => array( 'en' => 'From final assembly line to export container, YKE follows standardized loading procedures tailored to destination and certification requirements. Wooden crate fixing, moisture protection, dangerous goods declaration, and on-site photo archiving ensure the equipment arrives at overseas sites ready to install and energize.', 'zh' => '从设备下线到装柜出海，云科按海外项目目的地与认证要求执行标准化装柜与防护。木箱固定、防潮处理、危险品申报与现场拍照存档，确保货物抵达海外工地时完好可装可投运。' ),
	'home.hero.slide2.signal1'       => array( 'en' => 'Standardized loading', 'zh' => '标准化装柜作业' ),
	'home.hero.slide2.signal2'       => array( 'en' => 'Moisture & impact protection', 'zh' => '防潮防撞防护' ),
	'home.hero.slide2.signal3'       => array( 'en' => 'On-site photo archive', 'zh' => '现场拍照存档' ),
	'home.hero.slide2.primary_label' => array( 'en' => 'View Project Cases', 'zh' => '查看项目案例' ),
	'home.hero.slide2.secondary_label'=> array( 'en' => 'Send Inquiry', 'zh' => '发送需求' ),

	// ===== Home Why YKE =====
	'home.why_yke.eyebrow'   => array( 'en' => 'Company Intro + Advantages', 'zh' => '公司简介 + 公司优势' ),
	'home.why_yke.title'     => array( 'en' => 'Export-oriented power equipment manufacturer, delivering complete supply and project support', 'zh' => '出口型配电设备制造商，为海外项目提供成套供货与交付支持' ),
	'home.why_yke.lede'      => array( 'en' => 'Serving overseas EPC contractors, industrial end users, and regional channel partners - from product selection and documentation to inspection coordination and project delivery, all in one workflow.', 'zh' => '服务海外项目承包商、工业终端用户与区域渠道伙伴，从选型配套、资料准备、验货配合到项目交付，一站式响应。' ),
	'home.why_yke.quote'     => array( 'en' => 'Product scope, documentation, approval coordination, and delivery response - handled within a single supply chain.', 'zh' => '产品范围、文件准备、审批配合与交付响应，全部在同一条供应链里完成。' ),
	'home.why_yke.body'      => array( 'en' => 'YKE goes beyond single-device supply, offering overseas buyers an integrated solution across multiple product lines, English documentation, approval support, and cross-market delivery communication.', 'zh' => '云科不只提供单一设备，而是围绕海外买家的多产品线需求，提供英语资料、审批配合与跨区域交付沟通的整体解决方案。' ),
	'home.why_yke.button'    => array( 'en' => 'About YKE', 'zh' => '查看关于云科' ),
	'home.why_yke.img_alt'   => array( 'en' => 'YKE factory and production scene', 'zh' => '云科工厂与制造场景' ),
	'home.why_yke.adv1_title'=> array( 'en' => 'Export standards and documentation support', 'zh' => '出口标准与文件配套' ),
	'home.why_yke.adv1_body' => array( 'en' => 'Drawings, manuals, testing files, packing lists, nameplate information, and core commercial files can be prepared by project.', 'zh' => '可按项目准备图纸、说明书、测试记录、装箱单、铭牌信息和基础商务文件。' ),
	'home.why_yke.adv2_title'=> array( 'en' => 'Flexible component brands and wiring schemes', 'zh' => '元器件品牌与接线方案灵活' ),
	'home.why_yke.adv2_body' => array( 'en' => 'ABB, Schneider, Siemens, and similar component options can be matched with wiring and structure adjustments for destination-market habits.', 'zh' => '支持 ABB、施耐德、西门子等元器件配置，并按目的国习惯调整接线与结构方案。' ),
	'home.why_yke.adv3_title'=> array( 'en' => 'Adaptation for heat, dust, and humidity', 'zh' => '高温、防尘、高湿环境适配' ),
	'home.why_yke.adv3_body' => array( 'en' => 'Middle East, Africa, and coastal project conditions can be supported with targeted high-temperature, dust-protection, moisture-control, and outdoor protection configurations.', 'zh' => '针对中东、非洲及沿海项目，可围绕高温、防尘、防潮和户外防护等级做针对性配置。' ),
	'home.why_yke.adv4_title'=> array( 'en' => 'Third-party inspection and English files', 'zh' => '第三方验货与英文资料支持' ),
	'home.why_yke.adv4_body' => array( 'en' => 'SGS, BV, and Intertek inspection coordination can be paired with English document packs for procurement, technical review, and approval stages.', 'zh' => '支持 SGS / BV / Intertek 等验货配合，并以英文资料包对接海外采购、技术和审批流程。' ),
	'home.why_yke.adv5_title'=> array( 'en' => 'Overseas approval support', 'zh' => '海外认证与审批配合' ),
	'home.why_yke.adv5_body' => array( 'en' => 'Homepage products and project cards already surface SASO, SNI, and PLN signals for buyers reviewing Middle East and Southeast Asia opportunities.', 'zh' => '首页重点产品和项目已覆盖 SASO、SNI、PLN 等审批信号，便于中东和东南亚项目继续核验。' ),

	// ===== Home Quality =====
	'home.quality.eyebrow'   => array( 'en' => 'Certificates and Standards Compliance', 'zh' => '认证证书与国际标准符合' ),
	'home.quality.title'     => array( 'en' => 'CE, CCC, ISO systems, and IEC 61439 compliance', 'zh' => 'CE、CCC、ISO 三体系认证及 IEC 61439 标准符合' ),
	'home.quality.lede'      => array( 'en' => 'All product lines carry CE, CCC, and ISO three-system certifications. Low-voltage assemblies comply with IEC 61439. Every batch ships with complete third-party test reports.', 'zh' => '全线产品持有 CE、CCC 及 ISO 三体系认证，低压成套产品符合 IEC 61439 标准。每批次产品附完整第三方检测报告，随货交付。' ),
	'home.quality.cert_note' => array( 'en' => 'Certificate image to be supplied', 'zh' => '证书图待补' ),
	'home.quality.cert_desc_ce'     => array( 'en' => 'CE certificate image pending customer supply', 'zh' => 'CE 认证证书图片待客户补充' ),
	'home.quality.cert_desc_ccc'    => array( 'en' => 'CCC certificate image pending customer supply', 'zh' => 'CCC 认证证书图片待客户补充' ),
	'home.quality.cert_desc_iso9'   => array( 'en' => 'ISO 9001 certificate image pending customer supply', 'zh' => 'ISO 9001 认证证书图片待客户补充' ),
	'home.quality.cert_desc_iso14'  => array( 'en' => 'ISO 14001 certificate image pending customer supply', 'zh' => 'ISO 14001 认证证书图片待客户补充' ),
	'home.quality.cert_desc_iso45'  => array( 'en' => 'ISO 45001 certificate image pending customer supply', 'zh' => 'ISO 45001 认证证书图片待客户补充' ),
	'home.quality.iec_note'  => array( 'en' => 'Standards compliance slot', 'zh' => '标准符合展示位' ),
	'home.quality.iec_desc'  => array( 'en' => 'Homepage standards compliance slot for low-voltage assemblies', 'zh' => '低压成套产品标准符合展示位' ),

	// ===== Home Products Grid =====
	'home.products_grid.eyebrow' => array( 'en' => 'Hot Products / Five Product Lines', 'zh' => '热销产品 / 五大产品线' ),
	'home.products_grid.title'   => array( 'en' => 'Five product lines, matched to your project scenario', 'zh' => '五大产品线，按项目场景快速选型' ),
	'home.products_grid.lede'    => array( 'en' => 'From MV/LV switchgear to transformers, prefabricated substations, and new energy electrical. Hover a line to see its specific models, click a model to open its detail page.', 'zh' => '覆盖中低压开关柜、变压器、预装式变电站及新能源电气。悬停产品线卡查看具体型号，点击型号直达单品详情页。' ),

	// ===== Home Case Studies =====
	'home.case_studies.eyebrow'      => array( 'en' => 'Project Showcase', 'zh' => '实际项目展示' ),
	'home.case_studies.title'        => array( 'en' => 'Four overseas projects across the Middle East, Southeast Asia, and Africa', 'zh' => '四大海外项目，覆盖中东、东南亚、非洲市场' ),
	'home.case_studies.lede'         => array( 'en' => 'Saudi Arabia, Vietnam, Nigeria, Indonesia - each card shows supply scope and key delivery facts. Click through for full case details.', 'zh' => '沙特、越南、尼日利亚、印尼 - 每个项目标注供货范围与关键交付事实，点击查看详情。' ),
	'home.case_studies.placeholder_kick' => array( 'en' => 'Field photo to be supplied', 'zh' => '现场图待补' ),
	'home.case_studies.placeholder_body' => array( 'en' => 'Replace this slot directly when a real installation photo is available.', 'zh' => '客户补充现场安装照片后可直接替换此位置。' ),
	'home.case_studies.button'       => array( 'en' => 'View Case', 'zh' => '查看案例' ),

	// ===== Home Buyers Glance =====
	'home.buyers_glance.title'   => array( 'en' => 'Company Strength', 'zh' => '公司实力数据' ),
	'home.buyers_glance.stat1'   => array( 'en' => 'Year Founded', 'zh' => '成立时间' ),
	'home.buyers_glance.stat2'   => array( 'en' => 'Factory Area', 'zh' => '工厂面积' ),
	'home.buyers_glance.stat3'   => array( 'en' => 'Patents', 'zh' => '专利' ),
	'home.buyers_glance.stat4'   => array( 'en' => 'Equipment', 'zh' => '设备' ),

	// ===== Home Distributor =====
	'home.distributor.eyebrow' => array( 'en' => 'Service & Cooperation Paths', 'zh' => '服务与合作入口' ),
	'home.distributor.title'   => array( 'en' => 'Choose the next step by your buying goal', 'zh' => '按你的采购目标选择下一步' ),
	'home.distributor.lede'    => array( 'en' => 'Different visitors arrive with different priorities. Some are already matching a project, some are evaluating partnership options, and some are still screening suppliers at a high level.', 'zh' => '不同买家进入网站时关注点并不一样。有些已经在做项目采购，有些在评估代理合作，有些只是先确认供应商基础能力。' ),
	'home.distributor.card1_title' => array( 'en' => 'Project Procurement', 'zh' => '项目采购' ),
	'home.distributor.card1_body'  => array( 'en' => 'Best for buyers who already know the voltage class, application, and delivery window and want to move into product fit and supply feasibility.', 'zh' => '适合已经明确电压等级、应用场景和交期要求的项目询盘，优先进入产品匹配和供货可行性确认。' ),
	'home.distributor.card2_title' => array( 'en' => 'Distributor Partnership', 'zh' => '代理合作' ),
	'home.distributor.card2_body'  => array( 'en' => 'Best for channel partners with an existing local customer base who want to add MV, LV, transformer, or renewable electrical lines.', 'zh' => '适合已有本地客户或渠道基础，希望增加中压、低压、变压器或新能源产品线的合作伙伴。' ),
	'home.distributor.card3_title' => array( 'en' => 'Supplier Screening', 'zh' => '企业筛选' ),
	'home.distributor.card3_body'  => array( 'en' => 'If you are still comparing product range, quality control, project proof, and export support, start with a broader review first.', 'zh' => '如果你还在比较产品范围、质量控制、项目记录和出口配套，可以先查看产品、案例和关于云科页面。' ),

	// ===== Shared Inquiry CTA =====
	'shared.inquiry_cta.eyebrow' => array( 'en' => 'Next Step', 'zh' => '下一步' ),
	'shared.inquiry_cta.title'   => array( 'en' => 'Submit your project requirements for a tailored solution and quote', 'zh' => '提交项目需求，获取专属方案与报价' ),
	'shared.inquiry_cta.desc'    => array( 'en' => 'Share your target market, product line, voltage level, quantity, and delivery timeline - YKE will respond with a preliminary proposal within 24 hours.', 'zh' => '告诉我们您的目标市场、产品线、电压等级、数量与交期要求，云科将在 24 小时内回复初步方案。' ),
	'shared.inquiry_cta.btn1'    => array( 'en' => 'Contact YKE', 'zh' => '联系云科' ),
	'shared.inquiry_cta.btn2'    => array( 'en' => 'Browse Products', 'zh' => '浏览产品' ),

	// ===== Contact page =====
	'contact.company_zh'     => array( 'en' => 'Guangzhou Yunke Electrical Equipment Co., Ltd.', 'zh' => '广州云科电气设备有限公司' ),
	'contact.title'          => array( 'en' => 'Contact YKE', 'zh' => '联系云科' ),
	'contact.form_title'     => array( 'en' => 'Send Inquiry', 'zh' => '发送询盘' ),
	'contact.inquiry_tag'    => array( 'en' => 'General Inquiry', 'zh' => '通用询盘' ),

	// ===== Case study labels =====
	'case.label.snapshot'        => array( 'en' => 'Project Snapshot', 'zh' => '项目快照' ),
	'case.label.background'      => array( 'en' => 'Project Background & Requirements', 'zh' => '项目背景与需求' ),
	'case.label.supply'          => array( 'en' => 'Supply Scope & Key Parameters', 'zh' => '供货范围与关键参数' ),
	'case.label.technical'       => array( 'en' => 'Key Technology & Standards', 'zh' => '关键技术与标准' ),
	'case.label.result'          => array( 'en' => 'Delivery Result', 'zh' => '交付结果' ),
	'case.label.related_cases'   => array( 'en' => 'Related Cases', 'zh' => '相关案例' ),
	'case.label.related_lines'   => array( 'en' => 'Related Product Lines', 'zh' => '相关产品线' ),
	'case.label.region'          => array( 'en' => 'Region', 'zh' => '区域' ),
	'case.label.country'         => array( 'en' => 'Country', 'zh' => '国家' ),
	'case.label.client_type'     => array( 'en' => 'Client Type', 'zh' => '客户类型' ),
	'case.label.application'     => array( 'en' => 'Application', 'zh' => '应用行业' ),
	'case.label.project_capacity'=> array( 'en' => 'Project Capacity', 'zh' => '项目容量' ),
	'case.label.standards'       => array( 'en' => 'Standards', 'zh' => '标准' ),
	'case.sidebar.title'         => array( 'en' => 'Request a similar solution', 'zh' => '咨询类似方案' ),
	'case.sidebar.body'          => array( 'en' => 'Tell us your target market and requirements, YKE will respond with a feasible solution.', 'zh' => '告诉我们目标市场与需求，云科将回复可行方案。' ),
	'case.sidebar.inquiry_tag'   => array( 'en' => 'Case Study Inquiry', 'zh' => '案例相关询盘' ),

	// ===== Product detail labels =====
	'product_detail.label.overview'       => array( 'en' => 'Product Overview', 'zh' => '产品概述' ),
	'product_detail.label.applications'   => array( 'en' => 'Applications', 'zh' => '适用场景' ),
	'product_detail.label.models'         => array( 'en' => 'Representative Models', 'zh' => '代表型号' ),
	'product_detail.label.customization'  => array( 'en' => 'Customization', 'zh' => '定制能力' ),
	'product_detail.label.standards'      => array( 'en' => 'Certifications & Standards', 'zh' => '认证与标准' ),
	'product_detail.label.markets'        => array( 'en' => 'Target Market Fit', 'zh' => '目标市场适配' ),
	'product_detail.label.cases'          => array( 'en' => 'Related Cases', 'zh' => '相关案例' ),
	'product_detail.label.case_fallback'  => array( 'en' => 'More representative delivery cases can be provided during inquiry.', 'zh' => '更多代表性交付案例可在询盘沟通中提供。' ),

	// ===== Products overview =====
	'products.overview.eyebrow'       => array( 'en' => 'MV · LV · Transformers · Substations · New Energy', 'zh' => '高压 · 低压 · 变压器 · 箱变 · 新能源' ),
	'products.overview.title'         => array( 'en' => 'Five product lines covering 0.4kV to 35kV export projects', 'zh' => '五大产品线,覆盖 0.4kV 至 35kV 出口项目' ),
	'products.overview.key_point'     => array( 'en' => 'Key highlights:', 'zh' => '关键卖点：' ),

	// ===== Strength card =====
	'strength.eyebrow' => array( 'en' => 'Why YKE', 'zh' => '为什么选云科' ),
	'strength.title'   => array( 'en' => 'Four Core Delivery Capabilities', 'zh' => '四项核心交付能力' ),

	// ===== Hero kickers for pages =====
	'hero.fallback_kicker' => array( 'en' => 'Page', 'zh' => '页面' ),

);
