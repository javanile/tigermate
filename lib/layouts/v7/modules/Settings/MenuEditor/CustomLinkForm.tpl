{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}

{strip}
	<div class="modal-dialog modal-lg customLinkFormContainer">
		<div class="modal-content">
			<input type="hidden" name="appname" value="{$SELECTED_APP_NAME}" />
			<input type="hidden" name="customLinkId" value="{$CUSTOM_LINK_ID}" />
			<input type="hidden" name="formMode" value="{$FORM_MODE}" />
			<div class="modal-header">
				<div class="clearfix">
					<div class="pull-right">
						<button type="button" class="close" aria-label="Close" data-dismiss="modal" style="color: inherit;">
							<span aria-hidden="true" class='fa fa-close'></span>
						</button>
					</div>
					<h4 class="pull-left textOverflowEllipsis" style="word-break: break-all;max-width: 95%;">
						{if $FORM_MODE eq 'edit'}
							{vtranslate('LBL_EDIT_CUSTOM_LINK', $QUALIFIED_MODULE)}
						{else}
							{vtranslate('LBL_ADD_CUSTOM_LINK', $QUALIFIED_MODULE)}
						{/if}
					</h4>
				</div>
			</div>
			<div class="modal-body form-horizontal">
				<div class="form-group">
					<label class="control-label col-sm-3">{vtranslate('LBL_CUSTOM_LINK_LABEL', $QUALIFIED_MODULE)}</label>
					<div class="col-sm-8">
						<input type="text" class="inputElement form-control" name="label" value="{$CUSTOM_LINK_LABEL|escape:'html'}" />
					</div>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3">{vtranslate('LBL_CUSTOM_LINK_URL', $QUALIFIED_MODULE)}</label>
					<div class="col-sm-8">
						<input type="text" class="inputElement form-control" name="linkurl" value="{$CUSTOM_LINK_URL|escape:'html'}" placeholder="index.php?module=Accounts&view=List" />
					</div>
				</div>
			</div>
			{include file="ModalFooter.tpl"|vtemplate_path:$QUALIFIED_MODULE}
		</div>
	</div>
{/strip}
