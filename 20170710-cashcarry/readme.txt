DB:
DROP TABLE IF EXISTS tilldaily;
CREATE TABLE tilldaily (
  id int(11) NOT NULL auto_increment,
  till_initial decimal(15,4),
  cash_sys decimal(15,4),
  card_sys decimal(15,4),
  totalcash decimal(15,4),
  card_received decimal(15,4),
  cash_1 decimal(15,4),
  card_1 decimal(15,4),
  cash_2 decimal(15,4),
  card_2 decimal(15,4),
  handin decimal(15,4),
  flow decimal(15,4),
  date datetime,
  PRIMARY KEY (id)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci;

files changes:
/admin/cashcarry_report.php
/admin/includes/javascript/cashcarry/cashcarry.php
/admin/includes/database_tables.php