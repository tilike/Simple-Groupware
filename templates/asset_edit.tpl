{*
 * @package Simple Groupware
 * @link http://www.simple-groupware.de
 * @copyright Simple Groupware Solutions Thomas Bley 2002-2012
 * @license GPLv2
 *}
<script type="text/javascript" src="ext/cache/functions_edit_{$smarty.const.LANG}.js?{$smarty.const.CORE_VERSION}"></script>
{strip}
{if $t.rights.write}
<form method="post" action="index.php?" enctype="multipart/form-data" onsubmit="window.onbeforeunload=null; return form_submit(this);" id="asset_form" name="asset_form">
<input type="hidden" name="token" value="{""|modify::get_form_token}">
<input type="submit" class="hide"/>
{if $t.schema_mode eq "new"}
  {foreach key=lkey item=litem from=$t.limits}
    <input type="hidden" name="form_fields[]" value="{$litem}">
  {/foreach}
{else}
  {foreach key=lkey item=litem from=$t.data}
    <input type="hidden" name="form_fields[]" value="{$litem._id}">
    <input type="hidden" name="item[]" value="{$litem._id}">
  {/foreach}
{/if}
{foreach key=tab_key item=tab_item from=$t.tabs}
  {if $tab_key eq "general" || !$t.disable_tabs}
    {if $cycle_dataitem neq "items_odd"}{cycle assign="cycle_dataitem" values="items_even,items_odd"}{/if}
    <div class="tab2 tab2{$tab_key}" {if $tab_key neq "general"}style="display:none;"{/if}>
    {if $t.schema_mode eq "new"}
      {foreach key=lkey item=data_item from=$t.limits}
        {assign var="itemid" value=$data_item|escape:"md5"}
		<div id="form_{$itemid}" class="prefix">
        {include file="helper/form_fields.tpl" prefix="form_$itemid" mode="create"}
		</div>
      {/foreach}
    {else}
      {foreach key=lkey item=data_item from=$t.data}
		{assign var="itemid" value=$data_item._id|escape:"md5"}
		<div id="form_{$itemid}" class="prefix">
        {include file="helper/form_fields.tpl" prefix="form_$itemid" mode="edit"}
		</div>
  	  {/foreach}
    {/if}
    </div>
  {/if}
{/foreach}
{if $t.schema_mode eq "new" || $t.schema_mode eq "edit_as_new"}
  <input type="hidden" name="form_submit_create" value="1">
{else}
  <input type="hidden" name="form_submit_edit" value="1">
{/if}
<input type="hidden" id="form_submit_return" name="form_submit_return" value="">
<input type="hidden" id="form_submit_go_edit" name="form_submit_go_edit" value="">

<table cellspacing="0" border="0" class="data data_page">
  <tr>
	{if $t.datasets>0}
    <td style="width:10px;"><input type="checkbox" id="itemall" value="" class="checkbox" onmousedown="mselectall(this.checked);">
    <a onclick="mselectallkey(); return false;" href="#" accesskey="a"></a></td>
	{/if}
    <td style="width:50px;"><input onkeypress="if (getmykey(event)==13)	{ldelim} locate('index.php?limit='+escape(this.value)); return false; {rdelim}" type="text" maxlength="5" value="{$t.limit}" class="input" style="text-align:center; width:45px;"></td>
    <td style="text-align:center;">
  	  <input accesskey="s" title="Alt-s" type="submit" id="form_submit_b" value="   {t}S a v e{/t}   " class="submit bold" {if $sys.is_mobile}style="width:auto;"{/if}>
  	  <input accesskey="b" title="Alt-b" type="submit" value="   {t}S a v e  a n d  g o  b a c k{/t}   " class="submit bold" onclick="set_val('form_submit_return', 1);" {if $sys.is_mobile}style="width:auto;"{/if}>

	  {if $t.schema_mode eq "new" || $t.schema_mode eq "edit_as_new"}
		<input type="submit" value="   {t}S a v e  a n d  E d i t{/t}   " class="submit" onclick="set_val('form_submit_go_edit', 1);" {if $sys.is_mobile}style="width:auto;"{/if}>
	  {/if}
	  {assign var=back_url value=$sys.history|@array_slice:-2:1|@array_pop}
	  {if in_array($back_url[2], array("new", "edit_as_new"))}
		{assign var=back_url value=$sys.history|@array_slice:-3:1|@array_pop}
	  {/if}
	  {if $back_url[2] neq ""}
		<input type="button" value="{t}C a n c e l{/t}" class="submit" style="width:120px;" onclick="locate('index.php?view={$back_url[2]}');">
	  {/if}
	</td>
	{if !$sys.is_mobile}<td style="width:50px;"></td>{/if}
	{if $t.maxdatasets>0}
	<td style="text-align:right; white-space:nowrap; width:10px;" class="default">
      {if $t.datasets>0}[{$t.datasets}/{$t.maxdatasets}]{/if}
    </td>
	{/if}
  </tr>
</table>

</form>
<script>focus_form("content_def_table");</script>
{/if}
{/strip}