-- Migration: Change guestbook entries to require approval by default
-- New entries will be pending (approved=0) until an admin approves them

ALTER TABLE entries ALTER COLUMN approved SET DEFAULT 0;
