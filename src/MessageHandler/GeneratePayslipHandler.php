#[AsMessageHandler]
class GeneratePayslipHandler {
    public function __construct(private ArsClient $ars, private EntityManagerInterface $em) {}

    public function __invoke(GeneratePayslipMessage $message): void {
        $result = $this->ars->requestPayslip($message->employeeId, $message->period);
        // Update PayslipRequest entity status, store result reference
    }
}