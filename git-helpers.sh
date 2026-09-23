#!/bin/bash

# Git helpers для работы с несколькими репозиториями

# Функция для push в основной репозиторий (self-hosted GitLab)
git-push-main() {
    echo "📤 Push в основной репозиторий (GitLab)..."
    git push origin "${1:-master}"
}

# Функция для push в облачный репозиторий (GitHub для Cursor)  
git-push-cloud() {
    echo "☁️ Push в облачный репозиторий (GitHub)..."
    git push cloud "${1:-master}"
}

# Функция для push в оба репозитория
git-push-all() {
    local branch=${1:-master}
    echo "🔄 Push в оба репозитория..."
    
    echo "☁️ GitHub..."
    git push cloud "$branch"
    
    echo "🏠 GitLab..."  
    git push origin "$branch"
    
    echo "✅ Готово!"
}

# Функция для pull из основного репозитория
git-pull-main() {
    echo "📥 Pull из основного репозитория (GitLab)..."
    git pull origin "${1:-master}"
}

# Функция для проверки статуса синхронизации
git-sync-status() {
    echo "📊 Статус синхронизации репозиториев:"
    echo ""
    
    echo "🏠 Origin (GitLab):"
    git log --oneline -3 origin/master 2>/dev/null || echo "  ❌ Недоступен"
    
    echo ""
    echo "☁️ Cloud (GitHub):"
    git log --oneline -3 cloud/master 2>/dev/null || echo "  ❌ Недоступен"
    
    echo ""
    echo "💻 Local:"
    git log --oneline -3 HEAD
}

# Функция для показа remotes
git-show-remotes() {
    echo "📋 Настроенные remotes:"
    git remote -v
}

# Экспорт функций
export -f git-push-main git-push-cloud git-push-all git-pull-main git-sync-status git-show-remotes

echo "🔧 Git helpers загружены:"
echo "  git-push-main     - push в GitLab (основной)"
echo "  git-push-cloud    - push в GitHub (для Cursor)" 
echo "  git-push-all      - push в оба репозитория"
echo "  git-pull-main     - pull из GitLab"
echo "  git-sync-status   - проверка статуса синхронизации"
echo "  git-show-remotes  - показать все remotes"