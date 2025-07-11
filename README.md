# Job Portal API

A RESTful API for a job portal application built with Laravel. This API allows companies to post jobs and freelancers to apply for them, with features for managing applications and tracking freelancer performance.

## Technical Specifications

-   **PHP Version**: 8.0+
-   **MySQL Version**: 5.7+ (or MariaDB 10.2+)
-   **Laravel Version**: 9.x
-   **Authentication**: Laravel Sanctum (JWT tokens)

## Features

-   **User Authentication** (JWT via Laravel Sanctum)

    -   Register as company or freelancer
    -   Login/logout functionality
    -   Protected routes with role-based access

-   **Job Management**

    -   Create, read, update, delete jobs
    -   Draft/published status control
    -   View applications per job

-   **Application System**

    -   Freelancers can apply to published jobs
    -   CV upload capability
    -   Application status tracking (pending/reviewed/hired/rejected/completed)

-   **Honor Points**
    -   Companies can award points to freelancers
    -   Freelancers can track their earned points

## API Endpoints

### Authentication

| Method | Endpoint      | Description           |
| ------ | ------------- | --------------------- |
| POST   | /api/register | Register new user     |
| POST   | /api/login    | Login and get token   |
| POST   | /api/logout   | Invalidate token      |
| GET    | /api/user     | Get current user data |

### Jobs

| Method | Endpoint                    | Description                          |
| ------ | --------------------------- | ------------------------------------ |
| GET    | /api/jobs                   | List jobs                            |
| POST   | /api/jobs                   | Create new job (company only)        |
| GET    | /api/jobs/{id}              | Get job details                      |
| PUT    | /api/jobs/{id}              | Update job (owner only)              |
| DELETE | /api/jobs/{id}              | Delete job (owner only)              |
| GET    | /api/jobs/{id}/applications | List job applications (company only) |

### Applications

| Method | Endpoint                      | Description                    |
| ------ | ----------------------------- | ------------------------------ |
| POST   | /api/jobs/{id}/applications   | Apply to job (freelancer only) |
| GET    | /api/applications             | List user's applications       |
| GET    | /api/applications/{id}        | Get application details        |
| PATCH  | /api/applications/{id}/status | Update status (company only)   |
| GET    | /api/applications/{id}/cv     | Download CV (company only)     |

### Honor Points

| Method | Endpoint                    | Description                   |
| ------ | --------------------------- | ----------------------------- |
| POST   | /api/jobs/{id}/honor-points | Award points (company only)   |
| GET    | /api/honor-points           | List points (freelancer only) |

## Installation

1. Clone the repository:
   git clone https://github.com/yourusername/job-portal-api.git
   cd job-portal

Install dependencies:
composer install

Configure environment:
cp .env.example .env

Edit .env with your database credentials and app settings.

Generate application key:
php artisan key:generate

Run migrations:
php artisan migrate

Install Laravel Sanctum:
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

Storage Link
php artisan storage:link 

Start development server:
php artisan serve
