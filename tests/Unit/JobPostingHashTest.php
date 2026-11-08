<?php

namespace Tests\Unit;

use App\Enums\ApplicationStatus;
use App\Models\JobPosting;
use Tests\TestCase;

class JobPostingHashTest extends TestCase
{
    public function test_hash_ignores_case_and_spaces(): void
    {
        $a = JobPosting::makeHash('https://exemplo.com/vaga/1', 'Estágio PHP', 'Acme');
        $b = JobPosting::makeHash(' HTTPS://exemplo.com/vaga/1 ', 'estágio php', 'ACME ');

        $this->assertSame($a, $b);
        $this->assertSame(64, strlen($a));
    }

    public function test_different_jobs_have_different_hashes(): void
    {
        $this->assertNotSame(
            JobPosting::makeHash('https://exemplo.com/vaga/1', 'Estágio PHP'),
            JobPosting::makeHash('https://exemplo.com/vaga/2', 'Estágio PHP'),
        );
    }

    public function test_every_status_has_a_portuguese_label(): void
    {
        foreach (ApplicationStatus::cases() as $status) {
            $this->assertNotSame('', $status->label());
        }
    }
}
