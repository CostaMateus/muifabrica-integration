<?php
/**
 * Muifabrica Air Shipping.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Muifabrica_Integration\Classes\Shipping
 * @version 	1.0.0
 */
namespace Muifabrica_Integration\Classes\Shipping;

use Muifabrica_Integration\Api\Muifabrica_Api;
use Muifabrica_Integration\Classes\Helpers\MFI_Helper;

defined( "ABSPATH" ) || exit;

class MFI_Air_Shipping extends \WC_Shipping_Method
{
    protected $api               = null;
    protected $shipping_class_id = null;

    /**
     * Initialize the Correios shipping method.
     *
     * @since   1.0.0
     * @param   int     $instance_id    Shipping zone instance ID.
     */
    public function __construct( $instance_id = 0 )
    {
        $this->api                = new Muifabrica_Api();

        $this->instance_id        = absint( $instance_id );

        $this->id                 = "muifabrica-air-shipping";
        $this->method_title       = __( "Aéreo Muifabrica",              "muifabrica_integration" );
        $this->method_description = __( "Entrega aérea pela Muifabrica", "muifabrica_integration" );

        $this->enabled            = "yes";
        $this->title              = "Muifabrica";

        $this->supports           = [ "shipping-zones", "instance-settings", ];

        $this->init();
    }

    /**
     * Init your settings
     *
     * @since   1.0.0
     * @return  void
     */
    public function init() : void
    {
        // Load the settings API
        // This is part of the settings API.
        // Override the method to add your own settings
        $this->init_form_fields();

        // Define user set variables.
        $this->enabled            = $this->get_option( "enabled"           );
        $this->title              = $this->get_option( "title"             );
        $this->shipping_class_id  = $this->get_option( "shipping_class_id" );

        // Save settings in admin if you have any defined
        add_action( "woocommerce_update_options_shipping_" . $this->id, [ $this, "process_admin_options" ] );
    }

    /**
     * Admin options fields.
     *
     * @since   1.0.0
     * @return  void
     */
    public function init_form_fields() : void
    {
        $this->instance_form_fields = [
            "enabled"            => [
                "type"        => "checkbox",
                "title"       => __( "Habilitar/Desabilitar",                                       "muifabrica_integration" ),
                "label"       => __( "Ativar este método de envio",                                 "muifabrica_integration" ),
                "default"     => "yes",
            ],
            "title"              => [
                "type"        => "text",
                "title"       => __( "Título",                                                      "muifabrica_integration" ),
                "description" => __( "Isto controla o título que o usuário vê durante o checkout.", "muifabrica_integration" ),
                "desc_tip"    => true,
                "default"     => $this->method_title,
            ],
            "shipping_class_id"  => [
                "type"        => "select",
                "title"       => __( "Classe de entrega",                                           "muifabrica_integration" ),
                "description" => "",
                "default"     => "",
                "class"       => "wc-enhanced-select",
                "options"     => $this->get_shipping_classes_options(),
            ],
        ];
    }

    /**
     * Get shipping classes options.
     *
     * @since   1.0.0
     * @return  array
     */
    protected function get_shipping_classes_options() : array
    {
        $shipping_class = get_term_by( "slug", "muifabrica-shipping-class", "product_shipping_class" );

        return [ $shipping_class->term_id => __( $shipping_class->name, "muifabrica_integration" ) ];
    }

    /**
     * TODO
     * Check if package uses only the selected shipping class.
     *
     * @since   1.0.0
     * @param   array   $package    Cart package.
     * @return  bool
     */
    protected function has_only_selected_shipping_class( $package )
    {
        $only_selected = true;

        if ( in_array( -1, $this->shipping_class_id ) ) return $only_selected;

        foreach ( $package[ "contents" ] as $item_id => $values )
        {
            $product = $values[ "data" ];
            $qty     = $values[ "quantity" ];

            if ( $qty > 0 && $product->needs_shipping() )
            {
                if ( !in_array( $product->get_shipping_class_id(), $this->shipping_class_id ) )
                {
                    $only_selected = false;
                    break;
                }
            }
        }

        return $only_selected;
    }

    /**
     * TODO
     * calculate_shipping function.
     *
     * @since   1.0.0
     * @param   mixed   $package
     * @return  void
     */
    public function calculate_shipping( $package = [] )
    {
        // Check if valid to be calculeted
        if ( "" === $package[ "destination" ][ "postcode" ] || "BR" !== $package[ "destination" ][ "country" ] ) return;

        // Check if complete postcode
        $postcode = strlen($package[ "destination" ][ "postcode" ] );
        if ( $postcode < 8 || $postcode > 9 ) return;

        // Check for shipping classes.
        if ( ! $this->has_only_selected_shipping_class( $package ) ) return;

        // executa a consulta a api
        $shipping = $this->get_rate( $package );

        if ( $shipping[ "errorCode" ] )
        {
            \Muifabrica_Integration::log( "error", print_r( [ "calculate_shipping_error", $shipping ], true ), true );
            return;
        }

        $meta  = [];
        $costs = [];
        $time  = 0;

        foreach ( $shipping[ "data" ] as $data )
        {
            if ( $data[ "errorCode" ] ) return;

            $item         = $data[ "data" ];
            $id           = $item[ "product_id" ];
            $costs[ $id ] = ( float ) $item[ "deliveryPrice" ];
            $time         = ( $item[ "deliveryTime" ] > $time ) ? $item[ "deliveryTime" ] : $time;
        }

        $meta[ "price"   ] = $costs;
        $meta[ "company" ] = "Muifabrica";

        // Create the rate
        $rate = [
            "id"        => $this->id . $this->instance_id,
            "label"     => $this->title,
            "cost"      => $costs,
            "calc_tax"  => "per_item",
            "meta_data" => $meta,
            "package"   => $package
        ];

        // Register the rate
        $this->add_rate( $rate );
    }

	/**
     * TODO
	 * Get shipping rate.
	 *
     * @since   1.0.0
	 * @param   array   $package    Cart package.
	 * @return  array
	 */
	protected function get_rate( $package )
    {
        $body     = $this->process_package( $package );
        $shipping = $this->api->fs_freight_calculation( $body )[ "data" ];

        foreach ( $shipping[ "data" ] as $sku => &$data )
        {
            $id      = wc_get_product_id_by_sku( $sku );
            $product = wc_get_product( $id );
            $data[ "data" ][ "product_id" ] = $id;

            $code    = $data[ "errorCode" ];
            $item    = $data[ "data"      ];

            $fs_undf = $this->api->env() ? 1    : 4744;

            $fs_     = $this->api->env() ? 4453 : 3970;
            $fs_05   = $this->api->env() ? 4454 : 4309;
            $fs_14   = $this->api->env() ? 4451 : 4310;
            $fs_33   = $this->api->env() ? 4345 : 4311;

            $fs_4e   = $this->api->env() ? 1    : 4740;
            $fs_25   = $this->api->env() ? 1    : 4741;
            $fs_60   = $this->api->env() ? 1    : 4742;
            $fs_61   = $this->api->env() ? 1    : 4743;

            $fs_19   = $this->api->env() ? 1    : 4745;
            $fs_74   = $this->api->env() ? 1    : 4746;

            $fs_3e   = $this->api->env() ? 1    : 4747;

            $fs_97   = $this->api->env() ? 1    : 4759;

            $fs_83   = $this->api->env() ? 1    : 4776;

            if ( in_array( $code, [ 4001, 4002 ] ) )
            {
                $product->set_stock_quantity( 0 );
                $product->set_stock_status( "outofstock" );
                $product->update_meta_data( "_fastshop_hub", "-" );
                $product->set_shipping_class_id( $fs_ );
            }
            else if ( $code == 4000 )
            {
                $stock   = $item[ "stock" ];
                $product->set_stock_quantity( $stock );

                $status  = ( $stock > 0 ) ? "instock" : "outofstock";
                $product->set_stock_status( $status );

                $product->update_meta_data( "_fastshop_hub", "-" );
                $product->set_shipping_class_id( $fs_ );
            }
            else
            {
                $stock   = $item[ "stock" ];
                $product->set_stock_quantity( $stock );

                $status  = ( $stock > 0 ) ? "instock" : "outofstock";
                $product->set_stock_status( $status );

                $hub     = !empty( $item[ "hub" ] ) ? $item[ "hub" ] : "-";
                $product->update_meta_data( "_fastshop_hub", $hub );

                if ( $hub != "-" )
                {
                    $last_p  = explode( "_", $hub )[ 2 ];

                    $hub_map = [
                        "05" => $fs_05,
                        "14" => $fs_14,
                        "33" => $fs_33,
                        "4E" => $fs_4e,
                        "25" => $fs_25,
                        "60" => $fs_60,
                        "61" => $fs_61,
                        "19" => $fs_19,
                        "74" => $fs_74,
                        "3E" => $fs_3e,
                        "97" => $fs_97,
                        "83" => $fs_83
                    ];

                    $id      = $hub_map[ strtoupper( $last_p ) ] ?? $fs_undf;

                    $product->set_shipping_class_id( $id );
                }
                else
                {
                    $product->set_shipping_class_id( $fs_ );
                }
            }

            if ( isset( $item[ "regular_price" ] ) && isset( $item[ "sale_price" ] ) )
            {
                $product->set_regular_price( $item[ "regular_price" ] );
                $product->set_sale_price( $item[ "sale_price" ] );
            }

            $product->save();
        }

		return $shipping;
	}

    /**
     * Process the package
     *
     * @since   1.0.0
     * @param   array   $package
     * @return  array
     */
    private function process_package( $package ) : array
    {
        $postalCode = $package[ "destination" ][ "postcode" ];
        $items      = [];

        foreach ( $package[ "contents" ] as $content )
        {
            $product = wc_get_product( $content[ "product_id" ] );

            if ( !$product || !$product->get_meta( "_mfi_supplied", true ) )
                continue;

            $items[] = [
                "sku"      => $product->get_sku(),
                "quantity" => $content["quantity"]
            ];
        }

        return [
            "postalCode" => $postalCode,
            "items"      => $items
        ];
    }
}
