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
        r.url().includes("/web/users/sync?cursor="),
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
