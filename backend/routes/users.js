const express = require('express');
const router = express.Router();

const {
    createUser,
    listUsers,
    getUser,
    updateUser,
    deactivateUser,
    changePassword,
} = require('../controller/UserController');
const { authenticate, authorize } = require('../middleware/auth');
const validate = require('../middleware/validate');
const { createUserSchema, updateUserSchema, changePasswordSchema } = require('../validators/userValidator');

router.use(authenticate);

router.patch('/me/password', validate(changePasswordSchema), changePassword);

router.post('/', authorize('SysAdmin', 'User'), validate(createUserSchema), createUser);
router.get('/', authorize('SysAdmin', 'User'), listUsers);
router.get('/:id', authorize('SysAdmin', 'User'), getUser);
router.patch('/:id', authorize('SysAdmin', 'User'), validate(updateUserSchema), updateUser);
router.delete('/:id', authorize('SysAdmin', 'User'), deactivateUser);

module.exports = router;