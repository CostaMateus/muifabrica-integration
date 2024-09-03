<?php
/**
 * Orders table class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Muifabrica_Integration\Classes\Tables
 * @version 	1.0.0
 */

namespace Muifabrica_Integration\Classes\Tables;

use Muifabrica_Integration\Api\Muifabrica_Api;

class MFI_Orders_table extends \WP_List_Table
{
    /**
     * Orders data
     *
     * @since   1.0.0
     * @var     array|stdClass
     */
    protected $orders;

    /**
     * User data.
     *
     * @since   1.0.0
     * @var     API
     */
    protected $api;

    /**
     * Construct.
     *
     * @since   1.0.0
     * @return  void
     */
	public function __construct()
    {
        parent::__construct();

        $this->load_orders();

        $this->api = new Muifabrica_Api();
    }

    /**
     * Load the products data
     *
     * @since   1.0.0
     * @return  void
     */
    private function load_orders() : void
    {
        $orders       = wc_get_orders( [
            "limit" => -1,
            "type"  => "shop_order",
        ] );

        $this->orders = array_filter( $orders, function ( $order )
        {
            if ( $order->meta_exists( \Muifabrica_Integration::METAS[ "has_order" ] ) )
                return $order;
        } );
    }

    /**
     * Define the columns that are going to be used in the table
     *
     * @since   1.0.0
     * @return  array   $columns    The array of columns to use with the table
     */
    public function get_columns() : array
    {
        return [
            // "cb"       => "",
            "order_id" => __( "ID do pedido", "muifabrica_integration" ),
            "skus"     => __( "SKUs",         "muifabrica_integration" ),
            "date"     => __( "Data",         "muifabrica_integration" ),
            "status"   => __( "Status",       "muifabrica_integration" ),
            "total"    => __( "Total",        "muifabrica_integration" ),
        ];
    }

    /**
     * Decide which columns to activate the sorting functionality on
     *
     * @since   1.0.0
     * @return  array   $sortable     The array of columns that can be sorted by the user
     */
    public function get_sortable_columns() : array
    {
        return [
            "order_id" => [ "order_id", false ],
            "date"     => [ "date",     false ],
            "status"   => [ "status",   false ],
            "total"    => [ "total",    false ],
        ];
    }

    /**
     * Sort users by order_id, shopkeeper, date, status or total
     *
     * @since   1.0.0
     * @param   object $a
     * @param   object $b
     * @return  int|bool
     */
    public function usort_reorder( $a, $b ) : mixed
    {
        $order   = ( !empty( $_GET[ "order"   ] ) ) ? $_GET[ "order"   ] : "desc";
        $orderby = ( !empty( $_GET[ "orderby" ] ) ) ? $_GET[ "orderby" ] : "order_id";

        if ( $orderby == "order_id" )
        {
            $result = strcasecmp( $a->get_id(), $b->get_id() );

            if ( $result !== 0 )
                return ( $order === "asc" ) ? $result : -$result;
        }

        if ( $orderby == "date" )
        {
            $ta = strtotime( $a->get_date_created() );
            $tb = strtotime( $b->get_date_created() );

            if ( strcasecmp( $ta, $tb ) !== 0 )
            {
                if ( $order === "asc" )
                    return $ta - $tb;
                else
                    return $tb - $ta;
            }
        }

        if ( $orderby == "status" )
        {
            $result = strcasecmp( $a->get_status(), $b->get_status() );

            if ( $result !== 0 )
                return ( $order === "asc" ) ? $result : -$result;
        }

        if ( $orderby == "total" )
        {
            $ta = $a->get_total();
            $tb = $b->get_total();

            if ( strcasecmp( $ta, $tb ) !== 0 )
            {
                if ( $order === "asc" )
                    return $ta - $tb;
                else
                    return $tb - $ta;
            }
        }

        return null;
    }

    /**
     * Get bulk actions
     *
     * @since   1.0.0
     * @return  array
     */
    public function get_bulk_actions() : void
    {
        // $actions = [
        //     "upd_shipped" =>  __( "Set status shipped", "muifabrica_integration" )
        // ];
        //
        // return $actions;
    }

    /**
     * No items to show
     *
     * @since   1.0.0
     * @return  void
     */
    public function no_items() : void
    {
        _e( "Nenhum pedido encontrado.", "muifabrica_integration" );
    }

    /**
     * Prepare the table with different parameters, pagination, columns and table elements
     *
     * @since   1.0.0
     * @return  void
     */
    public function prepare_items() : void
    {
        // $this->process_bulk_action();

        $hidden                = [];
        $columns               = $this->get_columns();
        $sortable              = $this->get_sortable_columns();

        $this->_column_headers = [ $columns, $hidden, $sortable ];

        usort( $this->orders, [ &$this, "usort_reorder" ] );

        $per_page              = 25;
        $current_page          = $this->get_pagenum();
        $total_items           = count( $this->orders );
        $total_pages           = ceil( $total_items / $per_page );

        if ( $total_items >= 1 )
        {
            $this->orders = array_chunk( $this->orders, $per_page )[ $current_page - 1 ];

            if ( empty( $paged ) || !is_numeric( $paged ) || $paged<=0 )
                $paged = 1;

            $this->set_pagination_args( [
                "total_items" => $total_items,
                "total_pages" => $total_pages,
                "per_page"    => $per_page,
            ] );

            $this->items  = $this->orders;
        }
        else
        {
            $this->items  = [];
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
        //
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
     * @param   array|object $item
     * @return  string
     */
    public function column_cb( $item ) : void
    {
        // return sprintf( "<input type='checkbox' name='orders[]' value='%s' />", $item->ID );
    }

    /**
     * Name column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_order_id( $item ) : string
    {
        $customer      = new \WC_Customer( $item->get_customer_id() );
        $customer_name = "{$customer->get_first_name()} {$customer->get_last_name()}";

        $id            = "<strong>#{$item->get_id()} {$customer_name}</strong>";

        if ( current_user_can( "edit_posts" ) )
        {
            $edit_link = esc_url(
                add_query_arg(
                    "wp_http_referer",
                    urlencode( wp_unslash( $_SERVER[ "REQUEST_URI" ] ) ),
                    $item->get_edit_order_url()
                )
            );

            $id        = "<strong><a href='{$edit_link}' target='_blank' >{$id}</a></strong>";
        }

        return $id;
    }

    /**
     * Suppliers column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_suppliers( $item ) : string
    {
        $data     = [];
        $result   = "";

        foreach ( $item->get_items() as $aux_item )
        {
            $product = $aux_item->get_product();

            if ( !$product ) continue;

            if ( isset( $data[ $product->get_sku() ] ) )
                $data[ $product->get_sku() ] = $product->get_quantity();
        }

        $last_sku = array_key_last( $data );

        foreach ( $data as $sku => $qty )
        {
            $result .= "{$qty}x {$sku}";

            if ( $sku != $last_sku )
                $result .= "<br>";
        }

        return $result;
    }

    /**
     * Date column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_date( $item ) : string
    {
        return wc_format_datetime( $item->get_date_created(), "Y/m/d H:i" );
    }

    /**
     * Status column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_status( $item ) : string
    {
		if ( in_array( $item->get_status(), array( "trash", "draft", "auto-draft" ), true ) )
        {
			$status_name = ( get_post_status_object( $item->get_status() ) )->label;
		}
        else
        {
			$status_name = wc_get_order_status_name( $item->get_status() );
		}

        $class = esc_attr( sanitize_html_class( 'status-' . $item->get_status() ) );

        return "<mark class='order-status {$class}'><span>{$status_name}</span></mark>";
    }

    /**
     * Total column
     *
     * @since   1.0.0
     * @param   array|object $item
     * @return  string
     */
    public function column_total( $item ) : string
    {
        return wc_price( $item->get_total() );
    }

    /**
     * Extra infos on top or bottom of the table
     *
     * @since   1.0.0
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
