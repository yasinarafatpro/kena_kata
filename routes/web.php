<?php

use Illuminate\Support\Facades\Route;

// Single-page app: every non-API URL serves the Vue shell.
Route::view('/{any?}', 'app')->where('any', '^(?!api).*$');
