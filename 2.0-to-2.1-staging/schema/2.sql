
INSERT INTO oml_object(organization_id,name,display_name,parent,authorization_policy,auto_generate_id,auto_generate_method,search_index_config,recursion_level,maintain_cache,allow_fetch_all,encrypt_data,maintain_audit,maintain_history,allow_create,allow_edit,allow_delete,restrict_to_same_org,permission_check_on,cardinality,comments,auto_create,is_internal,is_external_syncable,is_deleted) values ('','metaQuestion','Meta Question','','metaAuthPolicy:meta',false,'','','','1',true,false,'',false,false,false,false,false,'this','n','',false,false,false,false);
INSERT INTO oml_object(organization_id,name,display_name,parent,authorization_policy,auto_generate_id,auto_generate_method,search_index_config,recursion_level,maintain_cache,allow_fetch_all,encrypt_data,maintain_audit,maintain_history,allow_create,allow_edit,allow_delete,restrict_to_same_org,permission_check_on,cardinality,comments,auto_create,is_internal,is_external_syncable,is_deleted) values ('','metaAnswer','Meta Answer','metaQuestion','metaAuthPolicy:meta',false,'','','','1',true,false,'',false,false,false,false,false,'parent','n','',false,false,false,false);
INSERT INTO oml_object(organization_id,name,display_name,parent,authorization_policy,auto_generate_id,auto_generate_method,search_index_config,recursion_level,maintain_cache,allow_fetch_all,encrypt_data,maintain_audit,maintain_history,allow_create,allow_edit,allow_delete,restrict_to_same_org,permission_check_on,cardinality,comments,auto_create,is_internal,is_external_syncable,is_deleted) values ('','patientActivationQA','Patient Activation Question Answer','patient','metaAuthPolicy:OEInstructorAndAllPatientDeny',true,'','','','1',true,false,'ALL',false,true,false,false,false,'','','',false,false,false,false);