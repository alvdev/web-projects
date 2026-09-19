<?php

declare(strict_types=1);

namespace Tests\Unit\Ai;

use DiarioGames\AI\AIClient;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;

final class AIClientTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    public function testTranslateReturnsInputUnchangedForBlankText(): void
    {
        $this->assertSame('', AIClient::translate(''));
        $this->assertSame('   ', AIClient::translate('   '));
    }

    public function testRewriteReturnsInputUnchangedForBlankText(): void
    {
        $this->assertSame('', AIClient::rewrite(''));
    }

    public function testGenerateReturnsEmptyForBlankPrompt(): void
    {
        $this->assertSame('', AIClient::generate(''));
    }

    public function testBuildMessagesShape(): void
    {
        $this->assertSame([
            ['role' => 'system', 'content' => 'system prompt'],
            ['role' => 'user', 'content' => 'user text'],
        ], AIClient::buildMessages('system prompt', 'user text'));
    }

    public function testParseCompletionExtractsTrimmedContent(): void
    {
        $data = ['choices' => [['message' => ['content' => "  hola mundo \n"]]]];

        $this->assertSame('hola mundo', AIClient::parseCompletion($data));
    }

    public function testParseCompletionReturnsNullForMissingOrBlankContent(): void
    {
        $this->assertNull(AIClient::parseCompletion([]));
        $this->assertNull(AIClient::parseCompletion(['choices' => [['message' => ['content' => '   ']]]]));
        $this->assertNull(AIClient::parseCompletion(['choices' => [['message' => []]]]));
    }
}
