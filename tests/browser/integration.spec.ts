import { test, expect } from "@playwright/test";

test("интеграции: меню, карточки, сохранение и просмотр без сети", async ({
    page,
    context,
}) => {
    const errors: string[] = [];
    page.on("pageerror", (error) => errors.push(error.message));
    await page.goto("/login");
    await page.getByLabel("Email", { exact: true }).fill("admin@example.test");
    await page.getByLabel("Пароль", { exact: true }).fill("Test_password_123");
    await page.getByRole("button", { name: "Войти", exact: true }).click();
    await page.getByRole("link", { name: "Интеграции", exact: true }).click();
    await expect(page).toHaveURL("/integration/webhooks");
    await expect(
        page.getByRole("heading", { name: "Вебхуки", exact: true }),
    ).toBeVisible();
    await expect(page.locator("thead th").first()).toHaveText("#");
    for (const [label, url] of [
        ["Правила", "rules"],
        ["Сервисы", "services"],
        ["Типы хуков", "type_hook"],
        ["Типы обработки", "type_processing"],
    ]) {
        await page.getByRole("button", { name: "Справочники" }).click();
        await page.getByRole("link", { name: label, exact: true }).click();
        await expect(page).toHaveURL(`/integration/${url}`);
        await expect(
            page.getByRole("heading", { name: label, exact: true }),
        ).toBeVisible();
    }
    await page.getByRole("link", { name: "Вебхуки", exact: true }).click();
    await expect(
        page.getByRole("button", { name: "Добавить запись" }),
    ).toBeEnabled();
    await page.getByRole("button", { name: "Добавить запись" }).click();
    await page
        .getByLabel("Название *", { exact: true })
        .fill("Тестовый вебхук");
    await page
        .getByLabel("Сервис", { exact: true })
        .selectOption({ label: "Тестовый сервис" });
    await page
        .getByLabel("Адрес вебхука", { exact: true })
        .fill("/test_webhook");
    await page
        .getByLabel("Параметры", { exact: true })
        .fill('{"synthetic":"test-detail"}');
    await page
        .getByRole("button", { name: /^(Создать запись|Сохранить изменения)$/ })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page.getByRole("button", { name: "Закрыть карточку" }).click();
    await page
        .getByRole("button", { name: "Тестовый вебхук", exact: true })
        .click();
    await expect(page.getByLabel("Адрес вебхука", { exact: true })).toHaveValue(
        "/test_webhook",
    );
    await expect(page.getByLabel("Параметры", { exact: true })).toHaveValue(
        /test-detail/,
    );
    await page.getByRole("button", { name: "Закрыть карточку" }).click();
    await page
        .locator("tbody tr")
        .filter({ hasText: "Тестовый вебхук" })
        .getByRole("button", { name: /Редактировать/ })
        .click();
    await expect(page.getByLabel("Название *", { exact: true })).toBeEnabled();
    await page
        .getByLabel("Название *", { exact: true })
        .fill("Изменённый вебхук");
    await page
        .getByRole("button", { name: /^(Создать запись|Сохранить изменения)$/ })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page.reload();
    await expect(page.getByLabel("Название *", { exact: true })).toHaveValue(
        "Изменённый вебхук",
    );
    await page.getByRole("button", { name: "Закрыть карточку" }).click();
    await context.setOffline(true);
    await page
        .getByRole("button", { name: "Изменённый вебхук", exact: true })
        .click();
    await expect(
        page.getByText("Полная карточка доступна при подключении к сети."),
    ).toBeVisible();
    await expect(page.getByLabel("Параметры", { exact: true })).toHaveValue("");
    await context.setOffline(false);
    await page.getByRole("button", { name: "Закрыть карточку" }).click();
    await page.getByRole("link", { name: "Данные", exact: true }).click();
    await expect(page).toHaveURL("/integration/data");
    await expect(
        page.getByRole("heading", { name: "Данные", exact: true }),
    ).toBeVisible();
    expect(errors).toEqual([]);
});
