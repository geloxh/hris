# Compliance module (not yet built)

Tables: audit_logs (already created in migrations - wire it up by calling a small
AuditLogger::log($userId, $action, $entityType, $entityId, $details) helper from
inside Services that mutate data - Employee, Payroll, Leave approvals, etc),
policy_documents, policy_acknowledgments (employee_id, policy_id, acknowledged_at).
