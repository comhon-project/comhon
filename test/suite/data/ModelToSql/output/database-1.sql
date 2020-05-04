
CREATE TABLE child_test (
    `id` INT,
    `name` TEXT,
    `parent_id_1` INT,
    `parent_id_2` VARCHAR(255),
    PRIMARY KEY (`id`)
);

CREATE TABLE public.version_2 (
    `id` INT,
    `serial_name` TEXT,
    `inheritanceKey` VARCHAR(255),
    PRIMARY KEY (`id`)
);
