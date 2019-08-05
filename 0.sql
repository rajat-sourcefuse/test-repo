
SET CONSTRAINTS ALL DEFERRED;
ALTER TABLE patient_vital ADD COLUMN reference text NULL;
ALTER TABLE course_session ADD COLUMN recurrence boolean NULL DEFAULT false;
ALTER TABLE course_session ADD COLUMN repeat_after numeric NULL;
ALTER TABLE course_session ADD COLUMN repeat_type numeric NULL;
ALTER TABLE course_session ADD COLUMN repeat_property text NULL;
ALTER TABLE course_session ADD COLUMN ends_on_type numeric NULL;
ALTER TABLE course_session ADD COLUMN ends_on_property text NULL;
ALTER TABLE course_session ADD COLUMN node character varying(255) NULL;
ALTER TABLE patient_address ;



INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','courseSession','recurrence','Recurrence Flag','bool','','[]','[]',false,'[]',false,'0',false,false,false,false,false,'Boolen flag for determine wherter recurrence is used',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','courseSession','repeatAfter','Repat After','number','','[]','[]',false,'[]',false,'',false,false,false,false,false,'Number which show after how much numbers it will repeat the session',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','courseSession','repeatType','Repat How','number','','[]','[]',false,'[]',false,'',false,false,false,false,false,'ENUM {day, month, week, year}',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','courseSession','repeatProperty','Repat Property','json','','[]','[]',false,'[]',false,'',false,false,false,false,false,'Either weekdays or month
{
 week: [“sun”, “mon”, “tue, “wed”, “thu”, “fri”, “sat”],
 month: “first monday” | “second monday” | “third monday” | “fourth monday” | “last monday” | “on day 9”
}',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','courseSession','endsOnType','End On','number','','[]','[]',false,'[]',false,'',false,false,false,false,false,'ENUM {never, date, occurrence}',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','courseSession','endsOnProperty','End on Property','json','','[]','[]',false,'[]',false,'',false,false,false,false,false,'Only one value is valid, either “date” or “after”
{
 date: “” // date string
 occurrence: 0 // positive integer
}',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','courseSession','node','Parent Node','refer','','{"object":"courseSession"}','[]',false,'[]',false,'',false,false,false,false,false,'ID of parent session from which it restarted',false);
UPDATE oml_property SET display_name = 'Blood sugar level ' WHERE object = 'patientVital' AND organization_id = '' AND property = 'a1c';
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','patientVital','reference','Reference','string','','{"maximLength":"-1"}','[]',false,'[]',false,'',false,false,false,true,false,'',false);


INSERT INTO meta_oml_script(name,authorization_policy,script_type,created_on_date,created_on_time,created_by,id,tags,organization_id,custom_property,checksum,object_path,is_deleted) values ('RecurrenceSessionUpdate','metaAuthPolicy:denyInstructorPatient','SUC',NULL,NULL,NULL,'MetaOmlScript:074',NULL,NULL,NULL,NULL,NULL,false);
INSERT INTO meta_oml_script(name,authorization_policy,script_type,created_on_date,created_on_time,created_by,id,tags,organization_id,custom_property,checksum,object_path,is_deleted) values ('RecurrenceSession','metaAuthPolicy:denyInstructorPatient','SUC',NULL,NULL,NULL,'MetaOmlScript:075',NULL,NULL,NULL,NULL,NULL,false);



ALTER TABLE course_session ADD CONSTRAINT fk_d4a948ea9f11466e13c57a54178745ef foreign  KEY (node) REFERENCES course_session(id) DEFERRABLE INITIALLY IMMEDIATE;
