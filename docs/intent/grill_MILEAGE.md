Как трактуем границу «не больше 400 000 км»: 400 000 ещё проходит без review (review начинается с 400 001) или 400 000 уже даёт review?
400 000 проходит (Recommended)

Question 1 answered: 400 000 inclusive passes, review from 400 001. Now question 2: reject semantics.

Заявка с reject по LTV и пробегом > 400 000: остаётся reject, или правило по пробегу перекрывает и понижает её до review?
Reject сильнее (Recommended)

Q2 answered: reject stays reject; rule only downgrades approve→review. Q3: empty mileage (string "" → 0 via (int) cast, frontend sends 0). Missing key case is settled (422). The open sub-question: should empty string be a validation error or stay as-is (out of scope)?

Отсутствие ключа `mileage` — ясно (422, существующее поведение). А пустая строка в поле пробега сейчас превращается в 0 км и проходит валидацию: оставить это вне scope задачи или сделать пустое поле ошибкой валидации?

А что такое 422?

422 — это HTTP-код ответа 422 Unprocessable Content: когда ApplicationValidator находит ошибку во входных данных (например, нет ключа mileage или год из будущего), контроллер (backend/src/Http/ApplicationController.php:30) выбрасывает ValidationException, и API возвращает статус 422 с телом {"errors": {...}} вместо решения — заявка просто не доходит до расчёта approve/review/reject. Повторю вопрос:

Если поле пробега пришло пустой строкой, сейчас оно превращается в 0 км и проходит валидацию (это баг-дыра, но не ломает новое правило: 0 < 400 000). Оставить это вне scope задачи MILEAGE или включить в неё?

Сделать ошибкой 422

Добавлять ли в ответ API поле причины решения (например, `reason: "high_mileage"`, когда review возник из-за пробега)? Сейчас поля причины нет, в плане его нет.

Не знаю, это к архитектору