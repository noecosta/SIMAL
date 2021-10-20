# Rechte für `simal`@`localhost`

GRANT USAGE ON *.* TO `simal`@`localhost` IDENTIFIED BY PASSWORD '*F399A53DEEE0DC8172DCE03755DC2EF26C78FB98';

GRANT SELECT, INSERT, UPDATE ON `simal`.* TO `simal`@`localhost`;

GRANT DELETE ON `simal`.`alert_region` TO `simal`@`localhost`;