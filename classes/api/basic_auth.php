<?php

use Opencontent\OpenApi\Loader;

class OpenApiBasicAuthStyle extends ezpRestAuthenticationStyle implements ezpRestAuthenticationStyleInterface
{
    public function setup(ezcMvcRequest $request)
    {
        try {
            $settings = Loader::instance()->getSettingsProvider()->provideSettings();
            if ($settings->jwtAccessEnabled
                && isset($request->raw['HTTP_AUTHORIZATION'])
                && preg_match('/Bearer\s(\S+)/', $request->raw['HTTP_AUTHORIZATION'], $matches)) {
                $token = $matches[1];
                if ($userID = JWTManager::instance($token)->getUserId()) {
                    $auth = new ezcAuthentication(new ezcAuthenticationIdCredentials($userID));
                    $auth->addFilter(new OpenApiAuthenticationEzFilter());
                    return $auth;
                }
            }
        }catch (Throwable $e){}
        if ($request->authentication === null) {
            eZSession::lazyStart();
            $userID = eZSession::issetkey( 'eZUserLoggedInID', false ) ? eZSession::get( 'eZUserLoggedInID' ) : eZUser::anonymousId();
            if ($userID) {
                $auth = new ezcAuthentication(new ezcAuthenticationIdCredentials($userID));
                $auth->addFilter(new OpenApiAuthenticationEzFilter());
                return $auth;
            }

            $authRequest = clone $request;
            $authRequest->uri = "{$this->prefix}/auth/http-basic-auth";
            $authRequest->protocol = "http-get";

            return new ezcMvcInternalRedirect($authRequest);
        }

        $cred = new ezcAuthenticationPasswordCredentials($request->authentication->identifier, $request->authentication->password);

        $auth = new ezcAuthentication($cred);
        $auth->addFilter(new OpenApiAuthenticationEzFilter());
        return $auth;
    }

    public function authenticate(ezcAuthentication $auth, ezcMvcRequest $request)
    {
        if (!$auth->run()) {
            $request->uri = "{$this->prefix}/auth/http-basic-auth";
            $request->protocol = "http-get";

            return new ezcMvcInternalRedirect($request);
        } else {
            // We're in. Get the ezp user and return it
            return eZUser::fetchByName($auth->credentials->id);
        }
    }
}