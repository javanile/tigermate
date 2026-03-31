{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}

{if $MENU_STRUCTURE}
{assign var="topMenus" value=$MENU_STRUCTURE->getTop()}
{assign var="moreMenus" value=$MENU_STRUCTURE->getMore()}

<div id="modules-menu" class="modules-menu">
	{foreach key=moduleName item=moduleModel from=$SELECTED_CATEGORY_MENU_LIST}
		{if $moduleModel->isCustomLink()}
			{assign var='translatedModuleLabel' value=$moduleModel->get('label')}
			{assign var='moduleUrl' value=$moduleModel->getDefaultUrl()}
			{if $moduleModel->needsAppParameter()}
				{assign var='moduleUrl' value=$moduleUrl|cat:"&app="|cat:$SELECTED_MENU_CATEGORY}
			{/if}
		{else}
			{assign var='translatedModuleLabel' value=vtranslate($moduleModel->get('label'),$moduleName )}
			{assign var='moduleUrl' value=$moduleModel->getDefaultUrl()|cat:"&app="|cat:$SELECTED_MENU_CATEGORY}
		{/if}
		<ul title="{$translatedModuleLabel}" class="module-qtip">
			<li {if !$moduleModel->isCustomLink() && $MODULE eq $moduleName}class="active"{else}class=""{/if}>
				<a href="{$moduleUrl}">
					{$moduleModel->getModuleIcon()}
					<span>{$translatedModuleLabel}</span>
				</a>
			</li>
		</ul>
	{/foreach}
</div>
{/if}
