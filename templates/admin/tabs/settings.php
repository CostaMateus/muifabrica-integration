<?php
/**
 * Tools tab view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Muifabrica_Integration\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

$_api_hml        = ( !empty( get_option( "_mfi_api"   ) ) ) ? "checked" : "";
$_debug          = ( !empty( get_option( "_mfi_debug" ) ) ) ? "checked" : "";

$_app_id_hml     = get_option( "_mfi_app_id_hml"     );
$_app_secret_hml = get_option( "_mfi_app_secret_hml" );
$_app_id         = get_option( "_mfi_app_id"         );
$_app_secret     = get_option( "_mfi_app_secret"     );
?>

<section>
	<div class="wrap-mfi" >
		<h2 class="wp-heading-inline" style="display:inline;" >
			<?php _e( "Configurações", "muifabrica_integration" ); ?>
		</h2>

		<form id="mfi-general-form" class="mfi-form" method="post" action="<?php echo admin_url( "admin-post.php" ); ?>" >
			<input type="hidden" name="action" value="mfi_save_general" />
			<?php wp_nonce_field( "mfi_save_general" ); ?>

			<table class="form-table" >
				<tbody>
					<tr valign="top" >
						<th scope="row" class="titledesc" >
							<label for="mfi_api" >
								<?php _e( "Homologação", "muifabrica_integration" ); ?>
							</label>
						</th>
						<td class="forminp" >
							<input id="mfi_api" class="input-text regular-input" type="checkbox" name="mfi_api" <?php echo $_api_hml; ?> >
							<?php _e( "Habilitar API de homologação", "muifabrica_integration" ); ?>
							<p class="description" ><?php _e( "Se marcado, a comunicação será feita com o servidor de HOMOLOGAÇÃO da Muifabrica. Se não, será feita com o servidor real, de PRODUÇÃO.", "muifabrica_integration" ); ?></p>
						</td>
					</tr>

					<?php // HOMOL KEYS ?>
					<tr valign="top" >
						<th scope="row" class="titledesc" >
							<label for="mfi_app_id_hml" >
								<?php _e( "App ID Homologação", "muifabrica_integration" ); ?>
							</label>
						</th>
						<td class="forminp" >
							<input id="mfi_app_id_hml" class="input-text regular-input" type="email" name="mfi_app_id_hml" value="<?php echo $_app_id_hml; ?>" >
							<p class="description" ><?php _e( "App ID do ambiente de testes (homologação).", "muifabrica_integration" ); ?></p>
						</td>
					</tr>
					<tr valign="top" >
						<th scope="row" class="titledesc" >
							<label for="mfi_app_secret_hml" >
								<?php _e( "App Secret Homologação", "muifabrica_integration" ); ?>
							</label>
						</th>
						<td class="forminp" >
							<input id="mfi_app_secret_hml" class="input-text regular-input" type="email" name="mfi_app_secret_hml" value="<?php echo $_app_secret_hml; ?>" >
							<p class="description" ><?php _e( "App Secret do ambiente de testes (homologação).", "muifabrica_integration" ); ?></p>
						</td>
					</tr>

					<?php // PROD KEYS ?>
					<tr valign="top" >
						<th scope="row" class="titledesc" >
							<label for="mfi_app_id" >
								<?php _e( "App ID", "muifabrica_integration" ); ?>
							</label>
						</th>
						<td class="forminp" >
							<input id="mfi_app_id" class="input-text regular-input" type="email" name="mfi_app_id" value="<?php echo $_app_id; ?>" >
							<p class="description" ><?php _e( "App ID do ambiente real (produção).", "muifabrica_integration" ); ?></p>
						</td>
					</tr>
					<tr valign="top" >
						<th scope="row" class="titledesc" >
							<label for="mfi_app_secret" >
								<?php _e( "App Secret", "muifabrica_integration" ); ?>
							</label>
						</th>
						<td class="forminp" >
							<input id="mfi_app_secret" class="input-text regular-input" type="email" name="mfi_app_secret" value="<?php echo $_app_secret; ?>" >
							<p class="description" ><?php _e( "App Secret do ambiente real (produção).", "muifabrica_integration" ); ?></p>
						</td>
					</tr>

					<tr valign="top" >
						<th scope="row" class="titledesc" >
							<label for="mfi_debug" >
								<?php _e( "Log de depuração", "muifabrica_integration" ); ?>
							</label>
						</th>
						<td class="forminp" >
							<input id="mfi_debug" class="input-text regular-input" type="checkbox" name="mfi_debug" <?php echo $_debug; ?> >
							<?php _e( "Ativar / Desativar", "muifabrica_integration" ); ?>
							<p class="description" ><?php _e( "Se ativado, irá registrar logs detalhados. Caso não, apenas logs críticos.", "muifabrica_integration" ); ?></p>
							<p class="description" ><?php echo $debug; ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit" style="clear: both;" >
				<input type="submit" class="button-primary" value="<?php _e( "Salvar configurações", "muifabrica_integration" );?>" ></input>
			</p>
		</form>
	</div>
</section>
