
UPDATE meta_auth_policy SET policy = '{"Deny":{"userProfileTypeActions":{"metaUserType:instructor":["create","update","delete"]}},"Policy":{"profileType":{"organizationEmployee":{"metaUserType:instructor":"instdataaccess"}}}}' WHERE id = 'metaAuthPolicy:instructorGetOnly';
INSERT INTO meta_auth_policy(policy,created_on_date,created_on_time,created_by,id,tags,organization_id,custom_property,checksum,object_path,is_deleted) values ('{"Deny":{"userProfileType":{"organizationEmployee":["metaUserType:instructor"]}}}',NULL,NULL,NULL,'metaAuthPolicy:denyInstructor',NULL,NULL,NULL,NULL,NULL,false);
UPDATE meta_auth_policy SET policy = '{"Deny":{"userProfileTypeActions":{"metaUserType:instructor":["create","delete"],"metaUserType:user":["create","delete"]}},"Policy":{"profileType":{"organizationEmployee":{"metaUserType:instructor":"instdataaccess"}}}}' WHERE id = 'metaAuthPolicy:OnlyOEAdminCanCreateDelete';
UPDATE meta_auth_policy SET policy = '{"Deny":{"userProfileTypeActions":{"metaUserType:instructor":["create","delete"]}},"Policy":{"profileType":{"organizationEmployee":{"metaUserType:instructor":"instdataaccess"}}}}' WHERE id = 'metaAuthPolicy:instructorGetUpdateOnly';
UPDATE meta_auth_policy SET policy = '{"Deny":{"userProfileTypeActions":{"metaUserType:instructor":["create","update","delete"],"metaUserType:user":["create","update","delete"]}},"Policy":{"profileType":{"organizationEmployee":{"metaUserType:instructor":"instdataaccess"}}}}' WHERE id = 'metaAuthPolicy:instructorUserGetOnly';
INSERT INTO meta_auth_policy(policy,created_on_date,created_on_time,created_by,id,tags,organization_id,custom_property,checksum,object_path,is_deleted) values ('{"Deny":{"userObjectType":["patient"],"userProfileTypeActions":{"metaUserType:instructor":["create","update","delete"]}},"Policy":{"profileType":{"organizationEmployee":{"metaUserType:instructor":"instdataaccess"}}}}',NULL,NULL,NULL,'metaAuthPolicy:instructorGetOnlyDenyPatient',NULL,NULL,NULL,NULL,NULL,false);
INSERT INTO meta_auth_policy(policy,created_on_date,created_on_time,created_by,id,tags,organization_id,custom_property,checksum,object_path,is_deleted) values ('{"Deny":{"userObjectType":["patient"]}}',NULL,NULL,NULL,'metaAuthPolicy:denyPatient',NULL,NULL,NULL,NULL,NULL,false);
INSERT INTO meta_auth_policy(policy,created_on_date,created_on_time,created_by,id,tags,organization_id,custom_property,checksum,object_path,is_deleted) values ('{"Deny":{"userObjectTypeActions":{"patient":["create","update","delete"]},"userProfileTypeActions":{"metaUserType:instructor":["create","update","delete"]}},"Policy":{"userType":{"patient":"patientdataaccess"},"profileType":{"organizationEmployee":{"metaUserType:instructor":"instdataaccess"}}}}',NULL,NULL,NULL,'metaAuthPolicy:instructorPatientGetOnly',NULL,NULL,NULL,NULL,NULL,false);
INSERT INTO meta_auth_policy(policy,created_on_date,created_on_time,created_by,id,tags,organization_id,custom_property,checksum,object_path,is_deleted) values ('{"Deny":{"userObjectType":["patient"],"userProfileTypeActions":{"metaUserType:instructor":["create","update","delete"],"metaUserType:user":["create","update","delete"]}},"Policy":{"profileType":{"organizationEmployee":{"metaUserType:instructor":"instdataaccess"}}}}',NULL,NULL,NULL,'metaAuthPolicy:instructorUserGetOnlyDenyPatient',NULL,NULL,NULL,NULL,NULL,false);
INSERT INTO meta_auth_policy(policy,created_on_date,created_on_time,created_by,id,tags,organization_id,custom_property,checksum,object_path,is_deleted) values ('{"Deny":{"userObjectTypeActions":{"patient":["create","update","delete"]},"userProfileType":{"organizationEmployee":["metaUserType:instructor"]}},"Policy":{"userType":{"patient":"patientdataaccess"}}}',NULL,NULL,NULL,'metaAuthPolicy:denyInstructorAllowPatientGet',NULL,NULL,NULL,NULL,NULL,false);
INSERT INTO meta_auth_policy(policy,created_on_date,created_on_time,created_by,id,tags,organization_id,custom_property,checksum,object_path,is_deleted) values ('{"Deny":{"userObjectTypeActions":{"patient":["create","update","delete"]}},"Policy":{"userType":{"patient":"patientdataaccess"}}}',NULL,NULL,NULL,'metaAuthPolicy:patientGetOnly',NULL,NULL,NULL,NULL,NULL,false);
INSERT INTO meta_auth_policy(policy,created_on_date,created_on_time,created_by,id,tags,organization_id,custom_property,checksum,object_path,is_deleted) values ('{"Deny":{"userProfileTypeActions":{"metaUserType:instructor":["create","delete"]},"userObjectTypeActions":{"patient":["create","delete"]}},"Policy":{"profileType":{"organizationEmployee":{"metaUserType:instructor":"instdataaccess"},"userType":{"patient":"patientdataaccess"}}}}',NULL,NULL,NULL,'metaAuthPolicy:instructorPatientGetUpdateOnly',NULL,NULL,NULL,NULL,NULL,false);
DELETE FROM meta_auth_policy WHERE id = 'metaAuthpolicy:denyInstructor';