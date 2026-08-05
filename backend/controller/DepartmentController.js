const Department = require('../models/Department');
const ApiError = require('../utils/ApiError');
const asyncHandler = require('../utils/asyncHandler');

const createDepartment = asyncHandler(async (req, res) => {
  const existing = await Department.findOne({
    $or: [{ name: req.body.name }, { code: req.body.code }],
  });
  if (existing) {
    throw ApiError.conflict('A department with that name or code already exists');
  }
  const department = await Department.create(req.body);
  res.status(201).json({ success: true, data: { department } });
});

const listDepartments = asyncHandler(async (req, res) => {
  const filter = {};
  if (req.query.isActive !== undefined) filter.isActive = req.query.isActive === 'true';

  const departments = await Department.find(filter)
    .populate('manager', 'firstName lastName email')
    .sort({ name: 1 });

  res.json({ success: true, data: { departments } });
});

const getDepartment = asyncHandler(async (req, res) => {
  const department = await Department.findById(req.params.id).populate(
    'manager',
    'firstName lastName email'
  );
  if (!department) throw ApiError.notFound('Department not found');
  res.json({ success: true, data: { department } });
});

const updateDepartment = asyncHandler(async (req, res) => {
  const department = await Department.findByIdAndUpdate(
    req.params.id,
    { $set: req.body },
    { new: true, runValidators: true }
  );
  if (!department) throw ApiError.notFound('Department not found');
  res.json({ success: true, data: { department } });
});

const deactivateDepartment = asyncHandler(async (req, res) => {
  const department = await Department.findByIdAndUpdate(
    req.params.id,
    { $set: { isActive: false } },
    { new: true }
  );
  if (!department) throw ApiError.notFound('Department not found');
  res.json({ success: true, message: 'Department deactivated', data: { department } });
});

module.exports = {
  createDepartment,
  listDepartments,
  getDepartment,
  updateDepartment,
  deactivateDepartment,
};
