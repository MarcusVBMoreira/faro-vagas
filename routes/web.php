<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\JobPostingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileItemController;
use App\Http\Controllers\ResumeController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::view('/privacidade', 'legal.privacy')->name('privacy');
Route::get('/idioma/{locale}', LocaleController::class)->name('locale');

Route::middleware('guest')->group(function () {
    Route::get('/cadastro', [RegisterController::class, 'create'])->name('register');
    Route::post('/cadastro', [RegisterController::class, 'store'])->middleware('throttle:10,1');

    Route::get('/entrar', [LoginController::class, 'create'])->name('login');
    Route::post('/entrar', [LoginController::class, 'store']);

    // Login em duas etapas (código por e-mail)
    Route::get('/entrar/codigo', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('/entrar/codigo', [TwoFactorController::class, 'verify'])->middleware('throttle:10,1')->name('two-factor.verify');
    Route::post('/entrar/codigo/reenviar', [TwoFactorController::class, 'resend'])->name('two-factor.resend');

    Route::get('/esqueci-a-senha', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/esqueci-a-senha', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/redefinir-senha/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/redefinir-senha', [PasswordResetController::class, 'update'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/sair', [LoginController::class, 'destroy'])->name('logout');

    // Confirmação do e-mail por código (obrigatória para usar o Faro)
    Route::get('/verificar-email', [EmailVerificationController::class, 'show'])->name('verification.notice');
    Route::post('/verificar-email', [EmailVerificationController::class, 'verify'])->middleware('throttle:10,1')->name('verification.verify');
    Route::post('/verificar-email/reenviar', [EmailVerificationController::class, 'resend'])->name('verification.send');

    Route::middleware('verified')->group(function () {
        Route::get('/perfil', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');

        Route::post('/perfil/foto', [AvatarController::class, 'store'])->middleware('throttle:10,1')->name('avatar.store');
        Route::delete('/perfil/foto', [AvatarController::class, 'destroy'])->name('avatar.destroy');

        Route::post('/perfil/experiencias', [ProfileItemController::class, 'storeExperience'])->name('experiences.store');
        Route::delete('/perfil/experiencias/{experience}', [ProfileItemController::class, 'destroyExperience'])->name('experiences.destroy');
        Route::post('/perfil/formacoes', [ProfileItemController::class, 'storeEducation'])->name('educations.store');
        Route::delete('/perfil/formacoes/{education}', [ProfileItemController::class, 'destroyEducation'])->name('educations.destroy');

        Route::post('/perfil/curriculo', [ResumeController::class, 'store'])->middleware('throttle:10,1')->name('resume.store');
        Route::get('/perfil/curriculo/status', [ResumeController::class, 'status'])->name('resume.status');

        Route::get('/vagas', [JobPostingController::class, 'index'])->name('jobs.index');
        Route::get('/vagas/nova', [JobPostingController::class, 'create'])->name('jobs.create');
        Route::post('/vagas', [JobPostingController::class, 'store'])->middleware('throttle:10,1')->name('jobs.store');
        Route::get('/vagas/{job}', [JobPostingController::class, 'show'])->whereNumber('job')->name('jobs.show');
        Route::delete('/vagas/{job}', [JobPostingController::class, 'destroy'])->whereNumber('job')->name('jobs.destroy');

        Route::put('/conta/seguranca', [AccountController::class, 'updateSecurity'])->name('account.security');
        Route::delete('/conta', [AccountController::class, 'destroy'])->name('account.destroy');
    });
});
