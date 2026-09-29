# План MILEAGE: пробег больше 400 000 км → решение review

ID задачи: `MILEAGE`. Источники: задание дня 1 (`docs/README.md`), карта кода
(`docs/setup/code_map.md:35–55`), разведка по пробегу (`docs/setup/agents.md`,
сверено с кодом 2026-09-29).

Правило: «пробег авто не больше 400 000 км, иначе решение review». Пробег
≤ 400 000 → решение по LTV как сейчас; пробег > 400 000 → решение `review`
независимо от LTV (спорный случай reject — см. «Риски» и вопрос 1). Порог не
хардкодим: новый ключ в `backend/config/rules.php`.

Примечание: файл сохранён в `.kilo/plans/plan_MILEAGE.md`, потому что текущие
права агента не разрешают запись в `docs/plan/` — целевое место по конвенции
репозитория (`docs/plan/README.md`) именно `docs/plan/plan_MILEAGE.md`; после
согласования прав файл переносится туда без изменений содержимого.

## Файлы

Всё, чего нет в списке, при реализации трогать нельзя.

- `backend/config/rules.php` — в блок `vehicle` после `max_mileage_km` (строка 23) добавить ключ `review_mileage_km => 400000` с комментарием «порог решения, не валидации»; другие блоки не трогать.
- `backend/src/Domain/DecisionEngine.php` — конструктор получает второй параметр `int $reviewMileageKm`; новая сигнатура `decide(float $ltv, int $mileage): string`; первой проверкой — `mileage > reviewMileageKm → REVIEW`, затем прежняя LTV-логика без изменений.
- `backend/src/Domain/AssessmentService.php` — строка 33: передать пробег в движок, `decide($ltv, $input['mileage'])`.
- `backend/src/AppFactory.php` — строка 37: прокинуть порог из конфига, `new DecisionEngine($rules['ltv'], $rules['vehicle']['review_mileage_km'])`.
- `tests/Unit/DecisionEngineTest.php` — в `setUp()` передать 400000 вторым аргументом; в существующем провайдере дополнить вызовы `decide()` пробегом ниже порога (ожидания не менять); добавить кейсы из раздела «Тесты».
- `tests/Unit/AssessmentServiceTest.php` — строка 27: передать `$rules['vehicle']['review_mileage_km']` вторым аргументом в `DecisionEngine`; новые кейсы из раздела «Тесты».
- `tests/Unit/ApplicationValidatorTest.php` — новые кейсы: отсутствующий `mileage`, 500000, 500001 (регрессия `max_mileage_km`).
- `docs/setup/code_map.md` — после реализации переписать строки 35–55: правило теперь в `DecisionEngine::decide()`, порог — `vehicle.review_mileage_km`; убрать «чего не хватает».
- `docs/plan/plan_MILEAGE.md` — этот план.

Не меняются: `ApplicationValidator.php`, `LtvCalculator.php`, `VehicleAge.php`,
`Http/ApplicationController.php`, `Repository/ApplicationRepository.php`,
`frontend/*`, `db/*`, `mocks/*`, `scripts/*`, `README.md`.

## Шаги

1. `backend/config/rules.php`: добавить `vehicle.review_mileage_km => 400000`.
2. Красные тесты: в `DecisionEngineTest` новые кейсы под будущую сигнатуру `decide(float $ltv, int $mileage)` — падают, правила ещё нет.
3. `DecisionEngine`: второй параметр конструктора, сигнатура `decide(float $ltv, int $mileage): string`, правило «mileage > порога → REVIEW» до LTV-веток.
4. Обновить вызовы в существующем провайдере `DecisionEngineTest` (второй аргумент — пробег ниже порога; ожидания прежние) — тест зелёный.
5. `AssessmentService.php:33`: передать `$input['mileage']` в `decide()`.
6. `AppFactory.php:37`: передать порог в конструктор `DecisionEngine`.
7. `AssessmentServiceTest`: кейсы 400001 → review (лимит 0) и 400000 → approve (лимит = сумме).
8. `ApplicationValidatorTest`: пустой пробег → `ValidationException`; регрессия 500000/500001.
9. `make test` и `make lint` — зелёные (без Docker — то же локально).
10. Ручная проверка: `make up`, POST `/api/applications` с mileage 400001 (→ review), 400000 (→ approve при низком LTV), без mileage (→ 422).
11. `docs/setup/code_map.md`: актуализировать раздел про MILEAGE.

## Тесты

Новые кейсы, граничные значения — по строке:

- `DecisionEngineTest`: ltv 50, mileage **399999** → `approve`.
- `DecisionEngineTest`: ltv 50, mileage **400000** → `approve` (ровно порог — ещё «не больше»).
- `DecisionEngineTest`: ltv 50, mileage **400001** → `review` (сразу за порогом).
- `DecisionEngineTest`: ltv 95 (зона reject), mileage 400001 → `review` — ⚠ ожидание спорно, привязано к вопросу 1.
- `DecisionEngineTest`: ltv 72.3 (зона review), mileage 400001 → `review` (не меняется).
- `AssessmentServiceTest`: mileage 400001 и низкий LTV → `decision = review`, `approved_limit = 0`.
- `AssessmentServiceTest`: mileage 400000 и низкий LTV → `decision = approve`, `approved_limit = запрошенной сумме`.
- `ApplicationValidatorTest`: payload **без ключа `mileage`** (пустой пробег) → `ValidationException`, в `errors()` есть ключ `mileage` — заявка не доходит до решения, HTTP 422, а не review.
- `ApplicationValidatorTest`: mileage 500000 → валидацию проходит (существующая граница `max_mileage_km` цела).
- `ApplicationValidatorTest`: mileage 500001 → `ValidationException` с ключом `mileage` (существующая граница цела).

Существующие тесты: ожидания не меняем (AGENTS.md). В `DecisionEngineTest`
правятся только вызовы `decide()` (новая сигнатура), в `AssessmentServiceTest` —
только конструктор в `setUp()`.

## Риски

1. Смена сигнатуры `decide()` ломает все точки вызова: прямые (`DecisionEngineTest:17,23`, `AssessmentServiceTest:27`) и сборку (`AppFactory:37`, `AssessmentService:33`). Забытый проброс порога в `AppFactory` — фатальная ошибка каждого запроса. Все правки — одним PR.
2. Рядом два порога пробега: `max_mileage_km = 500000` (валидация, `ApplicationValidator:44`, не трогаем) и новый `review_mileage_km = 400000` (решение). Итоговая схема: 0–400000 — решение по LTV; 400001–500000 — всегда review; больше 500000 — 422 без решения. Риск перепутать пороги или «подтянуть» валидацию к 400000 — тесты 500000/500001 фиксируют поведение. Менять `max_mileage_km` без риск-менеджмента нельзя (AGENTS.md).
3. Семантика «review перекрывает reject» — главная развилка. Если заказчик выберет «reject остаётся reject», меняется место проверки в `decide()` (не первой, а после reject-ветки — только понижение approve) и одно ожидание теста (95 + 400001 → reject). См. вопрос 1.
4. «Пустой пробег» на уровнях трактуется по-разному: отсутствие ключа/null → −1 → `ValidationException` → 422 (тест на этом); пустая строка `""` → `(int)"" = 0` → проходит как 0 км (существующее поведение, вне задачи); фронтенд из пустого поля шлёт `Number("") = 0` (`app.js:14`), то есть из браузера «пустой пробег» выглядит как 0 км. См. вопрос 4.
5. `approved_limit`: при review лимит 0 — логика уже есть (`AssessmentService:39`), но без теста регрессию легко не заметить (кейс в «Тестах» закрывает).
6. Существующая дыра валидации: нечисловая строка (`"abc"` → 0) проходит как 0 км — вне этой задачи, зафиксировано только здесь.
7. БД: `mileage_km INT UNSIGNED NOT NULL` (`db/schema.sql:22`) — 400001 влезает; схема, репозиторий и сиды (пробеги 20000–296000) не меняются, уже сохранённые решения не пересчитываются.
8. AGENTS.md: пороги и ожидания тестов не меняются ради зелёного `make test`; ключ 400000 — новое правило из задания, но значение требует подтверждения риск-менеджмента (вопрос 3).

Не входит:

- изменение `max_mileage_km` (500000) и любых других порогов `rules.php`;
- LOAN-12 (лимит по `ltv_by_age`) и CASE-08 (скидки за пробег);
- правки фронтенда, БД-схемы, репозитория, HTTP-контроллера (форма ответа API не меняется);
- обработка нечислового пробега и «причины review» в ответе API;
- `docs/intent/intent_MILEAGE.md` и `docs/spec/spec_MILEAGE.md` — следующие звенья цепочки (1.5.1, ДЗ.1), не часть этого плана.

## Вопросы (без заказчика не ответить)

1. **Reject или review при большом пробеге?** LTV 95% + пробег 450 000 — `review` или `reject`? Рекомендация (буквально по заданию и `code_map.md:39` — «перед reject-веткой»): review перекрывает всё. Альтернатива: reject остаётся reject, review понижает только approve. Есть ли решение риск-менеджмента?
2. **Граница «не больше 400 000».** Ровно 400 000 — ещё допустимо (возможен approve), 400 001 → review. Подтвердить: именно это фиксируют кейсы 399999/400000/400001.
3. **Число 400 000** утверждено ли риск-менеджментом как порог решения (AGENTS.md требует подтверждения порогов)?
4. **«Пустой пробег».** Подтвердить, что отсутствие/null остаётся ошибкой валидации (422), а не review; считать ли пустую строку (сейчас → 0 км) ошибкой?
5. **Видимость причины.** Нужен ли в ответе API/форме признак «review из-за пробега» (например, поле reasons)? Сейчас решение — голая строка; в этой задаче не делаем.


---
# Plan vs Planner
Три главных отличия (моё .kilo/plans/plan_MILEAGE.md против гит-версии из 7469fa2 «План от планера»):

Семантика reject: гит-план — «reject не смягчается», пробег понижает только approve→review (проверка после LTV-решения); мой — «review перекрывает и reject» (проверка первой веткой). Спорный кейс «LTV reject-зоны + 400001» ожидает противоположное: reject против review.
Порядок шагов: гит-план — сначала код, затем обязательная правка существующего провайдера до make test; мой — красный тест до кода (TDD-цепочка из docs/README.md:16).
Точки сборки: гит-план пропустил new DecisionEngine($rules['ltv']) в tests/Unit/AssessmentServiceTest.php:27 (риск №2 утверждает «других конструкций нет») — make test упал бы фатально; у меня точка закрыта. У гит-плана шире граничные кейсы (85.0+400000, 399999 на уровне сервиса), у меня — регрессия 500000/500001.