# Recruitment module (not yet built)

Tables: job_postings, candidates, applications (candidate_id, job_posting_id, stage, status),
interview_schedules.
Suggested controllers: JobPostingController, CandidateController, ApplicationController.
On "hired" transition, call EmployeeService::onboard() from Employee module to convert
an application into a real employee record - keep that as the one integration point.
