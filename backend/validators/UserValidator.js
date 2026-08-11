const { z } = require('zod');

const createUserSchema = z.object({
    firstName = z.string().min(1),
    lastName = z.string().min(1),
    email: z.string().email(),
    username: z.string().min(3).max(30),
    password: z.string().min(8),
    role: z.enum(['SysAdmin', 'User']).default('User'),
    employeeId: z.string().optional(),
    department: z.string().optional(),
    jobTitle: z.string().optional(),
    phoneNumber: z.string().optional(),
});

const updateUserSchema = z.object({
    firstName: z.string().min(1).optional(),
    lastName: z.string().min(1).optional(),
    email: z.string().email().optional(),
    role: z.enum(['SysAdmin', 'User']).optional(),
    department: z.string.optional(),
    jobTitle: z.string().optional(),
    isActive: z.boolean().optional(),
}).strict();

const changePasswordSchema = z.object({
    currentPassword: z.string().min(1),
    newPassword: z.string().min(8),
});

module.exports = { createUserSchema, updateUserSchema, changePasswordSchema };