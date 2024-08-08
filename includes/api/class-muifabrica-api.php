<?php
/**
 * Class Muifabrica Api
 *
 * This class handles API interactions for Muifabrica, including generating tokens,
 * setting webhooks, processing orders, and other related tasks.
 *
 * @author      Mateus Costa <mateus@costamateus.com.br>
 * @package 	Muifabrica_Integration/Api
 * @version 	1.0.0
 */

namespace Muifabrica_Integration\Api;

class Muifabrica_Api
{
	/**
	 * Base API url hml.
	 *
	 * @since 	1.0.0
	 * @var 	string
	 */
	const U_HML = "https://apist.mufabrica.com/btapi";

	/**
	 * Base API url prd.
	 *
	 * @since 	1.0.0
	 * @var 	string
	 */
	const U_PRD = "https://apist.mufabrica.com/btapi";

    /**
	 * All urls.
	 *
	 * @since 	1.0.0
	 * @var 	array
	 */
	protected $urls;

	/**
	 * Environment setting.
	 *
	 * @since 	1.0.0
	 * @var 	string|null
	 */
	protected $env;

	/**
	 * Constructor.
     * Initializes the class.
	 *
	 * @since 	1.0.0
     * @return  void
	 */
	public function __construct()
	{
		// Initialize environment, URLs, and logger properties

		$this->env  = get_option( "_mfi_env_api" );

		$this->urls = [
			"access_token"   => "access_token",     // POST
			"refresh_token"  => "refresh_token",    // GET
			"refresh_secret" => "refresh_secret",   // GET
			"freight"        => "freight",          // POST
			"category"       => "category",         // GET
			"order"          => [
				"shipping"     => "order/shipping", // GET
				"confirmation" => "order/confirm",  // POST
				"saved"        => "order",          // POST
				"notes"        => "order/remark",   // PUT
				"receive"      => "order/receive",  // PUT
				"cancel"       => "order/cancel",   // PUT
				"list"         => "order/list",     // GET
				"details"      => "order",          // GET
			],
			"product"        => [
				"list"         => "product/list",   // GET, ?cate_id={category_id}
				"details"      => "product",        // GET, /{product_id}
			],
        ];
    }

    /**
     * Logs data with appropriate log level.
     *
     * @since 	1.0.0
     * @param 	string 	$type 		The log type.
     * @param 	array 	$data 		The data to log.
     * @param 	bool 	$is_error 	Whether the log is an error.
     * @return 	void
     */
	private function log( string $type, array $data, bool $is_error = false ) : void
	{
		// Get instance of Nickelpay
		$mfi = \Muifabrica_Integration::init();

		// Log data with appropriate log level
		$mfi->log( $type, wc_print_r( $data, true ), $is_error );
	}

    /**
     * Get base URL based on environment.
	 *
	 * @since 	1.0.0
	 * @return 	string
     */
    private function get_base_url() : string
    {
        return ( $this->env != "prd" ) ? self::U_HML : self::U_PRD;
    }

    /**
     * Get app credentials based on environment.
	 *
	 * @since 	1.0.0
	 * @return 	array
     */
    private function get_app_credentials() : array
    {
        $app_id = ( $this->env != "prd" ) ? get_option( "_mfi_app_id_hml"     ) : get_option( "_mfi_app_id"     );
        $app_sc = ( $this->env != "prd" ) ? get_option( "_mfi_app_secret_hml" ) : get_option( "_mfi_app_secret" );

        return [ $app_id, $app_sc ];
    }

    /**
     * Handle API response and errors.
	 *
	 * @since 	1.0.0
	 * @param 	array|WP_Error 	$name
	 * @param 	string 			$url
	 * @param 	array 			$params
	 * @param 	string 			$function_name
	 * @return 	array
     */
    private function handle_api_response( array|WP_Error $response, string $url, array $params, string $function_name ) : array
    {
        if ( is_wp_error( $response ) )
        {
            $this->log( "error", [
                "success"  => 0,
                "function" => $function_name,
                "code"     => 500,
                "message"  => $response->get_error_message(),
                "url"      => $url,
                "args"     => $params,
                "response" => $response,
            ], true );

            return false;
        }

        $status        = wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( $response[ "body" ], true );

        if ( $status < 200 || $status >= 300 || ( isset( $response_body[ "status" ] ) && $response_body[ "status" ] != 200 ) )
        {
            $this->log( "error", [
                "success"  => 0,
                "function" => $function_name,
                "code"     => $status,
                "message"  => isset( $response_body[ "msg" ] ) ? $response_body[ "msg" ] : "",
                "url"      => $url,
                "args"     => $params,
                "response" => $response_body,
            ] );

            return false;
        }

        return $response_body;
    }

	/**
	 * Generate token for use in Webhook API calls.
	 *
	 * @since 	1.0.0
	 * @return 	bool 	True on success or false if generation fails.
	 */
	public function generate_token() : bool
	{
        $base_url = $this->get_base_url();
        $url      = "{$base_url}/" . $this->urls[ "access_token" ];

        list( $app_id, $app_sc ) = $this->get_app_credentials();

		if ( empty( $app_id ) || empty( $app_sc ) )
		{
			$this->log( "error", [
				"success"  => 0, // false
				"function" => "generate_token",
				"code"     => 500,
				"message"  => "Missing app_id or app_secret",
				"url"      => $url,
				"args"     => [ $this->env, $app_id, $app_sc ],
				"response" => null,
			], true );

			return false;
		}

		$params   = [
			"app_id"  => $app_id,
			"app_key" => $app_sc,
		];

		$url      = add_query_arg( $params, $url );

		$args     = [
			"timeout" => 60,
			"headers" => [
				"Content-Type" => "application/json",
			],
		];

		try
		{
			// Make API call
			$response      = wp_remote_post( $url, $args );

			$params        = array_merge( $args, $params );

            $response_body = $this->handle_api_response( $response, $url, $params, "generate_token" );

            if ( !$response_body )
				return false;

			// Set the access token for API authentication
			set_transient( "_mfi_access_token", $response_body[ "data" ][ "token" ], DAY_IN_SECONDS );

			// Log if debug enabled
			$this->log( "info", [
				"success"  => 1, // true
				"function" => "generate_token",
				"code"     => $status,
				"message"  => "Success",
				"url"      => $url,
				"args"     => $params,
				"response" => $response_body,
			] );

			return true;
		}
		catch ( Exception $e )
		{
			$this->log( "critical", [
				"success"  => 0, // false
				"function" => "generate_token",
				"code"     => $status,
				"message"  => $e->getMessage(),
				"url"      => $url,
				"args"     => array_merge( $args, $params ),
				"response" => $response ?? null,
			], true );

			return false;
		}
	}

	/**
     * Get access token, generating a new one if necessary.
	 *
	 * @since 	1.0.0
	 * @return 	string|false
	 */
	public function get_token() : string|false
	{
		$token = get_transient( "_mfi_access_token" );

		if ( $token === false )
		{
			if ( !$this->generate_token() )
				return false;

			$token = get_transient( "_mfi_access_token" );
		}

		return $token;
	}

	/**
     * Get categories from API.
	 *
	 * @since 	1.0.0
	 * @return 	array|false
	 */
	public function get_categories() : array|false
	{
        $base_url = $this->get_base_url();
        $url      = "{$base_url}/" . $this->urls[ "category" ];

        list( $app_id, $app_sc ) = $this->get_app_credentials();

		if ( empty( $app_id ) || empty( $app_sc ) )
		{
			$this->log( "error", [
				"success"  => 0, // false
				"function" => "get_categories",
				"code"     => 500,
				"message"  => "Missing app_id or app_secret",
				"url"      => $url,
				"args"     => [ $this->env, $app_id, $app_sc ],
				"response" => null,
			], true );

			return false;
		}

		$token    = $this->get_token();

		if ( $token === false )
			return false;

		$args     = [
			"timeout" => 60,
			"headers" => [
				"Content-Type"   => "application/json",
				"Authori-key"    => $app_sc,
				"Authori-zation" => "Beare {$token}",
			],
		];

        try
        {
            $response      = wp_remote_get( $url, $args );

            $response_body = $this->handle_api_response( $response, $url, $args, "get_categories" );

            if ( !$response_body )
				return false;

            return $response_body[ "data" ][ "list" ];
        }
        catch ( Exception $e )
        {
            $this->log( "critical", [
                "success"  => 0,
                "function" => "get_categories",
                "code"     => 500,
                "message"  => $e->getMessage(),
                "url"      => $url,
                "args"     => $args,
                "response" => null,
            ], true );

            return false;
        }
	}

	/**
	 * Get the product details.
	 *
	 * @since 	1.0.0
	 * @param 	int 	$product_id
	 * @return 	array|false
	 */
	public function get_product_detail( int $product_id ) : array|false
	{
		$detail_url = $this->urls[ "product" ][ "details" ];
        $base_url   = $this->get_base_url();
        $url        = "{$base_url}/{$detail_url}/{$product_id}";

        list( $app_id, $app_sc ) = $this->get_app_credentials();

        if ( empty( $app_id ) || empty( $app_sc ) )
        {
            $this->log( "error", [
                "success"  => 0,
                "function" => "get_product_detail",
                "code"     => 500,
                "message"  => "Missing app_id or app_secret",
                "url"      => $url,
                "args"     => [ $this->env, $app_id, $app_sc ],
                "response" => null,
            ], true );

            return false;
        }

        $token      = $this->get_token();

        if ( $token === false )
			return false;

        $args       = [
            "timeout" => 60,
            "headers" => [
                "Content-Type"   => "application/json",
                "Authori-key"    => $app_sc,
                "Authori-zation" => "Bearer {$token}",
            ],
        ];

        try
        {
            $response      = wp_remote_get( $url, $args );

            $response_body = $this->handle_api_response( $response, $url, $args, "get_product_detail" );

            if ( !$response_body )
				return false;

            return $response_body[ "data" ];
        }
        catch ( Exception $e )
        {
            $this->log( "critical", [
                "success"  => 0,
                "function" => "get_product_detail",
                "code"     => 500,
                "message"  => $e->getMessage(),
                "url"      => $url,
                "args"     => $args,
                "response" => null,
            ], true );

            return false;
        }
	}

	/**
	 * Get the list of products.
	 *
	 * @since 	1.0.0
	 * @return 	array|false
	 */
	public function get_products() : array|false
	{
        $base_url   = $this->get_base_url();
        $url        = "{$base_url}/" . $this->urls[ "product" ][ "list" ];

        list( $app_id, $app_sc ) = $this->get_app_credentials();

		if ( empty( $app_id ) || empty( $app_sc ) )
		{
			$this->log( "error", [
				"success"  => 0, // false
				"function" => "get_products",
				"code"     => 500,
				"message"  => "Missing app_id or app_secret",
				"url"      => $url,
				"args"     => [ $this->env, $app_id, $app_sc ],
				"response" => null,
			], true );

			return false;
		}

		$token      = $this->get_token();

		if ( $token === false )
			return false;

		$args       = [
			"timeout" => 60,
			"headers" => [
				"Content-Type"   => "application/json",
				"Authori-key"    => $app_sc,
				"Authori-zation" => "Beare {$token}",
			],
		];

		$limit      = 50;
		$aux_prds   = [];
		$products   = [];

		$categories = $this->get_categories();

		if ( $categories === false )
			return false;

		foreach ( $categories as $category )
		{
			$page  = 1;
			$count = 0;

			do
			{
				try
				{
					$full_url      = "{$url}?cate_id=" . $category[ "id" ] . "&page={$page}&limit={$limit}";

					$response      = wp_remote_get( $full_url, $args );

					$response_body = $this->handle_api_response( $response, $full_url, $args, "get_products" );

					if ( !$response_body )
						return false;

					if ( $response_body[ "data" ][ "count" ] >= 1 )
					{
						$list     = $response_body[ "data" ][ "list" ];
						$aux_prds = array_merge( $aux_prds, $list );
					}

					$page++;

					$count = $response_body[ "data" ][ "count" ] ?? 0;
				}
				catch ( Exception $e )
				{
					$this->log( "critical", [
						"success"  => 0, // false
						"function" => "get_products",
						"code"     => $status,
						"message"  => $e->getMessage(),
						"url"      => $full_url,
						"args"     => array_merge( $args, $params ),
						"response" => $response ?? null,
					], true );

					continue;
				}
			}
			while ( $count == $limit );
		}

		foreach ( $aux_prds as $product )
		{
			$details = $this->get_product_detail( $product[ "id" ] );

			if ( $details !== false )
				$products[ $details[ "id" ] ] = json_decode( json_encode( $details ) );
		}

		return $products;
	}
}
