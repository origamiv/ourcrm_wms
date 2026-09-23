#!/bin/bash

# Скрипт для настройки webhook между self-hosted GitLab и Cursor

echo "🔗 Настройка интеграции с self-hosted GitLab"

GITLAB_HOST="gitlab.our24.ru"
PROJECT_ID="ourcrm3/modules/wms"

echo "📋 Для подключения self-hosted GitLab к Cursor:"
echo ""
echo "1. Создайте Personal Access Token в GitLab:"
echo "   https://${GITLAB_HOST}/-/profile/personal_access_tokens"
echo "   Права: api, read_repository, write_repository"
echo ""
echo "2. Добавьте webhook в проект:"
echo "   https://${GITLAB_HOST}/${PROJECT_ID}/-/hooks"
echo "   URL: https://cursor.sh/api/webhooks/gitlab"
echo "   Trigger: Push events, Issues events, Merge requests events"
echo ""
echo "3. В настройках Cursor добавьте:"
echo "   - GitLab URL: https://${GITLAB_HOST}"
echo "   - Project: ${PROJECT_ID}"
echo "   - Token: [ваш токен]"
echo ""
echo "4. Альтернативно, можно работать через git напрямую:"
echo "   git remote set-url origin git@${GITLAB_HOST}:${PROJECT_ID}.git"
echo ""

# Проверяем доступность GitLab
if curl -s --connect-timeout 5 "https://${GITLAB_HOST}" > /dev/null; then
    echo "✅ GitLab доступен: https://${GITLAB_HOST}"
else
    echo "❌ GitLab недоступен или требует VPN: https://${GITLAB_HOST}"
fi

echo ""
echo "📝 Текущая конфигурация git:"
git remote -v