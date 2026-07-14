<?php

    namespace App\Service;

    use Shared\Hmac\HmacSigner;
    use Symfony\Contracts\HttpClient\HttpClientInterface;
    use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;

    /**
     * ArsClient
     *
     * Outbound HMAC-signed calls to ARS's internal API. ARS remains the
     * source of truth for employees, leave applications, and payslip
     * requests — HRIS reads/updates that data over this client rather than
     * keeping its own independent copy, so the two systems can't drift apart.
     *
     * Endpoint contract matches ARS's already-implemented
     * app/Controllers/Api/{LeaveCreditsController,PayslipRequestsController}.php
     * exactly. In particular, payslip requests flow ARS -> HRIS, not the
     * reverse: ARS persists the request when an employee asks for one; HRIS
     * polls for pending ones and calls back to fulfill them once generated.
     * There is no "ask ARS for a payslip" endpoint to call.
     */
    
    class ArsClient {
        public function __construct(
            private HttpClientInterface $client,
            private HmacSigner $signer,
            private string $arsBaseUrl,
            private string $serviceId,
            private string $secret,
        ) {}

        /**
         * @return array{ok: bool, leaveCredits: ?array, error: ?string}
         */
        public function getLeaveCredits (int $employeeId): array {
            $result = $this->request('GET', "/api/v1/employees/{$emplopyeeId}/leave-credits");

            if (!$result['ok']) {
                return ['ok' => false, 'leaveCredits' => null, 'error' => $result['error']];
            }

            return [
                'ok' => true,
                'leaveCredits' => $result['data']['leave_credits'] ?? null,
                'error' => null,
            ];
        }

        /**
         * @return array{ok: bool, requests: array, error: ?string}
         */
        public function getPendingPayslipRequests(): array {
            $result = $this->request('GET', '/api/v1/payslip-requests?status=pending');

            if (!$result['ok']) {
                return ['ok' => false, 'requests' => [], 'error' => $result['error']];  
            }
            
            return ['ok' => true, 'requests' => $result['data']['payslip_requests'] ?? [], 'error' => null];
        }
  
    }

        public function requestPayslip(int $employeeId, string $period): array {
            $body = json_encode(['employee_id' => $employeeId, 'period' => $period]);
            $timestamp = (string) time();
            $signature = $this->signer->sign($body, $timestamp, $this->arsSecret);

            $response = $this->client->request('POST', $this->arsBaseUrl . '/api/v1/payslip'. [
                'body' => $body,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Timestamp' => $timestamp,
                    'X-Signature' => $signature,
                ],
            ]);

            return $response->toArray();
        }
    }