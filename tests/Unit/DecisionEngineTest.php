<?php

declare(strict_types=1);

namespace CarMoneyLab\Tests\Unit;

use CarMoneyLab\Domain\DecisionEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DecisionEngineTest extends TestCase
{
    private DecisionEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new DecisionEngine(
            ['approve_max' => 60.0, 'review_max' => 85.0],
            400000,
        );
    }

    #[DataProvider('ltvAndMileageValues')]
    public function testDecidesByLtvAndMileage(float $ltv, int $mileage, string $expected): void
    {
        self::assertSame($expected, $this->engine->decide($ltv, $mileage));
    }

    /** @return array<string,array{float,int,string}> */
    public static function ltvAndMileageValues(): array
    {
        return [
            'низкий LTV, пробег ниже порога' => [28.5, 399999, DecisionEngine::APPROVE],
            'середина зелёной зоны, пробег ниже порога' => [45.0, 399999, DecisionEngine::APPROVE],
            'серая зона LTV, пробег ниже порога' => [72.3, 399999, DecisionEngine::REVIEW],
            'верхняя граница серой зоны, пробег ниже порога' => [85.0, 399999, DecisionEngine::REVIEW],
            'сразу за верхней границей, пробег ниже порога' => [85.01, 399999, DecisionEngine::REJECT],
            'высокий LTV, пробег ниже порога' => [120.0, 399999, DecisionEngine::REJECT],

            'граница 400000 включительно — ещё approve' => [28.5, 400000, DecisionEngine::APPROVE],
            'пробег 400001 понижает approve до review' => [28.5, 400001, DecisionEngine::REVIEW],
            'серая зона + пробег выше порога — review остаётся review' => [72.3, 400001, DecisionEngine::REVIEW],
            'зона reject + пробег выше порога — reject остаётся reject' => [90.0, 400001, DecisionEngine::REJECT],
            'верхняя граница LTV на 400000 — review, LTV-граница не сдвинулась' => [85.0, 400000, DecisionEngine::REVIEW],
        ];
    }
}
