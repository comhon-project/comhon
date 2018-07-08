
CREATE TABLE public.house (
    "id_serial" INT,
    "surface" FLOAT,
    "type" TEXT,
    "garden" BOOL,
    "garage" BOOL,
    "address" INT,
    PRIMARY KEY ("id_serial")
);

CREATE TABLE public.home (
    "id" INT,
    "begin_date" TEXT,
    "end_date" TEXT,
    "person_id" INT,
    "house_id" INT,
    PRIMARY KEY ("id")
);

CREATE TABLE public.child_test (
    "id" INT,
    "name" TEXT,
    "parent_id_1" INT,
    "parent_id_2" TEXT,
    PRIMARY KEY ("id")
);

CREATE TABLE public.house (
    "id_serial" INT,
    "surface" FLOAT,
    "type" TEXT,
    "garden" BOOL,
    "garage" BOOL,
    PRIMARY KEY ("id_serial")
);

CREATE TABLE public.test_multi_increment (
    "id1" INT,
    "id2" INT,
    "plop" TEXT,
    PRIMARY KEY ("id1", "id2")
);

CREATE TABLE public.main_test (
    "id" INT,
    "name" TEXT,
    "obj" TEXT,
    PRIMARY KEY ("id")
);

CREATE TABLE public.test (
    "id_1" INT,
    "id_2" TEXT,
    "date" TIMESTAMP,
    "timestamp" TIMESTAMP,
    "object" TEXT,
    "object_with_id" TEXT,
    "string" TEXT,
    "integer" INT,
    "main_test_id" INT,
    "objects_with_id" TEXT,
    "foreign_objects" TEXT,
    "lonely_foreign_object" TEXT,
    "lonely_foreign_object_two" TEXT,
    "default_value" TEXT,
    "man_body_json_id" INT,
    "woman_xml_id" INT,
    "boolean" BOOL,
    "boolean2" BOOL,
    "notLinkableTestDb" INT,
    "notLinkableTestObjValue" TEXT,
    PRIMARY KEY ("id_1", "id_2")
);

CREATE TABLE public.town (
    "id" INT,
    "name" TEXT,
    "surface" INT,
    "city_hall" INT,
    PRIMARY KEY ("id")
);

CREATE TABLE public.place (
    "id" INT,
    "number" INT,
    "type" TEXT,
    "name" TEXT,
    "geographic_latitude" FLOAT,
    "geographic_longitude" FLOAT,
    "town" INT,
    PRIMARY KEY ("id")
);

CREATE TABLE public.person (
    "id" INT,
    "first_name" TEXT,
    "lastName" TEXT,
    "birth_date" TIMESTAMP,
    "birth_place" INT,
    "best_friend" INT,
    "father_id" INT,
    "mother_id" INT,
    PRIMARY KEY ("id")
);

ALTER TABLE public.house
    ADD CONSTRAINT fk_public_house_public_place
    FOREIGN KEY ("address") REFERENCES public.place("id");

ALTER TABLE public.home
    ADD CONSTRAINT fk_public_home_public_person
    FOREIGN KEY ("person_id") REFERENCES public.person("id");

ALTER TABLE public.home
    ADD CONSTRAINT fk_public_home_public_house
    FOREIGN KEY ("house_id") REFERENCES public.house("id_serial");

ALTER TABLE public.child_test
    ADD CONSTRAINT fk_public_child_test_public_test
    FOREIGN KEY ("parent_id_1", "parent_id_2") REFERENCES public.test("id_1", "id_2");

ALTER TABLE public.test
    ADD CONSTRAINT fk_public_test_public_main_test
    FOREIGN KEY ("main_test_id") REFERENCES public.main_test("id");

ALTER TABLE public.town
    ADD CONSTRAINT fk_public_town_public_place
    FOREIGN KEY ("city_hall") REFERENCES public.place("id");

ALTER TABLE public.place
    ADD CONSTRAINT fk_public_place_public_town
    FOREIGN KEY ("town") REFERENCES public.town("id");

ALTER TABLE public.person
    ADD CONSTRAINT fk_public_person_public_place
    FOREIGN KEY ("birth_place") REFERENCES public.place("id");

ALTER TABLE public.person
    ADD CONSTRAINT fk_public_person_public_person
    FOREIGN KEY ("best_friend") REFERENCES public.person("id");
