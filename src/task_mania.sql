SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `completion` (
  `ID_COMPLETION` int(11) NOT NULL,
  `ID_TASK` int(11) NOT NULL,
  `SUBMITTED_BY` int(11) NOT NULL,
  `APPROVED_BY` int(11) DEFAULT NULL,
  `STATUS` enum('pending','approved','rejected') DEFAULT 'pending',
  `POINTS` int(11) NOT NULL,
  `AI_CONFIDENCE` int(11) DEFAULT NULL,
  `SUBMITTED_AT` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `household` (
  `ID_HOUSEHOLD` int(11) NOT NULL,
  `HOUSEHOLD_NAME` varchar(255) NOT NULL,
  `INVITE_LINK` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `household` (`ID_HOUSEHOLD`, `HOUSEHOLD_NAME`, `INVITE_LINK`) VALUES
(4, 'user1 household', 'b9591528b78b3e0192144ad38c414c1c'),
(5, 'user1 household', 'f3ec9f48e09adbc6c724bfe74afbc004'),
(6, 'household 1', '3667034d261e2d0d370b9ae69e901a88'),
(7, 'household 2', '9302aea6e3b00a9d9158d4338eae0067'),
(8, 'house 3', 'b73411c4ba8010448e5b915882767c7a'),
(9, 'house 5', '62cc9496323c8b51d88d44c7d6ddb7a6'),
(10, 'user10', 'a16de808724f9dfddf6388e7a9070512'),
(11, 'user10', '24c143ff41ac577e87ef97e77965a5ff'),
(12, 'house100', 'd762f0ad826c4dd1bae4089edcfde96a'),
(13, 'house200', '22f9aceeb18484317352dfb73fb1c8bf'),
(14, 'house of user2', 'b9b621ab175fa9b0fda528e9e2ad692d');

CREATE TABLE `household_member` (
  `ID_USER` int(11) NOT NULL,
  `ID_HOUSEHOLD` int(11) NOT NULL,
  `ROLE` enum('admin','member') NOT NULL DEFAULT 'member'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `household_member` (`ID_USER`, `ID_HOUSEHOLD`, `ROLE`) VALUES
(9, 4, 'admin'),
(9, 5, 'admin'),
(9, 6, 'admin'),
(9, 7, 'admin'),
(9, 8, 'admin'),
(9, 9, 'admin'),
(9, 10, 'admin'),
(9, 11, 'admin'),
(9, 12, 'admin'),
(9, 13, 'admin'),
(10, 12, 'member'),
(10, 14, 'admin');

CREATE TABLE `invitation` (
  `ID_INVITATION` int(11) NOT NULL,
  `ID_HOUSEHOLD` int(11) NOT NULL,
  `INVITED_EMAIL` varchar(300) NOT NULL,
  `INVITED_BY` int(11) NOT NULL,
  `STATUS` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `invitation` (`ID_INVITATION`, `ID_HOUSEHOLD`, `INVITED_EMAIL`, `INVITED_BY`, `STATUS`) VALUES
(8, 12, 'user2@gmail.com', 9, 'accepted'),
(9, 13, 'user2@gmail.com', 9, 'rejected'),
(10, 13, 'user3@gmail.com', 9, 'pending'),
(11, 14, 'user1@gmail.com', 10, 'rejected');

CREATE TABLE `notification` (
  `ID_NOTIFICATION` int(11) NOT NULL,
  `ID_USER` int(11) NOT NULL,
  `NOTIFICATION_TITLE` varchar(255) DEFAULT NULL,
  `NOTIFICATION_MESSAGE` varchar(1000) DEFAULT NULL,
  `IS_READ` tinyint(1) DEFAULT 0,
  `NOTIFICATION_CREATED` datetime DEFAULT current_timestamp(),
  `NOTIFICATION_TYPE` varchar(30) NOT NULL,
  `REFERENCE_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `notification` (`ID_NOTIFICATION`, `ID_USER`, `NOTIFICATION_TITLE`, `NOTIFICATION_MESSAGE`, `IS_READ`, `NOTIFICATION_CREATED`, `NOTIFICATION_TYPE`, `REFERENCE_ID`) VALUES
(3, 10, 'Household Invitation', 'You have been invited to join a household.', 1, '2025-11-20 21:44:32', 'invitation', 8),
(4, 10, 'Household Invitation', 'You have been invited to join a household.', 1, '2025-11-20 21:58:00', 'invitation', 9),
(5, 11, 'Household Invitation', 'You have been invited to join a household.', 0, '2025-11-20 21:58:00', 'invitation', 10),
(6, 9, 'Household Invitation', 'You have been invited to join a household.', 0, '2025-11-20 23:00:04', 'invitation', 11);

CREATE TABLE `points` (
  `ID_USER` int(11) NOT NULL,
  `ID_HOUSEHOLD` int(11) NOT NULL,
  `TOTAL_POINTS` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `rewards_catalogue` (
  `ID_REWARD` int(11) NOT NULL,
  `ID_HOUSEHOLD` int(11) NOT NULL,
  `ID_USER` int(11) DEFAULT NULL,
  `ID_COMPLETION` int(11) DEFAULT NULL,
  `REWARD_NAME` varchar(255) NOT NULL,
  `REWARD_DESCRIPTION` text DEFAULT NULL,
  `POINTS_TO_DISCOUNT` int(11) NOT NULL,
  `IS_ACTIVE` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `task` (
  `ID_TASK` int(11) NOT NULL,
  `ID_HOUSEHOLD` int(11) NOT NULL,
  `ID_USER` int(11) DEFAULT NULL,
  `TASK_NAME` varchar(255) NOT NULL,
  `TASK_DESCRIPTION` text DEFAULT NULL,
  `TASK_POINT` int(11) NOT NULL,
  `TASK_IMAGE` longblob DEFAULT NULL,
  `TASK_CREATED` datetime DEFAULT current_timestamp(),
  `TASK_STATUS` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user` (
  `ID_USER` int(11) NOT NULL,
  `USER_NAME` varchar(255) NOT NULL,
  `USER_EMAIL` varchar(255) NOT NULL,
  `USER_PASSWORD` varchar(255) NOT NULL,
  `AVATAR` longblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `user` (`ID_USER`, `USER_NAME`, `USER_EMAIL`, `USER_PASSWORD`, `AVATAR`) VALUES
(9, 'user1', 'user1@gmail.com', '$2y$10$YHJh0eEw5rJCNFfe7/APxeTPOLFmCv0g1DZgCAa1.5zJIBFen01yy', NULL),
(10, 'user2', 'user2@gmail.com', '$2y$10$G.TfPLO/k9aQErJ3g9ti6.A3SNGvASl7v7eveAGD63dhHG4ilyS5W', NULL),
(11, 'user3', 'user3@gmail.com', '$2y$10$HBLvBe5rpxutyCNJVWiZ1ug7Riy9ZEetCalNC9xyxoWKMJleSh/9i', NULL);


ALTER TABLE `completion`
  ADD PRIMARY KEY (`ID_COMPLETION`),
  ADD KEY `ID_TASK` (`ID_TASK`),
  ADD KEY `SUBMITTED_BY` (`SUBMITTED_BY`),
  ADD KEY `APPROVED_BY` (`APPROVED_BY`);

ALTER TABLE `household`
  ADD PRIMARY KEY (`ID_HOUSEHOLD`);

ALTER TABLE `household_member`
  ADD PRIMARY KEY (`ID_USER`,`ID_HOUSEHOLD`),
  ADD KEY `ID_HOUSEHOLD` (`ID_HOUSEHOLD`);

ALTER TABLE `invitation`
  ADD PRIMARY KEY (`ID_INVITATION`),
  ADD KEY `ID_HOUSEHOLD` (`ID_HOUSEHOLD`),
  ADD KEY `INVITED_BY` (`INVITED_BY`);

ALTER TABLE `notification`
  ADD PRIMARY KEY (`ID_NOTIFICATION`),
  ADD KEY `ID_USER` (`ID_USER`),
  ADD KEY `REFERENCE_ID` (`REFERENCE_ID`);

ALTER TABLE `points`
  ADD PRIMARY KEY (`ID_USER`,`ID_HOUSEHOLD`),
  ADD KEY `ID_HOUSEHOLD` (`ID_HOUSEHOLD`);

ALTER TABLE `rewards_catalogue`
  ADD PRIMARY KEY (`ID_REWARD`),
  ADD KEY `ID_HOUSEHOLD` (`ID_HOUSEHOLD`),
  ADD KEY `ID_USER` (`ID_USER`),
  ADD KEY `ID_COMPLETION` (`ID_COMPLETION`);

ALTER TABLE `task`
  ADD PRIMARY KEY (`ID_TASK`),
  ADD KEY `ID_HOUSEHOLD` (`ID_HOUSEHOLD`),
  ADD KEY `ID_USER` (`ID_USER`);

ALTER TABLE `user`
  ADD PRIMARY KEY (`ID_USER`),
  ADD UNIQUE KEY `USER_EMAIL` (`USER_EMAIL`);


ALTER TABLE `completion`
  MODIFY `ID_COMPLETION` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `household`
  MODIFY `ID_HOUSEHOLD` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

ALTER TABLE `invitation`
  MODIFY `ID_INVITATION` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

ALTER TABLE `notification`
  MODIFY `ID_NOTIFICATION` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `rewards_catalogue`
  MODIFY `ID_REWARD` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `task`
  MODIFY `ID_TASK` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `user`
  MODIFY `ID_USER` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;


ALTER TABLE `completion`
  ADD CONSTRAINT `completion_ibfk_1` FOREIGN KEY (`ID_TASK`) REFERENCES `task` (`ID_TASK`) ON DELETE CASCADE,
  ADD CONSTRAINT `completion_ibfk_2` FOREIGN KEY (`SUBMITTED_BY`) REFERENCES `user` (`ID_USER`),
  ADD CONSTRAINT `completion_ibfk_3` FOREIGN KEY (`APPROVED_BY`) REFERENCES `user` (`ID_USER`);

ALTER TABLE `household_member`
  ADD CONSTRAINT `household_member_ibfk_1` FOREIGN KEY (`ID_USER`) REFERENCES `user` (`ID_USER`) ON DELETE CASCADE,
  ADD CONSTRAINT `household_member_ibfk_2` FOREIGN KEY (`ID_HOUSEHOLD`) REFERENCES `household` (`ID_HOUSEHOLD`) ON DELETE CASCADE;

ALTER TABLE `invitation`
  ADD CONSTRAINT `invitation_ibfk_1` FOREIGN KEY (`ID_HOUSEHOLD`) REFERENCES `household` (`ID_HOUSEHOLD`),
  ADD CONSTRAINT `invitation_ibfk_2` FOREIGN KEY (`INVITED_BY`) REFERENCES `user` (`ID_USER`);

ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`ID_USER`) REFERENCES `user` (`ID_USER`);

ALTER TABLE `points`
  ADD CONSTRAINT `points_ibfk_1` FOREIGN KEY (`ID_USER`) REFERENCES `user` (`ID_USER`),
  ADD CONSTRAINT `points_ibfk_2` FOREIGN KEY (`ID_HOUSEHOLD`) REFERENCES `household` (`ID_HOUSEHOLD`);

ALTER TABLE `rewards_catalogue`
  ADD CONSTRAINT `rewards_catalogue_ibfk_1` FOREIGN KEY (`ID_HOUSEHOLD`) REFERENCES `household` (`ID_HOUSEHOLD`) ON DELETE CASCADE,
  ADD CONSTRAINT `rewards_catalogue_ibfk_2` FOREIGN KEY (`ID_USER`) REFERENCES `user` (`ID_USER`),
  ADD CONSTRAINT `rewards_catalogue_ibfk_3` FOREIGN KEY (`ID_COMPLETION`) REFERENCES `completion` (`ID_COMPLETION`);

ALTER TABLE `task`
  ADD CONSTRAINT `task_ibfk_1` FOREIGN KEY (`ID_HOUSEHOLD`) REFERENCES `household` (`ID_HOUSEHOLD`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_ibfk_2` FOREIGN KEY (`ID_USER`) REFERENCES `user` (`ID_USER`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
