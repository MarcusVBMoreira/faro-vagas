<?php

namespace App\Contracts;

use App\Models\JobSource;

/**
 * Um conector lê vagas de uma fonte (API pública, agregador...) e devolve JobData.
 * Para adicionar uma fonte nova: implementar esta interface e registrar o tipo em JobConnectors.
 */
interface JobSourceConnector
{
    /** A fonte tem o que precisa para rodar (ex.: chave de API no .env)? */
    public function isConfigured(): bool;

    /** @return iterable<\App\Services\Jobs\JobData> */
    public function fetch(JobSource $source): iterable;
}
