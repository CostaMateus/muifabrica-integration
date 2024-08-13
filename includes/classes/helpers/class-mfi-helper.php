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

class MFI_Helper
{
    /**
     * TODO rever uso da func
     *
     * Translate product data into a format suitable for WooCommerce API.
     *
     * This function takes an array of product data and transforms it into a format that can be sent to the WooCommerce API.
     * It removes unnecessary fields, creates categories, sets the product status to "draft", and formats attributes and metadata.
     *
     * @since   1.0.0
     * @param   array   $product    The product data array.
     * @return  array               The transformed product data array.
     */
    public static function translate_product( array $product ) : array
    {
        $categories = [];

        // Remove the "id" field from the product data..
        unset( $product[ "id" ] );

        // Loop through the product images and remove unnecessary fields.
        foreach( $product[ "images" ] as &$image )
            foreach ( $image as $key => $value )
                if ( !in_array( $key, [ "alt", "src", "name" ] ) )
                    unset( $image[ $key ] );

        // Check if general categories should be created.
        // And loop through the product categories and create or find them.
        if ( get_option( "_dsm_categories_general" ) )
            foreach ( $product[ "categories" ] as $category )
                $categories[] = self::find_or_create_category( $category[ "name" ], $category[ "slug" ] );

        // Set the product categories and status.
        $product[ "categories" ] = $categories;
        $product[ "status"     ] = "draft";

        // Format the product attributes.
        foreach ( $product[ "attributes" ] as $key => $attribute )
        {
            $product[ "attributes" ][ $key ] = [
                "name"      => ucfirst( strtolower( $attribute[ "name" ] ) ),
                "position"  => $attribute[ "position"  ],
                "visible"   => $attribute[ "visible"   ],
                "variation" => $attribute[ "variation" ],
                "options"   => $attribute[ "options"   ],
            ];
        }

        // Set the product metadata.
        $product[ "meta_data" ] = [
            [
                "key"   => "_dsm_supplier_regular_price",
                "value" => $product[ "regular_price" ],
            ], [
                "key"   => "_dsm_supplier_sale_price",
                "value" => $product[ "sale_price"    ],
            ], [
                "key"   => "_dsm_supplier_sku",
                "value" => $product[ "sku"           ],
            ], [
                "key"   => "_dsm_supplier_status_regular",
                "value" => "even",
            ], [
                "key"   => "_dsm_supplier_status_sale",
                "value" => "even",
            ],
        ];

        return $product;
    }

    /**
     * TODO rever uso da func
     *
     * Translate variation data into a format suitable for WooCommerce API.
     *
     * This function takes an array of variation data, along with images, SKU, SKU prefix, and product ID.
     * It formats the variations to be sent to the WooCommerce API, including assigning images, formatting attributes, and metadata.
     *
     * @since   1.0.0
     * @param   array   $variations     The array of variation data.
     * @param   array   $images         The array of product images.
     * @param   string  $sku            The product SKU.
     * @param   string  $sku_prefix     The SKU prefix.
     * @param   int     $product_id     The product ID.
     * @return  array                   The transformed variation data array.
     */
    public static function translate_variations( array $variations, array $images, string $sku, string $sku_prefix, int $product_id ) : array
    {
        $counter = 1;

        foreach ( $variations as &$variation )
        {
            // Set the parent ID, description, and regular price for the variation.
            $variation[ "parent_id"     ] = $product_id;
            $variation[ "description"   ] = $variation[ "variation_description" ];
            $variation[ "regular_price" ] = ( string ) $variation[ "display_regular_price" ];

            // Find the image for the variation or set a new one.
            $index = array_search( $variation[ "image" ][ "title" ], array_column( $images, "name" ) );

            if ( $index !== false )
            {
                $variation[ "image" ] = [
                    "id" => $images[ $index ][ "id" ]
                ];
            }
            else
            {
                $variation[ "image" ] = [
                    "alt"  => $variation[ "image" ][ "alt"   ],
                    "name" => $variation[ "image" ][ "title" ],
                    "src"  => $variation[ "image" ][ "url"   ],
                ];
            }

            // Remove unnecessary fields from the variation data.
            unset(
                $variation[ "variation_id"          ],
                $variation[ "variation_description" ],
                $variation[ "display_regular_price" ]
            );

            // Format the variation attributes.
            foreach ( $variation[ "attributes" ] as $key => $attribute )
            {
                unset( $variation[ "attributes" ][ $key ] );

                $variation[ "attributes" ][] = [
                    "name"   => ucfirst( strtolower( $key       ) ),
                    "option" => ucfirst( strtolower( $attribute ) )
                ];
            }

            // Set the variation metadata.
            $variation[ "meta_data" ] = [
                [
                    "key"   => "_dsm_supplier_regular_price",
                    "value" => $variation[ "regular_price" ],
                ], [
                    "key"   => "_dsm_supplier_sale_price",
                    "value" => $variation[ "sale_price"    ] ?? "",
                ], [
                    "key"   => "_dsm_supplier_sku",
                    "value" => $variation[ "sku"           ],
                ], [
                    "key"   => "_dsm_supplier_status_regular",
                    "value" => "even",
                ], [
                    "key"   => "_dsm_supplier_status_sale",
                    "value" => "even",
                ],
            ];

            // Update the variation SKU if it matches the product SKU or SKU prefix.
            if ( in_array( $variation[ "sku" ], [ $sku, "{$sku_prefix}{$sku}" ] ) )
            {
                $variation[ "sku" ] = "{$sku_prefix}{$sku}{$counter}";
                $counter++;
            }
        }

        return $variations;
    }

    /**
     * TODO rever uso da func
     *
     * Find or create a product category based on the given name and slug.
     *
     * This function checks if a product category already exists with the given slug.
     * If it doesn't exist, it creates a new category with the provided name and slug.
     *
     * @param   string  $name   The category name.
     * @param   string  $slug   The category slug.
     * @return  array           An array containing the category ID.
     */
    public static function find_or_create_category( string $name, string $slug ) : array
    {
        $result = [];

        // Get the term object for the given slug and taxonomy 'product_cat'.
        $cat    = get_term_by( "slug", $slug, "product_cat" );

        // If the term object exists, set the result with the term ID.
        if ( $cat instanceof \WP_Term )
        {
            $result = [ "id" => $cat->term_id ];
        }
        else
        {
            // If the term doesn't exist, create a new term with the given name and slug.
            $cat = wp_insert_term( $name, "product_cat", [ "slug" => $slug ] );

            // If the term creation was successful, set the result with the new term ID.
            if ( !is_wp_error( $cat ) )
                $result = [ "id" => $cat[ "term_id" ] ];
        }

        return $result;
    }

    /**
     * TODO rever uso da func
     *
     * Process regular price synchronization for a product.
     *
     * This function compares the current regular price with the new price and updates the product metadata accordingly.
     *
     * @since   1.0.0
     * @param   string|float    $regular    The current regular price.
     * @param   string|float    $new_price  The new regular price.
     * @param   string          $status     The current status of the regular price.
     * @param   object          $product    The WooCommerce product object.
     * @return  bool                        True if the regular price was changed, false otherwise.
     */
    public static function process_regular_price( string|float $regular, string|float $new_price, string $status, object &$product ) : bool
    {
        $change = false;

        // Call the process_price function to handle the regular price update.
        self::process_price( $change, $status, $product, $regular, $new_price, "_dsm_supplier_regular_price" );

        // If there was a change in the regular price or status.
        if ( $change )
        {
            // Update the product metadata with the new status and timestamp.
            $product->update_meta_data( "_dsm_supplier_status_regular", $status               );
            $product->update_meta_data( "_dsm_supplier_price_upd",      date( "Y-m-d H:i:s" ) );
        }

        return $change;
    }

    /**
     * TODO rever uso da func
     *
     * Process sale price synchronization for a product.
     *
     * This function compares the current sale price with the new price and updates the product metadata accordingly.
     *
     * @since   1.0.0
     * @param   string|float    $regular    The current regular price.
     * @param   string|float    $sale       The current sale price.
     * @param   string          $new_price  The new sale price.
     * @param   string          $status     The current status of the sale price.
     * @param   object          $product    The WooCommerce product object.
     * @return  bool                        True if the sale price was changed, false otherwise.
     */
    public static function process_sale_price( string|float $regular, string|float $sale, string $new_price, string $status, object &$product ) : bool
    {
        $change = false;

        // If both the current sale price and the new price are present.
        if ( $sale && $new_price )
        {
            // Call the process_price function to handle the sale price update.
            self::process_price( $change, $status, $product, $sale, $new_price, "_dsm_supplier_sale_price" );
        }
        elseif ( $sale && !$new_price )
        {
            // If the current sale price is present but the new price is not.
            $change = true;
            $status = "even";

            // Update the product metadata to remove the sale price.
            $product->update_meta_data( "_dsm_supplier_sale_price", null );
        }
        elseif ( !$sale && $new_price )
        {
            // If the current sale price is not present but the new price is.
            $change = true;

            // Set the new status based on whether the new price is higher or lower than the regular price.
            $status = ( $regular > $new_price ) ? "down" : "up";

            // Update the product metadata with the new sale price.
            $product->update_meta_data( "_dsm_supplier_sale_price", $new_price );
        }

        // If there was a change in the sale price or status.
        if ( $change )
        {
            // Update the product metadata with the new status and timestamp.
            $product->update_meta_data( "_dsm_supplier_status_sale", $status               );
            $product->update_meta_data( "_dsm_supplier_price_upd",   date( "Y-m-d H:i:s" ) );
        }

        return $change;
    }

    /**
     * TODO rever uso da func
     *
     * Helper function to process price updates.
     *
     * This function compares the current price with the new price and updates the product metadata accordingly.
     *
     * @since   1.0.0
     * @param   bool            $has_change     A reference to a boolean indicating if a change occurred.
     * @param   string          $status         A reference to a string to store the new status.
     * @param   object          $product        The WooCommerce product object.
     * @param   string|float    $current        The current price.
     * @param   string|float    $new            The new price.
     * @param   string          $meta_key       The meta key for the price.
     * @return  void
     */
    public static function process_price( bool &$has_change, string &$status, object &$product, string|float $current, string|float $new, string $meta_key ) : void
    {
        // If the current price is different from the new price.
        if ( $current != $new )
        {
            $has_change = true;

            // Set the new status based on whether the new price is higher or lower.
            $status     = ( $current > $new ) ? "down" : "up";

            // Update the product metadata with the new price.
            $product->update_meta_data( $meta_key, $new );
        }
        else
        {
            // If the current price is the same as the new price.
            if ( $status != "even" )
            {
                $has_change = true;
                $status     = "even";
            }
        }
    }

    /**
     * TODO rever uso da func
     *
     * Process product metadata.
     *
     * This function updates the metadata of a product with the provided metadata array.
     *
	 * @since 	1.0.0
     * @param   int     $product_id     The product ID.
     * @param   array   $metadata       The array of metadata.
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
     * TODO rever uso da func
     *
     * Inform the supplier about imported products.
     *
     * This function sends a POST request to a specific URL, informing the supplier about the imported product SKUs.
     *
     * @since   1.0.0
     * @param   int     $user_id    The supplier user ID.
     * @param   array   $skus       The array of product SKUs.
     * @param   array   $args       The array of arguments for the remote request.
     * @param   string  $url        The URL to send the request to.
     * @return  void
     */
    public static function send_imported_products( int $user_id, array $skus, array $args, string $url ) : void
    {
        // Prepare the request body with the SKUs in JSON format.
        $args[ "body"    ]                   = wp_json_encode( [ "skus" => $skus ] );
        $args[ "headers" ][ "Content-Type" ] = "application/json";

        // Send the POST request to the specified URL.
        $response = wp_remote_post( $url, $args );

        // Check if there was an error with the request.
        if ( is_wp_error( $response ) )
        {
            // Log the error message.
            \Muifabrica_Integration::log( "error", json_encode( [
                "ERROR",
                "Supplier ID {$user_id}",
                "api_query_error POST",
                json_encode( [ "url" => $url ] ),
                $response->get_error_message(),
            ] ), true );

            return;
        }

        // Get the response status code.
        $status   = wp_remote_retrieve_response_code( $response );
        $data     = json_decode( $response[ "body" ], true );

        // Check if the response status code is not 200 (OK).
        if ( $status != 200 )
        {
            // Log the error message with the response data.
            \Muifabrica_Integration::log( "error", json_encode( [
                "ERROR",
                "Supplier ID {$user_id}",
                "api_query_error POST",
                json_encode( [ "url" => $url ] ),
                json_encode( $data ),
            ] ), true );

            return;
        }

        // Log the response data.
        \Muifabrica_Integration::log( "info", json_encode( $data ) );
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
        // Prepare the data to be passed in the URL.
        $data = http_build_query( [ "data" => $data ] );

        // Construct the URL with the provided type and data.
        $url  = admin_url( "admin.php?page=" . \Muifabrica_Integration::DOMAIN. "&tab=preview&{$type}_message=DRPWE&{$data}" );

        // Redirect the user to the constructed URL
        if ( wp_redirect( $url ) ) exit();

        return;
    }

    /**
     * TODO rever uso da func
     *
     * Create a new product in WooCommerce.
     *
     * This function creates a new product in WooCommerce based on the provided product data, prefix, and user ID.
     * It translates the product data, creates the product using the WooCommerce API, and creates variations if the product is variable.
     *
	 * @since 	1.0.0
     * @param   array   $product    The product data array.
     * @param   string  $prefix     The prefix for the product SKU.
     * @param   int     $user_id    The supplier user ID.
     * @return  bool    True if the product was created successfully, false otherwise.
     */
    public static function create_product( array $product, string $prefix, int $user_id ) : bool
    {
        // Translate the product data.
        $t_product                  = self::translate_product( $product );

        // Prepare the product data for creating the product.
        $t_product[ "sku"       ]   = $prefix . $t_product[ "sku" ];
        $t_product[ "meta_data" ][] = [
            "key"   => "_dsm_supplier",
            "value" => $user_id,
        ];

        // Create a new WP_REST_Request for creating the product.
        $product_request            = new \WP_REST_Request( "POST", "/wc/v3/products" );
        $product_request->set_body_params( $t_product );

        // Send the request to create the product.
        $product_response           = rest_do_request( $product_request )->get_data();

        // Check if there was an error creating the product.
        if ( is_wp_error( $product_response ) )
        {
            // Log the error message.
            \Muifabrica_Integration::log( "error", "Supplier ID {$user_id} - error_create_product - " . $product_response->get_error_message(), true );
            \Muifabrica_Integration::log( "info", print_r( $product_response, true ), true );

            return false;
        }

        // Process the product metadata.
        self::process_product_metadata( $product_response[ "id" ], $t_product[ "meta_data" ] );

        // If the product is a variable product, create variations.
        if ( $product[ "type" ] == "variable" )
        {
            self::create_all_product_variations( $product_response[ "id" ] );

            $product       = wc_get_product( $product_response[ "id" ] );
            $variations_id = $product->get_children();

            foreach ( $variations_id as $variation_id )
            {
                $variation  = wc_get_product( $variation_id );

                $attributes = $variation->get_attributes();

                $has_change = false;

                foreach ( $t_product[ "variations" ] as $t_variation )
                {
                    $aux_attrs = array_combine(
                        array_map(
                            function ( $key ) { return str_replace( "attribute_", "", $key ); },
                            array_keys( $t_variation[ "attributes" ] )
                        ),
                        array_values( $t_variation[ "attributes" ] )
                    );

                    $all_equal = count( array_intersect_assoc( $attributes, $aux_attrs ) ) === count( $attributes );

                    if ( $all_equal )
                    {
                        if ( $variation->get_sku() != $t_variation[ "sku" ] )
                        {
                            $variation->set_sku( $prefix . $t_variation[ "sku" ] );
                            $variation->update_meta_data( "_dsm_supplier_sku", $t_variation[ "sku" ] );
                        }

                        $variation->update_meta_data( "_dsm_supplier", $user_id );

                        $variation->set_regular_price( ( string ) $t_variation[ "display_regular_price" ] );
                        $variation->set_sale_price( ( string ) $t_variation[ "display_price" ] );

                        $variation->set_manage_stock( $t_variation[ "manage_stock" ] );
                        $variation->set_stock_quantity( $t_variation[ "max_qty" ] );

                        $variation->set_weight( $t_variation[ "weight"     ]             );
                        $variation->set_length( $t_variation[ "dimensions" ][ "length" ] );
                        $variation->set_width(  $t_variation[ "dimensions" ][ "width"  ] );
                        $variation->set_height( $t_variation[ "dimensions" ][ "height" ] );

                        $images   = $product_response[ "images" ];
                        $index    = array_search( $t_variation[ "image" ][ "title" ], array_column( $images, "name" ) );

                        $image_id = ( $index !== false )
                                    ? $images[ $index ][ "id" ]
                                    : self::upload_file_by_url( $t_variation[ "image" ][ "url" ], $variation_id );

                        if ( $image_id ) $variation->set_image_id( $image_id );

                        $has_change = true;
                        break;
                    }
                }

                if ( $has_change ) $variation->save();
            }
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
}
