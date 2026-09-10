export function isoDate(value: string | null | undefined): Date | undefined {
    const match = value?.match(
        /^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?/,
    );
    if (!match) return;
    return checkedDate(
        +match[1],
        +match[2],
        +match[3],
        +(match[4] ?? 0),
        +(match[5] ?? 0),
    );
}
function checkedDate(
    year: number,
    month: number,
    day: number,
    hour: number,
    minute: number,
): Date | undefined {
    const date = new Date(year, month - 1, day, hour, minute);
    if (
        date.getFullYear() === year &&
        date.getMonth() === month - 1 &&
        date.getDate() === day &&
        date.getHours() === hour &&
        date.getMinutes() === minute
    )
        return date;
}
export function parseRussianDate(
    value: string,
    withTime = false,
    current?: string | null,
): Date | undefined {
    const match = value
        .trim()
        .match(
            withTime
                ? /^(\d{2})\.(\d{2})\.(\d{2}|\d{4}) (\d{2}):(\d{2})$/
                : /^(\d{2})\.(\d{2})\.(\d{2}|\d{4})$/,
        );
    if (!match) return;
    let year = +match[3];
    if (match[3].length === 2) {
        const old = isoDate(current);
        year +=
            old && old.getFullYear() % 100 === year
                ? Math.floor(old.getFullYear() / 100) * 100
                : year < 50
                  ? 2000
                  : 1900;
    }
    return checkedDate(
        year,
        +match[2],
        +match[1],
        +(match[4] ?? 0),
        +(match[5] ?? 0),
    );
}
const pad = (n: number) => String(n).padStart(2, "0");
export function toIsoDate(date: Date, withTime = false): string {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}${withTime ? `T${pad(date.getHours())}:${pad(date.getMinutes())}` : ""}`;
}
export function formatDate(
    value: string | null | undefined,
    withTime = false,
): string {
    const date = isoDate(value);
    return date
        ? `${pad(date.getDate())}.${pad(date.getMonth() + 1)}.${pad(date.getFullYear() % 100)}${withTime ? ` ${pad(date.getHours())}:${pad(date.getMinutes())}` : ""}`
        : "—";
}
