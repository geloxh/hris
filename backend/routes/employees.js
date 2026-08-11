const express = require('express');
const router = express.Router();

const {
    createEmployee,
    listEmployees,
    getEmployee,
    updateEmployee,
    deactivateEmployee,
} = require('../controller/EmployeeController');
const { authenticate, authorize } = require('../middleware/auth');
const validate = require('../middleware/validate');
const { createEmployeeSchema, updateEmployeeSchema } = require('../validators/employeeValidator');

router.use(authenticate);

router.get('/', listEmployees);
router.get('/:id', getEmployee);
router.post('/', authorize('SysAdmin', 'User'), validate(createEmployeeSchema), createEmployee);
router.patch('/:id', authorize('SysAdmin', 'User'), validate(updateEmployeeSchema), updateEmployee);
router.delete('/:id', authorize('SysAdmin', 'User'),  deactivateEmployee);

module.exports = router;