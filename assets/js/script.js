
( function( $ ) {
    "use strict";

    window.mfiShowPassword = function( e )
	{
		let el = $( `#${e.id}` );

		if ( el.children().attr( "class" ) == "dashicons dashicons-visibility" )
		{
			el.children().removeClass( "dashicons-visibility" );
			el.children().addClass( "dashicons-hidden" );
			el.prev().attr( "type", "text" );
		}
		else
		{
			el.children().removeClass( "dashicons-hidden" );
			el.children().addClass( "dashicons-visibility" );
			el.prev().attr( "type", "password" );
		}
    }

    function mfiShowLoad()
    {
        $( "#mfi-modal-load" ).removeClass( "mfi-d-none" );
    }

	function mfiHandleKeys()
	{
		let env    = $( "#mfi_env_api"        ),
		    ai_hml = $( "#mfi_app_id_hml"     ),
			as_hml = $( "#mfi_app_secret_hml" ),
			ai_prd = $( "#mfi_app_id"         ),
			as_prd = $( "#mfi_app_secret"     );

        if ( env.is( ":checked" ) )
        {
            ai_hml.closest( "tr" ).show();
            as_hml.closest( "tr" ).show();
            ai_hml.prop( "required", true );
            as_hml.prop( "required", true );

            ai_prd.closest( "tr" ).hide();
            as_prd.closest( "tr" ).hide();
            ai_prd.removeAttr( "required" );
            as_prd.removeAttr( "required" );
        }
        else
        {
            ai_hml.closest( "tr" ).hide();
            as_hml.closest( "tr" ).hide();
            ai_hml.removeAttr( "required" );
            as_hml.removeAttr( "required" );

            ai_prd.closest( "tr" ).show();
            as_prd.closest( "tr" ).show();
            ai_prd.prop( "required", true );
            as_prd.prop( "required", true );
        }
	}

    function mfiPreventSubmitBulkActions( e, a )
    {
        if ( a == "-1" ) return;

        const query  = window.location.search;
        const params = new URLSearchParams( query );

        const page   = params.has( "page" ) ? params.get( "page" ) : 0;
        if ( page == 0 || page != "muifabrica_integration" ) return;

        // const tab    = params.has( "tab"  ) ? params.get( "tab"  ) : 0;
        // if ( tab  == 0 || tab  == "products"          ) return;

        e.preventDefault();

        if ( $( '#mfi-preview_products-list-table-form' ).length > 0 )
        {
            const preview_products = $( '#mfi-preview_products-list-table-form input[name^="products"]:checked' );

            if ( preview_products.length == 0 )
            {
                alert( mfi_params.messages.select_preview );
                return;
            }

            $( "#mfi-modal-load" ).removeClass( "mfi-d-none" );
            $( "#mfi-preview_products-list-table-form" ).submit();

            return;
        }
    }

    $( document ).on( "submit", "#mfi-general-form", function() { mfiShowLoad();   } );
	$( document ).on( "change", "#mfi_env_api",      function() { mfiHandleKeys(); } );

	$( document ).ready( function() { mfiHandleKeys(); } );

	$( document ).on( "click", "#doaction",  function( e )
    {
        const action = $( "#bulk-action-selector-top"    ).val();
        mfiPreventSubmitBulkActions( e, action );
    } );
	$( document ).on( "click", "#doaction2", function( e )
    {
        const action = $( "#bulk-action-selector-bottom" ).val();
        mfiPreventSubmitBulkActions( e, action );
    } );

}( jQuery ) );