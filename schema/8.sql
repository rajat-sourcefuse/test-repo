
ALTER TABLE meta_oml_script ADD CONSTRAINT unik_meta_oml_script_name unique (name);
ALTER TABLE meta_oml_script ADD CONSTRAINT unik_meta_oml_script_id unique (id);
ALTER TABLE system_user ADD CONSTRAINT unik_system_user_id unique (id);
ALTER TABLE meta_oml_script ADD CONSTRAINT pk_meta_oml_script_id primary  KEY (id);
ALTER TABLE system_user ADD CONSTRAINT pk_system_user_id primary  KEY (id);
ALTER TABLE meta_oml_script ADD CONSTRAINT fk_227fffbc8a33470f454c599d053d0bdd foreign  KEY (created_by) REFERENCES synap_user(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE meta_oml_script ADD CONSTRAINT fk_b18e4af37fdcbea4b7c93b7a64ee61e9 foreign  KEY (organization_id) REFERENCES organization(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE system_user ADD CONSTRAINT fk_31946a1a8161bb7b584df9525e5c7673 foreign  KEY (synap_user_id) REFERENCES synap_user(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE system_user ADD CONSTRAINT fk_c83bb7642221daeadde8166ce5f9e38d foreign  KEY (user_type) REFERENCES meta_user_type(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE system_user ADD CONSTRAINT fk_1d345b89818f461d4d2297595c3283ac foreign  KEY (organization_id) REFERENCES organization(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE system_user ADD CONSTRAINT fk_86aca5e3c3bc79a9bf66f1550c4f2e81 foreign  KEY (created_by) REFERENCES synap_user(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE patient_associated_patient ADD CONSTRAINT fk_98810b35cf9fd7189395840481f5c0f7 foreign  KEY (patient_id) REFERENCES patient(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE patient_associated_patient ADD CONSTRAINT fk_cd9d3b58d0450d8e60dd689b8216d8e8 foreign  KEY (associated_patient) REFERENCES patient(id) DEFERRABLE INITIALLY IMMEDIATE;
