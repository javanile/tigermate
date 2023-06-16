<?php
/* Smarty version 3.1.39, created on 2023-06-16 15:43:52
  from '/var/www/html/lib/layouts/v7/modules/Settings/Workflows/TasksList.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_648c83389cf9c3_12838953',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'db599fa8c4279ee1398f47a1661ef388327e5fc6' => 
    array (
      0 => '/var/www/html/lib/layouts/v7/modules/Settings/Workflows/TasksList.tpl',
      1 => 1686745045,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_648c83389cf9c3_12838953 (Smarty_Internal_Template $_smarty_tpl) {
?><div style="padding-left: 15px;"><div id="table-content" class="table-container"><table id="listview-table" class="table <?php if ($_smarty_tpl->tpl_vars['TASK_LIST']->value == '0') {?>listview-table-norecords <?php } else { ?> listview-table<?php }?> "><thead><tr class="listViewContentHeader"><th width="20%"><?php echo vtranslate('LBL_ACTIVE',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value);?>
</th><th width="30%"><?php echo vtranslate('LBL_TASK_TYPE',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value);?>
</th><th><?php echo vtranslate('LBL_TASK_TITLE',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value);?>
</th></tr></thead><tbody><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['TASK_LIST']->value, 'TASK');
$_smarty_tpl->tpl_vars['TASK']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['TASK']->value) {
$_smarty_tpl->tpl_vars['TASK']->do_else = false;
?><tr class="listViewEntries"><td><div class="pull-left actions"><span class="actionImages"><a data-url="<?php echo $_smarty_tpl->tpl_vars['TASK']->value->getEditViewUrl();?>
"><i class="fa fa-pencil alignMiddle" title="<?php echo vtranslate('LBL_EDIT',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value);?>
"></i></a>&nbsp;&nbsp;<a class="deleteTask" data-deleteurl="<?php echo $_smarty_tpl->tpl_vars['TASK']->value->getDeleteActionUrl();?>
"><i class="fa fa-trash alignMiddle" title="<?php echo vtranslate('LBL_DELETE',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value);?>
"></i></a></span></div>&nbsp;&nbsp;<input style="opacity: 0;" type="checkbox" data-on-color="success" class="taskStatus" data-statusurl="<?php echo $_smarty_tpl->tpl_vars['TASK']->value->getChangeStatusUrl();?>
" <?php if ($_smarty_tpl->tpl_vars['TASK']->value->isActive()) {?> checked="" value="on" <?php } else { ?> value="off" <?php }?> /></td><td class="listViewEntryValue"><?php echo vtranslate($_smarty_tpl->tpl_vars['TASK']->value->getTaskType()->getLabel(),$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value);?>
</td><td><span class="pull-left"><?php echo Vtiger_Util_Helper::toSafeHTML($_smarty_tpl->tpl_vars['TASK']->value->getName());?>
</span></td><tr><?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?><tr class="listViewEntries hide taskTemplate"><td><div class="pull-left actions"><span class="actionImages"><a class="editTask"><i class="fa fa-pencil alignMiddle" ></i></a>&nbsp;&nbsp;<a class="deleteTaskTemplate"><i class="fa fa-trash alignMiddle"></i></a></span></div>&nbsp;&nbsp;<input style="opacity: 0;" type="checkbox" data-on-color="success" class="tmpTaskStatus" checked="" value="on"/></td><td class="listViewEntryValue taskType"></td><td><span class="pull-left taskName"></span></td></tr></tbody></table><?php if (empty($_smarty_tpl->tpl_vars['TASK_LIST']->value)) {?><table class="emptyRecordsDiv"><tbody><tr><td><?php echo vtranslate('LBL_NO_TASKS_ADDED',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value);?>
</td></tr></tbody></table><?php }?></div></div><?php }
}
