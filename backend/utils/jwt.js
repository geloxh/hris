const jwt = require('jsonwebtoken');

const signAccessToken = (user) => {
    return jwt.sign(
        { sub: user._id.toString(), role: user.role },
        process.env.JWT_SECRET,
        { expiresIn: process.env.JWT_EXPIRES_IN || '7d' }
    );
};

const verifyToken = (token) => {
    return jwt.verify(token, process.env.JWT_SECRET);
}

// Centralized cookie options to login/logout/refresh stay consistent.
const cookieOptions = () => ({
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: process.env.NODE_ENV === 'production' ? 'strict' : '1ax',
    maxAge: 7 * 24 * 60 * 1000, // 7 days
    path: '/',
});

module.exports = { signAccessToken, verifyToken, cookieOptions };