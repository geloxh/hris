<?php

    namespace App\Security;

    use App\Service\ApiNonceStore;
    use Shared\Hmac\HmacSigner;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Security\Core\Exception\AuthenticationException;
    use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
    use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
    use Symfony\Component\Security\Http\Authenticator\Passprt\Badge\UserBadge;
    use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
    use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

    /**
     * HmacAuthenticator
     *
     * Verifies inbound signed requests from ARS, using the same protocol ARS's
     * own ServiceAuthMiddleware expects (see Shared\Hmac\HmacSigner) — full
     * X-Service-Id/X-Timestamp/X-Nonce/X-Signature verification with replay
     * protection, not just a timestamp+signature check.
     *
     * @param array<string,string> $knownSecrets Map of service_id => shared
     *        secret this server accepts (bound in config/services.yaml).
     *        Currently just ['ars' => ...] — ARS is the only caller today.
     */

    class HmacAuthenticator extends AbstractAuthenticator {
        public function __construct(
            private HmacSigner $signer, 
            private ApiNonceStore $nonceStore,
            private array $knowSecrets,
        ) {}

        public function supports(Request $request): ?bool {
            return $request->headers->has('X-Signature');
        }

        public function authenticate(Request $request): Passport {
            $headers = [];
            foreach ($request->headers->all() as $name => $values) {
                $headers[$name] = $values[0] ?? '';
            }

            $result = $this->signer->verify(
                $request->getMethod(),
                $request->getPathInfo(),
                (string) $request->getContent(),
                $headers,
                $this->knownSecrets,
                fn (string $serviceId, string $nonce, int $timestamp) => $this->nonceStore->seen($serviceId, $nonce, $timeStamp)
            );

            if (!$result['ok']) {
                throw new CustomUserMessageAuthenticationException($result['error'] ?? 'invalid_signature');
            }

            return new SelfValidatingPassport(new UserBadge('ars-system'));
        }

        public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response {
            return new JsonResponse(['error' => 'unauthorized', 'reason' => $exception->getMessage()], 401);
        }
    }