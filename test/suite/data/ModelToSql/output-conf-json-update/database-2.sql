
ALTER TABLE "public"."db_constraint"
    DROP CONSTRAINT db_constraint_foreign_constraint_not_in_model_fkey,
    DROP CONSTRAINT db_constraint_foreign_not_in_model_one_fkey
;

ALTER TABLE "public"."test_no_id"
    DROP CONSTRAINT test_no_id_foreign_constraint_not_in_model_fkey
;

ALTER TABLE public.house
    ADD "ghosts" TEXT,
    ADD "address" INT
;

ALTER TABLE public.db_constraint
    DROP foreign_constraint_not_in_model,
    DROP foreign_not_in_model_one,
    DROP foreign_not_in_model_two
;

ALTER TABLE public.man_body
    ADD "arts" TEXT
;

ALTER TABLE public.woman_body
    ADD "arts" TEXT
;

ALTER TABLE public.test_no_id
    ADD "value" TEXT,
    DROP foreign_constraint_not_in_model
;

ALTER TABLE public.test
    ADD "notLinkableArrayTestDb" TEXT,
    ADD "notLinkableTestDb" INT,
    ADD "notLinkableTestObjValue" VARCHAR(255)
;

ALTER TABLE public.house
    ADD FOREIGN KEY ("address") REFERENCES public.place("id");

ALTER TABLE public.town
    ADD FOREIGN KEY ("city_hall") REFERENCES public.place("id");

ALTER TABLE public.test_private_id
    ADD FOREIGN KEY ("foreign_test_private_id") REFERENCES public.test_private_id("id");

ALTER TABLE public.place
    ADD FOREIGN KEY ("town") REFERENCES public.town("id");

ALTER TABLE public.man_body
    ADD FOREIGN KEY ("owner_id") REFERENCES "public"."person"("id");

ALTER TABLE public.woman_body
    ADD FOREIGN KEY ("owner_id") REFERENCES "public"."person"("id");

ALTER TABLE public.child_test
    ADD FOREIGN KEY ("parent_id_1", "parent_id_2") REFERENCES public.test("id_1", "id_2");

ALTER TABLE public.home
    ADD FOREIGN KEY ("person_id") REFERENCES "public"."person"("id");

ALTER TABLE public.home
    ADD FOREIGN KEY ("house_id") REFERENCES public.house("id_serial");

ALTER TABLE "public"."person"
    ADD FOREIGN KEY ("birth_place") REFERENCES public.place("id");

ALTER TABLE "public"."person"
    ADD FOREIGN KEY ("best_friend") REFERENCES "public"."person"("id");

ALTER TABLE "public"."person"
    ADD FOREIGN KEY ("father_id") REFERENCES "public"."person"("id");

ALTER TABLE "public"."person"
    ADD FOREIGN KEY ("mother_id") REFERENCES "public"."person"("id");

ALTER TABLE public.test
    ADD FOREIGN KEY ("main_test_id") REFERENCES public.main_test("id");
