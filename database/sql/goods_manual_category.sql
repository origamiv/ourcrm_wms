CREATE OR REPLACE FUNCTION wms.recalculate_goods_hierarchy()
RETURNS void LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE reached bigint; total bigint;
BEGIN
    WITH RECURSIVE tree AS (
        SELECT g.id, 0 AS depth FROM goods.goods g
        WHERE g.deleted_at IS NULL AND NOT EXISTS (
            SELECT 1 FROM goods.goods p WHERE p.id = g.parent_id AND p.deleted_at IS NULL
        )
        UNION ALL
        SELECT g.id, t.depth + 1 FROM goods.goods g JOIN tree t ON g.parent_id = t.id WHERE g.deleted_at IS NULL
    ) SELECT count(*) INTO reached FROM tree;
    SELECT count(*) INTO total FROM goods.goods WHERE deleted_at IS NULL;
    IF reached <> total THEN
        RAISE EXCEPTION 'Родительская связь товаров не может образовывать цикл' USING ERRCODE = '23514';
    END IF;
    WITH RECURSIVE tree AS (
        SELECT g.id, 0 AS depth FROM goods.goods g
        WHERE g.deleted_at IS NULL AND NOT EXISTS (
            SELECT 1 FROM goods.goods p WHERE p.id = g.parent_id AND p.deleted_at IS NULL
        )
        UNION ALL
        SELECT g.id, t.depth + 1 FROM goods.goods g JOIN tree t ON g.parent_id = t.id WHERE g.deleted_at IS NULL
    ), calculated AS (
        SELECT t.id, t.depth, CASE WHEN EXISTS (
            SELECT 1 FROM goods.goods child WHERE child.parent_id = t.id AND child.deleted_at IS NULL
        ) THEN true ELSE false END AS children, g.category_manual FROM tree t JOIN goods.goods g ON g.id = t.id
    )
    UPDATE goods.goods g SET level = c.depth, is_category = CASE WHEN c.children OR c.category_manual THEN 1 ELSE 2 END, has_children = c.children, updated_at = CURRENT_TIMESTAMP
    FROM calculated c WHERE g.id = c.id AND (g.level IS DISTINCT FROM c.depth OR g.is_category IS DISTINCT FROM CASE WHEN c.children OR c.category_manual THEN 1 ELSE 2 END OR g.has_children IS DISTINCT FROM c.children);
END;
$$;
REVOKE ALL ON FUNCTION wms.recalculate_goods_hierarchy() FROM PUBLIC;


CREATE OR REPLACE FUNCTION wms.capture_manual_good_category()
RETURNS trigger LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
BEGIN
    IF pg_trigger_depth() > 1 THEN RETURN NEW; END IF;
    IF TG_OP = 'INSERT' OR NEW.is_category IS DISTINCT FROM OLD.is_category THEN
        NEW.category_manual := coalesce(NEW.is_category = 1, false);
    END IF;
    RETURN NEW;
END;
$$;
CREATE TRIGGER wms_goods_manual_category BEFORE INSERT OR UPDATE OF is_category ON goods.goods
FOR EACH ROW EXECUTE FUNCTION wms.capture_manual_good_category();
DROP TRIGGER wms_goods_hierarchy ON goods.goods;
CREATE TRIGGER wms_goods_hierarchy AFTER INSERT OR DELETE OR UPDATE OF parent_id, deleted_at, level, is_category, category_manual, has_children ON goods.goods
FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_goods_hierarchy();
