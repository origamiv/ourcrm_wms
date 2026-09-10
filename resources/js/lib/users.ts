import { createEntitySync } from "./entitySync";
import type { UserRow } from "./cache";

export const createUsers = (scope: string) =>
    createEntitySync<UserRow>(scope, "users");
