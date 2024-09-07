<?php
/**
 * Tools tab view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Muifabrica_Integration\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

use \Muifabrica_Integration\Classes\Tables\MFI_Products_Table;

$pd_table = new MFI_Products_Table();
?>

<section>
	<div class="wrap-mfi" id="mfi-products-list-table" >

		<form id="mfi-products-list-table-form" method="post" >
			<?php
				$page  = htmlspecialchars( $_POST[ "page" ] ?? "" );
				$paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );

				printf( "<input type='hidden' name='page'  value='%s' />", $page  );
				printf( "<input type='hidden' name='paged' value='%d' />", $paged );

				$pd_table->prepare_items();
				$pd_table->search_box( "Busca", "search_id" );
				$pd_table->display();
			?>
		</form>

	</div>
</section>
