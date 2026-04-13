{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{strip}
    <div class="editViewPageDiv" id="editViewContent">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="contents">
                <form id="GanttConfigForm" class="form-horizontal" data-detail-url="{$MODEL->getDetailViewUrl()}" method="POST">
                    {assign var=WIDTHTYPE value=$CURRENT_USER_MODEL->get('rowheight')}
                    <div>
                        <h4>{vtranslate('LBL_GANTT_CONFIG', $QUALIFIED_MODULE)}</h4>
                    </div>
                    <hr>
                    <br>
                    <div class="detailViewInfo">
                        {assign var=FIELD_DATA value=$MODEL->getViewableData()}
                        {foreach key=FIELD_NAME item=FIELD_DETAILS from=$MODEL->getEditableFields()}
                            <div class="row form-group">
                                <div class="col-lg-4 control-label fieldLabel">
                                    <label>{vtranslate($FIELD_DETAILS.label, $QUALIFIED_MODULE)}</label>
                                </div>
                                <div class="{$WIDTHTYPE} col-lg-4 input-group">
                                    <select class="select2-container inputElement select2 col-lg-11" name="{$FIELD_NAME}" data-rule-required="true">
                                        {foreach key=OPTION_VALUE item=OPTION_LABEL from=$MODEL->getPicklistValues($FIELD_NAME)}
                                            <option value="{$OPTION_VALUE}" {if $OPTION_VALUE eq $FIELD_DATA[$FIELD_NAME]}selected{/if}>
                                                {$MODEL->getPicklistOptionLabel($FIELD_NAME, $OPTION_VALUE)}
                                            </option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                        {/foreach}
                    </div>
                    <div class="modal-overlay-footer clearfix">
                        <div class="row clearfix">
                            <div class="textAlignCenter col-lg-12 col-md-12 col-sm-12">
                                <button type="submit" class="btn btn-success saveButton">{vtranslate('LBL_SAVE', $MODULE)}</button>&nbsp;&nbsp;
                                <a class="cancelLink" type="reset">{vtranslate('LBL_CANCEL', $MODULE)}</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
{/strip}
