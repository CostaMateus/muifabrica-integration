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
defined( "ABSPATH" ) || exit;

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
				"list"         => "product/list",   // GET
				"details"      => "product",        // GET
			],
        ];
    }

    /**
     * Log data.
     * Logs data with appropriate log level.
     *
     * @since 	1.0.0
     * @param 	string 	$type 		The log type.
     * @param 	array 	$data 		The data to log.
     * @param 	bool 	$is_error 	Whether the log is an error.
     * @return 	void
     */
	private function log( string $type, array $data, bool $is_error = false )
	{
		// Get instance of Nickelpay
		$mfi = Muifabrica_Integration::init();

		// Log data with appropriate log level
		$mfi->log( $type, wc_print_r( $data, true ), $is_error );
	}

	/**
	 * Generate token for use in Webhook API calls.
	 * This function generates an access token for use in making API calls.
	 *
	 * @since 	1.0.0
	 * @return 	bool 	True on success or false if generation fails.
	 */
	public function generate_token()
	{
		$url    = ( $this->env != "prd" ) ? self::U_HML : self::U_PRD;
		$url   .= "/" . $this->urls[ "access_token" ];

		$app_id = ( $this->env != "prd" ) ? get_option( "_mfi_app_id_hml"     ) : get_option( "_mfi_app_id"     );
		$app_sc = ( $this->env != "prd" ) ? get_option( "_mfi_app_secret_hml" ) : get_option( "_mfi_app_secret" );

		if ( empty( $app_id ) || empty( $app_sc ) )
		{
			$this->log( "error", [
				"success"  => 0, // false
				"function" => "generate_token",
				"code"     => 500,
				"message"  => "Missing app_id or app_secret",
				"url"      => $url,
				"args"     => [ $this->env, $app_id, $app_sc ],
				"response" => $response,
			], true );

			return false;
		}

		$params = [
			"app_id"  => $app_id,
			"app_key" => $app_sc,
		];

		$url    = add_query_arg( $params, $url );

		$args   = [
			"timeout" => 60,
			"headers" => [
				"Content-Type" => "application/json",
			],
		];

		try
		{
			// Make API call
			$response = wp_remote_post( $url, $args );

			// Handle processing result
			if ( is_wp_error( $response ) )
			{
				// WP error
				$this->log( "error", [
					"success"  => 0, // false
					"function" => "generate_token",
					"code"     => 500,
					"message"  => $response->get_error_message(),
					"url"      => $url,
					"args"     => array_merge( $args, $params ),
					"response" => $response,
				], true );

				return false;
			}

			$status   = wp_remote_retrieve_response_code( $response );
			$response = json_decode( $response[ "body" ], true );
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

		// API error
		if ( $status < 200 || $status >= 300 || ( isset( $response[ "status" ] ) && $response[ "status" ] != 200 ) )
		{
			$this->log( "error", [
				"success"  => 0, // false
				"function" => "generate_token",
				"code"     => $status,
				"message"  => ( isset( $response[ "msg" ] ) ) ? $response[ "msg" ] : "",
				"url"      => $url,
				"args"     => array_merge( $args, $params ),
				"response" => $response,
			] );

			return false;
		}

		// Log if debug enabled
		$this->log( "info", [
			"success"  => 1, // true
			"function" => "generate_token",
			"code"     => $status,
			"message"  => "Success",
			"url"      => $url,
			"args"     => array_merge( $args, $params ),
			"response" => $response,
		] );

		// Returns an access token for API authentication
		set_transient( "_mfi_access_token", $response[ "data" ][ "token" ], DAY_IN_SECONDS );

		return true;
	}

	// /**
	//  * Prepare webhook args
	//  *
	//  * @since 	1.4.0
	//  * @param 	array 	$data
	//  * @param 	bool 	$unregister
	//  * @return 	array
	//  */
	// private function prepara_args_webhook( array $data, bool $unregister = false )
	// {
	// 	$return = [
	// 		"data_format" => "body",
	// 		"timeout"     => 60,
	// 		"method"      => "",
	// 		"headers"     => [
	// 			"Content-Type"  => "application/json",
	// 			"Authorization" => "Bearer {$data[ "token" ]}",
	// 		],
	// 	];

	// 	if ( !$unregister )
	// 	{
	// 		$return[ "body" ] = wp_json_encode( [
	// 			"url"              => $data[ "url"     ],
	// 			"transaction_type" => $data[ "type"    ],
	// 			"login"            => $data[ "account" ][ "login"    ],
	// 			"password"         => $data[ "account" ][ "password" ]
	// 		] );
	// 	}

	// 	return $return;
	// }

	// /**
	//  * Call external API webhooks.
	//  *
	//  * @since 	1.4.0
	//  * @param 	array 	$args 	The request data.
	//  * @param 	array 	$url 	The request url.
	//  * @return 	int
	//  */
	// private function call_webook( array $args, string $url )
	// {
	// 	try
	// 	{
	// 		// Make API call
	// 		$response = wp_remote_request( $url, $args );

	// 		// Handle processing result
	// 		if ( is_wp_error( $response ) )
	// 		{
	// 			// WP error
	// 			$this->log( "error", [
	// 				"success"  => false,
	// 				"function" => "set_webhook",
	// 				"code"     => 500,
	// 				"type"     => $data[ "type" ],
	// 				"wp_error" => $response->get_error_message(),
	// 				"url"      => $url,
	// 				"args"     => $args,
	// 				"response" => $response,
	// 			], true );

	// 			return 0;
	// 		}

	// 		$status   = wp_remote_retrieve_response_code( $response );
	// 		$response = json_decode( $response[ "body" ], true );
	// 	}
	// 	catch ( Exception $e )
	// 	{
	// 		$this->log( "critical", [
	// 			"success"   => false,
	// 			"function"  => "generate_token",
	// 			"code"      => $status,
	// 			"exception" => $e->getMessage(),
	// 			"url"       => $url,
	// 			"args"      => $args,
	// 			"response"  => $response,
	// 		], true );

	// 		return 0;
	// 	}

	// 	// API error
	// 	if ( $status < 200 || $status >= 300 )
	// 	{
	// 		$this->log( "warning", [
	// 			"success"  => false,
	// 			"function" => "set_webhook",
	// 			"code"     => $status,
	// 			"type"     => $data[ "type" ],
	// 			"error"    => $response[ "message" ],
	// 			"url"      => $url,
	// 			"args"     => $args,
	// 			"response" => $response,
	// 		] );

	// 		return 1;
	// 	}

	// 	// Log if debug enabled
	// 	$this->log( "info", [
	// 		"success"  => true,
	// 		"function" => "set_webhook",
	// 		"code"     => $status,
	// 		"type"     => $data[ "type" ],
	// 		"url"      => $url,
	// 		"args"     => $args,
	// 		"response" => $response,
	// 	] );

	// 	return 2;
	// }

	// /**
	//  * Register webhooks for receiving notifications.
	//  *
	//  * @since 	1.4.0
	//  * @param 	array 	$data 	The webhook data.
	//  * @return 	int
	//  */
	// private function create_webhook( array $data )
	// {
	// 	$url              = $this->urls[ "webhook" ];
	// 	$args             = $this->prepara_args_webhook( $data );
	// 	$args[ "method" ] = "POST";

	// 	return $this->call_webook( $args, $url );
	// }

	// /**
	//  * Deletes registred webhooks for receiving notifications.
	//  *
	//  * @since 	1.4.0
	//  * @param 	array 	$data 	The webhook data.
	//  * @return 	int
	//  */
	// private function delete_webhook( array $data )
	// {
	// 	$url              = $this->urls[ "webhook" ] . "/" . $data[ "type" ];
	// 	$args             = $this->prepara_args_webhook( $data, true );
	// 	$args[ "method" ] = "DELETE";

	// 	return $this->call_webook( $args, $url );
	// }

	// /**
	//  * Get customer account ID.
	//  *
	//  * @since 	1.0.0
	//  * @param 	array 	$account 	The account credentials.
	//  * @return 	string|null 		The customer account ID or null if retrieval fails.
	//  */
	// private function get_account( array $account )
	// {
	// 	$try   = 0;
	// 	$token = null;

	// 	do
	// 	{
	// 		// Retrieve token
	// 		$token = $this->generate_token( $account );
	// 		$try++;
	// 	}
	// 	while ( !$token && $try < 3 );

	// 	if ( !$token )
	// 		return null;

	// 	// Prepare data
	// 	$args  = [
	// 		"timeout" => 60,
	// 		"headers" => [
	// 			"Content-Type"  => "application/json",
	// 			"Authorization" => "Bearer {$token}",
	// 		],
	// 	];

	// 	try
	// 	{
	// 		// Make API call
	// 		$response = wp_remote_get( $this->urls[ "customer" ], $args );

	// 		// Handle processing result
	// 		if ( is_wp_error( $response ) )
	// 		{
	// 			// WP error
	// 			$this->log( "error", [
	// 				"success"  => false,
	// 				"function" => "get_account",
	// 				"code"     => 500,
	// 				"error"    => "Get account_id method failure - EC:101",
	// 				"wp_error" => $response->get_error_message(),
	// 				"url"      => $url,
	// 				"args"     => $args,
	// 				"response" => $response,
	// 			], true );

	// 			return null;
	// 		}

	// 		$status   = wp_remote_retrieve_response_code( $response );
	// 		$response = json_decode( $response[ "body" ], true );
	// 	}
	// 	catch ( Exception $e )
	// 	{
	// 		$this->log( "critical", [
	// 			"success"   => false,
	// 			"function"  => "get_account",
	// 			"code"      => $status,
	// 			"exception" => $e->getMessage(),
	// 			"url"       => $url,
	// 			"args"      => $args,
	// 			"response"  => $response,
	// 		], true );

	// 		return null;
	// 	}

	// 	// API error
	// 	if ( $status < 200 || $status >= 300 )
	// 	{
	// 		$this->log( "warning", [
	// 			"success"  => false,
	// 			"function" => "get_account",
	// 			"code"     => $status,
	// 			"error"    => "Get account_id method failure - EC:102",
	// 			"url"      => $url,
	// 			"args"     => $args,
	// 			"response" => $response,
	// 		] );

	// 		return null;
	// 	}

	// 	// Check if data is set
	// 	if ( !isset( $response[ "account" ] ) || !is_array( $response[ "account" ] ) || empty( $response[ "account" ] ) )
	// 	{
	// 		$this->log( "warning", [
	// 			"success"  => false,
	// 			"function" => "get_account",
	// 			"code"     => $status,
	// 			"error"    => "Get account_id method failure - EC:103",
	// 			"url"      => $url,
	// 			"args"     => $args,
	// 			"response" => $response,
	// 		], true );

	// 		return null;
	// 	}

	// 	// Log if debug enabled
	// 	$this->log( "info", [
	// 		"success"  => true,
	// 		"function" => "get_account",
	// 		"code"     => $status,
	// 		"url"      => $url,
	// 		"args"     => $args,
	// 		"response" => $response,
	// 	] );

	// 	return $response[ "account" ][ 0 ][ "_id" ];
	// }

	// /**
	//  * Set body payment to process
	//  *
	//  * @since 	1.0.0
	//  * @param 	string 	$account 	The customer account ID.
	//  * @param 	string 	$type 		The payment type.
	//  * @param 	array 	$data 		The payment data.
	//  * @return 	array 				The formatted payment data.
	//  */
	// private function set_body_payment( string $account, string $type, array $data )
	// {
	// 	if ( $type == "pix" )
	// 	{
	// 		return [
	// 			"account_id"     => $account,
	// 			"payer_question" => $data[ "description" ],
	// 			"value"          => $data[ "value"       ],
	// 			"due"            => $data[ "due_date"    ] . " 23:59:59",
	// 		];
	// 	}

	// 	if ( $type == "billet" )
	// 	{
	// 		return [
	// 			"account_id"     => $account,
	// 			"due_date"       => $data[ "due_date"    ],
	// 			"value"          => $data[ "value"       ],
	// 		];
	// 	}
	// }

	// /**
	//  * Process payment
	//  *
	//  * @since 	1.0.0
	//  * @param 	string 	$type 	The payment type.
	//  * @param 	array 	$data 	The payment data.
	//  * @return 	array 			The payment processing result.
	//  */
	// public function process_payment( string $type, array $data ): array
	// {
	// 	$try        = 0;
	// 	$token      = null;

	// 	$user       = ( "on" === $this->env ) ? get_option( "nickelpay_np_sb_user"       ) : get_option( "nickelpay_np_prd_user"       );
	// 	$pass       = ( "on" === $this->env ) ? get_option( "nickelpay_np_sb_pass"       ) : get_option( "nickelpay_np_prd_pass"       );
	// 	$account_id = ( "on" === $this->env ) ? get_option( "nickelpay_np_sb_account_id" ) : get_option( "nickelpay_np_prd_account_id" );

	// 	$order_id   = $data[ "order_id" ];

	// 	do
	// 	{
	// 		// Retrieve token
	// 		$token = $this->generate_token( [
	// 			"username" => $user,
	// 			"password" => $pass,
	// 		] );

	// 		$try++;
	// 	}
	// 	while ( !$token && $try < 3 );

	// 	// Token failed
	// 	if ( !$token )
	// 	{
	// 		$this->log( "error", [
	// 			"success"      => "false",
	// 			"function"     => "generate_token (process_payment)",
	// 			"code"         => 500,
	// 			"order_id"     => "#{$order_id}",
	// 			"payment_type" => $type,
	// 			"error"        => "Token creation failed - EC:001",
	// 			"url"          => null,
	// 			"args"         => null,
	// 			"response"     => null,
	// 		], true );

	// 		return [
	// 			"success" => false,
	// 			"message" => __( "Payment method failure.", "wc_nickelpay" ) . " EC:001",
	// 			"code"    => "generate_token",
	// 			"data"    => [],
	// 		];
	// 	}

	// 	// Unsupported payment method
	// 	if ( !in_array( $type, [ "pix", "billet" ] ) )
	// 	{
	// 		$this->log( "error", [
	// 			"success"      => "false",
	// 			"function"     => "unsupported_payment_method (process_payment)",
	// 			"code"         => 500,
	// 			"order_id"     => "#{$order_id}",
	// 			"payment_type" => $type,
	// 			"error"        => "Unsupported payment method - EC:002",
	// 			"url"          => null,
	// 			"args"         => null,
	// 			"response"     => null,
	// 		], true );

	// 		return [
	// 			"success" => false,
	// 			"message" => __( "Unsupported payment method.", "wc_nickelpay" ) . " EC:002",
	// 			"code"    => "unsupported_payment_method.",
	// 			"data"    => [],
	// 		];
	// 	}

	// 	// Get customer account ID, if not saved
	// 	if ( !$account_id )
	// 	{
	// 		$account_id = $this->get_account( [
	// 			"username" => $user,
	// 			"password" => $pass,
	// 		] );

	// 		if ( !$account_id )
	// 		{
	// 			$this->log( "error", [
	// 				"success"      => false,
	// 				"function"     => "get_account (process_payment)",
	// 				"code"         => 500,
	// 				"order_id"     => "#{$order_id}",
	// 				"payment_type" => $type,
	// 				"error"        => "Invalid account_id - EC:003",
	// 				"url"          => null,
	// 				"args"         => null,
	// 				"response"     => null,
	// 			], true );

	// 			return [
	// 				"success" => false,
	// 				"message" => __( "Payment method failure.", "wc_nickelpay" ) . " EC:003",
	// 				"code"    => "get_account",
	// 				"data"    => [],
	// 			];
	// 		}

	// 		if ( "on" === $this->env )
	// 			update_option( "nickelpay_np_sb_account_id",  $account_id );
	// 		else
	// 			update_option( "nickelpay_np_prd_account_id", $account_id );
	// 	}

	// 	$url        = ( $type == "pix" ) ? $this->urls[ "pix_dynamic" ] : $this->urls[ "billet" ];
	// 	$body       = $this->set_body_payment( $account_id, $type, $data );

	// 	// Prepare data
	// 	$args       = [
	// 		"data_format" => "body",
	// 		"timeout"     => 60,
	// 		"body"        => wp_json_encode( $body ),
	// 		"headers"     => [
	// 			"Content-Type"  => "application/json",
	// 			"Authorization" => "Bearer {$token}",
	// 		],
	// 	];

	// 	try
	// 	{
	// 		// Make API call
	// 		$response = wp_remote_post( $url, $args );

	// 		// Handle processing result
	// 		if ( is_wp_error( $response ) )
	// 		{
	// 			// WP Error
	// 			$this->log( "error", [
	// 				"success"      => false,
	// 				"function"     => "process_payment",
	// 				"code"         => $status,
	// 				"order_id"     => "#{$order_id}",
	// 				"payment_type" => $type,
	// 				"error"        => "Payment method failure - EC:004",
	// 				"wp_error"     => $response->get_error_message(),
	// 				"url"          => $url,
	// 				"args"         => $args,
	// 				"response"     => $response,
	// 			], true );

	// 			return [
	// 				"success" => false,
	// 				"message" => __( "Payment method failure.", "wc_nickelpay" ) . " EC:004",
	// 				"code"    => "process_payment",
	// 				"data"    => [],
	// 			];
	// 		}

	// 		$status   = wp_remote_retrieve_response_code( $response );
	// 		$response = json_decode( $response[ "body" ], true );
	// 	}
	// 	catch ( Exception $e )
	// 	{
	// 		$status   = 500;
	// 		$response = [];

	// 		$this->log( "critical", [
	// 			"success"      => false,
	// 			"function"     => "process_payment",
	// 			"code"         => $status,
	// 			"order_id"     => "#{$order_id}",
	// 			"payment_type" => $type,
	// 			"exception"    => $e->getMessage(),
	// 			"url"          => $url,
	// 			"args"         => $args,
	// 			"response"     => $response,
	// 		], true );
	// 	}

	// 	// Check response status not success
	// 	if ( $status < 200 || $status >= 300 )
	// 	{
	// 		$this->log( "critical", [
	// 			"success"      => false,
	// 			"function"     => "process_payment",
	// 			"code"         => $status,
	// 			"order_id"     => "#{$order_id}",
	// 			"payment_type" => $type,
	// 			"error"        => "Payment method failure - EC:005",
	// 			"url"          => $url,
	// 			"args"         => $args,
	// 			"response"     => $response,
	// 		], true );

	// 		return [
	// 			"success" => false,
	// 			"message" => __( "Payment method failure.", "wc_nickelpay" ) . " EC:005",
	// 			"code"    => "process_payment",
	// 			"data"    => [],
	// 		];
	// 	}

	// 	// Log if debug enabled
	// 	$this->log( "info", [
	// 		"success"      => true,
	// 		"function"     => "process_payment",
	// 		"code"         => $status,
	// 		"order_id"     => "#{$order_id}",
	// 		"payment_type" => $type,
	// 		"url"          => $url,
	// 		"args"         => $args,
	// 		"response"     => $response,
	// 	] );

	// 	return [
	// 		"success" => true,
	// 		"message" => __( "Payment processed successfully.", "wc_nickelpay" ),
	// 		"code"    => "process_payment",
	// 		"data"    => $response,
	// 	];
	// }

	// /**
	//  * Register the webhooks
	//  *
	//  * @since 	1.0.0
	//  * @param 	array 	$data
	//  * @param 	bool 	$unregister
	//  * @return 	array
	//  */
	// public function webhook( array $data, bool $unregister = false )
	// {
	// 	$try    = 0;
	// 	$token  = null;
	// 	$billet = -1;
	// 	$pix    = -1;

	// 	do
	// 	{
	// 		// Retrieve token
	// 		$token = $this->generate_token( $data[ "account" ] );
	// 		$try++;
	// 	}
	// 	while ( !$token && $try < 3 );

	// 	if ( !$token )
	// 		return null;

	// 	if ( isset( $data[ "billet" ] ) )
	// 	{
	// 		$type = "cash_in_boleto";

	// 		if ( $unregister )
	// 		{
	// 			$billet = $this->delete_webhook( [
	// 				"token"   => $token,
	// 				"type"    => $type,
	// 			] );
	// 		}
	// 		else
	// 		{
	// 			$billet = $this->create_webhook( [
	// 				"token"   => $token,
	// 				"type"    => $type,
	// 				"url"     => $data[ "billet" ][ "url"     ],
	// 				"account" => $data[ "billet" ][ "webhook" ],
	// 			] );
	// 		}
	// 	}

	// 	if ( isset( $data[ "pix" ] ) )
	// 	{
	// 		$type = "cash_in_pix_receive";

	// 		if ( $unregister )
	// 		{
	// 			$pix = $this->delete_webhook( [
	// 				"token"   => $token,
	// 				"type"    => $type,
	// 			] );
	// 		}
	// 		else
	// 		{
	// 			$pix = $this->create_webhook( [
	// 				"token"   => $token,
	// 				"type"    => $type,
	// 				"url"     => $data[ "pix" ][ "url"     ],
	// 				"account" => $data[ "pix" ][ "webhook" ],
	// 			] );
	// 		}
	// 	}

	// 	return [
	// 		"billet" => $billet,
	// 		"pix"    => $pix
	// 	];
	// }
}
