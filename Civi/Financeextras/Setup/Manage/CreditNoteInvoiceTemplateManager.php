<?php

namespace Civi\Financeextras\Setup\Manage;

use Civi\Api4\OptionValue;
use Civi\Api4\MessageTemplate;
use CRM_Financeextras_ExtensionUtil as E;
use Civi\Financeextras\WorkflowMessage\CreditNoteInvoice;

/**
 * Manages CreditNote Invoice Template.
 */
class CreditNoteInvoiceTemplateManager extends AbstractManager {

  /**
   * Adds custom credit note invoice template.
   */
  public function create(): void {
    $messageTemplate = MessageTemplate::get(FALSE)
      ->addSelect('id')
      ->addWhere('workflow_name', '=', CreditNoteInvoice::WORKFLOW)
      ->execute()
      ->first();

    $templatePath = E::path('/templates/CRM/Financeextras/MessageTemplate/CreditNoteInvoice.tpl');
    $templateBodyHtml = file_get_contents($templatePath);

    $params = [
      'workflow_name' => CreditNoteInvoice::WORKFLOW,
      'msg_title' => 'Credit Note Invoice',
      'msg_subject' => 'Credit Note Invoice',
      'msg_html' => $templateBodyHtml,
      'is_reserved' => 0,
      'is_default' => 1,
    ];

    if (!empty($messageTemplate)) {
      $params = array_merge(['id' => $messageTemplate['id']], $params);
    }

    $optionValue = OptionValue::get(FALSE)
      ->addWhere('option_group_id:name', '=', 'msg_tpl_workflow_contribution')
      ->addWhere('name', '=', CreditNoteInvoice::WORKFLOW)
      ->execute()
      ->first();

    if (empty($optionValue)) {
      $optionValue = OptionValue::create(FALSE)
        ->addValue('option_group_id.name', 'msg_tpl_workflow_contribution')
        ->addValue('label', 'CreditNote Invoice')
        ->addValue('name', CreditNoteInvoice::WORKFLOW)
        ->execute()
        ->first();
    }

    $params['workflow_id'] = $optionValue['id'];

    MessageTemplate::save(FALSE)->addRecord($params)->execute();
  }

  public function replaceText(string $search, string $replace): void {
    if (empty($search)) {
      return;
    }

    $messageTemplate = MessageTemplate::get(FALSE)
      ->addSelect('id', 'msg_html')
      ->addWhere('workflow_name', '=', CreditNoteInvoice::WORKFLOW)
      ->execute()
      ->first();
    if (empty($messageTemplate)) {
      return;
    }

    $replaced = str_replace($search, $replace, $messageTemplate['msg_html']);

    MessageTemplate::update(FALSE)
      ->addValue('msg_html', $replaced)
      ->addWhere('id', '=', $messageTemplate['id'])
      ->execute();
  }

  /**
   * {@inheritDoc}
   */
  public function remove(): void {
    MessageTemplate::delete(FALSE)
      ->addWhere('workflow_name', '=', CreditNoteInvoice::WORKFLOW)
      ->execute();

    OptionValue::delete(FALSE)
      ->addWhere('option_group_id:name', '=', 'msg_tpl_workflow_contribution')
      ->addWhere('name', '=', CreditNoteInvoice::WORKFLOW)
      ->execute()
      ->first();
  }

  /**
   * {@inheritDoc}
   */
  protected function toggle($status): void {
    MessageTemplate::update(FALSE)
      ->addValue('is_active', $status)
      ->addWhere('workflow_name', '=', CreditNoteInvoice::WORKFLOW)
      ->execute();
  }

}
