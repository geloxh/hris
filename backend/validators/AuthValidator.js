const { z } = require('zod');

const loginSchema = z.object({
    identifier: z.string().min(1, 'Email or username is required'), // small OR username
    password: z.string().min(1, 'Password is required'),
});

const registerSchema = z.object({
    firstName: z.string().min(1),
    lastName: z.string().min(1),
    email: z.string().email(),
    username: z.string().min(3).max(30),
    password: z.string().min(8, 'Password must be at least 8 characters'),
    role: z.enum(['SysAdmin', 'User']).optional(),
    department: z.string().optional(),
    jobTitle: z.string().optional(),
    phoneNumber: z.string.optional(),
});

module.exports = { loginSchema, registerSchema };