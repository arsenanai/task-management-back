Yii2 Task Management API
========================

This document provides instructions for setting up and running the project.

1\. Installation
----------------

1.  Clone the repository.
2.  Install dependencies using Composer:
    
        composer install
    

2\. Environment Configuration
-----------------------------

This project uses local configuration files to store sensitive data like database credentials and secret keys. These files are excluded from version control.

1.  **Development Database:**  
    Create a file named `config/db-local.php` with your development database credentials:
    
        <?php
            
            return [
                'dsn'      => 'mysql:host=localhost;dbname=task_management',
                'username' => 'db_user',
                'password' => 'db_password',
            ];
    
2.  **Test Database:**  
    Create a file named `config/test_db-local.php` with your test database credentials:
    
        <?php
            
            return [
                'dsn'      => 'mysql:host=localhost;dbname=task_management_test',
                'username' => 'db_user',
                'password' => 'db_password',
            ];
    
3.  **Application Secrets:**  
    Create a file named `config/params-local.php` for your application secrets, like the JWT secret key:
    
        <?php
            
            return [
                'jwtSecret' => 'replace-this-with-a-long-random-secret-key',
            ];
    

3\. Database Setup
------------------

You need to create two databases: one for development and one for testing.

1.  **Create Databases:**  
    Using your MySQL client, create the databases specified in `config/db.php` and `config/test_db.php`.
    
        CREATE DATABASE `task_management`;
        CREATE DATABASE `task_management_test`;
    
2.  **Run Migrations:**  
    Apply the database schema to both databases using the following commands.
    *   For the **development** database:
        
            php yii migrate
        
    *   For the **test** database:
        
            php tests/bin/yii migrate/fresh
        
        _(Note: This uses the test-specific entry script to load the test environment's database configuration.)_

4\. Running Tests
-----------------

1.  **Build Codeception Actors:**
    
        composer test-build
    
2.  **Run the Test Suite:**
    
        composer test
    

5\. Running the Application
---------------------------

You can run the application locally using Yii's built-in development server.

    php yii serve

The API will be available at `http://localhost:8080`.

6\. API Testing with Postman
----------------------------

A Postman collection is included in the root of the project (`Yii2 Task Management API.postman_collection.json`) to make manual API testing easier.

### 1\. Import the Collection

Import the `Yii2 Task Management API.postman_collection.json` file into your Postman client. The collection is pre-configured with all the necessary API endpoints.

### 2\. Default Users

The database migrations create two default users for you to use:

*   **Admin User:**
    *   **Email:** `admin@example.com`
    *   **Password:** `password`
*   **Regular User:**
    *   **Email:** `user@example.com`
    *   **Password:** `userpass`

### 3\. Authentication Workflow

The Postman collection is designed to handle authentication automatically.

1.  Run the **Authentication > Login as Admin** (or **Login as User**) request first.
2.  The test script in that request will automatically save the received JWT token to a collection variable.
3.  All other requests in the collection are configured to use this token for authorization, so you can immediately start testing other endpoints like `GET /tasks`.