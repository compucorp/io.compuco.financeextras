-- Add PaymentAttempt table for tracking payment attempts across all processors

CREATE TABLE IF NOT EXISTS `financeextras_payment_attempt` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
  `contribution_id` int unsigned NOT NULL COMMENT 'FK to Contribution',
  `contact_id` int unsigned NULL COMMENT 'FK to Contact (donor)',
  `payment_processor_id` int unsigned NULL COMMENT 'FK to Payment Processor',
  `processor_type` varchar(50) NOT NULL COMMENT 'Processor type: ''stripe'', ''gocardless'', ''itas'', etc.',
  `processor_session_id` varchar(255) COMMENT 'Processor session ID (cs_... for Stripe, mandate_... for GoCardless)',
  `processor_payment_id` varchar(255) COMMENT 'Processor payment ID (pi_... for Stripe, payment_... for GoCardless)',
  `status` varchar(25) NOT NULL DEFAULT 'pending' COMMENT 'Attempt status: pending, completed, failed, cancelled',
  `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When attempt was created',
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last updated',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `index_contribution_id`(contribution_id),
  INDEX `index_processor_type`(processor_type),
  INDEX `index_processor_session`(processor_session_id, processor_type),
  INDEX `index_processor_payment`(processor_payment_id, processor_type),
  CONSTRAINT FK_financeextras_payment_attempt_contribution_id FOREIGN KEY (`contribution_id`) REFERENCES `civicrm_contribution`(`id`) ON DELETE CASCADE,
  CONSTRAINT FK_financeextras_payment_attempt_contact_id FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL,
  CONSTRAINT FK_financeextras_payment_attempt_payment_processor_id FOREIGN KEY (`payment_processor_id`) REFERENCES `civicrm_payment_processor`(`id`) ON DELETE SET NULL)
ENGINE=InnoDB;