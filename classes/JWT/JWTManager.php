<?php

use Opencontent\OpenApi\Loader;
use phpseclib3\Common\Functions\Strings;
use phpseclib3\Crypt\RSA;
use phpseclib3\Math\BigInteger;
use Opencontent\OpenApi\Exceptions\UnauthorizedException;
use Firebase\JWT\JWT;

class JWTManager
{
    private static $instances;

    private $token;

    private $decodedToken = [];

    private $wellKnownUrls = [
        'uat.interop.pagopa.it' => 'https://uat.interop.pagopa.it/.well-known/jwks.json',
        'interop.pagopa.it' => 'https://interop.pagopa.it/.well-known/jwks.json',
    ];

    private $wellKnownUrl;

    private static $internalIssuer = 'opencity';

    private function __construct(string $token)
    {
        $this->token = $token;
    }

    public static function instance($token): JWTManager
    {
        if (empty($token)) {
            throw new UnauthorizedException('JWT Token not found');
        }
        if (!isset(self::$instances[$token])) {
            self::$instances[$token] = new static($token);
        }

        return self::$instances[$token];
    }

    public function getUserId(): ?int
    {
        $this->verify();
        $username = $this->decodedToken['payload']['purposeId'] ?? $this->decodedToken['payload']['username'];
        $user = eZUser::fetchByName($username);
        if (!$user instanceof eZUser) {
            if (!isset($this->decodedToken['payload']['purposeId'])){
                throw new UnexpectedValueException('PurposeId not found');
            }
            $user = $this->createUser();
        }

        $this->setRateLimit();

        return (int)$user->id();
    }

    public static function issueInternalJWTToken(eZUser $user): string
    {
        $issuer = OpenPABase::getCurrentSiteaccessIdentifier();
        $tokenTTL = 120;
        $now = time();
        /** @var eZContentObject[] $groups */
        $groups = $user->groups(true);
        $allowedRoles = [];
        foreach ($groups as $group) {
            $allowedRoles[] = eZCharTransform::instance()
                ->transformByGroup($group->attribute('name'), 'identifier');
        }

        $payload = [
            "iss" => self::$internalIssuer,
            "aud" => self::$internalIssuer,
            "iat" => $now,
            "nbf" => $now,
            "exp" => $now + $tokenTTL,
            "uid" => $user->id(),
            "name" => $user->attribute('contentobject')->attribute('name'),
            "username" => $user->attribute('login'),
            "email" => $user->attribute('email'),
            "email_verified" => true,
            self::$internalIssuer . "-claims" => [
                "allowed-roles" => $allowedRoles,
                "default-role" => $user->attribute('contentobject')->attribute('class_identifier'),
                "user-id" => $user->id(),
                "tenant" => $issuer,
            ],
        ];

        $privateKeyFilePath = './jwt/private.key.pem';
        $privateKey = file_get_contents($privateKeyFilePath);
        $jwt = JWT::encode($payload, $privateKey, 'RS256');

        return $jwt;
    }

    private function setRateLimit()
    {
        //@todo get rate limit from purpose
        //$purposeId = $this->decodedToken['payload']['purposeId'];
        try {
            $apiSettings = Loader::instance()->getSettingsProvider()->provideSettings();
            $apiSettings->rateLimitDocumentationEnabled = true;
            $apiSettings->rateLimitEnabled = true;
            OpenApiRateLimit::instance()->setInterval(60 * 60 * 24);
            OpenApiRateLimit::instance()->setRateLimitPerInterval(1000);
        }catch (Throwable $e){}
    }

    private function createUser(): eZUser
    {
        $moduleINI = eZINI::instance('module.ini');
        $globalModuleRepositories = $moduleINI->variable('ModuleSettings', 'ModuleRepositories');
        eZModule::setGlobalPathList($globalModuleRepositories);
        if (!isset($GLOBALS['eZRequestedModuleParams'])) {
            $GLOBALS['eZRequestedModuleParams'] = [
                'module_name' => null,
                'function_name' => null,
                'parameters' => null,
            ];
        }

        $anonymousId = eZUser::anonymousId();
        $parentNodeId = (int)eZContentObject::fetch($anonymousId)->mainParentNodeID();
        if (!$parentNodeId) {
            return eZUser::fetch($anonymousId);
        }
        $defaultUserPlacement = (int)eZINI::instance()->variable("UserSettings", "DefaultUserPlacement");

        $contentRepository = new \Opencontent\Opendata\Api\ContentRepository();
        $contentRepository->setEnvironment(new DefaultEnvironmentSettings());
        $userContent = $contentRepository->create([
            'metadata' => [
                'classIdentifier' => 'user',
                'parentNodes' => [$parentNodeId, $defaultUserPlacement],
            ],
            'data' => [
                'first_name' => 'Api user',
                'last_name' => 'PDND',
                'user_account' => [
                    'login' => $this->decodedToken['payload']['purposeId'],
                    'email' => $this->decodedToken['payload']['purposeId'] . '|@pdnd.example.com',
                ],
                'gdpr_acceptance' => 'true',
                'antispam' => 'true',
            ],
        ], true);

        $id = $userContent['content']['metadata']['id'] ?? null;
        if ($id) {
            return eZUser::fetch((int)$id);
        }

        throw new Exception('Internal error creating user');
    }

    public function verify()
    {
        $this->decode();

        $issuer = $this->decodedToken['payload']['iss'];
        if ($issuer === self::$internalIssuer) {
            $this->verifyInternalToken();
        } else {
            $this->verifyPDNDVoucher();
        }
    }

    private function verifyInternalToken()
    {
        $publicKeyFilePath = './jwt/public.key.pem';
        $publicKey = file_get_contents($publicKeyFilePath);
        $token = JWT::decode($this->token, $publicKey, ['RS256']);

        $now = new DateTimeImmutable();

        if ($token->iss !== self::$internalIssuer ||
            $token->nbf > $now->getTimestamp() ||
            $token->exp < $now->getTimestamp()) {
            throw new UnauthorizedException('Invalid JWT Token');
        }
    }

    private function verifyPDNDVoucher()
    {
        $typ = $this->decodedToken['headers']['typ'] ?? null;
        if ($typ !== 'at+jwt') {
            throw new UnauthorizedException('Invalid JWT Token: invalid header typ, expected typ');
        }

        $kid = $this->decodedToken['headers']['kid'] ?? null;
        if (!$kid) {
            throw new UnauthorizedException('Invalid JWT Token: missing header kid');
        }

        $issuer = $this->decodedToken['payload']['iss'];
        if (!isset($this->wellKnownUrls[$issuer])) {
            throw new UnauthorizedException('Invalid JWT Token: invalid issuer');
        }
        $this->wellKnownUrl = $this->wellKnownUrls[$issuer];

        if (strpos($this->decodedToken['payload']['aud'], 'openapi/audience') === false) {
            throw new UnauthorizedException('Invalid JWT Token: invalid aud');
        }

        $now = time();
        if ($now > $this->decodedToken['payload']['exp']) {
            throw new UnauthorizedException('Invalid JWT Token: token is expired');
        }

        [$headers, $payload, $sig] = explode('.', $this->token);
        $message = implode('.', [$headers, $payload]);
        $signature = Strings::base64url_decode($sig);

        $publicKey = $this->getPublicKeyByKid($kid);
        $isVerified = $publicKey->verify($message, $signature);
        if (!$isVerified) {
            $publicKey = $this->getPublicKeyByKid($kid, false);
            $isVerified = $publicKey->verify($message, $signature);
        }
        if (!$isVerified) {
            throw new UnauthorizedException('Invalid JWT Token: fail signature verification');
        }
    }

    private function decode()
    {
        if (strpos($this->token, '.') === false) {
            throw new UnauthorizedException('Invalid JWT Token');
        }

        [$headersB64, $payloadB64, $signature] = explode('.', $this->token);
        $headers = json_decode(Strings::base64url_decode($headersB64), true);
        $payload = json_decode(Strings::base64url_decode($payloadB64), true);
        if (!$headers || !$payload) {
            throw new UnauthorizedException('Invalid JWT Token');
        }

        $this->decodedToken = [
            'headers' => $headers,
            'payload' => $payload,
            'signature' => $signature,
        ];
    }

    private function getPublicKeyByKid($kid, $useCache = true): RSA
    {
        if ($useCache && $cached = $this->getCachedKid($kid)) {
            $nString = $cached['n'];
            $eString = $cached['e'];
        } else {
            $kidList = json_decode(file_get_contents($this->wellKnownUrl), true);
            if (empty($kidList)) {
                throw new UnauthorizedException(sprintf('Kid list not found in %s', $this->wellKnownUrl));
            }
            $keys = array_values(
                array_filter((array)$kidList['keys'], function ($item) use ($kid) {
                    return $item['kid'] === $kid;
                })
            );
            if (!isset($keys[0])) {
                throw new UnauthorizedException(sprintf('Kid not found in kid list %s', $this->wellKnownUrl));
            }
            $nString = $keys[0]['n'];
            $eString = $keys[0]['e'];
            $this->setCachedKid($kid, $nString, $eString);
        }

        $key = RSA::loadPublicKey([
            'n' => new BigInteger(Strings::base64url_decode($nString), 256),
            'e' => new BigInteger(Strings::base64url_decode($eString), 256),
        ])
            ->withPadding(RSA::SIGNATURE_PKCS1)
            ->withHash('sha256');

        return $key;
    }

    private function getCachedKid($kid)
    {
        $db = eZDB::instance();
        $name = $db->escapeString($kid);
        $existingRes = $db->arrayQuery("SELECT value FROM ezpreferences WHERE user_id = 0 AND name = '$name'");
        if (isset($existingRes[0]['value'])) {
            return json_decode($existingRes[0]['value'], true);
        }
        return null;
    }

    private function setCachedKid($kid, $nString, $eString)
    {
        $value = json_encode(['n' => $nString, 'e' => $eString]);
        $db = eZDB::instance();
        $name = $db->escapeString($kid);
        $existingRes = $db->arrayQuery("SELECT value FROM ezpreferences WHERE user_id = 0 AND name = '$name'");

        if (count($existingRes) > 0) {
            $id = $existingRes[0]['id'];
            $query = "UPDATE ezpreferences SET value='$value' WHERE id = $id AND name='$name'";
        } else {
            $query = "INSERT INTO ezpreferences ( user_id, name, value ) VALUES ( 0, '$name', '$value' )";
        }
        $db->query($query);
    }
}