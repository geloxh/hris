<?php

    namespace App\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Component\Security\Http\Attribute\IsGranted;

    /**
     * ArsCallbackController
     *
     * Receives push notifications *from* ARS, guarded by HmacAuthenticator
     * (see the `ars_callback` firewall in config/packages/security.yaml).
     *
     * Nothing in ARS calls this yet — today's flow is pull-based (HRIS polls
     * ArsClient::getPendingPayslipRequests()). This exists as a proven,
     * working landing point for when that's worth switching to push (e.g.
     * ARS notifying HRIS immediately when a new payslip request is created,
     * instead of HRIS finding out on its next poll).
     */

    #[Route('/api/ars-callback')]

    class ArsCallbackController extends AbstractController {
        #[Route('/payslip-ready', methods: ['POST'])]
        #[IsGranted('ROLE_ARS_SYSTEM')]
        public function payslipReady(Request $request): JsonResponse {
            // TODO: once ARS actually sends this, decode $request->getContent()
            // and act on it (e.g. dispatch a GeneratePayslipMessage). For now
            // this just proves the signed-request path is wired end to end.
            return $this->json(['status' => 'received']);
        }
    }