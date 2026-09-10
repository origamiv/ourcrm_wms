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
        submenu.getByRole("link", { name: "Модули", exact: true }),
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
