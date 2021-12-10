<?php
/**
 * @file
 *
 * Settings declaration for Line Item Tweaks extension.
 */

return [
  'lineitemtweaks_membership_label' => [
    'group_name' => 'Line Item Tweaks',
    'group' => 'lineitemtweaks',
    'name' => 'lineitemtweaks_membership_label',
    'type' => 'String',
    'default' => 'Membership Id %1: %2 from %3 to %4',
    'add' => '5.0',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Membership Line item Label',
    'help_text' => 'Label to use for Membership line items.  %1 - Membership ID, %2 - Type, %3 - Term start date, %4 - Term end date, %5 - Membership Organisation name, %6 - Member name',
  ],
  'lineitemtweaks_membership_label_lifetime' => [
    'group_name' => 'Line Item Tweaks',
    'group' => 'lineitemtweaks',
    'name' => 'lineitemtweaks_membership_label_lifetime',
    'type' => 'String',
    'default' => 'Membership Id %1: %2 from %3 onward',
    'add' => '5.0',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Membership Line item Label for lifetime memberships',
    'help_text' => 'Label to use for (Lifetime) Membership line items.  %1 - Membership ID, %2 - Type, %3 - Term start date, %4 - skipped, %5 - Membership Organisation name, %6 - Member name',
  ],
];
