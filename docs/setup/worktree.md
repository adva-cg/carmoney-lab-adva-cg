# worktree

Ответ ворктри-сессии «Обзор тестов в tests/Unit» (ветка `prairie-shaker`, папка `.kilo/worktrees/prairie-shaker`):

- `AssessmentServiceTest.php` — сервис оценки заявки: approve при низком LTV (лимит = запрошенной сумме), review при среднем (лимит 0), reject при высоком.
- `DecisionEngineTest.php` — DecisionEngine: решение (`approve`/`review`/`reject`) по значению LTV.
- `LtvCalculatorTest.php` — LtvCalculator: расчёт LTV в процентах, исключение при нулевой стоимости авто и неположительной сумме.
- `VinValidatorTest.php` — VinValidator: корректность формата VIN.
- `ApplicationValidatorTest.php` — ApplicationValidator: принимает валидную заявку и нормализует VIN, отклоняет год из будущего и сумму ниже минимума, собирает все ошибки разом.
