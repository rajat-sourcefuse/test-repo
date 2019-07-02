
ALTER TABLE course_category ADD CONSTRAINT unik_course_category_id unique (id);
ALTER TABLE course_category ADD CONSTRAINT pk_course_category_id primary  KEY (id);
ALTER TABLE course ADD CONSTRAINT fk_a21a130087fa21084083475299df085e foreign  KEY (category) REFERENCES course_category(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE course_category ADD CONSTRAINT fk_a0c3b59515206be675b71276724bd4b0 foreign  KEY (organization_id) REFERENCES organization(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE course_category ADD CONSTRAINT fk_bea3ac067e273e346d560dfd20c6fc20 foreign  KEY (created_by) REFERENCES synap_user(id) DEFERRABLE INITIALLY IMMEDIATE;