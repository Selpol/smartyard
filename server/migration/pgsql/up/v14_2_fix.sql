UPDATE core_vars SET editable = false WHERE var_name IN ('database.version');

UPDATE core_vars SET editable = true WHERE var_name IN ('intercom.clean', 'intercom.ntp', 'intercom.is.audio');