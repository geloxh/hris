### Human Resource Information System (HRIS)
Aims to Streamlines the Small-to-Medium(SME) Human Resources(HR) operations into a single platform. Core features include Employee Data Management, automated payroll, time and absence tracking, leave credits, paperless onboarding, performance evaluation tools, and employee self-service portals.

### Prerequisites
- **Php** - https://www.php.net/downloads.php
- **Composer** - https://getcomposer.org/download/
- **Docker** - https://docs.docker.com/desktop/setup/install/windows-install/
- **MySQL** - https://www.mysql.com/downloads/ (for deployment)

### Project Structure
```
    hris/
    ├── docker/
    │   ├── php/Dockerfile
    │   ├── nginx/default.conf
    │   └── mysql/init.sql
    ├── docker-compose.yml
    ├── public/                    # Web root (only this is exposed)
    │   ├── index.php              # Front controller
    │   ├── assets/
    │   │   ├── css/
    │   │   ├── js/
    │   │   └── img/
    │   └── .htaccess (if Apache) 
    ├── src/
    │   ├── Config/
    │   │   ├── config.php
    │   │   └── database.php
    │   ├── Core/
    │   │   ├── Router.php
    │   │   ├── Controller.php
    │   │   ├── Model.php
    │   │   ├── Database.php       # PDO singleton/wrapper
    │   │   ├── Request.php
    │   │   ├── Response.php
    │   │   ├── Auth.php
    │   │   ├── Validator.php
    │   │   └── Middleware.php
    │   ├── Modules/
    │   │   ├── Employee/
    │   │   │   ├── EmployeeController.php
    │   │   │   ├── EmployeeModel.php
    │   │   │   └── EmployeeService.php
    │   │   ├── EmployeeData/       # profiles, contracts, documents, org chart
    │   │   ├── Payroll/            # payroll runs, tax, compensation, benefits
    │   │   ├── TimeAttendance/      # timesheets, leave, scheduling
    │   │   ├── Recruitment/         # ATS, onboarding, job postings
    |   |   ├── Performance/         # goals/OKRs, reviews, 360 feedback
    |   |   ├── Learning/            # courses, certifications, career paths
    |   |   ├── SelfService/         # employee/manager portals (mostly thin controllers over other modules)
    |   |   ├── Analytics/           # dashboards, reports, report builder
    |   |   └── Compliance/          # audit trails, policy acknowledgment, labor law tracking
    |   |
    |   |
    │   ├── Helpers/
    │   └── Middleware/
    ├── database/
    │   ├── migrations/
    │   └── seeders/
    ├── storage/
    │   ├── logs/
    │   └── uploads/
    ├── tests/
    ├── vendor/                     # if using Composer for autoload only
    └── composer.json
```