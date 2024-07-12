<?php
/**
 * Settings tab view
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Muifabrica_Integration\Templates\Admin\Tabs
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

$_env                  = get_option( "_mfi_env_api" );
$_env_api              = ( !empty( $_env ) || $_env == "hml" ) ? "checked" : "";
$_env_api_label        = __( "Homologação",                                                                      "muifabrica_integration" );
$_env_api_input        = __( "Habilitar API de homologação",                                                     "muifabrica_integration" );
$_env_api_descr        = __( "Se marcado, a comunicação será feita com o servidor de HOMOLOGAÇÃO da Muifabrica.
								Se não, será feita com o servidor real, de PRODUÇÃO.",                           "muifabrica_integration" );

$_debug                = ( !empty( get_option( "_mfi_debug" ) ) ) ? "checked" : "";
$_debug_label          = __( "Log de depuração",                                                                 "muifabrica_integration" );
$_debug_input          = __( "Ativar / Desativar",                                                               "muifabrica_integration" );
$_debug_descr          = __( "Se ativado, irá registrar logs detalhados. Caso não, apenas logs críticos.",       "muifabrica_integration" );

$_app_id_hml           = get_option( "_mfi_app_id_hml"     );
$_app_id_hml_label     = __( "App ID Homologação",                                                               "muifabrica_integration" );
$_app_id_hml_descr     = __( "App ID do ambiente de testes (homologação).",                                      "muifabrica_integration" );

$_app_secret_hml       = get_option( "_mfi_app_secret_hml" );
$_app_secret_hml_label = __( "App Secret Homologação",                                                           "muifabrica_integration" );
$_app_secret_hml_descr = __( "App Secret do ambiente de testes (homologação).",                                  "muifabrica_integration" );

$_app_id               = get_option( "_mfi_app_id"         );
$_app_id_label         = __( "App ID",                                                                           "muifabrica_integration" );
$_app_id_descr         = __( "App ID do ambiente real (produção).",                                              "muifabrica_integration" );

$_app_secret           = get_option( "_mfi_app_secret"     );
$_app_secret_label     = __( "App Secret",                                                                       "muifabrica_integration" );
$_app_secret_descr     = __( "App Secret do ambiente real (produção).",                                          "muifabrica_integration" );

?>

<section>
	<div class="wrap-mfi" >
		<h2 class="wp-heading-inline" style="display:inline;" >
			<?php _e( "Configurações", "muifabrica_integration" ); ?>
		</h2>

		<form id="mfi-general-form" class="mfi-form" method="post" action="<?php echo admin_url( "admin-post.php" ); ?>" >
			<input type="hidden" name="action" value="mfi_save_settings" />
			<?php wp_nonce_field( "mfi_save_settings" ); ?>

			<table class="form-table" >
				<tbody>
					<!-- on/off homol -->
					<tr valign="top" >
						<th scope="row" class="titledesc" >
							<label for="mfi_env_api" >
								<?php echo $_env_api_label; ?>
							</label>
						</th>
						<td class="forminp" >
							<input id="mfi_env_api" class="input-text regular-input" type="checkbox" name="mfi_env_api" <?php echo $_env_api; ?> >
							<?php echo $_env_api_input; ?>

							<p class="description" >
								<?php echo $_env_api_descr; ?>
							</p>
						</td>
					</tr>

					<!-- hml id -->
					<tr valign="top" >
						<th scope="row" class="titledesc" >
							<label for="mfi_app_id_hml" >
								<?php echo $_app_id_hml_label; ?>
							</label>
						</th>
						<td class="forminp" >
							<input id="mfi_app_id_hml" class="input-text regular-input" type="password" name="mfi_app_id_hml"
								autocomplete="mfi_app_id_hml" value="<?php echo $_app_id_hml; ?>" >

							<button id="mfi-btn-show-pswd-mfi_app_id_hml" type="button" class="button button-secondary wp-hide-pw hide-if-no-js"
								data-toggle="0" onclick="mfiShowPassword( this )" >
								<span class="dashicons dashicons-visibility" aria-hidden="true" ></span>
							</button>

							<p class="description mfi-descr" >
								<?php echo $_app_id_hml_descr; ?>
							</p>
						</td>
					</tr>
					<!-- hml secret -->
					<tr valign="top" >
						<th scope="row" class="titledesc" >
							<label for="mfi_app_secret_hml" >
								<?php echo $_app_secret_hml_label; ?>
							</label>
						</th>
						<td class="forminp" >
							<input id="mfi_app_secret_hml" class="input-text regular-input" type="password" name="mfi_app_secret_hml"
								autocomplete="mfi_app_secret_hml" value="<?php echo $_app_secret_hml; ?>" >

							<button id="mfi-btn-show-pswd-mfi_app_secret_hml" type="button" class="nickelpay-show-pw button button-secondary wp-hide-pw hide-if-no-js"
								data-toggle="0" onclick="mfiShowPassword( this )" >
								<span class="dashicons dashicons-visibility" aria-hidden="true" ></span>
							</button>

							<p class="description mfi-descr" >
								<?php echo $_app_secret_hml_descr; ?>
							</p>
						</td>
					</tr>

					<!-- prod id -->
					<tr valign="top" >
						<th scope="row" class="titledesc" >
							<label for="mfi_app_id" >
								<?php echo $_app_id_label; ?>
							</label>
						</th>
						<td class="forminp" >
							<input id="mfi_app_id" class="input-text regular-input" type="password" name="mfi_app_id"
								autocomplete="mfi_app_id" value="<?php echo $_app_id; ?>" >

							<button id="mfi-btn-show-pswd-mfi_app_id" type="button" class="nickelpay-show-pw button button-secondary wp-hide-pw hide-if-no-js"
								data-toggle="0" onclick="mfiShowPassword( this )" >
								<span class="dashicons dashicons-visibility" aria-hidden="true" ></span>
							</button>

							<p class="description mfi-descr" >
								<?php echo $_app_id_descr; ?>
							</p>
						</td>
					</tr>
					<!-- prod secret -->
					<tr valign="top" >
						<th scope="row" class="titledesc" >
							<label for="mfi_app_secret" >
								<?php echo $_app_secret_label; ?>
							</label>
						</th>
						<td class="forminp" >
							<input id="mfi_app_secret" class="input-text regular-input" type="password" name="mfi_app_secret"
								autocomplete="mfi_app_secret" value="<?php echo $_app_secret; ?>" >

							<button id="mfi-btn-show-pswd-mfi_app_secret" type="button" class="nickelpay-show-pw button button-secondary wp-hide-pw hide-if-no-js"
								data-toggle="0" onclick="mfiShowPassword( this )" >
								<span class="dashicons dashicons-visibility" aria-hidden="true" ></span>
							</button>

							<p class="description mfi-descr" >
								<?php echo $_app_secret_descr; ?>
							</p>
						</td>
					</tr>

					<!-- on/off debug -->
					<tr valign="top" >
						<th scope="row" class="titledesc" >
							<label for="mfi_debug" >
								<?php echo $_debug_label; ?>
							</label>
						</th>
						<td class="forminp" >
							<input id="mfi_debug" class="input-text regular-input" type="checkbox" name="mfi_debug" <?php echo $_debug; ?> >
							<?php echo $_debug_input; ?>
							<p class="description" >
								<?php echo $_debug_descr; ?>
							</p>
							<p class="description" >
								<?php echo $debug; ?>
							</p>
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
