
CREATE TABLE testNoSerialization (
    `id` INT,
    `name` TEXT,
    PRIMARY KEY (`id`)
);

CREATE TABLE testPrivateId (
    `id` TEXT,
    `name` TEXT,
    `object_values` TEXT,
    `foreign_object_value` INT,
    `foreign_object_values` TEXT,
    `foreign_test_private_id` TEXT,
    PRIMARY KEY (`id`)
);

CREATE TABLE body (
    `id` INT,
    `date` TIMESTAMP,
    `height` FLOAT,
    `weight` FLOAT,
    `hair_color` TEXT,
    `hair_cut` TEXT,
    `eyes_color` TEXT,
    `physical_appearance` TEXT,
    `tatoos` TEXT,
    `piercings` TEXT,
    `arts` TEXT,
    `owner_id` INT,
    PRIMARY KEY (`id`)
);

CREATE TABLE testRestricted (
    `color` TEXT,
    `emails` TEXT,
    `natural_number` INT,
    `birth_date` TIMESTAMP,
    `interval_in_array` FLOAT,
    `enum_value` TEXT,
    `enum_int_array` INT,
    `enum_float_array` FLOAT
);

ALTER TABLE testPrivateId
    ADD CONSTRAINT fk_testPrivateId_testPrivateId
    FOREIGN KEY (`foreign_test_private_id`) REFERENCES testPrivateId(`id`);
