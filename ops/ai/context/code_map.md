## [Scan 2025-10-12]
Top-level file counts (≤2 levels):
     20 ./wordpress
      1 ./WEEK-1-PROGRESS.md
      1 ./SSAW-App-Details.md
      1 ./SHOPIFY-TO-WORDPRESS-MIGRATION-PLAN.md
      1 ./setup-progress.md
      1 ./README.md
      1 ./NEXT-STEPS.md
      1 ./HEADER-FOOTER-GUIDE.md
      1 ./.gitignore
      1 ./Dockerfile
      1 ./docker-compose.yml
      1 ./CLAUDE.md
      1 ./.claude
      1 ./CHANGELOG.md

Theme templates in kadence-child:
wordpress/wp-content/themes/kadence-child/functions.php

CPT/Taxonomy/Shortcode registrations (grep summary):
wordpress/wp-content/plugins/woocommerce/includes/class-wc-install.php:658:		WC_Post_types::register_post_types();
wordpress/wp-content/plugins/woocommerce/includes/import/abstract-wc-product-importer.php:697:		register_taxonomy(
wordpress/wp-content/plugins/woocommerce/includes/admin/class-wc-admin-importers.php:211:								register_taxonomy(
wordpress/wp-content/plugins/woocommerce/includes/wc-update-functions.php:1559:	register_post_type( 'shop_webhook' );
wordpress/wp-content/plugins/woocommerce/includes/wc-update-functions.php:1591:	unregister_post_type( 'shop_webhook' );
wordpress/wp-content/plugins/woocommerce/includes/class-wc-brands.php:38:		add_action( 'woocommerce_register_taxonomy', array( __CLASS__, 'init_taxonomy' ) );
wordpress/wp-content/plugins/woocommerce/includes/class-wc-brands.php:250:		register_taxonomy(
wordpress/wp-content/plugins/woocommerce/includes/class-wc-brands.php:261:				'register_taxonomy_product_brand',
wordpress/wp-content/plugins/woocommerce/includes/class-wc-brands.php:447:		add_shortcode( 'product_brand', array( $this, 'output_product_brand' ) );
wordpress/wp-content/plugins/woocommerce/includes/class-wc-brands.php:448:		add_shortcode( 'product_brand_thumbnails', array( $this, 'output_product_brand_thumbnails' ) );
wordpress/wp-content/plugins/woocommerce/includes/class-wc-brands.php:449:		add_shortcode( 'product_brand_thumbnails_description', array( $this, 'output_product_brand_thumbnails_description' ) );
wordpress/wp-content/plugins/woocommerce/includes/class-wc-brands.php:450:		add_shortcode( 'product_brand_list', array( $this, 'output_product_brand_list' ) );
wordpress/wp-content/plugins/woocommerce/includes/class-wc-brands.php:451:		add_shortcode( 'brand_products', array( $this, 'output_brand_products' ) );
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:27:		add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:32:		add_action( 'woocommerce_after_register_post_type', array( __CLASS__, 'maybe_flush_rewrite_rules' ) );
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:51:		do_action( 'woocommerce_register_taxonomy' );
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:55:		register_taxonomy(
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:72:		register_taxonomy(
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:89:		register_taxonomy(
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:133:		register_taxonomy(
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:178:		register_taxonomy(
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:281:					register_taxonomy( $name, apply_filters( "woocommerce_taxonomy_objects_{$name}", array( 'product' ) ), apply_filters( "woocommerce_taxonomy_args_{$name}", $taxonomy_data ) );
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:286:		do_action( 'woocommerce_after_register_taxonomy' );
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:292:	public static function register_post_types() {
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:297:		do_action( 'woocommerce_register_post_type' );
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:320:		register_post_type(
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:323:				'woocommerce_register_post_type_product',
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:378:			register_post_type(
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:387:					'woocommerce_register_post_type_product_form',
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:443:		register_post_type(
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:446:				'woocommerce_register_post_type_product_variation',
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:461:				'woocommerce_register_post_type_shop_order',
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:502:				'woocommerce_register_post_type_shop_order_refund',
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:521:			register_post_type(
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:524:					'woocommerce_register_post_type_shop_coupon',
wordpress/wp-content/plugins/woocommerce/includes/class-wc-post-types.php:563:		do_action( 'woocommerce_after_register_post_type' );
wordpress/wp-content/plugins/woocommerce/includes/wc-product-functions.php:69:	if ( ! did_action( 'woocommerce_init' ) || ! did_action( 'woocommerce_after_register_taxonomy' ) || ! did_action( 'woocommerce_after_register_post_type' ) ) {
wordpress/wp-content/plugins/woocommerce/includes/wc-product-functions.php:70:		/* translators: 1: wc_get_product 2: woocommerce_init 3: woocommerce_after_register_taxonomy 4: woocommerce_after_register_post_type */
wordpress/wp-content/plugins/woocommerce/includes/wc-product-functions.php:71:		wc_doing_it_wrong( __FUNCTION__, sprintf( __( '%1$s should not be called before the %2$s, %3$s and %4$s actions have finished.', 'woocommerce' ), 'wc_get_product', 'woocommerce_init', 'woocommerce_after_register_taxonomy', 'woocommerce_after_register_post_type' ), '3.9' );
wordpress/wp-content/plugins/woocommerce/includes/shipping/legacy-flat-rate/class-wc-shipping-legacy-flat-rate.php:129:		add_shortcode( 'fee', array( $this, 'fee' ) );
wordpress/wp-content/plugins/woocommerce/includes/shipping/flat-rate/class-wc-shipping-flat-rate.php:90:		add_shortcode( 'fee', array( $this, 'fee' ) );
wordpress/wp-content/plugins/woocommerce/includes/wc-order-functions.php:89:	if ( ! did_action( 'woocommerce_after_register_post_type' ) ) {
wordpress/wp-content/plugins/woocommerce/includes/wc-order-functions.php:90:		wc_doing_it_wrong( __FUNCTION__, 'wc_get_order should not be called before post types are registered (woocommerce_after_register_post_type action)', '2.5' );
wordpress/wp-content/plugins/woocommerce/includes/wc-order-functions.php:327: * $args are passed to register_post_type, but there are a few specific to this function:
wordpress/wp-content/plugins/woocommerce/includes/wc-order-functions.php:336: * @see    register_post_type for $args used in that function
wordpress/wp-content/plugins/woocommerce/includes/wc-order-functions.php:353:	if ( is_wp_error( register_post_type( $type, $args ) ) ) {
wordpress/wp-content/plugins/woocommerce/includes/wc-term-functions.php:652:		wc_doing_it_wrong( __FUNCTION__, 'wc_get_product_visibility_term_ids should not be called before taxonomies are registered (woocommerce_after_register_post_type action).', '3.1' );
wordpress/wp-content/plugins/woocommerce/includes/wc-attribute-functions.php:288: * https://codex.wordpress.org/Function_Reference/register_taxonomy#Reserved_Terms.
wordpress/wp-content/plugins/woocommerce/includes/class-wc-shortcodes.php:46:			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
wordpress/wp-content/plugins/woocommerce/includes/class-wc-shortcodes.php:50:		add_shortcode( 'woocommerce_messages', __CLASS__ . '::shop_messages' );
wordpress/wp-content/plugins/woocommerce/packages/action-scheduler/classes/data-stores/ActionScheduler_wpPostStore_PostTypeRegistrar.php:13:		register_post_type( ActionScheduler_wpPostStore::POST_TYPE, $this->post_type_args() );
wordpress/wp-content/plugins/woocommerce/packages/action-scheduler/classes/data-stores/ActionScheduler_wpPostStore_TaxonomyRegistrar.php:14:		register_taxonomy( ActionScheduler_wpPostStore::GROUP_TAXONOMY, ActionScheduler_wpPostStore::POST_TYPE, $this->taxonomy_args() );
wordpress/wp-content/plugins/woocommerce/packages/email-editor/src/Engine/Templates/class-templates.php:62:		$this->register_post_types_to_api();
wordpress/wp-content/plugins/woocommerce/packages/email-editor/src/Engine/Templates/class-templates.php:107:	public function register_post_types_to_api(): void {
wordpress/wp-content/plugins/woocommerce/packages/email-editor/src/Engine/class-email-editor.php:22: * See register_post_type for details about EmailPostType args.
wordpress/wp-content/plugins/woocommerce/packages/email-editor/src/Engine/class-email-editor.php:161:			register_post_type(
wordpress/wp-content/plugins/woocommerce/src/Internal/DataStores/Orders/CustomOrdersTableController.php:145:		add_action( 'woocommerce_after_register_post_type', array( $this, 'register_post_type_for_order_placeholders' ), 10, 0 );
wordpress/wp-content/plugins/woocommerce/src/Internal/DataStores/Orders/CustomOrdersTableController.php:536:	 * Handler for the woocommerce_after_register_post_type post,
wordpress/wp-content/plugins/woocommerce/src/Internal/DataStores/Orders/CustomOrdersTableController.php:543:	public function register_post_type_for_order_placeholders(): void {
wordpress/wp-content/plugins/woocommerce/src/Internal/Admin/Coupons.php:54:		add_action( 'woocommerce_register_post_type_shop_coupon', array( $this, 'move_coupons' ) );
wordpress/wp-content/plugins/woocommerce/src/Internal/Admin/Notes/OrderMilestones.php:84:		add_action( 'woocommerce_after_register_post_type', array( $this, 'init' ) );
wordpress/wp-content/plugins/woocommerce/src/Internal/Integrations/WPPostsImporter.php:67:				register_taxonomy(
wordpress/wp-content/plugins/woocommerce/src/Admin/Features/ProductBlockEditor/Init.php:72:			add_filter( 'woocommerce_register_post_type_product_variation', array( $this, 'enable_rest_api_for_product_variation' ) );
wordpress/wp-content/plugins/woocommerce/i18n/languages/woocommerce.pot:32528:#. translators: 1: wc_get_product 2: woocommerce_init 3: woocommerce_after_register_taxonomy 4: woocommerce_after_register_post_type
wordpress/wp-content/plugins/ajax-search-for-woocommerce/includes/Shortcode.php:11:        add_shortcode( 'wcas-search-form', array(__CLASS__, 'addBody') );
wordpress/wp-content/plugins/ajax-search-for-woocommerce/includes/Shortcode.php:12:        add_shortcode( 'fibosearch', array(__CLASS__, 'addBody') );
wordpress/wp-content/plugins/ajax-search-for-woocommerce/partials/themes/flatsome.php:15:	add_shortcode( 'search', array( 'DgoraWcas\\Shortcode', 'addBody' ) );
wordpress/wp-content/plugins/ajax-search-for-woocommerce/partials/themes/total.php:17:	add_shortcode( 'header_search_icon', function () {
wordpress/wp-content/plugins/ajax-search-for-woocommerce/partials/themes/total.php:20:	add_shortcode( 'searchform', function () {
wordpress/wp-content/plugins/ajax-search-for-woocommerce/partials/themes/thegem-elementor.php:85:		add_shortcode( 'thegem_te_search_form', function ( $atts, $content, $shortcodeTag ) {
wordpress/wp-content/plugins/ajax-search-for-woocommerce/partials/themes/thegem-elementor.php:94:		add_shortcode( 'thegem_te_search', function ( $atts, $content, $shortcodeTag ) {
wordpress/wp-content/plugins/kadence-blocks/includes/navigation/class-kadence-navigation-cpt.php:29:		add_action( 'init', array( $this, 'register_post_type' ), 2 );
wordpress/wp-content/plugins/kadence-blocks/includes/navigation/class-kadence-navigation-cpt.php:157:	public function register_post_type() {
wordpress/wp-content/plugins/kadence-blocks/includes/navigation/class-kadence-navigation-cpt.php:166:		register_post_type(
wordpress/wp-content/plugins/kadence-blocks/includes/advanced-form/advanced-form-cpt.php:28:		add_action( 'init', array( $this, 'register_post_type' ), 2 );
wordpress/wp-content/plugins/kadence-blocks/includes/advanced-form/advanced-form-cpt.php:128:	public function register_post_type() {
wordpress/wp-content/plugins/kadence-blocks/includes/advanced-form/advanced-form-cpt.php:177:		register_post_type( self::SLUG, $args );
wordpress/wp-content/plugins/kadence-blocks/includes/init.php:556:	register_post_type(
wordpress/wp-content/plugins/kadence-blocks/includes/init.php:591:	register_post_type(
wordpress/wp-content/plugins/kadence-blocks/includes/header/class-kadence-header-cpt.php:38:		add_action( 'init', array( $this, 'register_post_type' ), 2 );
wordpress/wp-content/plugins/kadence-blocks/includes/header/class-kadence-header-cpt.php:194:	public function register_post_type() {
wordpress/wp-content/plugins/kadence-blocks/includes/header/class-kadence-header-cpt.php:196:		register_post_type(
wordpress/wp-content/plugins/wordpress-seo/src/integrations/breadcrumbs-integration.php:64:		\add_shortcode( 'wpseo_breadcrumb', [ $this, 'render' ] );
wordpress/wp-content/plugins/wordpress-seo/src/integrations/front-end/category-term-description.php:30:		\add_filter( 'category_description', [ $this, 'add_shortcode_support' ] );
wordpress/wp-content/plugins/wordpress-seo/src/integrations/front-end/category-term-description.php:31:		\add_filter( 'term_description', [ $this, 'add_shortcode_support' ] );
wordpress/wp-content/plugins/wordpress-seo/src/integrations/front-end/category-term-description.php:44:	public function add_shortcode_support( $description ) {
wordpress/wp-content/plugins/newsletter/subscription/subscription.php:41:            add_shortcode('newsletter_form', [$this, 'shortcode_newsletter_form']);
wordpress/wp-content/plugins/newsletter/subscription/subscription.php:42:            add_shortcode('newsletter_field', [$this, 'shortcode_newsletter_field']);
wordpress/wp-content/plugins/newsletter/includes/composer.php:296:            add_shortcode('gallery', 'tnp_gallery_shortcode');
wordpress/wp-content/plugins/newsletter/profile/profile.php:21:        add_shortcode('newsletter_profile', [$this, 'shortcode_newsletter_profile']);
wordpress/wp-content/plugins/newsletter/profile/profile.php:22:        add_shortcode('newsletter_profile_field', [$this, 'shortcode_newsletter_profile_field']);
wordpress/wp-content/plugins/newsletter/profile/profile.php:29:            add_shortcode('newsletter_export_button', [$this, 'shortcode_newsletter_export_button']);
wordpress/wp-content/plugins/newsletter/profile/profile.php:30:            add_shortcode('newsletter_profile_button', [$this, 'shortcode_newsletter_profile_button']);
wordpress/wp-content/plugins/newsletter/plugin.php:239:            add_shortcode('newsletter', array($this, 'shortcode_newsletter'));
wordpress/wp-content/plugins/newsletter/plugin.php:240:            add_shortcode('newsletter_replace', [$this, 'shortcode_newsletter_replace']);
wordpress/wp-content/plugins/newsletter/unsubscription/unsubscription.php:30:            add_shortcode('newsletter_unsubscribe_button', [$this, 'shortcode_newsletter_unsubscribe_button']);
wordpress/wp-content/plugins/newsletter/unsubscription/unsubscription.php:31:            add_shortcode('newsletter_resubscribe_button', [$this, 'shortcode_newsletter_resubscribe_button']);
wordpress/wp-content/plugins/redirection/models/url/url-transform.php:45:			add_shortcode( $code, [ $this, 'do_shortcode' ] );
wordpress/wp-content/plugins/wordfence/modules/login-security/classes/controller/wordfencels.php:93:			add_shortcode(self::SHORTCODE_2FA_MANAGEMENT, array($this, '_handle_user_2fa_management_shortcode'));
wordpress/wp-content/plugins/woocommerce-square/includes/Sync/Product_Import.php:1073:						register_taxonomy(
wordpress/wp-content/plugins/woocommerce-square/includes/Handlers/Product.php:532:		register_taxonomy(
wordpress/wp-content/plugins/woocommerce-square/includes/Plugin.php:139:		add_action( 'woocommerce_register_taxonomy', array( $this, 'init_taxonomies' ) );
wordpress/wp-content/plugins/tawkto-live-chat/tawkto.php:603:			add_shortcode( 'tawkto', array( $this, 'shortcode_print_embed_code' ) );
wordpress/wp-content/plugins/code-snippets/php/export/class-export.php:103:					"add_shortcode( '%s', function () {\n\tob_start();\n\t?>\n\n\t%s\n\n\t<?php\n\treturn ob_get_clean();\n} );",
wordpress/wp-content/plugins/code-snippets/php/front-end/class-front-end.php:38:		add_shortcode( self::CONTENT_SHORTCODE, [ $this, 'render_content_shortcode' ] );
wordpress/wp-content/plugins/code-snippets/php/front-end/class-front-end.php:39:		add_shortcode( self::SOURCE_SHORTCODE, [ $this, 'render_source_shortcode' ] );
wordpress/wp-content/plugins/code-snippets/php/front-end/class-front-end.php:329:			add_shortcode( self::CONTENT_SHORTCODE, [ $this, 'render_content_shortcode' ] );
wordpress/wp-content/plugins/limit-login-attempts-reloaded/core/Shortcodes.php:14:		add_shortcode( 'llar-link', array( $this, 'llar_link_callback' ) );
wordpress/wp-content/plugins/wp-google-maps/includes/class.shortcodes.php:44:		add_shortcode(self::SLUG, array($this, "map"));
wordpress/wp-content/plugins/wp-google-maps/includes/class.shortcodes.php:45:		add_shortcode(self::SLUG . "_" . self::STORE_LOCATOR, array($this, "storeLocator"));
wordpress/wp-content/plugins/wp-google-maps/includes/class.shortcodes.php:560:		    add_shortcode( 'wpgmza', 'wpgmaps_tag_pro' );
wordpress/wp-content/plugins/wp-google-maps/includes/class.shortcodes.php:562:		    add_shortcode( 'wpgmza', 'wpgmaps_tag_basic' );
wordpress/wp-content/plugins/wp-google-maps/legacy-core.php:1279:    add_shortcode( 'wpgmza', 'wpgmaps_tag_pro' );
wordpress/wp-content/plugins/wp-google-maps/legacy-core.php:1281:    add_shortcode( 'wpgmza', 'wpgmaps_tag_basic' );
wordpress/wp-content/themes/kadence/inc/meta/class-theme-meta.php:73:		add_filter( 'register_post_type_args', array( $this, 'add_needed_custom_fields_support' ), 20, 2 );

No ACF JSON directory found

WooCommerce detected: plan product/catalog/cart/checkout URL parity & schema.
