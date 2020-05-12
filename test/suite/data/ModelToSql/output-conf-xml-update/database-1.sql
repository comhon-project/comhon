
ALTER TABLE `database`.`db_constraint`
    DROP FOREIGN KEY db_constraint_ibfk_3,
    DROP FOREIGN KEY db_constraint_ibfk_4
;

ALTER TABLE `database`.`test_no_id`
    DROP FOREIGN KEY test_no_id_ibfk_1
;

ALTER TABLE house
    ADD `ghosts` TEXT,
    ADD `address` INT
;

ALTER TABLE db_constraint
    DROP foreign_constraint_not_in_model,
    DROP foreign_not_in_model_one,
    DROP foreign_not_in_model_two
;

ALTER TABLE man_body
    ADD `arts` TEXT
;

ALTER TABLE woman_body
    ADD `arts` TEXT
;

ALTER TABLE test_no_id
    ADD `value` TEXT,
    DROP foreign_constraint_not_in_model
;

CREATE TABLE three (
    `string` TEXT
);

ALTER TABLE test
    ADD `notLinkableArrayTestDb` TEXT,
    ADD `notLinkableTestDb` INT,
    ADD `notLinkableTestObjValue` VARCHAR(255)
;

CREATE TABLE version_2 (
    `id` INT,
    `serial_name` TEXT,
    `inheritanceKey` TEXT,
    PRIMARY KEY (`id`)
);

ALTER TABLE house
    ADD FOREIGN KEY (`address`) REFERENCES place(`id`);

ALTER TABLE town
    ADD FOREIGN KEY (`city_hall`) REFERENCES place(`id`);

ALTER TABLE place
    ADD FOREIGN KEY (`town`) REFERENCES town(`id`);

ALTER TABLE man_body
    ADD FOREIGN KEY (`owner_id`) REFERENCES `person`(`id`);

ALTER TABLE woman_body
    ADD FOREIGN KEY (`owner_id`) REFERENCES `person`(`id`);

ALTER TABLE child_test
    ADD FOREIGN KEY (`parent_id_1`, `parent_id_2`) REFERENCES test(`id_1`, `id_2`);

ALTER TABLE home
    ADD FOREIGN KEY (`person_id`) REFERENCES `person`(`id`);

ALTER TABLE home
    ADD FOREIGN KEY (`house_id`) REFERENCES house(`id_serial`);

ALTER TABLE `person`
    ADD FOREIGN KEY (`birth_place`) REFERENCES place(`id`);

ALTER TABLE `person`
    ADD FOREIGN KEY (`best_friend`) REFERENCES `person`(`id`);

ALTER TABLE `person`
    ADD FOREIGN KEY (`father_id`) REFERENCES `person`(`id`);

ALTER TABLE `person`
    ADD FOREIGN KEY (`mother_id`) REFERENCES `person`(`id`);

ALTER TABLE test
    ADD FOREIGN KEY (`main_test_id`) REFERENCES main_test(`id`);
