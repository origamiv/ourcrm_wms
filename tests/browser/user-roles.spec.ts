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
    const rolesCell = row.locator("td").nth(4);
    await rolesCell.dblclick();
    await expect(
        page.getByLabel("Роли пользователя", { exact: true }),
    ).toBeVisible();
    await page
        .getByLabel("Роли пользователя", { exact: true })
        .selectOption({ label: "Кладовщик" });
    await page
        .locator(".roles-tagbox-actions")
        .getByRole("button", { name: "Сохранить", exact: true })
        .click();
    await expect(
        page.locator(".role-tag").filter({ hasText: "Кладовщик" }).first(),
    ).toBeVisible();
});
