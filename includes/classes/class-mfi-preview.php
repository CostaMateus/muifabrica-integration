<?php
/**
 * Products class.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Muifabrica_Integration\Classes
 * @version 	1.0.0
 */

namespace Muifabrica_Integration\Classes;

class MFI_Preview
{
    /**
     * Constructor
     *
     * @since   1.0.0
     * @return 	void
     */
    public function __construct()
    {
		add_filter( "manage_product_posts_custom_column", [ $this, "add_column_content" ], 10, 2 );
    }

	/**
	 * Adds 'Supplier' column content to 'All Products' page immediately after 'Categories' column.
	 *
	 * @since 	1.0.0
	 * @param 	string 	$column
	 * @param 	string 	$product_id
	 * @return 	void
	 */
	public function add_column_content( $column, $product_id ) : void
	{
		if ( $column === "sku" )
		{
			$product = wc_get_product( $product_id );

			if ( "yes" == $product->get_meta( "_mfi_supplied" ) )
				echo "<strong>Muifabrica</strong><br>";
		}
	}
}
