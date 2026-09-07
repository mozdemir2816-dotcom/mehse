<?php

use Illuminate\Support\Facades\Route;

// Kök adres doğrudan panele yönlensin (stok Laravel karşılama sayfası yerine).
Route::redirect('/', '/admin');
