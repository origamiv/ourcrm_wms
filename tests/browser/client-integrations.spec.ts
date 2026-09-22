import { test, expect } from "@playwright/test";

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
