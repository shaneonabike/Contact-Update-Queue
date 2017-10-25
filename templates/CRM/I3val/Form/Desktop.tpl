{*-------------------------------------------------------+
| Ilja's Input Validation Extension                      |
| Amnesty International Vlaanderen                       |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*}

{literal}
<style>
  .ui-progressbar {
    position: relative;
  }
  .progress-label {
    position: absolute;
    left: 50%;
    top: 4px;
    font-weight: bold;
    text-shadow: 1px 1px 0 #fff;
  }
</style>
{/literal}

{* header with progress bar *}
<div id="progressbar" class="ui-progressbar">
  <div class="progress-label"></div>
</div>
<br/>

{* activity info *}
<table class="crm-info-panel">
  <tbody>
    <tr>
      <td class="label">{ts}With Contact{/ts}</td>
      <td class="view-value">
        <span class="crm-frozen-field">
          <a class="view-contact no-popup" href="{$activity.with_link}" title="View Contact">{$activity.with_name}</a>
          <input value="{$activity.with_id}" name="target_contact_id" type="hidden">
        </span>
      </td>
    </tr>
    <tr>
      <td class="label">{ts}Subject{/ts}</td>
      <td class="view-value">{$activity.subject}</td>
    </tr>
    <tr>
      <td class="label">{ts}Campaign{/ts}</td>
      <td class="view-value">{if $activity.campaign_id}{$activity.campaign_name} [{$activity.campaign_id}]{else}<i>{ts}None{/ts}{/if}</td>
    </tr>
    <tr>
      <td class="label">{ts}Scheduled Date{/ts}</td>
      <td class="view-value">{$activity.activity_date_time|crmDate}</td>
    </tr>
    <tr>
      <td class="label">{ts}Status{/ts}</td>
      <td class="view-value">{$activity.status}</td>
    </tr>
  </tbody>
</table>

{* contact history *}
<div class="crm-accordion-wrapper collapsed">
  <div class="crm-accordion-header">{ts}Quick Contact History{/ts}</div>
  <div class="crm-accordion-body">
    <table class="i3val">
      <thead style="font-weight: bold;">
        <tr>
          <td>{ts}Type{/ts}</td>
          <td>{ts}Subject{/ts}</td>
          <td>{ts}Added by{/ts}</td>
          <td>{ts}Date{/ts}</td>
          <td>{ts}Status{/ts}</td>
          <td></td>
        </tr>
      </thead>
      <tbody>
      {foreach from=$history item=history_entry}
        <tr>
          <td>{$history_entry.type}</td>
          <td>{$history_entry.subject}</td>
          <td>{$history_entry.added_by}</td>
          <td>{$history_entry.date|crmDate}</td>
          <td>{$history_entry.status}</td>
          <td></td>
        </tr>
      {/foreach}
      </tbody>
    </table>
  </div>
</div>

<br/>

{* handler rendering *}
{foreach from=$handler_templates item=handler_template}
<div class="i3val-update">
{include file=$handler_template}
</div>
{/foreach}

{* actions *}
<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>


<script type="text/javascript">
// create progress bar
var processed_count = {$processed_count}; // the one we're working on
var pending_count   = {$pending_count};
var progress        = {$progress};
{literal}
cj(function() {
  cj("#progressbar").progressbar({value: progress * 100, disable: true});
  cj("div.progress-label").text((processed_count + 1 ) + " / " + (processed_count + pending_count));
});
{/literal}
</script>