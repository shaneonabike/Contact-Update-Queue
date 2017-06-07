<?php
/*-------------------------------------------------------+
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
+--------------------------------------------------------*/

class CRM_I3val_Logic {

  /**
   * Create a Manual Update Activity with the given data
   *
   * @param $contact_id  int    the contact to be updated
   * @param $update      array  the new values
   * @param $params      array  additional parameters like 'activity_id' for the trigger activity
   */
  public static function createManualContactUpdateActivity($contact_id, $update, $params = array()) {
    // first: load contact
    $contact = civicrm_api3('Contact', 'getsingle', array('id' => $contact_id));

    // generate diff data
    $activity_data = self::createDiff($contact, $update, CRM_I3val_Configuration::getContactUpdateFields());

    // add basic activity params
    self::addActivityParams($params, $contact_id, $activity_data);

    // add specific activity params
    // TODO:
    $activity_data['subject'] = "Manual Contact Updgrade (TODO)";
    $activity_data['activity_type_id'] = CRM_Core_OptionGroup::getValue('activity_type', 'FWTM Contact Update', 'name');

    // create activity, reload and return
    CRM_I3val_CustomData::resolveCustomFields($activity_data);
    $activity = civicrm_api3('Activity', 'create', $activity_data);
    return civicrm_api3('Activity', 'getsingle', array('id' => $activity['id']));
  }


  /**
   * Generate the orginal/submitted data for the given fields
   *
   * @param $original_data  array the data as it's currently present in DB
   * @param $submitted_data array the data as it's been submitted
   * @param $field_specs    array see CRM_I3val_Configuration::getContactUpdateFields()
   */
  protected static function createDiff($original_data, $submitted_data, $field_specs) {
    $diff_data = array();
    foreach ($field_specs as $field_name => $field_spec) {
      if (isset($submitted_data[$field_name])) {
        // an update was submitted
        $diff_data["{$field_spec['custom_group']}.{$field_name}_submitted"] = $submitted_data[$field_name];
        $diff_data["{$field_spec['custom_group']}.{$field_name}_original"]  = CRM_Utils_Array::value($field_name, $original_data, '');
      }
    }
    return $diff_data;
  }

  /**
   * Add the generic activity parameters, partly derived from the $params
   *
   * @param $params         array the parameters present
   * @param $activity_data  array the activity parameters will be added to this array
   */
  protected static function addActivityParams($params, $contact_id, &$activity_data) {
    $activity_data['activity_date_time'] = date('YmdHis'); // NOW
    $activity_data['status_id'] = CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name');

    if (!empty($params['activity_id'])) {
      $activity_data['parent_id'] = $params['activity_id'];

      $trigger_activity = civicrm_api3('Activity', 'getsingle', array('id' => $params['activity_id']));
      if (!empty($trigger_activity['campaign_id'])) {
        $activity_data['campaign_id'] = $trigger_activity['campaign_id'];
      }
    }

    // assign contacts
    $activity_data['assignee_id'] = CRM_I3val_Configuration::getAssignee();
    $activity_data['source_contact_id'] = CRM_I3val_Configuration::getCurrentUserID();
    $activity_data['target_id'] = $contact_id;
  }


  /**
   * Inject the JavaScript to adjust the activity view
   */
  public static function adjustAcitivityView($activity_id, $activity_type_id) {
    switch ($activity_type_id) {
      case CRM_Core_OptionGroup::getValue('activity_type', 'FWTM Contact Update', 'name'):
        $fields = CRM_I3val_Configuration::getContactUpdateFields();
        break;

      case CRM_Core_OptionGroup::getValue('activity_type', 'FWTM Mandate Update', 'name'):
        $fields = CRM_I3val_Configuration::getMandateUpdateFields();
        break;

      default:
        // that's not one of ours -> do nothing!
        error_log("OUT");
        return;
    }

    // load data
    $activity = civicrm_api3('Activity', 'getsingle', array('id' => $activity_id));
    $values = array();
    foreach ($fields as $field_name => $field_spec) {
      $original_data_field = CRM_I3val_CustomData::getCustomField($field_spec['custom_group'], "{$field_name}_original");
      $submitted_data_field = CRM_I3val_CustomData::getCustomField($field_spec['custom_group'], "{$field_name}_submitted");
      $applied_data_field = CRM_I3val_CustomData::getCustomField($field_spec['custom_group'], "{$field_name}_applied");
      if (isset($activity["custom_{$original_data_field['id']}"])) {
        // i.e. the value is set
        $values[] = array(
          'title'      => $field_spec['title'],
          'field_name' => $field_name,
          'original'   => CRM_Utils_Array::value("custom_{$original_data_field['id']}",  $activity, ''),
          'submitted'  => CRM_Utils_Array::value("custom_{$submitted_data_field['id']}", $activity, ''),
          'applied'    => CRM_Utils_Array::value("custom_{$applied_data_field['id']}",   $activity, ''),
          );
      }
    }

    // render panel
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('i3val_activity', $activity);
    $smarty->assign('i3val_values', $values);
    $smarty->assign('i3val_edit', ($activity['status_id'] == 1));
    $panel = array(
      'html'   => $smarty->fetch('CRM/Activity/I3valPanel.tpl'),
      'fields' => $fields);

    $script = file_get_contents(__DIR__ . '/../../js/activity_view_changes.js');
    $script = str_replace('INJECTED_ACTIVITY_ID', $activity_id, $script);
    $script = str_replace('INJECTED_ACTIVITY_TYPE_ID', $activity_type_id, $script);
    $script = str_replace('INJECTED_PANEL', json_encode($panel), $script);

    CRM_Core_Region::instance('page-footer')->add(array('script' => $script));
  }
}