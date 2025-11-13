<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use OurCRM\BaseController;
use Spatie\RouteAttributes\Attributes\Get;

final class DashboardController extends BaseController
{

    #[Get('/')]
    public function index()
    {
        return view('welcome');
    }
}
