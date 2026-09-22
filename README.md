# Invoice Manager

A PHP-based **Invoice Management System** developed as a web application for creating and managing invoices.

The project demonstrates how multiple PHP files can work together to handle user input, process forms, manage invoice data, and perform CRUD operations.

## Project Overview

The Invoice Manager allows users to manage invoice records through a simple web interface.

The application supports the following operations:

* Create a new invoice
* View invoice information
* Edit existing invoices
* Delete invoices
* Handle and validate form submissions
* Process user input between PHP pages
* Manage data through interconnected PHP files

## Features

### Invoice Management

Users can perform the following actions:

**Add Invoice**

Create a new invoice by entering the required information through a form.

**Edit Invoice**

Update information for an existing invoice.

**Delete Invoice**

Remove an invoice from the system.

**View Invoices**

Display existing invoice records and their information.

### Form Handling

The project demonstrates PHP form handling, including:

* Receiving form data
* Processing `GET` and `POST` requests
* Validating user input
* Handling submitted form values
* Passing data between PHP files
* Redirecting users after form submissions

## Technologies Used

| Technology       | Purpose                     |
| ---------------- | --------------------------- |
| PHP              | Server-side programming     |
| HTML             | Page structure and forms    |
| CSS              | User interface styling      |
| MySQL / Database | Storing invoice information |
| Git / GitHub     | Version control             |

## CRUD Operations

The application demonstrates the four basic CRUD operations:

| Operation  | Function                 |
| ---------- | ------------------------ |
| **Create** | Add a new invoice        |
| **Read**   | View invoice information |
| **Update** | Edit an existing invoice |
| **Delete** | Remove an invoice        |

```text
Create → Add Invoice
Read   → View Invoice
Update → Edit Invoice
Delete → Delete Invoice
```

## PHP File Structure

The application is organized into multiple PHP files that work together.

A typical structure may look like:

```text
invoice-manager/
│
├── index.php
├── add.php
├── update.php
├── delete.php
├── template.php
└── README.md
```

The exact files and structure may vary depending on the project implementation.

## Form Handling Workflow

The application follows a basic form-processing workflow:

```text
User opens form
       ↓
Enters invoice information
       ↓
Submits form
       ↓
PHP receives form data
       ↓
Input is processed/validated
       ↓
Invoice data is stored or updated
       ↓
User is redirected/displayed the result
```

## How It Works

The project uses separate PHP files to handle different parts of the application.

For example:

1. A user opens the invoice form.
2. The user enters the required invoice information.
3. The form sends the data to the appropriate PHP file.
4. PHP processes the submitted information.
5. The application performs the required operation.
6. The user can add, edit, or delete the invoice.

This structure demonstrates how different PHP pages can be interconnected to create a functional web application.

## Running the Project

### Requirements

* PHP
* Herd

```

## Learning Outcomes

This project provided practical experience with:

* PHP fundamentals
* Server-side form processing
* Handling `GET` and `POST` requests
* Organizing functionality across multiple PHP files
* Input validation
* Redirects and page-to-page communication
* Building a basic web application without a framework

## Project Type

**Academic Web Development Project**

This project was created for educational purposes to practice PHP, form handling, database operations, and CRUD functionality.
