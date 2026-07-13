class HmacAuthenticator extends AbstractAuthenticator {
    public function __construct(private HmacSigner $signer, private string $arsSecret) {}

    public function supports(Request $request): ?bool {
        return $request->headers->has('X-Signature');
    }

    public function authenticate(Request $request): Passport {
        $timestamp = $request->headers->get('X-Timestamp');
        $signature = $request->headers->get('X-Signature');

        if (abs(time() - (int) $timestamp) > 300) {
            throw new CustomUserMessageAuthenticationException('Request expired.');
        }

        $expected = $this->signer->sign($request->getContent(), $timeStamp, $this->arsSecret);

        if (!hash_equals($expected, $signature)) {
            throw new CustomUserMessageAuthenticationException('Invalid signature.');
        }

        return new SelfValidatingPassport(new UserBadge('ars-system'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response {
        return new JsonResponse(['error' => $exception->getMessage()], 401);
    }
}