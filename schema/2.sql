
UPDATE meta_auth_policy SET policy = '{"Deny":{"userObjectType":["systemUser"],"userObjectTypeActions":{"organizationEmployee":["create","update","delete"],"patient":["create","update","delete"]}}}' WHERE id = 'metaAuthPolicy:meta';
UPDATE meta_auth_policy SET policy = '{"Deny":{"userObjectType":["systemUser"],"userProfileTypeActions":{"metaUserType:instructor":["create","update","delete"]}},"Policy":{"profileType":{"organizationEmployee":{"metaUserType:instructor":"instdataaccess"}}}}' WHERE id = 'metaAuthPolicy:instructorGetOnly';
UPDATE meta_auth_policy SET policy = '{"Deny":{"userObjectType":["systemUser"],"userProfileType":{"organizationEmployee":["metaUserType:instructor"]}}}' WHERE id = 'metaAuthPolicy:denyInstructor';
UPDATE meta_auth_policy SET policy = '{"Deny":{"userObjectType":["systemUser"],"userProfileTypeActions":{"metaUserType:instructor":["create","delete"],"metaUserType:user":["create","delete"]}},"Policy":{"profileType":{"organizationEmployee":{"metaUserType:instructor":"instdataaccess"}}}}' WHERE id = 'metaAuthPolicy:OnlyOEAdminCanCreateDelete';
UPDATE meta_auth_policy SET policy = '{"Deny":{"userObjectType":["systemUser"],"userProfileTypeActions":{"metaUserType:instructor":["create","delete"]}},"Policy":{"profileType":{"organizationEmployee":{"metaUserType:instructor":"instdataaccess"}}}}' WHERE id = 'metaAuthPolicy:instructorGetUpdateOnly';
UPDATE meta_auth_policy SET policy = '{"Deny":{"userObjectType":["patient","systemUser"],"userProfileType":{"organizationEmployee":["metaUserType:instructor"]}}}' WHERE id = 'metaAuthPolicy:OEInstructorAndAllPatientDeny';
UPDATE meta_auth_policy SET policy = '{"Deny":{"userObjectType":["systemUser"],"userProfileTypeActions":{"metaUserType:instructor":["create","update","delete"],"metaUserType:user":["create","update","delete"]}},"Policy":{"profileType":{"organizationEmployee":{"metaUserType:instructor":"instdataaccess"}}}}' WHERE id = 'metaAuthPolicy:instructorUserGetOnly';
UPDATE meta_auth_policy SET policy = '{"Deny":{"userObjectType":["patient","systemUser"],"userProfileTypeActions":{"metaUserType:instructor":["create","update","delete"]}},"Policy":{"profileType":{"organizationEmployee":{"metaUserType:instructor":"instdataaccess"}}}}' WHERE id = 'metaAuthPolicy:instructorGetOnlyDenyPatient';
UPDATE meta_auth_policy SET policy = '{"Deny":{"userObjectType":["patient","systemUser"]}}' WHERE id = 'metaAuthPolicy:denyPatient';
UPDATE meta_auth_policy SET policy = '{"Deny":{"userObjectType":["systemUser"],"userObjectTypeActions":{"patient":["create","update","delete"]},"userProfileTypeActions":{"metaUserType:instructor":["create","update","delete"]}},"Policy":{"userType":{"patient":"patientdataaccess"},"profileType":{"organizationEmployee":{"metaUserType:instructor":"instdataaccess"}}}}' WHERE id = 'metaAuthPolicy:instructorPatientGetOnly';
UPDATE meta_auth_policy SET policy = '{"Deny":{"userObjectType":["patient","systemUser"],"userProfileTypeActions":{"metaUserType:instructor":["create","update","delete"],"metaUserType:user":["create","update","delete"]}},"Policy":{"profileType":{"organizationEmployee":{"metaUserType:instructor":"instdataaccess"}}}}' WHERE id = 'metaAuthPolicy:instructorUserGetOnlyDenyPatient';
UPDATE meta_auth_policy SET policy = '{"Deny":{"userObjectType":["systemUser"],"userObjectTypeActions":{"patient":["create","update","delete"]},"userProfileType":{"organizationEmployee":["metaUserType:instructor"]}},"Policy":{"userType":{"patient":"patientdataaccess"}}}' WHERE id = 'metaAuthPolicy:denyInstructorAllowPatientGet';
UPDATE meta_auth_policy SET policy = '{"Deny":{"userObjectType":["systemUser"],"userObjectTypeActions":{"patient":["create","update","delete"]}},"Policy":{"userType":{"patient":"patientdataaccess"}}}' WHERE id = 'metaAuthPolicy:patientGetOnly';
UPDATE meta_auth_policy SET policy = '{"Deny":{"userObjectType":["systemUser"],"userProfileTypeActions":{"metaUserType:instructor":["create","delete"]},"userObjectTypeActions":{"patient":["create","delete"]}},"Policy":{"profileType":{"organizationEmployee":{"metaUserType:instructor":"instdataaccess"},"userType":{"patient":"patientdataaccess"}}}}' WHERE id = 'metaAuthPolicy:instructorPatientGetUpdateOnly';
INSERT INTO meta_auth_policy(policy,created_on_date,created_on_time,created_by,id,tags,organization_id,custom_property,checksum,object_path,is_deleted) values ('{"Deny":{"userObjectType":["patient","systemUser"],"userProfileType":{"organizationEmployee":["metaUserType:instructor"]}}}',NULL,NULL,NULL,'metaAuthPolicy:denyInstructorPatient',NULL,NULL,NULL,NULL,NULL,false);
INSERT INTO meta_auth_policy(policy,created_on_date,created_on_time,created_by,id,tags,organization_id,custom_property,checksum,object_path,is_deleted) values ('"{\"Deny\":{\"userObjectType\":[\"patient\",\"organizationEmployee\"]}"',NULL,NULL,NULL,'metaAuthPolicy:AllowOnlySystem',NULL,NULL,NULL,NULL,NULL,false);