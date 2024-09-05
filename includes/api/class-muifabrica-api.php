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

use WP_Error;

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
	 * Error message
	 *
	 * @var 	string
	 */
	protected $error_message;

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

		$this->env  = get_option( \Muifabrica_Integration::METAS[ "env_api" ] );

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
				"cancel"       => "order/cancle",   // PUT
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
		// Log data with appropriate log level
		\Muifabrica_Integration::log( $type, wc_print_r( $data, true ), $is_error );
	}

	/**
	 * Retrieve the current error message.
	 *
	 * This function returns the current error message stored in the object.
	 *
     * @since 	1.0.0
	 * @return 	string  	The current error message.
	 */
	public function get_error_message() : string
	{
		return $this->error_message;
	}

	/**
	 * Concatenate a new error message to the existing error message.
	 *
	 * This function appends a new error message to the existing error message.
	 * If there is no existing error message, it sets the new message as the error message.
	 *
     * @since 	1.0.0
	 * @param 	string 	$message 	The new error message to be added.
	 * @return 	void
	 */
	private function concat_error_message( string $message ) : void
	{
		$this->error_message .= ( !empty( $this->error_message ) ) ? "\n{$message}" : $message;
	}

	/**
	 * Logs data and concatenates error messages if necessary.
	 *
	 * This function logs the provided data with the specified log level and, if the log is an error,
	 * it concatenates the error message to the existing error messages.
	 *
     * @since 	1.0.0
	 * @param 	string 			$level 			The log level (e.g., "info", "error", "critical").
	 * @param 	integer 		$success 		Indicates the success status (0 for failure, 1 for success).
	 * @param 	string 			$function_name 	The name of the function where the log is being made.
	 * @param 	integer 		$code 			The status or error code.
	 * @param 	string 			$message 		The log or error message.
	 * @param 	string 			$url 			The URL related to the log entry.
	 * @param 	array 			$args 			The arguments or parameters related to the log entry.
	 * @param 	array|WP_Error 	$response 		The response data or error object.
	 * @param 	bool 			$is_error 		Indicates whether the log entry is an error.
	 * @return 	void
	 */
    private function log_and_concat_error( string $level = "info",int $success = 0, string $function_name = "", int $code = 0,  string $message = "", string $url = "", array $args = [], array|WP_Error $response = [], bool $is_error = false ) : void
    {
        $this->log( $level, [
            "success"  => $success,
            "function" => $function_name,
            "code"     => $code,
            "message"  => $message,
            "url"      => $url,
            "args"     => $args,
            "response" => $response,
        ], $is_error );

		if ( $is_error )
			$this->concat_error_message( $message );
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
        $suffix = ( $this->env != "prd" ) ? "_hml" : "";

        $app_id = get_option( \Muifabrica_Integration::METAS[ "app_id{$suffix}"     ] );
        $app_sc = get_option( \Muifabrica_Integration::METAS[ "app_secret{$suffix}" ] );

        return [ $app_id, $app_sc ];
    }

	/**
	 * Makes an API request to the specified endpoint with the given method and data.
	 *
	 * @since 	1.0.0
	 * @param 	string 	$endpoint 		The API endpoint to call.
	 * @param 	string 	$method 		The HTTP method to use (GET, POST, PUT).
	 * @param 	array 	$data 			The data to send with the request.
	 * @param 	string 	$function_name 	The name of the function making the request.
	 * @return 	array|false 			The response from the API or false on failure.
	 */
    private function api_request( string $endpoint, string $method = "GET", array $data = [], string $function_name = "" ) : array|false
    {
		if ( $function_name == "get_dollar_price" )
		{
			$url  = $endpoint;
		}
		else
		{
			// Get the base URL based on the environment.
			$base = $this->get_base_url();
			$url  = "{$base}/{$endpoint}";
		}

		// Get the base URL based on the environment.
		list( $app_id, $app_sc ) = $this->get_app_credentials();

		// Check if app credentials are missing.
		if ( empty( $app_id ) || empty( $app_sc ) )
		{
			$message = "App_id e/ou App_secret ausentes";
			$args    = [ "app_id" => $app_id, "app_secret" => $app_sc ];

			// Log the error and concatenate the error message.
			$this->log_and_concat_error( "error", 0, $function_name, 500, $message, $url, $args, [], true );

			return false;
		}

		// Special handling for the generate_token function.
		if ( $function_name == "generate_token" )
		{
			$data = [
				"app_id"     => $app_id,
				"app_secret" => $app_sc,
			];

			$url  = add_query_arg( $data, $url );

			$args = [
				"timeout" => 60,
				"headers" => [
					"Content-Type" => "application/json",
				],
			];
		}
		else
		{
			$token = $this->get_token();

			if ( $token === false )
				return false;

			$args  = [
				"timeout" => 60,
				"headers" => [
					"Content-Type"   => "application/json",
					"Authori-key"    => $app_sc,
					"Authori-zation" => "Bearer {$token}",
				],
			];
		}

		// Add the request body for POST and PUT methods.
		if ( ( $method === "POST" || $method === "PUT" ) && $function_name != "generate_token" )
		{
            $args[ "body"        ] = wp_json_encode( $data );
			$args[ "data_format" ] = "body";
        }

        try
		{
			// Make the API request based on the HTTP method.
			switch ( $method )
			{
				case "POST":
					$response = wp_remote_post( $url, $args );
				break;

				case "PUT":
					$response = wp_remote_request( $url, array_merge( $args, [ "method" => "PUT" ] ) );
				break;

				case "GET":
					$response = wp_remote_get( $url, $args );
				break;

				default:
					throw new \Exception( "Método HTTP inválido" );
				break;
			}

			// Handle the API response.
            return $this->handle_api_response( $response, $url, $args, $function_name );
        }
		catch ( \Exception $e )
		{
			$message = "{$e->getCode()} {$e->getMessage()}";

			// Log the exception and concatenate the error message.
            $this->log_and_concat_error( $message, 0, $function_name, $e->getCode(), $url, true );

            return false;
        }
    }

    /**
     * Handle API response and errors.
	 *
	 * @since 	1.0.0
	 * @param 	array|WP_Error 	$response
	 * @param 	string 			$url
	 * @param 	array 			$args
	 * @param 	string 			$function_name
	 * @return 	array|false
     */
    private function handle_api_response( array|WP_Error $response, string $url, array $args, string $function_name ) : array|false
    {
        if ( is_wp_error( $response ) )
		{
            $this->log_and_concat_error( "error", 0, $function_name, 500, $response->get_error_message(), $url, $args, $response, true );

            return false;
        }

		$status        = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( $response[ "body" ], true );

        if ( $status < 200 || $status >= 300 || ( isset( $response_body[ "status" ] ) && $response_body[ "status" ] != 200 ) )
        {
            $this->error_message = $response_body[ "msg" ] ?? $response_body[ "code" ] ?? "Erro não especificado e/ou desconhecido";

            $this->log_and_concat_error( "error", 0, $function_name, $status, $this->error_message, $url, $args, $response_body, true );

            return false;
        }

		// Log if debug enabled
		$this->log_and_concat_error( "info", 1, $function_name, $status, "success", $url, $args, $response_body, false );

        return $response_body;
    }

	/**
	 * Retrives the current dollar price in Brazilliasn Real (BRL) from the Awesome API.
	 *
     * @since 	1.0.0
	 * @return 	string|false 	The current dollar price or false.
	 */
	public function get_dollar_price() : string|false
	{
		$dollar   = get_transient( \Muifabrica_Integration::METAS[ "dollar" ] );

		if ( $dollar !== false )
			return $dollar;

		$endpoint = "https://economia.awesomeapi.com.br/last/USD-BRL";
		$response = $this->api_request( $endpoint, "GET", [], "get_dollar_price" );

        if ( !$response )
			return false;

		$dollar   = number_format( ( float ) $response[ "USDBRL" ][ "bid" ], 2, ",", "" );

		set_transient( \Muifabrica_Integration::METAS[ "dollar" ], $dollar, ( 15 * MINUTE_IN_SECONDS ) );

		return $dollar;
	}

	/**
	 * Generate token for use in API calls.
	 *
	 * @since 	1.0.0
	 * @return 	bool 	True on success or false if generation fails.
	 */
	public function generate_token() : bool
	{
		$endpoint = $this->urls[ "access_token" ];
		$response = $this->api_request( $endpoint, "POST", [], "generate_token" );

        if ( !$response )
			return false;

		set_transient( \Muifabrica_Integration::METAS[ "access_token" ], $response[ "data" ][ "token" ], DAY_IN_SECONDS );

        return true;
	}

	/**
     * Get access token, generating a new one if necessary.
	 *
	 * @since 	1.0.0
	 * @return 	string|false
	 */
	public function get_token() : string|false
	{
		$token = get_transient( \Muifabrica_Integration::METAS[ "access_token" ] );

		if ( $token === false )
			$this->generate_token();

		return get_transient( \Muifabrica_Integration::METAS[ "access_token" ] );
	}

	/**
     * Get the list of categories from API.
	 *
	 * @since 	1.0.0
	 * @return 	array|false
	 */
	public function get_categories() : array|false
	{
		$endpoint = $this->urls[ "category" ];
		$response = $this->api_request( $endpoint, "GET", [], "get_categories" );

		return $response[ "data" ][ "list" ] ?? false;
	}

	/**
	 * Get the product details from API.
	 *
	 * @since 	1.0.0
	 * @param 	int 	$product_id
	 * @return 	array|false
	 */
	public function get_product_detail( int $product_id ) : array|false
	{
		$endpoint = $this->urls[ "product" ][ "details" ] . "/{$product_id}";
		$response = $this->api_request( $endpoint, "GET", [], "get_product_detail" );

		return $response[ "data" ] ?? false;
	}

	/**
	 * Get the list of products from API.
	 *
	 * @since 	1.0.0
	 * @return 	array|false
	 */
	public function get_products() : array|false
	{
		$aux_prdts  = [];
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
				$endpoint  = $this->urls[ "product" ][ "list" ] . "?cate_id={$category[ "id" ]}&page={$page}&limit={$limit}";
				$response  = $this->api_request( $endpoint, "GET", [], "get_products" );

				if ( !$response )
					return false;

				if ( $response[ "data" ][ "count" ] >= 1 )
				{
				}

				$count     = $response[ "data" ][ "count" ] ?? 0;
				$list      = $response[ "data" ][ "list"  ] ?? [];
				$aux_prdts = array_merge( $aux_prdts, $list );

				$page++;
			}
			while ( $count == $limit );
		}

		foreach ( $aux_prdts as $product )
		{
			$details = $this->get_product_detail( $product[ "id" ] );

			if ( $details !== false )
				$products[ $details[ "id" ] ] = json_decode( json_encode( $details ) );
		}

		return $products;
	}

	/**
	 * Get the freight from API.
	 *
	 * @since 	1.0.0
	 * @param 	array 	$data
	 * @return 	array|false
	 */
	public function get_freight( array $data ) : array|false
	{
		$endpoint = $this->urls[ "freight" ];
		$response = $this->api_request( $endpoint, "POST", $data, "get_freight" );

		return $response[ "data" ] ?? false;
	}

	/**
	 * Post an order to the API.
	 *
	 * This function sends an order to the API and handles the response.
	 * It logs errors and concatenates error messages if any issues occur during the process.
	 *
	 * @since 	1.0.0
	 * @param 	array 	$data 	The order data to be sent.
	 * @return 	array|false 	The response data from the API or false on failure.
	 */
	public function post_order( array $data ) : array|false
	{
		$endpoint = $this->urls[ "order" ][ "saved" ];
		$response = $this->api_request( $endpoint, "POST", $data, "post_order" );

		return $response[ "data" ] ?? false;
	}

	/**
	 * Confirm order to the API.
	 *
	 * This function sends a confirmation order to the API and handles the response.
	 * It logs errors and concatenates error messages if any issues occur during the process.
	 *
	 * @since 	1.0.0
	 * @param 	array 	$data 	The order data to be sent.
	 * @return 	array|false 	The response data from the API or false on failure.
	 */
	public function confirm_order( array $data ) : array|false
	{
		$endpoint = $this->urls[ "order" ][ "confirmation" ];
		$response = $this->api_request( $endpoint, "POST", $data, "confirm_order" );

		return $response[ "data" ] ?? false;
	}

	/**
	 * Cancel order to the API.
	 *
	 * This function sends a cancelation order to the API and handles the response.
	 * It logs errors and concatenates error messages if any issues occur during the process.
	 *
	 * @since 	1.0.0
	 * @param 	array 	$data 		The order data to be sent.
	 * @param 	int 	$order_id 	The MFI order id.
	 * @return 	array|false 		The response data from the API or false on failure.
	 */
	public function cancel_order( array $data, int $order_id ) : array|false
	{
		$endpoint = $this->urls[ "order" ][ "cancel" ] . "/{$order_id}";
		$response = $this->api_request( $endpoint, "PUT", $data, "cancel_order" );

		return $response[ "data" ] ?? false;
	}
}
