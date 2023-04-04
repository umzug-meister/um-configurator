var regexpbrowser = /chrom(e|ium)/;
var app = undefined;
var temparray = [];
window.umconfigurator = true;
jQuery.browser.chrome = regexpbrowser.test( navigator.userAgent.toLowerCase( ) );

jQuery( document ).ready( function( $ ) {

	if ( $.browser.chrome ) {
		$( '#address-out' ).attr( 'autocomplete', 'off_' + Math.random().toString( 36 ).substring( 2 ) + Date.now() );
		$( '#address-in' ).attr( 'autocomplete', 'off_' + Math.random().toString( 36 ).substring( 2 ) + Date.now() );
	} else {
		$( '#address-out' ).attr( 'autocomplete', 'off' );
		$( '#address-in' ).attr( 'autocomplete', 'off' );
	}

	$( '.btn--open-overlay' ).click( function( e ) {
		e.preventDefault();
		$( '.configurator' ).addClass( 'configurator--sticky' );
		$( 'body' ).addClass( 'no-scroll' );
	});

	$( '.configurator__close' ).click( function( e ) {
		e.preventDefault();
		$( '.configurator' ).removeClass( 'configurator--sticky' );
		$( 'body' ).removeClass( 'no-scroll' );
	});

	$( '.input-field input' ).on( 'input', function() {
		var input = $( this );
		var parent = input.closest( '.input-field' );
		if ( 0 === ( input.val().length ) ) {
			parent.removeClass( 'has-content' );
		} else {
			parent.addClass( 'has-content' );
		}
	});

	$( '.input-field input' ).on( 'focus', function() {
		var input = $( this );
		var parent = input.closest( '.input-field' );
		parent.addClass( 'has-focus' );
	});

	$( '.input-field input' ).on( 'blur', function() {
		var input = $( this );
		var parent = input.closest( '.input-field' );
		parent.removeClass( 'has-focus' );
	});

	$( '#address-out' ).on( 'input', function( e ) {
		console.log( e );
	});

	if ( document.getElementById( 'configurator-form' ) ) {
		Vue.component( 'date-picker', {
			template: '<input/>',
			props: [ 'movementDate' ],
			mounted: function() {
				var self = this;
				$( this.$el ).datepicker({
					prevText: '&#x3c;zurück',
					prevStatus: '',
					prevJumpText: '&#x3c;&#x3c;',
					prevJumpStatus: '',
					nextText: 'Vor&#x3e;',
					nextStatus: '',
					nextJumpText: '&#x3e;&#x3e;',
					nextJumpStatus: '',
					currentText: 'heute',
					currentStatus: '',
					todayText: 'heute',
					todayStatus: '',
					clearText: '-',
					clearStatus: '',
					closeText: 'schließen',
					closeStatus: '',
					monthNames: [ 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
					'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember' ],
					monthNamesShort: [ 'Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun',
					'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez' ],
					dayNames: [ 'Sonntag', 'Montag', 'Dienstag', 'Mittwoch',
					'Donnerstag', 'Freitag', 'Samstag' ],
					dayNamesShort: [ 'So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa' ],
					dayNamesMin: [ 'So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa' ],
					showMonthAfterYear: false,
					timepicker: false,
					minDate: '+1',
					dateFormat: 'dd.mm.yy',
					onSelect: function( date ) {
						self.$emit( 'update-movement-date', date );
					}
				});

				$( this.$el ).on( 'input', function( e ) {
					self.$emit( 'update-movement-date', $( this ).val() );
				});
			},
			beforeDestroy: function() {
				$( this.$el ).datepicker( 'hide' ).datepicker( 'destroy' );
			}
		});

		app = new Vue({
			el: '#configurator-form',
			data: {
				originAddress: '',
				originAddressMessage: '',
				originAddressVariants: [],
				destinationAddress: '',
				destinationAddressMessage: '',
				destinationAddressVariants: [],
				movementDate: '',
				movementDateMessage: '',
				processing: false
			},
			methods: {
				updateDate: function( date ) {
					this.movementDate = date;
					this.movementDateMessage = '';
				},
				selectOriginAddress: function( value ) {
					this.originAddress = value;
				},
				selectDestinationAddress: function( value ) {
					this.destinationAddress = value;
				},
				getOriginAutocomplete: function() {
					var self = this;

					self.originAddressMessage = '';
					if ( 4 < self.originAddress.length ) {
						$.ajax({
							dataType: 'json',
							url: UMCONFUrls.baseUrl + 'um-configurator/v1/autocomplete/' + self.originAddress,
							success: function( data ) {
								temparray = [];
								data.results.forEach( function( element ) {
									if ( self.originAddress !== element.description ) {
										temparray.push( element );
									}
								});
								self.originAddressVariants = temparray;
							}
						});
					}
				},
				deleteOriginAutocomplete: function() {
					var self = this;
					setTimeout( function() {
						self.originAddressVariants = [];
					}, 100 );
				},
				getDestinationAutocomplete: function() {
					var self = this;

					self.destinationAddressMessage = '';
					if ( 4 < self.destinationAddress.length ) {
						$.ajax({
							dataType: 'json',
							url: UMCONFUrls.baseUrl + 'um-configurator/v1/autocomplete/' + self.destinationAddress,
							success: function( data ) {
								temparray = [];
								data.results.forEach( function( element ) {
									if ( self.destinationAddress !== element.description ) {
										temparray.push( element );
									}
								});
								self.destinationAddressVariants = temparray;
							}
						});
					}
				},
				deleteDestinationAutocomplete: function() {
					var self = this;
					setTimeout( function() {
						self.destinationAddressVariants = [];
					}, 100 );
				},
				createInputInformations: function() {
					var self = this;
					self.processing = true;
					$.ajax({
						dataType: 'json',
						type: 'PUT',
						url: UMCONFUrls.baseUrl + 'um-configurator/v1/inputinformations/',
						data: {
							'originAddress': self.originAddress,
							'destinationAddress': self.destinationAddress,
							'date': self.movementDate
						},
						success: function( data ) {
							self.processing = false;
							if ( undefined !== data.location ) {
								window.location = data.location;
							}
						},
						error: function( data ) {
							self.processing = false;

							if ( undefined !== data.responseJSON.origin_address_message ) {
								self.originAddressMessage = data.responseJSON.origin_address_message;
							}

							if ( undefined !== data.responseJSON.destination_address_message ) {
								self.destinationAddressMessage = data.responseJSON.destination_address_message;
							}
						}
					});
				}
			},
			computed: {
				ifOriginAddrCO: function() {
					return {
						'input-field--has-content': '' !== this.originAddress,
						'input-field--has-error': '' !== this.originAddressMessage,
						'input-field--has-variants': 0 < this.originAddressVariants.length
					};
				},
				ifDestAddrCO: function() {
					return {
						'input-field--has-content': '' !== this.destinationAddress,
						'input-field--has-error': '' !== this.destinationAddressMessage,
						'input-field--has-variants': 0 < this.destinationAddressVariants.length
					};
				},
				ifDateCO: function() {
					return {
						'input-field--has-content': '' !== this.movementDate,
						'input-field--has-error': '' !== this.movementDateMessage
					};
				},
				buttonActive: function() {
					return ( '' !== this.originAddress ) &&
						( '' !== this.destinationAddress ) &&
						( '' !== this.movementDate ) &&
						( '' == this.originAddressMessage ) &&
						( '' == this.destinationAddressMessage ) &&
						( '' == this.movementDateMessage ) &&
						! this.processing;
				}

			},
			mounted: function() {
				$( '.form--configurator .loading-screen' ).fadeOut();
			}
		});
	}

});
