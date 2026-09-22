import { test, expect } from "@playwright/test";

test("кнопка интеграций видна в действиях клиента", async ({ page }) => {
    await page.goto("/login");
    await page.getByLabel("Email", { exact: true }).fill("admin@example.test");
    await page.getByLabel("Пароль", { exact: true }).fill("Test_password_123");
    await page.getByRole("button", { name: "Войти", exact: true }).click();
    await expect(page).toHaveURL("/");
    const relationsLoaded = page.waitForResponse((response) => response.url().includes("/web/clients/relations") && response.status() === 200);
    await page.goto("/clients/clients");
    await relationsLoaded;

    const row = page.locator("tbody tr").filter({ has: page.getByRole("button", { name: "Тестовый клиент", exact: true }) }).first();
    const action = row.getByRole("button", { name: "Интеграции: Тестовый клиент" });
    await expect(action).toBeVisible();
    await expect(action).toBeEnabled();
    await expect(row.getByRole("button", { name: "Документы: Тестовый клиент" })).toBeEnabled();
    const disabledAccount = row.getByRole("button", { name: "Доступы: Тестовый клиент" });
    await expect(disabledAccount).toBeDisabled();
    await expect(disabledAccount).toHaveCSS("opacity", "0.45");
    await expect(disabledAccount.locator("img")).toHaveCSS("filter", "grayscale(1)");
    await expect(row.getByRole("button", { name: "Юрлица: Тестовый клиент" })).toBeEnabled();
    await expect(row.getByRole("button", { name: "Физлица: Тестовый клиент" })).toBeEnabled();
    const otherRow = page.locator("tbody tr").filter({ has: page.getByRole("button", { name: "Другой клиент", exact: true }) }).first();
    await expect(otherRow.getByRole("button", { name: "Документы: Другой клиент" })).toBeDisabled();
    await expect(otherRow.getByRole("button", { name: "Интеграции: Другой клиент" })).toBeDisabled();
    await action.click();
    await expect(page).toHaveURL(/\/clients\/integrations\?client_id=1$/);

    await page.setViewportSize({ width: 390, height: 844 });
    const mobileRelationsLoaded = page.waitForResponse((response) => response.url().includes("/web/clients/relations") && response.status() === 200);
    await page.goto("/clients/clients");
    await mobileRelationsLoaded;
    await expect(action).toBeVisible();
    await action.click();
    await expect(page).toHaveURL(/\/clients\/integrations\?client_id=1$/);
});

test("интеграции клиента: меню, создание и фильтр по клиенту", async ({ page }) => {
    await page.goto("/login");
    await page.getByLabel("Email", { exact: true }).fill("admin@example.test");
    await page.getByLabel("Пароль", { exact: true }).fill("Test_password_123");
    await page.getByRole("button", { name: "Войти", exact: true }).click();
    await expect(page).toHaveURL("/");

    await page.goto("/clients/integrations?client_id=1");
    await expect(page.getByRole("heading", { name: "Интеграции для клиента Тестовый клиент" })).toBeVisible();
    await expect(page.getByRole("link", { name: "Интеграции", exact: true }).last()).toHaveAttribute("aria-current", "page");
    await page.getByRole("button", { name: "Добавить запись" }).click();
    await expect(page.getByLabel("Клиент", { exact: true })).toBeDisabled();
    await page.getByLabel("Название *", { exact: true }).fill("Вебхук первого клиента");
    await page.getByRole("button", { name: /^(Создать запись|Сохранить изменения)$/ }).click();
    await expect(page.getByText("Запись создана. Можно добавить следующую.", { exact: true })).toBeVisible();
    await page.getByRole("button", { name: "Закрыть карточку" }).click();
    await expect(page.getByRole("button", { name: "Вебхук первого клиента", exact: true }).first()).toBeVisible();

    await page.goto("/clients/integrations?client_id=2");
    await expect(page.getByRole("button", { name: "Вебхук первого клиента", exact: true })).toHaveCount(0);
});

test("редактирование интеграции открывает отдельный конструктор", async ({ page }) => {
    await page.goto("/login");
    await page.getByLabel("Email", { exact: true }).fill("admin@example.test");
    await page.getByLabel("Пароль", { exact: true }).fill("Test_password_123");
    await page.getByRole("button", { name: "Войти", exact: true }).click();
    await expect(page).toHaveURL("/");
    await page.goto("/clients/integrations?client_id=1");
    await page.getByRole("button", { name: "Редактировать: Интеграция тестового клиента" }).click();
    await expect(page).toHaveURL(/\/clients\/integrations\/\d+\/edit\?client_id=1$/);
    await expect(page.getByRole("heading", { name: /Редактирование интеграции/ })).toBeVisible();
    await expect(page.getByText("Доступ (Start)")).toBeVisible();
    await expect(page.getByText("Для этого вебхука доступных блоков нет.")).toBeVisible();
    await page.getByRole("button", { name: "Свойства интеграции" }).click();
    await page.getByLabel("Название *").fill("Обновлённая интеграция");
    await page.getByRole("button", { name: "Сохранить", exact: true }).click();
    await expect(page.getByText("Изменения сохранены")).toBeVisible();
    await page.getByRole("button", { name: "К списку" }).click();
    await expect(page).toHaveURL(/\/clients\/integrations\?client_id=1$/);
    await expect(page.getByRole("button", { name: "Обновлённая интеграция", exact: true }).first()).toBeVisible();
});

test("конструктор WB сохраняет цепочку и расписание", async ({ page }) => {
    await page.goto("/login");
    await page.getByLabel("Email", { exact: true }).fill("admin@example.test");
    await page.getByLabel("Пароль", { exact: true }).fill("Test_password_123");
    await page.getByRole("button", { name: "Войти", exact: true }).click();
    await expect(page).toHaveURL("/");
    await page.goto("/clients/integrations?client_id=1");
    await page.getByRole("button", { name: "Редактировать: WB интеграция" }).click();
    await expect(page.locator(".rule-node").getByText("Синхронизация каталога")).toBeVisible();
    await page.getByLabel("Расписание").selectOption("2");
    await page.getByRole("button", { name: /Отправка остатков/ }).click();
    await expect(page.locator(".rule-node")).toHaveCount(3);
    await page.getByRole("button", { name: "Сохранить", exact: true }).click();
    await expect(page.getByText("Изменения сохранены")).toBeVisible();
    await page.reload();
    await expect(page.locator(".rule-node")).toHaveCount(3);
    await expect(page.getByText("Отправка остатков").last()).toBeVisible();
    await expect(page.getByLabel("Расписание")).toHaveValue("2");
});

test("конструктор доступен для Ozon и Яндекс Маркета", async ({ page }) => {
    await page.goto("/login");
    await page.getByLabel("Email", { exact: true }).fill("admin@example.test");
    await page.getByLabel("Пароль", { exact: true }).fill("Test_password_123");
    await page.getByRole("button", { name: "Войти", exact: true }).click();
    await expect(page).toHaveURL("/");
    for (const name of ["Ozon", "Яндекс Маркет"]) {
        await page.goto("/clients/integrations?client_id=1");
        await page.getByRole("button", { name: `Редактировать: ${name} интеграция` }).click();
        await expect(page.locator(".palette-rule")).toHaveCount(4);
        await expect(page.getByLabel("Расписание")).toHaveValue("4");
    }
});
