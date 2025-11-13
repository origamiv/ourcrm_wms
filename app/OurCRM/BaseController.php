<?php

declare(strict_types=1);

namespace OurCRM;


use Spatie\RouteDiscovery\Attributes\DoNotDiscover;


abstract class BaseController
{
    #[DoNotDiscover]
    public function __construct()
    {
        //
    }
}
