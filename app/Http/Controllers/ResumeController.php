<?php

namespace App\Http\Controllers;

use App\Jobs\AnalyzeResume;
use App\Models\Resume;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResumeController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'resume' => ['required', 'file', 'mimes:pdf', 'max:'.config('faro.resumes.max_kb')],
        ]);

        $user = $request->user();

        $uploadsToday = $user->resumes()->where('created_at', '>=', now()->startOfDay())->count();
        if ($uploadsToday >= config('faro.resumes.max_uploads_per_day')) {
            throw ValidationException::withMessages([
                'resume' => __('Você já enviou :count currículos hoje. Tente de novo amanhã.', ['count' => $uploadsToday]),
            ]);
        }

        $file = $request->file('resume');
        $path = $file->storeAs('resumes/'.$user->id, Str::uuid().'.pdf', 'local');

        $user->resumes()->where('is_current', true)->update(['is_current' => false]);

        $resume = $user->resumes()->create([
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'storage_path' => $path,
            'mime_type' => 'application/pdf',
            'size_bytes' => $file->getSize(),
            'is_current' => true,
            'status' => Resume::STATUS_PENDING,
        ]);

        AnalyzeResume::dispatch($resume);

        return redirect()->route('profile.show')
            ->with('status', __('Currículo recebido! A IA está lendo e o seu perfil aparece aqui em instantes.'));
    }

    /** Consultado pela tela de perfil enquanto a análise não termina. */
    public function status(Request $request): JsonResponse
    {
        $resume = $request->user()->currentResume;

        return response()->json([
            'status' => $resume?->status,
            'label' => $resume?->statusLabel(),
            'finished' => $resume ? $resume->isFinished() : true,
            'error' => $resume?->error_message,
        ]);
    }
}
