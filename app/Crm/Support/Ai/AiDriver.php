<?php

namespace App\Crm\Support\Ai;

enum AiDriver: string
{
    case OpenAI = 'openai';
    case Claude = 'claude';
    case Gemini = 'gemini';
    case Null = 'null';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $driver): string => $driver->value,
            self::cases()
        );
    }
}
