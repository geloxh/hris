const { z } = require('zod');

const createDepartmentSchema = z.object({
    name: z.string().min(1),
    code: z.string().min(1),
    manager: z.string().optional(),
    description: z.string().optional(),
});

const updateDepartmentSchema = createDepartmentSchema.partial();

module.exports = { createDepartmentSchema, updateDepartmentSchema };