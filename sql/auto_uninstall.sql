---- /*******************************************************
-- *
-- * Clean up the existing tables-- *
-- *******************************************************/

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `financeextras_credit_note_line`;
DROP TABLE IF EXISTS `financeextras_credit_note_allocation`;
DROP TABLE IF EXISTS `financeextras_credit_note`;

SET FOREIGN_KEY_CHECKS=1;