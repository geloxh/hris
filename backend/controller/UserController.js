const User = require('../models/User');
const ApiError = require('../utils/ApiError');
const asyncHandler = require('../utils/asyncHandler');

// POST /api/users  (SysAdmin/HR(Users) only - creating accounts)
const createUser = asyncHandler(async (req, res) => {
  const { email, username } = req.body;

  const existing = await User.findOne({ $or: [{ email }, { username }] });
  if (existing) {
    throw ApiError.conflict('Email or username already in use');
  }

  const user = await User.create(req.body);
  const safeUser = user.toObject();
  delete safeUser.password;

  res.status(201).json({ success: true, data: { user: safeUser } });
});

// GET /api/users?page=1&limit=20&role=Employee&search=jane
const listUsers = asyncHandler(async (req, res) => {
  const page = Math.max(parseInt(req.query.page) || 1, 1);
  const limit = Math.min(parseInt(req.query.limit) || 20, 100);
  const filter = {};

  if (req.query.role) filter.role = req.query.role;
  if (req.query.department) filter.department = req.query.department;
  if (req.query.isActive !== undefined) filter.isActive = req.query.isActive === 'true';
  if (req.query.search) {
    const re = new RegExp(req.query.search, 'i');
    filter.$or = [{ firstName: re }, { lastName: re }, { email: re }, { username: re }];
  }

  const [users, total] = await Promise.all([
    User.find(filter)
      .populate('department', 'name code')
      .sort({ createdAt: -1 })
      .skip((page - 1) * limit)
      .limit(limit),
    User.countDocuments(filter),
  ]);

  res.json({
    success: true,
    data: { users },
    pagination: { page, limit, total, pages: Math.ceil(total / limit) },
  });
});

// GET /api/users/:id
const getUser = asyncHandler(async (req, res) => {
  const user = await User.findById(req.params.id).populate('department', 'name code');
  if (!user) throw ApiError.notFound('User not found');
  res.json({ success: true, data: { user } });
});

// PATCH /api/users/:id
const updateUser = asyncHandler(async (req, res) => {
  const user = await User.findByIdAndUpdate(
    req.params.id,
    { $set: req.body },
    { new: true, runValidators: true }
  );
  if (!user) throw ApiError.notFound('User not found');
  res.json({ success: true, data: { user } });
});

// DELETE /api/users/:id  (soft delete - deactivate rather than remove)
const deactivateUser = asyncHandler(async (req, res) => {
  const user = await User.findByIdAndUpdate(
    req.params.id,
    { $set: { isActive: false } },
    { new: true }
  );
  if (!user) throw ApiError.notFound('User not found');
  res.json({ success: true, message: 'User deactivated', data: { user } });
});

// PATCH /api/users/me/password
const changePassword = asyncHandler(async (req, res) => {
  const { currentPassword, newPassword } = req.body;
  const user = await User.findById(req.user._id).select('+password');

  const isMatch = await user.comparePassword(currentPassword);
  if (!isMatch) {
    throw ApiError.badRequest('Current password is incorrect');
  }

  user.password = newPassword; // pre-save hook hashes it
  await user.save();

  res.json({ success: true, message: 'Password updated' });
});

module.exports = {
  createUser,
  listUsers,
  getUser,
  updateUser,
  deactivateUser,
  changePassword,
};
