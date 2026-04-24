{strip}
<table class="table table-bordered equalSplit detailview-table">
	<thead>
	<tr>
		<th class="blockHeader" colspan="4">
			&nbsp;&nbsp;{vtranslate('LBL_WEBFORM_INFORMATION', $MODULE_NAME)}
		</th>
	</tr>
	</thead>
	<tbody>
	{assign var=COUNTER value=0}
	<tr>
	{foreach item=DETAIL_ROW from=$DETAIL_INFORMATION}
		{if $COUNTER eq 2}
			</tr><tr>
			{assign var=COUNTER value=0}
		{/if}
		<td class="fieldLabel">
			<label class="muted pull-right marginRight10px">{vtranslate($DETAIL_ROW.label, $MODULE_NAME)}</label>
		</td>
		<td class="fieldValue">
			<span class="value">{$DETAIL_ROW.value|escape|nl2br nofilter}</span>
		</td>
		{assign var=COUNTER value=$COUNTER+1}
	{/foreach}
	{if $COUNTER eq 1}
		<td class="fieldLabel"></td><td class="fieldValue"></td>
	{/if}
	</tr>
	</tbody>
</table>
<br>
{/strip}
