create table admin_role_permissions
(
    role_id       int       not null,
    permission_id int       not null,
    created_at    timestamp null,
    updated_at    timestamp null
)
    collate = utf8mb4_unicode_ci;

create index admin_role_permissions_role_id_permission_id_index
    on admin_role_permissions (role_id, permission_id);

