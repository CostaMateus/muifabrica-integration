<?php
/**
 * Preview table class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Muifabrica_Integration\Classes\Tables
 * @version 	1.0.0
 */

namespace Muifabrica_Integration\Classes\Tables;

use Muifabrica_Integration\Api\Muifabrica_Api;
use Muifabrica_Integration\Classes\Helpers\MFI_Helper;

class MFI_Preview_table extends \WP_List_Table
{
    /**
     * Products data
     *
     * @since   1.0.0
     * @var     array|false
     */
    protected $products;

    /**
     * User data
     *
     * @since   1.0.0
     * @var     API
     */
    protected $api;

    /**
     * Constructor
     *
     * @since   1.0.0
     * @return  void
     */
	public function __construct()
    {
        parent::__construct();

        $this->api      = new Muifabrica_Api();
        $this->products = $this->api->get_products();
    }

    /**
     * Define the columns that are going to be used in the table
     *
     * @since   1.0.0
     * @return  array   The array of columns to use with the table
     */
    public function get_columns() : array
    {
        return [
            "cb"          => "<input type='checkbox' />",
            "thumbnail"   => "<span class='wc-image'></span>",
            "name"        => __( "Nome",              "muifabrica_integration" ),
            "description" => __( "Descrição",         "muifabrica_integration" ),
            "var_number"  => __( "Nº de variações",   "muifabrica_integration" ),
            "price"       => __( "Preço",             "muifabrica_integration" ),
            "ot_price"    => __( "Preço Commodities", "muifabrica_integration" ),
            "stock"       => __( "Estoque",           "muifabrica_integration" ),
        ];
    }

    /**
     * Decide which columns to activate the sorting functionality on
     *
     * @since   1.0.0
     * @return  array   The array of columns that can be sorted by the user
     */
    public function get_sortable_columns() : array
    {
        return [
            "name"       => [ "name",       false ],
            "var_number" => [ "var_number", false ],
            "price"      => [ "price",      false ],
            "ot_price"   => [ "ot_price",   false ],
            "stock"      => [ "stock",      false ],
        ];
    }

    /**
     * Sort users by name, supplier, sku, price or date.
     *
     * @since   1.0.0
     * @param   object $a
     * @param   object $b
     * @return  int|bool|null
     */
    public function usort_reorder( $a, $b ) : mixed
    {
        $order   = ( !empty( $_GET[ "order" ] ) ) ? $_GET[ "order" ] : "desc";

        $orderby = "name";

        if ( isset( $_GET[ "orderby" ] ) )
        {
            if ( $_GET[ "orderby" ] === "name" )
                $orderby = "store_name_pt";

            else
                $orderby = $_GET[ "orderby" ];
        }

        if ( in_array( $orderby, [ "price", "ot_price", "var_number", "stock" ] ) )
        {
            if ( $orderby == "var_number" )
            {
                $pa = count( $a->attrs );
                $pb = count( $b->attrs );
            }
            elseif ( $orderby == "stock" )
            {
                $pa = $a->stock;
                $pb = $b->stock;
            }
            else
            {
                $pa = ( float ) explode( "US$ ", $a->$orderby )[ 0 ];
                $pb = ( float ) explode( "US$ ", $b->$orderby )[ 0 ];
            }

            if ( strcasecmp( $pa, $pb ) !== 0 )
            {
                if ( $order === "asc" )
                    return $pa > $pb;
                else
                    return $pa < $pb;
            }
        }

        $result = strcasecmp( $a->store_name_pt, $b->store_name_pt );

        if ( $result !== 0 )
            return ( $order === "asc" ) ? $result : -$result;

        return null;
    }

    /**
     * Bulk actions options
     *
     * @since   1.0.0
     * @return  array
     */
    public function get_bulk_actions() : array
    {
        return [ "import" => __( "Importar produtos", "muifabrica_integration" ) ];
    }

    /**
     * No items to show
     *
     * @since   1.0.0
     * @return  void
     */
    public function no_items() : void
    {
        _e( "Nenhum produto encontrado.", "muifabrica_integration" );
    }

    /**
     * Prepare the table with different parameters, pagination, columns and table elements
     *
     * @since   1.0.0
     * @return  void
     */
    public function prepare_items() : void
    {
        $this->process_bulk_action();

        $hidden                = [];
        $columns               = $this->get_columns();
        $sortable              = $this->get_sortable_columns();

        $this->_column_headers = [ $columns, $hidden, $sortable ];

        usort( $this->products, [ &$this, "usort_reorder" ] );

        $per_page              = 50;
        $current_page          = $this->get_pagenum();
        $total_items           = count( $this->products );
        $total_pages           = ceil( $total_items / $per_page );

        if ( $total_items >= 1 )
        {
            $this->products = array_chunk( $this->products, $per_page )[ $current_page - 1 ];

            if ( empty( $paged ) || !is_numeric( $paged ) || $paged<=0 )
                $paged = 1;

            $this->set_pagination_args( [
                "total_items" => $total_items,
                "total_pages" => $total_pages,
                "per_page"    => $per_page,
            ] );

            $this->items    = $this->products;
        }
        else
        {
            $this->items    = [];
        }
    }

    /**
     * Process the bulk actions
     *
     * @since   1.0.0
     * @return  void
     */
    public function process_bulk_action() : void
    {
        if ( isset( $_POST[ "_wpnonce" ] ) && !empty( $_POST[ "_wpnonce" ] ) )
        {
            $nonce  = htmlspecialchars( $_POST[ "_wpnonce" ] );
            $action = "bulk-" . $this->_args[ "plural" ];

            if ( !wp_verify_nonce( $nonce, $action ) ) wp_die( "Nope! Security check failed!" );
        }

        $action   = $this->current_action();
        $actions  = array_keys( $this->get_bulk_actions() );

        if ( !in_array( $action, $actions ) ) return;

        $products = array_filter( $this->products, function( $p )
        {
            return in_array( $p->id, $_POST[ "products" ] );
        } );

        $products = json_decode( json_encode( $products ), true );

        $this->process_import_products( $products );
    }

    /**
     * Unmapped columns
     *
     * @since   1.0.0
     * @param   array|object    $item
     * @param   string          $column_name
     * @return  mixed
     */
    public function column_default( $item, $column_name ) : mixed
    {
        switch( $column_name )
        {
            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
            break;
        }
    }

    /**
     * Checkbox column
     *
     * @since   1.0.0
     * @param   array|object    $item
     * @return  string
     */
    public function column_cb( $item ) : string
    {
        return sprintf( "<input type='checkbox' name='products[]' value='%s' />", $item->id );
    }

    /**
     * Thumbnail column
     *
     * @since   1.0.0
     * @param   array|object    $item
     * @return  string
     */
    public function column_thumbnail( $item ) : string
    {
        $src = "/wp-content/uploads/woocommerce-placeholder-150x150.png";

        if ( $item->image )
            $src = $item->image;

        return "<img width='40' height='40' src='{$src}' class='attachment-40x40 size-40x40'
            decoding='async' loading='lazy' sizes='(max-width: 40px) 100vw, 40px' >";
    }

    /**
     * Name column
     *
     * @since   1.0.0
     * @param   array|object    $item
     * @return  string
     */
    public function column_name( $item ) : string
    {
        return "<strong>{$item->store_name_pt}</strong>";
    }

    /**
     * Description column
     *
     * @since   1.0.0
     * @param   array|object    $item
     * @return  string
     */
    public function column_description( $item ) : string
    {
        $id      = "preview-description-modal-{$item->id}";
        $text    = __( "Ver descrição", "muifabrica_integration" );
        $content = html_entity_decode( $item->description_pt );

        return "<div>
            <div id='{$id}' style='display:none;' >{$content}</div>
            <a href='#TB_inline?&inlineId={$id}' class='thickbox'>{$text}</a>
        </div>";
    }

    /**
     * Variations quantity column
     *
     * @since   1.0.0
     * @param   array|object    $item
     * @return  string
     */
    public function column_var_number( $item ) : string
    {
        return count( $item->attrs );
    }

    /**
     * Price column
     *
     * @since   1.0.0
     * @param   array|object    $item
     * @return  string
     */
    public function column_price( $item ) : string
    {
        return "US$ {$item->price}";
    }

    /**
     * Price column
     *
     * @since   1.0.0
     * @param   array|object    $item
     * @return  string
     */
    public function column_ot_price( $item ) : string
    {
        return "US$ {$item->ot_price}";
    }

    /**
     * Stock column
     *
     * @since   1.0.0
     * @param   array|object    $item
     * @return  string
     */
    public function column_stock( $item ) : string
    {
        if ( $item->stock > 0 )
        {
            $stock = __( "Em estoque",      "muifabrica_integration" );
            $class = "instock";
        }
        else
        {
            $stock = __( "Fora de estoque", "muifabrica_integration" );
            $class = "outofstock";
        }

        return "<mark class='{$class}'>{$stock}</mark> ({$item->stock})";
    }

    /**
     *
     * @since   1.0.0
     * @return  void
     */
    public function extra_tablenav( $which ) : void
    {
        // if ( $which == "top"    )
        // if ( $which == "bottom" )
    }

    /**
     * TODO rever uso da func
     *
     * Get products from supplier to preview
     *
     * @since   1.0.0
     * @return  array
     */
    public function get_preview_products() : array
    {
        if ( !$this->_url || !$this->_secret || !$this->_secret_s ) return [];

        $url      = "{$this->_url}/wp-json/dsm/v1/products";

        // Get supplier products
        $response = wp_remote_get( $url, $this->_req_args );

        if ( is_wp_error( $response ) )
        {
            \Muifabrica_Integration::log(
                "error",
                "Supplier ID {$this->supplier->ID} - api_query_error GET - " .
                $response->get_error_message() . " - " .
                json_encode( [ "url" => $url ] ),
                true
            );

            return [];
        }

        $data     = json_decode( $response[ "body" ] );

        if ( $data->code == "dsm_rest_cannot_view" )
        {
            // call error / no post
            return [];
        }
        else if ( $data->code == "dsm_rest_products_v1_invalid_email" )
        {
            // config error / account not created
            return [];
        }
        else if ( $data->code == "dsm_rest_products_v1_invalid_secret" )
        {
            // config error / invalid access key
            return [];
        }

        $products = $data->data;

        return $this->filter_products( $products );
    }

    /**
     * TODO rever uso da func
     *
     * Filters the products that have already been imported.
     *
     * This function filters out the products that have already been imported from the given list of products.
     *
     * @since   1.0.0
     * @param   array   $products   An array of product data to be filtered
     * @return  array               An array of product data that have not been imported yet
     */
    public function filter_products( $products ) : array
    {
        foreach ( $products as $key => $product )
        {
            $product_id = wc_get_product_id_by_sku( $this->_prefix . $product->sku );

            if ( $product_id ) unset( $products[ $key ] );
        }

        return $products;
    }

    /**
     * TODO rever uso da func
     *
     * Import products.
     *
     * This function processes the import of products from the supplier.
     *
     * @since   1.0.0
     * @param   array   $products   An array of product data to be imported
     * @return  void
     */
    private function process_import_products( $products ) : void
    {
        $errors[ $this->supplier->ID ] = [
            "errors"   => 0,
            "warnings" => 0,
            "code"     => 0,
            "count"    => 0,
            "user"     => $this->supplier->user_login,
        ];

		// Disable permissions check locally
		add_filter( "woocommerce_rest_check_permissions", "__return_true" );

        $imported = [];

        foreach ( $products as $aux_product )
        {
            if ( wc_get_product_id_by_sku( $this->_prefix . $aux_product[ "sku" ] ) ) continue;

            $success    = MFI_Helper::create_product( $aux_product, $this->_prefix, $this->supplier->ID );

            if ( !$success )
            {
                $errors[ $this->supplier->ID ][ "warnings" ]++;
                continue;
            }

            $imported[] = $aux_product[ "sku" ];
        }

        // Inform supplier about imported products
        if ( !empty( $imported ) ) $this->prepare_to_send_imported_products( $imported );

        $count   = count( $products ) - $errors[ $this->supplier->ID ][ "warnings" ];

        \Muifabrica_Integration::log( "info", "Supplier ID {$this->supplier->ID} - imported {$count} products" );

		// Remove the filter.
		remove_filter( "woocommerce_rest_check_permissions", "__return_true" );

        $errors[ $this->supplier->ID ][ "count" ] = $count;

        $warning = max( array_column( $errors, "warnings" ) ) == 0;
        $error   = max( array_column( $errors, "errors"   ) ) == 0;

        if ( $warning && $error )
        {
            MFI_Helper::redirect( "success", $errors );
            return;
        }
        else if ( !$warning )
        {
            MFI_Helper::redirect( "warning", $errors );
            return;
        }

        MFI_Helper::redirect( "error", $errors );
        return;
    }

    /**
     * TODO rever uso da func
     *
     * Inform supplier about imported products.
     *
     * This function prepares to send a notification to the supplier about the products that have been imported.
     *
     * @since   1.0.0
     * @param   array   $skus   An array of SKUs (Stock Keeping Units) for the imported products
     * @return  void
     */
    private function prepare_to_send_imported_products( $skus ) : void
    {
        $url = "{$this->_url}/wp-json/dsm/v1/products/imported";

        MFI_Helper::send_imported_products( $this->supplier->ID, $skus, $this->_req_args, $url );
    }
}
