# SAGA Waiting List System

A comprehensive waiting list management system with public signup and admin management interfaces.

## Table of Contents

- [User Guide](#user-guide)
  - [Overview](#overview)
  - [Accessing the Admin Dashboard](#accessing-the-admin-dashboard)
  - [Managing the Waiting List](#managing-the-waiting-list)
  - [Adding Users](#adding-users)
  - [Removing Users](#removing-users)
  - [Scheduled Openings](#scheduled-openings)
  - [Backup and Restore](#backup-and-restore)
  - [Settings Configuration](#settings-configuration)
- [Developer Guide](#developer-guide)
  - [System Architecture](#system-architecture)
  - [File Structure](#file-structure)
  - [Database Schema](#database-schema)
  - [API Endpoints](#api-endpoints)
  - [Authentication System](#authentication-system)
  - [Key Components](#key-components)
  - [Extending the System](#extending-the-system)
  - [Troubleshooting](#troubleshooting)

## User Guide

### Overview

The SAGA Waiting List System allows you to manage a waiting list for your service or event. It provides:

- A public interface for users to sign up to the waiting list
- An admin dashboard to manage the waiting list
- Scheduled automatic opening and closing of the waiting list
- Backup and restore functionality
- Customizable messages and settings

### Accessing the Admin Dashboard

1. Navigate to `/admin/login.html` in your web browser
2. Enter the admin password (default is `admin123`)
3. You will be redirected to the admin dashboard

**Note:** It is highly recommended to change the default password after your first login.

### Managing the Waiting List

#### Opening and Closing the Waiting List

The waiting list can be manually opened or closed from the admin dashboard:

1. On the dashboard, locate the "Waiting List Status" card
2. Toggle the switch to open or close the waiting list
3. When closed, users will see a customizable message on the public page

#### Adding Users

You can add users directly from the admin dashboard:

1. On the dashboard, locate the "Add New User" card
2. Fill in the required information:
   - Name (required)
   - Email or Phone (optional)
   - Language (required)
   - Comment (optional)
3. Click "Add User"
4. The user will be added to the bottom of the waiting list

#### Removing Users

To remove a user from the waiting list:

1. On the dashboard, locate the "Current Waiting List" table
2. Find the user you want to remove
3. Click the "Remove" button in the Actions column
4. Confirm the removal in the popup dialog

#### Scheduled Openings

The system can automatically open the waiting list at scheduled times:

1. Go to the Settings page by clicking "Settings" in the navigation menu
2. Under "Scheduled Open Times", you can configure when the list should automatically open
3. Format: `Day HH:MM` (e.g., `Monday 09:00,Thursday 14:00`)
4. Multiple times can be specified, separated by commas

**Important:** When the list opens automatically at a scheduled time, it will clear all existing entries. A backup is automatically created before clearing.

### Backup and Restore

#### Viewing Backups

1. On the dashboard, you can see the date of the last backup
2. Click "View Last Backup" to see the contents of the most recent backup

#### Exporting the Waiting List

1. On the dashboard, click the "Export Waiting List" button
2. A CSV file will be downloaded with the current waiting list data

#### Clearing the Waiting List

1. On the dashboard, locate the "Clear Waiting List" card
2. Click the "Clear Waiting List" button
3. Confirm that you have exported the current list
4. The list will be cleared and a backup will be automatically created

### Settings Configuration

1. Go to the Settings page by clicking "Settings" in the navigation menu
2. You can configure:
   - Scheduled open times
   - Closed message (shown when the list is closed)
   - Success message (shown after successful signup)
   - PDF confirmation file (downloadable after signup)

## Developer Guide

### System Architecture

The SAGA Waiting List System is built using:

- Frontend: HTML, CSS, JavaScript
- Backend: PHP
- Database: SQLite

The system follows a simple client-server architecture:

1. The frontend interfaces (public and admin) make AJAX requests to the backend API
2. The backend API processes these requests and interacts with the SQLite database
3. The API returns JSON responses which are processed by the frontend

### File Structure

\`\`\`
waitinglistv3current2/
├── app-backend/
│   ├── api/                  # Backend API endpoints
│   │   ├── add_user.php      # Public user signup
│   │   ├── add_user_admin.php # Admin user addition
│   │   ├── check_session.php # Session validation
│   │   ├── clear_waiting_list.php # Clear waiting list
│   │   ├── config.php        # Configuration settings
│   │   ├── create_db.php     # Database initialization
│   │   ├── db_connect.php    # Database connection helper
│   │   ├── export_backup.php # Export backup as CSV
│   │   ├── export_waiting_list.php # Export list as CSV
│   │   ├── get_backup_info.php # Get backup information
│   │   ├── get_backup_list.php # List available backups
│   │   ├── get_closed_message.php # Get closed message
│   │   ├── get_scheduled_open_times.php # Get scheduled times
│   │   ├── get_success_message.php # Get success message
│   │   ├── get_waiting_list.php # Get all waiting list entries
│   │   ├── get_waiting_list_state.php # Check if list is open
│   │   ├── login.php         # Admin login
│   │   ├── logout.php        # Admin logout
│   │   ├── move_user.php     # Change user position
│   │   ├── remove_user.php   # Remove user
│   │   ├── scheduled_open.php # Auto-open at scheduled times
│   │   ├── toggle_waiting_list.php # Open/close list
│   │   ├── update_closed_message.php # Update closed message
│   │   ├── update_scheduled_open_times.php # Update schedule
│   │   ├── update_success_message.php # Update success message
│   │   └── upload_pdf.php    # Upload confirmation PDF
│   ├── backups/              # Database backups
│   └── data/                 # SQLite database
├── webroot/                  # Web root directory
│   ├── admin/                # Admin interface
│   │   ├── api_proxy.php     # Proxy for API requests
│   │   ├── css/              # Admin CSS files
│   │   ├── index.html        # Admin dashboard
│   │   ├── logged_out.html   # Logout page
│   │   ├── login.html        # Login page
│   │   └── settings.html     # Settings page
│   ├── public/               # Public interface
│   │   ├── api_proxy.php     # Proxy for API requests
│   │   ├── confirmation.pdf  # Downloadable confirmation
│   │   ├── css/              # Public CSS files
│   │   └── index.html        # Public signup page
│   ├── db_test.php           # Database test utility
│   ├── index.html            # Root redirect
│   ├── init_db.php           # Web database initializer
│   └── test.php              # PHP test utility
\`\`\`

### Database Schema

The system uses an SQLite database with the following tables:

#### settings

Stores application settings.

| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER | Primary key |
| key | TEXT | Setting name (unique) |
| value | TEXT | Setting value |

#### waiting_list

Stores waiting list entries.

| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER | Primary key |
| name | TEXT | User's name |
| email_or_phone | TEXT | Contact information |
| comment | TEXT | Additional comments |
| language | TEXT | Preferred language |
| time | INTEGER | Unix timestamp of signup |
| confirmed | INTEGER | Whether entry is confirmed (1=yes, 0=no) |
| position | INTEGER | Position in the waiting list |

#### users

Stores admin users.

| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER | Primary key |
| username | TEXT | Username (unique) |
| password | TEXT | Hashed password |
| role | TEXT | User role (default: 'user') |

### API Endpoints

#### Public Endpoints

- `add_user.php`: Add a new user to the waiting list
- `get_waiting_list_state.php`: Check if the waiting list is open
- `get_closed_message.php`: Get the message shown when list is closed
- `get_success_message.php`: Get the message shown after successful signup

#### Admin Endpoints

- `login.php`: Admin login
- `logout.php`: Admin logout
- `check_session.php`: Validate admin session
- `get_waiting_list.php`: Get all waiting list entries
- `add_user_admin.php`: Add a user from the admin interface
- `remove_user.php`: Remove a user from the waiting list
- `move_user.php`: Change a user's position in the list
- `toggle_waiting_list.php`: Open or close the waiting list
- `clear_waiting_list.php`: Clear all entries from the waiting list
- `export_waiting_list.php`: Export the waiting list as CSV
- `get_backup_info.php`: Get information about the last backup
- `get_backup_list.php`: List all available backups
- `export_backup.php`: Export a specific backup as CSV
- `update_closed_message.php`: Update the closed message
- `update_success_message.php`: Update the success message
- `update_scheduled_open_times.php`: Update the scheduled open times
- `upload_pdf.php`: Upload a new confirmation PDF

### Authentication System

The admin interface uses a simple session-based authentication system:

1. Admin credentials are stored in `config.php`
2. When an admin logs in, their credentials are validated against the stored hash
3. If valid, a session is created with `$_SESSION['loggedin'] = true`
4. All admin API endpoints check for this session variable
5. Sessions expire after the configured timeout (default: 15 minutes)

### Key Components

#### API Proxies

The system uses API proxies (`api_proxy.php`) in both the admin and public interfaces to:

1. Forward requests to the backend API
2. Handle CORS and other cross-domain issues
3. Provide a unified endpoint for frontend requests

#### Scheduled Opening

The system can automatically open the waiting list at scheduled times:

1. Scheduled times are stored in the settings table
2. The `scheduled_open.php` script checks if the current time matches any scheduled time
3. If it does, the waiting list is opened and cleared
4. A backup is created before clearing the list
5. This script should be called regularly (e.g., via cron job or JavaScript setInterval)

#### Backup System

The system maintains backups of the waiting list:

1. Backups are created automatically when:
   - The waiting list is cleared manually
   - The waiting list is cleared by a scheduled opening
2. Only the three most recent backups are kept
3. Backups are stored in the `app-backend/backups/` directory
4. Backups can be viewed and exported from the admin interface

### Extending the System

#### Adding New Features

To add new features to the system:

1. Create a new PHP file in the `app-backend/api/` directory
2. Implement the feature logic
3. Update the frontend to use the new API endpoint

#### Modifying the Database Schema

To modify the database schema:

1. Update the `create_db.php` script with the new schema
2. Create a migration script to update existing databases
3. Update any affected API endpoints

#### Customizing the UI

To customize the UI:

1. Modify the HTML files in the `webroot/admin/` or `webroot/public/` directories
2. Update the CSS files in the respective `css/` directories
3. Modify the JavaScript code in the HTML files

### Troubleshooting

#### Database Issues

If you encounter database issues:

1. Check if the database file exists and is readable/writable
2. Run the `init_db.php` script to initialize the database
3. Check the database schema using a SQLite browser
4. Look for error messages in the API responses

#### API Errors

If you encounter API errors:

1. Check the browser console for error messages
2. Look for PHP errors in the server logs
3. Test the API endpoint directly using a tool like Postman
4. Check if the session is valid for admin endpoints

#### Session Issues

If you encounter session issues:

1. Clear browser cookies and cache
2. Check if the session timeout is configured correctly
3. Verify that the session is being created correctly on login
4. Check if the session is being properly maintained across requests
