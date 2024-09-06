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

class MFI_Preview_Table extends \WP_List_Table
{
    /**
     * Prefix sku parent.
     *
     * @since   1.0.0
     * @var     string
     */
    protected $_prefix_parent = \Muifabrica_Integration::METAS[ "parent" ];

    /**
     * Prefix sku child.
     *
     * @since   1.0.0
     * @var     string
     */
    protected $_prefix_child  = \Muifabrica_Integration::METAS[ "child" ];

    /**
     * Products data.
     *
     * @since   1.0.0
     * @var     array|false
     */
    protected $products;

    /**
     * User data.
     *
     * @since   1.0.0
     * @var     API
     */
    protected $api;

    /**
     * Constructor.
     *
     * Initializes the class and sets up necessary properties.
     *
     * @since   1.0.0
     * @return  void
     */
	public function __construct()
    {
        parent::__construct();

        $this->api      = new Muifabrica_Api();

        $aux_products   = $this->api->get_products();
        $this->products = $this->filter_products( $aux_products );
    }

    /**
     * Define the columns that are going to be used in the table.
     *
     * Returns an array of columns to display in the table.
     *
     * @since   1.0.0
     * @return  array   The array of columns to use with the table
     */
    public function get_columns() : array
    {
        return [
            "cb"          => "<input type='checkbox' />",
            "thumbnail"   => "<span class='wc-image'></span>",
            "name"        => __( "Nome",                 "muifabrica_integration" ),
            "description" => __( "Descrição",            "muifabrica_integration" ),
            "var_number"  => __( "Nº de variações",      "muifabrica_integration" ),
            "price"       => __( "MF Preço",             "muifabrica_integration" ),
            "ot_price"    => __( "MF Preço Commodities", "muifabrica_integration" ),
            "stock"       => __( "Estoque",              "muifabrica_integration" ),
        ];
    }

    /**
     * Decide which columns to activate the sorting functionality on.
     *
     * Returns an array of columns that can be sorted by the user.
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
     * Sort products by name, supplier, SKU, price, or stock.
     *
     * This function is used to reorder the products based on the selected column and order.
     *
     * @since   1.0.0
     * @param   object $a  The first object to compare
     * @param   object $b  The second object to compare
     * @return  int|bool|null  Returns a comparison result or null if no difference
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
     * Bulk actions options.
     *
     * Returns an array of bulk action options available for the table.
     *
     * @since   1.0.0
     * @return  array
     */
    public function get_bulk_actions() : array
    {
        return [ "import" => __( "Importar produtos", "muifabrica_integration" ) ];
    }

    /**
     * Display a message when there are no items to show.
     *
     * @since   1.0.0
     * @return  void
     */
    public function no_items() : void
    {
        _e( "Nenhum produto encontrado.", "muifabrica_integration" );
    }

    /**
     * Prepare the table with different parameters, including pagination, columns, and table elements.
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
     * Process bulk actions.
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
     * Unmapped Columns.
     *
     * This function handles columns that don't have specific functions mapped to them.
     * It returns the entire item array or object as a string for troubleshooting purposes.
     *
     * @since   1.0.0
     * @param   array|object    $item        The item for which the column data is being generated.
     * @param   string          $column_name The name of the column being displayed.
     * @return  mixed                        The item's data or a string representation of it.
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
     * Checkbox Column
     *
     * This function generates the display content for the "Checkbox" column in the table.
     * It returns a checkbox input element with the item's ID as the value.
     *
     * @since   1.0.0
     * @param   array|object    $item   The item for which the column data is being generated.
     * @return  string                  The HTML for the checkbox input element.
     */
    public function column_cb( $item ) : string
    {
        return sprintf( "<input type='checkbox' name='products[]' value='%s' />", $item->id );
    }

    /**
     * Thumbnail Column.
     *
     * This function generates the display content for the "Thumbnail" column in the table.
     * It displays a 40x40 pixel image, using a placeholder image if no image is provided for the item.
     *
     * @since   1.0.0
     * @param   array|object    $item   The item for which the column data is being generated.
     * @return  string                  The HTML for the thumbnail image.
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
     * Name Column.
     *
     * This function generates the display content for the "Name" column in the table.
     * It returns the store name of the item in bold text.
     *
     * @since   1.0.0
     * @param   array|object    $item   The item for which the column data is being generated.
     * @return  string                  The HTML for the store name in bold text.
     */
    public function column_name( $item ) : string
    {
        return "<strong>{$item->store_name_pt}</strong>";
    }

    /**
     * Description Column.
     *
     * This function generates the display content for the "Description" column in the table.
     * It creates a hidden modal containing the item's description and provides a link to view the description using a Thickbox modal.
     *
     * @since   1.0.0
     * @param   array|object    $item   The item for which the column data is being generated.
     * @return  string                  The HTML for the description modal and link.
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
     * Variations Quantity Column.
     *
     * This function generates the display content for the "Variations Quantity" column in the table.
     * It returns the number of variations available for the item.
     *
     * @since   1.0.0
     * @param   array|object    $item   The item for which the column data is being generated.
     * @return  string                  The number of variations as a string.
     */
    public function column_var_number( $item ) : string
    {
        return count( $item->attrs );
    }

    /**
     * Price Column.
     *
     * This function generates the display content for the "Price" column in the table.
     * It formats the price with a "US$" prefix.
     *
     * @since   1.0.0
     * @param   array|object    $item   The item for which the column data is being generated.
     * @return  string                  The formatted price string.
     */
    public function column_price( $item ) : string
    {
        return "US$ {$item->price}";
    }

    /**
     * Commodity Price Column.
     *
     * This function generates the display content for the "Commodity Price" column in the table.
     * It formats the price with a "US$" prefix.
     *
     * @since   1.0.0
     * @param   array|object    $item   The item for which the column data is being generated.
     * @return  string                  The formatted price string.
     */
    public function column_ot_price( $item ) : string
    {
        return "US$ {$item->ot_price}";
    }

    /**
     * Stock Status Column.
     *
     * This function generates the display content for the "Stock" column in the table.
     * It checks the stock quantity of the item and returns a string indicating whether
     * the item is "In Stock" or "Out of Stock" along with the stock quantity.
     *
     * @since   1.0.0
     * @param   array|object    $item   The item for which the column data is being generated.
     * @return  string                  The stock status with the quantity.
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
     * Extra Table Navigation.
     *
     * This function allows the addition of extra controls or content at the top or bottom of the table.
     *
     * @since   1.0.0
     * @param   string  $which  Indicates whether the current position is "top" or "bottom" of the table.
     * @return  void
     */
    public function extra_tablenav( $which ) : void
    {
        if ( $which == "top" )
        {
            if ( "BRL" === get_option( "woocommerce_currency" ) )
            {
                $dollar = $this->api->get_dollar_price();;

                echo "<div class='mfi-mb-9 mfi-float-left' >
                        <p class='mfi-my-5' >
                            <strong>Cotação do dólar:</strong>
                            R$ {$dollar}
                            <small>(Atualizado a cada 15 minutos)</small>
                        </p>
                    </div>";
            }
        }

        // if ( $which == "bottom" )
    }

    /**
     * Filters the products that have already been imported.
     *
     * This function filters out the products that have already been imported from the given list of products.
     *
     * @since   1.0.0
     * @param   array|false     $products   An array of product data to be filtered.
     * @return  array                       An array of product data that have not been imported yet.
     */
    public function filter_products( array|false $products ) : array
    {
        if ( $products === false )
            return [];

        foreach ( $products as $key => $product )
        {
            $product_id = wc_get_product_id_by_sku( $this->_prefix_parent . $product->attrs[ 0 ]->unique );

            if ( $product_id ) unset( $products[ $key ] );
        }

        return $products;
    }

    /**
     * Import products.
     *
     * This function processes the import of products from the supplier.
     * It filters out already imported products, generates WooCommerce products for each new item,
     * and logs the result of the import process.
     *
     * @since   1.0.0
     * @param   array   $products   An array of product data to be imported.
     * @return  void
     */
    private function process_import_products( $products ) : void
    {
        $count    = 0;
        $warnings = 0;

		// Disable permissions check locally
		add_filter( "woocommerce_rest_check_permissions", "__return_true" );

        foreach ( $products as $aux_product )
        {
            if ( wc_get_product_id_by_sku( $this->_prefix_parent . $aux_product[ "attrs" ][ 0 ][ "unique" ] ) ) continue;

            $prefixes = [
                "parent" => $this->_prefix_parent,
                "child"  => $this->_prefix_child,
            ];

            $success  = MFI_Helper::create_product( $aux_product, $prefixes );

            if ( !$success )
                $warnings++;
        }

        $count    = count( $products ) - $warnings;

        \Muifabrica_Integration::log( "info", "MFI Imported {$count} products" );

		// Remove the filter.
		remove_filter( "woocommerce_rest_check_permissions", "__return_true" );

        $data     = [
            "warnings" => $warnings,
            "count"    => $count,
            "code"     => ( $warnings == 0 ) ? "success" : "warning",
        ];

        MFI_Helper::redirect( $data[ "code" ], $data );
        return;
    }
}
