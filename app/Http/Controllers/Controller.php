<?php

namespace App\Http\Controllers;

use App\Domain\Shared\Actor;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function actor(Request $request): Actor
    {
        return Actor::web($request->user()->id);
    }
}
