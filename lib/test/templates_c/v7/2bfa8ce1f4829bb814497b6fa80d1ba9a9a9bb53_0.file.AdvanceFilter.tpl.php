<?php
/* Smarty version 3.1.39, created on 2023-06-16 15:43:52
  from '/var/www/html/lib/layouts/v7/modules/Settings/Workflows/AdvanceFilter.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_648c8338811b82_61498240',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '2bfa8ce1f4829bb814497b6fa80d1ba9a9a9bb53' => 
    array (
      0 => '/var/www/html/lib/layouts/v7/modules/Settings/Workflows/AdvanceFilter.tpl',
      1 => 1686745045,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_648c8338811b82_61498240 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_assignInScope('ALL_CONDITION_CRITERIA', $_smarty_tpl->tpl_vars['ADVANCE_CRITERIA']->value[1]);
$_smarty_tpl->_assignInScope('ANY_CONDITION_CRITERIA', $_smarty_tpl->tpl_vars['ADVANCE_CRITERIA']->value[2]);
if (empty($_smarty_tpl->tpl_vars['ALL_CONDITION_CRITERIA']->value)) {
$_smarty_tpl->_assignInScope('ALL_CONDITION_CRITERIA', array());
}
if (empty($_smarty_tpl->tpl_vars['ANY_CONDITION_CRITERIA']->value)) {
$_smarty_tpl->_assignInScope('ANY_CONDITION_CRITERIA', array());
}?><div class="filterContainer"><input type="hidden" name="date_filters" data-value='<?php echo Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($_smarty_tpl->tpl_vars['DATE_FILTERS']->value));?>
' /><input type=hidden name="advanceFilterOpsByFieldType" data-value='<?php echo Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($_smarty_tpl->tpl_vars['ADVANCED_FILTER_OPTIONS_BY_TYPE']->value));?>
' /><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['ADVANCED_FILTER_OPTIONS']->value, 'ADVANCE_FILTER_OPTION', false, 'ADVANCE_FILTER_OPTION_KEY');
$_smarty_tpl->tpl_vars['ADVANCE_FILTER_OPTION']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['ADVANCE_FILTER_OPTION_KEY']->value => $_smarty_tpl->tpl_vars['ADVANCE_FILTER_OPTION']->value) {
$_smarty_tpl->tpl_vars['ADVANCE_FILTER_OPTION']->do_else = false;
$_tmp_array = isset($_smarty_tpl->tpl_vars['ADVANCED_FILTER_OPTIONS']) ? $_smarty_tpl->tpl_vars['ADVANCED_FILTER_OPTIONS']->value : array();
if (!(is_array($_tmp_array) || $_tmp_array instanceof ArrayAccess)) {
settype($_tmp_array, 'array');
}
$_tmp_array[$_smarty_tpl->tpl_vars['ADVANCE_FILTER_OPTION_KEY']->value] = vtranslate($_smarty_tpl->tpl_vars['ADVANCE_FILTER_OPTION']->value,$_smarty_tpl->tpl_vars['MODULE']->value);
$_smarty_tpl->_assignInScope('ADVANCED_FILTER_OPTIONS', $_tmp_array);
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?><input type=hidden name="advanceFilterOptions" data-value='<?php echo Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($_smarty_tpl->tpl_vars['ADVANCED_FILTER_OPTIONS']->value));?>
' /><div class="allConditionContainer conditionGroup contentsBackground" style="padding-bottom:15px;"><div class="header"><span><strong><?php echo vtranslate('LBL_ALL_CONDITIONS',$_smarty_tpl->tpl_vars['MODULE']->value);?>
</strong></span>&nbsp;<span>(<?php echo vtranslate('LBL_ALL_CONDITIONS_DESC',$_smarty_tpl->tpl_vars['MODULE']->value);?>
)</span></div><br><div class="contents"><div class="conditionList"><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['ALL_CONDITION_CRITERIA']->value['columns'], 'CONDITION_INFO');
$_smarty_tpl->tpl_vars['CONDITION_INFO']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['CONDITION_INFO']->value) {
$_smarty_tpl->tpl_vars['CONDITION_INFO']->do_else = false;
$_smarty_tpl->_subTemplateRender(vtemplate_path('AdvanceFilterCondition.tpl',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('RECORD_STRUCTURE'=>$_smarty_tpl->tpl_vars['RECORD_STRUCTURE']->value,'CONDITION_INFO'=>$_smarty_tpl->tpl_vars['CONDITION_INFO']->value,'MODULE'=>$_smarty_tpl->tpl_vars['MODULE']->value), 0, true);
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);
if (php7_count($_smarty_tpl->tpl_vars['ALL_CONDITION_CRITERIA']->value) == 0) {
$_smarty_tpl->_subTemplateRender(vtemplate_path('AdvanceFilterCondition.tpl',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('RECORD_STRUCTURE'=>$_smarty_tpl->tpl_vars['RECORD_STRUCTURE']->value,'MODULE'=>$_smarty_tpl->tpl_vars['MODULE']->value,'CONDITION_INFO'=>array()), 0, true);
}?></div><div class="hide basic"><?php $_smarty_tpl->_subTemplateRender(vtemplate_path('AdvanceFilterCondition.tpl',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('RECORD_STRUCTURE'=>$_smarty_tpl->tpl_vars['RECORD_STRUCTURE']->value,'CONDITION_INFO'=>array(),'MODULE'=>$_smarty_tpl->tpl_vars['MODULE']->value,'NOCHOSEN'=>true), 0, true);
?></div><br><div class="addCondition"><button type="button" class="btn btn-default"><?php echo vtranslate('LBL_ADD_CONDITION',$_smarty_tpl->tpl_vars['MODULE']->value);?>
</button></div><div class="groupCondition"><?php $_smarty_tpl->_assignInScope('GROUP_CONDITION', $_smarty_tpl->tpl_vars['ALL_CONDITION_CRITERIA']->value['condition']);
if (empty($_smarty_tpl->tpl_vars['GROUP_CONDITION']->value)) {
$_smarty_tpl->_assignInScope('GROUP_CONDITION', "and");
}?><input type="hidden" name="condition" value="<?php echo $_smarty_tpl->tpl_vars['GROUP_CONDITION']->value;?>
" /></div></div></div><br><br><div class="anyConditionContainer conditionGroup contentsBackground"><div class="header"><span><strong><?php echo vtranslate('LBL_ANY_CONDITIONS',$_smarty_tpl->tpl_vars['MODULE']->value);?>
</strong></span>&nbsp;<span>(<?php echo vtranslate('LBL_ANY_CONDITIONS_DESC',$_smarty_tpl->tpl_vars['MODULE']->value);?>
)</span></div><br><div class="contents"><div class="conditionList"><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['ANY_CONDITION_CRITERIA']->value['columns'], 'CONDITION_INFO');
$_smarty_tpl->tpl_vars['CONDITION_INFO']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['CONDITION_INFO']->value) {
$_smarty_tpl->tpl_vars['CONDITION_INFO']->do_else = false;
$_smarty_tpl->_subTemplateRender(vtemplate_path('AdvanceFilterCondition.tpl',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('RECORD_STRUCTURE'=>$_smarty_tpl->tpl_vars['RECORD_STRUCTURE']->value,'CONDITION_INFO'=>$_smarty_tpl->tpl_vars['CONDITION_INFO']->value,'MODULE'=>$_smarty_tpl->tpl_vars['MODULE']->value,'CONDITION'=>"or"), 0, true);
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);
if (php7_count($_smarty_tpl->tpl_vars['ANY_CONDITION_CRITERIA']->value) == 0) {
$_smarty_tpl->_subTemplateRender(vtemplate_path('AdvanceFilterCondition.tpl',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('RECORD_STRUCTURE'=>$_smarty_tpl->tpl_vars['RECORD_STRUCTURE']->value,'MODULE'=>$_smarty_tpl->tpl_vars['MODULE']->value,'CONDITION_INFO'=>array(),'CONDITION'=>"or"), 0, true);
}?></div><div class="hide basic"><?php $_smarty_tpl->_subTemplateRender(vtemplate_path('AdvanceFilterCondition.tpl',$_smarty_tpl->tpl_vars['QUALIFIED_MODULE']->value), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('RECORD_STRUCTURE'=>$_smarty_tpl->tpl_vars['RECORD_STRUCTURE']->value,'MODULE'=>$_smarty_tpl->tpl_vars['MODULE']->value,'CONDITION_INFO'=>array(),'CONDITION'=>"or",'NOCHOSEN'=>true), 0, true);
?></div><br><div class="addCondition"><button type="button" class="btn  btn-default"><?php echo vtranslate('LBL_ADD_CONDITION',$_smarty_tpl->tpl_vars['MODULE']->value);?>
</button></div></div></div></div><?php }
}
