<?php
/**
 * Orders class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Muifabrica_Integration\Classes
 * @version 	1.0.0
 */

namespace Muifabrica_Integration\Classes;

use Muifabrica_Integration\Api\Muifabrica_Api;
use Muifabrica_Integration\Classes\Helpers\MFI_Helper;

class MFI_Orders
{
    /**
     * Constructor
     *
     * @since   1.0.0
     * @return  void
     */
    public function __construct()
    {
        add_action( "woocommerce_checkout_order_processed",            [ $this, "check_muifabrica_order"   ], 20, 3 );
        add_action( "woocommerce_order_status_changed",                [ $this, "order_status_change"      ], 9, 4  );

		add_filter( "woocommerce_shop_order_list_table_columns",       [ $this, "add_column_title"         ], 10, 1 );
		add_filter( "woocommerce_shop_order_list_table_custom_column", [ $this, "add_column_content"       ], 10, 2 );

        // Order Box Actions
        add_action( "woocommerce_order_actions",                       [ $this, "add_order_box_action"     ], 10, 1 );
        add_action( "woocommerce_order_action_mfi_order_action",       [ $this, "process_order_box_action" ], 10, 1 );
    }

    /**
     * Checks if any product in the order is supplied by MFI.
     *
     * This function iterates through the items in the order and checks if any product
     * has a specific meta indicating it is supplied by MFI (Muifabrica Integration).
     *
     * @since   1.0.0
     * @param   object  $order  The order object containing the items to check.
     * @return  bool            Returns true if any product is supplied by MFI, otherwise false.
     */
    private function check_mfi_products( object $order ) : bool
    {
        foreach ( $order->get_items() as $item )
        {
            $product  = $item->get_product();
            $supplied = $product->get_meta( \Muifabrica_Integration::METAS[ "supplied" ] );

            if ( $supplied )
            {
                return true;
                break;
            }
        }

        return false;
    }

    /**
     * Send order to suppliers
     *
     * @since   1.0.0
     * @param   int     $order_id
     * @param   array   $posted_data
     * @param   object  $order
     * @return  void
     */
    public function send_order_to_muifabrica( int $order_id, array $posted_data, object $order ) : void
    {
        if ( !$this->check_mfi_products( $order ) ) return;

        $order->update_meta_data( \Muifabrica_Integration::METAS[ "has_order" ], true );

        if ( $order->get_meta( \Muifabrica_Integration::METAS[ "order_sent" ], true ) ) return;

        $data     = MFI_Helper::translate_order( $order );

        $api      = new Muifabrica_Api();

        $response = $api->confirm_order( $data );

        if ( !$response )
        {
            $order->add_order_note( implode( "\n", [
                "Falha ao confirmar pedido com a Muifabrica!",
                "Erro: " . $api->get_error_message()
            ] ) );

            $order->save();

            return;
        }

        $response = $api->post_order( $data );

        if ( !$response )
        {
            $order->add_order_note( implode( "\n", [
                "Falha ao enviar pedido para a Muifabrica!",
                "Erro: " . $api->get_error_message()
            ] ) );

            $order->save();

            return;
        }

        $mfi_order_id      = $response[ 0 ][ "oid"      ];
        $mfi_order_number  = $response[ 0 ][ "order_id" ];
        $mfi_sub_order_ids = [];

        foreach ( $response[ "sorder_no" ] as $sub_order )
            $mfi_sub_order_ids[] = $sub_order;

        $order->add_meta_data( \Muifabrica_Integration::METAS[ "order_sent"    ], true               );
        $order->add_meta_data( \Muifabrica_Integration::METAS[ "order_id"      ], $mfi_order_id      );
        $order->add_meta_data( \Muifabrica_Integration::METAS[ "order_number"  ], $mfi_order_number  );
        $order->add_meta_data( \Muifabrica_Integration::METAS[ "sub_order_ids" ], $mfi_sub_order_ids );

        $order->add_order_note( implode( "\n", [
            "Pedido enviado com sucesso!",
            "ID do pedido: #{$mfi_order_id}",
            "Número do pedido: #{$mfi_order_number}",
            "IDs dos sub-pedidos: " . implode( " | ", $mfi_sub_order_ids )
        ] ) );

        $order->save();
    }

    /**
     * Check if order has Muifabrica products
     *
     * @since   1.0.0
     * @param   int     $order_id
     * @param   array   $posted_data
     * @param   object  $order
     * @return  void
     */
    public function check_muifabrica_order(   int $order_id, array $posted_data, object $order ) : void
    {
        if ( !$this->check_mfi_products( $order ) ) return;

        $order->update_meta_data( \Muifabrica_Integration::METAS[ "has_order" ], true );
        $order->save();
    }

	/**
	 * Adds 'Muifabrica' column header to 'All Orders' page immediately after 'Status' column.
	 *
	 * @since 	1.0.0
	 * @param   array   $columns
	 * @return  array
	 */
	public function add_column_title( array $columns ) : array
	{
		$new_columns = [];

		foreach ( $columns as $column_name => $column_info )
		{
			$new_columns[ $column_name ] = $column_info;

			if ( "order_status" === $column_name )
				$new_columns[ \Muifabrica_Integration::DOMAIN ] = __( "Muifabrica", "muifabrica_integration" );
		}

		return $new_columns;
	}

	/**
	 * Adds 'Muifabrica' column content to 'All Products' page immediately after 'Categories' column.
	 *
	 * @since 	1.0.0
	 * @param 	string 	$column
	 * @param 	object 	$order
	 * @return  void
	 */
	public function add_column_content( string $column, object $order ) : void
	{
        if ( $column !== \Muifabrica_Integration::DOMAIN )
            return;

        $result = "";

        foreach ( $order->get_items() as $item )
        {
            $product = $item->get_product();

            if ( !$product || !$product->get_meta( \Muifabrica_Integration::METAS[ "supplied" ], true ) )
                continue;

            $result .= "<li>{$item->get_quantity()}x {$product->get_sku()}</li>";
        }

        echo "<ul>{$result}</ul>";
	}

	/**
	 * Add custom action to order box.
	 *
     * @since   1.0.0
	 * @param 	array   $actions
     * @return  array
	 */
	public function add_order_box_action( array $actions ) : array
	{
		global $theorder;

        $to_send = false;

        foreach ( $theorder->get_items() as $item )
        {
            $product = $item->get_product();

            if ( !$product || !$product->get_meta( \Muifabrica_Integration::METAS[ "supplied" ], true ) )
                continue;

            if ( !$theorder->get_meta( \Muifabrica_Integration::METAS[ "order_sent" ], true ) )
                $to_send = true;
        }

		if ( $to_send )
		{
			$actions[ "mfi_order_action" ] = __( "Enviar pedido para Muifabrica", "muifabrica_integration" );

			return $actions;
		}

		return $actions;
	}

	/**
	 * Process custom action order box.
	 *
     * @since   1.0.0
     * @param   object  $order
     * @return  void
	 */
	public function process_order_box_action( object $order ) : void
	{
        $this->send_order_to_muifabrica( $order->get_id(), [], $order );
	}

    /**
     * Handles the order status change event.
     *
     * This function is triggered when the status of an order changes. It checks if the order contains
     * any products supplied by MFI (Muifabrica Integration). If so, and if the status change is from
     * "pending" or "on-hold" to "processing", it confirms the order with the MFI API.
     *
     * @since   1.0.0
     * @param   int     $order_id       The ID of the order.
     * @param   string  $old_status     The old status of the order.
     * @param   string  $new_status     The new status of the order.
     * @param   object  $order          The order object.
     * @return  void
     */
    public function order_status_change( int $order_id, string $old_status, string $new_status, object $order )
    {
        if ( !$this->check_mfi_products( $order ) ) return;

        $data      = MFI_Helper::translate_order( $order, true );
        $api       = new Muifabrica_Api();

        $cancelled = $order->get_meta( \Muifabrica_Integration::METAS[ "order_cancelled" ] );

        // Cancel order with MFI API
        if ( !$cancelled && $old_status != "completed" && $new_status == "cancelled" )
        {
            $mfi_order_id = $order->get_meta( \Muifabrica_Integration::METAS[ "order_id" ] );
            $response     = $api->cancel_order( $data, $mfi_order_id );

            if ( !$response )
            {
                $order->add_order_note( implode( "\n", [
                    "Falha ao cancelar pedido com a Muifabrica!",
                    "Erro: " . $api->get_error_message()
                ] ) );

                $order->save();

                return;
            }

            $order->add_meta_data( \Muifabrica_Integration::METAS[ "order_cancelled" ], true );
            $order->add_order_note( "Pedido cancelado com sucesso!" );
            $order->save();

            return;
        }
    }
}
