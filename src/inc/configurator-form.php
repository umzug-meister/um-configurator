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
										v-on:keydown="originKeydown"
										>
									<ul class="autocomplete-variants">
										<li v-for="(item, index) in originAddressVariants" v-bind:class="{ focused: index==originIndex }" v-html="item.description" v-on:mousedown="selectOriginAddress(item.description)"></li>
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
										v-on:keydown="targetKeydown"
										v-on:focusout="deleteDestinationAutocomplete" >
									<ul class="autocomplete-variants">
										<li v-for="(item, index) in destinationAddressVariants" v-bind:class="{ focused: index==targetIndex }" v-html="item.description" v-on:mousedown="selectDestinationAddress(item.description)"></li>
									</ul>
								</div>
								<span class="error-message" v-html="destinationAddressMessage"></span>
							</div>

							<div class="form-date-selector">
								<span class="form-title">Umzugsdatum</span>
								<div class="two-inputs two-inputs--no-margin two-inputs--centered-all">

									<div class="two-inputs--col">
										<label class="form-radios-label" v-on:click="selectMod(1)">
											Fixdatum
											<input name="moving_date" type="radio" checked="checked" class="form-radios">
											<span class="form-radios-checkmark"></span>
										</label>
									</div>

									<div class="two-inputs--col">
										<label class="form-radios-label" v-on:click="selectMod(2)">
											Von-bis
											<input name="moving_date" type="radio" class="form-radios">
											<span class="form-radios-checkmark"></span>
										</label>
									</div>

								</div>
							</div>

							<div v-bind:class="[ 'input-field', ifDateCO ]" v-show="1 === movementMod">
								<label for="movement-date">
									<i class="icon-kalender"></i> Datum
								</label>
								<date-picker @update-movement-date="updateDate" movement-date="movementDate"></date-picker>
								<span class="error-message" v-html="movementDateMessage"></span>
							</div>

							<div class="two-inputs" v-show="2 === movementMod">
								<div class="two-inputs--col">
									<div v-bind:class="[ 'input-field', ifDateFromCO ]">
										<label for="movement-date">
											<i class="icon-kalender"></i> von
										</label>
										<date-picker @update-movement-date="updateDateFrom" movement-date="movementDateFrom"></date-picker>
										<span class="error-message" v-html="movementDateFromMessage"></span>
									</div>
								</div>
								<div class="two-inputs--col">
									<div v-bind:class="[ 'input-field', ifDateToCO ]">
										<label for="movement-date">
											<i class="icon-kalender"></i> bis
										</label>
										<date-picker @update-movement-date="updateDateTo" movement-date="movementDateTo"></date-picker>
										<span class="error-message" v-html="movementDateToMessage"></span>
									</div>
								</div>
							</div>

						</fieldset>
						<div class="text-center">
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
</div>
<?php endif; ?>
