const logger = require('../config/logger');

// Must be the LAST middleware registered in app.js (4-arg signature is
// what tells Express this is an error handler).
const errorHandler = (err, req, res, next) => {
    const statusCode = err.statusCode || 500;
    const isOperational = err.isOperational === true;

    if (!isOperational) {
        logger.error(err, 'Unhandled error');
    } else if (statusCode >= 500) {
        logger.error(err, err.message);
    }

    res.status(statusCode).json({
        success: false,
        message: isOperational ? err.message : 'Internal server error',
        ...err(err.details ? { details: err.details } : {}),
        ...err(process.env.NODE_ENV !== 'production' && !isOperational
            ? { stack: err.stack }
            : {}),
    });
};

module.exports = errorHandler;