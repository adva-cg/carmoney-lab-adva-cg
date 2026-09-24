# code_map — фича «пробег ≤ 400 000 км, иначе review»

## Цепочка решения (файл → функция → порядок)

1. `backend/src/Domain/ApplicationValidator.php` → `validate($payload)`
   — нормализует поля, проверяет VIN / год / пробег / сумму / срок; при ошибках — `ValidationException`.
2. `backend/src/Domain/LtvCalculator.php` → `calculate($requested_amount, $market_value)`
   — LTV % = сумма / оценочная стоимость × 100.
3. `backend/src/Domain/DecisionEngine.php` → `decide($ltv)`
   — только по порогам `rules.ltv`: approve / review / reject.
4. `backend/src/Domain/AssessmentService.php` → `assess($payload)`
   — оркестратор: validate → LTV → decide → `approved_limit`; возраст через `VehicleAge::inYears`.

Пороги и лимиты: `backend/config/rules.php` (`vehicle`, `amount`, `term`, `ltv`).

Вызов снаружи (не Domain, для контекста): `ApplicationController` → `AssessmentService::assess`.

## Точка вставки правила «пробег > 400 000 → review»

Файл: `backend/src/Domain/AssessmentService.php`, метод `assess`.
Место: сразу после `$decision = $this->decisionEngine->decide($ltv);` — до сборки return.
Почему не в `DecisionEngine::decide`: сейчас он принимает только `float $ltv`, пробега не видит.
Почему не в `ApplicationValidator`: там превышение лимита — ошибка валидации, а не решение `review`.

★ Фрагмент вокруг точки вставки (сверка с файлом):

```php
        $ltv = $this->ltvCalculator->calculate($input['requested_amount'], $input['market_value']);
        $decision = $this->decisionEngine->decide($ltv);
        // ← сюда: если mileage выше порога — decision = review (порог из rules.php)
        return [
            'vehicle_age' => $this->vehicleAge->inYears($input['year']),
```

Порог 400000 — в `rules.php` (новый ключ), не хардкод в Domain. Сейчас такого порога в конфиге нет.

## Входные данные для правила

- Есть: `mileage` в payload → после `validate` в `$input['mileage']`.
- Нет: отдельного порога «review при большом пробеге» в `rules.php` (есть только `max_mileage_km` для валидации).
- Нет: учёта пробега в `DecisionEngine` / в расчёте LTV.

## Что уже проверяется про пробег

- `rules.php` → `vehicle.max_mileage_km` = **500000**.
- `ApplicationValidator::validate`: `mileage` от 0 до `max_mileage_km`, иначе ошибка поля `mileage` (заявка не оценивается).
- Решения `approve` / `review` / `reject` по пробегу сейчас нет.
