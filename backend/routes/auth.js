const express = require('express');
const router = express.Router();

const { login, logout, getCurrentUser, listMySessions, revokeSession } = require('../controller/SessionController');
const { authenticate } = require('../middleware/auth');
const { authLimiter } = require('../middleware/rateLimiter');
const validate = require('../middleware/validate');
const { loginSchema } = require('../validators/authValidator');

router.post('/login', authLimiter, validate(loginSchema), login);
router.post('/logout', authenticate, logout );
router.get('/me', authenticate, getCurrentUser);
router.get('/sessions', authenticate, listMySessions);
router.delete('/sessions/:id', authenticate, revokeSession);

module.exports = router;