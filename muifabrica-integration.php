<?php
/**
 * Plugin Name:          Integração com Muifabrica
 * Description:          Integração com Muifabrica for WooCommerce.
 * Author:               Mateus Costa
 * Author URI:           https://costamateus.com.br/
 * Version:              1.0.0
 * Text Domain:          muifabrica_integration
 * Requires at least:    6.4
 * Requires PHP:         7.4
 * WC requires at least: 8.2
 * WC tested up to:      9.0
 *
 * @package 			 Muifabrica_Integration
 * @version 			 1.0.0
 */

defined( "ABSPATH" ) || exit;

add_action( "plugins_loaded", [ "Muifabrica_Integration", "init" ] );

class Muifabrica_Integration
{
	/**
	 * Plugin version.
	 *
	 * @since 	1.0.0
	 * @var 	string
	 */
	const VERSION = "1.0.0";

	/**
	 * Plugin domain.
	 *
	 * @since 	1.0.0
	 * @var 	string
	 */
	const DOMAIN  = "muifabrica_integration";

	/**
	 * Plugin main file.
	 *
	 * @since 	1.0.0
	 * @var 	string
	 */
	const FILE    = __FILE__;

	/**
	 * Plugin dir.
	 *
	 * @since 	1.0.0
	 * @var 	string
	 */
	const DIR     = __DIR__;

	/**
	 * Instance of this class.
	 *
	 * @since 	1.0.0
	 * @var 	object
	 */
	protected static $instance = null;

	/**
	 * Construct
	 *
	 * @since 	1.0.0
	 */
	private function __construct()
    {
        $has_missing  = false;
        $dependencies = [ "woo" => "woocommerce/woocommerce.php", ];

        foreach ( $dependencies as $key => $dep )
        {
            if ( !in_array( $dep, apply_filters( "active_plugins", get_option( "active_plugins" ) ) ) )
            {
                add_action( "admin_notices", [ $this, "missing_notice_{$key}" ] );
                $has_missing = true;
            }
        }

        if ( $has_missing )
			return;

		if ( class_exists( "WooCommerce" ) )
			add_action( "before_woocommerce_init", [ $this, "setup_hpos_compatibility" ] );

		$this->add_requires();
        $this->add_actions();
        $this->add_filters();
		$this->add_classes();
    }

	/**
	 * Log
	 *
	 * @since 	1.0.0
	 * @param 	string 	$level
	 * @param 	string 	$message
	 * @param 	bool 	$is_error
	 * @return 	void
	 */
	public static function log( string $level, string $message, bool $is_error = false ) : void
	{
		$logger  = wc_get_logger();
		$context = [ "source" => self::DOMAIN ];

		if ( $is_error || get_option( "_mfi_debug" ) )
			$logger->$level( $message, $context );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 	1.0.0
	 * @return 	object A single instance of this class.
	 */
	public static function init() : object
    {
		if ( self::$instance === null ) self::$instance = new self;

		return self::$instance;
	}

	/**
	 * Woo missing notice.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public static function missing_notice_woo() : void
	{
		require_once( self::DIR . "/templates/notices/missing-woo.php" );
	}

	/**
	 * Setup WooCommerce HPOS compatibility.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function setup_hpos_compatibility() : void
	{
		if ( defined( "WC_VERSION" ) && version_compare( WC_VERSION, "7.1", "<" ) )
			return;

		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) )
		{
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				"custom_order_tables",
				__FILE__,
				true
			);
		}
	}

	/**
	 * Init plugin dependency classes.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function add_requires() : void
	{
		if ( !class_exists( "WP_List_Table" ) )
			require_once( ABSPATH . "wp-admin/includes/class-wp-list-table.php" );

		add_action( "admin_footer", function () {
			require_once( self::DIR . "/templates/partials/load.php" );
		} );

		// TODO uso de REST API
		// require_once( self::DIR . "/includes/rest-api/Utilities/SingletonTrait.php" );
		// require_once( self::DIR . "/includes/rest-api/Server.php"                   );

		require_once( self::DIR . "/includes/api/class-muifabrica-api.php" );

		// Shipping classes
		require_once( self::DIR . "/includes/classes/shipping/class-mfi-air-shipping.php" );
		require_once( self::DIR . "/includes/classes/shipping/class-mfi-sea-shipping.php" );
	}

	/**
	 * Init plugin actions.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
    public function add_actions() : void
    {
		// TODO rever inicialização da REST API
		// add_action( "init",                        [ $this, "load_rest_api"            ]     );

        add_action( "init",                         [ $this, "add_custom_shipping_class" ]     );

		add_action( "admin_menu",                   [ $this, "add_admin_menu"            ], 9  );
        add_action( "admin_enqueue_scripts",        [ $this, "add_admin_style_script"    ]     );

		add_action( "admin_post_mfi_save_settings", [ $this, "save_settings_tab"         ]     );
    }

	/**
	 * Init plugin filters.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
    public function add_filters() : void
    {
		add_filter( "plugin_action_links_" . plugin_basename( self::FILE ), [ $this, "plugin_action_links"  ]        );
		add_filter( "http_request_args",                                    [ $this, "accept_unsafe_urls"   ], 90, 2 );

		add_filter( "woocommerce_shipping_methods",                         [ $this, "add_shippings_method" ]        );
	}

	/**
	 * Init plugin needed classes.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function add_classes() : void
	{
		require_once( self::DIR . "/includes/classes/helpers/class-mfi-helper.php"        );

		// Preview, not imported products
		require_once( self::DIR . "/includes/classes/tables/class-mfi-preview-table.php"  );
		require_once( self::DIR . "/includes/classes/class-mfi-preview.php"               );
		new Muifabrica_Integration\Classes\MFI_Preview();

		// Products, already imported
		require_once( self::DIR . "/includes/classes/tables/class-mfi-products-table.php" );

		// Orders
		// require_once( self::DIR . "/includes/classes/tables/class-woo-mfi-orders-table.php"   );
		require_once( self::DIR . "/includes/classes/class-mfi-orders.php"                );
		new Muifabrica_Integration\Classes\MFI_Orders();
	}

	/**
	 * Load REST API.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function load_rest_api() : void
	{
		// \Muifabrica_Integration\RestApi\Server::instance()->init();
	}

	/**
	 * Create the shipping class.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
    public static function add_custom_shipping_class() : void
    {
		$sc_name = "Entrega Muifabrica";

		if ( !term_exists( $sc_name, "product_shipping_class" ) )
		{
			$sc_slug  = "muifabrica-shipping-class";
			$sc_descr = "Classe de entrega Muifabrica";

			wp_insert_term(
				$sc_name,
				"product_shipping_class",
				[
					"slug"        => $sc_slug,
					"description" => $sc_descr,
				]
			);
		}
    }

	/**
	 * Include Muifabrica shippings to WooCommerce.
	 *
     * @since   1.0.0
	 * @param  	array $methods Default shipping methods.
	 * @return 	array
	 */
	public static function add_shippings_method( $methods )
	{
        $methods[ "muifabrica-air-shipping" ] = "Muifabrica_Integration\Classes\Shipping\MFI_Air_Shipping";
        $methods[ "muifabrica-sea-shipping" ] = "Muifabrica_Integration\Classes\Shipping\MFI_Sea_Shipping";

		return $methods;
	}

    /**
     * Add admin style.
     *
	 * @since 	1.0.0
	 * @return 	void
     */
    public function add_admin_style_script() : void
    {
		$suffix   = ( defined( "SCRIPT_DEBUG" ) && SCRIPT_DEBUG ) ? "" : ".min";

		$css_path = "assets/css/style{$suffix}.css";
		$css_ver  = date( "ymdHis", filemtime( plugin_dir_path( self::FILE ) . $css_path ) );
		wp_enqueue_style( "mfi_css_js", plugins_url( $css_path, self::FILE ), false, $css_ver, "all" );

		$js_path  = "assets/js/script{$suffix}.js";
		$js_ver   = date( "ymdHis", filemtime( plugin_dir_path( self::FILE ) . $js_path ) );
		wp_enqueue_script( "mfi_css_js", plugins_url( $js_path, self::FILE ), [ "json2", "jquery" ], $js_ver, true );

		wp_localize_script(
			"mfi_css_js",
			"mfi_params",
			[
				"ajax_url" => admin_url( "admin-ajax.php" ),
				"messages" => [
					"select_preview" => __( "Selecione pelo menos um produto para importar.", "muifabrica_integration" ),
				]
			]
		);
    }

	/**
	 * Action links.
	 *
	 * @since 	1.0.0
	 * @param 	array 	$links
	 * @return 	array
	 */
	public static function plugin_action_links( $links ) : array
    {
        $url            = esc_url( admin_url( "admin.php?page=muifabrica_integration" ) );
        $text           = __( "Configurações", "muifabrica_integration" );
		$plugin_links   = [];
		$plugin_links[] = "<a href='{$url}' >{$text}</a>";

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Add submenu on WP Settings menu
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function add_admin_menu() : void
	{
		add_menu_page(
			"Integração Muifabrica",
			"Integ. Muifabrica",
			"manage_options",
			self::DOMAIN,
			[ $this, "display_admin_menu" ],
			plugins_url( "assets/images/mfi-menu.png", self::FILE ),
            55.1
		);
	}

	/**
	 * Add settings page.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function display_admin_menu() : void
	{
		if ( !current_user_can( "manage_options" ) ) wp_die( __( "Você não tem permissões suficientes para acessar essa página." ) );

		$tabs = [
			"settings" => __( "Configurações",           "muifabrica_integration" ),
			"preview"  => __( "Produtos não importados", "muifabrica_integration" ),
			"products" => __( "Produtos importados",     "muifabrica_integration" ),
			"orders"   => __( "Pedidos",                 "muifabrica_integration" ),
		];

		$tab  = isset( $_GET[ "tab" ] ) ? $_GET[ "tab" ] : "settings";

		if ( $tab == "settings" )
		{
			$date      = date( "Y-m-d" );
			$esc_attr  = esc_attr( self::DOMAIN );
			$sanitize  = sanitize_file_name( wp_hash( self::DOMAIN ) );
			$admin_url = "admin.php?page=wc-status&tab=logs&log_file=$esc_attr-$date-$sanitize.log";
			$link      = esc_url( admin_url( $admin_url ) );
			$text      = "WooCommerce &gt Status &gt; Logs";
			$debug     = "<a href='{$link}' target='_blank' >{$text}</a>";
		}

		if ( isset( $_GET[ "error_message" ] ) || isset( $_GET[ "warning_message" ] ) )
		{
			add_action( "admin_notices", [ $this, "admin_settings_messages" ], 10, 2 );
			do_action( "admin_notices", "error", $_GET[ "data" ] );
		}

		if ( isset( $_GET[ "success_message" ] ) )
		{
			add_action( "admin_notices", [ $this, "admin_settings_messages" ], 10, 2 );
			do_action( "admin_notices", "success", $_GET[ "data" ] );
		}

		require_once( self::DIR . "/templates/admin/settings.php" );
	}

	/**
	 * Handles errors and displays messages accordingly.
	 *
	 * @since 	1.0.0
	 * @param 	string 	$type
	 * @param 	array 	$data
	 * @return 	void
	 */
	public function admin_settings_messages( $type, $data ) : void
	{
		if ( isset( $data[ "erros" ] ) && $data[ "erros" ] )
		{
			switch ( $data[ "code" ] )
			{
				default:
					$message = __( "Erro interno!<br>Verifique os logs para maiores detalhes.", "muifabrica_integration" );
				break;
			}

			echo "<div class='notice notice-errror is-dismissible'><p>{$message}</p></div>";
		}
		elseif ( isset( $data[ "warnings" ] ) && $data[ "warnings" ] > 0 )
		{
			$message = sprintf(
				__( "Não foi possível importar %1\$s produto(s)!<br>Verifique os logs para maiores detalhes.", "muifabrica_integration" ),
				$data[ "warnings" ],
			);

			echo "<div class='notice notice-warning is-dismissible'><p>{$message}</p></div>";

			if ( $data[ "count" ] != 0 )
			{
				$message = sprintf(
					__( "%1\$s produto(s)! importados com sucesso!", "muifabrica_integration" ),
					$data[ "count" ],
				);

				echo "<div class='notice notice-success is-dismissible'><p>{$message}</p></div>";
			}
		}
		else
		{
			$count   = $data[ "count" ];
			$message = __( "{$count} produtos importados com sucesso!", "muifabrica_integration" );

			echo "<div class='notice notice-success is-dismissible'><p>{$message}</p></div>";
		}
	}

	/**
	 * Save settings tab settings.
	 *
	 * Handles the saving of settings for the settings tab in the WordPress admin.
	 *
	 * @since   1.0.0
	 * @return  void
	 */
	public static function save_settings_tab() : void
	{
        if ( isset( $_POST[ "_wpnonce" ] ) && !empty( $_POST[ "_wpnonce" ] ) )
        {
            $nonce = htmlspecialchars( $_POST[ "_wpnonce" ] );

            if ( !wp_verify_nonce( $nonce, $_POST[ "action" ] ) )
				wp_die( "Nope! Security check failed!" );
        }

		// env
		$env   = ( isset( $_POST[ "mfi_env_api" ] ) && $_POST[ "mfi_env_api" ] === "on" ) ? "hml" : "prd";
		// debug
		$debug = ( isset( $_POST[ "mfi_debug"   ] ) && $_POST[ "mfi_debug"   ] === "on" ) ? true  : false;

		update_option( "_mfi_env_api", $env   );
		update_option( "_mfi_debug",   $debug );

		// app id hml
		if ( isset( $_POST[ "mfi_app_id_hml" ] ) && !empty( $_POST[ "mfi_app_id_hml" ] ) )
			update_option( "_mfi_app_id_hml", $_POST[ "mfi_app_id_hml" ] );

		// app secret hml
		if ( isset( $_POST[ "mfi_app_secret_hml" ] ) && !empty( $_POST[ "mfi_app_secret_hml" ] ) )
			update_option( "_mfi_app_secret_hml", $_POST[ "mfi_app_secret_hml" ] );

		// app id prd
		if ( isset( $_POST[ "mfi_app_id" ] ) && !empty( $_POST[ "mfi_app_id" ] ) )
			update_option( "_mfi_app_id", $_POST[ "mfi_app_id" ] );

		// app secret prd
		if ( isset( $_POST[ "mfi_app_secret" ] ) && !empty( $_POST[ "mfi_app_secret" ] ) )
			update_option( "_mfi_app_secret", $_POST[ "mfi_app_secret" ] );

		$url = "page=" . self::DOMAIN;

		if ( isset( $_POST[ "_wp_http_referer" ] ) && !empty( $_POST[ "_wp_http_referer" ] ) )
			$url = explode( "?", $_POST[ "_wp_http_referer" ] )[ 1 ];

		// generate token with saved app id and secret
		( new Muifabrica_Integration\Api\Muifabrica_Api() )->generate_token();

		wp_redirect( admin_url( "admin.php?{$url}" ) );
		exit;
	}

	/**
	 * Allow unsafe URLs.
	 *
	 * Modifies the request arguments to allow unsafe URLs.
	 *
	 * @since   1.0.0
	 * @param   array   $args   The array of arguments for the request
	 * @param   string  $url    The URL being requested
	 * @return  array
	 */
	public function accept_unsafe_urls( $args, $url ) : array
	{
		$args[ "reject_unsafe_urls" ] = false;

		return $args;
	}
}