
SET CONSTRAINTS ALL DEFERRED;
ALTER TABLE patient_address ;
ALTER TABLE course_session ALTER COLUMN repeat_type TYPE character varying(128);
ALTER TABLE course_session ALTER COLUMN ends_on_type TYPE character varying(128);


UPDATE meta_oml_script SET name = 'RecurrenceSession' WHERE id = 'MetaOmlScript:074';
UPDATE meta_oml_script SET name = 'RecurrenceSessionDelete' WHERE id = 'MetaOmlScript:075';
INSERT INTO meta_oml_script(name,authorization_policy,script_type,created_on_date,created_on_time,created_by,id,tags,organization_id,custom_property,checksum,object_path,is_deleted) values ('RecurrenceSessionUpdate','metaAuthPolicy:denyInstructorPatient','SUC',NULL,NULL,NULL,'MetaOmlScript:076',NULL,NULL,NULL,NULL,NULL,false);
INSERT INTO meta_oml_script(name,authorization_policy,script_type,created_on_date,created_on_time,created_by,id,tags,organization_id,custom_property,checksum,object_path,is_deleted) values ('UpdateSyncedDataStatus','metaAuthPolicy:AllowOnlySystem','SUC',NULL,NULL,NULL,'MetaOmlScript:078',NULL,NULL,NULL,NULL,NULL,false);



UPDATE oml_property SET allow_edit = true WHERE object = 'courseSession' AND organization_id = '' AND property = 'recurrence';
UPDATE oml_property SET allow_edit = true WHERE object = 'courseSession' AND organization_id = '' AND property = 'repeatAfter';
UPDATE oml_property SET data_type = 'string',allow_edit = true WHERE object = 'courseSession' AND organization_id = '' AND property = 'repeatType';
UPDATE oml_property SET allow_edit = true WHERE object = 'courseSession' AND organization_id = '' AND property = 'repeatProperty';
UPDATE oml_property SET data_type = 'string',allow_edit = true WHERE object = 'courseSession' AND organization_id = '' AND property = 'endsOnType';
UPDATE oml_property SET allow_edit = true WHERE object = 'courseSession' AND organization_id = '' AND property = 'endsOnProperty';
UPDATE oml_property SET allow_edit = true WHERE object = 'courseSession' AND organization_id = '' AND property = 'node';




