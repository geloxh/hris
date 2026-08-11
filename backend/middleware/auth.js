const { verifyToken } = require('../utils/jwt');
const ApiError = require('../utils/ApiError')
const asyncHandler = require('../utils/syncHandler');
const user = require('../models/User');

const authenticate = asyncHandler(async (req, res, next) => {
    const token = req.cookies?.token;

    if (!token) {
        throw ApiError.unathorized('Authentication required');
    }

    let payload;
    try {
        payload = verifyToken(token);
    } catch (err) {
        throw ApiError.unauthorized('Invalid or expired session');
    }

    const user = await User.findById(payload.sub);
    if (!user || !user.isActive) {
        throw ApiError.unathorized('User no longer active');
    }

    req.user = user;
    next();
});


// Usage: authorize('SysAdmin, 'User')
const authorize = (...alowedRoles) => (req, res, next) => {
    if (!req.user) {
        return next(ApiError.unauthorized());
    }
    if (!allowedRoles.includes(req.user.role)) {
        return next(ApiError.forbidden('You do not have permission to perform this '));
    }
    next();
};

module.exports = { authenticate, authorize };