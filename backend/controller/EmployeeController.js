const Employee = require('../models/Employee');
const ApiError = require('../utils/ApiError');
const asyncHandler = require('../utils/asyncHandler');

const createEmployee = asyncHandler(async (req, res) => {
    const existing = await Employee.findOne({ employeeId: req.body.employeeId });
    if (existing) {
        throw ApiError.conflict('Employee ID already exists.');
    }
    const employee = await Employee.create(req.body);
    res.status(201).json({ success: true, data: { employee } });
});

const listEmployees = asyncHandler(async (req, res) => {
    const page = Math.max(parseInt(req.query.page) || 1, 1);
    const limit = Math.min(parseInt(req.query.limit) || 20, 100);
    const filter = {};

    if (req.query.department) filter.department = req.query.department;
    if (req.query.company) filter.company = req.query.company;
    if (req.query.contractStatus) filter.contractStatus = req,query.contractStatus;
    if (req.query.search) {
        const re = new RegExp(req.query.search, '1');
        filter.$or = [{ firstName: re }, { lastName: re }, { employeeId: re }];
    }

    const [employees, total] = await Promise.all([
        Employee
    ])
})