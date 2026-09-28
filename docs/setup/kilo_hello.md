# kilo_hello

1) Сервис предварительной оценки заявки на заём под ПТС: принимает заявку (VIN, год, пробег, оценочная стоимость, сумма, срок), считает LTV и возвращает решение approve / review / reject; учебный проект практикума М3, все данные синтетические.
2) Из Makefile: `make up`, `make down`, `make test`, `make lint`, `make install`, `make seed`, `make ps`, `make logs`, `make help`. В docker-compose.yml сервис поднимается через `docker compose up -d --build` (Makefile делает это сам), стартует `php -S 0.0.0.0:8080` из образа `backend/Dockerfile` и база `mysql:8.0` с init-скриптами `db/schema.sql` и `db/seed.sql`.
3) Решение `approve` / `review` / `reject` считается в `backend/src/Domain/DecisionEngine.php` (правила — в `backend/config/rules.php`, обвязка — `backend/src/Domain/AssessmentService.php`).

модель: training-2026-09-minimax-m3
