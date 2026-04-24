<div class="modal">
	<div class="modal-header contentsBackground">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3>{vtranslate('SINGLE_'|cat:$MODULE, $QUALIFIED_MODULE)} - {$RECORD_MODEL->getName()}</h3>
	</div>
	<div class="modal-body">
		<div class="marginBottom10px">{vtranslate('LBL_EMBED_THE_FOLLOWING_FORM_IN_YOUR_WEBSITE', $QUALIFIED_MODULE)}</div>
		<textarea id="showFormContent" class="input-xxlarge" style="height:400px;min-width: 600px" readonly></textarea>
		<code>
			<pre>
&lt;meta http-equiv="Content-Type" content="text/html;charset=UTF-8"&gt;
&lt;/head&gt;
&lt;form name="{$RECORD_MODEL->getName()}" action="{$ACTION_PATH}"
  method="post" accept-charset="utf-8" enctype="multipart/form-data"&gt;
	&lt;input type="hidden" name="publicid" value="{$RECORD_MODEL->get('publicid')}" /&gt;
	&lt;input type="hidden" name="name" value="{$RECORD_MODEL->getName()}" /&gt;
	{assign var=IS_CAPTCHA_ENABLED value=$RECORD_MODEL->isCaptchaEnabled()}
	&lt;table&gt;
	{foreach item=FIELD_MODEL key=FIELD_NAME from=$SELECTED_FIELD_MODELS_LIST}
		{assign var=SOURCE_MODULE value=$FIELD_MODEL->getModuleName()}
		{assign var=DATA_TYPE value=$FIELD_MODEL->getFieldDataType()}
		{assign var=HIDDEN_STATUS value=$FIELD_MODEL->get('hidden')}
		{assign var=TYPE value=""}
&lt;tr&gt;
{if $FIELD_MODEL->get('hidden') neq 1}&lt;td&gt;&lt;label&gt;{vtranslate($FIELD_MODEL->get('label'), $SOURCE_MODULE)}{if $FIELD_MODEL->get('required') eq 1}*{/if}&lt;/label&gt;&lt;/td&gt;{/if}
&lt;td&gt;
	{if ($DATA_TYPE eq 'picklist' || $DATA_TYPE eq 'multipicklist')}
		{assign var=PICKLIST_VALUES value=$FIELD_MODEL->getPicklistValues()}
		{if Settings_Webforms_Record_Model::isCustomField($FIELD_NAME)}
			{assign var=FIELD_LABEL value=$FIELD_MODEL->get('label')}
			{assign var=PICKLIST_NAME value="label:{$FIELD_LABEL|replace:' ':'_'}"}
		{else}
			{assign var=PICKLIST_NAME value=$FIELD_MODEL->get('name')}
		{/if}
	{else if ($DATA_TYPE eq "salutation") or ($DATA_TYPE eq "string") or ($DATA_TYPE eq "time") or ($DATA_TYPE eq "currency") or ($DATA_TYPE eq "date") or ($DATA_TYPE eq "url") or ($DATA_TYPE eq "phone")}
		{assign var=TYPE value="text"}
	{else if ($DATA_TYPE eq "text")}
		{assign var=TYPE value="text"}
		&lt;textarea name="{$FIELD_MODEL->getFieldName()}" {if $FIELD_MODEL->get('required') eq 1} required{/if}{if $FIELD_MODEL->get('hidden') eq 1} hidden{/if}&gt;{$FIELD_MODEL->get('fieldvalue')}&lt;/textarea&gt;
	{else if ($DATA_TYPE eq "email")}
		{assign var=TYPE value="email"}
	{else if ($DATA_TYPE eq "image")}
		{assign var=TYPE value="image"}
	{else if (($DATA_TYPE eq "integer") or ($DATA_TYPE eq "double"))}
		{assign var=TYPE value="number"}
	{else if ($DATA_TYPE eq "boolean")}
		{assign var=TYPE value="checkbox"}
	{/if}
	{if $HIDDEN_STATUS eq 1}
		{assign var=TYPE value=hidden}
	{/if}
	{if $DATA_TYPE eq 'picklist'}
&lt;select name="{$PICKLIST_NAME}" {if $FIELD_MODEL->get('required') eq 1} required{/if}{if $FIELD_MODEL->get('hidden') eq 1} hidden{/if}&gt;
	&lt;option value&gt;{vtranslate('LBL_SELECT_VALUE',$QUALIFIED_MODULE)}&lt;/option&gt;
	{foreach item=PICKLIST_VALUE key=PICKLIST_NAME from=$PICKLIST_VALUES}
	&lt;option value="{$PICKLIST_NAME}" {if trim(decode_html($FIELD_MODEL->get('fieldvalue'))) eq trim($PICKLIST_NAME)} selected {/if}&gt;{$PICKLIST_VALUE}&lt;/option&gt;
	{/foreach}
&lt;/select&gt;
	{else if $DATA_TYPE eq 'multipicklist'}
		{assign var="FIELD_VALUE_LIST" value=explode(' |##| ',$FIELD_MODEL->get('fieldvalue'))}
&lt;select name="{$PICKLIST_NAME}[]" {if $FIELD_MODEL->get('required') eq 1} required{/if} multiple style="width: 60%;" {if $FIELD_MODEL->get('hidden') eq 1} hidden{/if}&gt;
	{foreach item=PICKLIST_VALUE from=$PICKLIST_VALUES}
	&lt;option value="{$PICKLIST_VALUE}" {if in_array(Vtiger_Util_Helper::toSafeHTML($PICKLIST_VALUE), $FIELD_VALUE_LIST)} selected {/if}&gt;{vtranslate($PICKLIST_VALUE, $MODULE)}&lt;/option&gt;
	{/foreach}
&lt;/select&gt;
	{elseif $DATA_TYPE eq "reference"}
		&lt;input type="hidden" name="{$FIELD_MODEL->getFieldName()}" value="{$FIELD_MODEL->get('fieldvalue')}" /&gt;
		{assign var=EXPLODED_FIELD_VALUES value='x'|explode:$FIELD_MODEL->get('fieldvalue')}
		&lt;input type="{$TYPE}" value="{$FIELD_MODEL->getEditViewDisplayValue($EXPLODED_FIELD_VALUES[1])}" readonly= /&gt;
	{elseif $DATA_TYPE eq "image"}
		&lt;input type="file" name="{$FIELD_MODEL->getFieldName()}" {if $FIELD_MODEL->get('hidden') eq 1} hidden{/if} {if $FIELD_MODEL->get('required') eq 1} required{/if}/&gt;
	{else if $DATA_TYPE eq "boolean"}
		&lt;input type="hidden" name="{$FIELD_MODEL->getFieldName()}" value=0 /&gt;
		{if ($HIDDEN_STATUS eq 1) and ($FIELD_MODEL->get('fieldvalue') eq "on")}
			&lt;input type="hidden" name="{$FIELD_MODEL->getFieldName()}" value=1 checked /&gt;
		{elseif ($HIDDEN_STATUS neq 1)}
			&lt;input type="{$TYPE}" name="{$FIELD_MODEL->getFieldName()}" value=1 {if $FIELD_MODEL->get('fieldvalue') eq "on"} checked {/if}/&gt;
		{/if}
	{elseif ($DATA_TYPE neq "text") and ($DATA_TYPE neq "boolean")}
		&lt;input type="{$TYPE}" name="{$FIELD_MODEL->getFieldName()}" value="{$FIELD_MODEL->get('fieldvalue')}" {if ($FIELD_MODEL->get('required') eq 1) || ($FIELD_MODEL->isMandatory(true))} required{/if} /&gt;{if ($DATA_TYPE eq "date") and ($FIELD_MODEL->get('hidden') neq 1)}(yyyy-mm-dd){/if}
	{/if}
&lt;/td&gt;&lt;/tr&gt;
	{/foreach}
	&lt;/table&gt;
	{if $IS_CAPTCHA_ENABLED}
		&lt;div id="captchaField"&gt;&lt;/div&gt;
		&lt;input type="hidden" id="captchaUrl" value="{$CAPTCHA_PATH}"&gt;
		&lt;input type="hidden" id="recaptcha_validation_value"&gt;
	{/if}
&lt;input type="submit" value="Submit" &gt;&lt;/input&gt;
&lt;/form&gt;
			</pre>
		</code>
		<input type="hidden" name="isCaptchaEnabled" value="{$IS_CAPTCHA_ENABLED}">
	</div>
	<div class="modal-footer">
		<div class="pull-right cancelLinkContainer">
			<a class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE)}</a>
		</div>
	</div>
</div>
