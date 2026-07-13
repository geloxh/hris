class ArsClient {
    public function __construct(
        private HttpClientInterface $client,
        private HmacSigner $signer,
        private string $arsBaseUrl,
        private string $arsSecret,
    ) {}

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