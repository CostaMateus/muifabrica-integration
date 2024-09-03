<?php
/**
 * TODO verificar
 *
 * Orders class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Muifabrica_Integration\Classes
 * @version 	1.0.0
 */

namespace Muifabrica_Integration\Classes;

class MFI_Orders
{
    /**
     * TODO verificar
     *
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
     * TODO verificar
     *
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
        $api         = null;
        $products    = [];
        $customer_ip = $order->get_customer_ip_address();

        $billing     = [
            "first_name" => $order->get_billing_first_name(),
            "last_name"  => $order->get_billing_last_name(),
            "company"    => $order->get_billing_company(),
            "address_1"  => $order->get_billing_address_1(),
            "address_2"  => $order->get_billing_address_2(),
            "city"       => $order->get_billing_city(),
            "state"      => $order->get_billing_state(),
            "postcode"   => $order->get_billing_postcode(),
            "country"    => $order->get_billing_country(),
            "email"      => $order->get_billing_email(),
            "phone"      => $order->get_billing_phone(),
        ];

        $shipping    = [
            "first_name" => $order->get_shipping_first_name(),
            "last_name"  => $order->get_shipping_last_name(),
            "company"    => $order->get_shipping_company(),
            "address_1"  => $order->get_shipping_address_1(),
            "address_2"  => $order->get_shipping_address_2(),
            "city"       => $order->get_shipping_city(),
            "state"      => $order->get_shipping_state(),
            "postcode"   => $order->get_shipping_postcode(),
            "country"    => $order->get_shipping_country(),
        ];

        foreach ( $order->get_items() as $item )
        {
            $product = $item->get_product();

            if ( !$product || !$product->get_meta( \Muifabrica_Integration::METAS[ "supplied" ], true ) )
                continue;

            $sku     = $product->get_meta( \Muifabrica_Integration::METAS[ "supplier_sku" ] );

            $products[ $sku ] = [
                "sku"      => $product->get_sku(),
                "quantity" => $item->get_quantity(),
                "sale"     => $product->get_meta( \Muifabrica_Integration::METAS[ "regular_price" ]    ),
                // "regular"  => $product->get_meta( \Muifabrica_Integration::METAS[ "regular_ot_price" ] ),
            ];
        }

        if ( empty( $products ) ) return;

        $order->update_meta_data( \Muifabrica_Integration::METAS[ "has_order" ], true );

        if ( $order->get_meta( \Muifabrica_Integration::METAS[ "order_sent" ], true ) ) return;

        $api         = new Muifabrica_Api();

        // TODO enviar pedido para MF

        $order->add_order_note( implode( "\n", [
            "Order sent successfully",
            "Order ID: #{$response_order_id}",
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
