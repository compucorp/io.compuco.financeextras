-- /*******************************************************
-- *
-- * Clean up the existing tables - this section generated from drop.tpl
-- *
-- *******************************************************/

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `financeextras_credit_note_line`;
DROP TABLE IF EXISTS `financeextras_credit_note`;

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
  CONSTRAINT FK_financeextras_credit_note_contact_id FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE
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
