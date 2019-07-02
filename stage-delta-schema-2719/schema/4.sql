
UPDATE meta_auth_policy SET policy = '{"Deny":{"userObjectType":["patient","organizationEmployee"]}}' WHERE id = 'metaAuthPolicy:AllowOnlySystem';