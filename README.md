# HR Management System

A basic web application where HR manages employees, attendance, leave and monthly salary, and employees can see their own records online.

**Tech stack:** PHP · MySQL · HTML · CSS · JavaScript · Bootstrap

---

## Table of Contents

- [Problem and Solution](#problem-and-solution)
- [User Roles](#user-roles)
- [Features](#features)
- [Main Pages](#main-pages)
- [Payroll Logic](#payroll-logic)
- [Database Design](#database-design)
- [Tech Stack](#tech-stack)
- [Installation](#installation)
- [Project Structure](#project-structure)
- [Development Plan](#development-plan)
- [Out of Scope](#out-of-scope)
- [Future Ideas](#future-ideas)

---

## Problem and Solution

**The problem**

- Employee records are kept on paper or in many spreadsheets.
- Attendance and leave are counted by hand, so mistakes happen.
- Monthly salary takes a lot of time to calculate and check.

**The solution**

- One website where all HR data is in one place.
- Employees check in, apply for leave and see their payslip online.
- Salary is worked out automatically from attendance and leave.

> Goal: make managing people and salary simple, fast and clear.

---

## User Roles

Everyone logs in from a single `users` table. The `role` column decides what they can do.

| Role | What they can do |
|------|------------------|
| **Admin** | Creates HR and employee accounts, manages departments and leave types, sets holidays, views all reports |
| **HR Manager** | Adds and updates employee records, approves or rejects leave requests, generates monthly payroll |
| **Employee** | Checks in and out each day, applies for leave and sees the balance, views own payslip and profile |

### Workflow

**Employee:** log in → check in and apply for leave → see attendance and payslip

**HR Manager:** add the employee profile → approve leave and check attendance → generate and pay the salary

---

## Features

- Role-based login with PHP sessions
- Employee profile management (add, search, update)
- Daily check-in / check-out, with late check-ins marked automatically
- Leave application, approval and automatic balance update
- Automatic monthly payroll from attendance and leave
- Printable payslips
- Dashboard with today's summary and filters (department, designation, month, status, date)
- Monthly reports
- In-app notifications (e.g. "Your leave is approved")
- Passwords stored as secure hashes

---

## Main Pages

| Page | Purpose |
|------|---------|
| Dashboard | Totals and today's summary |
| Employees | Add, search and update |
| Attendance | Daily check-in and report |
| Leave | Apply, approve, balance |
| Payroll | Salary and payslips |
| Reports | Monthly summaries |

---

## Payroll Logic

1. **Set the salary:** basic salary and allowances are saved in `salary_structures`.
2. **Count attendance:** present, absent and leave days come from `attendance` and `leave_requests`.
3. **Apply deductions:** absent days and fixed cuts.
4. **Make the payslip:** save the month in `payroll` and print it.

### Rules set by HR

- **Absent deduction** = `basic ÷ 30 × absent days`
- **Approved leave is never deducted.**
- Holidays are skipped when counting working days.
- When payroll is generated, amounts are **copied** into `payroll`, so an old payslip never changes if the salary structure changes later.
- Each employee gets **one payroll row per month**; the same month cannot be generated twice for the same employee.

### Payroll status flow

```
draft  →  approved  →  paid
```

1. **Generate payroll:** a new row is created in `payroll` (draft)
2. **HR checks it:** attendance and leave are reviewed (approved)
3. **Salary is paid:** the month is marked as done (paid)

### Sample payslip (October 2026)

| Earnings | Tk | Deductions | Tk |
|----------|---:|------------|---:|
| Basic salary | 20,000 | Absent days (2) | 1,333 |
| House rent | 6,000 | Provident fund | 500 |
| Medical | 1,000 | | |
| Conveyance | 1,000 | | |
| **Gross salary** | **28,000** | **Total deductions** | **1,833** |

**Net salary: Tk 26,167** (working days 26, present 24, absent without leave 2)

---

## Database Design

12 tables in 6 groups.

### Group 1: Users and profiles

| Table | What it stores | Linked to |
|-------|----------------|-----------|
| `users` | Login details of every user (admin, HR manager, employee) | - |
| `employees` | Employee profile: code, phone, join date, status | `users`, `departments`, `designations` |

### Group 2: Lookup lists (managed by Admin)

| Table | What it stores | Linked to |
|-------|----------------|-----------|
| `departments` | Department names, such as IT or Sales | `employees` |
| `designations` | Job titles, such as Developer or Manager | `employees` |
| `leave_types` | Kinds of leave and days per year (casual, sick, annual) | `leave_balances`, `leave_requests` |
| `holidays` | Public holidays, skipped when counting work days | - |

### Group 3: Attendance

| Table | What it stores | Linked to |
|-------|----------------|-----------|
| `attendance` | One row per employee per day: check-in, check-out, status | `employees` |

Status values: `present`, `late`, `absent`, `on_leave`

### Group 4: Leave

| Table | What it stores | Linked to |
|-------|----------------|-----------|
| `leave_balances` | Days left of each leave type, for each year | `employees`, `leave_types` |
| `leave_requests` | Each leave application: dates, reason, status | `employees`, `leave_types`, `users` |

Status values: `pending`, `approved`, `rejected`, `cancelled`

### Group 5: Payroll

| Table | What it stores | Linked to |
|-------|----------------|-----------|
| `salary_structures` | Basic salary and allowances of an employee | `employees` |
| `payroll` | Monthly salary: gross, deductions, net and status | `employees` |

### Group 6: Alerts

| Table | What it stores | Linked to |
|-------|----------------|-----------|
| `notifications` | Messages like "Your leave is approved" | `users` |

### Relationships

- **One to one:** `users` ↔ `employees` (one profile per login account)
- **One to many:** `departments` → `employees`, `designations` → `employees`, `employees` → `attendance`, `leave_requests`, `payroll`, `salary_structures`; `users` → `notifications`
- **Many to many:** `employees` ↔ `leave_types` through `leave_balances`

`leave_requests.approved_by` points to `users.id`.

### Table columns (ERD summary)

```
users              id PK, name, email UQ, password, role, status
departments        id PK, name UQ
designations       id PK, title
employees          id PK, user_id FK, department_id FK, designation_id FK,
                   emp_code UQ, phone, join_date, status
attendance         id PK, employee_id FK, att_date, check_in, check_out, status
leave_types        id PK, name UQ, days_per_year
leave_balances     id PK, employee_id FK, leave_type_id FK, year, days_left
leave_requests     id PK, employee_id FK, leave_type_id FK, start_date,
                   end_date, reason, status, approved_by FK
salary_structures  id PK, employee_id FK, basic, house_rent, medical, conveyance
payroll            id PK, employee_id FK, month_year, gross, deductions,
                   net_salary, status, paid_at
holidays           id PK, title, holiday_date
notifications      id PK, user_id FK, message, is_read, created_at
```

`PK` = primary key, `FK` = foreign key, `UQ` = unique.

### Key rules

- One attendance row per employee per day.
- One payroll row per employee per month.
- Deleting a user also removes their profile.

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Frontend | HTML, CSS, JavaScript, Bootstrap |
| Backend | PHP (no framework), sessions for login |
| Database | MySQL, managed with phpMyAdmin |
| Server | Apache (XAMPP) |
| Version control | Git |

---

## Installation

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL**.
2. Clone the project into the XAMPP web folder:
   ```bash
   cd C:/xampp/htdocs
   git clone <your-repository-url> hr-management-system
   ```
3. Open phpMyAdmin (`http://localhost/phpmyadmin`) and create a database, for example `hr_system`.
4. Import the SQL file (for example `database/hr_system.sql`) into that database.
5. Set the database connection details (host, database name, username, password) in the project's config file.
6. Open `http://localhost/hr-management-system` in your browser and log in.

> Adjust the folder and file names above to match your actual project.

---

## Project Structure

A suggested layout (change it to fit your code):

```
hr-management-system/
├── config/          # database connection
├── includes/        # header, footer, auth checks
├── admin/           # admin pages
├── hr/              # HR manager pages
├── employee/        # employee pages
├── assets/          # css, js, images
├── database/        # SQL schema and sample data
└── index.php        # login page
```

---

## Development Plan

| Week | Module |
|------|--------|
| 1 | Requirements, database design, project setup |
| 2 | Login and role-based access |
| 3 | Employee management |
| 4 | Daily attendance |
| 5 | Leave management |
| 6 | Payroll and payslip |
| 7 | Dashboard and reports |
| 8 | Testing, bug fixing, documentation |

---

## Out of Scope

Not in this version (can be added later without changing the 12 tables):

- Recruitment
- Biometric devices
- Mobile app
- Performance reviews

---

## Future Ideas

- **Email alerts:** leave and payslip notifications by email
- **Biometric attendance:** connect a fingerprint or RFID device
- **Mobile app:** check in and apply for leave from a phone
- **Bangla and English:** let users choose the website language

---

## Author

**Name:**
**ID:**
**Batch / Course:**
**Date:** November 2026
