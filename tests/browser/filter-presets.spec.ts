import { expect, test, type Page } from "@playwright/test";

async function login(page: Page) {
    await page.goto("/login");
    await page.getByLabel("Email", { exact: true }).fill("admin@example.test");
    await page.getByLabel("Пароль", { exact: true }).fill("Test_password_123");
    await page.getByRole("button", { name: "Войти", exact: true }).click();
    await expect(page).toHaveURL("/");
}

test("конструктор открывается, а активная плитка сохраняется", async ({
    page,
}) => {
    await login(page);
    await page.evaluate(async () => {
        const csrf = document.querySelector<HTMLMetaElement>(
            'meta[name="csrf-token"]',
        )?.content;
        const response = await fetch("/web/filter_presets", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrf ?? "",
            },
            body: JSON.stringify({
                screen_key: "users",
                name: "Все Иваны",
                is_active: true,
                rules: {
                    glue: "and",
                    rules: [
                        {
                            field: "last_name",
                            type: "text",
                            filter: "contains",
                            value: "Иван",
                            includes: [],
                        },
                    ],
                },
            }),
        });
        if (!response.ok) throw new Error(await response.text());
    });

    await page.goto("/main/users");
    await expect(
        page.getByRole("button", { name: "Все Иваны", exact: true }),
    ).toBeVisible();
    const tableBody = page.locator(".table-scroll tbody");
    await expect(tableBody.getByText(/Иванов.*Михаил/)).toBeVisible();
    await expect(tableBody.getByText(/Смирнова/)).toHaveCount(0);
    await page
        .getByRole("button", { name: "Открыть конструктор фильтров" })
        .click();
    await expect(
        page.getByRole("heading", { name: "Конструктор фильтров" }),
    ).toBeVisible();
    await page.getByText("Добавить условие", { exact: true }).click();
    const fieldSelector = page
        .locator(".wx-filter-editor .wx-richselect")
        .first();
    await expect(fieldSelector).toBeVisible();
    await fieldSelector.click();
    await expect(page.getByText("Email", { exact: true })).toBeVisible();
    await page.getByText("Email", { exact: true }).click();
    await expect(fieldSelector).toContainText("Email");
    const emailOption = page
        .locator(".wx-filter-editor .wx-item")
        .filter({ hasText: "admin@example.test" });
    await expect(emailOption).toBeVisible();
    await emailOption.click();
    await expect(emailOption.locator('input[type="checkbox"]')).toBeChecked();
    await page.getByRole("button", { name: "Закрыть" }).click();

    await page.setViewportSize({ width: 390, height: 844 });
    await page
        .getByRole("button", { name: "Открыть конструктор фильтров" })
        .click();
    const modal = page.locator(".filter-builder-modal");
    await expect(modal).toBeVisible();
    const box = await modal.boundingBox();
    expect(box?.width).toBe(390);
    expect(box?.height).toBe(844);
});

test("сохранённый фильтр клиентов открывается для редактирования", async ({
    page,
}) => {
    await login(page);
    await page.evaluate(async () => {
        const csrf = document.querySelector<HTMLMetaElement>(
            'meta[name="csrf-token"]',
        )?.content;
        const response = await fetch("/web/filter_presets", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrf ?? "",
            },
            body: JSON.stringify({
                screen_key: "clients",
                name: "Активные А",
                is_active: false,
                rules: {
                    glue: "and",
                    rules: [
                        {
                            field: "status",
                            type: "tuple",
                            filter: "equal",
                            value: 1,
                            includes: [],
                        },
                        {
                            field: "id",
                            type: "number",
                            filter: "contains",
                            value: null,
                            includes: [],
                        },
                    ],
                },
            }),
        });
        if (!response.ok) throw new Error(await response.text());
    });

    await page.goto("/clients/clients");
    await page
        .getByRole("button", { name: "Изменить фильтр Активные А" })
        .click();
    await expect(
        page.getByRole("heading", { name: "Изменение фильтра" }),
    ).toBeVisible();
    await expect(page.getByLabel("Название сохранённого варианта")).toHaveValue(
        "Активные А",
    );
    await page
        .getByLabel("Название сохранённого варианта")
        .fill("Активные клиенты А");
    await page.getByRole("button", { name: "Сохранить", exact: true }).click();
    await expect(
        page.getByRole("button", {
            name: "Активные клиенты А",
            exact: true,
        }),
    ).toBeVisible();
});
