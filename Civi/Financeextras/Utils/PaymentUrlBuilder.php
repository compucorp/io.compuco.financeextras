<?php

namespace Civi\Financeextras\Utils;

/**
 * Utility class for building payment processor redirect URLs
 *
 * Provides generic URL building for success/cancel redirects that works
 * with Stripe, GoCardless, ITAS, and other payment processors.
 */
class PaymentUrlBuilder {

  /**
   * Build success URL for payment processor redirect
   *
   * Returns user to the thank-you page after successful payment.
   * Supports processor-specific parameters (e.g., session_id, redirect_flow_id).
   *
   * @param int $contributionId CiviCRM contribution ID
   * @param array $params Must include: contributionPageID, qfKey
   * @param array $additionalParams Processor-specific params (e.g., {CHECKOUT_SESSION_ID})
   * @return string Absolute URL to thank-you page with parameters
   */
  public static function buildSuccessUrl(int $contributionId, array $params, array $additionalParams = []): string {
    $queryParams = [
      'id' => $params['contributionPageID'] ?? NULL,
      '_qf_ThankYou_display' => 1,
      'qfKey' => $params['qfKey'] ?? NULL,
    ];

    // Add processor-specific parameters (e.g., session_id for Stripe)
    foreach ($additionalParams as $key => $value) {
      $queryParams[$key] = $value;
    }

    return \CRM_Utils_System::url(
      'civicrm/contribute/transact',
      $queryParams,
    // absolute URL (required for external redirects)
      TRUE,
    // fragment
      NULL,
    // htmlize (FALSE to avoid HTML entity encoding)
      FALSE
    );
  }

  /**
   * Build cancel URL for payment processor redirect
   *
   * Returns user to the contribution page main form with error/cancel message.
   *
   * @param int $contributionId CiviCRM contribution ID
   * @param array $params Must include: contributionPageID, qfKey
   * @return string Absolute URL to contribution page with cancel flag
   */
  public static function buildCancelUrl(int $contributionId, array $params): string {
    $queryParams = [
      'id' => $params['contributionPageID'] ?? NULL,
      '_qf_Main_display' => 1,
      'qfKey' => $params['qfKey'] ?? NULL,
      'cancel' => 1,
    // For logging/debugging
      'contribution_id' => $contributionId,
    ];

    return \CRM_Utils_System::url(
      'civicrm/contribute/transact',
      $queryParams,
      TRUE,
      NULL,
      FALSE
    );
  }

  /**
   * Build error URL for payment processor error redirects
   *
   * Returns user to the contribution page with error message.
   *
   * @param int $contributionId CiviCRM contribution ID
   * @param array $params Must include: contributionPageID, qfKey
   * @param string $errorMessage Optional error message to display
   * @return string Absolute URL to contribution page with error flag
   */
  public static function buildErrorUrl(int $contributionId, array $params, string $errorMessage = ''): string {
    $queryParams = [
      'id' => $params['contributionPageID'] ?? NULL,
      '_qf_Main_display' => 1,
      'qfKey' => $params['qfKey'] ?? NULL,
      'error' => 1,
      'contribution_id' => $contributionId,
    ];

    if (!empty($errorMessage)) {
      $queryParams['error_message'] = $errorMessage;
    }

    return \CRM_Utils_System::url(
      'civicrm/contribute/transact',
      $queryParams,
      TRUE,
      NULL,
      FALSE
    );
  }

  /**
   * Build event registration success URL
   *
   * For processors used with event registration instead of contribution pages.
   *
   * @param int $participantId CiviCRM participant ID
   * @param array $params Must include: eventID, qfKey
   * @param array $additionalParams Processor-specific params
   * @return string Absolute URL to event thank-you page
   */
  public static function buildEventSuccessUrl(int $participantId, array $params, array $additionalParams = []): string {
    $queryParams = [
      'id' => $params['eventID'] ?? NULL,
      '_qf_ThankYou_display' => 1,
      'qfKey' => $params['qfKey'] ?? NULL,
    ];

    foreach ($additionalParams as $key => $value) {
      $queryParams[$key] = $value;
    }

    return \CRM_Utils_System::url(
      'civicrm/event/register',
      $queryParams,
      TRUE,
      NULL,
      FALSE
    );
  }

  /**
   * Build event registration cancel URL
   *
   * @param int $participantId CiviCRM participant ID
   * @param array $params Must include: eventID, qfKey
   * @return string Absolute URL to event registration page with cancel flag
   */
  public static function buildEventCancelUrl(int $participantId, array $params): string {
    $queryParams = [
      'id' => $params['eventID'] ?? NULL,
      '_qf_Register_display' => 1,
      'qfKey' => $params['qfKey'] ?? NULL,
      'cancel' => 1,
      'participant_id' => $participantId,
    ];

    return \CRM_Utils_System::url(
      'civicrm/event/register',
      $queryParams,
      TRUE,
      NULL,
      FALSE
    );
  }

}
