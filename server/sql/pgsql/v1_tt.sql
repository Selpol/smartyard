-- projects
CREATE TABLE tt_projects
(
    project_id serial not null primary key,
    acronym character varying not null,
    project character varying not null,
    workflow text not null,                                                                                             -- workflow name (.php)
    version text not null                                                                                               -- current version (for workflow upgrade)
);
CREATE UNIQUE INDEX tt_projects_acronym on tt_projects(acronym);
CREATE UNIQUE INDEX tt_projects_name on tt_projects(project);

-- issue statuses
CREATE TABLE tt_issue_statuses
(
    status_id serial not null primary key,
    project_id integer not null,
    final integer,
    status character varying not null,
    status_display character varying not null
);
CREATE UNIQUE INDEX tt_issue_stauses_uniq on tt_issue_statuses(project_id, status);

-- issue resolutions
CREATE TABLE tt_issue_resolutions
(
    resolution_id serial not null primary key,
    project_id integer not null,
    workflow integer,                                                                                                   -- managed by workflow, only resolution_display can be edited
    resolution character varying,
    resolution_display character varying not null
);
CREATE UNIQUE INDEX tt_issue_resolutions_uniq on tt_issue_resolutions(project_id, resolution);

-- issues
CREATE TABLE tt_issues
(
    issue_id serial not null primary key,                                                                               -- primary key
    project_id integer,                                                                                                 -- project id
    subject character varying not null,                                                                                 -- subject
    description character varying not null,                                                                             -- description
    author integer,                                                                                                     -- uid
    type_id integer,                                                                                                    -- issue type
    status_id integer,                                                                                                  -- status
    resolution_id integer,                                                                                              -- resolution
    created timestamp not null,                                                                                         -- "YYYY-MM-DD HH:MM:SS.SSS"
    updated timestamp,                                                                                                  -- "YYYY-MM-DD HH:MM:SS.SSS"
    closed timestamp,                                                                                                   -- "YYYY-MM-DD HH:MM:SS.SSS"
    external_id integer,                                                                                                -- link to external id
    external_id_type character varying                                                                                  -- external object description
);
CREATE INDEX tt_issues_subject on tt_issues(subject);
CREATE INDEX tt_issues_author on tt_issues(author);
CREATE INDEX tt_issues_type_id on tt_issues(type_id);
CREATE INDEX tt_issues_status_id on tt_issues(status_id);
CREATE INDEX tt_issues_resolution_id on tt_issues(resolution_id);
CREATE INDEX tt_issues_created on tt_issues(created);
CREATE INDEX tt_issues_updated on tt_issues(updated);
CREATE INDEX tt_issues_closed on tt_issues(closed);
CREATE INDEX tt_issues_external on tt_issues(external_id, external_id_type);

-- assigned(s)
CREATE TABLE tt_issue_assigned
(
    assigned_id serial not null primary key,
    issue_id integer,
    uid integer,
    gid integer
);
CREATE UNIQUE INDEX tt_issue_assigned_uniq on tt_issue_assigned(issue_id, uid, gid);
CREATE INDEX tt_issue_assigned_issue_id on tt_issue_assigned(issue_id);
CREATE INDEX tt_issue_assigned_uid on tt_issue_assigned(uid);
CREATE INDEX tt_issue_assigned_gid on tt_issue_assigned(gid);

-- watchers
CREATE TABLE tt_issue_watchers
(
    watcher_id serial not null primary key,
    issue_id integer,
    uid integer
);
CREATE UNIQUE INDEX tt_issue_watchers_uniq on tt_issue_watchers (issue_id, uid);
CREATE INDEX tt_issue_watchers_issue_id on tt_issue_watchers(issue_id);
CREATE INDEX tt_issue_watchers_uid on tt_issue_watchers(uid);

-- plans
CREATE TABLE tt_issue_plans
(
    plan_id serial not null primary key,
    issue_id integer,
    action character varying,
    planned timestamp,                                                                                                  -- "YYYY-MM-DD HH:MM:SS.SSS"
    uid integer,
    gid integer
);
CREATE UNIQUE INDEX tt_issue_plans_uniq on tt_issue_plans(issue_id, action);
CREATE INDEX tt_issue_plans_issue_id on tt_issue_plans(issue_id);
CREATE INDEX tt_issue_plans_planned on tt_issue_plans(planned);
CREATE INDEX tt_issue_plans_uid on tt_issue_plans(uid);
CREATE INDEX tt_issue_plans_gid on tt_issue_plans(gid);

-- comments
CREATE TABLE tt_issue_comments
(
    comment_id serial not null primary key,
    issue_id integer,                                                                                                   -- issue
    comment character varying,                                                                                          -- comment
    role_id integer,                                                                                                    -- permission level
    created timestamp,                                                                                                  -- "YYYY-MM-DD HH:MM:SS.SSS"
    updated timestamp,                                                                                                  -- "YYYY-MM-DD HH:MM:SS.SSS"
    author integer                                                                                                      -- uid
);
CREATE INDEX tt_issue_comments_issue_id on tt_issue_comments(issue_id);

-- attachments
CREATE TABLE tt_issue_attachments
(
    attachment_id serial not null primary key,
    issue_id integer,                                                                                                   -- issue
    uuid character varying,                                                                                             -- file uuid for attachments backend
    role_id integer,                                                                                                    -- permission level
    created timestamp,                                                                                                  -- "YYYY-MM-DD HH:MM:SS.SSS"
    author integer                                                                                                      -- uid
);
CREATE INDEX tt_issue_attachments_issue_id on tt_issue_attachments(issue_id);

-- checklist
CREATE TABLE tt_issue_checklist
(
    check_id serial not null primary key,
    issue_id integer,
    checkbox character varying,
    checked integer
);
CREATE UNIQUE INDEX tt_issue_checklist_uniq on tt_issue_checklist(issue_id, checkbox);
CREATE INDEX tt_issue_checklist_issue_id on tt_issue_checklist(issue_id);

-- tags
CREATE TABLE tt_issue_tags
(
    tag_id serial not null primary key,
    issue_id integer,
    tag character varying
);
CREATE UNIQUE INDEX tt_issue_tags_uniq on tt_issue_tags (issue_id, tag);
CREATE INDEX tt_issue_tags_issue_id on tt_issue_tags(issue_id);
CREATE INDEX tt_issue_tags_tag on tt_issue_tags(tag);

-- custom fields
CREATE TABLE tt_issue_custom_fields
(
    custom_field_id serial not null primary key,
    project_id integer not null,
    type character varying not null,
    workflow integer,                                                                                                   -- managed by workflow, only field_display can be edited
    field character varying not null,
    field_display character varying not null
);
CREATE UNIQUE INDEX tt_issue_custom_fields_name on tt_issue_custom_fields(project_id, field);

-- custom fields values options
CREATE TABLE tt_issue_custom_fields_options
(
    custom_field_option_id serial not null primary key,
    custom_field_id integer,
    value character varying,
    option character varying not null,
    option_display character varying
);
CREATE UNIQUE INDEX tt_issue_custom_fields_options_uniq on tt_issue_custom_fields_options(custom_field_id, option);

-- custom fields values
CREATE TABLE tt_issue_custom_fields_values
(
    custom_field_value_id serial not null primary key,
    issue_id integer,
    custom_field_id integer,
    value character varying
);
CREATE INDEX tt_issue_custom_fields_values_issue_id on tt_issue_custom_fields_values(issue_id);
CREATE INDEX tt_issue_custom_fields_values_field_id on tt_issue_custom_fields_values(custom_field_id);
CREATE INDEX tt_issue_custom_fields_values_type_value on tt_issue_custom_fields_values(value);

-- projects roles types
CREATE TABLE tt_roles
(
    role_id serial not null primary key,
    name character varying,
    level integer
);
CREATE INDEX tt_roles_level on tt_roles(level);
INSERT INTO tt_roles (level, name) values (1000, 'participant.junior');                                                 -- can view only
INSERT INTO tt_roles (level, name) values (2000, 'participant.middle');                                                 -- can comment, can edit and delete own comments, can attach files and delete own files
INSERT INTO tt_roles (level, name) values (3000, 'participant.senior');                                                 -- can create issues
INSERT INTO tt_roles (level, name) values (4000, 'employee.junior');                                                    -- can change status (by workflow, without final)
INSERT INTO tt_roles (level, name) values (5000, 'employee.middle');                                                    -- can change status (by workflow)
INSERT INTO tt_roles (level, name) values (6000, 'employee.senior');                                                    -- can edit issues
INSERT INTO tt_roles (level, name) values (7000, 'manager.junior');                                                     -- can edit all comments and delete comments, can delete files, can create tag
INSERT INTO tt_roles (level, name) values (8000, 'manager.middle');                                                     -- can delete issues
INSERT INTO tt_roles (level, name) values (9000, 'manager.senior');                                                     -- can create and configure projects

-- project rights
CREATE TABLE tt_projects_roles
(
    project_role_id serial not null primary key,
    project_id integer not null,
    role_id integer not null,
    uid integer,
    gid integer
);
CREATE UNIQUE INDEX tt_projects_roles_uniq on tt_projects_roles (project_id, role_id, uid, gid);
CREATE INDEX tt_projects_roles_project_id on tt_projects_roles(project_id);
CREATE INDEX tt_projects_roles_role_id on tt_projects_roles(role_id);
CREATE INDEX tt_projects_roles_uid on tt_projects_roles(uid);
CREATE INDEX tt_projects_roles_gid on tt_projects_roles(gid);

-- subtasks
CREATE TABLE tt_subtasks
(
    subtask_id serial not null primary key,
    issue_id integer,
    sub_issue_id integer
);
CREATE UNIQUE INDEX tt_subtasks_uniq on tt_subtasks(issue_id, sub_issue_id);
