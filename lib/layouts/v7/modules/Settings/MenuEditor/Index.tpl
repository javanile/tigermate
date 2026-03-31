{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{* modules/Settings/MenuEditor/views/Index.php *}

{* START YOUR IMPLEMENTATION FROM BELOW. Use {debug} for information *}
{assign var=APP_IMAGE_MAP value=Vtiger_MenuStructure_Model::getAppIcons()}
<div class="listViewPageDiv detailViewContainer col-sm-12" id="listViewContent">
	<div class="col-sm-12">
		<div class="row">
			<div class=" vt-default-callout vt-info-callout">
				<h4 class="vt-callout-header"><span class="fa fa-info-circle"></span>{vtranslate('LBL_INFO', $QUALIFIED_MODULE_NAME)}</h4>
				<p>{vtranslate('LBL_MENU_EDITOR_INFO', $QUALIFIED_MODULE_NAME)}</p>
			</div>
		</div>
	</div>
	<div class="col-lg-12" style="margin-top: 10px;">
		<div class="row" style="margin-left: -28px;">
			{assign var=APP_LIST value=Vtiger_MenuStructure_Model::getAppMenuList()}
			{foreach item=APP_IMAGE key=APP_NAME from=$APP_IMAGE_MAP name=APP_MAP}
				{if !in_array($APP_NAME, $APP_LIST)} {continue} {/if}
				<div class="col-lg-2{if $smarty.foreach.APP_MAP.index eq 0 or php7_count($APP_LIST) eq 1}{/if}">
					<div class="menuEditorItem app-{$APP_NAME}" data-app-name="{$APP_NAME}">
						<span class="fa {$APP_IMAGE}"></span>
						{assign var=TRANSLATED_APP_NAME value={vtranslate("LBL_$APP_NAME")}}
						<div class="textOverflowEllipsis" title="{$TRANSLATED_APP_NAME}">{$TRANSLATED_APP_NAME}</div>
					</div>
					<div class="sortable appContainer" data-appname="{$APP_NAME}">
						{foreach key=moduleName item=moduleModel from=$APP_MAPPED_MODULES[$APP_NAME]}
							{assign var=IS_CUSTOM_LINK value=$moduleModel->isCustomLink()}
							<div class="modules{if !$IS_CUSTOM_LINK} noConnect{/if} menuEditorEntry" data-module="{$moduleName}" data-entry-type="{if $IS_CUSTOM_LINK}customLink{else}module{/if}"{if $IS_CUSTOM_LINK} data-custom-link-id="{$moduleModel->get('id')}"{/if}>
								{if $IS_CUSTOM_LINK}
									<i class="fa fa-pencil pull-right whiteIcon menuEditorEditCustomLink" style="margin: 5%;padding-top:15px;padding-right:10px;"></i>
								{/if}
								<i data-appname="{$APP_NAME}" class="fa fa-times pull-right whiteIcon menuEditorRemoveItem" style="margin: 5%;padding-top:15px;"></i>
								<div class="menuEditorItem menuEditorModuleItem">
									<span class="pull-left marginRight10px marginTop5px">
										{if $IS_CUSTOM_LINK}
											<i class="fa fa-link marginTop5px"></i>
										{else}
											<img class="alignMiddle cursorDrag" src="{vimage_path('drag.png')}"/>
										{/if}
									</span>
									{if $IS_CUSTOM_LINK}
										{assign var='translatedModuleLabel' value=$moduleModel->get('label')}
									{else}
										{assign var='translatedModuleLabel' value=vtranslate($moduleModel->get('label'),$moduleName )}
									{/if}
									<span>
										<span class="marginRight10px marginTop5px pull-left">{$moduleModel->getModuleIcon()}</span>
									</span>
									<div class="textOverflowEllipsis marginTop5px textAlignLeft" title="{$translatedModuleLabel}">{$translatedModuleLabel}</div>
								</div>
								{if $IS_CUSTOM_LINK}
									<input type="hidden" class="customLinkLabelValue" value="{$moduleModel->get('label')|escape:'html'}" />
									<input type="hidden" class="customLinkUrlValue" value="{$moduleModel->get('linkurl')|escape:'html'}" />
								{/if}
							</div>
						{/foreach}
						<div class="menuEditorItem menuEditorModuleItem menuEditorAddItem" data-appname="{$APP_NAME}">
							<i class="fa fa-plus pull-left marginTop5px"></i>
							<div class="marginTop10px">{vtranslate('LBL_SELECT_HIDDEN_MODULE', $QUALIFIED_MODULE_NAME)}</div>
						</div>
						<div class="menuEditorItem menuEditorModuleItem menuEditorAddItem menuEditorCustomLinkAddItem" data-appname="{$APP_NAME}">
							<i class="fa fa-link pull-left marginTop5px"></i>
							<div class="marginTop10px">{vtranslate('LBL_ADD_CUSTOM_LINK', $QUALIFIED_MODULE_NAME)}</div>
						</div>
					</div>
				</div>
			{/foreach}
		</div>
	</div>
</div>
