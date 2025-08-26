-- /*******************************************************
-- *
-- * financeextras_company
-- *
-- * Add company_number, sales_tax_registration_number, display_currency_conversion_for_tax_on_invoices and sales_tax_currency columns.
-- *
-- *******************************************************/
ALTER TABLE `financeextras_company`
    ADD COLUMN `company_number` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN `sales_tax_registration_number` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN `display_currency_conversion_for_tax_on_invoices` TINYINT DEFAULT 0,
    ADD COLUMN `sales_tax_currency` VARCHAR(100) DEFAULT NULL;
