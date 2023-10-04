-- /*******************************************************
-- *
-- * Clean up the existing tables - this section generated from drop.tpl
-- *
-- *******************************************************/

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `financeextras_credit_note_line`;
DROP TABLE IF EXISTS `financeextras_credit_note_allocation`;
DROP TABLE IF EXISTS `financeextras_credit_note`;
DROP TABLE IF EXISTS `financeextras_company`;
DROP TABLE IF EXISTS `financeextras_batch_owner_org`;

SET FOREIGN_KEY_CHECKS=1;
-- /*******************************************************
-- *
-- * Create new tables
-- *
-- *******************************************************/

-- /*******************************************************
-- *
-- * financeextras_credit_note
-- *
-- * Stores credit note for contribution refund or allocations
-- *
-- *******************************************************/
CREATE TABLE `financeextras_credit_note` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique CreditNote ID',
  `contact_id` int unsigned COMMENT 'FK to Contact',
  `owner_organization` int unsigned NOT NULL COMMENT 'FK to Contact',
  `cn_number` varchar(11),
  `date` date COMMENT 'Credit Note date',
  `status_id` int unsigned NOT NULL COMMENT 'One of the values of the financeextras_credit_note_status option group',
  `reference` varchar(11),
  `currency` varchar(3) DEFAULT NULL COMMENT '3 character string, value from config setting or input via user.',
  `description` text NULL COMMENT 'Credit note description',
  `comment` text NULL COMMENT 'Credit note comment',
  `subtotal` decimal(20,2) NULL COMMENT 'Total of all the total price fields',
  `sales_tax` decimal(20,2) NULL COMMENT 'Credit note sales tax total',
  `total_credit` decimal(20,2) NULL COMMENT 'Total value of the credit note',
  PRIMARY KEY (`id`),
  CONSTRAINT FK_financeextras_credit_note_contact_id FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,
  CONSTRAINT FK_financeextras_credit_note_owner_organization FOREIGN KEY (`owner_organization`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE
)
ENGINE=InnoDB;

-- /*******************************************************
-- *
-- * financeextras_credit_note_allocation
-- *
-- * Stores amounts of credit that have been allocated or âusedâ from a credit note.
-- *
-- *******************************************************/
CREATE TABLE `financeextras_credit_note_allocation` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique CreditNoteAllocation ID',
  `credit_note_id` int unsigned COMMENT 'FK to CreditNote',
  `contribution_id` int unsigned COMMENT 'FK to Contribution',
  `type_id` int unsigned NULL DEFAULT NULL COMMENT 'One of the values of the financeextras_credit_note_allocation_type option group',
  `currency` varchar(3) DEFAULT NULL COMMENT '3 character string, value from config setting or input via user.',
  `reference` text,
  `amount` decimal(20,2) NULL COMMENT 'Ammount allocated',
  `date` date COMMENT 'Allocation date',
  `is_reversed` tinyint NOT NULL DEFAULT 0 COMMENT 'Allocation has been deleted by user',
  PRIMARY KEY (`id`),
  CONSTRAINT FK_financeextras_credit_note_allocation_credit_note_id FOREIGN KEY (`credit_note_id`) REFERENCES `financeextras_credit_note`(`id`) ON DELETE CASCADE,
  CONSTRAINT FK_financeextras_credit_note_allocation_contribution_id FOREIGN KEY (`contribution_id`) REFERENCES `civicrm_contribution`(`id`)
)
ENGINE=InnoDB;

-- /*******************************************************
-- *
-- * financeextras_credit_note_line
-- *
-- * Credit note line items
-- *
-- *******************************************************/
CREATE TABLE `financeextras_credit_note_line` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique CreditNoteLine ID',
  `credit_note_id` int unsigned COMMENT 'FK to CreditNote',
  `financial_type_id` int unsigned COMMENT 'FK to CiviCRM Financial Type',
  `description` text NULL COMMENT 'line item description',
  `quantity` decimal(20,2) COMMENT 'Quantity',
  `unit_price` decimal(20,2) COMMENT 'Unit Price',
  `tax_amount` decimal(20,2) COMMENT 'Tax amount for the line item',
  `line_total` decimal(20,2) COMMENT 'Line Total',
  PRIMARY KEY (`id`),
  CONSTRAINT FK_financeextras_credit_note_line_credit_note_id FOREIGN KEY (`credit_note_id`) REFERENCES `financeextras_credit_note`(`id`) ON DELETE CASCADE,
  CONSTRAINT FK_financeextras_credit_note_line_financial_type_id FOREIGN KEY (`financial_type_id`) REFERENCES `civicrm_financial_type`(`id`) ON DELETE SET NULL
)
ENGINE=InnoDB;

-- /*******************************************************
-- *
-- * financeextras_company
-- *
-- * Holds the company (legal entity) information
-- *
-- *******************************************************/
CREATE TABLE `financeextras_company` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Company ID',
  `contact_id` int unsigned COMMENT 'FK to Contact',
  `invoice_template_id` int unsigned COMMENT 'FK to the message template.',
  `invoice_prefix` varchar(11),
  `next_invoice_number` varchar(11),
  `creditnote_template_id` int unsigned COMMENT 'FK to the message template.',
  `creditnote_prefix` varchar(11),
  `next_creditnote_number` varchar(11),
  PRIMARY KEY (`id`),
  CONSTRAINT FK_financeextras_company_contact_id FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,
  CONSTRAINT FK_financeextras_company_invoice_template_id FOREIGN KEY (`invoice_template_id`) REFERENCES `civicrm_msg_template`(`id`) ON DELETE SET NULL,
  CONSTRAINT FK_financeextras_company_creditnote_template_id FOREIGN KEY (`creditnote_template_id`) REFERENCES `civicrm_msg_template`(`id`) ON DELETE SET NULL
)
ENGINE=InnoDB;

-- /*******************************************************
-- *
-- * financeextras_batch_owner_org
-- *
-- * The financial batch owner organisations
-- *
-- *******************************************************/
CREATE TABLE `financeextras_batch_owner_org` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique BatchOwnerOrganisation ID',
  `batch_id` int unsigned COMMENT 'FK to Batch.',
  `owner_org_id` int unsigned COMMENT 'FK to Contact',
  PRIMARY KEY (`id`),
  CONSTRAINT FK_financeextras_batch_owner_org_batch_id FOREIGN KEY (`batch_id`) REFERENCES `civicrm_batch`(`id`) ON DELETE CASCADE,
  CONSTRAINT FK_financeextras_batch_owner_org_owner_org_id FOREIGN KEY (`owner_org_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE
)
ENGINE=InnoDB;
