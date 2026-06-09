--
-- PostgreSQL database dump
--

\restrict iN108D11isIKXCV4Jg5lFpTuP2OIcXtwcrY3wBqbcBj1bOFxLA6M7Me9pO5Gog3

-- Dumped from database version 18.1
-- Dumped by pg_dump version 18.1

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
-- Name: tbl_booking; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tbl_booking (
    booking_id integer NOT NULL,
    booking_date date,
    start_time time without time zone,
    end_time time without time zone,
    booking_status character varying(20),
    user_id integer,
    parking_id integer,
    plate_number character varying(20),
    phone_number character varying(15)
);


ALTER TABLE public.tbl_booking OWNER TO postgres;

--
-- Name: tbl_booking_booking_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tbl_booking_booking_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tbl_booking_booking_id_seq OWNER TO postgres;

--
-- Name: tbl_booking_booking_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tbl_booking_booking_id_seq OWNED BY public.tbl_booking.booking_id;


--
-- Name: tbl_parking_listing; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tbl_parking_listing (
    parking_id integer NOT NULL,
    location character varying(255),
    slot_number character varying(20),
    price numeric(6,2),
    status character varying(20),
    lister_id integer,
    branch character varying(50) DEFAULT 'Batu Pahat'::character varying,
    current_plate character varying(20),
    x_coord integer DEFAULT 0,
    y_coord integer DEFAULT 0
);


ALTER TABLE public.tbl_parking_listing OWNER TO postgres;

--
-- Name: tbl_parking_listing_parking_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tbl_parking_listing_parking_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tbl_parking_listing_parking_id_seq OWNER TO postgres;

--
-- Name: tbl_parking_listing_parking_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tbl_parking_listing_parking_id_seq OWNED BY public.tbl_parking_listing.parking_id;


--
-- Name: tbl_payment; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tbl_payment (
    payment_id integer NOT NULL,
    amount numeric(6,2),
    payment_date date,
    payment_status character varying(20),
    booking_id integer
);


ALTER TABLE public.tbl_payment OWNER TO postgres;

--
-- Name: tbl_payment_payment_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tbl_payment_payment_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tbl_payment_payment_id_seq OWNER TO postgres;

--
-- Name: tbl_payment_payment_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tbl_payment_payment_id_seq OWNED BY public.tbl_payment.payment_id;


--
-- Name: tbl_plate_display; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tbl_plate_display (
    display_id integer NOT NULL,
    plate_number character varying(20),
    parking_id integer,
    display_status character varying(20)
);


ALTER TABLE public.tbl_plate_display OWNER TO postgres;

--
-- Name: tbl_plate_display_display_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tbl_plate_display_display_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tbl_plate_display_display_id_seq OWNER TO postgres;

--
-- Name: tbl_plate_display_display_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tbl_plate_display_display_id_seq OWNED BY public.tbl_plate_display.display_id;


--
-- Name: tbl_reviews; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tbl_reviews (
    review_id integer CONSTRAINT tbl_review_review_id_not_null NOT NULL,
    rating integer,
    comment text,
    booking_id integer
);


ALTER TABLE public.tbl_reviews OWNER TO postgres;

--
-- Name: tbl_review_review_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tbl_review_review_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tbl_review_review_id_seq OWNER TO postgres;

--
-- Name: tbl_review_review_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tbl_review_review_id_seq OWNED BY public.tbl_reviews.review_id;


--
-- Name: tbl_user; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tbl_user (
    user_id integer NOT NULL,
    full_name character varying(100),
    email character varying(100),
    password character varying(255),
    role character varying(20),
    reset_token character varying(64),
    token_expires timestamp without time zone
);


ALTER TABLE public.tbl_user OWNER TO postgres;

--
-- Name: tbl_user_user_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tbl_user_user_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tbl_user_user_id_seq OWNER TO postgres;

--
-- Name: tbl_user_user_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tbl_user_user_id_seq OWNED BY public.tbl_user.user_id;


--
-- Name: tbl_booking booking_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tbl_booking ALTER COLUMN booking_id SET DEFAULT nextval('public.tbl_booking_booking_id_seq'::regclass);


--
-- Name: tbl_parking_listing parking_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tbl_parking_listing ALTER COLUMN parking_id SET DEFAULT nextval('public.tbl_parking_listing_parking_id_seq'::regclass);


--
-- Name: tbl_payment payment_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tbl_payment ALTER COLUMN payment_id SET DEFAULT nextval('public.tbl_payment_payment_id_seq'::regclass);


--
-- Name: tbl_plate_display display_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tbl_plate_display ALTER COLUMN display_id SET DEFAULT nextval('public.tbl_plate_display_display_id_seq'::regclass);


--
-- Name: tbl_reviews review_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tbl_reviews ALTER COLUMN review_id SET DEFAULT nextval('public.tbl_review_review_id_seq'::regclass);


--
-- Name: tbl_user user_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tbl_user ALTER COLUMN user_id SET DEFAULT nextval('public.tbl_user_user_id_seq'::regclass);


--
-- Data for Name: tbl_booking; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tbl_booking (booking_id, booking_date, start_time, end_time, booking_status, user_id, parking_id, plate_number, phone_number) FROM stdin;
2	2026-06-06	00:19:00	01:19:00	Cancelled	7	1	JMT1000	0172580818
3	2026-06-08	23:32:00	00:32:00	Cancelled	7	1	JMT1000	0172580818
4	2026-06-08	23:32:00	00:32:00	Cancelled	7	2	JMT1000	0172580818
5	2026-06-08	23:35:00	00:35:00	Completed	7	1	JMT1000	0172580818
6	2026-06-08	23:38:00	00:38:00	Cancelled	7	1	JMT1000	0172580818
7	2026-06-08	23:38:00	00:38:00	Cancelled	7	1	JMT1000	0172580818
8	2026-06-08	23:46:00	00:46:00	Cancelled	7	1	JMT1000	0172580818
9	2026-06-08	23:47:00	00:47:00	Cancelled	7	1	JMT1000	0172580818
10	2026-06-08	23:52:00	00:52:00	Completed	7	1	JMT1000	0172580818
11	2026-06-08	23:58:00	00:58:00	Cancelled	7	1	JMT1000	0172580818
12	2026-06-08	00:12:00	01:12:00	Cancelled	7	1	JMT1001	0172580818
13	2026-06-08	23:59:00	00:59:00	Cancelled	7	1	JMT1000	0172580818
1	2026-06-06	00:16:00	01:16:00	Cancelled	7	1	JMT1000	0172580818
14	2026-06-08	00:11:00	01:11:00	Cancelled	7	1	JMT1000	0172580818
15	2026-06-08	00:13:00	02:13:00	Cancelled	7	1	JMT1000	0172580818
16	2026-06-08	00:18:00	01:18:00	Cancelled	7	1	JMT1000	0172580818
17	2026-06-08	00:22:00	01:22:00	Cancelled	7	1	JMT1000	0172580818
18	2026-06-08	00:21:00	01:21:00	Cancelled	7	1	JMT1000	0172580818
19	2026-06-08	00:26:00	01:26:00	Cancelled	7	1	JMT1000	0172580818
20	2026-06-08	00:27:00	01:27:00	Cancelled	7	1	JMT1000	0172580818
21	2026-06-08	00:32:00	01:32:00	Cancelled	7	1	JMT1000	0172580818
22	2026-06-08	00:34:00	01:34:00	Cancelled	7	1	JMT1000	0172580818
23	2026-06-08	00:34:00	01:34:00	Cancelled	7	1	JMT1000	0172580818
24	2026-06-08	00:35:00	01:35:00	Cancelled	7	1	JMT1000	0172580818
25	2026-06-08	00:36:00	01:36:00	Cancelled	7	1	JMT1000	0172580818
26	2026-06-08	00:36:00	01:36:00	Cancelled	7	1	JMT1000	0172580818
40	2026-06-08	00:57:00	01:57:00	Cancelled	7	1	JMT1000	0172580818
27	2026-06-08	00:40:00	01:40:00	Cancelled	7	3	JMT1000	0172580818
28	2026-06-08	00:39:00	01:39:00	Cancelled	7	1	JMT1000	0172580818
29	2026-06-08	00:41:00	01:41:00	Cancelled	7	1	JMT1000	0172580818
30	2026-06-08	00:42:00	01:42:00	Cancelled	7	1	JMT1000	0172580818
31	2026-06-08	00:44:00	01:44:00	Cancelled	7	1	JMT1000	0172580818
32	2026-06-08	00:46:00	01:46:00	Cancelled	7	1	JMT1000	0172580818
33	2026-06-08	00:43:00	01:43:00	Cancelled	7	1	JMT1000	0172580818
34	2026-06-08	00:44:00	01:44:00	Cancelled	7	1	JMT1000	0172580818
35	2026-06-08	00:44:00	01:44:00	Cancelled	7	1	JMT1000	0172580818
36	2026-06-08	00:49:00	01:49:00	Cancelled	7	1	JMT1000	0172580818
37	2026-06-08	00:48:00	01:48:00	Cancelled	7	1	JMT1000	0172580818
38	2026-06-08	00:53:00	01:53:00	Cancelled	7	1	JMT1000	0172580818
39	2026-06-08	00:53:00	01:53:00	Cancelled	7	1	JMT1000	0172580818
41	2026-06-08	00:58:00	01:58:00	Cancelled	7	1	JMT1000	0172580818
42	2026-06-08	01:05:00	02:05:00	Cancelled	7	1	JMT1000	0172580818
43	2026-06-08	01:13:00	02:13:00	Cancelled	7	1	JMT1000	0172580818
44	2026-06-08	01:28:00	02:28:00	Cancelled	7	1	JMT1000	0172580818
45	2026-06-08	01:32:00	02:32:00	Cancelled	7	1	JMT1000	0172580818
46	2026-06-08	01:34:00	02:34:00	Cancelled	7	1	JMT1000	0172580818
47	2026-06-08	01:37:00	02:37:00	Cancelled	7	1	JMT1000	0172580818
48	2026-06-08	01:39:00	02:39:00	Cancelled	7	1	JMT1000	0172580818
49	2026-06-08	01:40:00	02:40:00	Cancelled	7	1	JMT1000	0172580818
50	2026-06-08	01:40:00	02:40:00	Cancelled	7	1	JMT1000	0172580818
51	2026-06-08	01:41:00	03:41:00	Cancelled	7	1	JMT1000	0172580818
52	2026-06-08	01:42:00	02:42:00	Cancelled	7	1	JMT1000	0172580818
53	2026-06-08	01:43:00	03:43:00	Cancelled	7	1	JMT1000	0172580818
54	2026-06-08	01:51:00	02:51:00	Cancelled	7	1	JMT1000	0172580818
55	2026-06-08	01:50:00	02:50:00	Cancelled	7	1	JMT1000	0172580818
56	2026-06-08	01:52:00	02:52:00	Cancelled	7	1	JMT1000	0172580818
57	2026-06-08	01:53:00	02:53:00	Cancelled	7	1	JMT1000	0172580818
58	2026-06-08	01:53:00	02:53:00	Cancelled	7	1	JMT1000	0172580818
59	2026-06-08	01:57:00	02:57:00	Cancelled	7	1	JMT1000	0172580818
60	2026-06-08	02:06:00	04:06:00	Cancelled	7	1	JMT1000	0172580818
61	2026-06-08	02:08:00	03:08:00	Cancelled	7	1	JMT1000	0172580818
62	2026-06-08	02:25:00	03:25:00	Cancelled	7	1	JMT1000	0172580818
63	2026-06-08	02:26:00	04:26:00	Cancelled	13	2	JMT1000	0172580818
64	2026-06-08	02:32:00	02:33:11.789059	Completed	7	1	JMT1000	0172580818
65	2026-06-08	02:33:00	03:33:00	Cancelled	7	1	JMT1000	0172580818
66	2026-06-08	02:34:00	04:34:00	Cancelled	7	1	JMT1000	0172580818
67	2026-06-09	11:16:00	12:16:00	Cancelled	7	1	JMT1000	0172580818
68	2026-06-09	11:23:00	12:23:00	Cancelled	7	1	JMT1000	0172580818
\.


--
-- Data for Name: tbl_parking_listing; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tbl_parking_listing (parking_id, location, slot_number, price, status, lister_id, branch, current_plate, x_coord, y_coord) FROM stdin;
3	Wing B	B01	2.00	Available	2	Batu Pahat	\N	83	25
4	Underground	U01	3.00	Available	2	Batu Pahat	\N	49	70
2	Wing A	A02	2.00	Available	2	Batu Pahat	\N	17	25
1	Wing A	A01	2.00	Available	2	Batu Pahat	\N	11	25
\.


--
-- Data for Name: tbl_payment; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tbl_payment (payment_id, amount, payment_date, payment_status, booking_id) FROM stdin;
\.


--
-- Data for Name: tbl_plate_display; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tbl_plate_display (display_id, plate_number, parking_id, display_status) FROM stdin;
\.


--
-- Data for Name: tbl_reviews; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tbl_reviews (review_id, rating, comment, booking_id) FROM stdin;
\.


--
-- Data for Name: tbl_user; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tbl_user (user_id, full_name, email, password, role, reset_token, token_expires) FROM stdin;
3	Driver One	driver@parking.com	driver123	Driver	\N	\N
4	Iman Fahmi 	iman@gmail.com	1234567	Driver	\N	\N
6	Ali	ali@gmail.com	1234567	Driver	\N	\N
8	Aman	aman@gmail.com	1234567	Lister	\N	\N
10	Aqil	aqil@gmail.com	aqil123	Driver	\N	\N
12	Amir	amir@gmail.com	aqil123	Lister	\N	\N
16	IMAN FAHMI BIN ROSLIN	imaniman@gmail.com	imanfahmi123	Driver	\N	\N
17	IMAN FAHMI BIN ROSLIN	if17@gmail.com	iman123	Driver	\N	\N
19	IMAN FAHMI BIN ROSLIN	ifbr@gmail.com	iman	Driver	\N	\N
20	IMAN FAHMI BIN ROSLIN	aku@gmail.com	aku	Driver	\N	\N
21	IMAN FAHMI BIN ROSLIN	iman2@gmail.com	imanfahmi17	Driver	\N	\N
22	IMAN FAHMI BIN ROSLIN	iman3@gmail.com	imanfahmi17	Driver	\N	\N
13	IMAN FAHMI	imanfahmi7171@gmail.com	$2y$10$lDsV/2LUBBQt2QnqjCEKceB5mjRH./oQieI..waC67EFqqY/Cecj6	Driver	\N	\N
28	IMAN FAHMI BIN ROSLIN	imanfahmi1717@gmail.com	$2y$10$0dxTj1TFIO00JXjwH4lHqem0QH8ZCyPgWNwCQs60qLMEGdgws68VO	Driver	793829520c4662734ec1845b8189be2ac16cc77655858a5cb9a49bad5d80e72d	2026-06-09 03:18:00
23	ALIF	alif17@gmail.com	imanfahmi17	Driver	\N	\N
24	mal	mal17@gmail.com	mal123	Lister	\N	\N
25	Amir	amir17@gmail.com	$2y$10$hbxbz2BcDgOeR8w3HB1.luQdk5eFlbunLj88mcWifl8Zx2GbcMZUe	Driver	\N	\N
1	System Admin	admin@parking.com	$2y$10$eJwZ4QbCQr./VxxHeXRPfeVrunJ21pSS1D1f9IiDLnB74dIgpMgUO	Admin	\N	\N
2	Lister One	lister@parking.com	$2y$10$P6P1gln6LeWcgze7NhtCZuvX9HJZDPssu3S57eDdWT.eLMgEHxNGC	Lister	\N	\N
26	Iman	iman17@gmail.com	$2y$10$RwQ8nfzTH7dxC888rhqnpOFNM3rB6u9JU9qE8eU4DaV7LW2lW0s7u	Driver	\N	\N
27	iman	iman1717@gmail.com	$2y$10$67R8SirFunW2qIMc.Hbr1OmHhvgkDxXPO9x3IZpWcmieqBmrO8khe	Driver	\N	\N
7	abu	abu@gmail.com	$2y$10$/0qr2kS0CkHloBGKgRspb.kYlGuca2UdFt0wAMs6p17Bk6c7A4ME6	Driver	\N	\N
\.


--
-- Name: tbl_booking_booking_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tbl_booking_booking_id_seq', 68, true);


--
-- Name: tbl_parking_listing_parking_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tbl_parking_listing_parking_id_seq', 4, true);


--
-- Name: tbl_payment_payment_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tbl_payment_payment_id_seq', 1, false);


--
-- Name: tbl_plate_display_display_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tbl_plate_display_display_id_seq', 1, false);


--
-- Name: tbl_review_review_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tbl_review_review_id_seq', 1, false);


--
-- Name: tbl_user_user_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tbl_user_user_id_seq', 28, true);


--
-- Name: tbl_booking tbl_booking_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tbl_booking
    ADD CONSTRAINT tbl_booking_pkey PRIMARY KEY (booking_id);


--
-- Name: tbl_parking_listing tbl_parking_listing_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tbl_parking_listing
    ADD CONSTRAINT tbl_parking_listing_pkey PRIMARY KEY (parking_id);


--
-- Name: tbl_payment tbl_payment_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tbl_payment
    ADD CONSTRAINT tbl_payment_pkey PRIMARY KEY (payment_id);


--
-- Name: tbl_plate_display tbl_plate_display_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tbl_plate_display
    ADD CONSTRAINT tbl_plate_display_pkey PRIMARY KEY (display_id);


--
-- Name: tbl_reviews tbl_review_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tbl_reviews
    ADD CONSTRAINT tbl_review_pkey PRIMARY KEY (review_id);


--
-- Name: tbl_user tbl_user_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tbl_user
    ADD CONSTRAINT tbl_user_email_key UNIQUE (email);


--
-- Name: tbl_user tbl_user_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tbl_user
    ADD CONSTRAINT tbl_user_pkey PRIMARY KEY (user_id);


--
-- Name: tbl_booking tbl_booking_parking_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tbl_booking
    ADD CONSTRAINT tbl_booking_parking_id_fkey FOREIGN KEY (parking_id) REFERENCES public.tbl_parking_listing(parking_id) ON DELETE CASCADE;


--
-- Name: tbl_booking tbl_booking_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tbl_booking
    ADD CONSTRAINT tbl_booking_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.tbl_user(user_id);


--
-- Name: tbl_parking_listing tbl_parking_listing_lister_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tbl_parking_listing
    ADD CONSTRAINT tbl_parking_listing_lister_id_fkey FOREIGN KEY (lister_id) REFERENCES public.tbl_user(user_id);


--
-- Name: tbl_payment tbl_payment_booking_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tbl_payment
    ADD CONSTRAINT tbl_payment_booking_id_fkey FOREIGN KEY (booking_id) REFERENCES public.tbl_booking(booking_id);


--
-- Name: tbl_plate_display tbl_plate_display_parking_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tbl_plate_display
    ADD CONSTRAINT tbl_plate_display_parking_id_fkey FOREIGN KEY (parking_id) REFERENCES public.tbl_parking_listing(parking_id) ON DELETE CASCADE;


--
-- Name: tbl_reviews tbl_review_booking_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tbl_reviews
    ADD CONSTRAINT tbl_review_booking_id_fkey FOREIGN KEY (booking_id) REFERENCES public.tbl_booking(booking_id);


--
-- PostgreSQL database dump complete
--

\unrestrict iN108D11isIKXCV4Jg5lFpTuP2OIcXtwcrY3wBqbcBj1bOFxLA6M7Me9pO5Gog3

