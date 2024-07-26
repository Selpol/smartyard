CREATE INDEX audit_user_id on audit (user_id);
CREATE INDEX audit_auditable_id on audit (auditable_id);
CREATE INDEX audit_auditable_type on audit (auditable_type);
CREATE INDEX audit_event_ip on audit (event_ip);
CREATE INDEX audit_event_type on audit (event_type);
CREATE INDEX audit_event_code on audit (event_code);

CREATE INDEX flat_block_service on flat_block (service);
CREATE INDEX subscriber_block_service on subscriber_block (service);

CREATE INDEX task_class on task (class);