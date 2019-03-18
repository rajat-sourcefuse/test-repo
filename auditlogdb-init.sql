CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

CREATE TABLE public.api_logs
(
    id uuid NOT NULL DEFAULT uuid_generate_v4(),
    input json,
    output json,
    url text COLLATE pg_catalog."default",
    request_method character varying(30) COLLATE pg_catalog."default",
    ip text COLLATE pg_catalog."default",
    header json,
    session_code text COLLATE pg_catalog."default",
    created timestamp(6) without time zone,
    CONSTRAINT logs_pkey PRIMARY KEY (id)
)
WITH (
    OIDS = FALSE
);

CREATE TABLE public.cruds
(
    id uuid NOT NULL DEFAULT uuid_generate_v4(),
    session_code text COLLATE pg_catalog."default",
    type character varying(150) COLLATE pg_catalog."default",
    ip character varying COLLATE pg_catalog."default",
    processed boolean,
    update_operations json,
    checksum character varying(255) COLLATE pg_catalog."default",
    actedonsubject json,
    created timestamp(6) without time zone,
    actedby jsonb,
    encounterid character varying COLLATE pg_catalog."default",
    statename character varying COLLATE pg_catalog."default",
    subjectid character varying COLLATE pg_catalog."default",
    actedbyid character varying COLLATE pg_catalog."default",
    organization json,
    input_data jsonb,
    output_objects jsonb,
    subject_type character varying COLLATE pg_catalog."default",
    CONSTRAINT cruds_pkey PRIMARY KEY (id)
)
WITH (
    OIDS = FALSE
)
