{strip}
<div class="detailViewContainer full-height">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 main-scroll">
		<div class="detailViewInfo">
			{include file="DetailViewHeader.tpl"|vtemplate_path:Vtiger MODULE_NAME=$MODULE_NAME}
			{include file='RecordDetailView.tpl'|@vtemplate_path:$MODULE_NAME MODULE_NAME=$MODULE_NAME}
			{include file='FieldsDetailView.tpl'|@vtemplate_path:$MODULE_NAME MODULE_NAME=$MODULE_NAME SOURCE_MODULE=$SOURCE_MODULE SELECTED_FIELD_MODELS_LIST=$SELECTED_FIELD_MODELS_LIST RECORD=$RECORD}
		</div>
	</div>
</div>
{/strip}
