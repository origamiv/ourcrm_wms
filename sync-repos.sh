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
    
    echo "📤 Отправка в GitHub.com (cloud)..."
    if git push cloud "$branch"; then
        echo "✅ GitHub.com обновлен"
    else
        echo "❌ Ошибка при отправке в GitHub.com"
        return 1
    fi
    
    echo "📤 Отправка в self-hosted GitLab (origin)..."
    if git push origin "$branch"; then
        echo "✅ Self-hosted GitLab обновлен"
    else
        echo "❌ Ошибка при отправке в self-hosted GitLab"
        return 1
    fi
    
    echo "🎉 Синхронизация завершена успешно!"
}

# Проверяем наличие необходимых remotes
if ! git remote get-url origin >/dev/null 2>&1; then
    echo "❌ Remote 'origin' не найден"
    exit 1
fi

if ! git remote get-url cloud >/dev/null 2>&1; then
    echo "❌ Remote 'cloud' не найден"
    exit 1
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