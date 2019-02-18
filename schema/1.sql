
SET CONSTRAINTS ALL DEFERRED;

CREATE TABLE meta_oml_script (name character varying(128) NULL, authorization_policy character varying(128) NULL DEFAULT '{}', script_type character varying(128) NULL, created_on_date date NULL, created_on_time character varying(128) NULL, created_by character varying(255) NULL, id character varying(128) NOT NULL, tags varchar[] NULL, organization_id character varying(255) NULL, custom_property text NULL, checksum character varying(128) NULL, object_path ltree NULL, is_deleted boolean NULL DEFAULT false);
CREATE TABLE system_user (first_name character varying(35) NULL, last_name character varying(35) NULL, user_type character varying(255) NULL, synap_user_id character varying(255) NULL, created_on_date date NULL, created_on_time character varying(128) NULL, created_by character varying(255) NULL, id character varying(128) NOT NULL, tags varchar[] NULL, organization_id character varying(255) NULL, custom_property text NULL, checksum character varying(128) NULL, object_path ltree NULL, is_deleted boolean NULL DEFAULT false);
ALTER TABLE patient ADD COLUMN associations_only boolean NULL DEFAULT false;
ALTER TABLE patient_medication RENAME COLUMN route TO meta_route character varying(255) NULL;
ALTER TABLE preferred_provider ADD COLUMN email character varying(128) NULL;
CREATE INDEX meta_oml_script_tags ON meta_oml_script USING gin(tags);
CREATE INDEX system_user_tags ON system_user USING gin(tags);
