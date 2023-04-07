jQuery( document ).ready( function( $ ) {
	var placeNameElement = $( '#' + UMCONFADMIN.placeNameElementId );
	var placeIdElement = $( '#' + UMCONFADMIN.placeIdElementId );
	var xhr = undefined;
	if ( placeNameElement ) {

		placeNameElement.on( 'input', function() {
			addressChunk = $( this ).val().trim();
			if ( undefined !== xhr ) {
				xhr.abort();
			}

			xhr = $.ajax({
				dataType: 'json',
				url: UMCONFADMIN.baseUrl + 'um-configurator/v1/autocomplete/' + addressChunk,
				success: function( data ) {
					$( '#umconf-autocomplete' ).remove();
					ulElement = document.createElement( 'ul' );
					ulElement.id = 'umconf-autocomplete';

					data.results.forEach( function( result ) {
						liElement = document.createElement( 'li' );
						aElement = document.createElement( 'a' );
						textElement = document.createTextNode( result.description );

						aElement.appendChild( textElement );

						aElement.addEventListener( 'click', function( e ) {
							e.preventDefault();
							placeIdElement.val( this.getAttribute( 'data-place-id' ) );
							placeNameElement.val( this.innerHTML );
							$( '#umconf-autocomplete' ).remove();
						});

						aElement.setAttribute( 'href', 'javascript:void(0);' );
						aElement.setAttribute( 'data-place-id', result.place_id );

						liElement.appendChild( aElement );
						ulElement.appendChild( liElement );
					});

					$( ulElement ).insertAfter( placeNameElement );
				}
			});
		});
	}
});
