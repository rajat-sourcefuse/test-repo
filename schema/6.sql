
UPDATE oml_object SET authorization_policy = 'metaAuthPolicy:instructorPatientGetOnly' WHERE name = 'courseSession';
INSERT INTO oml_object(organization_id,name,display_name,parent,authorization_policy,auto_generate_id,auto_generate_method,search_index_config,recursion_level,maintain_cache,allow_fetch_all,encrypt_data,maintain_audit,maintain_history,allow_create,allow_edit,allow_delete,restrict_to_same_org,permission_check_on,cardinality,comments,auto_create,is_internal,is_deleted) values ('','metaOmlScript','OML Scripts','','metaAuthPolicy:meta',true,'','','','',false,false,'',false,false,false,false,false,'this','n','',false,false,false);
INSERT INTO oml_object(organization_id,name,display_name,parent,authorization_policy,auto_generate_id,auto_generate_method,search_index_config,recursion_level,maintain_cache,allow_fetch_all,encrypt_data,maintain_audit,maintain_history,allow_create,allow_edit,allow_delete,restrict_to_same_org,permission_check_on,cardinality,comments,auto_create,is_internal,is_deleted) values ('','systemUser','System User','organization','',true,'','','','',false,false,'ALL',false,false,true,false,false,'this','n','',false,false,false);