{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{strip}
	<input type="hidden" name="parameters" id="entityMethodParameters" value="{$TASK_OBJECT->parameters|escape:'html'}">

	<div class="row form-group">
        <div class="col-sm-6 col-xs-6">
            <div class="row">
                <div class="col-sm-3 col-xs-3">{vtranslate('LBL_METHOD_NAME',$QUALIFIED_MODULE)} :</div>
                <div class="col-sm-8 col-xs-8">
                    {assign var=ENTITY_METHODS value=$WORKFLOW_MODEL->getEntityMethods()}
                    {if empty($ENTITY_METHODS)}
                        <div class="alert alert-info">{vtranslate('LBL_NO_METHOD_IS_AVAILABLE_FOR_THIS_MODULE',$QUALIFIED_MODULE)}</div>
                    {else}
                        <select name="methodName" id="entityMethodName" class="select2">
                            {foreach from=$ENTITY_METHODS item=METHOD}
                                <option {if $TASK_OBJECT->methodName eq $METHOD}selected="" {/if} value="{$METHOD}">{vtranslate($METHOD,$QUALIFIED_MODULE)}</option>
                            {/foreach}
                        </select>
                    {/if}
                </div>
            </div>
        </div>
	</div>

	<div id="entityMethodDynamicFields"></div>

	<script type="text/javascript">
	(function(){
		var moduleNameVal = jQuery('#module_name').val() || '{$WORKFLOW_MODEL->get('module_name')|escape:'javascript'}';
		var savedParams   = {};
		try { savedParams = JSON.parse(jQuery('#entityMethodParameters').val() || '{}'); } catch(e){}

		function renderFields(fields) {
			var container = jQuery('#entityMethodDynamicFields');
			container.empty();
			if (!fields || fields.length === 0) return;

			jQuery.each(fields, function(i, field) {
				var val = savedParams[field.name] !== undefined ? savedParams[field.name] : '';
				var row = jQuery(
					'<div class="row form-group">' +
						'<div class="col-sm-6 col-xs-6">' +
							'<div class="row">' +
								'<div class="col-sm-3 col-xs-3">' + field.label + ' :</div>' +
								'<div class="col-sm-8 col-xs-8">' +
									'<input type="text" class="inputElement entityMethodParam" data-param="' + field.name + '" value="' + jQuery('<div>').text(val).html() + '">' +
								'</div>' +
							'</div>' +
						'</div>' +
					'</div>'
				);
				container.append(row);
			});

			container.on('input', '.entityMethodParam', serializeParams);
		}

		function serializeParams() {
			var params = {};
			jQuery('.entityMethodParam').each(function(){
				params[jQuery(this).data('param')] = jQuery(this).val();
			});
			jQuery('#entityMethodParameters').val(JSON.stringify(params));
		}

		function loadFields(methodName) {
			if (!methodName) { jQuery('#entityMethodDynamicFields').empty(); return; }
			jQuery.post('index.php', {
				module:      'Workflows',
				parent:      'Settings',
				action:      'EntityMethodFields',
				mode:        'get',
				methodName:  methodName,
				module_name: moduleNameVal
			}, function(response) {
				var fields = (response && response.result) ? response.result : [];
				renderFields(fields);
			}, 'json');
		}

		var initialMethod = jQuery('#entityMethodName').val();
		if (initialMethod) loadFields(initialMethod);

		jQuery(document).on('change', '#entityMethodName', function(){
			savedParams = {};
			jQuery('#entityMethodParameters').val('');
			loadFields(jQuery(this).val());
		});

		jQuery('#saveTask').on('submit', serializeParams);
	})();
	</script>
{/strip}
