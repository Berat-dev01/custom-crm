<?php

namespace Tests\Unit;

use App\Crm\Contracts\AiProviderContract;
use App\Crm\Services\Ai\AiAssistant;
use App\Crm\Services\Ai\AiDriverManager;
use App\Crm\Services\Ai\PromptTemplates;
use Mockery;
use Tests\TestCase;

class AiAssistantPromptTest extends TestCase
{
    public function test_it_passes_clean_bounded_context_to_provider(): void
    {
        $provider = new class implements AiProviderContract
        {
            public string $content = '';

            public array $context = [];

            public function summarize(string $content, array $context = []): string
            {
                $this->content = $content;
                $this->context = $context;

                return 'Clean summary.';
            }

            public function draftEmail(string $brief, array $context = []): string
            {
                return 'Draft.';
            }

            public function draftFollowUp(string $brief, array $context = []): string
            {
                return 'Follow-up.';
            }

            public function analyzeLostDeal(string $brief, array $context = []): string
            {
                return 'Analysis.';
            }
        };
        $manager = Mockery::mock(AiDriverManager::class);
        $manager->shouldReceive('available')->once()->andReturn(true);

        $assistant = new AiAssistant($provider, $manager, new PromptTemplates);
        $result = $assistant->summarizeNote(null, '<b>Call notes</b><script>alert(1)</script>');

        $this->assertTrue($result->ok);
        $this->assertSame('Clean summary.', $result->content);
        $this->assertSame('Call notesalert(1)', $provider->content);
        $this->assertStringContainsString('bounded context', $provider->context['system']);
        $this->assertStringContainsString('Do not claim', $provider->context['system']);
        $this->assertSame('Summarize the CRM note/activity into decisions, risks, next steps, and a one-line customer mood.', $provider->context['task']);
    }
}
