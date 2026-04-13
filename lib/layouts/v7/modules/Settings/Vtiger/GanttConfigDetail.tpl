{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{strip}
    <div class="detailViewContainer" id="GanttConfigDetails">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="contents">
                <div class="clearfix">
                    <h4 class="pull-left">{vtranslate('LBL_GANTT_CONFIG', $QUALIFIED_MODULE)}</h4>
                    <div class="btn-group pull-right">
                        <button class="btn btn-default editButton" data-url="{$MODEL->getEditViewUrl()}" type="button" title="{vtranslate('LBL_EDIT', $QUALIFIED_MODULE)}">
                            {vtranslate('LBL_EDIT', $QUALIFIED_MODULE)}
                        </button>
                    </div>
                </div>
                <hr>
                <br>
                <div class="detailViewInfo">
                    {assign var=FIELD_DATA value=$MODEL->getViewableData()}
                    {foreach key=FIELD_NAME item=FIELD_DETAILS from=$MODEL->getEditableFields()}
                        <div class="row form-group">
                            <div class="col-lg-4 col-md-4 col-sm-4 fieldLabel">
                                <label>{vtranslate($FIELD_DETAILS.label, $QUALIFIED_MODULE)}</label>
                            </div>
                            <div class="col-lg-8 col-md-8 col-sm-8 fieldValue break-word">
                                {$MODEL->getPicklistOptionLabel($FIELD_NAME, $FIELD_DATA[$FIELD_NAME])}
                            </div>
                        </div>
                    {/foreach}
                </div>
            </div>
        </div>
    </div>
{/strip}
