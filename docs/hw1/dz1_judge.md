# DZ.1 Judge: MILEAGE

- Дата проверки: 2026-09-29
- Спецификация: `docs/spec/spec_MILEAGE.md`
- План: `docs/plan/plan_MILEAGE.md`
- Проверенные тесты: `tests/Unit/DecisionEngineTest.php`, `tests/Unit/AssessmentServiceTest.php`, `tests/Unit/ApplicationValidatorTest.php`, `tests/Unit/LtvCalculatorTest.php`
- Критерий: REQ считается покрытым только когда тест проверяет заявленное наблюдаемое поведение. Инспекция кода и конфигурации отмечена отдельно и не заменяет тест.

## Итог

Утверждение «каждый REQ покрыт тестом» **не подтверждается**. Основная логика решения и граничные значения 399999 / 400000 / 400001 покрыты, но конфигурационные инварианты и часть API-контрактов проверяются только инспекцией либо не проверяются вовсе.

Утверждение «лишних требований нет» **подтверждается для новых MILEAGE-тестов**: каждый новый сценарий соответствует REQ-MILEAGE-01...10 или сохраняемому регрессионному поведению. Существующие тесты VIN, года, суммы, срока и LTV не относятся к MILEAGE, но являются предсуществующими регрессионными проверками, а не новыми бизнес-требованиями задачи.

## Матрица REQ -> тесты

| REQ | Доказательство в тестах | Статус | Пробел |
|---|---|---|---|
| REQ-MILEAGE-01 | `DecisionEngineTest::testDecidesByLtvAndMileage`, набор `пробег 400001 понижает approve до review`; `AssessmentServiceTest::testApproveLtvWithMileage400001DowngradesToReviewAndZeroesLimit` | Покрыт | Нет |
| REQ-MILEAGE-02 | `DecisionEngineTest`, наборы с 399999, 400000 и 400001; сервисные тесты с теми же границами | Покрыт | Нет |
| REQ-MILEAGE-03 | `DecisionEngineTest`, наборы `серая зона + пробег выше порога` и `зона reject + пробег выше порога`; `AssessmentServiceTest::testRejectLtvWithHighMileageStaysRejectAndZeroesLimit` | Частично | Для одной и той же LTV-review заявки не проверены все три значения 399999 / 400000 / 400001, как сформулировано в AC-MILEAGE-03. Критичная ветка при 400001 покрыта. |
| REQ-MILEAGE-04 | `DecisionEngineTest` сохраняет решения LTV при пробеге 399999/400000, включая LTV 85.0; `LtvCalculatorTest` проверяет расчёт LTV | Частично | Нет теста, который читает `rules.php` и фиксирует значения `ltv.approve_max = 60.0`, `ltv.review_max = 85.0`. |
| REQ-MILEAGE-05 | Нет | Не покрыт тестом | Нет теста на `vehicle.review_mileage_km = 400000`; нет статической проверки отсутствия литерала 400000 в production-классам. Реализация инспекцией соответствует: ключ есть в `backend/config/rules.php:27`, `DecisionEngine` получает значение в конструкторе. |
| REQ-MILEAGE-06 | `ApplicationValidatorTest::testRejectsMissingMileageKey`, `testRejectsEmptyMileageString` | Частично | Покрыта доменная ошибка `ValidationException`, но не HTTP 422 через `ApplicationController` и не отсутствие расчёта на API-уровне. |
| REQ-MILEAGE-07 | `ApplicationValidatorTest::testRejectsNonNumericMileageString`, `testAcceptsNumericStringMileageAndNormalises`, `testAcceptsZeroMileage` | Частично | Нет HTTP-422 feature-теста и отдельного кейса float, хотя текст REQ сохраняет обработку int/float/числовых строк. |
| REQ-MILEAGE-08 | `ApplicationValidatorTest::testAcceptsMileageAtValidationLimit`, `testRejectsMileageAboveValidationLimit`, `testRejectsNegativeMileage` | Частично | Нет сервисного теста: 500000 + LTV-approve -> review; нет HTTP-422 feature-теста для 500001. |
| REQ-MILEAGE-09 | `AssessmentServiceTest` проверяет approve/limit на 400000, review/0 на 400001 и reject/0 на 450000 | Частично | Нет API-теста состава полей ответа и отсутствия нового поля `reason`. |
| REQ-MILEAGE-10 | Нет | Не покрыт тестом | Нет теста `review_mileage_km < max_mileage_km` и сценария 450000 + LTV-approve -> review. Существующий 450000-кейс находится в LTV-reject и не доказывает достижимость нового правила. |

## Проверка отсутствия лишних требований

Новые тестовые ожидания сопоставляются со спецификацией:

| Новый сценарий | Основание |
|---|---|
| 399999 / 400000 / 400001 и переход approve -> review | REQ-MILEAGE-01, REQ-MILEAGE-02 |
| review и reject при пробеге выше порога | REQ-MILEAGE-03 |
| `approved_limit` равен 0 для review/reject | REQ-MILEAGE-09 |
| Нет ключа mileage, `''`, `'abc'` | REQ-MILEAGE-06, REQ-MILEAGE-07 |
| `'84000'`, 0, 500000, 500001, -1 | REQ-MILEAGE-07, REQ-MILEAGE-08 |
| Существующие LTV-кейсы 28.5, 45.0, 72.3, 85.0, 85.01, 120.0 | Регрессия существующей логики; REQ-MILEAGE-04 требует не сдвигать LTV-решения |

Нового поля ответа, нового решения, изменения LTV-порогов, лимитов, фронтенда или БД тесты не требуют. Лишних бизнес-требований не найдено.

## Наблюдения, влияющие на достоверность тестов

1. `AssessmentServiceTest.php:29` использует `(int) ($rules['vehicle']['review_mileage_km'] ?? 400000)`. Fallback не входит в план и скрывает отсутствие ключа в конфигурации в этом тесте. `AppFactory.php:37` берёт ключ без fallback, поэтому runtime всё же упадёт при отсутствии ключа. Для REQ-MILEAGE-05 лучше добавить отдельный тест конфига вместо опоры на fallback.
2. Порог 400000 в `DecisionEngineTest` допустим как тестовая фикстура по плану (план, «Файлы», п. 6), но он не доказывает, что production использует `rules.php`.
3. `docs/setup/code_map.md` по плану должен быть обновлён после реализации (план, «Файлы», п. 9), однако это не REQ и не влияет на матрицу покрытия.

## Рекомендуемые недостающие тесты

1. Конфигурационный unit-тест: `review_mileage_km === 400000`, `max_mileage_km === 500000`, `review_mileage_km < max_mileage_km`.
2. Сервисный тест: LTV 50 + пробег 450000 -> `review`, `approved_limit = 0`.
3. Сервисный или feature-тест: пробег 500000 + LTV-approve -> `review`.
4. Feature-тесты `/api/ltv`: `mileage: ''` и `'abc'` возвращают HTTP 422 с ключом `mileage`; успешный ответ не содержит `reason` и сохраняет набор полей.
5. При буквальном выполнении AC-MILEAGE-03: один LTV-review кейс на 399999, 400000 и 400001.
