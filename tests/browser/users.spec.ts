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
    await other.goto("/users");
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
        await other.goto(title === "Роли" ? "/roles" : "/permissions");
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
    await other.goto("/roles_rights");
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
