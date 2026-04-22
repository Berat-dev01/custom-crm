<?php

namespace Tests\Unit;

use InvalidArgumentException;
use Sanalkopru\Crm\Services\Ai\AiDriverManager;
use Sanalkopru\Crm\Support\Ai\AiDriver;
use Tests\TestCase;

class AiDriverManagerTest extends TestCase
{
    public function test_it_resolves_the_selected_ai_driver(): void
    {
        config([
            'crm.ai.driver' => 'claude',
            'crm.ai.drivers.claude.model' => 'claude-custom-model',
        ]);

        $manager = app(AiDriverManager::class);

        $this->assertSame(AiDriver::Claude, $manager->selected());
        $this->assertSame('claude-custom-model', $manager->model());
    }

    public function test_it_exposes_generation_defaults(): void
    {
        config([
            'crm.ai.max_tokens' => 800,
            'crm.ai.temperature' => 0.2,
        ]);

        $manager = app(AiDriverManager::class);

        $this->assertSame(800, $manager->maxTokens());
        $this->assertSame(0.2, $manager->temperature());
    }

    public function test_it_supports_null_ai_driver_for_disabled_ai(): void
    {
        config([
            'crm.ai.enabled' => false,
            'crm.ai.driver' => 'null',
        ]);

        $manager = app(AiDriverManager::class);

        $this->assertFalse($manager->enabled());
        $this->assertSame(AiDriver::Null, $manager->selected());
        $this->assertNull($manager->model());
    }

    public function test_it_rejects_unknown_ai_drivers(): void
    {
        config(['crm.ai.driver' => 'unsupported']);

        $this->expectException(InvalidArgumentException::class);

        app(AiDriverManager::class)->selected();
    }
}
