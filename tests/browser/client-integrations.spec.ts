import { test, expect } from "@playwright/test";

test("кнопка интеграций видна в действиях клиента", async ({ page }) => {
    await page.goto("/login");
    await page.getByLabel("Email", { exact: true }).fill("admin@example.test");
    await page.getByLabel("Пароль", { exact: true }).fill("Test_password_123");
    await page.getByRole("button", { name: "Войти", exact: true }).click();
    await expect(page).toHaveURL("/");
    const relationsLoaded = page.waitForResponse(
        (response) =>
            response.url().includes("/web/clients/relations") &&
            response.status() === 200,
    );
    await page.goto("/clients/clients");
    await relationsLoaded;

    const row = page
        .locator("tbody tr")
        .filter({
            has: page.getByRole("button", {
                name: "Тестовый клиент",
                exact: true,
            }),
        })
        .first();
    const action = row.getByRole("button", {
        name: "Интеграции: Тестовый клиент",
    });
    await expect(action).toBeVisible();
    await expect(action).toBeEnabled();
    await expect(
        row.getByRole("button", { name: "Документы: Тестовый клиент" }),
    ).toBeEnabled();
    const disabledAccount = row.getByRole("button", {
        name: "Доступы: Тестовый клиент",
    });
    await expect(disabledAccount).toBeDisabled();
    await expect(disabledAccount).toHaveCSS("opacity", "0.45");
    await expect(disabledAccount.locator("img")).toHaveCSS(
        "filter",
        "grayscale(1)",
    );
    await expect(
        row.getByRole("button", { name: "Юрлица: Тестовый клиент" }),
    ).toBeEnabled();
    await expect(
        row.getByRole("button", { name: "Физлица: Тестовый клиент" }),
    ).toBeEnabled();
    const otherRow = page
        .locator("tbody tr")
        .filter({
            has: page.getByRole("button", {
                name: "Другой клиент",
                exact: true,
            }),
        })
        .first();
    await expect(
        otherRow.getByRole("button", { name: "Документы: Другой клиент" }),
    ).toBeDisabled();
    await expect(
        otherRow.getByRole("button", { name: "Интеграции: Другой клиент" }),
    ).toBeDisabled();
    await action.click();
    await expect(page).toHaveURL(/\/clients\/integrations\?client_id=1$/);

    await page.setViewportSize({ width: 390, height: 844 });
    const mobileRelationsLoaded = page.waitForResponse(
        (response) =>
            response.url().includes("/web/clients/relations") &&
            response.status() === 200,
    );
    await page.goto("/clients/clients");
    await mobileRelationsLoaded;
    await expect(action).toBeVisible();
    await action.click();
    await expect(page).toHaveURL(/\/clients\/integrations\?client_id=1$/);
});

test("интеграции клиента: меню, создание и фильтр по клиенту", async ({
    page,
}) => {
    await page.goto("/login");
    await page.getByLabel("Email", { exact: true }).fill("admin@example.test");
    await page.getByLabel("Пароль", { exact: true }).fill("Test_password_123");
    await page.getByRole("button", { name: "Войти", exact: true }).click();
    await expect(page).toHaveURL("/");

    await page.goto("/clients/integrations?client_id=1");
    await expect(
        page.getByRole("heading", {
            name: "Интеграции для клиента Тестовый клиент",
        }),
    ).toBeVisible();
    await expect(
        page.getByRole("link", { name: "Интеграции", exact: true }).last(),
    ).toHaveAttribute("aria-current", "page");
    await page.getByRole("button", { name: "Добавить запись" }).click();
    await expect(page.getByLabel("Клиент", { exact: true })).toBeDisabled();
    await page
        .getByLabel("Название *", { exact: true })
        .fill("Вебхук первого клиента");
    await page
        .getByRole("button", { name: /^(Создать запись|Сохранить изменения)$/ })
        .click();
    await expect(
        page.getByText("Запись создана. Можно добавить следующую.", {
            exact: true,
        }),
    ).toBeVisible();
    await page.getByRole("button", { name: "Закрыть карточку" }).click();
    await expect(
        page
            .getByRole("button", {
                name: "Вебхук первого клиента",
                exact: true,
            })
            .first(),
    ).toBeVisible();

    await page.goto("/clients/integrations?client_id=2");
    await expect(
        page.getByRole("button", {
            name: "Вебхук первого клиента",
            exact: true,
        }),
    ).toHaveCount(0);
});

test("редактирование интеграции открывает отдельный конструктор", async ({
    page,
}) => {
    await page.goto("/login");
    await page.getByLabel("Email", { exact: true }).fill("admin@example.test");
    await page.getByLabel("Пароль", { exact: true }).fill("Test_password_123");
    await page.getByRole("button", { name: "Войти", exact: true }).click();
    await expect(page).toHaveURL("/");
    await page.goto("/clients/integrations?client_id=1");
    await page
        .getByRole("button", {
            name: "Редактировать: Интеграция тестового клиента",
        })
        .click();
    await expect(page).toHaveURL(
        /\/clients\/integrations\/\d+\/edit\?client_id=1$/,
    );
    await expect(
        page.getByRole("heading", { name: /Редактирование интеграции/ }),
    ).toBeVisible();
    await expect(page.getByText("Доступы (Start)")).toBeVisible();
    await expect(page.getByRole("button", { name: /Прочее/ })).toBeVisible();
    await page.getByText("Доступы (Start)").click();
    await expect(page.getByText("Доступ", { exact: true })).toBeVisible();
    await page.getByRole("button", { name: "Свойства интеграции" }).click();
    await page.getByLabel("Название *").fill("Обновлённая интеграция");
    await page.getByRole("button", { name: "Сохранить", exact: true }).click();
    await expect(page.getByText("Изменения сохранены")).toBeVisible();
    await page.getByRole("button", { name: "К списку" }).click();
    await expect(page).toHaveURL(/\/clients\/integrations\?client_id=1$/);
    await expect(
        page
            .getByRole("button", {
                name: "Обновлённая интеграция",
                exact: true,
            })
            .first(),
    ).toBeVisible();
});

test("конструктор WB показывает настройки выбранного блока и сохраняет цепочку", async ({
    page,
}) => {
    await page.goto("/login");
    await page.getByLabel("Email", { exact: true }).fill("admin@example.test");
    await page.getByLabel("Пароль", { exact: true }).fill("Test_password_123");
    await page.getByRole("button", { name: "Войти", exact: true }).click();
    await expect(page).toHaveURL("/");
    await page.goto("/clients/integrations?client_id=1");
    await page
        .getByRole("button", { name: "Редактировать: WB интеграция" })
        .click();
    await expect(
        page.locator(".rule-node").getByText("Товары МП"),
    ).toBeVisible();
    await expect(page.locator(".rule-node .marketplace-avatar")).toHaveText(
        "WB",
    );
    await expect(page.getByText("Маркетплейс", { exact: true })).toBeVisible();
    await page.getByRole("combobox", { name: "Маркетплейс" }).click();
    await expect(
        page.getByRole("option", { name: /#\d+ Wildberries/ }),
    ).toBeVisible();
    await expect(
        page
            .getByRole("option", { name: /#\d+ Wildberries/ })
            .locator(".select-badge"),
    ).toHaveText("WB");
    await page.getByRole("combobox", { name: "Маркетплейс" }).press("Escape");
    await expect(
        page
            .locator(".switch-line")
            .filter({ hasText: "Синхронизировать цены" }),
    ).toBeVisible();
    await expect(page.locator(".mapping-head")).toContainText(
        "Сопоставление категорий",
    );
    await expect(page.getByText("Расписание", { exact: true })).toHaveCount(0);
    await expect(page.getByText("Доступ", { exact: true })).toHaveCount(0);
    await expect(
        page.getByText("Правила обработки", { exact: true }),
    ).toHaveCount(0);
    await page.getByRole("button", { name: /Отправка остатков/ }).click();
    await expect(page.locator(".rule-node")).toHaveCount(3);
    await page.getByLabel("Выгружать остатки").selectOption("true");
    await page.getByLabel("Формула расчёта").selectOption("fixed");
    await page.getByLabel("Выгружать фикс.").fill("25");
    await page.getByLabel("Запуск выгрузки").selectOption("schedule");
    await page.getByLabel("Расписание").selectOption("6");
    await page.getByRole("button", { name: "Сохранить", exact: true }).click();
    await expect(page.getByText("Изменения сохранены")).toBeVisible();
    await page.reload();
    await expect(page.locator(".rule-node")).toHaveCount(3);
    await expect(page.getByText("Отправка остатков").last()).toBeVisible();
    await page
        .locator(".rule-node")
        .filter({ hasText: "Отправка остатков" })
        .click();
    await expect(page.getByLabel("Формула расчёта")).toHaveValue("fixed");
    await expect(page.getByLabel("Выгружать фикс.")).toHaveValue("25");
    await expect(page.getByLabel("Расписание")).toHaveValue("6");
    await page.getByText("Доступы (Start)").click();
    await expect(page.getByText("Доступ", { exact: true })).toBeVisible();
    await expect(page.getByText("Маркетплейс", { exact: true })).toHaveCount(0);
});

test("конструктор доступен для Ozon и Яндекс Маркета", async ({ page }) => {
    await page.goto("/login");
    await page.getByLabel("Email", { exact: true }).fill("admin@example.test");
    await page.getByLabel("Пароль", { exact: true }).fill("Test_password_123");
    await page.getByRole("button", { name: "Войти", exact: true }).click();
    await expect(page).toHaveURL("/");
    for (const name of ["Ozon", "Яндекс Маркет"]) {
        await page.goto("/clients/integrations?client_id=1");
        await page
            .getByRole("button", { name: `Редактировать: ${name} интеграция` })
            .click();
        await expect(page.locator(".palette-rule")).toHaveCount(5);
        await expect(
            page.getByText("Маркетплейс", { exact: true }),
        ).toBeVisible();
    }
});

test("блок Прочее перетаскивается и сохраняет правило с JSON", async ({
    page,
}) => {
    await page.goto("/login");
    await page.getByLabel("Email", { exact: true }).fill("admin@example.test");
    await page.getByLabel("Пароль", { exact: true }).fill("Test_password_123");
    await page.getByRole("button", { name: "Войти", exact: true }).click();
    await expect(page).toHaveURL("/");
    await page.goto("/clients/integrations?client_id=1");
    await page
        .getByRole("button", {
            name: /Редактировать: (Интеграция тестового клиента|Обновлённая интеграция)/,
        })
        .click();
    await page
        .locator(".palette-rule")
        .filter({ hasText: "Прочее" })
        .dragTo(page.locator(".empty-chain"));
    await expect(page.locator(".rule-node")).toHaveCount(2);
    await page.getByRole("combobox", { name: "Правило" }).click();
    await page.getByRole("option", { name: "Тестовое правило" }).click();
    await page.getByLabel("Настройки JSON").fill('{"key":"value"}');
    await page.getByRole("button", { name: "Сохранить", exact: true }).click();
    await expect(page.getByText("Изменения сохранены")).toBeVisible();
    await page.reload();
    await page.locator(".rule-node").filter({ hasText: "Прочее" }).click();
    await expect(page.getByRole("combobox", { name: "Правило" })).toHaveValue(
        "Тестовое правило",
    );
    await expect(page.getByLabel("Настройки JSON")).toHaveValue(
        '{"key":"value"}',
    );
});

test("страница конструктора прокручивается до нижних полей", async ({
    page,
}) => {
    await page.setViewportSize({ width: 1440, height: 700 });
    await page.goto("/login");
    await page.getByLabel("Email", { exact: true }).fill("admin@example.test");
    await page.getByLabel("Пароль", { exact: true }).fill("Test_password_123");
    await page.getByRole("button", { name: "Войти", exact: true }).click();
    await expect(page).toHaveURL("/");
    await page.goto("/clients/integrations?client_id=1");
    await page
        .getByRole("button", { name: "Редактировать: WB интеграция" })
        .click();
    await page.getByRole("button", { name: "Свойства интеграции" }).click();
    const workspace = page.locator(".integration-builder-page");
    const dimensions = await workspace.evaluate((element) => ({
        height: element.clientHeight,
        content: element.scrollHeight,
    }));
    expect(dimensions.content).toBeGreaterThan(dimensions.height);
    await workspace.evaluate((element) => {
        element.scrollTop = element.scrollHeight;
    });
    await expect(
        page.getByRole("button", { name: "Сохранить", exact: true }),
    ).toBeInViewport();
});

test("логи запуска показывают календарь и запуски выбранного дня", async ({
    page,
}) => {
    await page.goto("/login");
    await page.getByLabel("Email", { exact: true }).fill("admin@example.test");
    await page.getByLabel("Пароль", { exact: true }).fill("Test_password_123");
    await page.getByRole("button", { name: "Войти", exact: true }).click();
    await expect(page).toHaveURL("/");
    await page.goto("/clients/integrations?client_id=1");
    await page
        .getByRole("button", { name: "Логи запуска: WB интеграция" })
        .click();
    await expect(page).toHaveURL(
        /\/clients\/integrations\/\d+\/logs\?client_id=1$/,
    );
    await expect(
        page.getByRole("heading", { name: "Календарь активности" }),
    ).toBeVisible();
    const activeDay = page.getByRole("button", { name: /: 2 запусков$/ });
    await expect(activeDay).toBeVisible();
    await activeDay.click();
    await expect(page.locator(".runs-table tbody tr")).toHaveCount(2);
    await expect(page.getByText("125", { exact: true })).toBeVisible();
    await expect(page.getByText("Ошибка", { exact: true })).toBeVisible();
    await page.getByRole("button", { name: "К списку" }).click();
    await expect(page).toHaveURL(/\/clients\/integrations\?client_id=1$/);
});
