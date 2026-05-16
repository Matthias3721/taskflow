-- Migracja dla istniejącej bazy (przed dodaniem status do init.sql)
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'project_status') THEN
        CREATE TYPE project_status AS ENUM ('active', 'on_hold', 'completed');
    END IF;
END $$;

ALTER TABLE projects
    ADD COLUMN IF NOT EXISTS status project_status NOT NULL DEFAULT 'active';
