<?php

namespace Shortcodes\Toolbox\Traits\Crudable;

use Illuminate\Support\Facades\Route;

trait RequestManagement
{
    public function applyRequest()
    {
        if ($this->requests[Route::getCurrentRoute()->getActionMethod()] ?? null) {
            app($this->requests[Route::getCurrentRoute()->getActionMethod()]);
        }
    }

}
