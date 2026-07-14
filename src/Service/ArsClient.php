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

        /**
         * @return array{ok: bool, requests: array, error: ?string}
         */
        public function getPayslipRequestsForEmployee(int $employeeId): array
    {
        $result = $this->request('GET', "/api/v1/employees/{$employeeId}/payslip-requests");

        if (!$result['ok']) {
            return ['ok' => false, 'requests' => [], 'error' => $result['error']];
        }

        return ['ok' => true, 'requests' => $result['data']['payslip_requests'] ?? [], 'error' => null];
    }

    /**
     * @return array{ok: bool, error: ?string}
     */
    public function fulfillPayslipRequest(int $requestId, string $payslipReference): array
    {
        $result = $this->request(
            'POST',
            "/api/v1/payslip-requests/{$requestId}/fulfill",
            ['payslip_reference' => $payslipReference]
        );

        return ['ok' => $result['ok'], 'error' => $result['error']];
    }

    /**
     * @return array{ok: bool, status: int, data: mixed, error: ?string}
     */
    private function request(string $method, string $path, ?array $body = null): array
    {
        if ($this->arsBaseUrl === '') {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => 'ars_base_url_not_configured'];
        }
        if ($this->secret === '' || $this->secret === 'CHANGE_ME_GENERATE_A_RANDOM_64_CHAR_HEX_SECRET') {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => 'ars_hmac_secret_not_configured'];
        }

        // Signing uses only the path (no query string) per ARS's protocol —
        // strip it off here so ?status=pending doesn't end up inside the
        // signed canonical string while still being sent on the wire.
        $signedPath = strtok($path, '?');
        $bodyJson = $body !== null ? json_encode($body) : '';

        $headers = $this->signer->sign($method, $signedPath, $bodyJson, $this->serviceId, $this->secret);
        $headers['Content-Type'] = 'application/json';

        try {
            $response = $this->client->request($method, $this->arsBaseUrl . $path, [
                'headers' => $headers,
                'body' => $bodyJson,
                'timeout' => 10,
            ]);

            $status = $response->getStatusCode();
            $data = null;
            try {
                $data = $response->toArray(false);
            } catch (\Throwable) {
                // Non-JSON or empty body — leave $data null, status still tells the caller what happened.
            }

            return [
                'ok' => $status >= 200 && $status < 300,
                'status' => $status,
                'data' => $data,
                'error' => ($status >= 200 && $status < 300) ? null : ($data['error'] ?? 'http_' . $status),
            ];
        } catch (HttpExceptionInterface $e) {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => 'connection_failed: ' . $e->getMessage()];
        }
    }
}
