-- TaskFlow – wymagania bazodanowe (widoki, funkcja, triggery)
-- Idempotentna migracja – można uruchamiać wielokrotnie

-- 1. Funkcja calculate_project_progress
CREATE OR REPLACE FUNCTION calculate_project_progress(p_project_id INT)
RETURNS NUMERIC
LANGUAGE sql
STABLE
AS $$
    SELECT CASE
        WHEN COUNT(*) = 0 THEN 0::NUMERIC
        ELSE ROUND(
            (COUNT(*) FILTER (WHERE status = 'done')::NUMERIC / COUNT(*)::NUMERIC) * 100,
            2
        )
    END
    FROM tasks
    WHERE project_id = p_project_id;
$$;

-- 2. Widok view_project_progress
DROP VIEW IF EXISTS view_project_progress;

CREATE VIEW view_project_progress AS
SELECT
    p.id AS project_id,
    p.name AS project_name,
    u.name AS owner_name,
    COUNT(t.id) AS total_tasks,
    COUNT(t.id) FILTER (WHERE t.status = 'done') AS done_tasks,
    CASE
        WHEN COUNT(t.id) = 0 THEN 0::NUMERIC
        ELSE ROUND(
            (COUNT(t.id) FILTER (WHERE t.status = 'done')::NUMERIC / COUNT(t.id)::NUMERIC) * 100,
            2
        )
    END AS progress_percent
FROM projects p
INNER JOIN users u ON u.id = p.owner_id
LEFT JOIN tasks t ON t.project_id = p.id
GROUP BY p.id, p.name, u.name;

-- 3. Widok view_user_task_summary
DROP VIEW IF EXISTS view_user_task_summary;

CREATE VIEW view_user_task_summary AS
SELECT
    u.id AS user_id,
    u.name AS user_name,
    COUNT(t.id) AS total_tasks,
    COUNT(t.id) FILTER (WHERE t.status = 'todo') AS todo_tasks,
    COUNT(t.id) FILTER (WHERE t.status = 'in_progress') AS in_progress_tasks,
    COUNT(t.id) FILTER (WHERE t.status = 'done') AS done_tasks
FROM users u
LEFT JOIN tasks t ON t.assignee_id = u.id
GROUP BY u.id, u.name;

-- 4. Trigger: updated_at
CREATE OR REPLACE FUNCTION trg_set_tasks_updated_at()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    NEW.updated_at := CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS update_tasks_updated_at ON tasks;

CREATE TRIGGER update_tasks_updated_at
    BEFORE UPDATE ON tasks
    FOR EACH ROW
    EXECUTE FUNCTION trg_set_tasks_updated_at();

-- 5. Trigger: log_task_status_change
CREATE OR REPLACE FUNCTION trg_log_task_status_change()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    IF OLD.status IS DISTINCT FROM NEW.status THEN
        INSERT INTO task_status_history (task_id, old_status, new_status, changed_by, changed_at)
        VALUES (NEW.id, OLD.status, NEW.status, NEW.assignee_id, CURRENT_TIMESTAMP);
    END IF;
    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS log_task_status_change ON tasks;

CREATE TRIGGER log_task_status_change
    AFTER UPDATE ON tasks
    FOR EACH ROW
    EXECUTE FUNCTION trg_log_task_status_change();
