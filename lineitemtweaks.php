<?php

require_once 'lineitemtweaks.civix.php';

use CRM_Lineitemtweaks_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function lineitemtweaks_civicrm_config(&$config) {
  _lineitemtweaks_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function lineitemtweaks_civicrm_install() {
  _lineitemtweaks_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function lineitemtweaks_civicrm_postInstall() {
  _lineitemtweaks_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function lineitemtweaks_civicrm_uninstall() {
  _lineitemtweaks_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function lineitemtweaks_civicrm_enable() {
  _lineitemtweaks_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function lineitemtweaks_civicrm_disable() {
  _lineitemtweaks_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function lineitemtweaks_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _lineitemtweaks_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_pre().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_pre
 */
function lineitemtweaks_civicrm_pre($op, $objectName, $id, &$params) {
  switch ($objectName) {
    case 'LineItem':

      // Exit early if a delete operation
      if ('delete' == $op) {
        return;
      }

      // Fetch the contribution if available, required for other processes
      if (!empty($params['contribution_id'])) {
        $contribution = civicrm_api3('Contribution', 'getsingle', ['id' => $params['contribution_id']]);
      }

      // The membership_id may be stored in two possible variables depending on how the membership was created
      if ($params['entity_table'] == 'civicrm_membership') {
        // Usually set for back-end memberships
        $membership_id = $params['entity_id'] ?? NULL;
      }
      else {
        // Usually set by Web Form
        $membership_id = $params['membership_id'] ?? NULL;
      }


      // If the line items are being created or line items have been previously created and the membership id is set then...
      if (('create' == $op || 'edit' == $op) && !empty($membership_id)) {
        // This will catch when Webform creates the Line Items before creating the Contribution
        // And for back-end memberships, the Contribution will be created before the Line Items which makes more sense
        if (!empty($contribution)) {

          // Webform may not set these variables, so do it now as this is used by _lineitemtweaks_fix_membership_lineitem
          if (empty($params['entity_table'])) {
            $params['entity_table'] = 'civicrm_membership';
            $params['entity_id'] = $params['membership_id'];
          }

          _lineitemtweaks_fix_membership_lineitem($contribution, $params);
        }
      }
      elseif (($params['entity_id'] == $params['contribution_id']) && ($params['entity_table'] == 'civicrm_contribution')) {
        if ('create' == $op) {
          try {
            $financial_type_name = civicrm_api3('FinancialType', 'getvalue', [
              'return' => 'name',
              'id' => $params['financial_type_id'],
            ]);
            if ((empty($params['label']) || ($params['label'] == $financial_type_name)) && !empty($contribution['contribution_source'])) {
              $params['label'] = $contribution['contribution_source'];
            }
          } catch (CiviCRM_API3_Exception $e) {
          }
        }
      }
      elseif (('create' == $op || 'edit' == $op) && ('civicrm_participant' == $params['entity_table'])) {
        $entity = civicrm_api3('Participant', 'getsingle', [
          'id' => $params['entity_id'],
        ]);

        $description = $entity['event_title'];

        if (!empty($entity['event_type'])) {
          $description .= ' (' . $entity['event_type'] . ') ';
        }
        if (!empty($entity['event_start_date'])) {
          $description .= ' on ' . strftime('%e/%m/%Y', strtotime($entity['event_start_date']));
        }

        $params['label'] = $description;
      }
      break;

    default:
      break;
  }
}

function __lineitemtweaks_new_membership($id, $add = FALSE) {
  static $new_membership = [];
  if ($add == TRUE) {
    $new_membership[$id] = TRUE;
  }
  return !empty($new_membership[$id]);
}

/**
 * Implements hook_civicrm_post().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_post
 */
function lineitemtweaks_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  switch ($objectName) {
    case 'Contribution':
      if ('create' == $op) {
        $line_items = civicrm_api3('LineItem', 'get', [
          'contribution_id' => $objectRef->id,
          'entity_table' => [
            'IN' => [
              'civicrm_contribution',
              'civicrm_membership',
            ],
          ],
          'return' => 'id,contribution_id,entity_table,entity_id,qty',
        ]);

        $contribution = [
          'id' => $objectId,
          'contribution_status_id' => $objectRef->contribution_status_id,
        ];

        foreach ($line_items['values'] as &$item) {
          if (($item['entity_table'] == 'civicrm_membership') && !empty($item['entity_id'])) {
            _lineitemtweaks_fix_membership_lineitem($contribution, $item);
          }
          civicrm_api3('LineItem', 'create', $item);
        }
      }
      break;

    case 'Membership':
      if ('create' == $op) {
        __lineitemtweaks_new_membership($objectId, TRUE);
      }
      break;

    default:
      break;
  }
}

function _lineitemtweaks_fix_membership_lineitem($contribution, &$params) {
  try {
    $membership = civicrm_api3('Membership', 'getsingle', ['id' => $params['entity_id']]);

    $membership_type = civicrm_api3('MembershipType', 'getsingle', ['id' => $membership['membership_type_id']]);

    $member_name = civicrm_api3('Contact', 'getvalue', [
      'id' => $membership['contact_id'],
      'return' => 'display_name',
    ]);

    $org_name = civicrm_api3('Contact', 'getvalue', [
      'id' => $membership_type['member_of_contact_id'],
      'return' => 'display_name',
    ]);
    $type = $membership_type['name'];
    $membershipToUse = $membership;

    if ($membership_type['duration_unit'] != 'lifetime') {
      // Normal memberships (Not lifetime)
      if (!__lineitemtweaks_new_membership($membership["id"])) {
        $status = FALSE;

        if (!empty($contribution['id'])) {
          $status = Civi\Api4\Contribution::get(FALSE)
            ->addSelect('contribution_status_id:name')
            ->addWhere('id', '=', $contribution['id'])
            ->execute()
            ->first();
          $status = $status['contribution_status_id:name'];
        }

        if ($status == 'Pending') {
          // Derive new membership dates according to the end date and number of terms
          $membershipToUse = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType(
            $membership['id'],
            NULL,
            NULL,
            $params['qty'] ?? 1
          );
        }
        else {
          // Get new membership dates from the log, which should be written out already.
          $lastMembershipLog = civicrm_api3('MembershipLog', 'get', [
            'sequential' => 1,
            'membership_id' => $membership["id"],
            'options' => ['limit' => 1, 'sort' => "id DESC"],
          ]);
          if ($lastMembershipLog["count"]) {
            $lastMembershipLog = $lastMembershipLog["values"][0];
            $membershipToUse = $lastMembershipLog;

          }
        }
      }

      $from = strftime('%m/%Y', strtotime($membershipToUse['log_start_date'] ?? $membershipToUse['start_date']));
      $to = strftime('%m/%Y', strtotime($membershipToUse['end_date']));

      $label = civicrm_api3('Setting', 'getvalue', ['name' => 'lineitemtweaks_membership_label']);
      $params['label'] = E::ts($label, [
        1 => $membership['id'],
        2 => $type,
        3 => $from,
        4 => $to,
        5 => $org_name,
        6 => $member_name,
      ]);
    }
    else {
      $from = strftime('%m/%Y', strtotime($membershipToUse['start_date']));
      $label = civicrm_api3('Setting', 'getvalue', ['name' => 'lineitemtweaks_membership_label_lifetime']);
      $params['label'] = E::ts($label, [
        1 => $membership['id'],
        2 => $type,
        3 => $from,
        4 => '',
        5 => $org_name,
        6 => $member_name,
      ]);
    }
  } catch (CiviCRM_API3_Exception $e) {
    CRM_Core_Error::debug_log_message('Could not find membership with id "' . $params['entity_id'] . '"');
    CRM_Core_Error::backtrace(__FUNCTION__, TRUE);
  }
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function lineitemtweaks_civicrm_entityTypes(&$entityTypes) {
  _lineitemtweaks_civix_civicrm_entityTypes($entityTypes);
}
