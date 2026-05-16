-- Migracja: priorytet zadań
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'task_priority') THEN
        CREATE TYPE task_priority AS ENUM ('low', 'medium', 'high');
    END IF;
END $$;

ALTER TABLE tasks
    ADD COLUMN IF NOT EXISTS priority task_priority NOT NULL DEFAULT 'medium';
