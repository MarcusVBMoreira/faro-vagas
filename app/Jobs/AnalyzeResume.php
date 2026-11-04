<?php

namespace App\Jobs;

use App\Models\Resume;
use App\Services\Ai\AiException;
use App\Services\ProfileUpdater;
use App\Services\Resume\PdfTextExtractor;
use App\Services\Resume\ResumeAnalyzer;
use App\Services\Resume\UnreadablePdfException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Lê o PDF, pede para a IA montar o perfil e grava o resultado.
 * Roda na fila porque a IA (CPU do VPS) leva de 20 a 60 segundos.
 */
class AnalyzeResume implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 600;

    public int $backoff = 30;

    public function __construct(public Resume $resume)
    {
    }

    public function handle(PdfTextExtractor $extractor, ResumeAnalyzer $analyzer, ProfileUpdater $updater): void
    {
        $resume = $this->resume->fresh();

        // Se o usuário já enviou outro currículo, este não importa mais.
        if (! $resume || ! $resume->is_current) {
            return;
        }

        $resume->update(['status' => Resume::STATUS_PROCESSING, 'error_message' => null]);

        try {
            if (blank($resume->extracted_text)) {
                $text = $extractor->extract(Storage::disk('local')->path($resume->storage_path));
                $resume->update(['extracted_text' => $text]);
            }

            $data = $analyzer->analyze($resume->extracted_text, $resume->user);
            $updater->applyFromAi($resume->user, $data);
        } catch (UnreadablePdfException $e) {
            $this->markFailed($resume, __('Não conseguimos ler o texto desse PDF. Se ele é uma imagem escaneada, exporte o currículo do Word ou do Google Docs como PDF e envie de novo.', [], 'pt_BR'));

            return;
        } catch (AiException $e) {
            Log::warning('Falha na IA ao analisar currículo', ['resume' => $resume->id, 'erro' => $e->getMessage()]);

            if (str_contains($e->getMessage(), 'Limite diário')) {
                $this->markFailed($resume, __('Você atingiu o limite de análises de hoje. Tente de novo amanhã.', [], 'pt_BR'));

                return;
            }

            if ($this->attempts() < $this->tries) {
                $resume->update(['status' => Resume::STATUS_PENDING]);
                $this->release($this->backoff);

                return;
            }

            $this->markFailed($resume, __('A IA está indisponível no momento. Tente enviar de novo em alguns minutos.', [], 'pt_BR'));

            return;
        }

        $resume->update(['status' => Resume::STATUS_DONE, 'parsed_at' => now(), 'error_message' => null]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->markFailed($this->resume->fresh() ?? $this->resume, __('Algo deu errado ao analisar o currículo. Tente enviar de novo.', [], 'pt_BR'));
    }

    /**
     * A mensagem é gravada em português (a chave da tradução) e traduzida na hora de exibir,
     * no idioma de quem estiver vendo a página.
     */
    private function markFailed(Resume $resume, string $message): void
    {
        $resume->update(['status' => Resume::STATUS_FAILED, 'error_message' => $message]);
    }
}
