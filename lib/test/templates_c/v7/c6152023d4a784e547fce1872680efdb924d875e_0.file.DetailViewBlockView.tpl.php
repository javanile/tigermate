<?php
/* Smarty version 3.1.39, created on 2023-06-16 15:42:56
  from '/var/www/html/lib/layouts/v7/modules/Users/DetailViewBlockView.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_648c8300b55ad0_64643501',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'c6152023d4a784e547fce1872680efdb924d875e' => 
    array (
      0 => '/var/www/html/lib/layouts/v7/modules/Users/DetailViewBlockView.tpl',
      1 => 1686745045,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_648c8300b55ad0_64643501 (Smarty_Internal_Template $_smarty_tpl) {
?>
<input type=hidden name="timeFormatOptions" data-value='<?php echo $_smarty_tpl->tpl_vars['DAY_STARTS']->value;?>
' /><input type='hidden' name='pwd_regex' value= <?php echo ZEND_json::encode($_smarty_tpl->tpl_vars['PWD_REGEX']->value);?>
 /><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['RECORD_STRUCTURE']->value, 'FIELD_MODEL_LIST', false, 'BLOCK_LABEL_KEY');
$_smarty_tpl->tpl_vars['FIELD_MODEL_LIST']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['BLOCK_LABEL_KEY']->value => $_smarty_tpl->tpl_vars['FIELD_MODEL_LIST']->value) {
$_smarty_tpl->tpl_vars['FIELD_MODEL_LIST']->do_else = false;
if ($_smarty_tpl->tpl_vars['BLOCK_LABEL_KEY']->value != 'LBL_CALENDAR_SETTINGS') {
$_smarty_tpl->_assignInScope('BLOCK', $_smarty_tpl->tpl_vars['BLOCK_LIST']->value[$_smarty_tpl->tpl_vars['BLOCK_LABEL_KEY']->value]);
if ($_smarty_tpl->tpl_vars['BLOCK']->value == null || count($_smarty_tpl->tpl_vars['FIELD_MODEL_LIST']->value) <= 0) {
continue 1;
}?><div class="block block_<?php echo $_smarty_tpl->tpl_vars['BLOCK_LABEL_KEY']->value;?>
" data-block="<?php echo $_smarty_tpl->tpl_vars['BLOCK_LABEL_KEY']->value;?>
" data-blockid="<?php echo $_smarty_tpl->tpl_vars['BLOCK_LIST']->value[$_smarty_tpl->tpl_vars['BLOCK_LABEL_KEY']->value]->get('id');?>
"><?php $_smarty_tpl->_assignInScope('IS_HIDDEN', $_smarty_tpl->tpl_vars['BLOCK']->value->isHidden());
$_smarty_tpl->_assignInScope('WIDTHTYPE', $_smarty_tpl->tpl_vars['USER_MODEL']->value->get('rowheight'));?><div><h4><?php ob_start();
echo $_smarty_tpl->tpl_vars['BLOCK_LABEL_KEY']->value;
$_prefixVariable10 = ob_get_clean();
ob_start();
echo $_smarty_tpl->tpl_vars['MODULE_NAME']->value;
$_prefixVariable11 = ob_get_clean();
echo vtranslate($_prefixVariable10,$_prefixVariable11);?>
</h4></div><hr><div class="blockData"><table class="table detailview-table no-border"><tbody><?php $_smarty_tpl->_assignInScope('COUNTER', 0);?><tr><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['FIELD_MODEL_LIST']->value, 'FIELD_MODEL', false, 'FIELD_NAME');
$_smarty_tpl->tpl_vars['FIELD_MODEL']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['FIELD_NAME']->value => $_smarty_tpl->tpl_vars['FIELD_MODEL']->value) {
$_smarty_tpl->tpl_vars['FIELD_MODEL']->do_else = false;
$_smarty_tpl->_assignInScope('fieldDataType', $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->getFieldDataType());
if (!$_smarty_tpl->tpl_vars['FIELD_MODEL']->value->isViewableInDetailView()) {
continue 1;
}
if ($_smarty_tpl->tpl_vars['FIELD_MODEL']->value->getName() == 'theme' || $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->getName() == 'rowheight') {
continue 1;
}
if ($_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('uitype') == "83") {
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['TAXCLASS_DETAILS']->value, 'tax', false, 'count');
$_smarty_tpl->tpl_vars['tax']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['count']->value => $_smarty_tpl->tpl_vars['tax']->value) {
$_smarty_tpl->tpl_vars['tax']->do_else = false;
if ($_smarty_tpl->tpl_vars['COUNTER']->value == 2) {?></tr><tr><?php $_smarty_tpl->_assignInScope('COUNTER', 1);
} else {
$_smarty_tpl->_assignInScope('COUNTER', $_smarty_tpl->tpl_vars['COUNTER']->value+1);
}?><td class="fieldLabel <?php echo $_smarty_tpl->tpl_vars['WIDTHTYPE']->value;?>
"><span class='muted'><?php echo vtranslate($_smarty_tpl->tpl_vars['tax']->value['taxlabel'],$_smarty_tpl->tpl_vars['MODULE']->value);?>
(%)</span></td><td class="fieldValue <?php echo $_smarty_tpl->tpl_vars['WIDTHTYPE']->value;?>
"><span class="value textOverflowEllipsis" data-field-type="<?php echo $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->getFieldDataType();?>
" ><?php if ($_smarty_tpl->tpl_vars['tax']->value['check_value'] == 1) {
echo $_smarty_tpl->tpl_vars['tax']->value['percentage'];
} else { ?>0<?php }?></span></td><?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);
} elseif ($_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('uitype') == "69" || $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('uitype') == "105") {
if ($_smarty_tpl->tpl_vars['COUNTER']->value != 0) {
if ($_smarty_tpl->tpl_vars['COUNTER']->value == 2) {?></tr><tr><?php $_smarty_tpl->_assignInScope('COUNTER', 0);
}
}?><td class="fieldLabel <?php echo $_smarty_tpl->tpl_vars['WIDTHTYPE']->value;?>
"><span class="muted"><?php ob_start();
echo $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('label');
$_prefixVariable12 = ob_get_clean();
ob_start();
echo $_smarty_tpl->tpl_vars['MODULE_NAME']->value;
$_prefixVariable13 = ob_get_clean();
echo vtranslate($_prefixVariable12,$_prefixVariable13);?>
</span></td><td class="fieldValue <?php echo $_smarty_tpl->tpl_vars['WIDTHTYPE']->value;?>
"><div id="imageContainer" width="300" height="200"><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['IMAGE_DETAILS']->value, 'IMAGE_INFO', false, 'ITER');
$_smarty_tpl->tpl_vars['IMAGE_INFO']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['ITER']->value => $_smarty_tpl->tpl_vars['IMAGE_INFO']->value) {
$_smarty_tpl->tpl_vars['IMAGE_INFO']->do_else = false;
if (!empty($_smarty_tpl->tpl_vars['IMAGE_INFO']->value['url'])) {?><img src="<?php echo $_smarty_tpl->tpl_vars['IMAGE_INFO']->value['url'];?>
" width="300" height="200"><?php }
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?></div></td><?php $_smarty_tpl->_assignInScope('COUNTER', $_smarty_tpl->tpl_vars['COUNTER']->value+1);
} else {
if ($_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('uitype') == "20" || $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('uitype') == "19" || $_smarty_tpl->tpl_vars['fieldDataType']->value == 'reminder' || $_smarty_tpl->tpl_vars['fieldDataType']->value == 'recurrence') {
if ($_smarty_tpl->tpl_vars['COUNTER']->value == '1') {?><td class="fieldLabel <?php echo $_smarty_tpl->tpl_vars['WIDTHTYPE']->value;?>
"></td><td class="<?php echo $_smarty_tpl->tpl_vars['WIDTHTYPE']->value;?>
"></td></tr><tr><?php $_smarty_tpl->_assignInScope('COUNTER', 0);
}
}
if ($_smarty_tpl->tpl_vars['COUNTER']->value == 2) {?></tr><tr><?php $_smarty_tpl->_assignInScope('COUNTER', 1);
} else {
$_smarty_tpl->_assignInScope('COUNTER', $_smarty_tpl->tpl_vars['COUNTER']->value+1);
}?><td class="fieldLabel <?php echo $_smarty_tpl->tpl_vars['WIDTHTYPE']->value;?>
 textOverflowEllipsis" id="<?php echo $_smarty_tpl->tpl_vars['MODULE_NAME']->value;?>
_detailView_fieldLabel_<?php echo $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->getName();?>
" <?php if ($_smarty_tpl->tpl_vars['FIELD_MODEL']->value->getName() == 'description' || $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('uitype') == '69') {?> style='width:8%'<?php }?>><span class="muted"><?php if ($_smarty_tpl->tpl_vars['MODULE_NAME']->value == 'Documents' && $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('label') == "File Name" && $_smarty_tpl->tpl_vars['RECORD']->value->get('filelocationtype') == 'E') {
ob_start();
echo $_smarty_tpl->tpl_vars['MODULE_NAME']->value;
$_prefixVariable14 = ob_get_clean();
echo vtranslate("LBL_FILE_URL",$_prefixVariable14);
} else {
ob_start();
echo $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('label');
$_prefixVariable15 = ob_get_clean();
ob_start();
echo $_smarty_tpl->tpl_vars['MODULE_NAME']->value;
$_prefixVariable16 = ob_get_clean();
echo vtranslate($_prefixVariable15,$_prefixVariable16);
}
if (($_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('uitype') == '72') && ($_smarty_tpl->tpl_vars['FIELD_MODEL']->value->getName() == 'unit_price')) {?>(<?php echo $_smarty_tpl->tpl_vars['BASE_CURRENCY_SYMBOL']->value;?>
)<?php }?></span></td><td class="fieldValue <?php echo $_smarty_tpl->tpl_vars['WIDTHTYPE']->value;?>
" id="<?php echo $_smarty_tpl->tpl_vars['MODULE_NAME']->value;?>
_detailView_fieldValue_<?php echo $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->getName();?>
" <?php if ($_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('uitype') == '19' || $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('uitype') == '20' || $_smarty_tpl->tpl_vars['fieldDataType']->value == 'reminder' || $_smarty_tpl->tpl_vars['fieldDataType']->value == 'recurrence') {?> colspan="3" <?php $_smarty_tpl->_assignInScope('COUNTER', $_smarty_tpl->tpl_vars['COUNTER']->value+1);?> <?php }?>><?php $_smarty_tpl->_assignInScope('FIELD_VALUE', $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('fieldvalue'));
if ($_smarty_tpl->tpl_vars['fieldDataType']->value == 'multipicklist') {
$_smarty_tpl->_assignInScope('FIELD_DISPLAY_VALUE', $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->getDisplayValue($_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('fieldvalue')));
} else {
$_smarty_tpl->_assignInScope('FIELD_DISPLAY_VALUE', Vtiger_Util_Helper::toSafeHTML($_smarty_tpl->tpl_vars['FIELD_MODEL']->value->getDisplayValue($_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('fieldvalue'))));
}?><span class="value textOverflowEllipsis" data-field-type="<?php echo $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->getFieldDataType();?>
" <?php if ($_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('uitype') == '19' || $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('uitype') == '20' || $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('uitype') == '21') {?> style="white-space:normal;" <?php }?> <?php if ($_smarty_tpl->tpl_vars['fieldDataType']->value == 'email') {?>title='<?php echo $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('fieldvalue');?>
'<?php }?> ><?php if ($_smarty_tpl->tpl_vars['FIELD_MODEL']->value->getName() != 'defaultlandingpage') {
$_smarty_tpl->_subTemplateRender(vtemplate_path($_smarty_tpl->tpl_vars['FIELD_MODEL']->value->getUITypeModel()->getDetailViewTemplateName(),$_smarty_tpl->tpl_vars['MODULE_NAME']->value), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('FIELD_MODEL'=>$_smarty_tpl->tpl_vars['FIELD_MODEL']->value,'USER_MODEL'=>$_smarty_tpl->tpl_vars['USER_MODEL']->value,'MODULE'=>$_smarty_tpl->tpl_vars['MODULE_NAME']->value,'RECORD'=>$_smarty_tpl->tpl_vars['RECORD']->value), 0, true);
} else {
echo vtranslate($_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('fieldvalue'),$_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('fieldvalue'));
}?></span><?php if ($_smarty_tpl->tpl_vars['IS_AJAX_ENABLED']->value && $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->isEditable() == 'true' && $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->isAjaxEditable() == 'true') {?><span class="hide edit pull-left"><?php if ($_smarty_tpl->tpl_vars['fieldDataType']->value == 'multipicklist') {?><input type="hidden" class="fieldBasicData" data-name='<?php echo $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('name');?>
[]' data-type="<?php echo $_smarty_tpl->tpl_vars['fieldDataType']->value;?>
" data-displayvalue='<?php echo $_smarty_tpl->tpl_vars['FIELD_DISPLAY_VALUE']->value;?>
' data-value="<?php echo $_smarty_tpl->tpl_vars['FIELD_VALUE']->value;?>
" /><?php } else { ?><input type="hidden" class="fieldBasicData" data-name='<?php echo $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('name');?>
' data-type="<?php echo $_smarty_tpl->tpl_vars['fieldDataType']->value;?>
" data-displayvalue='<?php echo $_smarty_tpl->tpl_vars['FIELD_DISPLAY_VALUE']->value;?>
' data-value="<?php echo $_smarty_tpl->tpl_vars['FIELD_VALUE']->value;?>
" /><?php }?></span><span class="action pull-right"><a href="#" onclick="return false;" class="editAction fa fa-pencil"></a></span><?php }?></td><?php }
if (count($_smarty_tpl->tpl_vars['FIELD_MODEL_LIST']->value) == 1 && $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('uitype') != "19" && $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('uitype') != "20" && $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('uitype') != "30" && $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('name') != "recurringtype" && $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('uitype') != "69" && $_smarty_tpl->tpl_vars['FIELD_MODEL']->value->get('uitype') != "105") {?><td class="fieldLabel <?php echo $_smarty_tpl->tpl_vars['WIDTHTYPE']->value;?>
"></td><td class="<?php echo $_smarty_tpl->tpl_vars['WIDTHTYPE']->value;?>
"></td><?php }
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);
if (end($_smarty_tpl->tpl_vars['FIELD_MODEL_LIST']->value) == true && count($_smarty_tpl->tpl_vars['FIELD_MODEL_LIST']->value) != 1 && $_smarty_tpl->tpl_vars['COUNTER']->value == 1) {?><td class="fieldLabel <?php echo $_smarty_tpl->tpl_vars['WIDTHTYPE']->value;?>
"></td><td class="<?php echo $_smarty_tpl->tpl_vars['WIDTHTYPE']->value;?>
"></td><?php }?></tr></tbody></table></div></div><br><?php }
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);
}
}
