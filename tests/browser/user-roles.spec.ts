import { test, expect } from "@playwright/test";

test("назначает роли двойным кликом по ячейке пользователя", async ({
    page,
}) => {
    await page.goto("/login");
    await page.getByLabel("Email", { exact: true }).fill("admin@example.test");
    await page.getByLabel("Пароль", { exact: true }).fill("Test_password_123");
    await page.getByRole("button", { name: "Войти", exact: true }).click();
    await page
        .getByRole("link", { name: "Администрирование", exact: true })
        .click();

    const row = page.locator("tbody tr").filter({ hasText: "Иванов Михаил" });
    await row.getByRole("button", { name: "Редактировать пользователя" }).click();
    await expect(page.locator(".roles-form-field .v-select")).toBeVisible();
    await page.locator(".roles-form-field .v-field").click();
    await page
        .locator(".v-list-item")
        .filter({ hasText: "Кладовщик" })
        .click();
    await page.getByRole("button", { name: "Сохранить изменения", exact: true }).click();
    await expect(
        page.locator(".role-tag").filter({ hasText: "Кладовщик" }).first(),
    ).toBeVisible();
});
