#[Route('/api/ars-callback')]

class ArsCallbackController extends AbstractController {
    #[Route('/payslip-ready', methods: ['POST'])]
    #[IsGranted('ROLE_ARS_SYSTEM')]
    public function payslipReady(Request $request): JsonResponse {
        // Handle the webhook payload
        return $this->json(['status' => 'received']);
    }
}