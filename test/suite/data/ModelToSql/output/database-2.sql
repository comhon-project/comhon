
CREATE TABLE public.house (
    "id_serial" INT,
    "surface" FLOAT,
    "type" TEXT,
    "garden" BOOLEAN,
    "garage" BOOLEAN,
    "ghosts" TEXT,
    "address" INT,
    PRIMARY KEY ("id_serial")
);

CREATE TABLE public.town (
    "id" INT,
    "name" TEXT,
    "surface" INT,
    "city_hall" INT,
    PRIMARY KEY ("id")
);

CREATE TABLE public.test_private_id (
    "id" VARCHAR(255),
    "name" TEXT,
    "object_values" TEXT,
    "foreign_object_value" TEXT,
    "foreign_object_values" TEXT,
    "foreign_test_private_id" VARCHAR(255),
    "foreign_test_private_ids" TEXT,
    PRIMARY KEY ("id")
);

CREATE TABLE public.db_constraint (
    "id" INT,
    "unique_name" TEXT,
    "foreign_constraint" INT,
    "unique_one" INT,
    "unique_two" TEXT,
    "unique_foreign_one" INT,
    "unique_foreign_two" VARCHAR(255),
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

CREATE TABLE public.main_test (
    "id" INT,
    "name" TEXT,
    "obj" TEXT,
    PRIMARY KEY ("id")
);

CREATE TABLE public.man_body (
    "id" INT,
    "date" TIMESTAMP NULL,
    "height" FLOAT,
    "weight" FLOAT,
    "hair_color" TEXT,
    "hair_cut" TEXT,
    "eyes_color" TEXT,
    "physical_appearance" TEXT,
    "tatoos" TEXT,
    "piercings" TEXT,
    "arts" TEXT,
    "owner" INT,
    "baldness" BOOLEAN,
    PRIMARY KEY ("id")
);

CREATE TABLE public.woman_body (
    "id" INT,
    "date" TIMESTAMP NULL,
    "height" FLOAT,
    "weight" FLOAT,
    "hair_color" TEXT,
    "hair_cut" TEXT,
    "eyes_color" TEXT,
    "physical_appearance" TEXT,
    "tatoos" TEXT,
    "piercings" TEXT,
    "arts" TEXT,
    "owner_id" INT,
    "chest_size" TEXT,
    PRIMARY KEY ("id")
);

CREATE TABLE public.test_no_id (
    "name" TEXT,
    "value" TEXT
);

CREATE TABLE public.child_test (
    "id" INT,
    "name" TEXT,
    "parent_id_1" INT,
    "parent_id_2" VARCHAR(255),
    PRIMARY KEY ("id")
);

CREATE TABLE three (
    "string" TEXT
);

CREATE TABLE public.home (
    "id" INT,
    "begin_date" TEXT,
    "end_date" TEXT,
    "person_id" INT,
    "house_id" INT,
    PRIMARY KEY ("id")
);

CREATE TABLE public.test_multi_increment (
    "id1" INT,
    "id2" INT,
    "plop" TEXT,
    PRIMARY KEY ("id1", "id2")
);

CREATE TABLE "public"."person" (
    "id" INT,
    "first_name" TEXT,
    "last_name" TEXT,
    "birth_date" TIMESTAMP NULL,
    "birth_place" INT,
    "best_friend" INT,
    "father_id" INT,
    "mother_id" INT,
    "sex" VARCHAR(255),
    PRIMARY KEY ("id")
);

CREATE TABLE public.test (
    "id_1" INT,
    "id_2" VARCHAR(255),
    "date" TIMESTAMP NULL,
    "timestamp" TIMESTAMP NULL,
    "object" TEXT,
    "object_with_id" TEXT,
    "string" TEXT,
    "integer" INT,
    "main_test_id" INT,
    "objects_with_id" TEXT,
    "foreign_objects" TEXT,
    "lonely_foreign_object" VARCHAR(255),
    "lonely_foreign_object_two" VARCHAR(255),
    "default_value" TEXT,
    "man_body_json_id" INT,
    "woman_xml_id" INT,
    "boolean" BOOLEAN,
    "boolean2" BOOLEAN,
    "notLinkableArrayTestDb" TEXT,
    "notLinkableTestDb" INT,
    "notLinkableTestObjValue" VARCHAR(255),
    PRIMARY KEY ("id_1", "id_2")
);

ALTER TABLE public.house
    ADD CONSTRAINT fk_public_house_1
    FOREIGN KEY ("address") REFERENCES public.place("id");

ALTER TABLE public.town
    ADD CONSTRAINT fk_public_town_2
    FOREIGN KEY ("city_hall") REFERENCES public.place("id");

ALTER TABLE public.test_private_id
    ADD CONSTRAINT fk_public_test_private_id_3
    FOREIGN KEY ("foreign_test_private_id") REFERENCES public.test_private_id("id");

ALTER TABLE public.db_constraint
    ADD CONSTRAINT fk_public_db_constraint_4
    FOREIGN KEY ("foreign_constraint") REFERENCES public.db_constraint("id");

ALTER TABLE public.db_constraint
    ADD CONSTRAINT fk_public_db_constraint_5
    FOREIGN KEY ("unique_foreign_one", "unique_foreign_two") REFERENCES public.test("id_1", "id_2");

ALTER TABLE public.place
    ADD CONSTRAINT fk_public_place_6
    FOREIGN KEY ("town") REFERENCES public.town("id");

ALTER TABLE public.man_body
    ADD CONSTRAINT fk_public_man_body_7
    FOREIGN KEY ("owner") REFERENCES "public"."person"("id");

ALTER TABLE public.woman_body
    ADD CONSTRAINT fk_public_woman_body_8
    FOREIGN KEY ("owner_id") REFERENCES "public"."person"("id");

ALTER TABLE public.child_test
    ADD CONSTRAINT fk_public_child_test_9
    FOREIGN KEY ("parent_id_1", "parent_id_2") REFERENCES public.test("id_1", "id_2");

ALTER TABLE public.home
    ADD CONSTRAINT fk_public_home_10
    FOREIGN KEY ("person_id") REFERENCES "public"."person"("id");

ALTER TABLE public.home
    ADD CONSTRAINT fk_public_home_11
    FOREIGN KEY ("house_id") REFERENCES public.house("id_serial");

ALTER TABLE "public"."person"
    ADD CONSTRAINT fk_public_person_12
    FOREIGN KEY ("birth_place") REFERENCES public.place("id");

ALTER TABLE "public"."person"
    ADD CONSTRAINT fk_public_person_13
    FOREIGN KEY ("best_friend") REFERENCES "public"."person"("id");

ALTER TABLE "public"."person"
    ADD CONSTRAINT fk_public_person_14
    FOREIGN KEY ("father_id") REFERENCES "public"."person"("id");

ALTER TABLE "public"."person"
    ADD CONSTRAINT fk_public_person_15
    FOREIGN KEY ("mother_id") REFERENCES "public"."person"("id");

ALTER TABLE public.test
    ADD CONSTRAINT fk_public_test_16
    FOREIGN KEY ("main_test_id") REFERENCES public.main_test("id");
