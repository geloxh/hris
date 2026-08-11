const ApiError = require('../utils/ApiError');

// Usage: validate(loginSchema) as a route middleware.
// Validates req.body against the given Zod schema and replaces req.body
// With the parsed (and coerced/defaulted) result.
const validate = (schema) => (req, res, next) => {
    const result = schema.safeParse(req.body);

    if (!result.success) {
        const details = result.error.issues.map((issue) => ({
            path: issue.path.join('.'),
            message: issue.message,
        }));
        return next(ApiError.badRequest('Validation failed', details));
    }

    req.body = result.data;
    next();
};

module.exports = validate;