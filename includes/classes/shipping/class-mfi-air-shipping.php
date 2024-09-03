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

        $this->title              = "Muifabrica";
        $this->enabled            = "yes";

        $this->supports           = [
            // "settings",
            "shipping-zones",
            "instance-settings",
            "shipping-calculator",
            "package-rates",
            "requires_shipping_address",
            "is_available"
        ];

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
        $this->init_settings();

        // Define user set variables.
        $this->title              = $this->get_option( "title"             );
        $this->enabled            = $this->get_option( "enabled"           );
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
        $shipping_class             = $this->get_shipping_classes_options();

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
                "desc_tip"    => false,
                "default"     => $this->method_title,
            ],
            "shipping_class_id"  => [
                "type"        => "select",
                "title"       => __( "Classe de entrega",                                           "muifabrica_integration" ),
                "description" => "",
                "default"     => array_key_first( $shipping_class ),
                "class"       => "wc-enhanced-select",
                "options"     => $shipping_class,
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
     * Check if package uses only the selected shipping class.
     *
     * @since   1.0.0
     * @param   array   $package    Cart package.
     * @return  bool
     */
    protected function check_shipping_class( $package ) : bool
    {
        $only_selected = true;

        foreach ( $package[ "contents" ] as $item_id => $values )
        {
            $product = $values[ "data"     ];
            $qty     = $values[ "quantity" ];

            if ( $qty > 0 && $product->needs_shipping() )
            {
                if ( $product->get_shipping_class_id() != $this->shipping_class_id )
                {
                    $only_selected = false;
                    break;
                }
            }
        }

        return $only_selected;
    }

    /**
     * Checks if the WooCommerce currency is set to BRL (Brazilian Real) and returns the dollar price.
     *
     * @return  float   The dollar price if the currency is BRL, otherwise 1.
     */
    protected function is_brl_currency() : float
    {
        return ( "BRL" === get_option( "woocommerce_currency" ) )
                ? str_replace( ",", ".", $this->api->get_dollar_price() )
                : 1;
    }

    /**
     * Calculates the shipping coast for a given package.
     *
     * @since   1.0.0
     * @param   mixed   $package    The package details including destination and items.
     * @return  void
     */
    public function calculate_shipping( $package = [] ) : void
    {
        // Check if the destination postcode is valid and the country is Brazil.
        if ( "" === $package[ "destination" ][ "postcode" ] || "BR" !== $package[ "destination" ][ "country" ] )
            return;

        // Check if the postcode length is valid (between 8 and 9 characters).
        $postcode = strlen( $package[ "destination" ][ "postcode" ] );
        if ( $postcode < 8 || $postcode > 9 )
            return;

        // Check if the package has valid shipping classes.
        if ( !$this->check_shipping_class( $package ) )
            return;

        // Get the shipping rate for the package.
        $shipping = $this->get_rate( $package );

        // If no shipping rate is found, log an error and return.
        if ( !$shipping )
        {
            \Muifabrica_Integration::log( "error", "calculate_shipping", true );
            return;
        }

        // Get the current dollar price from the API.
        $dollar   = $this->is_brl_currency();
        $costs    = 0;

        // Calculate the total shipping cost.
        foreach ( $shipping as $i => $value )
            $costs += ( float ) $value;

        $cost    *= $dollar;

        // Prepare metadata for the shipping rate.
        $meta[ "price"   ] = $costs;
        $meta[ "company" ] = "Muifabrica";

        // Create the shipping rate array.
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
	 * Get shipping rate.
	 *
     * @since   1.0.0
	 * @param   array   $package    Cart package.
	 * @return  array|false
	 */
	protected function get_rate( $package ) : array|false
    {
        $body = $this->process_package( $package );

        return $this->api->get_freight( $body );
	}

    /**
     * Process the package
     *
     * @since   1.0.0
     * @param   array   $package
     * @return  array
     */
    private function process_package( array $package ) : array
    {
        $products = [];

        foreach ( $package[ "contents" ] as $content )
        {
            if ( $content[ "variation_id" ] != 0 )
                $product = wc_get_product( $content[ "variation_id" ] );
            else
                $product = wc_get_product( $content[ "product_id" ] );

            if ( !$product || !$product->get_meta( \Muifabrica_Integration::METAS[ "supplied" ], true ) )
                continue;

            $products[] = [
                "unique"           => $product->get_meta( \Muifabrica_Integration::METAS[ "supplier_sku" ], true ),
                "quantity"         => ( int ) $content[ "quantity" ],
                "transport_method" => "1"
            ];
        }

        return [
            "freight_type" => 1,
            "products"     => $products,
        ];
    }
}
