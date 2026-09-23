# Подключение Self-hosted GitLab к Cursor

## Проблема
Cursor по умолчанию поддерживает только GitHub.com и GitLab.com, но у нас self-hosted GitLab на `gitlab.our24.ru`.

## Решение 1: Через настройки Cursor

1. Откройте настройки Cursor (`Cmd/Ctrl + ,`)
2. Найдите раздел "Source Control" или "Git"
3. Добавьте custom Git provider:
   - Host: `gitlab.our24.ru`
   - Repository: `ourcrm3/modules/wms`
   - Protocol: SSH

## Решение 2: Через расширения

1. Установите расширение "GitLens" в Cursor
2. В настройках GitLens укажите:
   - Remote URL: `git@gitlab.our24.ru:ourcrm3/modules/wms.git`
   - Custom GitLab instance: `https://gitlab.our24.ru`

## Решение 3: Работа через облачную копию

Поскольку у нас есть mirror на GitLab.com, можно:

1. Работать с основным репозиторием локально
2. Синхронизировать изменения с облачной копией:
   ```bash
   git push origin master  # в self-hosted
   git push cloud master   # в облако
   ```

## Решение 4: Personal Access Token

1. Создайте Personal Access Token в вашем GitLab:
   - Перейдите в `https://gitlab.our24.ru/-/profile/personal_access_tokens`
   - Создайте токен с правами: `api`, `read_repository`, `write_repository`

2. Добавьте токен в переменные окружения:
   ```bash
   export GITLAB_TOKEN="your-token-here"
   export GITLAB_URL="https://gitlab.our24.ru"
   ```

## Текущая конфигурация репозитория

```
Remote repositories:
- origin: git@gitlab.our24.ru:ourcrm3/modules/wms.git (self-hosted)
- cloud:  git@gitlab.com:ourcrm3/modules/wms.git (GitLab.com mirror)
```

## Рекомендуемый workflow

1. Основная работа ведется с `origin` (self-hosted GitLab)
2. Периодически синхронизируем с `cloud` для backup
3. Cloud Agents настроены для работы с локальной копией
4. Все коммиты идут в основной репозиторий на `gitlab.our24.ru`

```bash
# Обычный workflow
git add .
git commit -m "Описание изменений"
git push origin master

# Синхронизация с облаком (опционально)
git push cloud master
```