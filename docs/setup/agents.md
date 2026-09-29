# Planner
Может создавать план в docs/plan. Файлы в других расположениях менять не может.
Без явного указания scout он не вызывался

# Scout
Может осуществлять поиск по файлам, но не менять их

Ответ Planner через Scout:

# Все места, где читается пробег (mileage)

## 1. Backend (PHP)

### `backend/src/Domain/ApplicationValidator.php` — главное место чтения
| Строка | Контекст |
|---|---|
| 22 | PHPDoc нормализованного выхода: `array{...mileage:int...}` |
| 43 | `$mileage = (int) ($payload['mileage'] ?? -1);` — чтение из HTTP-входа, дефолт `-1` |
| 44 | `$mileage < 0 \|\| $mileage > $this->rules['vehicle']['max_mileage_km']` — диапазон `[0, 500000]` |
| 45 | `$errors['mileage']` — ключ ошибки валидации |
| 78 | `'mileage' => $mileage` — проброс нормализованного int в результат |

### `backend/src/Repository/ApplicationRepository.php` — чтение для записи в БД
| Строка | Контекст |
|---|---|
| 19 | PHPDoc параметра `$input`: `array{...mileage:int...}` |
| 38–39 | SQL `INSERT INTO vehicles (... mileage_km ...)` + плейсхолдер `:mileage` |
| 45 | `':mileage' => $input['mileage']` — биндинг из PHP-массива в INSERT |
| 68 | `find()`: `v.mileage_km` в SELECT — **возвращается, но ни одним потребителем не читается** |

### Проверено: НЕ читают пробег
`DecisionEngine`, `LtvCalculator`, `AssessmentService`, `VehicleAge`, `VinValidator`, `Http/ApplicationController`, `public/index.php`, `AppFactory`, `Database` — пробег не упоминается.

> Пробег **никак не влияет** на решение `approve/review/reject` — только валидация и сохранение.

## 2. Тесты (`tests/**`)

| Файл | Строка | Контекст |
|---|---|---|
| `tests/Unit/ApplicationValidatorTest.php` | 34 | Фикстура `validPayload()`: `'mileage' => 84000` |
| `tests/Unit/AssessmentServiceTest.php` | 38 | Фикстура `payload()`: `'mileage' => 96000` |

**Граничные тесты на пробег отсутствуют** (см. отдельную секцию ниже).

## 3. Схема БД и сиды (`db/**`)

| Файл | Строка | Контекст |
|---|---|---|
| `db/schema.sql` | 22 | `mileage_km INT UNSIGNED NOT NULL` — отрицательные и NULL невозможны на уровне БД |
| `db/seed.sql` | 31–55 | 24 синтетические заявки: пробег 20 000 → 296 000 км (все в пределах валидного диапазона) |

## 4. Конфиг правил

| Файл | Строка | Контекст |
|---|---|---|
| `backend/config/rules.php` | 23 | `'max_mileage_km' => 500000` — **единственный порог для пробега** в конфиге |

Отдельного порога «решения» (например, 400 000 → review) в конфиге нет.

## 5. Frontend (`frontend/**`)

| Файл | Строка | Контекст |
|---|---|---|
| `frontend/index.html` | 30–31 | `<label for="mileage">Пробег, км</label>` + `<input id="mileage" name="mileage" type="number" value="84000" required>` |
| `frontend/app.js` | 8, 14 | `mileage` в `NUMERIC_FIELDS` — приводится к `Number(value)` через `collectPayload()` |

`frontend/styles.css` — без упоминаний.

## 6. Документация и клиентские материалы

| Файл | Строка | Что сказано |
|---|---|---|
| `docs/setup/code_map.md` | 23, 27, 35–55 | Карта кода; явный план «куда встанет правило `пробег > 400 000 → review`»; что **не хватает** |
| `docs/plan/README.md` | 13–14 | Граничные значения для плана (399999/400000/400001, пустой пробег); риск рядом с `max_mileage_km` |
| `docs/README.md` | 4 | Учебная фича ещё не реализована |
| `docs/intent/intent_K-0417.md` | 5–7 | Бизнес-кейс с пробегом 412 300 (выше 400 000, ниже 500 000) |
| `docs/sources/client_note.md` | 24, 40 | Клиентский кейс: 412 300 vs 380 000 |
| `docs/sources/CASE-08.md` | 15, 23 | Скидки за пробег (TODO) |
| `docs/sources/LOAN-12.md` | 9, 36 | Пробег среди полей заявки |
| `docs/hw1/README.md` | 14 | ДЗ.1: «Пробег ≤ 400000» |
| `README.md` | 6, 59, 124 | Описание сервиса, curl-пример, таблица ДЗ |
| `AGENTS.md` | 4 | Описание входных полей |

---

## Граничные значения пробега — отдельная подсекция

### 0 (нижняя граница — проходит)
- `backend/src/Domain/ApplicationValidator.php:44` — `$mileage < 0` (0 проходит)
- `backend/src/Domain/ApplicationValidator.php:45` — текст ошибки «от 0 до …»
- `docs/setup/code_map.md:55` — документировано

### 500 000 (верхняя граница — проходит)
- `backend/config/rules.php:23` — источник порога
- `backend/src/Domain/ApplicationValidator.php:44` — `$mileage > max_mileage_km` (500 001 — нет)
- `backend/src/Domain/ApplicationValidator.php:45` — текст ошибки
- `docs/setup/code_map.md:45, 49, 55` — документировано
- `docs/plan/README.md:14` — риск

### 400 000 (планируемый порог решения → review, **НЕ реализован**)
- `docs/setup/code_map.md:35, 37, 39, 49–51, 55` — план: «правила `400 000 → review` нет»
- `docs/plan/README.md:13` — планируемые тесты 399999/400000/400001
- `docs/README.md:4` — фича ещё не реализована
- `docs/intent/intent_K-0417.md:5` — кейс 412 300 (выше 400 000, ниже 500 000)
- `docs/sources/client_note.md:24, 40` — кейс клиента
- `docs/hw1/README.md:14` — ДЗ.1
- `README.md:124` — таблица ДЗ

### Отрицательные / отсутствующий ключ
- `backend/src/Domain/ApplicationValidator.php:43` — `(int) ($payload['mileage'] ?? -1)` (отсутствие → `-1` → ошибка)
- `backend/src/Domain/ApplicationValidator.php:44` — ловит отрицательные
- `db/schema.sql:22` — `INT UNSIGNED NOT NULL` (защита на уровне БД)
- `docs/plan/README.md:13` — планируемый тест «пустой пробег»

### Нечисловые строки (фронт → бэк)
- `frontend/app.js:8` — `Number(value)`: пустая строка → `0`, нечисловая → `NaN`
- `frontend/index.html:31` — `<input type="number">` (HTML5-валидация, но не строгая)
- `backend/src/Domain/ApplicationValidator.php:43` — `(int)` для нечисловой строки даст `0` и **пройдёт** валидацию (потенциальная дыра)

### Тесты на границах — отсутствуют
В `tests/Unit/ApplicationValidatorTest.php` и `tests/Unit/AssessmentServiceTest.php` **нет ни одного** теста, который проверял бы:
- `mileage = 0`
- `mileage = 500000` / `500001`
- `mileage = -1`
- отсутствие ключа `mileage` в payload
- нечисловую строку `'abc'`

---

## Краткий итог: где реально читается пробег

1. **Валидация HTTP-входа** → `ApplicationValidator::validate()` (L43, L78)
2. **Сохранение в БД** → `ApplicationRepository::save()` (L45)
3. **Возврат из БД, но без потребителя** → `ApplicationRepository::find()` (L68, `v.mileage_km`)
4. **Приведение типа на фронте** → `frontend/app.js` (L8, L14)
5. **Тестовые фикстуры** → `ApplicationValidatorTest.php:34`, `AssessmentServiceTest.php:38`

**На решение (`approve`/`review`/`reject`) пробег сейчас не влияет.** Все материалы, связанные с будущим правилом `> 400 000 → review`, собраны в `docs/setup/code_map.md` (раздел «Куда встанет правило…»).