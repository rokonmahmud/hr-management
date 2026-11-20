-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 20, 2026 at 07:11 PM
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
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `attendance_date` date NOT NULL,
  `clock_in_time` datetime NOT NULL,
  `clock_out_time` datetime DEFAULT NULL,
  `clock_in_ip_address` varchar(45) NOT NULL,
  `clock_out_ip_address` varchar(45) DEFAULT NULL,
  `total_duration` time DEFAULT NULL,
  `is_late` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('Present','Completed') NOT NULL DEFAULT 'Present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `employee_id`, `attendance_date`, `clock_in_time`, `clock_out_time`, `clock_in_ip_address`, `clock_out_ip_address`, `total_duration`, `is_late`, `status`) VALUES
(1, 11, '2026-11-19', '2026-11-19 20:20:11', '2026-11-19 20:20:12', '::1', '::1', '00:00:01', 1, 'Completed');

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
  `birth_date` date DEFAULT NULL,
  `role` enum('admin','hr','employee','') NOT NULL,
  `phone` varchar(15) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `name`, `email`, `temporary_password`, `department`, `designation`, `basic_salary`, `joining_date`, `birth_date`, `role`, `phone`, `profile_picture`, `user_id`) VALUES
(7, 'admin', 'admin@gmail.com', '827ccb0eea8a706c4c34a16891f84e7b', 'admin', 'admin', 50000, '2026-11-01', NULL, 'admin', '01753031073', NULL, 7),
(8, 'employee', 'employee@gmail.com', '827ccb0eea8a706c4c34a16891f84e7b', 'Accounts', 'Ass. Accounts', 30000, '2026-11-01', '1995-11-18', 'employee', '+8801753031073', NULL, 8),
(9, 'rokon uddin', 'rokonudden6969@gmail.com', '827ccb0eea8a706c4c34a16891f84e7b', 'Accounts', 'Ass. Accounts', 25000, '2026-11-01', '1996-11-19', 'employee', '+8801753031073', NULL, 9),
(11, 'sunny', 'sunny@gmail.com', '827ccb0eea8a706c4c34a16891f84e7b', 'EEE', 'Jr. Instructor', 18344, '2026-11-19', '2026-11-19', 'employee', '01374632538', 'assets/uploads/profile-pictures/employee-11-747898620c056454.jpg', 11);

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

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`leave_id`, `employee_id`, `leave_reason`, `leave_type`, `start_date`, `end_date`, `total_days`, `status`, `approved_by`, `admin_remarks`) VALUES
(1, 8, 'dghfsj', 'annual', '2026-11-13', '2026-11-14', 2, 'Pending', 'Panding', ''),
(2, 8, 'I am very sick', 'sick', '2026-11-13', '2026-11-14', 2, 'Pending', 'Panding', '');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `payroll_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `payroll_month` date NOT NULL,
  `salary` decimal(10,2) NOT NULL,
  `status` enum('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timesheets`
--

CREATE TABLE `timesheets` (
  `timesheet_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `work_date` date NOT NULL,
  `project_name` varchar(150) NOT NULL,
  `task_description` varchar(500) NOT NULL,
  `hours_worked` decimal(4,1) NOT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `admin_remarks` varchar(250) DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
(9, 'rokon uddin', 'rokonudden6969@gmail.com', '827ccb0eea8a706c4c34a16891f84e7b', 'employee'),
(11, 'sunny', 'sunny@gmail.com', '827ccb0eea8a706c4c34a16891f84e7b', 'employee');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `unique_employee_attendance_date` (`employee_id`,`attendance_date`);

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
  ADD PRIMARY KEY (`leave_id`),
  ADD KEY `fk_leave_employee` (`employee_id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`payroll_id`),
  ADD UNIQUE KEY `unique_employee_payroll_month` (`employee_id`,`payroll_month`);

--
-- Indexes for table `timesheets`
--
ALTER TABLE `timesheets`
  ADD PRIMARY KEY (`timesheet_id`),
  ADD UNIQUE KEY `unique_employee_timesheet_date` (`employee_id`,`work_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `leave_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `payroll_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timesheets`
--
ALTER TABLE `timesheets`
  MODIFY `timesheet_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employees_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `fk_leave_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `fk_payroll_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `timesheets`
--
ALTER TABLE `timesheets`
  ADD CONSTRAINT `fk_timesheet_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
