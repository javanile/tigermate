<?php
/* Smarty version 3.1.39, created on 2023-06-16 15:41:45
  from '/var/www/html/lib/layouts/v7/modules/Calendar/SharedCalendarView.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_648c82b9da9ff0_72604337',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'e75d5599d289dd2fe272864ada107020e3191a21' => 
    array (
      0 => '/var/www/html/lib/layouts/v7/modules/Calendar/SharedCalendarView.tpl',
      1 => 1686745045,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_648c82b9da9ff0_72604337 (Smarty_Internal_Template $_smarty_tpl) {
?><input type="hidden" id="currentView" value="<?php echo $_REQUEST['view'];?>
" /><input type="hidden" id="start_day" value="<?php echo $_smarty_tpl->tpl_vars['CURRENT_USER']->value->get('dayoftheweek');?>
" /><input type="hidden" id="activity_view" value="<?php echo $_smarty_tpl->tpl_vars['CURRENT_USER']->value->get('activity_view');?>
" /><input type="hidden" id="time_format" value="<?php echo $_smarty_tpl->tpl_vars['CURRENT_USER']->value->get('hour_format');?>
" /><input type="hidden" id="start_hour" value="<?php echo $_smarty_tpl->tpl_vars['CURRENT_USER']->value->get('start_hour');?>
" /><input type="hidden" id="date_format" value="<?php echo $_smarty_tpl->tpl_vars['CURRENT_USER']->value->get('date_format');?>
" /><input type="hidden" id="hideCompletedEventTodo" value="<?php echo $_smarty_tpl->tpl_vars['CURRENT_USER']->value->get('hidecompletedevents');?>
"><input type="hidden" id="show_allhours" value="<?php echo $_smarty_tpl->tpl_vars['CURRENT_USER']->value->get('showallhours');?>
" /><div id="sharedcalendar" class="calendarview col-lg-12"><?php $_smarty_tpl->_assignInScope('LEFTPANELHIDE', $_smarty_tpl->tpl_vars['CURRENT_USER']->value->get('leftpanelhide'));?><div class="essentials-toggle" title="<?php echo vtranslate('LBL_LEFT_PANEL_SHOW_HIDE','Vtiger');?>
"><span class="essentials-toggle-marker fa <?php if ($_smarty_tpl->tpl_vars['LEFTPANELHIDE']->value == '1') {?>fa-chevron-right<?php } else { ?>fa-chevron-left<?php }?> cursorPointer"></span></div></div>
<?php }
}
