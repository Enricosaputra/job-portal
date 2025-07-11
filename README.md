# Job Portal API

![Laravel](https://img.shields.io/badge/Laravel-9.x-FF2D20?logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?logo=mysql)

A RESTful API for a job portal application built with Laravel, enabling companies to post jobs and freelancers to apply for them, with comprehensive application management and performance tracking features.

## ✨ Features

### 🔐 Authentication

-   JWT-based authentication using Laravel Sanctum
-   Role-based access control (company/freelancer)
-   User registration and profile management

### 💼 Job Management

-   Create, read, update, and delete job listings
-   Draft/published status control
-   Application tracking per job

### 📝 Application System

-   Freelancers can apply to published jobs
-   CV upload and management
-   Application status workflow (pending → reviewed → hired/rejected → completed)

### 🏆 Honor Points System

-   Companies can award points to freelancers
-   Freelancer performance tracking
-   Points history and analytics

## 🚀 API Endpoints

### Authentication

| Method | Endpoint    | Description         | Access        |
| ------ | ----------- | ------------------- | ------------- |
| POST   | `/register` | Register new user   | Public        |
| POST   | `/login`    | Login and get token | Public        |
| POST   | `/logout`   | Logout user         | Authenticated |
| GET    | `/profile`  | Get user profile    | Authenticated |

### Jobs

| Method | Endpoint                  | Description                   | Access       |
| ------ | ------------------------- | ----------------------------- | ------------ |
| GET    | `/jobs`                   | List all jobs                 | Public       |
| POST   | `/jobs`                   | Create new job                | Company only |
| PUT    | `/jobs/{id}`              | Update job                    | Owner only   |
| DELETE | `/jobs/{id}`              | Delete job                    | Owner only   |
| GET    | `/jobs/{id}/applications` | List job applications         | Company only |
| POST   | `/jobs/{id}/complete`     | Complete job and award points | Company only |

### Applications

| Method | Endpoint                    | Description               | Access          |
| ------ | --------------------------- | ------------------------- | --------------- |
| POST   | `/jobs/{id}/applications`   | Apply to job              | Freelancer only |
| GET    | `/applications`             | List user's applications  | Freelancer only |
| PATCH  | `/applications/{id}/status` | Update application status | Company only    |
| GET    | `/company/cvs`              | View all applicant CVs    | Company only    |

### Honor Points

| Method | Endpoint        | Description        | Access          |
| ------ | --------------- | ------------------ | --------------- |
| GET    | `/honor-points` | List earned points | Freelancer only |

## 🛠️ Installation

### Prerequisites

-   PHP 8.0+
-   MySQL 5.7+ or MariaDB 10.2+
-   Composer

### Setup Instructions

1. Clone the repository:

```bash
    git clone https://github.com/Enricosaputra/job-portal.git
    cd job-portal
```

2. Install dependencies:

```bash
    composer install
```

3. Configure environment:

```bash
cp .env.example .env
```

Edit .env with your database credentials and app settings.

4. Generate application key:

```bash
php artisan key:generate
```

5. Run migrations:

```bash
php artisan migrate
```

6. Install Laravel Sanctum:

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

7. Create storage link:

```bash
php artisan storage:link
```

8. Start development server:

```bash
php artisan serve
```

📚 API Documentation
View Postman Collection
https://www.postman.com/maintenance-physicist-61027762/workspace/job-portal/collection/18290709-3938c871-f3a7-490f-a456-1da2496ff660?action=share&creator=18290709

📝 License
This project is open-source and available under the MIT License.
