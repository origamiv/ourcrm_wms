# ✅ Финальная настройка remotes для Cursor Cloud Agents

## 🎯 Правильная конфигурация

### Git Remotes
```
origin → git@gitlab.our24.ru:ourcrm3/modules/wms.git (основной репозиторий)
cloud  → git@github.com:origamiv/ourcrm_wms.git     (для Cursor Cloud Agents)
```

### Логика работы
- **Origin (GitLab)**: Основная разработка, source of truth
- **Cloud (GitHub)**: Зеркало для интеграции с Cursor Cloud Agents

## 🚀 Ежедневный workflow

### Обычная разработка
```bash
# Разработка как обычно с origin (GitLab)
git add .
git commit -m "Описание изменений"
git push origin master

# Синхронизация с GitHub для Cloud Agents
git push cloud master

# Или сразу в оба (рекомендуется)
./sync-repos.sh
```

### Использование git-helpers
```bash
# Загрузить helpers
source ./git-helpers.sh

# Быстрые команды
git-push-main      # → GitLab
git-push-cloud     # → GitHub  
git-push-all       # → оба
git-sync-status    # проверка синхронизации
```

## 🔧 Cursor Cloud Agents

### Конфигурация
- **Repository URL**: `https://github.com/origamiv/ourcrm_wms.git`
- **Provider**: GitHub
- **Primary remote для Cursor**: `cloud`
- **Development remote**: `origin`

### Триггер build
1. Откройте: https://cursor.com/dashboard/cloud-agents/environments/e/e73a5852-b77b-11f1-bb68-864e54d14197
2. Нажмите "Trigger build"
3. Cursor будет клонировать из GitHub репозитория

## 📊 Преимущества текущей настройки

### ✅ Плюсы
- **Сохранен основной workflow**: GitLab остается главным
- **Cursor интеграция**: GitHub обеспечивает совместимость с Cloud Agents
- **Автоматическая синхронизация**: Один скрипт обновляет оба
- **Резервное копирование**: Код дублируется в двух местах
- **Безопасность**: Self-hosted GitLab защищен, GitHub доступен для CI/CD

### 🔄 Синхронизация
```
Self-hosted GitLab ←→ Local Development ←→ GitHub.com
    (origin)              (commits)         (cloud)
   Source of Truth                      Cursor Cloud Agents
```

## 🛠️ Доступные команды

### Основные
```bash
./sync-repos.sh              # Синхронизация обоих репозиториев
source ./git-helpers.sh      # Загрузка вспомогательных функций
```

### Git helpers (после загрузки)
```bash
git-push-main               # Push только в GitLab
git-push-cloud              # Push только в GitHub
git-push-all                # Push в оба репозитория
git-pull-main               # Pull из GitLab
git-sync-status             # Статус синхронизации
git-show-remotes            # Показать remotes
```

### Проверка
```bash
git remote -v               # Текущие remotes
git log --oneline -5        # Последние коммиты
git status                  # Статус рабочей копии
```

## 🎉 Готовность

- ✅ **Origin**: Self-hosted GitLab (gitlab.our24.ru)
- ✅ **Cloud**: GitHub.com (для Cursor)
- ✅ **Синхронизация**: Автоматическая через скрипты
- ✅ **Cursor Cloud Agents**: Настроены для работы с GitHub
- ✅ **Документация**: Полная с примерами команд

**Можете запускать "Trigger build" в Cursor Cloud Agents!** 🚀

## 📝 Примечания

- SSH ключ должен быть добавлен в GitHub: https://github.com/settings/keys
- При проблемах с доступом используйте Personal Access Token
- Основная разработка ведется через GitLab (origin)
- GitHub (cloud) используется только для Cursor интеграции