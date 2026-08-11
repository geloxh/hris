const express = require('express');
const router = express.Router();

router.use('/users', require('/users'));
router.use('/employees', require('./employees'));
router.use('/departments', require('./departments'));

module.exports = router;