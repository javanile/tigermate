{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{strip}
	{foreach item=HEADER from=$RELATED_HEADERS}
		{if $HEADER->get('label') eq "Project Milestone Name"}
			{assign var=PROJECTMILESTONE_NAME_HEADER value={vtranslate($HEADER->get('label'),$MODULE)}}
		{elseif $HEADER->get('label') eq "Milestone Date"}
			{assign var=PROJECTMILESTONE_DATE_HEADER value={vtranslate($HEADER->get('label'),$MODULE)}}
		{/if}
	{/foreach}
	<style type="text/css">
		.tmProjectMilestoneSummaryTable {
			display: table;
			width: 100%;
			table-layout: fixed;
			border-collapse: collapse;
			margin-top: 4px;
		}
		.tmProjectMilestoneSummaryTable .tmProjectMilestoneRow {
			display: table-row;
		}
		.tmProjectMilestoneSummaryTable .recentActivitiesContainer {
			display: table-row;
			padding: 0;
			margin: 0;
		}
		.tmProjectMilestoneSummaryTable .tmProjectMilestoneCell {
			display: table-cell;
			vertical-align: middle;
			padding: 8px 10px;
			border-bottom: 1px solid #e8e8e8;
		}
		.tmProjectMilestoneSummaryTable .tmProjectMilestoneHeader .tmProjectMilestoneCell {
			padding-top: 0;
			padding-bottom: 7px;
			font-weight: bold;
			color: #555;
			border-bottom: 1px solid #d9d9d9;
		}
		.tmProjectMilestoneSummaryTable .tmProjectMilestoneName {
			width: 68%;
		}
		.tmProjectMilestoneSummaryTable .tmProjectMilestoneDate {
			width: 32%;
			text-align: right;
			white-space: nowrap;
		}
		.tmProjectMilestoneSummaryTable .tmProjectMilestoneRecordName {
			display: block;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}
	</style>
	<div class="tmProjectMilestoneSummaryTable">
		<div class="tmProjectMilestoneRow tmProjectMilestoneHeader">
			<div class="tmProjectMilestoneCell tmProjectMilestoneName">{$PROJECTMILESTONE_NAME_HEADER}</div>
			<div class="tmProjectMilestoneCell tmProjectMilestoneDate">{$PROJECTMILESTONE_DATE_HEADER}</div>
		</div>
		{foreach item=RELATED_RECORD from=$RELATED_RECORDS}
			<div class="tmProjectMilestoneRow recentActivitiesContainer">
				<div class="tmProjectMilestoneCell tmProjectMilestoneName">
					<a class="tmProjectMilestoneRecordName" href="{$RELATED_RECORD->getDetailViewUrl()}" id="{$MODULE}_{$RELATED_MODULE}_Related_Record_{$RELATED_RECORD->get('id')}" title="{$RELATED_RECORD->getDisplayValue('projectmilestonename')}">{$RELATED_RECORD->getDisplayValue('projectmilestonename')}</a>
				</div>
				<div class="tmProjectMilestoneCell tmProjectMilestoneDate">{$RELATED_RECORD->getDisplayValue('projectmilestonedate')}</div>
			</div>
		{/foreach}
	</div>
	{assign var=NUMBER_OF_RECORDS value=php7_count($RELATED_RECORDS)}
	{if $NUMBER_OF_RECORDS eq 5}
		<div class="row-fluid">
			<div class="pull-right">
				<a class="moreRecentMilestones cursorPointer">{vtranslate('LBL_MORE',$MODULE)}</a>
			</div>
		</div>
	{/if}
{/strip}