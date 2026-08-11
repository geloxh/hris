const { z } = require('zod');

const createEmployeeSchema = z.object({
    firstName: z.string().min(1),
    lastName: z.string().min(1),
    employeeId: z.string().min(1),
    department: z.string().optional(),
    jobTitle: z.string().optional(),
    company: z.enum(['SPK', '3Ehitech', 'NORM', 'PowerNet']).optional(),
    contractStatus: z.enum(['Active', 'Resigned', 'Awol', 'Terminated']).default('Active'),
});

const updateEmployeeSchema = createEmployeeSchema.partial();

module.exports = { createEmployeeSchema, updateEmployeeSchema };