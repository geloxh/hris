const User = require('../models/User');
const Session = require('../models/Session');
const ApiError = require('../utils/ApiError');
const asyncHandler = require('../utils/asyncHandler');
const { signAccessToken, cookieOptions } = require('../utils/jwt');

// POST /api/auth/login
const login = asyncHandler(async (req, res) => {
  const { identifier, password } = req.body;

  const user = await User.findOne({
    $or: [{ email: identifier.toLowerCase() }, { username: identifier.toLowerCase() }],
  }).select('+password');

  if (!user || !user.isActive) {
    throw ApiError.unauthorized('Invalid credentials');
  }

  const isMatch = await user.comparePassword(password);
  if (!isMatch) {
    throw ApiError.unauthorized('Invalid credentials');
  }

  const token = signAccessToken(user);

  await Session.create({
    userId: user._id,
    token,
    userAgent: req.get('user-agent'),
    ipAddress: req.ip,
    expiresAt: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000),
  });

  user.lastLogin = new Date();
  await user.save();

  res.cookie('token', token, cookieOptions());

  const safeUser = user.toObject();
  delete safeUser.password;

  res.json({ success: true, data: { user: safeUser } });
});

// POST /api/auth/logout
const logout = asyncHandler(async (req, res) => {
  const token = req.cookies?.token;
  if (token) {
    await Session.deleteOne({ token });
  }
  res.clearCookie('token', { path: '/' });
  res.json({ success: true, message: 'Logged out' });
});

// GET /api/auth/me
const getCurrentUser = asyncHandler(async (req, res) => {
  res.json({ success: true, data: { user: req.user } });
});

// GET /api/auth/sessions - list active sessions for the current user
const listMySessions = asyncHandler(async (req, res) => {
  const sessions = await Session.find({ userId: req.user._id }).sort({ createdAt: -1 });
  res.json({ success: true, data: { sessions } });
});

// DELETE /api/auth/sessions/:id - revoke a specific session (e.g. "sign out of that device")
const revokeSession = asyncHandler(async (req, res) => {
  const session = await Session.findOneAndDelete({
    _id: req.params.id,
    userId: req.user._id,
  });
  if (!session) {
    throw ApiError.notFound('Session not found');
  }
  res.json({ success: true, message: 'Session revoked' });
});

module.exports = { login, logout, getCurrentUser, listMySessions, revokeSession };