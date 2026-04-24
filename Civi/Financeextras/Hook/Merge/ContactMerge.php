<?php

namespace Civi\Financeextras\Hook\Merge;

/**
 * Preserves ownership of event and membership contributions when two contact records are merged.
 */
class ContactMerge {

  /**
   * @var string
   *   The hook "type" – we only act when $type is 'sqls'.
   */
  private string $type;

  /**
   * @var array
   *   Reference to the array of SQL statements that CiviCRM will execute.
   */
  private array $sqls;

  /**
   * @var int
   *   The id of the contact that is being kept (merge target).
   */
  private int $mainId;

  /**
   * @var int
   *   The id of the contact that is being deleted (merge source).
   */
  private int $otherId;

  public function __construct(string $type, array &$sqls, int $mainId, int $otherId) {
    $this->type = $type;
    $this->sqls = &$sqls;
    $this->mainId = $mainId;
    $this->otherId = $otherId;
  }

  /**
   * Determines whether the hook should run for the current invocation.
   */
  public static function shouldHandle(string $type, ?int $mainId, ?int $otherId): bool {
    return $type === 'sqls' && !empty($mainId) && !empty($otherId);
  }

  /**
   * Entry point for the hook.
   *
   * Appends UPDATE statements to $sqls that preserves the contribution contact_id.
   */
  public function handle(): void {
    $contributions = array_merge(
      $this->getEventContributionsOwnedByOrganisation(),
      $this->getMembershipContributionsOwnedByOrganisation()
    );

    $this->appendRestoreOwnershipSqls($contributions);
  }

  /**
   * Returns contributions that are linked to events.
   *
   * NOTE ON WHY APIv4 IS NOT USED HERE
   * ----------------------------------
   * The logical query we need is a three-hop join:
   *
   *   civicrm_contribution
   *     -> civicrm_participant_payment (by contribution_id)
   *     -> civicrm_participant         (by participant_id)
   *     -> civicrm_contact             (contribution.contact_id must be an Organisation)
   *
   * But APIv4's query builder cannot express this graph reliably
   *
   * @return array
   *   A list of ['id' => contributionId, 'contact_id' => organisationId].
   */
  private function getEventContributionsOwnedByOrganisation(): array {
    $query = "
      SELECT contribution.id          AS id,
             contribution.contact_id  AS contact_id
        FROM civicrm_contribution         contribution
  INNER JOIN civicrm_participant_payment  pp          ON pp.contribution_id  = contribution.id
  INNER JOIN civicrm_participant          participant ON participant.id      = pp.participant_id
  INNER JOIN civicrm_contact              org         ON org.id              = contribution.contact_id
                                                    AND org.contact_type   = 'Organization'
       WHERE participant.contact_id = %1
         AND contribution.contact_id <> %1
    ";

    return $this->fetchContributionRows($query);
  }

  /**
   * Returns contributions that are linked to memberships.
   *
   * @return array
   *   A list of ['id' => contributionId, 'contact_id' => organisationId].
   */
  private function getMembershipContributionsOwnedByOrganisation(): array {
    $query = "
      SELECT contribution.id          AS id,
             contribution.contact_id  AS contact_id
        FROM civicrm_contribution         contribution
  INNER JOIN civicrm_membership_payment   mp          ON mp.contribution_id  = contribution.id
  INNER JOIN civicrm_membership           membership  ON membership.id       = mp.membership_id
  INNER JOIN civicrm_contact              org         ON org.id              = contribution.contact_id
                                                     AND org.contact_type   = 'Organization'
       WHERE membership.contact_id = %1
         AND contribution.contact_id <> %1
    ";

    return $this->fetchContributionRows($query);
  }

  /**
   * @param string $query
   * @return array
   */
  private function fetchContributionRows(string $query): array {
    $rows = [];
    $dao = \CRM_Core_DAO::executeQuery($query, [
      1 => [$this->otherId, 'Integer'],
    ]);

    while ($dao->fetch()) {
      $rows[] = [
        'id' => $dao->id,
        'contact_id' => $dao->contact_id,
      ];
    }

    return $rows;
  }

  /**
   * Appends UPDATE statements to $sqls that reset each contribution's contact_id.
   *
   * @param array $contributions
   */
  private function appendRestoreOwnershipSqls(array $contributions): void {
    foreach ($contributions as $contribution) {
      $contributionId = (int) ($contribution['id'] ?? 0);
      $organisationId = (int) ($contribution['contact_id'] ?? 0);

      if ($contributionId <= 0 || $organisationId <= 0) {
        continue;
      }

      $this->sqls[] = "UPDATE civicrm_contribution SET contact_id = {$organisationId} WHERE id = {$contributionId}";
    }
  }

}
