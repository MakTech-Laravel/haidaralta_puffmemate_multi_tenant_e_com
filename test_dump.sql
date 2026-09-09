--
-- PostgreSQL database dump
--

\restrict laravelbackupfixedkey123

-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: activity_log; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.activity_log (
    id bigint NOT NULL,
    log_name character varying(255),
    description text NOT NULL,
    subject_type character varying(255),
    subject_id bigint,
    event character varying(255),
    causer_type character varying(255),
    causer_id bigint,
    attribute_changes json,
    properties json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.activity_log OWNER TO postgres;

--
-- Name: activity_log_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.activity_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.activity_log_id_seq OWNER TO postgres;

--
-- Name: activity_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.activity_log_id_seq OWNED BY public.activity_log.id;


--
-- Name: branches; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.branches (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    icon character varying(255),
    address text,
    latitude numeric(10,7),
    longitude numeric(10,7),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.branches OWNER TO postgres;

--
-- Name: branches_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.branches_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.branches_id_seq OWNER TO postgres;

--
-- Name: branches_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.branches_id_seq OWNED BY public.branches.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


ALTER TABLE public.cache OWNER TO postgres;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO postgres;

--
-- Name: database_backup_histories; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.database_backup_histories (
    id bigint NOT NULL,
    type character varying(255) NOT NULL,
    status character varying(255) NOT NULL,
    disk character varying(255) DEFAULT 'local'::character varying NOT NULL,
    path character varying(255),
    filename character varying(255),
    size_bytes bigint,
    triggered_by bigint,
    source_history_id bigint,
    connection character varying(255),
    message text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.database_backup_histories OWNER TO postgres;

--
-- Name: database_backup_histories_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.database_backup_histories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.database_backup_histories_id_seq OWNER TO postgres;

--
-- Name: database_backup_histories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.database_backup_histories_id_seq OWNED BY public.database_backup_histories.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection character varying(255) NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: fcm_device_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.fcm_device_tokens (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    token text NOT NULL,
    device_name character varying(255),
    user_agent character varying(255),
    platform character varying(32) DEFAULT 'web'::character varying NOT NULL,
    last_used_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.fcm_device_tokens OWNER TO postgres;

--
-- Name: fcm_device_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.fcm_device_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.fcm_device_tokens_id_seq OWNER TO postgres;

--
-- Name: fcm_device_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.fcm_device_tokens_id_seq OWNED BY public.fcm_device_tokens.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO postgres;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: model_has_permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.model_has_permissions (
    id bigint NOT NULL,
    permission_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL,
    branch_id bigint
);


ALTER TABLE public.model_has_permissions OWNER TO postgres;

--
-- Name: model_has_permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.model_has_permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.model_has_permissions_id_seq OWNER TO postgres;

--
-- Name: model_has_permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.model_has_permissions_id_seq OWNED BY public.model_has_permissions.id;


--
-- Name: model_has_roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.model_has_roles (
    id bigint NOT NULL,
    role_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL,
    branch_id bigint
);


ALTER TABLE public.model_has_roles OWNER TO postgres;

--
-- Name: model_has_roles_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.model_has_roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.model_has_roles_id_seq OWNER TO postgres;

--
-- Name: model_has_roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.model_has_roles_id_seq OWNED BY public.model_has_roles.id;


--
-- Name: passkeys; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.passkeys (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    credential_id character varying(255) NOT NULL,
    credential json NOT NULL,
    last_used_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.passkeys OWNER TO postgres;

--
-- Name: passkeys_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.passkeys_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.passkeys_id_seq OWNER TO postgres;

--
-- Name: passkeys_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.passkeys_id_seq OWNED BY public.passkeys.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO postgres;

--
-- Name: permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.permissions OWNER TO postgres;

--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.permissions_id_seq OWNER TO postgres;

--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: role_has_permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.role_has_permissions (
    permission_id bigint NOT NULL,
    role_id bigint NOT NULL
);


ALTER TABLE public.role_has_permissions OWNER TO postgres;

--
-- Name: roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    branch_id bigint,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.roles OWNER TO postgres;

--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.roles_id_seq OWNER TO postgres;

--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO postgres;

--
-- Name: telescope_entries; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.telescope_entries (
    sequence bigint NOT NULL,
    uuid uuid NOT NULL,
    batch_id uuid NOT NULL,
    family_hash character varying(255),
    should_display_on_index boolean DEFAULT true NOT NULL,
    type character varying(20) NOT NULL,
    content text NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.telescope_entries OWNER TO postgres;

--
-- Name: telescope_entries_sequence_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.telescope_entries_sequence_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.telescope_entries_sequence_seq OWNER TO postgres;

--
-- Name: telescope_entries_sequence_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.telescope_entries_sequence_seq OWNED BY public.telescope_entries.sequence;


--
-- Name: telescope_entries_tags; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.telescope_entries_tags (
    entry_uuid uuid NOT NULL,
    tag character varying(255) NOT NULL
);


ALTER TABLE public.telescope_entries_tags OWNER TO postgres;

--
-- Name: telescope_monitoring; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.telescope_monitoring (
    tag character varying(255) NOT NULL
);


ALTER TABLE public.telescope_monitoring OWNER TO postgres;

--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    two_factor_secret text,
    two_factor_recovery_codes text,
    two_factor_confirmed_at timestamp(0) without time zone,
    user_type character varying(255) NOT NULL,
    branch_id bigint,
    is_active boolean DEFAULT true NOT NULL
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: activity_log id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.activity_log ALTER COLUMN id SET DEFAULT nextval('public.activity_log_id_seq'::regclass);


--
-- Name: branches id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.branches ALTER COLUMN id SET DEFAULT nextval('public.branches_id_seq'::regclass);


--
-- Name: database_backup_histories id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.database_backup_histories ALTER COLUMN id SET DEFAULT nextval('public.database_backup_histories_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: fcm_device_tokens id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fcm_device_tokens ALTER COLUMN id SET DEFAULT nextval('public.fcm_device_tokens_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: model_has_permissions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.model_has_permissions ALTER COLUMN id SET DEFAULT nextval('public.model_has_permissions_id_seq'::regclass);


--
-- Name: model_has_roles id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.model_has_roles ALTER COLUMN id SET DEFAULT nextval('public.model_has_roles_id_seq'::regclass);


--
-- Name: passkeys id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.passkeys ALTER COLUMN id SET DEFAULT nextval('public.passkeys_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: telescope_entries sequence; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.telescope_entries ALTER COLUMN sequence SET DEFAULT nextval('public.telescope_entries_sequence_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: activity_log; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.activity_log (id, log_name, description, subject_type, subject_id, event, causer_type, causer_id, attribute_changes, properties, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: branches; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.branches (id, name, slug, icon, address, latitude, longitude, is_active, created_at, updated_at) FROM stdin;
1	Downtown	downtown	\N	123 Main Street, Downtown	40.7128000	-74.0060000	t	2026-09-09 11:46:07	2026-09-09 11:46:07
2	Uptown	uptown	\N	456 North Avenue, Uptown	40.7831000	-73.9712000	t	2026-09-09 11:46:07	2026-09-09 11:46:07
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: database_backup_histories; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.database_backup_histories (id, type, status, disk, path, filename, size_bytes, triggered_by, source_history_id, connection, message, created_at, updated_at) FROM stdin;
1	export	failed	local	database-backups/export-2026-09-09-114638-tmbqnsof.sql	export-2026-09-09-114638-tmbqnsof.sql	\N	2	\N	pgsql	pg_dump: error:	2026-09-09 11:46:38	2026-09-09 11:46:38
2	export	failed	local	database-backups/export-2026-09-09-114653-9bwpgy8y.sql	export-2026-09-09-114653-9bwpgy8y.sql	\N	2	\N	pgsql	pg_dump: error:	2026-09-09 11:46:53	2026-09-09 11:46:53
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: fcm_device_tokens; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.fcm_device_tokens (id, user_id, token, device_name, user_agent, platform, last_used_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2024_01_01_000000_create_passkeys_table	1
5	2025_08_14_170933_add_two_factor_columns_to_users_table	1
6	2026_09_01_105201_create_permission_tables	1
7	2026_09_01_105222_create_branches_table	1
8	2026_09_01_105223_add_portal_fields_to_users_table	1
9	2026_09_02_113047_create_telescope_entries_table	1
10	2026_09_09_054547_create_fcm_device_tokens_table	1
11	2026_09_09_094758_create_activity_log_table	1
12	2026_09_09_110323_create_database_backup_histories_table	1
\.


--
-- Data for Name: model_has_permissions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.model_has_permissions (id, permission_id, model_type, model_id, branch_id) FROM stdin;
\.


--
-- Data for Name: model_has_roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.model_has_roles (id, role_id, model_type, model_id, branch_id) FROM stdin;
1	1	App\\Models\\User	1	0
2	2	App\\Models\\User	2	0
3	3	App\\Models\\User	3	0
4	5	App\\Models\\User	4	1
5	6	App\\Models\\User	5	1
6	9	App\\Models\\User	6	2
7	10	App\\Models\\User	7	2
\.


--
-- Data for Name: passkeys; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.passkeys (id, user_id, name, credential_id, credential, last_used_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: permissions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.permissions (id, name, guard_name, created_at, updated_at) FROM stdin;
1	view-dashboard	platform	2026-09-09 11:46:06	2026-09-09 11:46:06
2	manage-users	platform	2026-09-09 11:46:06	2026-09-09 11:46:06
3	manage-roles	platform	2026-09-09 11:46:06	2026-09-09 11:46:06
4	manage-branches	platform	2026-09-09 11:46:06	2026-09-09 11:46:06
5	manage-reports	platform	2026-09-09 11:46:06	2026-09-09 11:46:06
6	manage-database-backups	platform	2026-09-09 11:46:06	2026-09-09 11:46:06
7	view-branches	platform	2026-09-09 11:46:06	2026-09-09 11:46:06
8	create-branches	platform	2026-09-09 11:46:06	2026-09-09 11:46:06
9	edit-branches	platform	2026-09-09 11:46:06	2026-09-09 11:46:06
10	delete-branches	platform	2026-09-09 11:46:06	2026-09-09 11:46:06
11	view-branch-dashboard	branch	2026-09-09 11:46:06	2026-09-09 11:46:06
12	manage-branch-products	branch	2026-09-09 11:46:06	2026-09-09 11:46:06
13	manage-branch-orders	branch	2026-09-09 11:46:06	2026-09-09 11:46:06
14	manage-branch-staff	branch	2026-09-09 11:46:06	2026-09-09 11:46:06
15	manage-branch-roles	branch	2026-09-09 11:46:06	2026-09-09 11:46:06
\.


--
-- Data for Name: role_has_permissions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.role_has_permissions (permission_id, role_id) FROM stdin;
1	2
2	2
3	2
4	2
5	2
6	2
7	2
8	2
9	2
10	2
1	3
2	3
4	3
5	3
7	3
8	3
9	3
10	3
1	4
11	6
12	6
13	6
14	6
15	6
11	7
12	7
13	7
11	8
11	10
12	10
13	10
14	10
15	10
11	11
12	11
13	11
11	12
\.


--
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.roles (id, branch_id, name, guard_name, created_at, updated_at) FROM stdin;
1	0	super-admin	platform	2026-09-09 11:46:06	2026-09-09 11:46:06
2	0	admin	platform	2026-09-09 11:46:06	2026-09-09 11:46:06
3	0	manager	platform	2026-09-09 11:46:07	2026-09-09 11:46:07
4	0	staff	platform	2026-09-09 11:46:07	2026-09-09 11:46:07
5	1	super-admin	branch	2026-09-09 11:46:07	2026-09-09 11:46:07
6	1	admin	branch	2026-09-09 11:46:07	2026-09-09 11:46:07
7	1	manager	branch	2026-09-09 11:46:07	2026-09-09 11:46:07
8	1	staff	branch	2026-09-09 11:46:07	2026-09-09 11:46:07
9	2	super-admin	branch	2026-09-09 11:46:07	2026-09-09 11:46:07
10	2	admin	branch	2026-09-09 11:46:07	2026-09-09 11:46:07
11	2	manager	branch	2026-09-09 11:46:07	2026-09-09 11:46:07
12	2	staff	branch	2026-09-09 11:46:07	2026-09-09 11:46:07
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
\.


--
-- Data for Name: telescope_entries; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.telescope_entries (sequence, uuid, batch_id, family_hash, should_display_on_index, type, content, created_at) FROM stdin;
\.


--
-- Data for Name: telescope_entries_tags; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.telescope_entries_tags (entry_uuid, tag) FROM stdin;
\.


--
-- Data for Name: telescope_monitoring; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.telescope_monitoring (tag) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at, user_type, branch_id, is_active) FROM stdin;
1	Platform Super Admin	superadmin@dev.com	2026-09-09 11:46:07	$2y$12$kc4TbSgLrrx/paGBS1c3cOKkgR2Ofi0JYBxL1i/Tc6O2OhdsQcfS6	\N	2026-09-09 11:46:07	2026-09-09 11:46:07	\N	\N	\N	platform	\N	t
2	Platform Admin	admin@dev.com	2026-09-09 11:46:07	$2y$12$3nJ/Juii7jD8ZISq4epMleEguZwYezY1fURnwzHLMWgkV/YnEjLoa	\N	2026-09-09 11:46:08	2026-09-09 11:46:08	\N	\N	\N	platform	\N	t
3	Platform Manager	manage@dev.com	2026-09-09 11:46:08	$2y$12$MLp.luwnnxCyudZTsHQFzukP/JrUKMunjaOJlsgQ5LjhvAlKWXKc2	\N	2026-09-09 11:46:08	2026-09-09 11:46:08	\N	\N	\N	platform	\N	t
4	Branch 1 Super Admin	branch1.superadmin@dev.com	2026-09-09 11:46:08	$2y$12$S38ctXyYmCjGXbqW77cC0up0rJVb0PJZKAqgT4K8R13lUnitqj3aq	\N	2026-09-09 11:46:08	2026-09-09 11:46:08	\N	\N	\N	branch	1	t
5	Branch 1 Admin	branch1.admin@dev.com	2026-09-09 11:46:08	$2y$12$twt49SaNxnDXSCgVWntvCOGM2sgcThSbo0zBnhYKrKcTUCFjLnV6.	\N	2026-09-09 11:46:08	2026-09-09 11:46:08	\N	\N	\N	branch	1	t
6	Branch 2 Super Admin	branch2.superadmin@dev.com	2026-09-09 11:46:08	$2y$12$YnsM76H9y.q9UM14XUYEp.3k4AryCeSusHMocMe8t9twjGoOwsB6S	\N	2026-09-09 11:46:08	2026-09-09 11:46:08	\N	\N	\N	branch	2	t
7	Branch 2 Admin	branch2.admin@dev.com	2026-09-09 11:46:08	$2y$12$qihnOh4tje.G7HYj5f.8V.KFg6n3l72gm2b1gw8aOJvKV1nACgsiu	\N	2026-09-09 11:46:09	2026-09-09 11:46:09	\N	\N	\N	branch	2	t
8	Driver	driver@dev.com	2026-09-09 11:46:09	$2y$12$Qz0JHj5I2MwoS2AHke5s9etzlWEyfTaDhUhHRV9C/NxOgB/pRC7Ou	\N	2026-09-09 11:46:09	2026-09-09 11:46:09	\N	\N	\N	driver	\N	t
9	Customer	user@dev.com	2026-09-09 11:46:09	$2y$12$sPXzlFw0nTYVzZayOLlK4eSqXAPXwTlpJLeXquLte4c.kCqp1V2ey	\N	2026-09-09 11:46:09	2026-09-09 11:46:09	\N	\N	\N	customer	\N	t
\.


--
-- Name: activity_log_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.activity_log_id_seq', 1, false);


--
-- Name: branches_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.branches_id_seq', 2, true);


--
-- Name: database_backup_histories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.database_backup_histories_id_seq', 2, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: fcm_device_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.fcm_device_tokens_id_seq', 1, false);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.migrations_id_seq', 12, true);


--
-- Name: model_has_permissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.model_has_permissions_id_seq', 1, false);


--
-- Name: model_has_roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.model_has_roles_id_seq', 7, true);


--
-- Name: passkeys_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.passkeys_id_seq', 1, false);


--
-- Name: permissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.permissions_id_seq', 15, true);


--
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.roles_id_seq', 12, true);


--
-- Name: telescope_entries_sequence_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.telescope_entries_sequence_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 9, true);


--
-- Name: activity_log activity_log_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.activity_log
    ADD CONSTRAINT activity_log_pkey PRIMARY KEY (id);


--
-- Name: branches branches_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.branches
    ADD CONSTRAINT branches_pkey PRIMARY KEY (id);


--
-- Name: branches branches_slug_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.branches
    ADD CONSTRAINT branches_slug_unique UNIQUE (slug);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: database_backup_histories database_backup_histories_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.database_backup_histories
    ADD CONSTRAINT database_backup_histories_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: fcm_device_tokens fcm_device_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fcm_device_tokens
    ADD CONSTRAINT fcm_device_tokens_pkey PRIMARY KEY (id);


--
-- Name: fcm_device_tokens fcm_device_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fcm_device_tokens
    ADD CONSTRAINT fcm_device_tokens_token_unique UNIQUE (token);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: model_has_permissions model_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_pkey PRIMARY KEY (id);


--
-- Name: model_has_permissions model_has_permissions_team_permission_model_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_team_permission_model_unique UNIQUE (branch_id, permission_id, model_id, model_type);


--
-- Name: model_has_roles model_has_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_pkey PRIMARY KEY (id);


--
-- Name: model_has_roles model_has_roles_team_role_model_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_team_role_model_unique UNIQUE (branch_id, role_id, model_id, model_type);


--
-- Name: passkeys passkeys_credential_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.passkeys
    ADD CONSTRAINT passkeys_credential_id_unique UNIQUE (credential_id);


--
-- Name: passkeys passkeys_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.passkeys
    ADD CONSTRAINT passkeys_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: permissions permissions_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: role_has_permissions role_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_pkey PRIMARY KEY (permission_id, role_id);


--
-- Name: roles roles_branch_id_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_branch_id_name_guard_name_unique UNIQUE (branch_id, name, guard_name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: telescope_entries telescope_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.telescope_entries
    ADD CONSTRAINT telescope_entries_pkey PRIMARY KEY (sequence);


--
-- Name: telescope_entries_tags telescope_entries_tags_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.telescope_entries_tags
    ADD CONSTRAINT telescope_entries_tags_pkey PRIMARY KEY (entry_uuid, tag);


--
-- Name: telescope_entries telescope_entries_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.telescope_entries
    ADD CONSTRAINT telescope_entries_uuid_unique UNIQUE (uuid);


--
-- Name: telescope_monitoring telescope_monitoring_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.telescope_monitoring
    ADD CONSTRAINT telescope_monitoring_pkey PRIMARY KEY (tag);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: activity_log_log_name_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX activity_log_log_name_index ON public.activity_log USING btree (log_name);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: causer; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX causer ON public.activity_log USING btree (causer_type, causer_id);


--
-- Name: database_backup_histories_created_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX database_backup_histories_created_at_index ON public.database_backup_histories USING btree (created_at);


--
-- Name: database_backup_histories_type_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX database_backup_histories_type_status_index ON public.database_backup_histories USING btree (type, status);


--
-- Name: failed_jobs_connection_queue_failed_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX failed_jobs_connection_queue_failed_at_index ON public.failed_jobs USING btree (connection, queue, failed_at);


--
-- Name: fcm_device_tokens_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX fcm_device_tokens_user_id_index ON public.fcm_device_tokens USING btree (user_id);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: model_has_permissions_model_id_model_type_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX model_has_permissions_model_id_model_type_index ON public.model_has_permissions USING btree (model_id, model_type);


--
-- Name: model_has_permissions_team_foreign_key_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX model_has_permissions_team_foreign_key_index ON public.model_has_permissions USING btree (branch_id);


--
-- Name: model_has_roles_model_id_model_type_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type);


--
-- Name: model_has_roles_team_foreign_key_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX model_has_roles_team_foreign_key_index ON public.model_has_roles USING btree (branch_id);


--
-- Name: passkeys_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX passkeys_user_id_index ON public.passkeys USING btree (user_id);


--
-- Name: roles_team_foreign_key_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX roles_team_foreign_key_index ON public.roles USING btree (branch_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: subject; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX subject ON public.activity_log USING btree (subject_type, subject_id);


--
-- Name: telescope_entries_batch_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX telescope_entries_batch_id_index ON public.telescope_entries USING btree (batch_id);


--
-- Name: telescope_entries_created_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX telescope_entries_created_at_index ON public.telescope_entries USING btree (created_at);


--
-- Name: telescope_entries_family_hash_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX telescope_entries_family_hash_index ON public.telescope_entries USING btree (family_hash);


--
-- Name: telescope_entries_tags_tag_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX telescope_entries_tags_tag_index ON public.telescope_entries_tags USING btree (tag);


--
-- Name: telescope_entries_type_should_display_on_index_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX telescope_entries_type_should_display_on_index_index ON public.telescope_entries USING btree (type, should_display_on_index);


--
-- Name: database_backup_histories database_backup_histories_source_history_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.database_backup_histories
    ADD CONSTRAINT database_backup_histories_source_history_id_foreign FOREIGN KEY (source_history_id) REFERENCES public.database_backup_histories(id) ON DELETE SET NULL;


--
-- Name: database_backup_histories database_backup_histories_triggered_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.database_backup_histories
    ADD CONSTRAINT database_backup_histories_triggered_by_foreign FOREIGN KEY (triggered_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: fcm_device_tokens fcm_device_tokens_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fcm_device_tokens
    ADD CONSTRAINT fcm_device_tokens_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: model_has_permissions model_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: model_has_roles model_has_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: passkeys passkeys_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.passkeys
    ADD CONSTRAINT passkeys_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: telescope_entries_tags telescope_entries_tags_entry_uuid_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.telescope_entries_tags
    ADD CONSTRAINT telescope_entries_tags_entry_uuid_foreign FOREIGN KEY (entry_uuid) REFERENCES public.telescope_entries(uuid) ON DELETE CASCADE;


--
-- Name: users users_branch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES public.branches(id) ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

\unrestrict laravelbackupfixedkey123

