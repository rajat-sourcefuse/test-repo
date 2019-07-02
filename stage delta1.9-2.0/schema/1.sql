
SET CONSTRAINTS ALL DEFERRED;
CREATE TABLE course_category (name character varying(128) NULL, created_on_date date NULL, created_on_time character varying(128) NULL, created_by character varying(255) NULL, id character varying(128) NOT NULL, tags varchar[] NULL, organization_id character varying(255) NULL, custom_property text NULL, checksum character varying(128) NULL, object_path ltree NULL, is_deleted boolean NULL DEFAULT false);
ALTER TABLE course ADD COLUMN category character varying(255) NULL;
CREATE INDEX course_category_tags ON course_category USING gin(tags);