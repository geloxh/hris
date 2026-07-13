### Human Resource Information System (HRIS)
Aims to Streamlines the Small-to-Medium(SME) Human Resources(HR) operations into a single platform. Core features include Employee Data Management, automated payroll, time and absence tracking, leave credits, paperless onboarding, performance evaluation tools, and employee self-service portals.

### Prerequisites
- **Php** - https://www.php.net/downloads.php
- **Composer** - https://getcomposer.org/download/
- **Symfony** - https://symfony.com/download
- **Docker** - https://docs.docker.com/desktop/setup/install/windows-install/
- **MySQL** - https://www.mysql.com/downloads/ (for deployment)

### Project Structure
```
    project-root/
    ├── docker-compose.yml
    ├── docker-compose.override.yml
    ├── automated-requesting-system/     # existing PHP, unchanged
    │   ├── public/
    │   ├── src/
    │   └── Dockerfile
    ├── hris/                            # Symfony
    │   ├── bin/console
    │   ├── config/
    │   │   ├── packages/
    │   │   │   └── messenger.yaml       # async payslip queue config
    │   │   ├── services.yaml
    │   │   └── routes.yaml
    │   ├── public/
    │   │   └── index.php
    │   ├── src/
    │   │   ├── Controller/
    │   │   │   ├── Api/
    │   │   │   │   └── PayslipController.php
    │   │   │   └── EmployeeController.php
    │   │   ├── Entity/
    │   │   │   ├── Employee.php
    │   │   │   └── PayslipRequest.php
    │   │   ├── Repository/
    │   │   ├── Security/
    │   │   │   └── HmacAuthenticator.php   # verifies inbound HMAC (if ARS calls back)
    │   │   ├── Service/
    │   │   │   ├── ArsClient.php           # outbound HMAC-signed client to ARS
    │   │   │   └── HmacSigner.php          # shared signing logic
    │   │   ├── Message/
    │   │   │   └── GeneratePayslipMessage.php
    │   │   └── MessageHandler/
    │   │       └── GeneratePayslipHandler.php
    │   ├── migrations/
    │   ├── tests/
    │   │   ├── Unit/
    │   │   └── Functional/
    │   ├── Dockerfile
    │   └── .env
    ├── shared/
    │   └── hmac-lib/                    # composer package, required by both apps
    │       ├── src/HmacSigner.php
    │       └── composer.json
    ├── nginx/
    │   ├── ars.conf
    │   └── hris.conf
    └── docker/
        ├── php-ars/Dockerfile
        └── php-hris/Dockerfile
```