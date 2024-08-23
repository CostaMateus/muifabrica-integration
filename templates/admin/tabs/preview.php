<?php
/**
 * Tools tab view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Muifabrica_Integration\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

use \Muifabrica_Integration\Classes\Tables\MFI_Preview_Table;

add_thickbox();

$pv_table = new MFI_Preview_Table();
?>

<section>
	<div class="wrap-mfi" id="mfi-preview-products-list-table" >

		<p>
			<strong>Cotação do dólar:</strong>
			R$ <?php echo ( new \Muifabrica_Integration\Api\Muifabrica_Api() )->get_dollar_price(); ?>
			<small>(Atualizado a cada 15 minutos)</small>
		</p>

		<form id="mfi-preview_products-list-table-form" method="post" >
			<?php
				$page  = htmlspecialchars( $_POST[ "page" ] ?? "" );
				$paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );

				printf( "<input type='hidden' name='page'  value='%s' />", $page  );
				printf( "<input type='hidden' name='paged' value='%d' />", $paged );

				$pv_table->prepare_items();
				$pv_table->display();
			?>
		</form>

	</div>
</section>
