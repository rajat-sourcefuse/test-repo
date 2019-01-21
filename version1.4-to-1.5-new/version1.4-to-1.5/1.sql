
SET CONSTRAINTS ALL DEFERRED;
DROP TABLE patient_social_needs_on_behalf_of_client_to CASCADE;
ALTER TABLE patient_social_needs DROP COLUMN related_to_transportation;
CREATE TABLE meta_referral (value character varying(128) NULL, description_required boolean NULL DEFAULT false, value_order integer NULL, created_on_date date NULL, created_on_time character varying(128) NULL, created_by character varying(255) NULL, id character varying(128) NOT NULL, tags varchar[] NULL, organization_id character varying(255) NULL, custom_property text NULL, checksum character varying(128) NULL, object_path ltree NULL, is_deleted boolean NULL DEFAULT false);
CREATE TABLE patient_associated_patient (associated_patient character varying(255) NULL, patient_id character varying(255) NULL, checksum character varying(128) NULL);
ALTER TABLE patient ADD COLUMN referral character varying(255) NULL;
ALTER TABLE patient_social_needs ADD COLUMN on_behalf_of_client_to boolean NULL DEFAULT false;
ALTER TABLE patient_social_needs ADD COLUMN related_to_transition boolean NULL DEFAULT false;
ALTER TABLE patient_social_needs ADD COLUMN hospital boolean NULL DEFAULT false;
ALTER TABLE patient_social_needs ADD COLUMN ada_fair_housing_education boolean NULL DEFAULT false;
ALTER TABLE patient_social_needs ADD COLUMN assisted_obtaining_fair_housing_counsel boolean NULL DEFAULT false;
ALTER TABLE patient_social_needs ADD COLUMN voter_registration_assistance boolean NULL DEFAULT false;
ALTER TABLE work_list ADD COLUMN social_need_name character varying(128) NULL;
CREATE INDEX meta_referral_tags ON meta_referral USING gin(tags);