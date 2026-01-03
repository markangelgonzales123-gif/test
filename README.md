# City College of Angeles - EPMS System

This is an Employee Performance Management System (EPMS) for City College of Angeles. The system allows users to create, manage and track Department Performance Commitment and Review (DPCR), Individual Performance Commitment and Review (IPCR), and Individual Development Plan (IDP) forms.

## Features

- User Authentication (Login, Register, Forgot Password)
- DPCR Management
- Records Management
- Responsive design using Bootstrap and Tailwind CSS

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache, Nginx, etc.)

## Installation

1. Clone the repository or download the ZIP file and extract it to your web server directory (e.g., `htdocs` for XAMPP).

2. Create a database named `epms_db` in your MySQL server.

3. Import the database schema from `epms_db.sql`:
   ```
   mysql -u username -p epms_db < epms_db.sql
   ```
   Or use a tool like phpMyAdmin to import the SQL file.

4. Configure the database connection in the following files:
   - `process_login.php`
   - `register.php`
   - `forgot_password.php`
   - `reset_password.php`
   - `records.php`
   - `dpcr.php`

   Update the following lines with your database credentials:
   ```php
   $host = "localhost";
   $username = "root"; // Change to your MySQL username
   $password = ""; // Change to your MySQL password
   $database = "epms_db";
   ```

5. Create an `images` directory and place a logo image named `logo.png` in it.

## Usage

1. Access the application through your web browser:
   ```
   http://localhost/epms
   ```

2. Default login credentials:
   - Admin:
     - Email: admin@cca.edu.ph
     - Password: admin123
   - Regular User:
     - Email: john@cca.edu.ph
     - Password: password123

## Project Structure

- `index.php` - Login page
- `process_login.php` - Login processing script
- `register.php` - User registration page
- `forgot_password.php` - Forgot password page
- `reset_password.php` - Reset password page
- `logout.php` - Logout script
- `dpcr.php` - DPCR form page
- `records.php` - Records listing page
- `epms_db.sql` - Database schema and sample data

## License

This project is proprietary and confidential. Unauthorized copying, distribution, or use is strictly prohibited.

## Support

For any issues or inquiries, please contact the administrator. 