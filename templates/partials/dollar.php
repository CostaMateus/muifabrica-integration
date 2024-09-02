<!-- dollar -->
<tr valign="top" >
    <th scope="row" class="titledesc" >
        <label for="mfi_env_api" >
            Cotação do dólar
        </label>
    </th>
    <td class="forminp" >
        <?php echo "R$ " . ( new \Muifabrica_Integration\Api\Muifabrica_Api() )->get_dollar_price(); ?>

        <p class="description" >
            Atualizado a cada 15 minutos
        </p>
    </td>
</tr>