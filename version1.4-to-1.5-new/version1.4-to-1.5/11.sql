
ALTER TABLE meta_referral ADD CONSTRAINT unik_meta_referral_id unique (id);
ALTER TABLE meta_referral ADD CONSTRAINT pk_meta_referral_id primary  KEY (id);
ALTER TABLE patient ADD CONSTRAINT fk_19f5d8f446d987148b32bb31b618529a foreign  KEY (referral) REFERENCES meta_referral(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE meta_referral ADD CONSTRAINT fk_86e0436ba1cba247b630e633ff193890 foreign  KEY (organization_id) REFERENCES organization(id) DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE meta_referral ADD CONSTRAINT fk_e4c1aa38808bb9db7f0db008b8540194 foreign  KEY (created_by) REFERENCES synap_user(id) DEFERRABLE INITIALLY IMMEDIATE;