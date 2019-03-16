
UPDATE oml_property SET data_attributes = '{"maxValue":"registrantLimit"}' WHERE object = 'course' AND organization_id = '' AND property = 'minRegistrantLimit';
UPDATE oml_property SET data_attributes = '{"minValue":"minRegistrantLimit"}' WHERE object = 'course' AND organization_id = '' AND property = 'registrantLimit';
UPDATE oml_property SET data_attributes = '{"format":"time","minValue":"startTime"}' WHERE object = 'courseSession' AND organization_id = '' AND property = 'endTime';
UPDATE oml_property SET data_attributes = '{"format":"date","minValue":"startDate"}' WHERE object = 'housingUpgrade' AND organization_id = '' AND property = 'completionDate';
UPDATE oml_property SET data_attributes = '{"format":"date","maxValue":"locationEndDate"}' WHERE object = 'organizationDivision' AND organization_id = '' AND property = 'locationStartDate';
UPDATE oml_property SET data_attributes = '{"format":"date","minValue":"locationStartDate"}' WHERE object = 'organizationDivision' AND organization_id = '' AND property = 'locationEndDate';
UPDATE oml_property SET display_when_referred = true WHERE object = 'patient' AND organization_id = '' AND property = 'image';
UPDATE oml_property SET data_attributes = '{"format":"date","minValue":"admissionDate"}' WHERE object = 'patient' AND organization_id = '' AND property = 'dischargeDate';
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','patient','associationsOnly','Associtaions Only','bool','','[]','[]',false,'[]',false,'0',false,false,false,true,false,'',false);
UPDATE oml_property SET data_attributes = '{"format":"date","minValue":"admissionDate"}' WHERE object = 'patientAdmission' AND organization_id = '' AND property = 'dischargeDate';
UPDATE oml_property SET data_attributes = '{"format":"date","maxValue":"resolutionDate"}' WHERE object = 'patientDiagnosis' AND organization_id = '' AND property = 'onsetDate';
UPDATE oml_property SET data_attributes = '{"format":"date","minValue":"onsetDate"}' WHERE object = 'patientDiagnosis' AND organization_id = '' AND property = 'resolutionDate';
DELETE FROM oml_property WHERE object = 'patientMedication' AND organization_id = '' AND property = 'route';
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','patientMedication','metaRoute','Route','meta','','{"object":"metaMedicationDeliveryRoute"}','[]',false,'[]',false,'',true,true,false,true,false,'',false);
UPDATE oml_property SET data_attributes = '{"format":"date","minValue":"startDate"}' WHERE object = 'patientMedication' AND organization_id = '' AND property = 'endDate';
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','preferredProvider','email','Email','string','','{"format":"email"}','[]',false,'[]',false,'',true,false,false,true,false,'',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','metaOmlScript','name','Name','string','','[]','[]',false,'[]',false,'',false,true,false,false,true,'',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','metaOmlScript','authorizationPolicy','Authorization Policy','string','','[]','[]',false,'[]',false,'{}',false,false,false,false,false,'',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','metaOmlScript','scriptType','Script Type','string','','[]','[]',false,'[]',false,'',false,false,false,false,false,'',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','metaOmlScript','createdOnDate','Created On Date','string','[]','{"format":"date"}','[]',false,'[]',false,'',false,false,false,false,false,'Auto added field from syncronizer',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','metaOmlScript','createdOnTime','Created On Time','string','[]','{"format":"time"}','[]',false,'[]',false,'',false,false,false,false,false,'Auto added field from syncronizer',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','metaOmlScript','createdBy','Created By','refer','[]','{"object":"synapUser"}','[]',false,'[]',false,'',false,false,false,true,false,'Auto added field from syncronizer',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','metaOmlScript','id','Id','string','[]','[]','[]',false,'[]',false,'',false,false,false,false,true,'Auto added field from syncronizer',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','metaOmlScript','tags','Tags','arrays','[]','[]','[]',false,'[]',false,'',false,false,false,true,false,'Auto added field from syncronizer',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','metaOmlScript','organizationId','Organization Id','refer','[]','{"object":"organization"}','[]',false,'[]',false,NULL,false,false,false,false,false,'Auto added field from syncronizer',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','metaOmlScript','customProperty','custom Property','json','[]','[]','[]',false,'[]',false,'',false,false,false,false,false,'Auto added field from syncronizer',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','metaOmlScript','checksum','Checksum','string','[]','[]','[]',false,'[]',false,'',false,false,false,false,false,'Auto added field from syncronizer',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','metaOmlScript','objectPath','Object Path','string','[]','{"format":"ltree"}','[]',false,'[]',false,'',false,false,false,false,false,'Auto added field from syncronizer',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','systemUser','firstName','First Name','string','','{"maximLength":"35"}','[]',false,'[]',false,'',true,true,false,true,false,'',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','systemUser','lastName','Last Name','string','','{"maximLength":"35"}','[]',false,'[]',false,'',true,true,false,true,false,'',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','systemUser','userType','User Type','meta','','{"object":"metaUserType"}','[]',false,'[]',false,'',true,true,false,true,false,'',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','systemUser','synapUserId','Aperio User ID','refer','','{"object":"synapUser"}','[]',false,'[]',false,'',true,false,false,false,false,'',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','systemUser','createdOnDate','Created On Date','string','[]','{"format":"date"}','[]',false,'[]',false,'',false,false,false,false,false,'Auto added field from syncronizer',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','systemUser','createdOnTime','Created On Time','string','[]','{"format":"time"}','[]',false,'[]',false,'',false,false,false,false,false,'Auto added field from syncronizer',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','systemUser','createdBy','Created By','refer','[]','{"object":"synapUser"}','[]',false,'[]',false,'',false,false,false,true,false,'Auto added field from syncronizer',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','systemUser','id','Id','string','[]','[]','[]',false,'[]',false,'',false,false,false,false,true,'Auto added field from syncronizer',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','systemUser','tags','Tags','arrays','[]','[]','[]',false,'[]',false,'',false,false,false,true,false,'Auto added field from syncronizer',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','systemUser','organizationId','Organization Id','refer','[]','{"object":"organization"}','[]',false,'[]',false,NULL,false,false,false,false,false,'Auto added field from syncronizer',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','systemUser','customProperty','custom Property','json','[]','[]','[]',false,'[]',false,'',false,false,false,false,false,'Auto added field from syncronizer',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','systemUser','checksum','Checksum','string','[]','[]','[]',false,'[]',false,'',false,false,false,false,false,'Auto added field from syncronizer',false);
INSERT INTO oml_property(organization_id,object,property,display_name,data_type,search_config,data_attributes,dependency,is_calculated,calculation_formula,allow_multiple_values,default_value,display_when_referred,required,property_permissions,allow_edit,unique_index,comments,is_deleted) values ('','systemUser','objectPath','Object Path','string','[]','{"format":"ltree"}','[]',false,'[]',false,'',false,false,false,false,false,'Auto added field from syncronizer',false);