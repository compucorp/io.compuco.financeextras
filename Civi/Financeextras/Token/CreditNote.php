<?php

namespace Civi\Financeextras\Token;

use Civi\Token\Event\TokenValueEvent;
use Civi\Token\AbstractTokenSubscriber;
use Civi\Token\TokenRow;
use CRM_Utils_Request;

/**
 * Class CRM_Financeextras_Token_CreditNote
 *
 * Generate "credit_note.*" tokens.
 *
 * This class defines tokens for credit note fields.
 */
class CreditNote extends AbstractTokenSubscriber {

  private const TOKEN = 'creditNote';

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct('creditNote', $this->geTokens());
  }

  public static function getSubscribedEvents() {
    return [
      'civi.token.list' => 'registerTokens',
      'civi.token.eval' => 'evaluateTokens',
    ];
  }

  /**
   * Determine whether this token-handler should be used with
   * the given processor.
   *
   * @param \Civi\Token\TokenProcessor $processor
   * @return bool
   */
  public function checkActive(\Civi\Token\TokenProcessor $processor) {
    $creditNoteId = $processor->getContextValues('creditNoteId');

    return (is_array($creditNoteId) && count($creditNoteId) === 1) || $this->isMessageTemplatePage();
  }

  /**
   * To perform a bulk lookup before rendering tokens
   *
   * @param \Civi\Token\Event\TokenValueEvent $e
   *
   * @return mixed
   */
  public function prefetch(TokenValueEvent $e): array {
    $creditNoteId = $e->getTokenProcessor()->getContextValues('creditNoteId');

    $resolvedTokens = [];
    if (empty($creditNoteId)) {
      return $resolvedTokens;
    }

    try {
      if (is_array($creditNoteId) && count($creditNoteId) === 1) {
        $creditNoteId = $creditNoteId[0];
        $creditNote = \Civi\Api4\CreditNote::get(FALSE)
          ->addWhere('id', '=', $creditNoteId)
          ->addChain('organization', \Civi\Api4\Contact::get(FALSE)
            ->addWhere('id', '=', '$owner_organization')
          )
          ->execute()->first();

        if (empty($creditNote)) {
          return $resolvedTokens;
        }

        $creditNote['allocated_credit'] = ($creditNote['allocated_manual_refund'] + $creditNote['allocated_online_refund'] + $creditNote['allocated_invoice']);
        $creditNote['owner_organization_id'] = $creditNote['owner_organization'];
        $creditNote['owner_organization_name'] = $creditNote['organization'][0]['display_name'];

        foreach (['allocated_credit', 'subtotal', 'sales_tax', 'total_credit', 'remaining_credit', 'allocated_invoice', 'allocated_manual_refund', 'allocated_online_refund'] as $key) {
          $creditNote[$key] = \CRM_Utils_Money::format($creditNote[$key], $creditNote['currency']);
        }
        $resolvedTokens = array_merge($this->geTokens(), (array) $creditNote);
      }
    }
    catch (\Exception $e) {
      \CRM_Core_Session::setStatus('Error resolving credit note tokens');
    }

    return $resolvedTokens;
  }

  /**
   * Evaluate the content of a single token.
   *
   * @param \Civi\Token\TokenRow $row
   *   The record for which we want token values.
   * @param string $entity
   *   The name of the token entity.
   * @param string $field
   *   The name of the token field.
   * @param mixed $prefetch
   *   Any data that was returned by the prefetch().
   */
  public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = NULL): void {
    $value = \CRM_Utils_Array::value($field, $prefetch);
    $prefix = \CRM_Utils_String::munge(self::TOKEN);

    if ($value) {
      $row->format('text/plain')->tokens($prefix, $field, $value);
      $row->format('text/html')->tokens($prefix, $field, $value);
    }
  }

  private function geTokens(): array {
    return [
      'id' => 'Credit Note Id',
      'contact_id' => 'Credit Note Contact Id',
      'cn_number' => 'Credit Note Number',
      'date' => 'Credit Note Date',
      'reference' => 'Credit Note Reference',
      'currency' => 'Credit Note Currency',
      'description' => 'Credit Note Description',
      'comment' => 'Credit Note Comment',
      'subtotal' => 'Credit Note Sub Total',
      'sales_tax' => 'Credit Note Sales Tax',
      'total_credit' => 'Credit Note Total Credit',
      'remaining_credit' => 'Credit Note Remaining Total',
      'allocated_credit' => 'Credit Note Allocated Total',
      'allocated_invoice' => 'Credit Note Invoice Allocations',
      'allocated_manual_refund' => 'Credit Note Manual Refunds',
      'allocated_online_refund' => 'Credit Note Online Refunds',
      'owner_organization_id' => 'Credit Note Owner Organization ID',
      'owner_organization_name' => 'Credit Note Owner Organization Name',
    ];
  }

  private function isMessageTemplatePage() {
    $store = NULL;
    $activePath = CRM_Utils_Request::retrieve('q', 'String', $store, FALSE, NULL, 'GET');
    return $activePath == "civicrm/admin/messageTemplates/add";
  }

}
