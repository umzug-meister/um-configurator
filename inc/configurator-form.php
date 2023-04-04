<?php
/**
 * Umzugmeister Konfigurator Settings Page Fields
 *
 * Fields HTML for Settingspage.
 *
 * @package UmConfigurator
 */

?>

<?php if ( function_exists( 'the_field' ) ) : ?>
<div class="configurator">
	<span class="configurator__close">✕</span>
	<div class="container">
		<div class="configurator__inner">
			<div class="box box--blue">
				<div class="box__body">
					<p class="box__title"><?php the_field( 'configurator_title' ); ?></p>

					<div class="form form--configurator" id="configurator-form">
						<div class="loading-screen">
							<div class="lds-dual-ring"></div>
						</div>
						<fieldset>
							<div v-bind:class="[ 'input-field', ifOriginAddrCO ]">
								<label for="address-out">
									<i class="icon-haus"></i> Auszugsadresse
								</label>
								<div class="input-wrapper">
									<input
										id="address-out"
										type="text"
										name="address-out"
										v-model="originAddress"
										v-on:input="getOriginAutocomplete"
										v-on:focusout="deleteOriginAutocomplete"
										>
									<ul class="autocomplete-variants">
										<li v-for="item in originAddressVariants" v-html="item.description" v-on:click="selectOriginAddress(item.description)"></li>
									</ul>
								</div>
								<span class="error-message" v-html="originAddressMessage"></span>
							</div>

							<div v-bind:class="[ 'input-field', ifDestAddrCO ]">
								<label for="address-in">
									<i class="icon-map"></i> Einzugsadresse
								</label>
								<div class="input-wrapper">
									<input
										id="address-in"
										type="text"
										name="address-in"
										v-model="destinationAddress"
										v-on:input="getDestinationAutocomplete"
										v-on:focusout="deleteDestinationAutocomplete" >
									<ul class="autocomplete-variants">
										<li v-for="item in destinationAddressVariants" v-html="item.description" v-on:click="selectDestinationAddress(item.description)"></li>
									</ul>
								</div>
								<span class="error-message" v-html="destinationAddressMessage"></span>
							</div>

							<div v-bind:class="[ 'input-field', ifDateCO ]">
								<label for="movement-date">
									<i class="icon-kalender"></i> Umzugsdatum
								</label>
								<date-picker @update-movement-date="updateDate" movement-date="movementDate"></date-picker>
								<span class="error-message" v-html="movementDateMessage"></span>
							</div>

						</fieldset>

						<button
							type="submit"
							class="btn btn--secondary"
							:disabled="!buttonActive"
							v-on:click="createInputInformations" ><?php the_field( 'configurator_button' ); ?></button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>
