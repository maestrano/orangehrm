CREATE TABLE IF NOT EXISTS `mno_id_map` (
  `mno_entity_guid` varchar(255) NOT NULL,
  `mno_entity_name` varchar(255) NOT NULL,
  `app_entity_id` varchar(255) NOT NULL,
  `app_entity_name` varchar(255) NOT NULL,
  `db_timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_flag` int(1) NOT NULL DEFAULT '0'
);

CREATE UNIQUE INDEX mno_id_map_unique_key ON `mno_id_map` (`mno_entity_guid`, `mno_entity_name`, `app_entity_id`, `app_entity_name`);
CREATE INDEX mno_id_map_mno_key ON `mno_id_map` (`mno_entity_guid`, `mno_entity_name`);
CREATE INDEX mno_id_map_app_key ON `mno_id_map` (`app_entity_id`, `app_entity_name`);
