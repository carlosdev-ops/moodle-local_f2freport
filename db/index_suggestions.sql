-- Performance optimization indexes for face-to-face tables
-- These indexes should significantly improve query performance for the f2freport plugin

-- Index on facetoface_sessions for faster session lookups
-- Covers the main JOIN condition and common filters
CREATE INDEX idx_facetoface_sessions_performance ON mdl_facetoface_sessions (facetoface, id, capacity);

-- Index on facetoface_sessions_dates for date-based filtering
-- Covers date range queries and NULL date checks
CREATE INDEX idx_facetoface_sessions_dates_performance ON mdl_facetoface_sessions_dates (sessionid, timestart, timefinish);

-- Composite index on facetoface_signups for participant counting
-- Covers the most common participant queries
CREATE INDEX idx_facetoface_signups_performance ON mdl_facetoface_signups (sessionid, userid);

-- Index on facetoface_signups_status for status filtering
-- Covers status-based queries and latest status lookups
CREATE INDEX idx_facetoface_signups_status_performance ON mdl_facetoface_signups_status (signupid, statuscode, superceded, id);

-- Index on facetoface_session_data for custom field lookups
-- Covers city/venue/room data retrieval
CREATE INDEX idx_facetoface_session_data_performance ON mdl_facetoface_session_data (sessionid, fieldid, data(50));

-- Index on facetoface_session_field for field alias matching
-- Covers field ID resolution by name
CREATE INDEX idx_facetoface_session_field_performance ON mdl_facetoface_session_field (shortname(20), name(50));

-- Index on course table for course filtering
-- Covers course name searches and visibility checks
CREATE INDEX idx_course_f2freport_performance ON mdl_course (visible, fullname(100));

-- Additional index on facetoface table for course relationship
CREATE INDEX idx_facetoface_course_performance ON mdl_facetoface (course, id);

-- NOTES:
-- 1. Apply these indexes during low-traffic periods
-- 2. Monitor index usage with EXPLAIN ANALYZE
-- 3. Drop unused indexes to save space
-- 4. Adjust prefix lengths based on your data
-- 5. These are suggestions - test on your specific dataset first