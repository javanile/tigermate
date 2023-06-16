<?php
/* Smarty version 3.1.39, created on 2023-06-16 15:43:52
  from '/var/www/html/lib/layouts/v7/modules/Settings/Workflows/WorkFlowConditions.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_648c83388062f2_28546065',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '34b113bb7a6b3daa3e8571297966dff74bc3364a' => 
    array (
      0 => '/var/www/html/lib/layouts/v7/modules/Settings/Workflows/WorkFlowConditions.tpl',
      1 => 1686745045,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_648c83388062f2_28546065 (Smarty_Internal_Template $_smarty_tpl) {
?><input type="hidden" name="conditions" id="advanced_filter" value='' /><input type="hidden" id="olderConditions" value='<?php echo Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($_smarty_tpl->tpl_vars['WORKFLOW_MODEL']->value->get('conditions')));?>
' /><input type="hidden" name="filtersavedinnew" value="<?php echo $_smarty_tpl->tpl_vars['WORKFLOW_MODEL']->value->get('filtersavedinnew');?>
" /><div class="editViewHeader"><div class='row'><div class="col-lg-12 col-md-12 col-lg-pull-0"><h4><?php echo vtranslate('LBL_WORKFLOW_CONDITION',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value);?>
</h4></div></div></div><hr style="margin-top: 0px !important;"><div class="editViewBody"><div class="editViewContents" style="padding-bottom: 0px;"><div class="form-group"><div class="col-sm-12"><?php if ($_smarty_tpl->tpl_vars['IS_FILTER_SAVED_NEW']->value == false) {?><div class="alert alert-info"><?php echo vtranslate('LBL_CREATED_IN_OLD_LOOK_CANNOT_BE_EDITED',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value);?>
</div><div class="row"><span class="col-sm-6"><input type="radio" name="conditionstype" class="alignMiddle" checked=""/>&nbsp;&nbsp;<span class="alignMiddle"><?php echo vtranslate('LBL_USE_EXISTING_CONDITIONS',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value);?>
</span></span><span class="col-sm-6"><input type="radio" id="enableAdvanceFilters" name="conditionstype" class="alignMiddle recreate"/>&nbsp;&nbsp;<span class="alignMiddle"><?php echo vtranslate('LBL_RECREATE_CONDITIONS',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value);?>
</span></span></div><br><?php }?><div id="advanceFilterContainer" class="conditionsContainer <?php if ($_smarty_tpl->tpl_vars['IS_FILTER_SAVED_NEW']->value == false) {?> zeroOpacity <?php }?>"><div class="col-sm-12"><div class="table table-bordered" style="padding: 5%"><?php $_smarty_tpl->_subTemplateRender(vtemplate_path('AdvanceFilter.tpl',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('RECORD_STRUCTURE'=>$_smarty_tpl->tpl_vars['RECORD_STRUCTURE']->value), 0, true);
?></div></div><?php $_smarty_tpl->_subTemplateRender(vtemplate_path("FieldExpressions.tpl",$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('EXECUTION_CONDITION'=>$_smarty_tpl->tpl_vars['WORKFLOW_MODEL']->value->get('execution_condition')), 0, true);
?></div></div></div></div></div><div class="editViewHeader"><div class='row'><div class="col-lg-12 col-md-12 col-lg-pull-0"><h4><?php echo vtranslate('LBL_WORKFLOW_ACTIONS',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value);?>
</h4></div></div></div><hr style="margin-top: 0px !important;"><div class="editViewBody" id="workflow_action" style="padding-bottom: 15px;"><div style="padding-left: 15px;"><div class="btn-group"><button class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" type="button" aria-expanded="true"><strong><?php echo vtranslate('LBL_ADD_TASK',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value);?>
</strong>&nbsp;&nbsp;<span class="caret"></span></button><ul class="dropdown-menu" role="menu"><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['TASK_TYPES']->value, 'TASK_TYPE');
$_smarty_tpl->tpl_vars['TASK_TYPE']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['TASK_TYPE']->value) {
$_smarty_tpl->tpl_vars['TASK_TYPE']->do_else = false;
?><li><a class="cursorPointer" data-url="index.php<?php echo $_smarty_tpl->tpl_vars['TASK_TYPE']->value->getV7EditViewUrl();?>
&for_workflow=<?php echo $_smarty_tpl->tpl_vars['RECORD']->value;?>
"><?php echo vtranslate($_smarty_tpl->tpl_vars['TASK_TYPE']->value->get('label'),$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value);?>
</a></li><?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?></ul></div></div><div id="taskListContainer" style="min-height: 250px;"><?php $_smarty_tpl->_subTemplateRender(vtemplate_path('TasksList.tpl',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?></div></div>
<?php }
}
