
SET CONSTRAINTS ALL DEFERRED;
CREATE TABLE meta_question (question character varying(128) NULL, sort_order numeric NULL, created_on_date date NULL, created_on_time character varying(128) NULL, created_by character varying(255) NULL, id character varying(128) NOT NULL, tags varchar[] NULL, organization_id character varying(255) NULL, custom_property text NULL, checksum character varying(128) NULL, object_path ltree NULL, is_deleted boolean NULL DEFAULT false);
CREATE TABLE meta_answer (answer character varying(128) NULL, sort_order numeric NULL, created_on_date date NULL, created_on_time character varying(128) NULL, created_by character varying(255) NULL, meta_question_id character varying(255) NULL, id character varying(128) NOT NULL, tags varchar[] NULL, organization_id character varying(255) NULL, custom_property text NULL, checksum character varying(128) NULL, object_path ltree NULL, is_deleted boolean NULL DEFAULT false);
CREATE TABLE patient_activation_q_a (question_id character varying(255) NULL, answer_id character varying(255) NULL, created_on_date date NULL, created_on_time character varying(128) NULL, created_by character varying(255) NULL, patient_id character varying(255) NULL, id character varying(128) NOT NULL, tags varchar[] NULL, organization_id character varying(255) NULL, custom_property text NULL, checksum character varying(128) NULL, object_path ltree NULL, is_deleted boolean NULL DEFAULT false);
ALTER TABLE patient_address ADD COLUMN versioned integer NULL;
ALTER TABLE patient_address ADD COLUMN nod character varying(128) NULL;
ALTER TABLE patient_vital ADD COLUMN a1c numeric NULL;
ALTER TABLE patient_vital ADD COLUMN bcat numeric NULL;
ALTER TABLE patient_vital ADD COLUMN tug_time numeric NULL;
ALTER TABLE patient_vital ADD COLUMN fall_risk_estimate numeric NULL;
ALTER TABLE patient_vital ADD COLUMN frailty_estimate numeric NULL;
CREATE INDEX meta_question_tags ON meta_question USING gin(tags);
CREATE INDEX meta_answer_tags ON meta_answer USING gin(tags);
CREATE INDEX patient_activation_q_a_tags ON patient_activation_q_a USING gin(tags);