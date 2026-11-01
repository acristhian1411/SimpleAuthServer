<?php

namespace App\Passport;

use App\Models\User;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse as BaseBearerTokenResponse;

class BearerTokenResponse extends BaseBearerTokenResponse
{
    /**
     * Add the reset_password flag to token responses that belong to a user.
     */
    protected function getExtraParams(AccessTokenEntityInterface $accessToken): array
    {
        $user = $accessToken->getUserIdentifier()
            ? User::find($accessToken->getUserIdentifier())
            : null;

        return $user ? ['reset_password' => $user->reset_password] : [];
    }
}
