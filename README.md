# FindR Project

This is a complete PHP/MySQL project for finding Rooms, Services, and more.

## Prerequisites

- **XAMPP** (or any PHP/MySQL environment)
- A web browser

## Setup Instructions

1.  **Place Files**: Ensure this project folder (`FINDR`) is inside your `htdocs` directory (e.g., `C:\xampp\htdocs\FINDR`).
2.  **Start Servers**:
    - Open XAMPP Control Panel.
    - Start **Apache** and **MySQL**.
3.  **Setup Database**:
    - Open your browser and go to `http://localhost/phpmyadmin`.
    - Click **New** in the sidebar.
    - Create a database named `findr` (utf8mb4_unicode_ci).
    - Select the `findr` database.
    - Click **Import** in the top menu.
    - Choose the file `findr_full.sql` from this project folder.
    - Click **Import** at the bottom.
4.  **Run the App**:
    - Open your browser and go to: `http://localhost/FINDR`

## Default Accounts (for testing)

- **Admin**: `admin@example.com` / `hash4`
- **Student**: `rahim@example.com` / `hash1`
- **Owner**: `karim.owner@example.com` / `hash2`

*Note: Passwords in the sample SQL are simple strings like 'hash1'. For new users signing up via the form, real secure hashes are used.*

## Features Fixed

- **Admin Panel**: Now available at `admin.php` for Admin users to approve Service Providers.
- **Owner Applications**: Now available at `owner_applications.php` for Owners to manage requests.
- **Service Provider Signup**: Now correctly creates a service provider profile upon registration.
