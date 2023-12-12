<?php

namespace Civi\Financeextras\Token;

use Civi\Token\Event\TokenValueEvent;
use Civi\Token\AbstractTokenSubscriber;
use Civi\Token\TokenRow;

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

    return is_array($creditNoteId) && count($creditNoteId) === 1;
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

    try {
      if (is_array($creditNoteId) && count($creditNoteId) === 1) {
        $creditNoteId = $creditNoteId[0];
        $creditNote = CRM_Financeextras_BAO_CreditNote::findById($creditNoteId);

        if (empty($creditNote)) {
          return $resolvedTokens;
        }

        $resolvedTokens = array_merge($this->geTokens(), (array) $creditNote);
      }
    }
    catch (Exception $e) {
      CRM_Core_Session::setStatus('Error resolving credit note tokens');
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
    $value = CRM_Utils_Array::value($field, $prefetch);
    $prefix = CRM_Utils_String::munge(self::TOKEN);

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
    ];
  }

}
