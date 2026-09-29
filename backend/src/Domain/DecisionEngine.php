<?php

declare(strict_types=1);

namespace CarMoneyLab\Domain;

/**
 * Решение по заявке на основании LTV и пробега.
 *
 *   LTV <= approve_max              -> approve
 *   approve_max < LTV <= review_max -> review
 *   LTV > review_max                -> reject
 *
 * Дополнительно: если решение по LTV — approve, а пробег строго выше
 * reviewMileageKm, оно понижается до review. reject и review по LTV
 * пробег не смягчает.
 */
final class DecisionEngine
{
    public const APPROVE = 'approve';
    public const REVIEW = 'review';
    public const REJECT = 'reject';

    private float $approveMax;
    private float $reviewMax;
    private int $reviewMileageKm;

    /** @param array{approve_max:float,review_max:float} $thresholds */
    public function __construct(array $thresholds, int $reviewMileageKm)
    {
        $this->approveMax = $thresholds['approve_max'];
        $this->reviewMax = $thresholds['review_max'];
        $this->reviewMileageKm = $reviewMileageKm;
    }

    public function decide(float $ltv, int $mileage): string
    {
        if ($ltv < $this->approveMax) {
            return $mileage > $this->reviewMileageKm ? self::REVIEW : self::APPROVE;
        }

        if ($ltv <= $this->reviewMax) {
            return self::REVIEW;
        }

        return self::REJECT;
    }
}
