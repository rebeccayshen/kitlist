<?php
/**
 * API
 * 
 * Generates html code for fields
 * 
 * 
 * @package default
 * @author Max Tsiplyakov
 */
class PMXI_API 
{
	/**
	 * Function for generating html code for fields
	 * @param string $field_type simple, enum or textarea
	 * @param string $label field label
	 * @param array $params contains field params like tooltip, enum_values, mapping, field_name, field_value	 
	 */
	public static function add_field( $field_type = 'simple', $label = '', $params = array()){
		
		$params += array(
			'tooltip' => '',
			'enum_values' => array(),
			'mapping' => false,
			'mapping_key' => '',
			'mapping_rules' => array(),
			'xpath' => '',
			'field_name' => '',
			'field_value' => ''
		);

		ob_start();
		if ($label != ""){
			?>
			<label for="<?php echo sanitize_title($params['field_name']); ?>"><?php echo $label;?></label>			
			<?php
		}
		if ( ! empty($params['tooltip'])){
			?>
			<a href="#help" class="wpallimport-help" title="<?php echo $params['tooltip']; ?>" style="position: relative; top: -2px;">?</a>
			<?php
		}
		?>
		<div class="input">
		<?php
		switch ($field_type){
			case 'simple':
				?>
				<input type="text" name="<?php echo $params['field_name']; ?>" id="<?php echo sanitize_title($params['field_name']); ?>" value="<?php echo $params['field_value']; ?>" style="width:100%;"/>
				<?php
				break;
			case 'enum':
				?>
				
				<?php foreach ($params['enum_values'] as $value):?>
				<div class="form-field wpallimport-radio-field">
					<input type="radio" id="<?php echo sanitize_title($params['field_name']); ?>_<?php echo $value; ?>" class="switcher" name="<?php echo $params['field_name']; ?>" value="<?php echo $value; ?>" <?php echo $value == $params['field_value'] ? 'checked="checked"': '' ?>/>
					<label for="<?php echo sanitize_title($params['field_name']); ?>_<?php echo $value; ?>"><?php echo $value; ?></label>
				</div>
				<?php endforeach;?>							
				<div class="form-field wpallimport-radio-field">
					<input type="radio" id="<?php echo sanitize_title($params['field_name']); ?>_xpath" class="switcher" name="<?php echo $params['field_name']; ?>" value="xpath" <?php echo 'xpath' == $params['field_value'] ? 'checked="checked"': '' ?>/>
					<label for="<?php echo sanitize_title($params['field_name']); ?>_xpath"><?php _e('Set with XPath', 'pmxi_plugin' )?></label>
					<span class="wpallimport-clear"></span>
					<div class="switcher-target-<?php echo sanitize_title($params['field_name']); ?>_xpath set_with_xpath">
						<span class="wpallimport-slide-content" style="padding-left:0px;">
							<table class="form-table custom-params" style="max-width:none; border:none;">
								<tr class="form-field">
									<td class="wpallimport-enum-input-wrapper">
										<input type="text" class="smaller-text" name="pmre[xpaths][<?php echo $params['mapping_key']; ?>]" value="<?php echo esc_attr($params['xpath']) ?>"/>	
									</td>
									<td class="action">
										<?php if ($params['mapping']): ?>

										<?php $custom_mapping_rules = (!empty($params['mapping_rules'])) ? json_decode($params['mapping_rules'], true) : false; ?>
										
										<div class="input wpallimport-custom-fields-actions">
											<a href="javascript:void(0);" class="wpallimport-cf-options"><?php _e('Field Options...', 'pmxi_plugin'); ?></a>
											<ul id="wpallimport-cf-menu-<?php echo sanitize_title($params['field_name']);?>" class="wpallimport-cf-menu">						
												<li class="<?php echo ( ! empty($custom_mapping_rules) ) ? 'active' : ''; ?>">
													<a href="javascript:void(0);" class="set_mapping pmxi_cf_mapping" rel="cf_mapping_<?php echo sanitize_title($params['field_name']); ?>"><?php _e('Mapping', 'pmxi_plugin'); ?></a>
												</li>
											</ul>														
										</div>
										<div id="cf_mapping_<?php echo sanitize_title($params['field_name']); ?>" class="custom_type" rel="mapping" style="display:none;">
											<fieldset>
												<table cellpadding="0" cellspacing="5" class="cf-form-table" rel="cf_mapping_<?php echo sanitize_title($params['field_name']); ?>">
													<thead>
														<tr>
															<td><?php _e('In Your File', 'pmxi_plugin') ?></td>
															<td><?php _e('Translated To', 'pmxi_plugin') ?></td>
															<td>&nbsp;</td>						
														</tr>
													</thead>
													<tbody>	
														<?php																																	
															if ( ! empty($custom_mapping_rules) and is_array($custom_mapping_rules)){
																
																foreach ($custom_mapping_rules as $key => $value) {

																	$k = $key;

																	if (is_array($value)){
																		$keys = array_keys($value);
																		$k = $keys[0];
																	}

																	?>
																	<tr class="form-field">
																		<td>
																			<input type="text" class="mapping_from widefat" value="<?php echo $k; ?>">
																		</td>
																		<td>
																			<input type="text" class="mapping_to widefat" value="<?php echo (is_array($value)) ? $value[$k] : $value; ?>">
																		</td>
																		<td class="action remove">
																			<a href="#remove" style="right:-10px;"></a>
																		</td>
																	</tr>
																	<?php
																}
															}
															else{
																if ( ! empty($params['enum_values']) and is_array($params['enum_values'])){
																	foreach ($params['enum_values'] as $value){
																	?>
																	<tr class="form-field">
																		<td>
																			<input type="text" class="mapping_from widefat">
																		</td>
																		<td>
																			<input type="text" class="mapping_to widefat" value="<?php echo $value; ?>">
																		</td>
																		<td class="action remove">
																			<a href="#remove" style="right:-10px;"></a>
																		</td>
																	</tr>
																	<?php
																	}
																} else {
																	?>
																	<tr class="form-field">
																		<td>
																			<input type="text" class="mapping_from widefat">
																		</td>
																		<td>
																			<input type="text" class="mapping_to widefat">
																		</td>
																		<td class="action remove">
																			<a href="#remove" style="right:-10px;"></a>
																		</td>
																	</tr>
																	<?php
																}
															}
														?>												
														<tr class="form-field template">
															<td>
																<input type="text" class="mapping_from widefat">
															</td>
															<td>
																<input type="text" class="mapping_to widefat">
															</td>
															<td class="action remove">
																<a href="#remove" style="right:-10px;"></a>
															</td>
														</tr>
														<tr>
															<td colspan="3">
																<a href="javascript:void(0);" title="<?php _e('Add Another', 'pmxi_plugin')?>" class="action add-new-key add-new-entry"><?php _e('Add Another', 'pmxi_plugin') ?></a>
															</td>
														</tr>
														<tr>																										
															<td colspan="3">
																<div class="wrap" style="position:relative;">
																	<a class="save_popup save_mr" href="javascript:void(0);"><?php _e('Save Rules', 'pmxi_plugin'); ?></a>
																</div>
															</td>
														</tr>
													</tbody>
												</table>
												<input type="hidden" class="pmre_mapping_rules" name="pmre[mapping][<?php echo $params['mapping_key']; ?>]" value="<?php if (!empty($params['mapping_rules'])) echo esc_html($params['mapping_rules']); ?>"/>
											</fieldset>
										</div>
										<?php endif; ?>
									</td>
								</tr>
							</table>								
						</span>
					</div>
				</div>																
				<?php
				break;
			case 'textarea':
				?>
				<textarea name="<?php echo $params['field_name']; ?>" id="<?php echo sanitize_title($params['field_name']); ?>" class="rad4 newline" style="height: 70px;margin: 5px 0;padding-top: 5px;width: 70%;"><?php echo $params['field_value']; ?></textarea>
				<?php
				break;
		}
		?>
		</div>
		<?php
		echo ob_get_clean();
	}

}
