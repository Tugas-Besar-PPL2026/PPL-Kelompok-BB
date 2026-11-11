<<<<<<< Updated upstream
<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/dev/login/{email}', function (string $email) {
    Auth::login(User::where('email', $email)->firstOrFail());
    return redirect('/umkm/seleksi');
});
=======
   <?php

   use App\Models\User;
   use Illuminate\Support\Facades\Auth;
   use Illuminate\Support\Facades\Route;

   Route::get('/dev/login/{email}', function (string $email) {
       Auth::login(User::where('email', $email)->firstOrFail());
       return redirect('/umkm/seleksi');
   });
>>>>>>> Stashed changes
