#!/bin/bash

echo "🔄 Синхронизация с GitLab репозиториями..."

# Функция для проверки статуса git
check_git_status() {
    if [ -n "$(git status --porcelain)" ]; then
        echo "❌ Есть незакоммиченные изменения. Сделайте коммит перед синхронизацией."
        git status --short
        exit 1
    fi
}

# Функция для push в оба репозитория
sync_both() {
    local branch=${1:-master}
    
    echo "📤 Отправка в self-hosted GitLab (gitlab.our24.ru)..."
    if git push gitlab-self "$branch"; then
        echo "✅ Self-hosted GitLab обновлен"
    else
        echo "❌ Ошибка при отправке в self-hosted GitLab"
        return 1
    fi
    
    echo "📤 Отправка в GitLab.com..."
    if git push cloud "$branch"; then
        echo "✅ GitLab.com обновлен"
    else
        echo "❌ Ошибка при отправке в GitLab.com"
        return 1
    fi
    
    echo "🎉 Синхронизация завершена успешно!"
}

# Добавляем remote для self-hosted если его нет
if ! git remote get-url gitlab-self >/dev/null 2>&1; then
    echo "➕ Добавляем remote для self-hosted GitLab..."
    git remote add gitlab-self git@gitlab.our24.ru:ourcrm3/modules/wms.git
fi

# Проверяем статус
check_git_status

# Получаем текущую ветку
current_branch=$(git branch --show-current)

echo "🌿 Текущая ветка: $current_branch"

# Синхронизируем
sync_both "$current_branch"

echo ""
echo "📋 Текущие remotes:"
git remote -v