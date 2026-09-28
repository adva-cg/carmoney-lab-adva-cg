# README: раздел «Как проверить, что сервис жив»

## Цель
В `README.md` появился раздел «Как проверить, что сервис жив» с командами
проверки живости сервиса и базы, взятыми из `Makefile` и `docker-compose.yml`.

## Контекст
В `Makefile` есть цели `up` (запуск через `docker compose up -d --build`),
`ps` (статус контейнеров) и `logs` (логи `backend`). Порт снаружи — из
переменной `APP_PORT`, по умолчанию `8080`. В `docker-compose.yml` у сервиса
`db` задан `healthcheck` (`mysqladmin ping`); у `backend` healthcheck не
задан, проверка делается снаружи. Health-endpoint — `GET /health`
(описан в таблице API `README.md:50`).

## Принятое решение
Раздел вставлен в `README.md` между таблицей `make`-команд (конец строки 32)
и разделом `## API` (строка 46). Содержимое — три команды (`make up`,
`make ps`, `curl /health`) с упоминанием healthcheck базы и команды
диагностики `make logs`.

## Дизайн блока (финальный текст в `README.md:34-44`)
```markdown
## Как проверить, что сервис жив

```bash
make up                       # поднять сервис и базу (порт — $APP_PORT, по умолчанию 8080)
make ps                       # статус контейнеров (backend, db)
curl -sS http://localhost:${APP_PORT:-8080}/health
```

`/health` отвечает 200, когда backend поднялся. База считается здоровой по `mysqladmin ping`
(см. `healthcheck` в `docker-compose.yml` у сервиса `db`). Если `/health` не отвечает —
`make logs` покажет, что случилось при старте.
```

## Альтернативы, которые не выбрали
- Дать полный Docker-стек команд (`docker compose ps`, `docker compose logs`)
  вместо `make`-обёрток: хуже читается, расходится со стилем README, где всё
  идёт через `make`.
- Проверять живость только `curl /health`, без `make ps`: теряем быстрый
  способ увидеть состояние контейнеров, особенно когда база ещё стартует.

## Изменённые файлы
- `README.md` — добавлен раздел между таблицей команд и разделом `## API`
  (в файле сейчас строки 34–44).

## Валидация
1. `Read README.md:30-50` — раздел присутствует, якорь и заголовок `H2`.
2. В блоке три команды; `$APP_PORT` подставляется так же, как в `Makefile:19`.
3. Упоминание `mysqladmin ping` соответствует `docker-compose.yml:39`.
4. Существующие разделы (`## Запуск за три команды`, таблица API,
   `curl http://localhost:8080/health` на `README.md:20`) не тронуты.

## Что не в скоупе
- Не правим `Makefile`/`docker-compose.yml`.
- Не вводим healthcheck у backend в compose — это отдельная задача.
- Не добавляем проверки в `make help` или в `.githooks/`.
