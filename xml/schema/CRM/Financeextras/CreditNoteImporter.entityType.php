<?php
// This file declares a new entity type. For more details, see "hook_civicrm_entityTypes" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
//
// CreditNoteImporter is a fake entity used solely to expose the credit-note
// CSV importer to APIv3 so that it can be picked up by the
// nz.co.fuzion.csvimport extension. No physical table is ever created for
// this entity (the listed table name only exists to satisfy CiviCRM
// metadata expectations).
return [
  [
    'name' => 'CreditNoteImporter',
    'class' => 'CRM_Financeextras_DAO_CreditNoteImporter',
    'table' => 'financeextras_credit_note_importer_fake_entity',
  ],
];
