-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 11, 2026 at 07:10 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hr-management`
--

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `temporary_password` varchar(250) NOT NULL,
  `department` varchar(100) NOT NULL,
  `designation` varchar(150) NOT NULL,
  `basic_salary` decimal(10,0) NOT NULL,
  `joining_date` date NOT NULL,
  `role` enum('admin','hr','employee','') NOT NULL,
  `phone` varchar(15) NOT NULL,
  `user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `name`, `email`, `temporary_password`, `department`, `designation`, `basic_salary`, `joining_date`, `role`, `phone`, `user_id`) VALUES
(7, 'admin', 'admin@gmail.com', '827ccb0eea8a706c4c34a16891f84e7b', 'admin', 'admin', 50000, '2026-11-01', 'admin', '01753031073', 7),
(8, 'employee', 'employee@gmail.com', '827ccb0eea8a706c4c34a16891f84e7b', 'Accounts', 'Ass. Accounts', 30000, '2026-11-01', 'employee', '+8801753031073', 8),
(9, 'rokon uddin', 'rokonudden6969@gmail.com', '827ccb0eea8a706c4c34a16891f84e7b', 'Accounts', 'Ass. Accounts', 25000, '2026-11-01', 'employee', '+8801753031073', 9);

--
-- Triggers `employees`
--
DELIMITER $$
CREATE TRIGGER `after_employee_insert` BEFORE INSERT ON `employees` FOR EACH ROW BEGIN
    INSERT INTO users (name, email, password, role)
    VALUES (NEW.name, NEW.email, NEW.temporary_password, NEW.role);

    SET NEW.user_id = LAST_INSERT_ID();
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_user` AFTER UPDATE ON `employees` FOR EACH ROW BEGIN
	IF NEW.user_id IS NOT NULL THEN UPDATE users 
    SET name = NEW.name,  
    	email = COALESCE(NEW.email, email) WHERE user_id = NEW.user_id;
        END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `leave_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `leave_reason` varchar(200) NOT NULL,
  `leave_type` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int NOT NULL,
  `status` varchar(20) NOT NULL,
  `approved_by` varchar(50) NOT NULL,
  `admin_remarks` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(250) NOT NULL COMMENT 'md5',
  `role` enum('admin','hr','employee') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`) VALUES
(7, 'admin', 'admin@gmail.com', '827ccb0eea8a706c4c34a16891f84e7b', 'admin'),
(8, 'employee', 'employee@gmail.com', '827ccb0eea8a706c4c34a16891f84e7b', 'employee'),
(9, 'rokon uddin', 'rokonudden6969@gmail.com', '827ccb0eea8a706c4c34a16891f84e7b', 'employee');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD KEY `fk_employees_user` (`user_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`leave_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `leave_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employees_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
