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
        add_action( "woocommerce_checkout_order_processed",            [ $this, "send_order_to_muifabrica" ], 20, 3 );
		add_filter( "woocommerce_shop_order_list_table_columns",       [ $this, "add_column_title"         ], 10, 1 );
		add_filter( "woocommerce_shop_order_list_table_custom_column", [ $this, "add_column_content"       ], 10, 2 );

        // Order Box Actions
        add_action( "woocommerce_order_actions",                       [ $this, "add_order_box_action"     ], 10, 1 );
        add_action( "woocommerce_order_action_mfi_order_action",       [ $this, "process_order_box_action" ], 10, 1 );
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
    public function send_order_to_muifabrica( $order_id, $posted_data, $order ) : void
    {
        $order->update_meta_data( \Muifabrica_Integration::METAS[ "has_order" ], true );

        if ( $order->get_meta( \Muifabrica_Integration::METAS[ "order_sent" ], true ) ) return;

        $data     = MFI_Helper::translate_order( $order );

        $api      = new Muifabrica_Api();

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

        $order->add_meta_data( \Muifabrica_Integration::METAS[ "order_sent" ], true );
        $order->save();
    }

	/**
	 * Adds 'Muifabrica' column header to 'All Orders' page immediately after 'Status' column.
	 *
	 * @since 	1.0.0
	 * @param   array   $columns
	 * @return  array
	 */
	public function add_column_title( $columns ) : array
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
	public function add_column_content( $column, $order ) : void
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
	public function add_order_box_action( $actions ) : array
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
	public function process_order_box_action( $order ) : void
	{
        $this->send_order_to_muifabrica( $order->get_id(), [], $order );
	}
}
