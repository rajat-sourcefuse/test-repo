
ALTER TABLE meta_question ADD CONSTRAINT unik_meta_question_id unique (id);
ALTER TABLE meta_answer ADD CONSTRAINT unik_meta_answer_id unique (id);
ALTER TABLE patient_activation_q_a ADD CONSTRAINT unik_patient_activation_q_a_id unique (id);
ALTER TABLE meta_question ADD CONSTRAINT pk_meta_question_id primary  KEY (id);
ALTER TABLE meta_answer ADD CONSTRAINT pk_meta_answer_id primary  KEY (id);
ALTER TABLE patient_activation_q_a ADD CONSTRAINT pk_patient_activation_q_a_id primary  KEY (id);
ALTER TABLE meta_question ADD CONSTRAINT fk_cb0c7b57380295e0c8bc539484ff01e0 foreign  KEY (organization_id) REFERENCES organization(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE meta_question ADD CONSTRAINT fk_7b52daa45c02c10f24bcc6cfb5caabc3 foreign  KEY (created_by) REFERENCES synap_user(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE meta_answer ADD CONSTRAINT fk_4e3b0e9c8dbd1f79badc2afbe0afccde foreign  KEY (created_by) REFERENCES synap_user(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE meta_answer ADD CONSTRAINT fk_0c5d1816722250108902175955376fea foreign  KEY (meta_question_id) REFERENCES meta_question(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE meta_answer ADD CONSTRAINT fk_ae7ae93d511b98e2c29db4f672b7896c foreign  KEY (organization_id) REFERENCES organization(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE patient_activation_q_a ADD CONSTRAINT fk_feeb91dded2c89421f42a4460c565e0a foreign  KEY (patient_id) REFERENCES patient(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE patient_activation_q_a ADD CONSTRAINT fk_439dce170d59119de32ae24881e69058 foreign  KEY (answer_id) REFERENCES meta_answer(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE patient_activation_q_a ADD CONSTRAINT fk_73c3d0fefb65fcd5f52a4a882701b011 foreign  KEY (question_id) REFERENCES meta_question(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE patient_activation_q_a ADD CONSTRAINT fk_cb7b09cb870a0a37deaf16bca60db745 foreign  KEY (created_by) REFERENCES synap_user(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE patient_activation_q_a ADD CONSTRAINT fk_3534152f56f50392207a574f651ddcf6 foreign  KEY (organization_id) REFERENCES organization(id) DEFERRABLE INITIALLY IMMEDIATE;