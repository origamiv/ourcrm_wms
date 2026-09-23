export interface TreeGood {
    id: string;
    parent_id?: string | number | null;
    name?: string | null;
    is_category?: number | null;
}
export interface GoodNode<T extends TreeGood> {
    row: T;
    children: GoodNode<T>[];
    depth: number;
}
/** Build only from visible records; unavailable parents never hide their children. */
export function goodsTree<T extends TreeGood>(
    rows: T[],
    matches: Set<string>,
    descending = false,
): { roots: GoodNode<T>[]; nodes: Map<string, GoodNode<T>> } {
    const nodes = new Map(
        rows.map((row) => [
            String(row.id),
            { row, children: [], depth: 0 } as GoodNode<T>,
        ]),
    );
    const parents = new Map<string, string>();
    for (const row of rows) {
        const parent = String(row.parent_id ?? "");
        if (parent !== String(row.id) && nodes.has(parent))
            parents.set(String(row.id), parent);
    }
    // Old offline data may contain a cycle. Keep its records reachable.
    for (const row of rows) {
        const rowId = String(row.id);
        const seen = new Set<string>([rowId]);
        let parent = parents.get(rowId);
        while (parent) {
            if (seen.has(parent)) {
                parents.delete(rowId);
                break;
            }
            seen.add(parent);
            parent = parents.get(parent);
        }
    }
    const keep = new Set([...matches].map(String));
    for (const rawId of matches) {
        const id = String(rawId);
        let parent = parents.get(id);
        while (parent && !keep.has(parent)) {
            keep.add(parent);
            parent = parents.get(parent);
        }
    }
    const roots: GoodNode<T>[] = [];
    for (const [id, node] of nodes) {
        if (!keep.has(String(id))) {
            nodes.delete(id);
            continue;
        }
        const parent = parents.get(id);
        if (parent && nodes.has(parent)) nodes.get(parent)!.children.push(node);
        else roots.push(node);
    }
    const sort = (a: GoodNode<T>, b: GoodNode<T>) =>
        Number(!!b.children.length || b.row.is_category === 1) -
            Number(!!a.children.length || a.row.is_category === 1) ||
        (a.row.name ?? "").localeCompare(b.row.name ?? "", "ru") *
            (descending ? -1 : 1) ||
        String(a.row.id).localeCompare(String(b.row.id), "en", {
            numeric: true,
        });
    roots.sort(sort);
    const stack = roots.map((node) => ({ node, depth: 0 }));
    while (stack.length) {
        const { node, depth } = stack.pop()!;
        node.depth = depth;
        node.children.sort(sort);
        stack.push(
            ...node.children.map((child) => ({
                node: child,
                depth: depth + 1,
            })),
        );
    }
    return { roots, nodes };
}
export function flattenGoods<T extends TreeGood>(
    roots: GoodNode<T>[],
    expanded: Set<string>,
    forceOpen = false,
): T[] {
    const result: T[] = [];
    const stack = [...roots].reverse();
    while (stack.length) {
        const node = stack.pop()!;
        result.push(node.row);
        if (forceOpen || expanded.has(String(node.row.id)))
            stack.push(...[...node.children].reverse());
    }
    return result;
}
