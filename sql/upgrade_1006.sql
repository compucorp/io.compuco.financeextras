-- Add overpayment_financial_type_id column to financeextras_company
ALTER TABLE `financeextras_company`
ADD COLUMN `overpayment_financial_type_id` INT UNSIGNED NULL
COMMENT 'Financial type to use for overpayment credit notes'
AFTER `receivable_payment_method`;

ALTER TABLE `financeextras_company`
ADD CONSTRAINT `FK_financeextras_company_overpayment_financial_type_id`
FOREIGN KEY (`overpayment_financial_type_id`)
REFERENCES `civicrm_financial_type`(`id`)
ON DELETE SET NULL;
