<?php

    namespace App\Message;

    /**
     * GeneratePayslipMessage
     *
     * Dispatched (via Messenger, see config/packages/messenger.yaml) to
     * generate a payslip for one of ARS's pending payslip requests, and
     * report it back as fulfilled. Async so payslip generation (PDF
     * rendering, computation) doesn't block the HTTP request that triggered it.
     */

    final class GeneratePayslipHandler {
        public function __construct(
            public readonly int $arsRequestId,
            public readonly int $employeeId,
            public readonly string $period,
        ) {
            
        }
    }