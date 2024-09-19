<?php
/**
 * Helper class.
 *
 * This class contains various helper functions for handling products and variations in WooCommerce.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Muifabrica_Integration\Classes\Helpers
 * @version 	1.0.0
 */

namespace Muifabrica_Integration\Classes\Helpers;

use Muifabrica_Integration\Api\Muifabrica_Api;

class MFI_Helper
{
    /**
     * Translate product data into a format suitable for WooCommerce API.
     *
     * This function transforms a given array of product data into a structure that is compatible with the WooCommerce API.
     * It handles tasks such as removing unnecessary fields, creating categories, setting the product status to "draft,"
     * and formatting attributes, images, variations, and metadata.
     *
     * @since   1.0.0
     * @param   array   $product    The array of product data to be transformed.
     * @param   string  $prefix     The prefix to be used for the product SKU.
     * @return  array               The transformed product data array suitable for the WooCommerce API.
     */
    public static function translate_product( array $product, string $prefix ) : array
    {
        $images       = [];
        $attributes   = [];
        $combinations = [];
        $variations   = [];
        $categories[] = self::find_or_create_category( $product[ "cate_name_pt" ] );
        $metadatas    = [
            [
                "key"   => \Muifabrica_Integration::METAS[ "product_id"        ],
                "value" => $product[ "id"       ],
            ], [
                "key"   => \Muifabrica_Integration::METAS[ "regular_price"     ],
                "value" => $product[ "price"    ],
            ], [
                "key"   => \Muifabrica_Integration::METAS[ "status_regular"    ],
                "value" => "even",
            ],
            [
                "key"   => \Muifabrica_Integration::METAS[ "regular_ot_price"  ],
                "value" => $product[ "ot_price" ],
            ], [
                "key"   => \Muifabrica_Integration::METAS[ "status_regular_ot" ],
                "value" => "even",
            ],
            [
                "key"   => \Muifabrica_Integration::METAS[ "supplied"          ],
                "value" => "yes",
            ],
        ];

        $short        = strip_tags( $product[ "description_pt" ] );
        $short        = strip_tags( $short );
        $short        = substr( $short, 0, 150 );
        $short        = rtrim( $short ) . "...";

        $slug         = sanitize_title( $product[ "store_name_pt" ] );

        // Images
        foreach ( $product[ "slider_image" ] as $image )
        {
            $images[] = [
                "src"  => $image,
                "name" => "",
                "alt"  => "",
            ];
        }

        // Attributes and default
        foreach ( $product[ "items" ] as $i => $attr )
        {
            $attributes[] = [
                "name"      => $attr[ "value_pt"  ],
                "position"  => 1,
                "visible"   => true,
                "variation" => true,
                "options"   => $attr[ "detail_pt" ],
            ];
        }

        $combinations = self::combine_attributes( $attributes );

        // Variations
        foreach ( $product[ "attrs" ] as $variation )
        {
            $aux_attrs    = self::find_combination( $combinations, $variation[ "suk_pt" ] );

            $variations[] = [
                "description"    => "",
                "sku"            => $variation[ "unique" ],
                "regular_price"  => $variation[ "price"  ],
                // "sale_price"     => $variation[ "price"  ],
                "status"         => "publish",
                "shipping_class" => "muifabrica-shipping-class",
                "manage_stock"   => true,
                "stock_quantity" => $variation[ "stock"  ],
                "stock_status"   => $variation[ "stock"  ] > 0 ? "instock" : "outofstock",
                "weight"         => $variation[ "weight" ],
                "dimensions"     => [
                    "length" => $variation[ "length" ],
                    "width"  => $variation[ "width"  ],
                    "height" => $variation[ "height" ],
                ],
                "image"          => [
                    "src"    => $variation[ "pic"    ],
                    "name"   => "",
                    "alt"    => "",
                ],
                "attributes"     => $aux_attrs,
            ];
        }

        return [
            "name"               => $product[ "store_name_pt"  ],
            "slug"               => $slug,
            "type"               => "variable",
            "status"             => "draft",
            "feature"            => false,
            "catalog_visibility" => "visible",
            "shipping_class"     => "muifabrica-shipping-class",
            "description"        => $product[ "description_pt" ],
            "short_description"  => $short,
            "sku"                => $prefix . $product[ "attrs" ][ 0 ][ "unique" ],
            "regular_price"      => $product[ "price"          ],
            "sale_price"         => $product[ "price"          ],
            "virtual"            => $product[ "is_virtual"     ],
            "manage_stock"       => true,
            "stock_quantity"     => $product[ "stock"          ],
            "stock_status"       => $product[ "stock"          ] > 0 ? "instock" : "outofstock",
            "categories"         => $categories,
            "images"             => $images,
            "attributes"         => $attributes,
            "variations"         => $variations,
            "meta_data"          => $metadatas
        ];
    }

    /**
     * Translate an order into a specific format for further processing.
     *
     * This function translates the given order object into an associative array
     * containing customer details, shipping information, payment details, and
     * product information. It also calculates the freight costs, ICMS, and tariff
     * for the order.
     *
     * @since   1.0.0
     * @param   object  $order          The order object containing items and shipping details.
     * @param   bool    $to_confirm     To return earlier, only data necessary to confirm the order. Default false.
     * @return  array                   An associative array with translated order details.
     */
    public static function translate_order( object $order, bool $to_confirm = false )
    {
        $products      = [];
        $costumer_name = "{$order->get_billing_first_name()} {$order->get_billing_last_name()}";

        $address_1     = $order->get_shipping_address_1();
        $address_2     = $order->get_shipping_address_2();
        $number        = $order->get_meta( "_shipping_number" );
        $address       = "{$address_1}, {$number}, {$address_2}";

        $neighborhood  = $order->get_meta( "_shipping_neighborhood" );

        $pay_token     = "#{$order->get_id()}_#{$order->get_customer_id()}_#{$order->get_payment_method()}";

        $icms          = 0;
        $tariff        = 0;

        $freight       = self::get_freight( $order );

        $freight_type  = ( int ) self::get_freight_type( $order );

        foreach ( $freight as $key => $value )
        {
            $icms   += $value[ "icms"   ];
            $tariff += $value[ "tariff" ];
        }

        foreach ( $order->get_items() as $item )
        {
            $product       = $item->get_product();
            $supplied      = $product->get_meta( \Muifabrica_Integration::METAS[ "supplied" ] );

            if ( !$supplied ) continue;

            $mfi_prd_sku   = $product->get_meta( \Muifabrica_Integration::METAS[ "supplier_sku"  ] );
            $mfi_prd_id    = $product->get_meta( \Muifabrica_Integration::METAS[ "product_id"    ] );
            $mfi_prd_price = $product->get_meta( \Muifabrica_Integration::METAS[ "regular_price" ] );

            $products[]    = [
                "product_attr_unique" => $mfi_prd_sku,
                "product_id"          => $mfi_prd_id,
                "product_price"       => ( float ) $mfi_prd_price,
                "cart_num"            => $item->get_quantity(),
                "freight_price"       => $freight[ $mfi_prd_sku ][ "total" ],
            ];
        }

        if ( $to_confirm )
        {
            return [
                "freight_type" => $freight_type,
                "products"     => $products,
            ];
        }

        return [
            "freight_type" => $freight_type,
            "real_name"    => $costumer_name,
            "email"        => $order->get_billing_email(),
            "phone"        => preg_replace( "/\D/", "", $order->get_billing_phone() ),

            "post_code"    => $order->get_shipping_postcode(),
            "detail"       => $address,
            "city"         => $order->get_shipping_city(),
            "district"     => $neighborhood,
            "province"     => $order->get_shipping_state(),
            "state"        => $order->get_shipping_state(),
            "house_number" => $number,

            "paid"         => 0, // 0 - not paid | 1 - paid // TODO o que exatamente significa
            "pay_time"     => $order->get_date_created()->getTimestamp(),
            "pay_type"     => $order->get_payment_method(),
            "pay_token"    => $pay_token,

            "uid"          => $order->get_customer_id(),
            "personcode"   => $order->get_meta( "_billing_cpf" ),
            "tariff"       => $tariff,
            "icms"         => $icms,
            "products"     => $products,
        ];
    }

    /**
     * Find or create a product category based on the given name and slug.
     *
     * This function checks whether a product category with the given slug exists in the WooCommerce store.
     * If it exists, the function returns the category ID. If it does not exist, the function creates a new
     * category with the provided name and slug and then returns the new category ID.
     *
     * @since   1.0.0
     * @param   string  $name   The name of the product category.
     * @return  array           An array containing the category ID.
     */
    public static function find_or_create_category( string $name ) : array
    {
        $slug     = sanitize_title( $name );

        // Get the term object for the given slug and taxonomy 'product_cat'.
        $category = get_term_by( "slug", $slug, "product_cat" );

        // If the term object exists, set the result with the term ID.
        if ( $category instanceof \WP_Term )
        {
            return [ "id" => $category->term_id ];
        }
        else
        {
            // If the term doesn't exist, create a new term with the given name and slug.
            $category = wp_insert_term( $name, "product_cat", [ "slug" => $slug ] );

            // If the term creation was successful, set the result with the new term ID.
            if ( !is_wp_error( $category ) )
                return [ "id" => $category[ "term_id" ] ];
        }

        return [];
    }

    /**
     * Process product metadata.
     *
     * This function updates the metadata of a specified WooCommerce product by iterating through an array of metadata key-value pairs.
     *
     * @since   1.0.0
     * @param   int     $product_id     The ID of the WooCommerce product to update.
     * @param   array   $metadata       An array of associative arrays, where each contains a 'key' and 'value' to be set as product metadata.
     * @return  void
     */
    public static function process_product_metadata( int $product_id, array $metadata ) : void
    {
        // Get the WooCommerce product object.
        $product = wc_get_product( $product_id );

        // Loop through the metadata array.
        // And update the product metadata with the key and value from the array.
        foreach ( $metadata as $meta )
            $product->update_meta_data( $meta[ "key" ], $meta[ "value" ] );

        // Save the product with the updated metadata.
        $product->save();
    }

    /**
     * Redirect the user to a specific URL with data.
     *
     * This function constructs a URL with a given type and data, and then redirects the user to that URL. It is typically used within the WordPress admin area.
     *
     * @since   1.0.0
     * @param   string  $type   The type of message to be passed in the URL (e.g., success or error).
     * @param   array   $data   An associative array of data to be included in the URL as a query string.
     * @return  void
     */
    public static function redirect( string $type, array $data ) : void
    {
        $domain = \Muifabrica_Integration::DOMAIN;

        // Prepare the data to be passed in the URL.
        $data   = http_build_query( [ "data" => $data ] );

        // Construct the URL with the provided type and data.
        $url    = admin_url( "admin.php?page={$domain}&tab=preview&{$type}_message=OK&{$data}" );

        // Redirect the user to the constructed URL
        if ( wp_redirect( $url ) ) exit();

        return;
    }

    /**
     * Create a new product in WooCommerce.
     *
     * This function creates a new product in WooCommerce based on the provided product data array and SKU prefixes.
     * It performs several actions including translating the product data into a format suitable for WooCommerce,
     * sending a request to the WooCommerce API to create the product, processing the product metadata, and creating variations if applicable.
     *
     * @since   1.0.0
     * @param   array   $product    The associative array containing the product data to be created.
     * @param   array   $prefixes   An associative array with 'parent' and 'child' keys used as prefixes for the product SKU.
     * @return  bool                Returns true if the product was created successfully, otherwise returns false.
     */
    public static function create_product( array $product, array $prefixes ) : bool
    {
        // Translate the product data.
        $t_product        = self::translate_product( $product, $prefixes[ "parent" ] );

        // Create a new WP_REST_Request for creating the product.
        $product_request  = new \WP_REST_Request( "POST", "/wc/v3/products" );
        $product_request->set_body_params( $t_product );

        // Send the request to create the product.
        $product_response = rest_do_request( $product_request )->get_data();

        // Check if there was an error creating the product.
        if ( is_wp_error( $product_response ) )
        {
            // Log the error message.
            \Muifabrica_Integration::log( "error", "error_create_product - " . $product_response->get_error_message(), true );
            \Muifabrica_Integration::log( "info", print_r( $product_response, true ), true );

            return false;
        }

        // Process the product metadata.
        self::process_product_metadata( $product_response[ "id" ], $t_product[ "meta_data" ] );

        self::create_all_product_variations( $product_response[ "id" ] );

        $wc_product    = wc_get_product( $product_response[ "id" ] );
        $variations_id = $wc_product->get_children();

        foreach ( $variations_id as $variation_id )
        {
            $has_change   = false;

            $variation    = wc_get_product( $variation_id );
            $v_attributes = $variation->get_attributes();

            $t_variation  = self::find_variation( $t_product[ "variations" ], $v_attributes );

            if ( $t_variation )
            {
                if ( $variation->get_sku() != $t_variation[ "sku" ] )
                {
                    $variation->set_sku( $prefixes[ "child" ] . $t_variation[ "sku" ] );
                    $variation->update_meta_data( \Muifabrica_Integration::METAS[ "supplier_sku" ], $t_variation[ "sku" ] );
                }

                $variation->update_meta_data( \Muifabrica_Integration::METAS[ "supplied"       ], "yes"                           );
                $variation->update_meta_data( \Muifabrica_Integration::METAS[ "product_id"     ], $product[ "id" ]                );
                $variation->update_meta_data( \Muifabrica_Integration::METAS[ "regular_price"  ], $t_variation[ "regular_price" ] );
                $variation->update_meta_data( \Muifabrica_Integration::METAS[ "status_regular" ], "even"                          );

                $variation->set_regular_price( ( string ) $t_variation[ "regular_price" ] );
                // $variation->set_sale_price( ( string ) $t_variation[ "sale_price" ] );

                $variation->set_manage_stock( $t_variation[ "manage_stock" ] );
                $variation->set_stock_quantity( $t_variation[ "stock_quantity" ] );

                $variation->set_weight( $t_variation[ "weight"     ]             );
                $variation->set_length( $t_variation[ "dimensions" ][ "length" ] );
                $variation->set_width(  $t_variation[ "dimensions" ][ "width"  ] );
                $variation->set_height( $t_variation[ "dimensions" ][ "height" ] );

                $image_id = self::upload_file_by_url( $t_variation[ "image" ][ "url" ], $variation_id );

                if ( $image_id ) $variation->set_image_id( $image_id );

                $has_change = true;
            }

            if ( $has_change )
                $variation->save();
        }

        return true;
    }

	/**
     * Create all product variations from existing attributes.
     *
     * This function generates all possible variations for a given product based on its attributes.
     * It interacts with the WooCommerce data store to create and sort the variations.
     *
	 * @since 	1.0.0
     * @param   int   $product_id   The ID of the product for which variations are to be created.
     * @return  void
     */
	private static function create_all_product_variations( $product_id ) : void
    {
		$product    = wc_get_product( $product_id );

		$data_store = $product->get_data_store();

		if ( !is_callable( [ $data_store, "create_all_product_variations" ] ) )
			wp_die();

		$data_store->create_all_product_variations( $product );
		$data_store->sort_all_product_variations( $product->get_id() );
	}

    /**
     * Upload an image from a URL and attach it to a post.
     *
     * This function downloads an image from a provided URL, handles the file upload, and associates
     * the uploaded image as an attachment to a specified post. It returns the attachment ID if successful.
     *
	 * @since 	1.0.0
     * @param   string  $image_url  The URL of the image to be downloaded and uploaded.
     * @param   int     $post_id    The ID of the post to which the image should be attached.
     * @return  mixed               The attachment ID if successful, or null if there was an error.
     */
    private static function upload_file_by_url( $image_url, $post_id ) : mixed
    {
        require_once( ABSPATH . "wp-admin/includes/file.php" );

        $temp_file     = download_url( $image_url );

        if ( is_wp_error( $temp_file ) ) return null;

        $file          = [
            "name"     => basename( $image_url ),
            "type"     => mime_content_type( $temp_file ),
            "tmp_name" => $temp_file,
            "size"     => filesize( $temp_file ),
        ];

        $sideload      = wp_handle_sideload( $file, [ "test_form" => false ] );

        if ( !empty( $sideload[ "error" ] ) ) return null;

        $attachment_id = wp_insert_attachment(
            [
                "guid"           => $sideload[ "url"  ],
                "post_mime_type" => $sideload[ "type" ],
                "post_title"     => basename( $sideload[ "file" ] ),
                "post_content"   => "",
                "post_status"    => "inherit",
            ],
            $sideload[ "file" ],
            $post_id
        );

        if ( is_wp_error( $attachment_id ) || !$attachment_id )
            return null;

        require_once( ABSPATH . "wp-admin/includes/image.php" );

        $attachment_metadata = wp_generate_attachment_metadata( $attachment_id, $sideload[ "file" ] );

        wp_update_attachment_metadata( $attachment_id, $attachment_metadata );

        return $attachment_id;
    }

    /**
     * Generates all possible combinations of attribute options.
     *
     * This function recursively generates combinations of attribute options.
     * It takes an array of attributes, each containing a name and an array of options,
     * and returns all possible combinations of these options.
     *
     * @since   1.0.0
     * @param   array   $attributes An array of attributes where each attribute contains a name and an array of options.
     * @param   int     $index      The current index in the attributes array being processed. Defaults to 0.
     * @return  array               An array of all possible combinations of attribute options.
     */
    private static function combine_attributes( array $attributes, int $index = 0 ) : array
    {
        if ( $index == count( $attributes ) ) return [[]];

        $remaining    = self::combine_attributes( $attributes, $index + 1 );
        $combinations = [];

        foreach ( $attributes[ $index ][ "options" ] as $option )
        {
            foreach ( $remaining as $combination )
            {
                $combinations[] = array_merge(
                    [
                        [
                            "name"   => $attributes[ $index ][ "name" ],
                            "option" => $option,
                        ]
                    ],
                    $combination
                );
            }
        }

        return $combinations;
    }

    /**
     * Finds a specific combination within an array of combinations.
     *
     * This function searches through an array of attribute combinations to find a
     * combination that matches the provided search string. The search string should be
     * a comma-separated list of attribute options.
     *
     * @since   1.0.0
     * @param   array   $combinations An array of attribute combinations.
     * @param   string  $search       A comma-separated string of attribute options to search for.
     * @return  array|null            The matching combination if found, or null if not found.
     */
    private static function find_combination( array $combinations, string $search ) : mixed
    {
        $search_array = explode( ",", $search );

        foreach ( $combinations as $combination )
        {
            $options = array_column( $combination, "option" );

            if ( $options === $search_array )
                return $combination;
        }

        return null;
    }

    /**
     * Finds a specific variation within an array of variations based on attributes.
     *
     * This function searches through an array of variations to find a variation that matches
     * the provided search array. The search array contains key-value pairs where the key is
     * the attribute name (case-insensitive) and the value is the desired option.
     *
     * @since   1.0.0
     * @param   array   $variations An array of variations, each containing a description, sku, and attributes.
     * @param   array   $search     An associative array of attribute names (case-insensitive) and desired options.
     * @return  array|null          The matching variation if found, or null if not found.
     */
    private static function find_variation( array $variations, array $search ) : mixed
    {
        foreach ( $variations as $variation )
        {
            $match = true;

            foreach ( $search as $key => $value )
            {
                $found = false;

                foreach ( $variation[ "attributes" ] as $attribute )
                {
                    if ( strcasecmp( $attribute[ "name" ], $key ) === 0 && $attribute[ "option" ] == $value )
                    {
                        $found = true;
                        break;
                    }
                }

                if ( !$found )
                {
                    $match = false;
                    break;
                }
            }

            if ( $match )
                return $variation;
        }

        return null;
    }

    /**
     * Formats and returns a price string with optional HTML markup based on the price status.
     *
     * This method takes the last update date and an array of regular price data,
     * and returns a formatted price string. If the last update date is more than
     * two days old, it returns the price without any additional formatting.
     * Otherwise, it returns the price wrapped in a <mark> HTML element with a CSS class
     * indicating the price trend (up, down, or even).
     *
     * @since   1.0.0
     * @param   string  $last_upd       The date of the last update in a string format.
     * @param   array   $data_regular   An associative array containing price data.
     *                                  It must include:
     *                                  - 'status': A string indicating the price trend ('up', 'down', 'even').
     *                                  - 'price': The price value to be formatted.
     * @return  string                  The formatted price string, with HTML markup if applicable, or the plain price if the last update is more than two days old.
     */
    public static function format_mf_price( string $last_upd, array $data_regular ) : string
    {
        $class  = "";
        $text   = "";
        $status = $data_regular[ "status" ];
        $price  = $data_regular[ "price"  ];

        // Last update is more than two days old
        if ( $last_upd && strtotime( $last_upd ) < strtotime( "-2 days" ) )
            return "US$ {$price}";

        if ( $price )
        {
            $class = ( $status == "up" ) ? "upprice" : ( ( $status == "down" ) ? "downprice" : "evenprice" );
            $text  = "<mark class='{$class}'>US$ {$price}</mark>";
        }

        return $text;
    }

    /**
     * Determine the freight type based on the shipping method of an order.
     *
     * This function retrieves the shipping method from the order and determines
     * the freight type. It returns "1" for air shipping and "2" for other types.
     *
     * @since   1.0.0
     * @param   object  $order  The order object containing items and shipping details.
     * @return  string          The freight type ("1" for air shipping, "2" for others).
     */
    private static function get_freight_type( object $order )
    {
        $shippings    = $order->get_shipping_methods();
        $key          = array_key_first( $shippings );
        $shipping_id  = $shippings[ $key ]->get_method_id();

        $freight_type = $shipping_id == "muifabrica-air-shipping" ? "1" : "2";

        return $freight_type;
    }

    /**
     * Calculate freight costs for an order.
     *
     * This function calculates the freight costs for each product in an order
     * by interacting with the Muifabrica API.
     *
     * @since   1.0.0
     * @param   object  $order  The order object containing items and shipping details.
     * @return  array           An associative array with product SKUs as keys and their freight costs as values.
     */
    private static function get_freight( object $order )
    {
        $api          = new Muifabrica_Api();

        $freight_type = self::get_freight_type( $order );

        $freight      = [];
        $products     = [];

        foreach ( $order->get_items() as $item )
        {
            $product  = $item->get_product();

            if ( !$product ) continue;

            $supplied = $product->get_meta( \Muifabrica_Integration::METAS[ "supplied"     ] );

            if ( !$supplied ) continue;

            $products[] = [
                "product_attr_unique" => $product->get_meta( \Muifabrica_Integration::METAS[ "supplier_sku" ], true ),
                "product_id"          => $product->get_meta( \Muifabrica_Integration::METAS[ "product_id"   ], true ),
                "cart_num"            => $item->get_quantity(),
            ];
        }

        $freight      = [
            "freight_type" => ( int ) $freight_type,
            "products"     => $products,
        ];

        $response     = $api->confirm_order( $freight );

        if ( !$response ) return null;

        return $response;
    }

    /**
     * Updates the stock and price of a WooCommerce product based on MFI product data.
     *
     * This function updates the stock quantity and price of a WooCommerce product
     * using the data provided by the MFI (Muifabrica Integration) product. It also
     * updates meta data to track the price update status and the time of the update.
     *
     * @param   object  $wc_product     The WooCommerce product object to be updated.
     * @param   array   $mfi_product    The MFI product data containing the new price and stock.
     * @return  void
     */
    public static function update_stock_price( object $wc_product, array $mfi_product ) : void
    {
        $mfi_price       = ( float ) $mfi_product[ "price"    ];
        $mfi_ot_price    = ( float ) $mfi_product[ "ot_price" ];

        $wc_price        = ( float ) $wc_product->get_meta( \Muifabrica_Integration::METAS[ "regular_price"    ] );
        $wc_ot_price     = ( float ) $wc_product->get_meta( \Muifabrica_Integration::METAS[ "regular_ot_price" ] );

        $status_price    = ( $mfi_price    > $wc_price    ) ? "up" : ( ( $mfi_price    < $wc_price    ) ? "down" : "even" );
        $status_ot_price = ( $mfi_ot_price > $wc_ot_price ) ? "up" : ( ( $mfi_ot_price < $wc_ot_price ) ? "down" : "even" );

        $date            = date( "Y-m-d H:i:s" );

        $wc_product->update_meta_data( \Muifabrica_Integration::METAS[ "price_upd"         ], $date                      );
        $wc_product->update_meta_data( \Muifabrica_Integration::METAS[ "status_regular"    ], $status_price              );
        $wc_product->update_meta_data( \Muifabrica_Integration::METAS[ "regular_price"     ], $mfi_product[ "price"    ] );

        $wc_product->update_meta_data( \Muifabrica_Integration::METAS[ "ot_price_upd"      ], $date                      );
        $wc_product->update_meta_data( \Muifabrica_Integration::METAS[ "status_regular_ot" ], $status_ot_price           );
        $wc_product->update_meta_data( \Muifabrica_Integration::METAS[ "regular_ot_price"  ], $mfi_product[ "ot_price" ] );

        $wc_product->set_stock_quantity( $mfi_product[ "stock" ] );

        $wc_product->save();
    }
}
