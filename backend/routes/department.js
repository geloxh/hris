const express = require('express');
const router = express.Router();

const {
    createDepartment,
    listDepartments,
    getDeparment,
    updateDepartment,
    deactivateDepartment,
} = require('../controller/DepartmentController');
const { authenticate, authorize } = require('../middleware/auth');
const validate = require('../middleware/validate');
const { createDepartmentSchema, updateDepartmentSchema } = require('../validators/departmentValidator');

router.use(authenticate);

router.get('/', listDepartments);
router.get('/:id', getDepartment);
router.post('/', authorize('SysAdmin', 'User'), validate(createDepartmentSchema), createDepartment);
router.patch('/:id', authorize('SysAdmin', 'User'), validate(updateDepartmentSchema), updateDepartment);
router.delete('/:id', authorize('SysAdmin', 'User'), deactivateDepartment);

module.exports = router;