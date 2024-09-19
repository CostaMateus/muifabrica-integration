<?php
/**
 * Products table class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Muifabrica_Integration\Classes\Tables
 * @version 	1.0.0
 */

namespace Muifabrica_Integration\Classes\Tables;

use Muifabrica_Integration\Api\Muifabrica_Api;
use Muifabrica_Integration\Classes\Helpers\MFI_Helper;

class MFI_Products_Table extends \WP_List_Table
{
    /**
     * Products data
     *
     * @since   1.0.0
     * @var     array|stdClass
     */
    protected $products;

    /**
     * Constructor.
     *
     * @since   1.0.0
     * @return  void
     */
	public function __construct()
    {
        parent::__construct();

        $this->load_products();
    }

    /**
     * Load the products data.
     *
     * This method retrieves the product data from the database and associates each product with its corresponding supplier.
     *
     * @since   1.0.0
     * @return  void
     */
    private function load_products() : void
    {
        $this->products = wc_get_products( [
            "limit"        => -1,
            "meta_key"     => \Muifabrica_Integration::METAS[ "supplied" ],
            "meta_compare" => "EXISTS",
        ] );
    }

    /**
     * Searches for products by name or SKU.
     *
     * @param   string  $search  The search term.
     * @return  array            The array of matching products.
     */
    private function search_products( string $search = "" ) : array
    {
        if ( empty( $search ) )
            return $this->products;

        $search   = wp_unslash( trim( $search ) );

        $products = [];

        foreach ( $this->products as $index => $product )
        {
            if ( stripos( $product->get_name(), $search ) !== false )
                $products[ $index ] = $product;

            elseif (  stripos( $product->get_sku(), $search ) !== false  )
                $products[ $index ] = $product;
        }

        return $products;
    }

    /**
     * Define the columns that are going to be used in the table.
     *
     * This method defines the columns that will be displayed in the table.
     *
     * @since   1.0.0
     * @return  array   The array of columns to use with the table.
     */
    public function get_columns() : array
    {
        return [
            // "cb"         => "<input type='checkbox' name='users[]' />",
            "thumbnail"  => "<span class='wc-image tips'></span>",
            "name"       => __( "Nome",                "muifabrica_integration" ),
            "sku"        => __( "SKU",                 "muifabrica_integration" ),
            "stock"      => __( "Estoque",             "muifabrica_integration" ),
            "price"      => __( "Preço",               "muifabrica_integration" ),
            "m_price"    => __( "MF Preço",            "muifabrica_integration" ),
            "m_ot_price" => __( "MF Preço de Mercado", "muifabrica_integration" ),
            "categories" => __( "Categorias",          "muifabrica_integration" ),
            "date"       => __( "Última alteração",    "muifabrica_integration" ),
        ];
    }

    /**
     * Decide which columns to activate the sorting functionality on.
     *
     * This method defines the columns that can be sorted by the user in the table.
     *
     * @since   1.0.0
     * @return  array   The array of columns that can be sorted by the user.
     */
    public function get_sortable_columns() : array
    {
        return [
            "name"       => [ "name",       false ],
            "sku"        => [ "sku",        false ],
            "stock"      => [ "stock",      false ],
            "price"      => [ "price",      false ],
            "m_price"    => [ "m_price",    false ],
            "m_ot_price" => [ "m_ot_price", false ],
            "categories" => [ "categories", false ],
            "date"       => [ "date",       false ],
        ];
    }

    /**
     * Sort users by name, sku, price or date.
     *
     * This method is a custom sorting function used to sort the users (products) based on the selected column and order.
     *
     * @since   1.0.0
     * @param   object $a
     * @param   object $b
     * @return  int|bool|null
     */
    public function usort_reorder( $a, $b ) : mixed
    {
        $order   = ( !empty( $_GET[ "order" ] ) ) ? $_GET[ "order" ] : "desc";
        $orderby = "date";

        if ( isset( $_GET[ "orderby" ] ) )
        {
            if ( $_GET[ "orderby" ] === "name" )
                $orderby = "get_name";

            else if ( $_GET[ "orderby" ] === "sku"  )
                $orderby = "get_sku";

            else
                $orderby = $_GET[ "orderby" ];
        }

        if ( $orderby == "date" )
        {
            $ta = strtotime( $a->get_date_modified() );
            $tb = strtotime( $b->get_date_modified() );

            if ( strcasecmp( $ta, $tb ) !== 0 )
            {
                if ( $order === "asc" )
                    return $ta - $tb;
                else
                    return $tb - $ta;
            }

            $orderby = "price";
        }

        if ( in_array( $orderby, [ "price", "stock" ] ) )
        {
            if ( $orderby == "price" )
                $func = "get_price";
            elseif ( $orderby == "stock" )
                $func = "get_stock_quantity";

            $pa = ( float ) $a->$func();
            $pb = ( float ) $b->$func();

            if ( strcasecmp( $pa, $pb ) !== 0 )
            {
                if ( $order === "asc" )
                    return $pa > $pb;
                else
                    return $pa < $pb;
            }
        }

        $columns = [ "get_name", "get_sku" ];

        foreach ( $columns as $column )
        {
            $result = strcasecmp( $a->$column, $b->$column );

            if ( $result !== 0 )
                return ( $order === "asc" ) ? $result : -$result;
        }

        return null;
    }

    /**
     * Bulk actions options.
     *
     * This method is intended to define the bulk actions that can be performed on the selected items in the table.
     * However, it is currently empty and does not provide any bulk action options.
     *
     * @since   1.0.0
     * @return  void
     */
    public function get_bulk_actions() : void
    {
        //
    }

    /**
     * No items to show.
     *
     * This function is called when there are no products to display in the table.
     * It simply outputs the message "No products found." using the WordPress translation function.
     *
     * @since   1.0.0
     * @return  void
     */
    public function no_items() : void
    {
        _e( "Nenhum produto encontrado.", "muifabrica_integration" );
    }

    /**
     * Prepare the table with different parameters, pagination, columns and table elements.
     *
     * This function prepares the table by setting up various parameters such as columns, sorting,
     * filtering, pagination, and chunking the data into pages.
     *
     * @since   1.0.0
     * @return  void
     */
    public function prepare_items() : void
    {
        $products              = $this->search_products( $_POST[ "s" ] ?? "" );

        $hidden                = [];
        $columns               = $this->get_columns();
        $sortable              = $this->get_sortable_columns();

        $this->_column_headers = [ $columns, $hidden, $sortable ];

        usort( $products, [ &$this, "usort_reorder" ] );

        $per_page              = 25;
        $current_page          = $this->get_pagenum();
        $total_items           = count( $products );
        $total_pages           = ceil( $total_items / $per_page );

        if ( $total_items >= 1 )
        {
            $products = array_chunk( $products, $per_page )[ $current_page - 1 ];

            if ( empty( $paged ) || !is_numeric( $paged ) || $paged <= 0 )
                $paged = 1;

            $this->set_pagination_args( [
                "total_items" => $total_items,
                "total_pages" => $total_pages,
                "per_page"    => $per_page,
            ] );

            $this->items    = $products;
        }
        else
        {
            $this->items    = [];
        }
    }

    /**
     * Unmapped columns.
     *
     * This function handles the display of unmapped columns in the table.
     * If the column is not recognized, it will print the entire item array for troubleshooting purposes.
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
                //Show the whole array for troubleshooting purposes
                return print_r( $item, true ) ;
            break;
        }
    }

    /**
     * Checkbox column.
     *
     * This function is responsible for rendering the checkbox column in the table.
     * It is currently commented out, but it would typically return an HTML checkbox input
     * with the product ID as the value, allowing for bulk actions on selected products.
     *
     * @since   1.0.0
     * @param   array|object $item   The product item being displayed in the table row.
     * @return  string               The HTML markup for the checkbox column.
     */
    public function column_cb( $item ) : void
    {
        // if ( $this->preview )
        //     return sprintf( "<input type='checkbox' name='products[]' value='%s' />", $item->ID );
    }

    /**
     * Thumbnail column.
     *
     * This function renders the thumbnail column in the table.
     *
     * @since   1.0.0
     * @param   array|object $item   The product item being displayed in the table row.
     * @return  string               The HTML markup for the thumbnail column.
     */
    public function column_thumbnail( $item ) : string
    {
        if ( $item->get_image_id() )
            return wp_get_attachment_image( $item->get_image_id(), [ 40, 40 ], true );

        return "<img width='40' height='40' src='/wp-content/uploads/woocommerce-placeholder-150x150.png' class='attachment-40x40 size-40x40'
            decoding='async' loading='lazy' sizes='(max-width: 40px) 100vw, 40px' >";
    }

    /**
     * Name column.
     *
     * This function renders the name column in the table.
     *
     * @since   1.0.0
     * @param   array|object    $item   The product item being displayed in the table row.
     * @return  string                  The HTML markup for the name column.
     */
    public function column_name( $item ) : string
    {
        $status          = $item->get_status();
        $class           = "";
        $arr_status      = [
            "publish" => __( "Published",      "muifabrica_integration" ),
            "pending" => __( "Pending review", "muifabrica_integration" ),
            "draft"   => __( "Draft",          "muifabrica_integration" ),
            "private" => __( "Private",        "muifabrica_integration" ),
        ];

        if ( $status == "publish" )
            $class = "ispublish";

        if ( $status == "draft"   )
            $class = "isdraft";

        $status          = isset( $arr_status[ $status ] ) ? $arr_status[ $status ] : ucfirst( $status ) ;
        $status          = "<small class='{$class}' >{$status}</small>";

        $product_name    = $item->get_name() . " - {$status}";

        $actions         = [];
        $actions[ "id" ] = "ID: {$item->get_id()}";

        if ( current_user_can( "edit_posts" ) )
        {
            $edit_link = esc_url(
                add_query_arg(
                    "wp_http_referer",
                    urlencode( wp_unslash( $_SERVER[ "REQUEST_URI" ] ) ),
                    get_edit_post_link( $item->get_id() )
                )
            );

            $actions[ "edit" ] = "<a href='{$edit_link}' target='_blank' >" . __( "Edit",   "muifabrica_integration" ) . "</a>";
            $product_name      = "<strong><a href='{$edit_link}' target='_blank' >{$item->get_name()}</a></strong>";
            $product_name     .= " - {$status}";
        }

        return sprintf( "%1s %2s", $product_name, $this->row_actions( $actions ) );
    }

    /**
     * SKU column.
     *
     * This function renders the SKU (Stock Keeping Unit) column in the table.
     * It simply displays the product's SKU.
     *
     * @since   1.0.0
     * @param   array|object    item    The product item being displayed in the table row.
     * @return  string                  The product's SKU.
     */
    public function column_sku( $item ) : string
    {
        return $item->get_sku();
    }

    /**
     * Stock column.
     *
     * This function renders the stock column in the table.
     *
     * @since   1.0.0
     * @param   array|object    $item   The product item being displayed in the table row.
     * @return  string                  The HTML markup for the stock column.
     */
    public function column_stock( $item ) : string
    {
        $stock = "";
        $class = "";

        switch ( $item->get_stock_status() )
        {
            case "instock":
                $stock = __( "Em estoque",      "muifabrica_integration" );
                $class = "instock";
            break;

            case "outofstock":
                $stock = __( "Fora de estoque", "muifabrica_integration" );
                $class = "outofstock";
            break;

            case "onbackorder":
                $stock = __( "Sob encomenda",   "muifabrica_integration" );
                $class = "onbackorder";
            break;
        }

        $text  = "<mark class='{$class}'>{$stock}</mark>";

        if ( $item->get_manage_stock() )
        {
            $qtty = $item->get_stock_quantity();
            $text = "<mark class='{$class}'>{$stock}</mark> ({$qtty})";
        }

        return $text;
    }

    /**
     * Price column.
     *
     * This function renders the price column in the table.
     *
     * @since   1.0.0
     * @param   array|object    $item   The product item being displayed in the table row.
     * @return  string                  The HTML markup for the price column.
     */
    public function column_price( $item ) : string
    {
        $r = wc_price( $item->get_regular_price() );
        $s = wc_price( $item->get_sale_price()    );

        return ( $item->get_sale_price() ) ? "{$s} <del>{$r}</del>" : $r;
    }

    /**
     * MF Price column.
     *
     * This function renders the supplier price column in the table.
     *
     * @since   1.0.0
     * @param   array|object    $item   The product item being displayed in the table row.
     * @return  string                  The HTML markup for the supplier price column.
     */
    public function column_m_price( $item ) : string
    {
        $last_upd = $item->get_meta( \Muifabrica_Integration::METAS[ "price_upd" ] );

        $regular  = [
            "status" => $item->get_meta( \Muifabrica_Integration::METAS[ "status_regular" ] ),
            "price"  => $item->get_meta( \Muifabrica_Integration::METAS[ "regular_price"  ] ),
        ];

        return MFI_Helper::format_mf_price( $last_upd, $regular );
    }

    /**
     * MF Price Commodities Price column.
     *
     * This function renders the supplier price column in the table.
     *
     * @since   1.0.0
     * @param   array|object    $item   The product item being displayed in the table row.
     * @return  string                  The HTML markup for the supplier price column.
     */
    public function column_m_ot_price( $item ) : string
    {
        $last_upd = $item->get_meta( \Muifabrica_Integration::METAS[ "ot_price_upd" ] );

        $regular  = [
            "status" => $item->get_meta( \Muifabrica_Integration::METAS[ "status_regular_ot" ] ),
            "price"  => $item->get_meta( \Muifabrica_Integration::METAS[ "regular_ot_price"  ] ),
        ];

        return MFI_Helper::format_mf_price( $last_upd, $regular );
    }

    /**
     * Categories column.
     *
     * This function renders the categories column in the table.
     * It displays the product categories associated with the product, separated by commas.
     *
     * @since   1.0.0
     * @param   array|object    $item   The product item being displayed in the table row.
     * @return  string                  The product categories.
     */
    public function column_categories( $item ) : string
    {
        return wc_get_product_category_list( $item->get_id() );
    }

    /**
     * Date column.
     *
     * This function renders the date column in the table.
     *
     * @since   1.0.0
     * @param   array|object    $item   The product item being displayed in the table row.
     * @return  string                  The formatted date and time of the last modification.
     */
    public function column_date( $item ) : string
    {
        $date = $item->get_date_modified();
        $date = wc_format_datetime( $date, "Y/m/d H:i:s" );

        return $date;
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
                $dollar = ( new Muifabrica_Api() )->get_dollar_price();

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
}
