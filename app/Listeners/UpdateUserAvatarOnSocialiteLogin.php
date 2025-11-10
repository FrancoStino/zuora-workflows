<?php

namespace App\Listeners;

use DutchCodingCompany\FilamentSocialite\Events\Login;
use DutchCodingCompany\FilamentSocialite\Events\Registered;

class UpdateUserAvatarOnSocialiteLogin
{
    public function handle(Login|Registered $event): void
    {
        $oauthUser = $event->oauthUser;
        $user = $event->socialiteUser->getUser();
        $avatar = $oauthUser->getAvatar();

        if ($avatar && $avatar !== $user->avatar_url) {
            $user->update([
                'avatar_url' => $avatar,
            ]);
        }
    }
}
