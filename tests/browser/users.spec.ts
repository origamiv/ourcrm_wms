import { execFileSync } from "node:child_process";
import { test, expect, type Page } from "@playwright/test";
async function login(page: Page) {
    await page.goto("/login");
    await page.getByLabel("Email", { exact: true }).fill("admin@example.test");
    await page.getByLabel("Пароль", { exact: true }).fill("Test_password_123");
    await page.getByRole("button", { name: "Войти", exact: true }).click();
    await expect(page).toHaveURL("/");
    await page
        .getByRole("link", { name: "Администрирование", exact: true })
        .click();
    await expect(
        page.getByText("Данные синхронизированы", { exact: true }),
    ).toBeVisible();
}
test("cache, mutations, other tabs, offline reading and logout", async ({
    page,
    context,
}) => {
    const errors: string[] = [];
    page.on("pageerror", (e) => errors.push(e.message));
    await login(page);
    await expect(
        page.getByRole("button", { name: "Без почты Сотрудник", exact: true }),
    ).toBeVisible();
    await expect(
        page.getByRole("button", { name: /Иванов Михаил/ }),
    ).toBeVisible();
    await page.getByRole("button", { name: "Добавить пользователя" }).click();
    await page.getByLabel("Имя", { exact: false }).fill("Новый");
    await page
        .getByLabel("Email *", { exact: true })
        .fill("created@example.test");
    await page
        .getByLabel("Пароль", { exact: true })
        .fill("Created_password_123");
    await page.getByLabel("Подтверждение пароля").fill("Created_password_123");
    await page
        .getByRole("button", { name: "Создать пользователя", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page
        .getByRole("button", { name: "Активировать", exact: true })
        .click();
    await expect(page.locator(".profile-summary .badge")).toHaveText("Активен");
    await page.getByRole("button", { name: "Закрыть карточку" }).click();
    await page.getByRole("link", { name: "Главная", exact: true }).click();
    const deltaRequest = page.waitForRequest((r) =>
        r.url().includes("/web/sync/users?cursor="),
    );
    await page
        .getByRole("link", { name: "Администрирование", exact: true })
        .click();
    await deltaRequest;
    await expect(page.getByRole("button", { name: /Новый/ })).toBeVisible();
    const other = await context.newPage();
    await other.goto("/main/users");
    await expect(
        other.getByText("Данные синхронизированы", { exact: true }),
    ).toBeVisible();
    await page.getByRole("button", { name: /Иванов Михаил/ }).click();
    await page.getByLabel("Имя", { exact: false }).fill("Михаил изменён");
    await page.getByRole("button", { name: "Сохранить изменения" }).click();
    await expect(
        other.getByRole("button", { name: /Иванов Михаил изменён/ }),
    ).toBeVisible();
    await page.getByRole("button", { name: "Закрыть карточку" }).click();
    await context.setOffline(true);
    await expect(
        page.getByText("Нет связи · сохранённые данные"),
    ).toBeVisible();
    await page.getByRole("link", { name: "Главная", exact: true }).click();
    await page
        .getByRole("link", { name: "Администрирование", exact: true })
        .click();
    await expect(
        page.getByRole("button", { name: /Иванов Михаил изменён/ }),
    ).toBeVisible();
    await expect(
        page.getByRole("button", { name: "Добавить пользователя" }),
    ).toBeDisabled();
    await context.setOffline(false);
    await expect(
        page.getByText("Данные синхронизированы", { exact: true }),
    ).toBeVisible();
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.screenshot({
        path: "/tmp/wms-users-desktop.png",
        fullPage: true,
    });
    await page.getByRole("button", { name: "Выйти", exact: true }).click();
    await expect(page).toHaveURL("/login");
    await expect(other).toHaveURL("/login");
    const count = await page.evaluate(async () => {
        return await new Promise<number>((resolve, reject) => {
            const r = indexedDB.open("wms_cache");
            r.onsuccess = () => {
                const q = r.result
                    .transaction("entries")
                    .objectStore("entries")
                    .count();
                q.onsuccess = () => resolve(q.result);
                q.onerror = reject;
            };
            r.onerror = reject;
        });
    });
    expect(count).toBe(0);
    expect(errors).toEqual([]);
});
test("mobile editor stays inside viewport", async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await login(page);
    await page.getByRole("button", { name: /Петрова Елена/ }).click();
    await expect(
        page.getByRole("complementary", { name: "Карточка пользователя" }),
    ).toBeVisible();
    expect(
        await page.evaluate(
            () => document.documentElement.scrollWidth <= window.innerWidth,
        ),
    ).toBe(true);
    await page.screenshot({
        path: "/tmp/wms-users-mobile.png",
        fullPage: true,
    });
});

test("administration catalogs support search and cached navigation", async ({
    page,
    context,
}) => {
    await login(page);
    await page.getByRole("link", { name: "Роли", exact: true }).click();
    await expect(
        page.getByRole("heading", { name: "Роли", exact: true }),
    ).toBeVisible();
    await expect(page.getByText("Кладовщик", { exact: true })).toBeVisible();
    await page
        .getByLabel("Поиск: Роли", { exact: true })
        .fill("нет_такой_роли");
    await expect(
        page.getByText("По выбранным условиям ничего не найдено"),
    ).toBeVisible();
    await page.getByLabel("Поиск: Роли", { exact: true }).fill("");
    await page
        .getByRole("button", { name: "Справочники", exact: false })
        .click();
    await page
        .getByRole("link", { name: "Права доступа", exact: true })
        .click();
    await expect(
        page.getByText("Просмотр остатков", { exact: true }),
    ).toBeVisible();
    await page.screenshot({ path: "/tmp/wms-permissions.png", fullPage: true });
    await page.setViewportSize({ width: 390, height: 844 });
    expect(
        await page.evaluate(
            () => document.documentElement.scrollWidth <= window.innerWidth,
        ),
    ).toBe(true);
    await context.setOffline(true);
    await page.getByRole("link", { name: "Роли", exact: true }).click();
    await expect(page.getByText("Кладовщик", { exact: true })).toBeVisible();
    await page
        .getByRole("button", { name: "Справочники", exact: false })
        .click();
    await page
        .getByRole("link", { name: "Права доступа", exact: true })
        .click();
    await expect(
        page.getByText("Просмотр остатков", { exact: true }),
    ).toBeVisible();
    await context.setOffline(false);
    const delta = page.waitForRequest((request) =>
        request.url().includes("/web/sync/roles?cursor="),
    );
    await page.getByRole("link", { name: "Роли", exact: true }).click();
    await delta;
});

test("creates and edits roles and permissions and updates another tab", async ({
    page,
    context,
}) => {
    await login(page);
    for (const [title, button, code] of [
        ["Роли", "Добавить роль", "browser_role"],
        ["Права доступа", "Добавить право", "browser_permission"],
    ]) {
        if (title === "Права доступа")
            await page
                .getByRole("button", { name: "Справочники", exact: false })
                .click();
        await page.getByRole("link", { name: title, exact: true }).click();
        await page.getByRole("button", { name: button, exact: true }).click();
        await page
            .getByLabel("Название *", { exact: true })
            .fill(`Создано ${code}`);
        await page.getByLabel("Код *", { exact: true }).fill(code);
        if (title === "Права доступа")
            await page
                .getByLabel("Ресурс *", { exact: true })
                .fill("inventory");
        await page
            .getByRole("button", { name: "Создать запись", exact: true })
            .click();
        await expect(
            page.getByText("Изменения сохранены", { exact: true }),
        ).toBeVisible();
        const other = await context.newPage();
        await other.goto(
            title === "Роли" ? "/main/roles" : "/main/permissions",
        );
        await expect(
            other.getByRole("button", { name: `Создано ${code}`, exact: true }),
        ).toBeVisible();
        await page
            .getByLabel("Название *", { exact: true })
            .fill(`Изменено ${code}`);
        await page
            .getByRole("button", { name: "Сохранить изменения", exact: true })
            .click();
        await expect(
            other.getByRole("button", {
                name: `Изменено ${code}`,
                exact: true,
            }),
        ).toBeVisible();
        await other.close();
        await page
            .getByRole("button", { name: "Закрыть карточку", exact: true })
            .click();
    }
});

test("role rights matrix saves assignments and synchronizes tabs and offline view", async ({
    page,
    context,
}) => {
    await login(page);
    await page
        .getByRole("button", { name: "Справочники", exact: false })
        .click();
    await page.getByRole("link", { name: "Роли и права", exact: true }).click();
    await expect(
        page.getByRole("heading", { name: "Роли и права", exact: true }),
    ).toBeVisible();
    const name = "Кладовщик: Просмотр остатков";
    const checkbox = page.getByRole("checkbox", { name, exact: true });
    await expect(checkbox).toBeEnabled();
    await expect(checkbox).not.toBeChecked();
    await checkbox.click();
    await expect(checkbox).toBeChecked();
    const other = await context.newPage();
    await other.goto("/main/roles_rights");
    await expect(
        other.getByRole("checkbox", { name, exact: true }),
    ).toBeChecked();
    await checkbox.click();
    await expect(
        other.getByRole("checkbox", { name, exact: true }),
    ).not.toBeChecked();
    await page.screenshot({
        path: "/tmp/wms-roles-rights.png",
        fullPage: true,
    });
    await context.setOffline(true);
    await expect(checkbox).toBeDisabled();
    await page.getByRole("link", { name: "Роли", exact: true }).click();
    await page
        .getByRole("button", { name: "Справочники", exact: false })
        .click();
    await page.getByRole("link", { name: "Роли и права", exact: true }).click();
    await expect(
        page.getByRole("checkbox", { name, exact: true }),
    ).not.toBeChecked();
    await page.setViewportSize({ width: 390, height: 844 });
    expect(
        await page.evaluate(
            () => document.documentElement.scrollWidth <= window.innerWidth,
        ),
    ).toBe(true);
    await other.close();
});

test("user and role status dropdowns persist selected values", async ({
    page,
}) => {
    await login(page);
    await page.getByRole("button", { name: /Иванов Михаил/ }).click();
    await page
        .getByLabel("Статус пользователя", { exact: true })
        .selectOption("2");
    await page
        .getByRole("button", { name: "Сохранить изменения", exact: true })
        .click();
    await expect(page.locator(".profile-summary .badge")).toHaveText(
        "Отключен",
    );
    await page
        .getByLabel("Статус пользователя", { exact: true })
        .selectOption("0");
    await page
        .getByRole("button", { name: "Сохранить изменения", exact: true })
        .click();
    await expect(page.locator(".profile-summary .badge")).toHaveText("Новый");
    await page
        .getByRole("button", { name: "Закрыть карточку", exact: true })
        .click();
    await page.getByRole("link", { name: "Роли", exact: true }).click();
    await page
        .getByRole("button", { name: "Редактировать: Кладовщик", exact: true })
        .click();
    await page.getByLabel("Статус", { exact: true }).selectOption("2");
    await page
        .getByRole("button", { name: "Сохранить изменения", exact: true })
        .click();
    await expect(
        page
            .getByRole("row")
            .filter({ hasText: "warehouse_operator" })
            .getByText("Отключен", { exact: true }),
    ).toBeVisible();
    await page.reload();
    await page
        .getByRole("button", { name: "Редактировать: Кладовщик", exact: true })
        .click();
    await expect(page.getByLabel("Статус", { exact: true })).toHaveValue("2");
});

test("password action opens a separate panel and saves the new password", async ({
    page,
}) => {
    await login(page);
    const row = page.getByRole("row").filter({ hasText: "Иванов Михаил" });
    await row
        .getByRole("button", {
            name: "Редактировать пользователя",
            exact: true,
        })
        .click();
    await expect(
        page
            .getByRole("complementary", {
                name: "Карточка пользователя",
                exact: true,
            })
            .getByRole("button", { name: "Установить пароль", exact: true }),
    ).toHaveCount(0);
    await row
        .getByRole("button", { name: "Установить пароль", exact: true })
        .click();
    const panel = page.getByRole("complementary", {
        name: "Установка пароля",
        exact: true,
    });
    await expect(panel).toBeVisible();
    await expect(
        panel.getByLabel("Статус пользователя", { exact: true }),
    ).toHaveCount(0);
    await panel
        .getByLabel("Пароль", { exact: true })
        .fill("Changed_password_456");
    await panel
        .getByLabel("Подтверждение пароля", { exact: true })
        .fill("Changed_password_456");
    const saved = page.waitForResponse(
        (response) =>
            response.url().endsWith("/password") &&
            response.request().method() === "POST",
    );
    await panel
        .getByRole("button", { name: "Установить пароль", exact: true })
        .click();
    expect((await saved).status()).toBe(200);
    await expect(panel.getByRole("status")).toHaveText("Изменения сохранены");
    await expect(panel.getByLabel("Пароль", { exact: true })).toHaveValue("");
    await expect(
        panel.getByLabel("Подтверждение пароля", { exact: true }),
    ).toHaveValue("");
});

test("delete action requires confirmation and removes the selected user", async ({
    page,
}) => {
    await login(page);
    const row = page.getByRole("row").filter({ hasText: "Иванов Михаил" });
    await row
        .getByRole("button", {
            name: "Редактировать пользователя",
            exact: true,
        })
        .click();
    const panel = page.getByRole("complementary", {
        name: "Карточка пользователя",
        exact: true,
    });
    await expect(
        panel.getByRole("button", {
            name: "Удалить пользователя",
            exact: true,
        }),
    ).toHaveCount(0);
    await expect(
        panel.getByRole("button", { name: "Отключить", exact: true }),
    ).toHaveCount(0);
    await row
        .getByRole("button", { name: "Удалить пользователя", exact: true })
        .click();
    const confirmation = page.getByRole("dialog", {
        name: /Удалить пользователя Иванов Михаил/,
    });
    await expect(confirmation).toBeVisible();
    await page.screenshot({ path: "/tmp/wms-delete-confirmation.png" });
    await confirmation
        .getByRole("button", { name: "Отмена", exact: true })
        .click();
    await expect(confirmation).toHaveCount(0);
    await expect(row).toBeVisible();
    await row
        .getByRole("button", { name: "Удалить пользователя", exact: true })
        .click();
    await page.keyboard.press("Escape");
    await expect(confirmation).toHaveCount(0);
    await row
        .getByRole("button", { name: "Удалить пользователя", exact: true })
        .click();
    await confirmation
        .getByRole("button", { name: "Закрыть подтверждение" })
        .click();
    await expect(confirmation).toHaveCount(0);
    await panel
        .getByRole("button", { name: "Закрыть карточку", exact: true })
        .click();
    await page.setViewportSize({ width: 390, height: 844 });
    await row
        .getByRole("button", { name: "Удалить пользователя", exact: true })
        .click();
    const bounds = await confirmation.boundingBox();
    expect(bounds!.x).toBeGreaterThanOrEqual(0);
    expect(bounds!.x + bounds!.width).toBeLessThanOrEqual(390);
    await confirmation
        .getByRole("button", { name: "Удалить", exact: true })
        .click();
    await expect(row).toHaveCount(0);
    await page.reload();
    await expect(
        page.getByText("Данные синхронизированы", { exact: true }),
    ).toBeVisible();
    await expect(row).toHaveCount(0);
});

test("roles have read-only viewing and confirmed deletion", async ({
    page,
}) => {
    await login(page);
    await page.getByRole("link", { name: "Роли", exact: true }).click();
    await page
        .getByRole("button", { name: "Просмотр: Кладовщик", exact: true })
        .click();
    await expect(page.getByLabel("Название *", { exact: true })).toBeDisabled();
    await expect(
        page.getByRole("button", { name: "Сохранить изменения", exact: true }),
    ).toHaveCount(0);
    await page
        .getByRole("button", { name: "Закрыть карточку", exact: true })
        .click();
    await page
        .getByRole("button", { name: "Удалить: Кладовщик", exact: true })
        .click();
    const dialog = page.getByRole("dialog", {
        name: "Удалить роль Кладовщик?",
        exact: true,
    });
    await dialog.getByRole("button", { name: "Отмена", exact: true }).click();
    await expect(
        page.getByRole("button", { name: "Просмотр: Кладовщик", exact: true }),
    ).toBeVisible();
    await page
        .getByRole("button", { name: "Удалить: Кладовщик", exact: true })
        .click();
    await dialog.getByRole("button", { name: "Удалить", exact: true }).click();
    await expect(
        page.getByRole("button", { name: "Просмотр: Кладовщик", exact: true }),
    ).toHaveCount(0);
    await page.reload();
    await expect(
        page.getByText("Данные синхронизированы", { exact: true }),
    ).toBeVisible();
    await expect(
        page.getByRole("button", { name: "Просмотр: Кладовщик", exact: true }),
    ).toHaveCount(0);
});

test("companies and contacts support CRUD, JSON fields, cached navigation and other tabs", async ({
    page,
    context,
}) => {
    await login(page);
    await page.getByRole("link", { name: "Компании", exact: true }).click();
    await expect(
        page.getByRole("heading", { name: "Компании", exact: true }),
    ).toBeVisible();
    await expect(
        page.getByRole("button", { name: "ООО Тестовый склад", exact: true }),
    ).toBeVisible();
    await page
        .getByRole("button", { name: "+ Добавить компанию", exact: true })
        .click();
    await page
        .getByLabel("Название компании *", { exact: true })
        .fill("ООО Новая компания");
    await page.getByLabel("Краткое название *", { exact: true }).fill("Новая");
    await page.getByLabel("ИНН", { exact: true }).fill("9876543210");
    await page.getByLabel("Telegram", { exact: true }).fill("@warehouse");
    await page.screenshot({
        path: "/tmp/wms-company-editor.png",
        fullPage: true,
    });
    await page.getByRole("checkbox", { name: "Клиент", exact: true }).check();
    await page
        .getByRole("button", { name: "Создать запись", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page
        .getByRole("button", { name: "Закрыть карточку", exact: true })
        .click();
    const other = await context.newPage();
    await other.goto("/main/companies");
    await expect(
        other.getByRole("button", { name: "ООО Новая компания", exact: true }),
    ).toBeVisible();
    await page
        .getByRole("button", {
            name: "Редактировать: ООО Новая компания",
            exact: true,
        })
        .click();
    await expect(page.getByLabel("Telegram", { exact: true })).toHaveValue(
        "@warehouse",
    );
    await page
        .getByLabel("Название компании *", { exact: true })
        .fill("ООО Обновлённая компания");
    await page
        .getByRole("button", { name: "Сохранить изменения", exact: true })
        .click();
    await expect(
        other.getByRole("button", {
            name: "ООО Обновлённая компания",
            exact: true,
        }),
    ).toBeVisible();
    await page
        .getByRole("button", { name: "Закрыть карточку", exact: true })
        .click();
    await page.screenshot({ path: "/tmp/wms-companies.png", fullPage: true });
    await page
        .getByRole("link", { name: "Контактные лица", exact: true })
        .click();
    await page
        .getByRole("button", {
            name: "+ Добавить контактное лицо",
            exact: true,
        })
        .click();
    await page.getByLabel("ФИО *", { exact: true }).fill("Пётр Контактный");
    await page.getByLabel("Краткое имя *", { exact: true }).fill("Пётр");
    await page
        .getByLabel("Контактное значение", { exact: true })
        .fill("petr@example.test");
    await page
        .getByLabel("Компания", { exact: true })
        .selectOption({ label: "ООО Обновлённая компания" });
    await page
        .getByRole("button", { name: "Создать запись", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page
        .getByRole("button", { name: "Закрыть карточку", exact: true })
        .click();
    await page.screenshot({
        path: "/tmp/wms-company-contacts.png",
        fullPage: true,
    });
    await context.setOffline(true);
    await page.getByRole("link", { name: "Компании", exact: true }).click();
    await expect(
        page.getByRole("button", {
            name: "ООО Обновлённая компания",
            exact: true,
        }),
    ).toBeVisible();
    await page
        .getByRole("link", { name: "Контактные лица", exact: true })
        .click();
    await expect(
        page.getByRole("button", { name: "Пётр Контактный", exact: true }),
    ).toBeVisible();
    await expect(
        page.getByRole("button", {
            name: "+ Добавить контактное лицо",
            exact: true,
        }),
    ).toBeDisabled();
    await context.setOffline(false);
    await page
        .getByRole("button", { name: "Удалить: Пётр Контактный", exact: true })
        .click();
    await page
        .getByRole("dialog")
        .getByRole("button", { name: "Удалить", exact: true })
        .click();
    await expect(
        page.getByRole("button", { name: "Пётр Контактный", exact: true }),
    ).toHaveCount(0);
    await page.getByRole("link", { name: "Компании", exact: true }).click();
    await page
        .getByRole("button", {
            name: "Удалить: ООО Обновлённая компания",
            exact: true,
        })
        .click();
    await page
        .getByRole("dialog")
        .getByRole("button", { name: "Удалить", exact: true })
        .click();
    await expect(
        other.getByRole("button", {
            name: "ООО Обновлённая компания",
            exact: true,
        }),
    ).toHaveCount(0);
    await page.setViewportSize({ width: 390, height: 844 });
    expect(
        await page.evaluate(
            () => document.documentElement.scrollWidth <= window.innerWidth,
        ),
    ).toBe(true);
    await other.close();
});

test("DaData fills company and bank details on create and edit while preserving manual fields", async ({
    page,
}) => {
    const party = {
        value: "ООО Подсказка",
        detail: "ИНН 1234567890 · Москва",
        fields: {
            name: "ООО Подсказка",
            shortname: "Подсказка",
            fullname: "Общество Подсказка",
            inn: "1234567890",
            kpp: "123456789",
            ogrn: "1234567890123",
            director_fio: "Иванов Иван",
            director_position: "Директор",
            src: { opf: "ООО", legal_address: "Москва, Тестовая, 1" },
        },
    };
    const bank = {
        value: "Тестовый банк",
        detail: "БИК 044525225",
        fields: {
            bank: "Тестовый банк",
            bik: "044525225",
            korr_schet: "30101810400000000225",
        },
    };
    await page.route("**/web/companies/suggestions/*", async (route) => {
        await route.fulfill({
            json: {
                suggestions: [
                    route.request().url().endsWith("/bank") ? bank : party,
                ],
            },
        });
    });
    await login(page);
    await page.getByRole("link", { name: "Компании", exact: true }).click();
    await page
        .getByRole("button", { name: "+ Добавить компанию", exact: true })
        .click();
    await page.getByLabel("Telegram", { exact: true }).fill("@manual");
    await page.getByRole("checkbox", { name: "Наша", exact: true }).check();
    const name = page.getByRole("combobox", {
        name: "Название компании *",
        exact: true,
    });
    await name.fill("Подсказка");
    await expect(
        page.getByRole("option", { name: /ООО Подсказка/ }),
    ).toBeVisible();
    await page.screenshot({
        path: "/tmp/wms-dadata-suggestions.png",
        fullPage: true,
    });
    await name.press("ArrowDown");
    await name.press("Enter");
    await expect(page.getByLabel("ИНН", { exact: true })).toHaveValue(
        "1234567890",
    );
    await expect(page.getByLabel("КПП", { exact: true })).toHaveValue(
        "123456789",
    );
    await expect(
        page.getByLabel("Юридический адрес", { exact: true }),
    ).toHaveValue("Москва, Тестовая, 1");
    await expect(page.getByLabel("Telegram", { exact: true })).toHaveValue(
        "@manual",
    );
    await expect(
        page.getByRole("checkbox", { name: "Наша", exact: true }),
    ).toBeChecked();
    await page
        .getByLabel("Расчётный счёт", { exact: true })
        .fill("40702810000000000001");
    await page.getByLabel("БИК", { exact: true }).fill("0445");
    await page.getByRole("option", { name: /Тестовый банк/ }).click();
    await expect(page.getByLabel("Банк", { exact: true })).toHaveValue(
        "Тестовый банк",
    );
    await expect(
        page.getByLabel("Корреспондентский счёт", { exact: true }),
    ).toHaveValue("30101810400000000225");
    await expect(
        page.getByLabel("Расчётный счёт", { exact: true }),
    ).toHaveValue("40702810000000000001");
    await page
        .getByRole("button", { name: "Создать запись", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page
        .getByRole("button", { name: "Закрыть карточку", exact: true })
        .click();
    await page
        .getByRole("button", {
            name: "Редактировать: ООО Подсказка",
            exact: true,
        })
        .click();
    await page.getByLabel("ИНН", { exact: true }).fill("123456");
    await page.getByRole("option", { name: /ООО Подсказка/ }).click();
    await expect(page.getByLabel("ФИО директора", { exact: true })).toHaveValue(
        "Иванов Иван",
    );
    await page
        .getByRole("button", { name: "Сохранить изменения", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page.unroute("**/web/companies/suggestions/*");
    await page.route("**/web/companies/suggestions/*", (route) =>
        route.fulfill({
            status: 502,
            json: { message: "Подсказки временно недоступны" },
        }),
    );
    await page.getByLabel("БИК", { exact: true }).fill("04452");
    await expect(
        page.getByText("Подсказки временно недоступны", { exact: true }),
    ).toBeVisible();
    await page.getByLabel("Телефон", { exact: true }).fill("+7 999 1234567");
    await page
        .getByRole("button", { name: "Сохранить изменения", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
});

test("company contact action locks the company for viewing editing and creation", async ({
    page,
    context,
}) => {
    await login(page);
    await page.getByRole("link", { name: "Компании", exact: true }).click();
    await page
        .getByRole("button", {
            name: "Контактные лица: ООО Тестовый склад",
            exact: true,
        })
        .click();
    await expect(page).toHaveURL(/\/main\/company_contacts\?company_id=\d+/);
    await expect(
        page.getByRole("button", { name: "Другой контакт", exact: true }),
    ).toHaveCount(0);
    await expect(
        page.getByRole("button", { name: "Иван Тестовый", exact: true }),
    ).toBeVisible();
    await expect(
        page.getByRole("combobox", { name: "Фильтр компании", exact: true }),
    ).toHaveCount(0);
    await page
        .getByRole("button", { name: "Просмотр: Иван Тестовый", exact: true })
        .click();
    await expect(page.getByLabel("ФИО *", { exact: true })).toBeDisabled();
    await page
        .getByRole("button", { name: "Закрыть карточку", exact: true })
        .click();
    await page
        .getByRole("button", {
            name: "Редактировать: Иван Тестовый",
            exact: true,
        })
        .click();
    await expect(page.getByLabel("Компания", { exact: true })).toBeDisabled();
    await page
        .getByLabel("Контактное значение", { exact: true })
        .fill("updated@example.test");
    await page
        .getByRole("button", { name: "Сохранить изменения", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page
        .getByRole("button", { name: "Закрыть карточку", exact: true })
        .click();
    await page
        .getByRole("button", {
            name: "+ Добавить контактное лицо",
            exact: true,
        })
        .click();
    await expect(page.getByLabel("Компания", { exact: true })).toBeDisabled();
    await page
        .getByLabel("ФИО *", { exact: true })
        .fill("Контакт только склада");
    await page.getByLabel("Краткое имя *", { exact: true }).fill("Контакт");
    await page
        .getByRole("button", { name: "Создать запись", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await expect(page).toHaveURL(
        /\/main\/company_contacts\/\d+\/edit\?company_id=\d+/,
    );
    const contactUrl = page.url();
    await page.reload();
    await expect(page).toHaveURL(contactUrl);
    await expect(
        page.getByRole("button", {
            name: "Контакт только склада",
            exact: true,
        }),
    ).toBeVisible();
    await page.screenshot({
        path: "/tmp/wms-company-scoped-contacts.png",
        fullPage: true,
    });
    await context.setOffline(true);
    await page.getByRole("link", { name: "Компании", exact: true }).click();
    await page
        .getByRole("button", {
            name: "Контактные лица: ООО Тестовый склад",
            exact: true,
        })
        .click();
    await expect(
        page.getByRole("button", {
            name: "Контакт только склада",
            exact: true,
        }),
    ).toBeVisible();
    await expect(
        page.getByRole("combobox", { name: "Фильтр компании", exact: true }),
    ).toHaveCount(0);
    await page
        .getByRole("link", { name: "Контактные лица", exact: true })
        .click();
    await expect(
        page.getByRole("combobox", { name: "Фильтр компании", exact: true }),
    ).toBeVisible();
    await expect(
        page.getByRole("button", { name: "Другой контакт", exact: true }),
    ).toBeVisible();
});

test("clients ribbon, CRUD and offline cache", async ({ page, context }) => {
    await login(page);
    await page
        .locator(".sidebar")
        .getByRole("link", { name: "Клиенты", exact: true })
        .click();
    await expect(
        page
            .getByRole("navigation", { name: "Разделы клиентов" })
            .getByRole("link", { name: "Клиенты", exact: true }),
    ).toBeVisible();
    await expect(
        page.getByRole("button", { name: "Тестовый клиент", exact: true }),
    ).toBeVisible();
    await page
        .getByRole("button", { name: "Добавить клиента", exact: true })
        .click();
    await page
        .getByLabel("Название *", { exact: true })
        .fill("Новый клиент браузера");
    await page.getByLabel("Краткое название", { exact: true }).fill("Браузер");
    await page
        .getByRole("button", { name: "Создать клиента", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page
        .getByRole("button", { name: "Закрыть карточку", exact: true })
        .click();
    await page
        .getByRole("button", {
            name: "Просмотр: Новый клиент браузера",
            exact: true,
        })
        .click();
    await expect(page.getByLabel("Название *", { exact: true })).toBeDisabled();
    await page
        .getByRole("button", { name: "Закрыть карточку", exact: true })
        .click();
    await page
        .getByRole("button", {
            name: "Редактировать: Новый клиент браузера",
            exact: true,
        })
        .click();
    await page.getByLabel("Статус", { exact: true }).selectOption("2");
    await page
        .getByRole("button", { name: "Сохранить изменения", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page
        .getByRole("button", { name: "Закрыть карточку", exact: true })
        .click();
    await page.getByLabel("Фильтр статуса", { exact: true }).selectOption("2");
    await expect(
        page.getByRole("button", {
            name: "Новый клиент браузера",
            exact: true,
        }),
    ).toBeVisible();
    await page.screenshot({ path: "/tmp/wms-clients.png", fullPage: true });
    await context.setOffline(true);
    await page
        .getByRole("link", { name: "Администрирование", exact: true })
        .click();
    await page
        .locator(".sidebar")
        .getByRole("link", { name: "Клиенты", exact: true })
        .click();
    await expect(
        page.getByRole("button", {
            name: "Новый клиент браузера",
            exact: true,
        }),
    ).toBeVisible();
    await expect(
        page.getByRole("button", { name: "Добавить клиента", exact: true }),
    ).toBeDisabled();
    await context.setOffline(false);
    await expect(
        page.getByRole("button", { name: "Добавить клиента", exact: true }),
    ).toBeEnabled();
    await page
        .getByRole("button", {
            name: "Удалить: Новый клиент браузера",
            exact: true,
        })
        .click();
    await page
        .getByRole("dialog")
        .getByRole("button", { name: "Удалить", exact: true })
        .click();
    await expect(
        page.getByRole("button", {
            name: "Новый клиент браузера",
            exact: true,
        }),
    ).toHaveCount(0);
});

test("canonical card links restore forms and support browser history", async ({
    page,
}) => {
    await login(page);
    await expect(page).toHaveURL("/main/users");
    await page
        .locator(".sidebar")
        .getByRole("link", { name: "Клиенты", exact: true })
        .click();
    await expect(page).toHaveURL("/clients/clients");
    await page
        .getByRole("button", { name: "Просмотр: Тестовый клиент", exact: true })
        .click();
    await expect(page).toHaveURL(/\/clients\/clients\/\d+\/view$/);
    const viewUrl = page.url();
    await page.reload();
    await expect(page.getByLabel("Название *", { exact: true })).toBeDisabled();
    await page
        .getByRole("button", { name: "Закрыть карточку", exact: true })
        .click();
    await expect(page).toHaveURL("/clients/clients");
    await page.goBack();
    await expect(page).toHaveURL(viewUrl);
    await expect(page.getByLabel("Название *", { exact: true })).toBeDisabled();
    await page.goForward();
    await expect(page.getByLabel("Название *", { exact: true })).toHaveCount(0);
    await page
        .getByRole("button", {
            name: "Редактировать: Тестовый клиент",
            exact: true,
        })
        .click();
    await expect(page).toHaveURL(/\/clients\/clients\/\d+\/edit$/);
    await page.reload();
    await expect(page.getByLabel("Название *", { exact: true })).toHaveValue(
        "Тестовый клиент",
    );
    await expect(page.getByLabel("Название *", { exact: true })).toBeEnabled();
    await page
        .getByRole("button", { name: "Закрыть карточку", exact: true })
        .click();
    await page
        .getByRole("button", { name: "Удалить: Тестовый клиент", exact: true })
        .click();
    await expect(page).toHaveURL(/\/clients\/clients\/\d+\/delete$/);
    await page.reload();
    await expect(page.getByRole("dialog")).toBeVisible();
    await page
        .getByRole("dialog")
        .getByRole("button", { name: "Отмена", exact: true })
        .click();
    await expect(
        page.getByRole("button", { name: "Тестовый клиент", exact: true }),
    ).toBeVisible();
    await page.goto("/clients/clients/0/create");
    await expect(page.getByLabel("Название *", { exact: true })).toHaveValue(
        "",
    );
    await expect(
        page.getByRole("button", { name: "Создать клиента", exact: true }),
    ).toBeVisible();
});

test("reference submenu supports all four directories and cached navigation", async ({
    page,
    context,
}) => {
    await login(page);
    await page
        .getByRole("button", { name: "Справочники", exact: false })
        .click();
    const submenu = page.getByRole("navigation", {
        name: "Справочники",
        exact: true,
    });
    await expect(submenu).toBeVisible();
    const triggerBox = await page
        .getByRole("button", { name: "Справочники", exact: false })
        .boundingBox();
    const menuBox = await submenu.boundingBox();
    expect(Math.abs(menuBox!.x - triggerBox!.x)).toBeLessThan(2);
    expect(menuBox!.y).toBeGreaterThanOrEqual(
        triggerBox!.y + triggerBox!.height,
    );
    const moduleBox = await submenu
        .getByRole("link", { name: "Модули", exact: true })
        .boundingBox();
    const featureBox = await submenu
        .getByRole("link", { name: "Возможности", exact: true })
        .boundingBox();
    expect(featureBox!.y).toBeGreaterThanOrEqual(
        moduleBox!.y + moduleBox!.height,
    );
    await page.keyboard.press("Escape");
    await expect(submenu).toHaveCount(0);
    await page
        .getByRole("button", { name: "Справочники", exact: false })
        .press("ArrowDown");
    await expect(
        submenu.getByRole("link", { name: "Права доступа", exact: true }),
    ).toBeFocused();

    for (const [title, entity] of [
        ["Модули", "modules"],
        ["Возможности", "features"],
        ["Иконки", "icons"],
        ["Файлы", "files"],
    ]) {
        if (
            !(await page
                .getByRole("navigation", { name: "Справочники", exact: true })
                .isVisible())
        )
            await page
                .getByRole("button", { name: "Справочники", exact: false })
                .click();
        await page
            .getByRole("navigation", { name: "Справочники", exact: true })
            .getByRole("link", { name: title, exact: true })
            .click();
        await expect(page).toHaveURL(`/main/${entity}`);
        await page
            .getByRole("button", { name: "Добавить запись", exact: true })
            .click();
        await expect(page).toHaveURL(`/main/${entity}/0/create`);
        await page
            .getByLabel("Название *", { exact: true })
            .fill(`Новая запись ${entity}`);
        if (entity === "features") {
            await page
                .getByLabel("Модуль", { exact: true })
                .selectOption({ label: "Склад" });
            await page.getByLabel("Ресурс", { exact: true }).selectOption("1");
        }
        if (["icons", "files"].includes(entity)) {
            await page
                .getByLabel("Путь", { exact: true })
                .fill(`documents/${entity}.svg`);
            await page.getByLabel("Категория", { exact: true }).fill("Склад");
            await page.getByLabel("Размер, байт", { exact: true }).fill("128");
        }
        await page
            .getByRole("button", { name: "Создать запись", exact: true })
            .click();
        await expect(
            page.getByText("Изменения сохранены", { exact: true }),
        ).toBeVisible();
        await expect(page).toHaveURL(new RegExp(`/main/${entity}/\\d+/edit$`));
        await page
            .getByRole("button", { name: "Закрыть карточку", exact: true })
            .click();
        await page
            .getByRole("button", {
                name: `Просмотр: Новая запись ${entity}`,
                exact: true,
            })
            .click();
        await expect(
            page.getByLabel("Название *", { exact: true }),
        ).toBeDisabled();
        await page.reload();
        await expect(
            page.getByLabel("Название *", { exact: true }),
        ).toBeDisabled();
        await page
            .getByRole("button", { name: "Закрыть карточку", exact: true })
            .click();
        await page
            .getByRole("button", {
                name: `Редактировать: Новая запись ${entity}`,
                exact: true,
            })
            .click();
        await page.getByLabel("Статус", { exact: true }).selectOption("2");
        await page
            .getByRole("button", { name: "Сохранить изменения", exact: true })
            .click();
        await expect(
            page.getByText("Изменения сохранены", { exact: true }),
        ).toBeVisible();
        await page
            .getByRole("button", { name: "Закрыть карточку", exact: true })
            .click();
        await page
            .getByRole("button", {
                name: `Удалить: Новая запись ${entity}`,
                exact: true,
            })
            .click();
        await page
            .getByRole("dialog")
            .getByRole("button", { name: "Удалить", exact: true })
            .click();
        await expect(
            page.getByRole("button", {
                name: `Новая запись ${entity}`,
                exact: true,
            }),
        ).toHaveCount(0);
    }
    await page
        .getByRole("button", { name: "Справочники", exact: false })
        .click();
    await page
        .getByRole("navigation", { name: "Справочники", exact: true })
        .getByRole("link", { name: "Модули", exact: true })
        .click();
    await expect(
        page.getByRole("button", { name: "Склад", exact: true }),
    ).toBeVisible();
    await page
        .getByRole("button", { name: "Справочники", exact: false })
        .click();
    await page.screenshot({ path: "/tmp/wms-references.png", fullPage: true });
    await page.keyboard.press("Escape");
    await context.setOffline(true);
    await page
        .getByRole("button", { name: "Справочники", exact: false })
        .click();
    await page
        .getByRole("navigation", { name: "Справочники", exact: true })
        .getByRole("link", { name: "Файлы", exact: true })
        .click();
    await expect(
        page.getByRole("button", { name: "Документ", exact: true }),
    ).toBeVisible();
    await expect(
        page.getByRole("button", { name: "Добавить запись", exact: true }),
    ).toBeDisabled();
});

test("client legal entities and individuals support cards, links and offline navigation", async ({
    page,
    context,
}) => {
    await login(page);
    await page
        .locator(".sidebar")
        .getByRole("link", { name: "Клиенты", exact: true })
        .click();
    const ribbon = page.getByRole("navigation", {
        name: "Разделы клиентов",
        exact: true,
    });
    await ribbon.getByRole("link", { name: "Юр.лица", exact: true }).click();
    await expect(page).toHaveURL("/clients/companies");
    await expect(
        page.getByRole("combobox", { name: "Тип компании", exact: true }),
    ).toHaveCount(0);
    for (const label of ["Наша", "Клиент", "Партнёр"])
        await expect(
            page.getByRole("columnheader", { name: label, exact: true }),
        ).toHaveCount(0);
    await page
        .getByRole("button", { name: "+ Добавить компанию", exact: true })
        .click();
    await page
        .getByLabel("Название компании *", { exact: true })
        .fill("Юрлицо браузера");
    await page.getByLabel("Краткое название *", { exact: true }).fill("Юрлицо");
    await page
        .getByRole("combobox", { name: "Клиент", exact: true })
        .selectOption({ label: "Тестовый клиент" });
    await page.getByLabel("ОКПО", { exact: true }).fill("12345678");
    for (const label of ["Наша", "Клиент", "Партнёр"])
        await expect(
            page.getByRole("checkbox", { name: label, exact: true }),
        ).toHaveCount(0);
    await page
        .getByRole("button", { name: "Создать запись", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await expect(page).toHaveURL(/\/clients\/companies\/\d+\/edit$/);
    await page
        .getByRole("button", { name: "Закрыть карточку", exact: true })
        .click();
    await page
        .getByRole("button", { name: "Просмотр: Юрлицо браузера", exact: true })
        .click();
    await expect(page.getByLabel("ОКПО", { exact: true })).toBeDisabled();
    await expect(
        page.getByRole("button", {
            name: "Контактные лица: Юрлицо браузера",
            exact: true,
        }),
    ).toHaveCount(0);
    await ribbon.getByRole("link", { name: "Физ.лица", exact: true }).click();
    await expect(page).toHaveURL("/clients/individuals");
    await page
        .getByRole("button", { name: "Добавить запись", exact: true })
        .click();
    await page.getByLabel("ФИО *", { exact: true }).fill("Физлицо браузера");
    await page.getByLabel("Имя", { exact: true }).fill("Иван");
    await page.getByLabel("Дата рождения", { exact: true }).fill("02.01.1990");
    await page.getByLabel("Серия паспорта", { exact: true }).fill("0000");
    await page
        .getByRole("combobox", { name: "Клиент", exact: true })
        .selectOption({ label: "Тестовый клиент" });
    await page
        .getByRole("button", { name: "Создать запись", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await expect(page).toHaveURL(/\/clients\/individuals\/\d+\/edit$/);
    await page.reload();
    await expect(page.getByLabel("Дата рождения", { exact: true })).toHaveValue(
        "02.01.90",
    );
    await page.getByLabel("Телефон", { exact: true }).fill("+79991234567");
    await page
        .getByRole("button", { name: "Сохранить изменения", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page
        .getByRole("button", { name: "Закрыть карточку", exact: true })
        .click();
    await page.screenshot({
        path: "/tmp/wms-client-individuals.png",
        fullPage: true,
    });
    await context.setOffline(true);
    await ribbon.getByRole("link", { name: "Юр.лица", exact: true }).click();
    await expect(
        page.getByRole("button", { name: "Юрлицо браузера", exact: true }),
    ).toBeVisible();
    await ribbon.getByRole("link", { name: "Физ.лица", exact: true }).click();
    await expect(
        page.getByRole("button", { name: "Физлицо браузера", exact: true }),
    ).toBeVisible();
    await context.setOffline(false);
    await page
        .getByRole("button", { name: "Удалить: Физлицо браузера", exact: true })
        .click();
    await page
        .getByRole("dialog")
        .getByRole("button", { name: "Удалить", exact: true })
        .click();
    await expect(
        page.getByRole("button", { name: "Физлицо браузера", exact: true }),
    ).toHaveCount(0);
    await ribbon.getByRole("link", { name: "Юр.лица", exact: true }).click();
    await page
        .getByRole("button", { name: "Удалить: Юрлицо браузера", exact: true })
        .click();
    await page
        .getByRole("dialog")
        .getByRole("button", { name: "Удалить", exact: true })
        .click();
    await expect(
        page.getByRole("button", { name: "Юрлицо браузера", exact: true }),
    ).toHaveCount(0);
});

test("client actions lock both party directories to the selected client", async ({
    page,
    context,
}) => {
    await login(page);
    for (const [party, action, ownName, otherName] of [
        [
            "companies",
            "Юрлица",
            "ООО Клиентское юрлицо",
            "Юрлицо другого клиента",
        ],
        [
            "individuals",
            "Физлица",
            "Тестовое физлицо",
            "Физлицо другого клиента",
        ],
    ]) {
        await page
            .locator(".sidebar")
            .getByRole("link", { name: "Клиенты", exact: true })
            .click();
        await page
            .getByRole("button", {
                name: `${action}: Тестовый клиент`,
                exact: true,
            })
            .click();
        await expect(page).toHaveURL(
            new RegExp(`/clients/${party}\\?client_id=1$`),
        );
        await expect(
            page.getByRole("button", { name: otherName, exact: true }),
        ).toHaveCount(0);
        await page
            .getByRole("button", { name: `Просмотр: ${ownName}`, exact: true })
            .click();
        await expect(
            page.getByRole("combobox", { name: "Клиент", exact: true }),
        ).toBeDisabled();
        await page
            .getByRole("button", { name: "Закрыть карточку", exact: true })
            .click();
        await page
            .getByRole("button", {
                name: `Редактировать: ${ownName}`,
                exact: true,
            })
            .click();
        await expect(
            page.getByRole("combobox", { name: "Клиент", exact: true }),
        ).toBeDisabled();
        await page.getByLabel("Телефон", { exact: true }).fill("+79990001122");
        await page
            .getByRole("button", { name: "Сохранить изменения", exact: true })
            .click();
        await expect(
            page.getByText("Изменения сохранены", { exact: true }),
        ).toBeVisible();
        await page
            .getByRole("button", { name: "Закрыть карточку", exact: true })
            .click();
        await page
            .getByRole("button", {
                name:
                    party === "companies"
                        ? "+ Добавить компанию"
                        : "Добавить запись",
                exact: true,
            })
            .click();
        await expect(
            page.getByRole("combobox", { name: "Клиент", exact: true }),
        ).toBeDisabled();
        await expect(
            page.getByRole("combobox", { name: "Клиент", exact: true }),
        ).toHaveValue("1");
        await page
            .getByLabel(
                party === "companies" ? "Название компании *" : "ФИО *",
                { exact: true },
            )
            .fill(`Новое ${action} клиента`);
        if (party === "companies")
            await page
                .getByLabel("Краткое название *", { exact: true })
                .fill("Клиентское");
        await page
            .getByRole("button", { name: "Создать запись", exact: true })
            .click();
        await expect(
            page.getByText("Изменения сохранены", { exact: true }),
        ).toBeVisible();
        await page.reload();
        await expect(page).toHaveURL(
            new RegExp(`/clients/${party}/\\d+/edit\\?client_id=1$`),
        );
        await expect(
            page.getByRole("combobox", { name: "Клиент", exact: true }),
        ).toBeDisabled();
        await page
            .getByRole("button", { name: "Закрыть карточку", exact: true })
            .click();
        await context.setOffline(true);
        await page
            .locator(".sidebar")
            .getByRole("link", { name: "Клиенты", exact: true })
            .click();
        await page
            .getByRole("button", {
                name: `${action}: Тестовый клиент`,
                exact: true,
            })
            .click();
        await expect(
            page.getByRole("button", {
                name: `Новое ${action} клиента`,
                exact: true,
            }),
        ).toBeVisible();
        await expect(
            page.getByRole("button", { name: otherName, exact: true }),
        ).toHaveCount(0);
        await page
            .getByRole("navigation", { name: "Разделы клиентов" })
            .getByRole("link", {
                name: party === "companies" ? "Юр.лица" : "Физ.лица",
                exact: true,
            })
            .click();
        await expect(
            page.getByRole("button", { name: otherName, exact: true }),
        ).toBeVisible();
        await context.setOffline(false);
    }
    await page.screenshot({
        path: "/tmp/wms-client-party-actions.png",
        fullPage: true,
    });
});

test("documents retain comments JSON dates and statuses; document types dropdown", async ({
    page,
    context,
}) => {
    await login(page);
    await page
        .locator(".sidebar")
        .getByRole("link", { name: "Клиенты", exact: true })
        .click();
    await page.getByRole("link", { name: "Документы", exact: true }).click();
    await page
        .getByRole("button", { name: "Добавить запись", exact: true })
        .click();
    await page
        .getByLabel("Название *", { exact: true })
        .fill("Документ браузерной проверки");
    await page.getByLabel("Сумма", { exact: true }).fill("1234,56");
    await page
        .getByRole("combobox", { name: "Клиент", exact: true })
        .selectOption("1");
    await page
        .getByRole("combobox", { name: "Тип документа", exact: true })
        .selectOption("1");
    await page
        .getByLabel("Комментарий", { exact: true })
        .fill("Комментарий к документу");
    await page
        .getByLabel("Внутренний комментарий", { exact: true })
        .fill("Для сотрудников");
    await page
        .getByLabel("Дополнительные данные (JSON)", { exact: true })
        .fill('{"number":"42","nested":{"ok":true}}');
    await page.getByLabel("Дата документа", { exact: true }).fill("10.09.26");
    await page
        .getByLabel("Дата подписания", { exact: true })
        .fill("10.09.26 12:30");
    await page
        .getByRole("button", { name: "Создать запись", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page.reload();
    await expect(page.getByLabel("Комментарий", { exact: true })).toHaveValue(
        "Комментарий к документу",
    );
    await expect(
        page.getByLabel("Внутренний комментарий", { exact: true }),
    ).toHaveValue("Для сотрудников");
    await expect(
        page.getByLabel("Дата подписания", { exact: true }),
    ).toHaveValue("10.09.26 12:30");
    expect(
        JSON.parse(
            await page.getByLabel("Дополнительные данные (JSON)").inputValue(),
        ),
    ).toEqual({ number: "42", nested: { ok: true } });
    await page
        .getByRole("combobox", { name: "Статус", exact: true })
        .selectOption("3");
    await page
        .getByRole("button", { name: "Сохранить изменения", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page.getByRole("button", { name: "Закрыть карточку" }).click();
    await page.getByLabel("Фильтр статуса", { exact: true }).selectOption("3");
    await expect(
        page.getByRole("button", {
            name: "Документ браузерной проверки",
            exact: true,
        }),
    ).toBeVisible();
    await page
        .getByRole("button", { name: "Справочники", exact: false })
        .click();
    await page
        .getByRole("link", { name: "Типы документов", exact: true })
        .click();
    await expect(
        page.getByRole("button", { name: "Счёт-фактура", exact: true }),
    ).toBeVisible();
    await context.setOffline(true);
    await page.getByRole("link", { name: "Документы", exact: true }).click();
    await page
        .getByRole("button", {
            name: "Просмотр: Документ браузерной проверки",
            exact: true,
        })
        .click();
    await expect(
        page.getByLabel("Внутренний комментарий", { exact: true }),
    ).toHaveValue("Для сотрудников");
    await page.screenshot({ path: "/tmp/wms-documents.png", fullPage: true });
    await context.setOffline(false);
});

test("document print fields follow settings and download the saved PDF", async ({
    page,
}) => {
    await login(page);
    await page.goto("/clients/documents/0/create");
    await page.getByLabel("Название *", { exact: true }).fill("PDF проверка");
    await page
        .getByRole("combobox", { name: "Клиент", exact: true })
        .selectOption("1");
    await page
        .getByRole("combobox", { name: "Тип документа", exact: true })
        .selectOption("1");
    await page
        .getByRole("combobox", { name: "Исполнитель", exact: true })
        .selectOption("1");
    await page
        .getByRole("combobox", { name: "Заказчик", exact: true })
        .selectOption("1");
    await expect(page.getByLabel("Оплатить до", { exact: true })).toBeVisible();
    await page.getByLabel("Номер документа", { exact: true }).fill("PDF-42");
    await page
        .getByRole("button", { name: "Добавить позицию", exact: true })
        .click();
    await page.getByLabel("Наименование: позиция 1").fill("Хранение");
    await page.getByLabel("Цена без НДС: позиция 1").fill("100.25");
    await page
        .getByRole("button", { name: "Создать запись", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page.reload();
    await expect(
        page.getByRole("combobox", { name: "Заказчик", exact: true }),
    ).toHaveValue("1");
    await expect(
        page.getByLabel("Номер документа", { exact: true }),
    ).toHaveValue("PDF-42");
    await page
        .getByRole("button", { name: "Закрыть карточку", exact: true })
        .click();
    const download = page.waitForEvent("download");
    await page
        .getByRole("button", { name: "Скачать: PDF проверка", exact: true })
        .click();
    expect((await download).suggestedFilename()).toMatch(/^document_\d+\.pdf$/);
    await page
        .getByRole("button", {
            name: "Редактировать: PDF проверка",
            exact: true,
        })
        .click();
    await page
        .getByRole("combobox", { name: "Тип документа", exact: true })
        .selectOption("5");
    await expect(
        page.getByLabel("Номер договора", { exact: true }),
    ).toBeVisible();
    await expect(
        page.getByLabel("Изменения договора", { exact: true }),
    ).toBeVisible();
    await expect(page.getByLabel("Оплатить до", { exact: true })).toHaveCount(
        0,
    );
});

test("Russian calendar ignores browser locale and shows Monday first and 24-hour time", async ({
    page,
}) => {
    await login(page);
    await page.goto("/clients/documents/0/create");
    const date = page.getByLabel("Дата подписания", { exact: true });
    await date.fill("10.09.26 23:45");
    await date.press("Tab");
    await expect(date).toHaveValue("10.09.26 23:45");
    await date.click();
    const calendar = page.locator(".flatpickr-calendar.open");
    await expect(calendar.locator(".flatpickr-weekday")).toHaveText([
        "Пн",
        "Вт",
        "Ср",
        "Чт",
        "Пт",
        "Сб",
        "Вс",
    ]);
    await expect(calendar.locator(".flatpickr-am-pm")).toHaveCount(0);
    await expect(calendar.locator(".flatpickr-hour")).toHaveValue("23");
    await expect(calendar.locator(".flatpickr-minute")).toHaveValue("45");
    await page.screenshot({
        path: "/tmp/wms-russian-calendar.png",
        fullPage: true,
    });
});

test("goods navigation CRUD articles and offline cache", async ({
    page,
    context,
}) => {
    await login(page);
    await page
        .locator(".sidebar")
        .getByRole("link", { name: "Товары", exact: true })
        .click();
    await expect(page).toHaveURL("/goods/goods");
    await expect(
        page
            .getByRole("navigation", { name: "Разделы товаров" })
            .getByRole("link", { name: "Товары", exact: true }),
    ).toBeVisible();
    await expect(
        page.locator("thead tr").first().locator("th").first(),
    ).toHaveText("#");
    await page
        .getByRole("button", { name: "Добавить запись", exact: true })
        .click();
    await page.getByLabel("Название *", { exact: true }).fill("Товар браузера");
    await page.getByLabel("Краткое название", { exact: true }).fill("Товар");
    await page.getByLabel("Код", { exact: true }).fill("G-001");
    const typeSelect = page.getByLabel("Тип товара", { exact: true });
    const unitSelect = page.getByLabel("Единица измерения", { exact: true });
    await expect(
        typeSelect.locator("option").filter({ hasText: /^Товар$/ }),
    ).toHaveCount(1);
    await expect(
        unitSelect.locator("option").filter({ hasText: /^Штука$/ }),
    ).toHaveCount(1);
    await typeSelect.selectOption({ label: "Товар" });
    await unitSelect.selectOption({ label: "Штука" });
    const chosenType = await typeSelect.inputValue();
    const chosenUnit = await unitSelect.inputValue();
    for (const [label, first, second] of [
        ["Артикулы", "000123", "ART-002"],
        ["Штрихкоды", "0000123456789", "0012345678901"],
    ]) {
        await page.getByLabel(label + ": 1", { exact: true }).fill(first);
        await page
            .getByRole("button", {
                name: "Добавить строку: " + label + ", 1",
                exact: true,
            })
            .click();
        await page.getByLabel(label + ": 2", { exact: true }).fill("лишнее");
        await page
            .getByRole("button", {
                name: "Добавить строку: " + label + ", 2",
                exact: true,
            })
            .click();
        await page.getByLabel(label + ": 3", { exact: true }).fill(second);
        await page
            .getByRole("button", {
                name: "Удалить строку: " + label + ", 2",
                exact: true,
            })
            .click();
        await page
            .getByRole("button", {
                name: "Добавить строку: " + label + ", 2",
                exact: true,
            })
            .click();
    }
    await page
        .getByRole("button", { name: "Создать запись", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page.reload();
    await expect(typeSelect).toHaveValue(chosenType);
    await expect(unitSelect).toHaveValue(chosenUnit);
    await expect(page.getByLabel("Артикулы: 1", { exact: true })).toHaveValue(
        "000123",
    );
    await expect(page.getByLabel("Артикулы: 2", { exact: true })).toHaveValue(
        "ART-002",
    );
    await expect(page.getByLabel("Артикулы: 3", { exact: true })).toHaveCount(
        0,
    );
    await expect(page.getByLabel("Штрихкоды: 1", { exact: true })).toHaveValue(
        "0000123456789",
    );
    await expect(page.getByLabel("Штрихкоды: 2", { exact: true })).toHaveValue(
        "0012345678901",
    );
    await page
        .getByRole("button", {
            name: "Удалить строку: Штрихкоды, 2",
            exact: true,
        })
        .click();
    await page
        .getByRole("button", {
            name: "Удалить строку: Штрихкоды, 1",
            exact: true,
        })
        .click();
    await expect(page.getByLabel("Штрихкоды: 1", { exact: true })).toHaveValue(
        "",
    );
    await page.getByLabel("Код", { exact: true }).fill("G-002");
    await page
        .getByRole("button", { name: "Сохранить изменения", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page.getByRole("button", { name: "Закрыть карточку" }).click();
    await page.getByLabel("Поиск: Товары", { exact: true }).fill("ART-002");
    await expect(
        page.getByRole("button", { name: "Товар браузера", exact: true }),
    ).toBeVisible();
    await context.setOffline(true);
    await page
        .locator(".sidebar")
        .getByRole("link", { name: "Главная", exact: true })
        .click();
    await page
        .locator(".sidebar")
        .getByRole("link", { name: "Товары", exact: true })
        .click();
    await expect(
        page.getByRole("button", { name: "Товар браузера", exact: true }),
    ).toBeVisible();
    await expect(
        page.getByRole("button", { name: "Добавить запись", exact: true }),
    ).toBeDisabled();
    await context.setOffline(false);
    await page.screenshot({ path: "/tmp/wms-goods.png", fullPage: true });
    await page
        .getByRole("button", { name: "Удалить: Товар браузера", exact: true })
        .click();
    await page
        .getByRole("dialog")
        .getByRole("button", { name: "Удалить", exact: true })
        .click();
    await expect(
        page.getByRole("button", { name: "Товар браузера", exact: true }),
    ).toHaveCount(0);
});

test("goods reference dropdown creates edits and caches goods catalogs", async ({
    page,
    context,
}) => {
    await login(page);
    await page
        .locator(".sidebar")
        .getByRole("link", { name: "Товары", exact: true })
        .click();
    for (const [label, path] of [
        ["Типы товаров", "type_goods"],
        ["Единицы измерения", "unit_goods"],
        ["Виды кодов маркировки", "kind_kiz"],
    ]) {
        await page
            .getByRole("button", { name: "Справочники", exact: false })
            .click();
        await page
            .getByRole("navigation", { name: "Справочники", exact: true })
            .getByRole("link", { name: label, exact: true })
            .click();
        await expect(page).toHaveURL(`/goods/${path}`);
        await page
            .getByRole("button", { name: "Добавить запись", exact: true })
            .click();
        await page
            .getByLabel("Название *", { exact: true })
            .fill(`Новая запись ${path}`);
        await page
            .getByRole("button", { name: "Создать запись", exact: true })
            .click();
        await expect(
            page.getByText("Изменения сохранены", { exact: true }),
        ).toBeVisible();
        await page.getByLabel("Краткое название", { exact: true }).fill("Тест");
        await page
            .getByRole("button", { name: "Сохранить изменения", exact: true })
            .click();
        await expect(
            page.getByText("Изменения сохранены", { exact: true }),
        ).toBeVisible();
        await page.reload();
        await expect(
            page.getByLabel("Краткое название", { exact: true }),
        ).toHaveValue("Тест");
        await page.getByRole("button", { name: "Закрыть карточку" }).click();
    }
    await context.setOffline(true);
    await page
        .getByRole("button", { name: "Справочники", exact: false })
        .click();
    await page.getByRole("link", { name: "Типы товаров", exact: true }).click();
    await expect(
        page.getByRole("button", {
            name: "Новая запись type_goods",
            exact: true,
        }),
    ).toBeVisible();
    await expect(
        page.getByRole("button", { name: "Добавить запись", exact: true }),
    ).toBeDisabled();
    await context.setOffline(false);
});

test("shared records retain NULL ownership and disappear from the card and IndexedDB on revocation", async ({
    page,
}) => {
    const sql = (query: string) =>
        execFileSync(
            "psql",
            [
                "-h",
                "/var/run/postgresql",
                "-U",
                "root",
                "-d",
                "wms_browser_test",
                "-v",
                "ON_ERROR_STOP=1",
                "-At",
                "-c",
                query,
            ],
            { encoding: "utf8" },
        ).trim();
    const id = sql(
        "INSERT INTO goods.type_goods (name, status) VALUES ('Общий тип браузера', 1) RETURNING id",
    ).split("\n")[0];
    expect(id).toMatch(/^\d+$/);
    try {
        await login(page);
        await page.goto("/goods/type_goods/" + id + "/edit");
        await expect(
            page.getByLabel("Название *", { exact: true }),
        ).toHaveValue("Общий тип браузера");
        await page
            .getByLabel("Название *", { exact: true })
            .fill("Общий тип изменён");
        const saved = page.waitForResponse(
            (response) =>
                response.url().endsWith("/web/goods/type_goods/" + id) &&
                response.request().method() === "PUT",
        );
        await page
            .getByRole("button", { name: "Сохранить изменения", exact: true })
            .click();
        expect((await (await saved).json()).data.tenant_id).toBeNull();
        expect(
            sql(
                "SELECT tenant_id IS NULL FROM goods.type_goods WHERE id = " +
                    id,
            ),
        ).toBe("t");
        sql(
            "INSERT INTO main.tenant_entity (entity_type, entity_id, tenant_id) VALUES ('App\\Models\\GoodType', " +
                id +
                ", 'another_tenant')",
        );
        await page.evaluate(() => window.dispatchEvent(new Event("focus")));
        await expect(
            page.getByLabel("Название *", { exact: true }),
        ).toHaveCount(0);
        await expect(
            page.getByRole("button", {
                name: "Общий тип изменён",
                exact: true,
            }),
        ).toHaveCount(0);
        const stored = await page.evaluate(async () => {
            const db = await new Promise<IDBDatabase>((resolve, reject) => {
                const request = indexedDB.open("wms_cache");
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });
            return await new Promise<string>((resolve, reject) => {
                const request = db
                    .transaction("entries")
                    .objectStore("entries")
                    .getAll();
                request.onsuccess = () => {
                    db.close();
                    resolve(JSON.stringify(request.result));
                };
                request.onerror = () => reject(request.error);
            });
        });
        expect(stored).not.toContain("Общий тип изменён");
        sql(
            "DELETE FROM main.tenant_entity WHERE entity_type = 'App\\Models\\GoodType' AND entity_id = " +
                id,
        );
        await page.evaluate(() => window.dispatchEvent(new Event("focus")));
        await expect(
            page.getByRole("button", {
                name: "Общий тип изменён",
                exact: true,
            }),
        ).toBeVisible();
    } finally {
        sql(
            "DELETE FROM main.tenant_entity WHERE entity_type = 'App\\Models\\GoodType' AND entity_id = " +
                id,
        );
        sql("DELETE FROM goods.type_goods WHERE id = " + id);
    }
});

test("goods parent searchable select saves choices and supports keyboard and clearing", async ({
    page,
}) => {
    await login(page);
    await page.goto("/goods/goods/0/create");
    await page
        .getByLabel("Название *", { exact: true })
        .fill("Родитель для поиска");
    await page.getByLabel("Код", { exact: true }).fill("PARENT-SEARCH");
    await page
        .getByRole("button", { name: "Создать запись", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page.goto("/goods/goods/0/create");
    await page
        .getByLabel("Название *", { exact: true })
        .fill("Дочерний для поиска");
    const parent = page.getByRole("combobox", {
        name: "Родительская запись",
        exact: true,
    });
    await parent.fill("parent-search");
    await expect(
        page.getByRole("option", { name: "Родитель для поиска", exact: true }),
    ).toBeVisible();
    await parent.press("ArrowDown");
    await parent.press("Enter");
    await expect(parent).toHaveValue("Родитель для поиска");
    await page
        .getByRole("button", { name: "Создать запись", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page.reload();
    await expect(parent).toHaveValue("Родитель для поиска");
    await parent.fill("несуществующий родитель");
    await expect(
        page.getByText("Ничего не найдено", { exact: true }),
    ).toBeVisible();
    await parent.press("Escape");
    await expect(parent).toHaveValue("Родитель для поиска");
    await parent.click();
    await page
        .getByRole("listbox", { name: "Родительская запись", exact: true })
        .getByRole("option", { name: "Не выбрано", exact: true })
        .click();
    await page
        .getByRole("button", { name: "Сохранить изменения", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page.reload();
    await expect(parent).toHaveValue("");
});

test("goods tree expands categories filters with ancestors and refreshes parents after a move", async ({
    page,
}) => {
    const sql = (query: string) =>
        execFileSync(
            "psql",
            [
                "-h",
                "/var/run/postgresql",
                "-U",
                "root",
                "-d",
                "wms_browser_test",
                "-v",
                "ON_ERROR_STOP=1",
                "-At",
                "-c",
                query,
            ],
            { encoding: "utf8" },
        ).trim();
    const create = (name: string, parent?: string) =>
        sql(
            "INSERT INTO goods.goods (name, tenant_id, parent_id) VALUES ('" +
                name +
                "', 'test_org', " +
                (parent ?? "NULL") +
                ") RETURNING id",
        ).split("\n")[0];
    const a = create("АА Дерево А");
    const b = create("АА Дерево Б");
    const branch = create("АА Дерево Ветка", a);
    const leaf = create("АА Дерево Лист", branch);
    try {
        await login(page);
        await page.goto("/goods/goods");
        const tree = page.getByRole("treegrid", {
            name: "Дерево товаров",
            exact: true,
        });
        const row = (name: string) =>
            tree.getByRole("row").filter({
                has: page.getByRole("button", { name, exact: true }),
            });
        await expect(
            row("АА Дерево А").getByRole("img", {
                name: "Категория",
                exact: true,
            }),
        ).toBeVisible();
        await expect(row("АА Дерево Ветка")).toHaveCount(0);
        await page
            .getByRole("button", {
                name: "Развернуть: АА Дерево А",
                exact: true,
            })
            .click();
        await expect(row("АА Дерево Ветка")).toHaveAttribute("aria-level", "2");
        await page
            .getByRole("button", {
                name: "Развернуть: АА Дерево Ветка",
                exact: true,
            })
            .click();
        await expect(row("АА Дерево Лист")).toHaveAttribute("aria-level", "3");
        await expect(
            row("АА Дерево Лист").getByRole("img", {
                name: "Товар",
                exact: true,
            }),
        ).toBeVisible();
        await page
            .getByRole("button", {
                name: "Редактировать: АА Дерево Ветка",
                exact: true,
            })
            .click();
        await page
            .getByRole("combobox", { name: "Родительская запись", exact: true })
            .fill("АА Дерево Б");
        await page
            .getByRole("option", { name: "АА Дерево Б", exact: true })
            .click();
        await page
            .getByRole("button", { name: "Сохранить изменения", exact: true })
            .click();
        await expect(
            page.getByText("Изменения сохранены", { exact: true }),
        ).toBeVisible();
        await page
            .getByRole("button", { name: "Закрыть карточку", exact: true })
            .click();
        await expect(
            row("АА Дерево А").getByRole("img", { name: "Товар", exact: true }),
        ).toBeVisible();
        await expect(
            row("АА Дерево Б").getByRole("img", {
                name: "Категория",
                exact: true,
            }),
        ).toBeVisible();
        await page
            .getByRole("button", { name: "Свернуть всё", exact: true })
            .click();
        await expect(row("АА Дерево Лист")).toHaveCount(0);
        await page
            .getByLabel("Поиск: Товары", { exact: true })
            .fill("АА Дерево Лист");
        await expect(row("АА Дерево Б")).toBeVisible();
        await expect(row("АА Дерево Ветка")).toBeVisible();
        await expect(row("АА Дерево Лист")).toHaveAttribute("aria-level", "3");
        await expect(row("АА Дерево А")).toHaveCount(0);
        await page.screenshot({
            path: "/tmp/wms-goods-tree.png",
            fullPage: true,
        });
    } finally {
        sql(
            "DELETE FROM goods.goods WHERE id IN (" +
                [leaf, branch, a, b].join(",") +
                ")",
        );
    }
});

test("empty good category switch persists while categories with children cannot be toggled", async ({
    page,
}) => {
    await login(page);
    await page.goto("/goods/goods/0/create");
    await page
        .getByLabel("Название *", { exact: true })
        .fill("Ручная пустая категория");
    const toggle = page.getByRole("switch", {
        name: "Является категорией",
        exact: true,
    });
    await expect(toggle).not.toBeChecked();
    await page.getByText("Является категорией", { exact: true }).click();
    await expect(toggle).toBeChecked();
    await page
        .getByRole("button", { name: "Создать запись", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    const categoryUrl = page.url();
    await page.reload();
    await expect(toggle).toBeChecked();
    await page.goto("/goods/goods/0/create");
    await page
        .getByLabel("Название *", { exact: true })
        .fill("Ребёнок ручной категории");
    await page
        .getByRole("combobox", { name: "Родительская запись", exact: true })
        .fill("Ручная пустая категория");
    await page
        .getByRole("option", { name: "Ручная пустая категория", exact: true })
        .click();
    await page
        .getByRole("button", { name: "Создать запись", exact: true })
        .click();
    await expect(
        page.getByText("Изменения сохранены", { exact: true }),
    ).toBeVisible();
    await page.goto(categoryUrl);
    await expect(page.getByLabel("Название *", { exact: true })).toHaveValue(
        "Ручная пустая категория",
    );
    await expect(toggle).toHaveCount(0);
});

test("markings link to clients and kinds and retain edits in browser storage", async ({ page, context }) => {
    execFileSync("psql", ["-h", "/var/run/postgresql", "-U", "root", "-d", "wms_browser_test", "-v", "ON_ERROR_STOP=1", "-c", "INSERT INTO goods.goods (name, code, tenant_id) VALUES ('Товар маркировки', 'KIZ-SEARCH-001', 'test_org')"]);

    await login(page);
    await page.locator(".sidebar").getByRole("link", { name: "Товары", exact: true }).click();
    await page.getByRole("link", { name: "Маркировка", exact: true }).click();
    await expect(page).toHaveURL("/goods/kizes");
    await page.getByRole("button", { name: "Добавить запись", exact: true }).click();
    await page.getByLabel("Код маркировки", { exact: true }).fill("000123-TEST");
    await page.getByRole("combobox", { name: "Клиент", exact: true }).fill("Тестовый");
    await page.getByRole("option", { name: "Тестовый клиент", exact: true }).click();
    await page.getByLabel("Вид кода маркировки", { exact: true }).selectOption({ label: "IMEI" });
    await page.getByRole("combobox", { name: "Товар", exact: true }).fill("KIZ-SEARCH-001");
    await page.getByRole("option", { name: "Товар маркировки", exact: true }).click();

    await page.getByRole("button", { name: "Создать запись", exact: true }).click();
    await expect(page.getByText("Изменения сохранены", { exact: true })).toBeVisible();
    await page.getByLabel("Код маркировки", { exact: true }).fill("000124-TEST");
    await page.getByRole("button", { name: "Сохранить изменения", exact: true }).click();
    await expect(page.getByText("Изменения сохранены", { exact: true })).toBeVisible();
    await page.reload();
    await expect(page.getByLabel("Код маркировки", { exact: true })).toHaveValue("000124-TEST");
    await expect(page.getByRole("combobox", { name: "Клиент", exact: true })).toHaveValue("Тестовый клиент");
    await expect(page.getByRole("combobox", { name: "Товар", exact: true })).toHaveValue("Товар маркировки");
    await page.getByRole("button", { name: "Закрыть карточку" }).click();
    await expect(page.getByRole("columnheader").first()).toHaveText("#");
    await context.setOffline(true);
    await page.getByRole("button", { name: "000124-TEST", exact: true }).click();
    await expect(page.getByLabel("Код маркировки", { exact: true })).toHaveValue("000124-TEST");
    await expect(page.getByLabel("Код маркировки", { exact: true })).toBeDisabled();
    await context.setOffline(false);
    await page.getByRole("button", { name: "Закрыть карточку" }).click();
    await page.getByRole("button", { name: "Удалить: 000124-TEST", exact: true }).click();
    await page.getByRole("dialog").getByRole("button", { name: "Удалить", exact: true }).click();
    await expect(page.getByRole("button", { name: "000124-TEST", exact: true })).toHaveCount(0);
});
