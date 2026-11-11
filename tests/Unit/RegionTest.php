<?php

namespace Tests\Unit;

use App\Services\Jobs\Connectors\RemotiveConnector;
use App\Support\Region;
use Tests\TestCase;

class RegionTest extends TestCase
{
    public function test_parses_common_location_formats(): void
    {
        $this->assertSame(['city' => 'Santo André', 'state' => 'SP', 'remote' => false], Region::parse('Santo André - SP'));
        $this->assertSame(['city' => 'São Paulo', 'state' => 'SP', 'remote' => false], Region::parse('São Paulo, São Paulo, Brasil'));
        $this->assertSame(['city' => 'Mauá', 'state' => 'SP', 'remote' => false], Region::parse('Maua/SP'));
        $this->assertSame(['city' => 'São Bernardo do Campo', 'state' => 'SP', 'remote' => false], Region::parse('sao bernardo do campo'));
        $this->assertSame(['city' => 'Campinas', 'state' => 'SP', 'remote' => false], Region::parse('Campinas, SP'));
        $this->assertSame(['city' => 'Rio de Janeiro', 'state' => 'RJ', 'remote' => false], Region::parse('Rio de Janeiro, RJ'));
        $this->assertSame(['city' => null, 'state' => 'SP', 'remote' => false], Region::parse('Estado de São Paulo, SP'));
        $this->assertTrue(Region::parse('Remoto')['remote']);
        $this->assertTrue(Region::parse('Home Office - Brasil')['remote']);
        $this->assertSame(['city' => null, 'state' => null, 'remote' => false], Region::parse(''));
    }

    public function test_accepts_only_greater_sao_paulo_or_remote(): void
    {
        $this->assertTrue(Region::accepts('São Paulo', 'SP', false));
        $this->assertTrue(Region::accepts('Mauá', 'SP', false));
        $this->assertTrue(Region::accepts('Guarulhos', 'SP', false));
        $this->assertFalse(Region::accepts('Campinas', 'SP', false));
        $this->assertFalse(Region::accepts('Rio de Janeiro', 'RJ', false));
        $this->assertFalse(Region::accepts(null, null, false));
        $this->assertTrue(Region::accepts('Recife', 'PE', true));
        $this->assertSame(39, count(Region::GRANDE_SP));
    }

    public function test_region_can_be_opened_to_all_brazil(): void
    {
        config(['faro.jobs.region' => 'brasil']);

        $this->assertTrue(Region::accepts('Recife', 'PE', false));
    }

    public function test_remotive_keeps_only_jobs_open_to_brazil(): void
    {
        foreach (['Worldwide', 'Anywhere in the World', 'LATAM', 'Americas', 'Brazil', 'USA, LATAM'] as $ok) {
            $this->assertTrue(RemotiveConnector::acceptsBrazil($ok), $ok);
        }
        foreach (['USA', 'Europe', 'UK only', 'Germany', ''] as $no) {
            $this->assertFalse(RemotiveConnector::acceptsBrazil($no), $no);
        }
    }
}
