# Граф зависимостей импорта TSWMS

## Анализ зависимостей между сущностями

### Уровень 1: Базовые справочники (без зависимостей)
Эти сущности не зависят от других и должны импортироваться первыми:

1. **clients** (Partners) - `importPartners()`
   - Источник: `tswms-partners`
   - Создает: `clients.clients` + `clients.companies`
   - Зависимости: нет

2. **services** - `importServices()` 
   - Источник: системные справочники
   - Создает: `clients.services`
   - Зависимости: нет

3. **warehouses** - `importWarehouses()`
   - Источник: `warehouse_types`, `warehouses`, `tswms-places`
   - Создает: `wms.kind_warehouses`, `wms.warehouses`, `wms.warehouse_places`
   - Зависимости: нет

4. **task_stages** - `importTaskStages()`
   - Источник: `tswms-tasks-statuses`
   - Создает: `wms.task_stages`
   - Зависимости: нет

5. **users** - `importImportedUsers()`
   - Источник: `users` или `tswms-users`
   - Создает: `wms.users`
   - Зависимости: нет

6. **goods** - `importGoods()`
   - Источник: `tswms-goods`
   - Создает: `wms.goods`
   - Зависимости: нет (но может ссылаться на уже существующие товары)

### Уровень 2: Интеграционные сущности (зависят от clients)
Зависят от клиентов/партнеров:

7. **accounts** - `importAccounts()`
   - Источник: `legasy_integrations_registry`, `tswms-integrations`
   - Создает: `clients.accounts`
   - **Зависимости: clients** (`tswms-partners`)

8. **webhooks** - `importWebhooks()`
   - Источник: `legasy_integrations_registry`, `tswms-integrations` 
   - Создает: `clients.integration_webhooks`
   - **Зависимости: clients, accounts** (`tswms-partners`, accounts)

9. **documents** - `importDocuments()`
   - Источник: документооборот TSWMS
   - Создает: документы WMS
   - **Зависимости: clients** (возможно)

### Уровень 3: Справочники заказов (зависят от интеграций)
Создаются на основе данных из интеграций:

10. **order_statuses** - создаются через `importOrders()`
11. **order_sources** - создаются через `importOrders()`  
12. **order_cancel_statuses** - создаются через `importOrders()`
13. **logistic_companies** - создаются через `importOrders()`
14. **shipment_statuses** - создаются через `importOrders()`

### Уровень 4: Операционные данные (зависят от всех справочников)

15. **orders** - `importOrders()`
    - **Зависимости: clients, warehouses, webhooks, services**
    - Использует: `$clients`, `$warehouses`, `$integrations`, статусы заказов

16. **tasks** - `importTasks()`
    - **Зависимости: clients, warehouses, task_stages, users**
    - Использует: партнеров, склады, этапы задач, пользователей

### Уровень 5: Связанные данные (зависят от операционных данных)

17. **order_goods** - `importOrderGoods()`
    - **Зависимости: orders, goods**

18. **order_histories** - `importOrderHistories()`
    - **Зависимости: orders**

19. **shipments** - `importShipments()`
    - **Зависимости: orders**

20. **task_goods** - `importTaskGoods()`
    - **Зависимости: tasks, goods**

21. **acceptances** - `importAcceptances()`
    - **Зависимости: tasks, clients, warehouses**

22. **cell_goods** - `importCellGoods()`
    - **Зависимости: tasks, goods, warehouses**

## Оптимальный порядок импорта

### Группа "references" (справочники)
Порядок импорта внутри группы:
1. **clients** (партнеры) - основа для всех интеграций
2. **services** - справочник сервисов  
3. **warehouses** - склады
4. **task_stages** - этапы задач
5. **users** - пользователи
6. **accounts** - аккаунты интеграций (зависят от clients)
7. **webhooks** - вебхуки интеграций (зависят от clients + accounts)
8. **documents** - документы (зависят от clients)

### Группа "goods" (товары)
9. **goods** - товары (могут импортироваться параллельно со справочниками)

### Группа "orders" (заказы)  
Порядок импорта внутри группы:
10. **orders** - заказы (создают справочники статусов)
11. **order_goods** - позиции заказов
12. **order_histories** - история заказов  
13. **shipments** - отправления
14. **order_statuses** - статусы заказов (создаются автоматически)
15. **order_sources** - источники заказов (создаются автоматически)
16. **order_cancel_statuses** - статусы отмены (создаются автоматически)
17. **logistic_companies** - логистические компании (создаются автоматически)
18. **shipment_statuses** - статусы отправлений (создаются автоматически)

### Группа "tasks" (задачи)
Порядок импорта внутри группы:
19. **tasks** - задачи
20. **task_goods** - товары в задачах
21. **acceptances** - приемки 
22. **cell_goods** - размещения в ячейках

## Рекомендации по реализации

1. **Изменить порядок в группах** в константе `ENTITY_GROUPS`
2. **Добавить валидацию зависимостей** перед импортом каждой сущности
3. **Логировать пропущенные записи** из-за отсутствующих зависимостей
4. **Создать команду для импорта в правильном порядке** всех зависимостей
5. **Добавить опцию --force-dependencies** для принудительного импорта зависимостей

## Проблема с текущим импортом accounts

В импорте **8169** было обработано только **11 из 27** записей accounts, потому что:
- У 16 записей `partner_id` не найден в таблице `clients.clients`  
- Эти партнеры не были импортированы, так как импорт accounts запускался отдельно
- **Решение**: всегда импортировать clients перед accounts