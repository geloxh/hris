### Human Resource Information System (HRIS)
Aims to Streamlines the Small-to-Medium(SME) Human Resources(HR) operations into a single platform. Core features include Employee Data Management, automated payroll, time and absence tracking, leave credits, paperless onboarding, performance evaluation tools, and employee self-service portals.

### Requirements
Technology Stack

### Backend (The Engine)
- **Runtime**: Node.js
- **Framework**: Express.js (RESTful API)
- **Database**: MongoDB with Mongoose (ODM)
- **Authentication**: JWT (JSON Web Tokens) stored in HttpOnly cookies.
- **Validation**: Zod (Schema-based validation)
- **Security**: Helmet (HTTP headers), CORS, Express Rate Limit.
- **Logging**: Pino & Morgan for structured observability.

### Frontend (The Interface)
- **Framework**: React 19
- **Build Tool**: Vite
- **Styling**: Tailwind CSS 4.0 (Modern, utility-first CSS)
- **Components**: Shadcn UI (Built on Radix UI primitives)
- **Routing**: React Router 7
- **Icons**: Lucide React
- **Desktop Wrapper**: Electron (Enables native desktop features)

### Run System
```bash
# backend
cd backend && npm install && cp .env.example .env   # fill in JWT_SECRET + Mongo URI
npm run dev

# frontend, in a second terminal
cd frontend && npm install && cp .env.example .env
npm run electron:dev   # opens as a real desktop window
# or: npm run dev       # opens in a browser tab instead, for faster iteration
```

## geloxh