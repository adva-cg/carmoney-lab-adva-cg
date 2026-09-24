# worktree — две копии репо

## `git worktree list`

```
C:/git/carmoney-lab                               bf581b2 [d1/1.2.1-1.2.3-adva-cg]
C:/git/carmoney-lab/.kilo/worktrees/session-unit  bf581b2 [d1/1.2.3-wt-adva-cg]
```

## Ответ агента из второй сессии (worktree)

Тесты в `tests/Unit/`:
- `ApplicationValidatorTest.php` — валидация полей заявки и ValidationException.
- `AssessmentServiceTest.php` — сквозная оценка: LTV, decision, approved_limit.
- `DecisionEngineTest.php` — пороги approve / review / reject по LTV.
- `LtvCalculatorTest.php` — расчёт LTV в процентах и ошибки на нулевых суммах.
- `VinValidatorTest.php` — формат VIN (длина, запрещённые буквы).

Папка: `C:/git/carmoney-lab/.kilo/worktrees/session-unit`
Ветка: `d1/1.2.3-wt-adva-cg`

## ★ Почему два агента в одной папке на одной ветке — плохая идея

Они правят одни и те же файлы и конкурируют за индекс/working tree: правки и коммиты смешиваются.
Worktree даёт каждой сессии свою копию и ветку, поэтому параллельная работа не ломает друг друга.
