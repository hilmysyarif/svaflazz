<?php

namespace Svakode\Svaflazz\Routes;

use Illuminate\Support\Facades\Route;
use Svakode\Svaflazz\Controllers\Seller\Topup;

Route::post('digiflazz/order', [Topup::class, 'order']);