--
-- PostgreSQL database dump
--

-- Dumped from database version 9.5.14
-- Dumped by pg_dump version 10.7 (Ubuntu 10.7-0ubuntu0.18.04.1)

-- Started on 2019-04-10 02:39:07 CEST

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 1 (class 3079 OID 12403)
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- TOC entry 2296 (class 0 OID 0)
-- Dependencies: 1
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET default_tablespace = '';

SET default_with_oids = false;

DROP TABLE IF EXISTS public.child_test;
DROP TABLE IF EXISTS public.db_constraint;
DROP TABLE IF EXISTS public.home;
DROP TABLE IF EXISTS public.house;
DROP TABLE IF EXISTS public.main_test;
DROP TABLE IF EXISTS public.man_body;
DROP TABLE IF EXISTS public.person;
DROP TABLE IF EXISTS public.place;
DROP TABLE IF EXISTS public.test;
DROP TABLE IF EXISTS public.test_multi_increment;
DROP TABLE IF EXISTS public.town;
DROP TABLE IF EXISTS public.woman_body;

--
-- TOC entry 181 (class 1259 OID 58966)
-- Name: child_test; Type: TABLE; Schema: public; Owner: root
--

CREATE TABLE public.child_test (
    id bigint NOT NULL,
    name text NOT NULL,
    parent_id_1 integer NOT NULL,
    parent_id_2 text NOT NULL
);


ALTER TABLE public.child_test OWNER TO root;

--
-- TOC entry 182 (class 1259 OID 58972)
-- Name: child_test_id_seq; Type: SEQUENCE; Schema: public; Owner: root
--

CREATE SEQUENCE public.child_test_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.child_test_id_seq OWNER TO root;

--
-- TOC entry 2297 (class 0 OID 0)
-- Dependencies: 182
-- Name: child_test_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: root
--

ALTER SEQUENCE public.child_test_id_seq OWNED BY public.child_test.id;


--
-- TOC entry 183 (class 1259 OID 58974)
-- Name: db_constraint; Type: TABLE; Schema: public; Owner: root
--

CREATE TABLE public.db_constraint (
    unique_name character varying(32) NOT NULL,
    id bigint NOT NULL,
    foreign_constraint bigint,
    unique_one integer,
    unique_two text,
    unique_foreign_one integer,
    unique_foreign_two text
);


ALTER TABLE public.db_constraint OWNER TO root;

--
-- TOC entry 184 (class 1259 OID 58980)
-- Name: db_constraint_id_seq; Type: SEQUENCE; Schema: public; Owner: root
--

CREATE SEQUENCE public.db_constraint_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.db_constraint_id_seq OWNER TO root;

--
-- TOC entry 2298 (class 0 OID 0)
-- Dependencies: 184
-- Name: db_constraint_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: root
--

ALTER SEQUENCE public.db_constraint_id_seq OWNED BY public.db_constraint.id;


--
-- TOC entry 185 (class 1259 OID 58982)
-- Name: home; Type: TABLE; Schema: public; Owner: root
--

CREATE TABLE public.home (
    id bigint NOT NULL,
    begin_date text NOT NULL,
    end_date text NOT NULL,
    person_id integer NOT NULL,
    house_id integer NOT NULL
);


ALTER TABLE public.home OWNER TO root;

--
-- TOC entry 186 (class 1259 OID 58988)
-- Name: home_id_seq; Type: SEQUENCE; Schema: public; Owner: root
--

CREATE SEQUENCE public.home_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.home_id_seq OWNER TO root;

--
-- TOC entry 2299 (class 0 OID 0)
-- Dependencies: 186
-- Name: home_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: root
--

ALTER SEQUENCE public.home_id_seq OWNED BY public.home.id;


--
-- TOC entry 187 (class 1259 OID 58990)
-- Name: house; Type: TABLE; Schema: public; Owner: root
--

CREATE TABLE public.house (
    id_serial bigint NOT NULL,
    surface integer NOT NULL,
    type text NOT NULL,
    garden boolean NOT NULL,
    garage boolean NOT NULL
);


ALTER TABLE public.house OWNER TO root;

--
-- TOC entry 188 (class 1259 OID 58996)
-- Name: house_id_serial_seq; Type: SEQUENCE; Schema: public; Owner: root
--

CREATE SEQUENCE public.house_id_serial_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.house_id_serial_seq OWNER TO root;

--
-- TOC entry 2300 (class 0 OID 0)
-- Dependencies: 188
-- Name: house_id_serial_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: root
--

ALTER SEQUENCE public.house_id_serial_seq OWNED BY public.house.id_serial;


--
-- TOC entry 189 (class 1259 OID 58998)
-- Name: main_test; Type: TABLE; Schema: public; Owner: root
--

CREATE TABLE public.main_test (
    id bigint NOT NULL,
    name text NOT NULL,
    obj text
);


ALTER TABLE public.main_test OWNER TO root;

--
-- TOC entry 190 (class 1259 OID 59004)
-- Name: main_test_id_seq; Type: SEQUENCE; Schema: public; Owner: root
--

CREATE SEQUENCE public.main_test_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.main_test_id_seq OWNER TO root;

--
-- TOC entry 2301 (class 0 OID 0)
-- Dependencies: 190
-- Name: main_test_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: root
--

ALTER SEQUENCE public.main_test_id_seq OWNED BY public.main_test.id;


--
-- TOC entry 191 (class 1259 OID 59006)
-- Name: man_body; Type: TABLE; Schema: public; Owner: root
--

CREATE TABLE public.man_body (
    id bigint NOT NULL,
    height double precision NOT NULL,
    weight double precision NOT NULL,
    hair_color text NOT NULL,
    hair_cut text NOT NULL,
    eyes_color text NOT NULL,
    physical_appearance text NOT NULL,
    tatoos text NOT NULL,
    piercings text NOT NULL,
    owner_id bigint NOT NULL,
    baldness boolean DEFAULT false NOT NULL,
    date timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.man_body OWNER TO root;

--
-- TOC entry 192 (class 1259 OID 59014)
-- Name: man_body_id_seq; Type: SEQUENCE; Schema: public; Owner: root
--

CREATE SEQUENCE public.man_body_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.man_body_id_seq OWNER TO root;

--
-- TOC entry 2302 (class 0 OID 0)
-- Dependencies: 192
-- Name: man_body_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: root
--

ALTER SEQUENCE public.man_body_id_seq OWNED BY public.man_body.id;


--
-- TOC entry 193 (class 1259 OID 59016)
-- Name: person; Type: TABLE; Schema: public; Owner: root
--

CREATE TABLE public.person (
    id bigint NOT NULL,
    first_name text,
    last_name text,
    sex text NOT NULL,
    birth_place integer,
    father_id integer,
    mother_id integer,
    birth_date timestamp with time zone,
    best_friend integer
);


ALTER TABLE public.person OWNER TO root;

--
-- TOC entry 194 (class 1259 OID 59022)
-- Name: person_id_seq; Type: SEQUENCE; Schema: public; Owner: root
--

CREATE SEQUENCE public.person_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.person_id_seq OWNER TO root;

--
-- TOC entry 2303 (class 0 OID 0)
-- Dependencies: 194
-- Name: person_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: root
--

ALTER SEQUENCE public.person_id_seq OWNED BY public.person.id;


--
-- TOC entry 195 (class 1259 OID 59024)
-- Name: place; Type: TABLE; Schema: public; Owner: root
--

CREATE TABLE public.place (
    id bigint NOT NULL,
    number integer,
    type text,
    name text,
    geographic_latitude double precision,
    geographic_longitude double precision,
    town integer
);


ALTER TABLE public.place OWNER TO root;

--
-- TOC entry 196 (class 1259 OID 59030)
-- Name: place_id_seq; Type: SEQUENCE; Schema: public; Owner: root
--

CREATE SEQUENCE public.place_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.place_id_seq OWNER TO root;

--
-- TOC entry 2304 (class 0 OID 0)
-- Dependencies: 196
-- Name: place_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: root
--

ALTER SEQUENCE public.place_id_seq OWNED BY public.place.id;


--
-- TOC entry 197 (class 1259 OID 59032)
-- Name: test; Type: TABLE; Schema: public; Owner: root
--

CREATE TABLE public.test (
    id_1 integer NOT NULL,
    id_2 text NOT NULL,
    date timestamp without time zone,
    object text,
    object_with_id text,
    "timestamp" timestamp with time zone DEFAULT now() NOT NULL,
    "integer" integer,
    string text NOT NULL,
    main_test_id integer NOT NULL,
    objects_with_id character varying(1024) DEFAULT '[]'::character varying NOT NULL,
    foreign_objects character varying(1024) DEFAULT '[]'::character varying NOT NULL,
    lonely_foreign_object text,
    lonely_foreign_object_two text,
    default_value text,
    woman_xml_id integer,
    man_body_json_id integer,
    "boolean" boolean NOT NULL,
    boolean2 boolean DEFAULT true NOT NULL
);


ALTER TABLE public.test OWNER TO root;

--
-- TOC entry 198 (class 1259 OID 59042)
-- Name: test_multi_increment; Type: TABLE; Schema: public; Owner: root
--

CREATE TABLE public.test_multi_increment (
    id1 bigint NOT NULL,
    plop text,
    id2 integer NOT NULL
);


ALTER TABLE public.test_multi_increment OWNER TO root;

--
-- TOC entry 199 (class 1259 OID 59048)
-- Name: test_multi_increment_id1_seq; Type: SEQUENCE; Schema: public; Owner: root
--

CREATE SEQUENCE public.test_multi_increment_id1_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.test_multi_increment_id1_seq OWNER TO root;

--
-- TOC entry 2305 (class 0 OID 0)
-- Dependencies: 199
-- Name: test_multi_increment_id1_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: root
--

ALTER SEQUENCE public.test_multi_increment_id1_seq OWNED BY public.test_multi_increment.id1;


--
-- TOC entry 200 (class 1259 OID 59050)
-- Name: town; Type: TABLE; Schema: public; Owner: root
--

CREATE TABLE public.town (
    id bigint NOT NULL,
    name text NOT NULL,
    surface integer,
    city_hall integer
);


ALTER TABLE public.town OWNER TO root;

--
-- TOC entry 201 (class 1259 OID 59056)
-- Name: town_id_seq; Type: SEQUENCE; Schema: public; Owner: root
--

CREATE SEQUENCE public.town_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.town_id_seq OWNER TO root;

--
-- TOC entry 2306 (class 0 OID 0)
-- Dependencies: 201
-- Name: town_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: root
--

ALTER SEQUENCE public.town_id_seq OWNED BY public.town.id;


--
-- TOC entry 202 (class 1259 OID 59058)
-- Name: woman_body; Type: TABLE; Schema: public; Owner: root
--

CREATE TABLE public.woman_body (
    id bigint NOT NULL,
    height double precision NOT NULL,
    weight double precision NOT NULL,
    hair_color text NOT NULL,
    hair_cut text NOT NULL,
    eyes_color text NOT NULL,
    physical_appearance text NOT NULL,
    tatoos text NOT NULL,
    piercings text NOT NULL,
    owner_id bigint NOT NULL,
    chest_size text NOT NULL,
    date timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.woman_body OWNER TO root;

--
-- TOC entry 203 (class 1259 OID 59065)
-- Name: woman_body_id_seq; Type: SEQUENCE; Schema: public; Owner: root
--

CREATE SEQUENCE public.woman_body_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.woman_body_id_seq OWNER TO root;

--
-- TOC entry 2307 (class 0 OID 0)
-- Dependencies: 203
-- Name: woman_body_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: root
--

ALTER SEQUENCE public.woman_body_id_seq OWNED BY public.woman_body.id;


--
-- TOC entry 2103 (class 2604 OID 59067)
-- Name: child_test id; Type: DEFAULT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.child_test ALTER COLUMN id SET DEFAULT nextval('public.child_test_id_seq'::regclass);


--
-- TOC entry 2104 (class 2604 OID 59068)
-- Name: db_constraint id; Type: DEFAULT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.db_constraint ALTER COLUMN id SET DEFAULT nextval('public.db_constraint_id_seq'::regclass);


--
-- TOC entry 2105 (class 2604 OID 59069)
-- Name: home id; Type: DEFAULT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.home ALTER COLUMN id SET DEFAULT nextval('public.home_id_seq'::regclass);


--
-- TOC entry 2106 (class 2604 OID 59070)
-- Name: house id_serial; Type: DEFAULT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.house ALTER COLUMN id_serial SET DEFAULT nextval('public.house_id_serial_seq'::regclass);


--
-- TOC entry 2107 (class 2604 OID 59071)
-- Name: main_test id; Type: DEFAULT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.main_test ALTER COLUMN id SET DEFAULT nextval('public.main_test_id_seq'::regclass);


--
-- TOC entry 2110 (class 2604 OID 59072)
-- Name: man_body id; Type: DEFAULT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.man_body ALTER COLUMN id SET DEFAULT nextval('public.man_body_id_seq'::regclass);


--
-- TOC entry 2111 (class 2604 OID 59073)
-- Name: person id; Type: DEFAULT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.person ALTER COLUMN id SET DEFAULT nextval('public.person_id_seq'::regclass);


--
-- TOC entry 2112 (class 2604 OID 59074)
-- Name: place id; Type: DEFAULT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.place ALTER COLUMN id SET DEFAULT nextval('public.place_id_seq'::regclass);


--
-- TOC entry 2117 (class 2604 OID 59075)
-- Name: test_multi_increment id1; Type: DEFAULT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.test_multi_increment ALTER COLUMN id1 SET DEFAULT nextval('public.test_multi_increment_id1_seq'::regclass);


--
-- TOC entry 2118 (class 2604 OID 59076)
-- Name: town id; Type: DEFAULT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.town ALTER COLUMN id SET DEFAULT nextval('public.town_id_seq'::regclass);


--
-- TOC entry 2120 (class 2604 OID 59077)
-- Name: woman_body id; Type: DEFAULT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.woman_body ALTER COLUMN id SET DEFAULT nextval('public.woman_body_id_seq'::regclass);


--
-- TOC entry 2265 (class 0 OID 58966)
-- Dependencies: 181
-- Data for Name: child_test; Type: TABLE DATA; Schema: public; Owner: root
--

INSERT INTO public.child_test VALUES (1, 'plop', 1, '1501774389');
INSERT INTO public.child_test VALUES (2, 'plop2', 1, '1501774389');


--
-- TOC entry 2267 (class 0 OID 58974)
-- Dependencies: 183
-- Data for Name: db_constraint; Type: TABLE DATA; Schema: public; Owner: root
--



--
-- TOC entry 2269 (class 0 OID 58982)
-- Dependencies: 185
-- Data for Name: home; Type: TABLE DATA; Schema: public; Owner: root
--

INSERT INTO public.home VALUES (1, '16/09/1988', '16/09/1989', 1, 1);
INSERT INTO public.home VALUES (2, '16/09/1990', '16/09/1995', 1, 2);
INSERT INTO public.home VALUES (3, '16/09/1955', '16/09/1960', 6, 3);
INSERT INTO public.home VALUES (4, '18/09/1988', '22/12/1988', 5, 4);
INSERT INTO public.home VALUES (5, '16/12/1988', '16/09/1989', 5, 5);
INSERT INTO public.home VALUES (6, '01/01/70', '16/09/1995', 1, 6);


--
-- TOC entry 2271 (class 0 OID 58990)
-- Dependencies: 187
-- Data for Name: house; Type: TABLE DATA; Schema: public; Owner: root
--

INSERT INTO public.house VALUES (1, 120, 'T4', true, true);
INSERT INTO public.house VALUES (2, 90, 'T4', false, false);
INSERT INTO public.house VALUES (3, 50, 'T1', false, false);
INSERT INTO public.house VALUES (4, 55, 'T2', false, false);
INSERT INTO public.house VALUES (5, 200, 'T4', true, true);
INSERT INTO public.house VALUES (6, 300, 'T6', true, true);


--
-- TOC entry 2273 (class 0 OID 58998)
-- Dependencies: 189
-- Data for Name: main_test; Type: TABLE DATA; Schema: public; Owner: root
--

INSERT INTO public.main_test VALUES (1, 'azeaze', NULL);
INSERT INTO public.main_test VALUES (2, 'qsdqsd', '{"plop":"ploooop","plop2":"ploooop2"}');


--
-- TOC entry 2275 (class 0 OID 59006)
-- Dependencies: 191
-- Data for Name: man_body; Type: TABLE DATA; Schema: public; Owner: root
--

INSERT INTO public.man_body VALUES (1, 1.80000000000000004, 80, 'black', 'short', 'blue', 'muscular', '[{"type":"tribal","location":"shoulder","tatooArtist":2}]', '', 1, false, '2010-12-24 00:00:00+01');
INSERT INTO public.man_body VALUES (2, 1.80000000000000004, 80, 'black', 'short', 'blue', 'slim', '[{"type":"tribal","location":"shoulder","tatooArtist":2},{"type":"tribal","location":"leg","tatooArtist":3}]', '', 1, true, '2016-01-01 00:00:00+01');


--
-- TOC entry 2277 (class 0 OID 59016)
-- Dependencies: 193
-- Data for Name: person; Type: TABLE DATA; Schema: public; Owner: root
--

INSERT INTO public.person VALUES (1, 'Bernard', 'Dupond', 'Test\Person\Man', 2, NULL, NULL, '2016-11-13 20:04:05+01', NULL);
INSERT INTO public.person VALUES (5, 'Jean', 'Henri', 'Test\Person\Man', NULL, 1, 2, '2016-11-13 20:04:05+01', 7);
INSERT INTO public.person VALUES (6, 'john', 'lennon', 'Test\Person\Man', NULL, 1, 2, '2016-11-13 20:04:05+01', NULL);
INSERT INTO public.person VALUES (2, 'Marie', 'Smith', 'Test\Person\Woman', NULL, NULL, NULL, '2016-11-13 20:04:05+01', 5);
INSERT INTO public.person VALUES (7, 'lois', 'lane', 'Test\Person\Woman', NULL, NULL, NULL, '2016-11-13 20:02:59+01', NULL);
INSERT INTO public.person VALUES (8, 'louise', 'truc', 'Test\Person\Woman', NULL, 6, 7, NULL, 9);
INSERT INTO public.person VALUES (9, 'lala', 'truc', 'Test\Person\Woman', NULL, 6, 7, NULL, NULL);
INSERT INTO public.person VALUES (10, 'plop', 'plop', 'Test\Person\Woman', NULL, 5, 7, NULL, NULL);
INSERT INTO public.person VALUES (11, 'Naelya', 'Dupond', 'Test\Person\Woman', 2, 1, NULL, NULL, NULL);


--
-- TOC entry 2279 (class 0 OID 59024)
-- Dependencies: 195
-- Data for Name: place; Type: TABLE DATA; Schema: public; Owner: root
--

INSERT INTO public.place VALUES (1, 1, 'square', 'George Frêche', NULL, NULL, 1);
INSERT INTO public.place VALUES (2, 16, 'street', 'Trocmé', NULL, NULL, 1);


--
-- TOC entry 2281 (class 0 OID 59032)
-- Dependencies: 197
-- Data for Name: test; Type: TABLE DATA; Schema: public; Owner: root
--

INSERT INTO public.test VALUES (1, '23', '2016-05-01 12:53:54', NULL, NULL, '2016-10-16 21:50:19+02', 0, 'aaaa', 1, '[]', '[]', NULL, NULL, 'default', NULL, NULL, false, true);
INSERT INTO public.test VALUES (1, '50', '2016-10-16 18:21:18', '{"plop":"plop","plop2":"plop2222"}', '{"plop":"plop","plop2":"plop2222"}', '2016-10-16 21:50:19+02', 1, 'bbbb', 1, '[]', '[]', NULL, NULL, 'default', NULL, NULL, false, true);
INSERT INTO public.test VALUES (1, '101', '2016-04-13 07:14:33', '{"plop":"plop","plop2":"plop2"}', '{"plop":"plop","plop2":"plop2"}', '2016-10-16 21:50:19+02', 2, 'cccc', 1, '[]', '[]', NULL, NULL, 'default', NULL, NULL, false, true);
INSERT INTO public.test VALUES (2, '50', '2016-05-01 21:37:18', '{"plop":"plop","plop2":"plop2222"}', '{"plop":"plop","plop2":"plop2222"}', '2016-10-16 21:50:19+02', 3, 'dddd', 1, '[]', '[]', NULL, NULL, 'default', NULL, NULL, false, true);
INSERT INTO public.test VALUES (2, '102', '2016-04-01 06:00:00', '{"plop":"plop10","plop2":"plop20"}', NULL, '2016-10-16 18:21:18+02', 4, 'eeee', 1, '[]', '[]', NULL, NULL, 'default', NULL, NULL, false, true);
INSERT INTO public.test VALUES (3, '50', '2016-05-01 21:39:29', '{"plop":"plop","plop2":"plop2222"}', NULL, '2016-10-16 18:21:18+02', 5, 'ffff', 2, '[]', '[]', NULL, NULL, 'default', NULL, NULL, false, true);
INSERT INTO public.test VALUES (4, '50', '2016-05-09 23:56:36', '{"plop":"plop","plop2":"plop2222"}', NULL, '2016-10-16 18:21:18+02', 6, 'gggg', 2, '[]', '[]', NULL, NULL, 'default', 4, 4567, false, true);
INSERT INTO public.test VALUES (40, '50', '2016-05-09 23:50:20', '{"plop":"plop","plop2":"plop2222"}', NULL, '2016-10-16 18:21:18+02', 7, 'hhhh', 2, '[]', '[]', NULL, NULL, 'default', 3, 1567, false, true);
INSERT INTO public.test VALUES (1, '1501774389', '2016-04-12 03:14:33', '{"plop":"plop","plop2":"plop2"}', '{"plop":"plop","plop2":"plop2"}', '2016-10-13 11:50:19+02', 2, 'nnnn', 1, '[{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","plop4":"heyplop4","__inheritance__":"Test\\TestDb\\ObjectWithIdAndMoreMore"},{"plop":"1","plop2":"heyplop2","plop3":"heyplop3","__inheritance__":"Test\\TestDb\\ObjectWithIdAndMore"},{"plop":"1","plop2":"heyplop2"},{"plop":"11","plop2":"heyplop22"},{"plop":"11","plop2":"heyplop22","plop3":"heyplop33","__inheritance__":"Test\\TestDb\\ObjectWithIdAndMore"}]', '[{"id":"1","__inheritance__":"Test\\TestDb\\ObjectWithIdAndMoreMore"},{"id":"1","__inheritance__":"Test\\TestDb\\ObjectWithIdAndMore"},"1","11",{"id":"11","__inheritance__":"Test\\TestDb\\ObjectWithIdAndMore"}]', '{"id":"11","__inheritance__":"Test\\TestDb\\ObjectWithIdAndMore"}', '11', 'default', NULL, NULL, false, true);


--
-- TOC entry 2282 (class 0 OID 59042)
-- Dependencies: 198
-- Data for Name: test_multi_increment; Type: TABLE DATA; Schema: public; Owner: root
--

INSERT INTO public.test_multi_increment VALUES (1, 'lalala', 0);
INSERT INTO public.test_multi_increment VALUES (2, 'lalala2', 0);
INSERT INTO public.test_multi_increment VALUES (3, 'hehe', 0);
INSERT INTO public.test_multi_increment VALUES (4, 'hoho', 0);
INSERT INTO public.test_multi_increment VALUES (5, 'hoho', 0);
INSERT INTO public.test_multi_increment VALUES (6, 'hoho', 0);
INSERT INTO public.test_multi_increment VALUES (7, 'hoho', 0);
INSERT INTO public.test_multi_increment VALUES (8, 'hoho', 45);
INSERT INTO public.test_multi_increment VALUES (9, 'hoho', 45);
INSERT INTO public.test_multi_increment VALUES (10, 'hoho', 45);
INSERT INTO public.test_multi_increment VALUES (11, 'hoho', 45);
INSERT INTO public.test_multi_increment VALUES (12, 'hoho', 45);
INSERT INTO public.test_multi_increment VALUES (13, 'hoho', 45);
INSERT INTO public.test_multi_increment VALUES (14, 'hoho', 45);
INSERT INTO public.test_multi_increment VALUES (15, 'hohohohoho', 45);
INSERT INTO public.test_multi_increment VALUES (16, 'hoho', 45);


--
-- TOC entry 2284 (class 0 OID 59050)
-- Dependencies: 200
-- Data for Name: town; Type: TABLE DATA; Schema: public; Owner: root
--

INSERT INTO public.town VALUES (1, 'Montpellier', NULL, 1);


--
-- TOC entry 2286 (class 0 OID 59058)
-- Dependencies: 202
-- Data for Name: woman_body; Type: TABLE DATA; Schema: public; Owner: root
--

INSERT INTO public.woman_body VALUES (1, 1.64999999999999991, 60, 'black', 'long', 'green', 'athletic', '[{"type":"sentence","location":"shoulder","tatooArtist":5},{"type":"sentence","location":"arm","tatooArtist":6},{"type":"sentence","location":"leg","tatooArtist":5}]', '[{"type":"earring","location":"ear","piercer":5},{"type":"earring","location":"ear","piercer":6},{"type":"clasp","location":"eyebrow","piercer":5}]', 2, '90-B', '2016-11-13 22:23:37+01');


--
-- TOC entry 2308 (class 0 OID 0)
-- Dependencies: 182
-- Name: child_test_id_seq; Type: SEQUENCE SET; Schema: public; Owner: root
--

SELECT pg_catalog.setval('public.child_test_id_seq', 1065, true);


--
-- TOC entry 2309 (class 0 OID 0)
-- Dependencies: 184
-- Name: db_constraint_id_seq; Type: SEQUENCE SET; Schema: public; Owner: root
--

SELECT pg_catalog.setval('public.db_constraint_id_seq', 1, true);


--
-- TOC entry 2310 (class 0 OID 0)
-- Dependencies: 186
-- Name: home_id_seq; Type: SEQUENCE SET; Schema: public; Owner: root
--

SELECT pg_catalog.setval('public.home_id_seq', 6, true);


--
-- TOC entry 2311 (class 0 OID 0)
-- Dependencies: 188
-- Name: house_id_serial_seq; Type: SEQUENCE SET; Schema: public; Owner: root
--

SELECT pg_catalog.setval('public.house_id_serial_seq', 6, true);


--
-- TOC entry 2312 (class 0 OID 0)
-- Dependencies: 190
-- Name: main_test_id_seq; Type: SEQUENCE SET; Schema: public; Owner: root
--

SELECT pg_catalog.setval('public.main_test_id_seq', 2, true);


--
-- TOC entry 2313 (class 0 OID 0)
-- Dependencies: 192
-- Name: man_body_id_seq; Type: SEQUENCE SET; Schema: public; Owner: root
--

SELECT pg_catalog.setval('public.man_body_id_seq', 2, true);


--
-- TOC entry 2314 (class 0 OID 0)
-- Dependencies: 194
-- Name: person_id_seq; Type: SEQUENCE SET; Schema: public; Owner: root
--

SELECT pg_catalog.setval('public.person_id_seq', 797, true);


--
-- TOC entry 2315 (class 0 OID 0)
-- Dependencies: 196
-- Name: place_id_seq; Type: SEQUENCE SET; Schema: public; Owner: root
--

SELECT pg_catalog.setval('public.place_id_seq', 2, true);


--
-- TOC entry 2316 (class 0 OID 0)
-- Dependencies: 199
-- Name: test_multi_increment_id1_seq; Type: SEQUENCE SET; Schema: public; Owner: root
--

SELECT pg_catalog.setval('public.test_multi_increment_id1_seq', 16, true);


--
-- TOC entry 2317 (class 0 OID 0)
-- Dependencies: 201
-- Name: town_id_seq; Type: SEQUENCE SET; Schema: public; Owner: root
--

SELECT pg_catalog.setval('public.town_id_seq', 1, true);


--
-- TOC entry 2318 (class 0 OID 0)
-- Dependencies: 203
-- Name: woman_body_id_seq; Type: SEQUENCE SET; Schema: public; Owner: root
--

SELECT pg_catalog.setval('public.woman_body_id_seq', 1, true);


--
-- TOC entry 2124 (class 2606 OID 59079)
-- Name: db_constraint db_constraint_pkey; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.db_constraint
    ADD CONSTRAINT db_constraint_pkey PRIMARY KEY (id);


--
-- TOC entry 2126 (class 2606 OID 59081)
-- Name: db_constraint db_constraint_unique_name_key; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.db_constraint
    ADD CONSTRAINT db_constraint_unique_name_key UNIQUE (unique_name);


--
-- TOC entry 2128 (class 2606 OID 59115)
-- Name: db_constraint db_constraint_unique_one_unique_two_key; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.db_constraint
    ADD CONSTRAINT db_constraint_unique_one_unique_two_key UNIQUE (unique_one, unique_two);
    
--
-- TOC entry 2128 (class 2606 OID 59115)
-- Name: db_constraint db_constraint_unique_one_unique_two_key; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.db_constraint
    ADD CONSTRAINT db_constraint_unique_foreign_one_unique_foreign_two_key UNIQUE (unique_foreign_one, unique_foreign_two);


--
-- TOC entry 2122 (class 2606 OID 59083)
-- Name: child_test pk_child_test; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.child_test
    ADD CONSTRAINT pk_child_test PRIMARY KEY (id);


--
-- TOC entry 2130 (class 2606 OID 59085)
-- Name: home pk_home; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.home
    ADD CONSTRAINT pk_home PRIMARY KEY (id);


--
-- TOC entry 2132 (class 2606 OID 59087)
-- Name: house pk_house; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.house
    ADD CONSTRAINT pk_house PRIMARY KEY (id_serial);


--
-- TOC entry 2134 (class 2606 OID 59089)
-- Name: main_test pk_main_test; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.main_test
    ADD CONSTRAINT pk_main_test PRIMARY KEY (id);


--
-- TOC entry 2136 (class 2606 OID 59091)
-- Name: man_body pk_man_body; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.man_body
    ADD CONSTRAINT pk_man_body PRIMARY KEY (id);


--
-- TOC entry 2138 (class 2606 OID 59093)
-- Name: person pk_person; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.person
    ADD CONSTRAINT pk_person PRIMARY KEY (id);


--
-- TOC entry 2140 (class 2606 OID 59095)
-- Name: place pk_place; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.place
    ADD CONSTRAINT pk_place PRIMARY KEY (id);


--
-- TOC entry 2142 (class 2606 OID 59097)
-- Name: test pk_test; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.test
    ADD CONSTRAINT pk_test PRIMARY KEY (id_1, id_2);


--
-- TOC entry 2144 (class 2606 OID 59099)
-- Name: test_multi_increment pk_test_multi_increment; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.test_multi_increment
    ADD CONSTRAINT pk_test_multi_increment PRIMARY KEY (id1, id2);


--
-- TOC entry 2146 (class 2606 OID 59101)
-- Name: town pk_town; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.town
    ADD CONSTRAINT pk_town PRIMARY KEY (id);


--
-- TOC entry 2148 (class 2606 OID 59103)
-- Name: woman_body pk_woman_body; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.woman_body
    ADD CONSTRAINT pk_woman_body PRIMARY KEY (id);


--
-- TOC entry 2149 (class 2606 OID 59104)
-- Name: db_constraint db_constraint_foreign_constraint_fkey; Type: FK CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.db_constraint
    ADD CONSTRAINT db_constraint_foreign_constraint_fkey FOREIGN KEY (foreign_constraint) REFERENCES public.db_constraint(id);


--
-- TOC entry 2150 (class 2606 OID 59109)
-- Name: db_constraint db_constraint_unique_one_fkey; Type: FK CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.db_constraint
    ADD CONSTRAINT db_constraint_unique_one_fkey FOREIGN KEY (unique_foreign_one, unique_foreign_two) REFERENCES public.test(id_1, id_2);


--
-- TOC entry 2295 (class 0 OID 0)
-- Dependencies: 7
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;

SET search_path TO public;

-- Completed on 2019-04-10 02:39:07 CEST

--
-- PostgreSQL database dump complete
--
