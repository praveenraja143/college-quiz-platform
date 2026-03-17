# JKKMCT Online Quiz Competition Portal

This is a comprehensive, role-based Online Quiz Competition portal developed in PHP and MySQL specifically for JKKMCT. The platform provides a strictly controlled environment for conducting multiple-choice quizzes, with an administrator portal for management and reporting.

## Features

### 1. Student Portal (`/student`)
*   **Registration:** Open registration capturing Name, Department, Year, Register Number, and Email.
*   **Automated Credentialing:** The system automatically generates a secure, randomized **Unique ID** (e.g., `JKKME4B1`) and **Password**, intended to be distributed via email to candidates.
*   **Pre-Competition Gateway:** A dedicated waiting room that displays candidate details and locks access until the exact scheduled start time of the competition.
*   **Strict Anti-Cheat Examination Interface:**
    *   **Keyboard Lockout:** Prevents all keyboard interactions (e.g., `Ctrl+C`, `F5`, `Tab`) via Javascript listeners. Any keypress immediately submits the exam, terminating the attempt.
    *   **Tab/Window Activity Monitoring:** Utilizing the Page Visibility and Window Blur APIs, the system detects if a candidate leaves the test tab or minimizes the browser, triggering an immediate forced submission.
    *   **Touch/Mouse Only:** The interface is designed exclusively for mouse clicks or touch screens.
    *   **Countdown Timer:** Secure server-synced countdown timer.
    *   **Single Attempt:** Enforces one attempt per candidate per competition on the database level.

### 2. Administrator Portal (`/admin`)
*   **Secure Authentication:** Protected login (`admin` / `password`).
*   **Dashboard Analytics:** High-level overview of total candidates and active competitions.
*   **Competition Management:** Create, define start/end schedule windows, and set the status (Upcoming, Active, Completed) for testing events.
*   **Question Bank Management:** Add questions and define the correct 'A/B/C/D' multiple-choice options for specific competitions.
*   **Candidate Auditing:** A list view displaying details of all registered candidates across the platform.
*   **Dynamic CSV Reporting:**
    *   Downloads a formatted Excel (CSV) document containing complete competition results.
    *   **Intelligent Ranking Algorithm:** Automatically sorts candidates based directly down the hierarchy:
        1.  **Highest Marks** (Descending)
        2.  **Lowest Time Taken** (Ascending, recorded down to the precise second).
    *   Automatically calculates rank ties dynamically.

## Technology Stack

*   **Backend:** Pure PHP (8.0+)
*   **Database:** MySQL Server
*   **Structure & Styling:** HTML5, CSS3 (Vanilla Custom Styles, responsive grid layouts)
*   **Client Logic (Anti-Cheat):** Vanilla JavaScript (ES6)

## Database Architecture

The system utilizes five core relational tables governed by `schema.sql`:
*   `admins`: Stores credentialed administrators.
*   `students`: Logs registration data and generated authentication tokens.
*   `competitions`: Manages testing event timelines and statuses.
*   `questions`: Holds individual questions mapped (`FOREIGN KEY`) to a competition.
*   `results`: Maps the precise score and `time_taken_seconds` of a `student_id` for a specific `competition_id`, maintaining a `UNIQUE` constraint to prevent duplicate attempts.

---

## Build and Setup Instructions

This application is designed to run seamlessly on a standard LAMP/WAMP/XAMPP stack. 

### Prerequisites
*   A local development environment like **XAMPP**, or a web server with **PHP (7.4 or higher)** and **MySQL/MariaDB**.

### 1. Database Configuration
1. Start your local MySQL server (via XAMPP Control Panel).
2. The project includes an auto-creation script in `config.php`, but for best results, manually import the schema:
    *   Open `phpMyAdmin` (typically `http://localhost/phpmyadmin`).
    *   Select the `Import` tab.
    *   Upload the `schema.sql` file located in the root directory.
    *   This will create the `jkkmct_quiz` database and populate the initial `admin` user.
3. Open `config.php` and verify the credentials. By default for XAMPP, this is:
   ```php
   $host = 'localhost';
   $username = 'root';
   $password = ''; // Default is empty
   $database = 'jkkmct_quiz';
   ```

### 2. Hosting the Application
1. Place the entire `jkkmct_quiz` project folder into your web server's root directory (e.g., `C:\xampp\htdocs\` for XAMPP).
2. Alternatively, you can use PHP's built-in development server from the command line inside the project folder:
   ```bash
   php -S localhost:8000
   ```

### 3. Usage & Testing

1. **Access the Portal:** Navigate to `http://localhost/jkkmct_quiz/` (or `http://localhost:8000` if using the built-in server).
2. **Admin Entry:** 
    *   Go to **Admin Portal**.
    *   Login with Username: `admin`, Password: `password`.
    *   Create a competition and set the times so it is currently "Active".
    *   Add at least one question to the competition.
3. **Student Flow:**
    *   Open an incognito window or alternate browser.
    *   Register a new student. 
    *   *(Note: The `mail()` function may not route to external inboxes locally without configuring an SMTP server in your `php.ini`. For testing, the generated Unique ID and Password will display on the registration page in a yellow debugging box.)*
    *   Login to the Student Portal.
    *   Initiate the exam and test the Javascript anti-cheat features by switching tabs or pressing the keyboard.
4. **Export Results:** Return to the Admin Portal -> Results & Reports -> Download Excel (CSV) to view the generated standings.
