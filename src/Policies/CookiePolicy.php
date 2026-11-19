<?php

namespace Dashed\DashedCookies\Policies;

use Dashed\DashedCore\Policies\BaseResourcePolicy;

class CookiePolicy extends BaseResourcePolicy
{
    protected function resourceName(): string
    {
        return 'Cookie';
    }
}
