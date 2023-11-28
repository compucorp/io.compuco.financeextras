<?php

namespace Civi\Financeextras\WorkflowMessage;

use Civi\WorkflowMessage\GenericWorkflowMessage;

/**
 * Invoice for Credit Notes.
 *
 * @support template-only
 *
 * @method ?string getTaxTerm()
 * @method $this setTaxTerm(?string $taxTerm)
 * @method ?string getDomainLogo()
 * @method $this setDomainLogo(?string $logo)
 * @method ?string getDomainName()
 * @method $this setDomainName(?string $domainName)
 * @method ?array getCreditNote()
 * @method $this setCreditNote(?array $creditNote)
 * @method ?int getCreditNoteId()
 * @method $this setCreditNoteId(?int $creditNoteId)
 * @method ?array getDomainLocation()
 * @method $this setDomainLocation(?array $domainLocation)
 * @method ?array getContactLocation()
 * @method $this setContactLocation(?array $contactLocation)
 */
class CreditNoteInvoice extends GenericWorkflowMessage {

  public const WORKFLOW = 'fe_credit_note_invoice';

  /**
   * Credit Note object.
   *
   * @var array
   * @scope tplParams as credit_note
   */
  protected $creditNote;

  /**
   * Domain location information.
   *
   * @var array
   * @scope tplParams as domain_location
   */
  protected $domainLocation;

  /**
   * Contact location information.
   *
   * @var array
   * @scope tplParams as contact_location
   */
  protected $contactLocation;

  /**
   * Domain Orgnanisation Image URL.
   *
   * @var array
   * @scope tplParams as domain_logo
   */
  protected $domainLogo;

  /**
   * Credit Note ID.
   *
   * @var int
   * @scope tokenContext
   */
  protected $creditNoteId;

  /**
   * Domain Name.
   *
   * @var string
   * @scope tplParams as domain_name
   */
  protected $domainName;

  /**
   * Base URL.
   *
   * @var string
   * @scope tplParams as base_url
   */
  protected $baseURL;

  /**
   * Tax Term.
   *
   * @var string
   * @scope tplParams as tax_term
   */
  protected $taxTerm;

}
