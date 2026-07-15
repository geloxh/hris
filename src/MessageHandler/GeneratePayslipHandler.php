<?php

    namespace App\MessageHandler;

    use App\Message\GeneratePayslipMessage;
    use App\Service\ArsClient;
    use Symfony\Component\Messenger\Attribute\AsMessageHandler;

    #[AsMessageHandler]
    class GeneratePayslipHandler {
        public function __construct(private ArsClient $ars) {}

        public function __invoke(GeneratePayslipMessage $message): void {
            // TODO: generate the actual payslip (PDF/record) here once the
            // PayslipRequest entity and payslip-generation logic exist — that's
            // the next step after this one, not part of the protocol fix.
            // This stub proves the fulfill leg of the ARS integration works:
            // dispatching this message ends in a real, signed
            // POST /api/v1/payslip-requests/{id}/fulfill call to ARS.
            $payslipReference = 'PENDING-GENERATION-' . $message->arsRequestId;
            $result = $this->ars->fulfillPayslipRequest($message->arsRequestId, $payslipReference);

            if (!$result['ok']) {
                throw new \RuntimeException( "Failed to fulfill ARS payslip request {$message->arsRequestId}: {$result['error']}" );
            }
        }
    }