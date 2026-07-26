<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class ChatOwner
{
    public function token(Request $request): string
    {
        $ownerToken = $request->cookie('chat_owner_token');

        if (is_string($ownerToken) && Str::isUuid($ownerToken)) {
            return $ownerToken;
        }

        $ownerToken = (string) Str::uuid();
        Cookie::queue(Cookie::forever('chat_owner_token', $ownerToken));

        return $ownerToken;
    }
}
