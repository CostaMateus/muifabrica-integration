
( function( $ ) {
    "use strict";

    // $( function() {
    //     $( "#set_new_key" ).click( function() {
    //         const query   = window.location.search;
    //         const params  = new URLSearchParams( query );
    //         const user_id = params.has( "user_id" ) ? params.get( "user_id" ) : 0;

    //         $( "#key_set" ).css( "display", "none" );

    //         let span      = $( this ).find( "span" );
    //         span.css( "display", "inline-block" );

    //         let error     = $( "#set_key_error" );
    //         error.css( "display", "none" ).html( "" );

    //         $.ajax( {
    //             url  : woo_mfi_params.ajax_url,
    //             type : "POST",
    //             data : {
    //                 action : "generate_secret",
    //                 id     : user_id,
    //             },
    //             success: function( response ) {
    //                 $( "#new_key" ).css( "display", "block" );
    //                 $( "#woo_ds_new_key" ).val( response.data );
    //                 span.css( "display", "none" );
    //             },
    //             error: function( err ) {
    //                 console.log( err );
    //                 error.css( "display", "block" ).html( err.responseJSON.data );
    //                 span.css( "display", "none" );
    //             },
    //         } );
    //     } );

    //     $( "#woo_ds_copy" ).click( function() {
    //         let copyText = document.getElementById( "woo_ds_new_key" );
    //         copyText.select();
    //         copyText.setSelectionRange( 0, 99999 );
    //         document.execCommand( "copy" );

    //         $( "#key_copied" ).css( "display", "block" );

    //         setTimeout( function() {
    //             $( "#key_copied" ).css( "display", "none" );
    //         }, 4000 );

    //         return false;
    //     } );

    //     // =========================================================

    //     $( "#doaction"  ).click( function ( e ) {
    //         const action = $( "#bulk-action-selector-top"    ).val();
    //         preventSubmitBulkActions( e, action );
    //     } );

    //     $( "#doaction2" ).click( function ( e ) {
    //         const action = $( "#bulk-action-selector-bottom" ).val();
    //         preventSubmitBulkActions( e, action );
    //     } );

    //     function preventSubmitBulkActions( e, a )
    //     {
    //         if ( a == "-1" ) return;

    //         const query  = window.location.search;
    //         const params = new URLSearchParams( query );

    //         const page   = params.has( "page" ) ? params.get( "page" ) : 0;
    //         if ( page == 0 || page != "muifabrica_integration" ) return;

    //         const tab    = params.has( "tab"  ) ? params.get( "tab"  ) : 0;
    //         if ( tab  == 0 || tab  == "products"               ) return;

    //         e.preventDefault();

    //         if ( $( '#mfi-users-list-table-form' ).length > 0 )
    //         {
    //             const users = $( '#mfi-users-list-table-form input[name^="users"]:checked' );

    //             if ( users.length == 0 )
    //             {
    //                 alert( woo_mfi_params.messages.select_users );
    //                 return;
    //             }

    //             if ( a == "del_users" )
    //             {
    //                 if ( confirm( woo_mfi_params.messages.del_users ) )
    //                 {
    //                     $( "#mfi-modal-load" ).removeClass( "mfi-d-none" );
    //                     $( "#mfi-users-list-table-form" ).submit();
    //                 }
    //             }
    //             else if ( a == "del_products" )
    //             {
    //                 if ( confirm( woo_mfi_params.messages.del_products ) )
    //                 {
    //                     $( "#mfi-modal-load" ).removeClass( "mfi-d-none" );
    //                     $( "#mfi-users-list-table-form" ).submit();
    //                 }
    //             }
    //             else if ( a == "preview" )
    //             {
    //                 if ( users.length > 1 )
    //                 {
    //                     alert( woo_mfi_params.messages.preview_pdcts );
    //                     return;
    //                 }

    //                 $( "#mfi-modal-load" ).removeClass( "mfi-d-none" );
    //                 $( "#mfi-users-list-table-form" ).submit();
    //             }
    //             else
    //             {
    //                 $( "#mfi-modal-load" ).removeClass( "mfi-d-none" );
    //                 $( "#mfi-users-list-table-form" ).submit();
    //             }

    //             return;
    //         }

    //         if ( $( '#mfi-preview_products-list-table-form' ).length > 0 )
    //         {
    //             const preview_products = $( '#mfi-preview_products-list-table-form input[name^="products"]:checked' );

    //             if ( preview_products.length == 0 )
    //             {
    //                 alert( woo_mfi_params.messages.select_preview );
    //                 return;
    //             }

    //             $( "#mfi-modal-load" ).removeClass( "mfi-d-none" );
    //             $( "#mfi-preview_products-list-table-form" ).submit();

    //             return;
    //         }
    //     }

    //     // =========================================================

    //     $( "#mfi-settings-form" ).on( "submit", function() {
    //         $( "#mfi-modal-load" ).removeClass( "mfi-d-none" );
    //     } );

    //     $( "#bulk-action-selector-top"    ).on( "change", function() {
    //         show_bulk_actions_message( $( this ).val() );
    //     } );
    //     $( "#bulk-action-selector-bottom" ).on( "change", function() {
    //         show_bulk_actions_message( $( this ).val() );
    //     } );

    //     function show_bulk_actions_message( val )
    //     {
    //         let e = "";

    //         $( ".mfi-bulkactions-sync"         ).addClass( "mfi-d-none" );
    //         $( ".mfi-bulkactions-import"       ).addClass( "mfi-d-none" );
    //         $( ".mfi-bulkactions-del-products" ).addClass( "mfi-d-none" );
    //         $( ".mfi-bulkactions-del-users"    ).addClass( "mfi-d-none" );

    //         switch ( val )
    //         {
    //             case "sync":
    //             case "preview":
    //                 e = ".mfi-bulkactions-sync";
    //             break;

    //             case "import":
    //                 e = ".mfi-bulkactions-import";
    //             break;

    //             case "del_products":
    //                 e = ".mfi-bulkactions-del-products";
    //             break;

    //             case "del_users":
    //                 e = ".mfi-bulkactions-del-users";
    //             break;
    //         }

    //         $( e ).removeClass( "mfi-d-none" );
    //     }

    //     // =========================================================

    //     $( "#mfi-instructions-toggle" ).click( function( e ) {
    //         if ( $( "#mfi-instructions" ).hasClass( "mfi-d-none" ) )
    //             $( "#mfi-instructions" ).removeClass( "mfi-d-none" );
    //         else
    //             $( "#mfi-instructions" ).addClass( "mfi-d-none" );
    //     } );

    //     $( "#mfi-tag-supplier" ).change( function() {
    //         $( "#mfi-tag-error-type" ).addClass( "mfi-d-none" );

    //         let supp_types = [
    //             "application/pdf",
    //             "image/jpeg",
    //             "image/png",
    //         ];

    //         if ( supp_types.includes( this.files[ 0 ].type ) )
    //             $( "#mfi-tag-supplier-label" ).html( this.files[ 0 ].name );
    //         else
    //             $( "#mfi-tag-error-type" ).removeClass( "mfi-d-none" );

    //         console.log( this.files[ 0 ] );
    //     } );

    //     $( "#mfi-send-tag" ).on( "click", function() {
    //         let s_id  = $( "#mfi-supplier-id"  ).val();
    //         let f_tag = $( "#mfi-tag-supplier" ).val();

    //         if ( s_id && f_tag ) $( "#mfi-modal-load" ).removeClass( "mfi-d-none" );
    //     } );

    // } );



	function handleKeys()
	{
		let env    = $( "#mfi_api"            ),
		    ai_hml = $( "#mfi_app_id_hml"     ),
			as_hml = $( "#mfi_app_secret_hml" ),
			ai_prd = $( "#mfi_app_id"         ),
			as_prd = $( "#mfi_app_secret"     );

        if ( env.is( ":checked" ) )
        {
            ai_hml.closest( "tr" ).show();
            as_hml.closest( "tr" ).show();
            ai_prd.closest( "tr" ).hide();
            as_prd.closest( "tr" ).hide();

            ai_hml.prop( "required", true );
            as_hml.prop( "required", true );
            ai_prd.removeAttr( "required" );
            as_prd.removeAttr( "required" );
        }
        else
        {
            ai_hml.closest( "tr" ).hide();
            as_hml.closest( "tr" ).hide();
            ai_prd.closest( "tr" ).show();
            as_prd.closest( "tr" ).show();

            ai_hml.removeAttr( "required" );
            as_hml.removeAttr( "required" );
            ai_prd.prop( "required", true );
            as_prd.prop( "required", true );
        }
	}

	$( document ).on( "change", "#mfi_api", function() { handleKeys(); } );
	$( document ).ready(                    function() { handleKeys(); } );

}( jQuery ) );