# Настройка Cursor Cloud Agents для WMS проекта

## 🚨 Проблема: "The source control provider for repository ourcrm3/modules/wms is not connected"

Эта ошибка возникает потому, что Cursor Cloud Agents не может автоматически подключиться к приватному репозиторию на GitLab.com.

## 🔧 Решение 1: Настройка SSH доступа

1. **Добавьте SSH ключ в GitLab.com**:
   - Перейдите в https://gitlab.com/-/profile/keys
   - Добавьте ваш публичный SSH ключ
   - Убедитесь, что ключ имеет доступ к репозиторию `ourcrm3/modules/wms`

2. **Проверьте доступ**:
   ```bash
   ssh -T git@gitlab.com
   git ls-remote git@gitlab.com:ourcrm3/modules/wms.git
   ```

## 🔑 Решение 2: Personal Access Token

1. **Создайте токен в GitLab.com**:
   - https://gitlab.com/-/profile/personal_access_tokens
   - Права: `read_repository`, `write_repository`, `api`

2. **Настройте HTTPS доступ**:
   ```bash
   git remote set-url origin https://gitlab-token:[TOKEN]@gitlab.com/ourcrm3/modules/wms.git
   ```

## 🏗️ Решение 3: Публичное зеркало (рекомендуется)

Создать публичную копию репозитория для Cloud Agents:

1. **Fork репозиторий** как публичный на GitLab.com
2. **Обновить URL** в конфигурации:
   ```bash
   git remote set-url origin https://gitlab.com/[YOUR_USERNAME]/wms.git
   ```

## 📋 Текущая конфигурация репозитория

```
Repository: ourcrm3/modules/wms
Provider: GitLab.com  
SSH URL: git@gitlab.com:ourcrm3/modules/wms.git
HTTPS URL: https://gitlab.com/ourcrm3/modules/wms.git
Status: Private (requires authentication)
```

## 🔄 Автоматическая синхронизация

После настройки доступа используйте скрипт для синхронизации:

```bash
./sync-repos.sh  # Синхронизирует все репозитории
```

## 🚀 Запуск в Cloud Agents

После решения проблемы с доступом:

1. **Trigger build** в Cursor Cloud Agents
2. Среда автоматически:
   - Клонирует репозиторий
   - Установит зависимости
   - Выполнит миграции
   - Запустит Laravel сервер

## ⚠️ Альтернативное решение

Если проблемы с доступом продолжаются, можно работать **без интеграции** с source control:

1. Код уже присутствует в локальной папке
2. Git операции работают через терминал
3. Используйте `./sync-repos.sh` для синхронизации
4. Cloud Agents будут работать с локальными файлами

## 📞 Проверка статуса

```bash
# Проверить доступ к репозиторию
git ls-remote origin

# Проверить все remotes
git remote -v

# Проверить конфигурацию
cat .cursor/repository.json
```