-- phpMyAdmin SQL Dump
-- version 4.6.6deb5
-- https://www.phpmyadmin.net/
--
-- Client :  localhost:3306
-- Généré le :  Lun 30 Décembre 2019 à 02:48
-- Version du serveur :  5.7.28-0ubuntu0.18.04.4
-- Version de PHP :  7.2.24-0ubuntu0.18.04.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `database`
--

/*ALTER TABLE child_test
    DROP FOREIGN KEY child_test_ibfk_1
;
ALTER TABLE home
    DROP FOREIGN KEY home_ibfk_1,
    DROP FOREIGN KEY home_ibfk_2
;
ALTER TABLE house
    DROP FOREIGN KEY house_ibfk_1
;
ALTER TABLE man_body
    DROP FOREIGN KEY man_body_ibfk_1
;
ALTER TABLE woman_body
    DROP FOREIGN KEY woman_body_ibfk_1
;
ALTER TABLE person
    DROP FOREIGN KEY person_ibfk_1,
    DROP FOREIGN KEY person_ibfk_2,
    DROP FOREIGN KEY person_ibfk_3,
    DROP FOREIGN KEY person_ibfk_4
;
ALTER TABLE place
    DROP FOREIGN KEY place_ibfk_1
;
ALTER TABLE test
    DROP FOREIGN KEY test_ibfk_1
;
ALTER TABLE town
    DROP FOREIGN KEY town_ibfk_1
;*/


DROP TABLE IF EXISTS child_test;
DROP TABLE IF EXISTS test_no_id;
DROP TABLE IF EXISTS db_constraint;
DROP TABLE IF EXISTS home;
DROP TABLE IF EXISTS house;
DROP TABLE IF EXISTS test;
DROP TABLE IF EXISTS main_test;
DROP TABLE IF EXISTS man_body;
DROP TABLE IF EXISTS person;
DROP TABLE IF EXISTS place;
DROP TABLE IF EXISTS test_multi_increment;
DROP TABLE IF EXISTS town;
DROP TABLE IF EXISTS woman_body;
DROP TABLE IF EXISTS test_private_id;

-- --------------------------------------------------------

--
-- Structure de la table `child_test`
--

CREATE TABLE `child_test` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `parent_id_1` int(11) NOT NULL,
  `parent_id_2` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Contenu de la table `child_test`
--

INSERT INTO `child_test` (`id`, `name`, `parent_id_1`, `parent_id_2`) VALUES
(1, 'plop', 1, '1501774389'),
(2, 'plop2', 1, '1501774389');

-- --------------------------------------------------------

--
-- Structure de la table `db_constraint`
--

CREATE TABLE `db_constraint` (
  `id` int(11) NOT NULL,
  `unique_name` varchar(32) CHARACTER SET utf8 NOT NULL,
  `foreign_constraint` int(11) DEFAULT NULL,
  `unique_one` int(11) DEFAULT NULL,
  `unique_two` varchar(32) DEFAULT NULL,
  `unique_foreign_one` int(11) DEFAULT NULL,
  `unique_foreign_two` varchar(32) DEFAULT NULL,
  `foreign_constraint_not_in_model` int(11) DEFAULT NULL,
  `foreign_not_in_model_one` int(11) DEFAULT NULL,
  `foreign_not_in_model_two` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `home`
--

CREATE TABLE `home` (
  `id` int(11) NOT NULL,
  `begin_date` text CHARACTER SET utf8 NOT NULL,
  `end_date` text CHARACTER SET utf8 NOT NULL,
  `person_id` int(11) NOT NULL,
  `house_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Contenu de la table `home`
--

INSERT INTO `home` (`id`, `begin_date`, `end_date`, `person_id`, `house_id`) VALUES
(1, '16/09/1988', '16/09/1989', 1, 1),
(2, '16/09/1990', '16/09/1995', 1, 2),
(3, '16/09/1955', '16/09/1960', 6, 3),
(4, '18/09/1988', '22/12/1988', 5, 4),
(5, '16/12/1988', '16/09/1989', 5, 5),
(6, '01/01/70', '16/09/1995', 1, 6);

-- --------------------------------------------------------

--
-- Structure de la table `house`
--

CREATE TABLE `house` (
  `id_serial` int(11) NOT NULL,
  `surface` DECIMAL(20,10) NOT NULL,
  `type` text CHARACTER SET utf8 NOT NULL,
  `garden` tinyint(1) NOT NULL,
  `garage` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Contenu de la table `house`
--

INSERT INTO `house` (`id_serial`, `surface`, `type`, `garden`, `garage`) VALUES
(1, 120, 'T4', 1, 1),
(2, 90, 'T4', 0, 0),
(3, 50, 'T1', 0, 0),
(4, 55, 'T2', 0, 0),
(5, 200, 'T4', 1, 1),
(6, 300, 'T6', 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `main_test`
--

CREATE TABLE `main_test` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `obj` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Contenu de la table `main_test`
--

INSERT INTO `main_test` (`id`, `name`, `obj`) VALUES
(1, 'azeaze', NULL),
(2, 'qsdqsd', '{\"plop\":\"ploooop\",\"plop2\":\"ploooop2\"}');

-- --------------------------------------------------------

--
-- Structure de la table `man_body`
--

CREATE TABLE `man_body` (
  `id` int(11) NOT NULL,
  `height` DECIMAL(20,10) NOT NULL,
  `weight` DECIMAL(20,10) NOT NULL,
  `hair_color` text NOT NULL,
  `hair_cut` text NOT NULL,
  `eyes_color` text NOT NULL,
  `physical_appearance` text NOT NULL,
  `tatoos` text NOT NULL,
  `piercings` text NOT NULL,
  `owner_id` int(11) NOT NULL,
  `baldness` tinyint(1) NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Contenu de la table `man_body`
--

INSERT INTO `man_body` (`id`, `height`, `weight`, `hair_color`, `hair_cut`, `eyes_color`, `physical_appearance`, `tatoos`, `piercings`, `owner_id`, `baldness`, `date`) VALUES
(1, 1.8, 80, 'black', 'short', 'blue', 'muscular', '[{\"type\":\"tribal\",\"location\":\"shoulder\",\"tatooArtist\":2}]', '', 1, 0, '2018-07-12 23:04:27'),
(2, 1.8, 80, 'black', 'short', 'blue', 'slim', '[{\"type\":\"tribal\",\"location\":\"shoulder\",\"tatooArtist\":2},{\"type\":\"tribal\",\"location\":\"leg\",\"tatooArtist\":3}]', '', 1, 1, '2018-07-12 23:04:41');

-- --------------------------------------------------------

--
-- Structure de la table `person`
--

CREATE TABLE `person` (
  `id` int(11) NOT NULL,
  `first_name` text CHARACTER SET utf8,
  `last_name` text CHARACTER SET utf8,
  `sex` text CHARACTER SET utf8 NOT NULL,
  `birth_place` int(11) DEFAULT NULL,
  `father_id` int(11) DEFAULT NULL,
  `mother_id` int(11) DEFAULT NULL,
  `birth_date` timestamp NULL DEFAULT NULL,
  `best_friend` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Contenu de la table `person`
--

INSERT INTO `person` (`id`, `first_name`, `last_name`, `sex`, `birth_place`, `father_id`, `mother_id`, `birth_date`, `best_friend`) VALUES
(1, 'Bernard', 'Dupond', 'Test\\Person\\Man', 2, NULL, NULL, '2016-11-13 19:04:05', NULL),
(2, 'Marie', 'Smith', 'Test\\Person\\Woman', NULL, NULL, NULL, '2016-11-13 19:04:05', 5),
(5, 'Jean', 'Henri', 'Test\\Person\\Man', NULL, 1, 2, '2016-11-13 19:04:05', 7),
(6, 'john', 'lennon', 'Test\\Person\\Man', NULL, 1, 2, '2016-11-13 19:04:05', NULL),
(7, 'lois', 'lane', 'Test\\Person\\Woman', NULL, NULL, NULL, '2016-11-13 19:02:59', NULL),
(8, 'louise', 'truc', 'Test\\Person\\Woman', NULL, 6, 7, NULL, 9),
(9, 'lala', 'truc', 'Test\\Person\\Woman', NULL, 6, 7, NULL, NULL),
(10, 'plop', 'plop', 'Test\\Person\\Woman', NULL, 5, 7, NULL, NULL),
(11, 'Naelya', 'Dupond', 'Test\\Person\\Woman', 2, 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `place`
--

CREATE TABLE `place` (
  `id` int(11) NOT NULL,
  `number` int(11) DEFAULT NULL,
  `type` varchar(31) CHARACTER SET utf8 DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `geographic_latitude` DECIMAL(20,10) DEFAULT NULL,
  `geographic_longitude` DECIMAL(20,10) DEFAULT NULL,
  `town` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Contenu de la table `place`
--

INSERT INTO `place` (`id`, `number`, `type`, `name`, `geographic_latitude`, `geographic_longitude`, `town`) VALUES
(1, 1, 'square', 'George Frêche', NULL, NULL, 1),
(2, 16, 'street', 'Trocmé', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Structure de la table `test`
--

CREATE TABLE `test` (
  `id_1` int(11) NOT NULL,
  `id_2` varchar(32) NOT NULL,
  `date` datetime DEFAULT NULL,
  `object` text CHARACTER SET utf8,
  `object_with_id` text,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `integer` int(11) DEFAULT NULL,
  `string` text,
  `main_test_id` int(11) NOT NULL,
  `objects_with_id` varchar(1024) NOT NULL DEFAULT '[]',
  `foreign_objects` varchar(1024) NOT NULL DEFAULT '[]',
  `lonely_foreign_object` text,
  `lonely_foreign_object_two` text,
  `default_value` text,
  `woman_xml_id` int(11) DEFAULT NULL,
  `man_body_json_id` int(11) DEFAULT NULL,
  `boolean` tinyint(1) NOT NULL,
  `boolean2` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Contenu de la table `test`
--

INSERT INTO `test` (`id_1`, `id_2`, `date`, `object`, `object_with_id`, `timestamp`, `integer`, `string`, `main_test_id`, `objects_with_id`, `foreign_objects`, `lonely_foreign_object`, `lonely_foreign_object_two`, `default_value`, `woman_xml_id`, `man_body_json_id`, `boolean`, `boolean2`) VALUES
(1, '101', '2016-04-13 07:14:33', '{\"plop\":\"plop\",\"plop2\":\"plop2\"}', '{\"plop\":\"plop\",\"plop2\":\"plop2\"}', '2016-10-16 19:50:19', 2, 'cccc', 1, '[]', '[]', NULL, NULL, 'default', NULL, NULL, 0, 1),
(1, '1501774389', '2016-04-12 03:14:33', '{\"plop\":\"plop\",\"plop2\":\"plop2\"}', '{\"plop\":\"plop\",\"plop2\":\"plop2\"}', '2016-10-13 09:50:19', 2, 'nnnn', 1, '[{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"plop4\":\"heyplop4\",\"inheritance-\":\"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\",\"plop3\":\"heyplop3\",\"inheritance-\":\"Test\\\\TestDb\\\\ObjectWithIdAndMore\"},{\"plop\":\"1\",\"plop2\":\"heyplop2\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\"},{\"plop\":\"11\",\"plop2\":\"heyplop22\",\"plop3\":\"heyplop33\",\"inheritance-\":\"Test\\\\TestDb\\\\ObjectWithIdAndMore\"}]', '[{\"id\":\"1\",\"inheritance-\":\"Test\\\\TestDb\\\\ObjectWithIdAndMoreMore\"},{\"id\":\"1\",\"inheritance-\":\"Test\\\\TestDb\\\\ObjectWithIdAndMore\"},\"1\",\"11\",{\"id\":\"11\",\"inheritance-\":\"Test\\\\TestDb\\\\ObjectWithIdAndMore\"}]', '{\"id\":\"11\",\"inheritance-\":\"Test\\\\TestDb\\\\ObjectWithIdAndMore\"}', '11', 'default', NULL, NULL, 0, 1),
(1, '23', '2016-05-01 12:53:54', NULL, NULL, '2016-10-16 19:50:19', 0, 'aaaa', 1, '[]', '[]', NULL, NULL, 'default', NULL, NULL, 0, 1),
(1, '50', '2016-10-16 18:21:18', '{\"plop\":\"plop\",\"plop2\":\"plop2222\"}', '{\"plop\":\"plop\",\"plop2\":\"plop2222\"}', '2016-10-16 19:50:19', 1, 'bbbb', 1, '[]', '[]', NULL, NULL, 'default', NULL, NULL, 0, 1),
(2, '102', '2016-04-01 06:00:00', '{\"plop\":\"plop10\",\"plop2\":\"plop20\"}', NULL, '2016-10-16 16:21:18', 4, 'eeee', 1, '[]', '[]', NULL, NULL, 'default', NULL, NULL, 0, 1),
(2, '50', '2016-05-01 21:37:18', '{\"plop\":\"plop\",\"plop2\":\"plop2222\"}', '{\"plop\":\"plop\",\"plop2\":\"plop2222\"}', '2016-10-16 19:50:19', 3, 'dddd', 1, '[]', '[]', NULL, NULL, 'default', NULL, NULL, 0, 1),
(3, '50', '2016-05-01 21:39:29', '{\"plop\":\"plop\",\"plop2\":\"plop2222\"}', NULL, '2016-10-16 16:21:18', 5, 'ffff', 2, '[]', '[]', NULL, NULL, 'default', NULL, NULL, 0, 1),
(4, '50', '2016-05-09 23:56:36', '{\"plop\":\"plop\",\"plop2\":\"plop2222\"}', NULL, '2016-10-16 16:21:18', 6, 'gggg', 2, '[]', '[]', NULL, NULL, 'default', 4, 4567, 0, 1),
(40, '50', '2016-05-09 23:50:20', '{\"plop\":\"plop\",\"plop2\":\"plop2222\"}', NULL, '2016-10-16 16:21:18', 7, 'hhhh', 2, '[]', '[]', NULL, NULL, 'default', 3, 1567, 0, 1);

-- --------------------------------------------------------

--
-- Structure de la table `test_multi_increment`
--

CREATE TABLE `test_multi_increment` (
  `id1` int(11) NOT NULL,
  `plop` text,
  `id2` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Contenu de la table `test_multi_increment`
--

INSERT INTO `test_multi_increment` (`id1`, `plop`, `id2`) VALUES
(1, 'lalala', 0),
(2, 'lalala2', 0),
(3, 'hehe', 0),
(4, 'hoho', 0),
(5, 'hoho', 0),
(6, 'hoho', 0),
(7, 'hoho', 0),
(8, 'hoho', 45),
(9, 'hoho', 45),
(10, 'hoho', 45),
(11, 'hoho', 45),
(12, 'hoho', 45),
(13, 'hoho', 45),
(14, 'hoho', 45),
(15, 'hohohohoho', 45),
(16, 'hoho', 45);

-- --------------------------------------------------------

--
-- Structure de la table `test_no_id`
--

CREATE TABLE `test_no_id` (
  `name` text,
  `foreign_constraint_not_in_model` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Contenu de la table `test_no_id`
--

INSERT INTO `test_no_id` (`name`, `foreign_constraint_not_in_model`) VALUES
('a', NULL),
('b', NULL),
('c', NULL),
('d', NULL),
('e', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `test_private_id`
--

CREATE TABLE `test_private_id` (
  `id` text NOT NULL,
  `name` text,
  `object_values` text,
  `foreign_object_value` text,
  `foreign_object_values` text,
  `foreign_test_private_id` text,
  `foreign_test_private_ids` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Contenu de la table `test_private_id`
--

INSERT INTO `test_private_id` (`id`, `name`, `object_values`, `foreign_object_value`, `foreign_object_values`, `foreign_test_private_id`, `foreign_test_private_ids`) VALUES
('id1', 'a', NULL, NULL, NULL, NULL, NULL),
('id2', 'b', NULL, NULL, NULL, NULL, NULL),
('id3', 'c', NULL, NULL, NULL, NULL, NULL),
('id4', 'd', NULL, NULL, NULL, NULL, NULL),
('id5', 'e', NULL, NULL, NULL, NULL, NULL),
('id6', 'f', NULL, NULL, NULL, NULL, NULL),
('id7', 'g', NULL, NULL, NULL, NULL, NULL),
('id8', 'h', NULL, NULL, NULL, NULL, NULL),
('id9', 'i', NULL, NULL, NULL, NULL, NULL),
('id10', 'j', NULL, NULL, NULL, NULL, NULL),
('id11', 'k', NULL, NULL, NULL, NULL, NULL),
('id12', 'l', NULL, NULL, NULL, NULL, NULL),
('id13', 'm', NULL, NULL, NULL, NULL, NULL),
('id14', 'n', NULL, NULL, NULL, NULL, NULL),
('id15', 'o', NULL, NULL, NULL, NULL, NULL),
('id16', 'p', NULL, NULL, NULL, NULL, NULL),
('id17', 'q', NULL, NULL, NULL, NULL, NULL),
('id18', 'r', NULL, NULL, NULL, NULL, NULL),
('id19', 's', NULL, NULL, NULL, NULL, NULL),
('id20', 't', NULL, NULL, NULL, NULL, NULL),
('id21', 'u', NULL, NULL, NULL, NULL, NULL),
('id22', 'v', NULL, NULL, NULL, NULL, NULL),
('id23', 'w', NULL, NULL, NULL, NULL, NULL),
('id24', 'x', NULL, NULL, NULL, NULL, NULL),
('id25', 'y', NULL, NULL, NULL, NULL, NULL),
('id26', 'z', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `town`
--

CREATE TABLE `town` (
  `id` int(11) NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `surface` int(11) DEFAULT NULL,
  `city_hall` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Contenu de la table `town`
--

INSERT INTO `town` (`id`, `name`, `surface`, `city_hall`) VALUES
(1, 'Montpellier', NULL, 1);

-- --------------------------------------------------------

--
-- Structure de la table `woman_body`
--

CREATE TABLE `woman_body` (
  `id` int(11) NOT NULL,
  `height` DECIMAL(20,10) NOT NULL,
  `weight` DECIMAL(20,10) NOT NULL,
  `hair_color` text NOT NULL,
  `hair_cut` text NOT NULL,
  `eyes_color` text NOT NULL,
  `physical_appearance` text NOT NULL,
  `tatoos` text NOT NULL,
  `piercings` text NOT NULL,
  `owner_id` int(11) NOT NULL,
  `chest_size` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Contenu de la table `woman_body`
--

INSERT INTO `woman_body` (`id`, `height`, `weight`, `hair_color`, `hair_cut`, `eyes_color`, `physical_appearance`, `tatoos`, `piercings`, `owner_id`, `chest_size`, `date`) VALUES
(1, 1.65, 60, 'black', 'long', 'green', 'athletic', '[{\"type\":\"sentence\",\"location\":\"shoulder\",\"tatooArtist\":5},{\"type\":\"sentence\",\"location\":\"arm\",\"tatooArtist\":6},{\"type\":\"sentence\",\"location\":\"leg\",\"tatooArtist\":5}]', '[{\"type\":\"earring\",\"location\":\"ear\",\"piercer\":5},{\"type\":\"earring\",\"location\":\"ear\",\"piercer\":6},{\"type\":\"clasp\",\"location\":\"eyebrow\",\"piercer\":5}]', 2, '90-B', '2017-06-27 22:23:17');

--
-- Index pour les tables exportées
--

--
-- Index pour la table `child_test`
--
ALTER TABLE `child_test`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `db_constraint`
--
ALTER TABLE `db_constraint`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_name` (`unique_name`),
  ADD UNIQUE KEY `unique_one` (`unique_one`,`unique_two`),
  ADD UNIQUE KEY `unique_foreign_one` (`unique_foreign_one`,`unique_foreign_two`);

--
-- Index pour la table `home`
--
ALTER TABLE `home`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `house`
--
ALTER TABLE `house`
  ADD PRIMARY KEY (`id_serial`);

--
-- Index pour la table `main_test`
--
ALTER TABLE `main_test`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `man_body`
--
ALTER TABLE `man_body`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `person`
--
ALTER TABLE `person`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `place`
--
ALTER TABLE `place`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `test`
--
ALTER TABLE `test`
  ADD PRIMARY KEY (`id_1`,`id_2`);

--
-- Index pour la table `test_multi_increment`
--
ALTER TABLE `test_multi_increment`
  ADD PRIMARY KEY (`id1`,`id2`);

--
-- Index pour la table `test_private_id`
--
ALTER TABLE `test_private_id`
  ADD PRIMARY KEY (`id`(10));

--
-- Index pour la table `town`
--
ALTER TABLE `town`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `woman_body`
--
ALTER TABLE `woman_body`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `child_test`
--
ALTER TABLE `child_test`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT pour la table `db_constraint`
--
ALTER TABLE `db_constraint`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `home`
--
ALTER TABLE `home`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT pour la table `house`
--
ALTER TABLE `house`
  MODIFY `id_serial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT pour la table `main_test`
--
ALTER TABLE `main_test`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT pour la table `man_body`
--
ALTER TABLE `man_body`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT pour la table `person`
--
ALTER TABLE `person`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT pour la table `place`
--
ALTER TABLE `place`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT pour la table `test_multi_increment`
--
ALTER TABLE `test_multi_increment`
  MODIFY `id1` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
--
-- AUTO_INCREMENT pour la table `town`
--
ALTER TABLE `town`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT pour la table `woman_body`
--
ALTER TABLE `woman_body`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `db_constraint`
--
ALTER TABLE `db_constraint`
  ADD CONSTRAINT `db_constraint_ibfk_1` FOREIGN KEY (`foreign_constraint`) REFERENCES `db_constraint` (`id`),
  ADD CONSTRAINT `db_constraint_ibfk_2` FOREIGN KEY (`unique_foreign_one`,`unique_foreign_two`) REFERENCES `test` (`id_1`, `id_2`),
  ADD CONSTRAINT `db_constraint_ibfk_3` FOREIGN KEY (`foreign_constraint_not_in_model`) REFERENCES `db_constraint` (`id`),
  ADD CONSTRAINT `db_constraint_ibfk_4` FOREIGN KEY (`foreign_not_in_model_one`,`foreign_not_in_model_two`) REFERENCES `test` (`id_1`, `id_2`);

--
-- Contraintes pour la table `test_no_id`
--
ALTER TABLE `test_no_id`
  ADD CONSTRAINT `test_no_id_ibfk_1` FOREIGN KEY (`foreign_constraint_not_in_model`) REFERENCES `db_constraint` (`id`);


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
