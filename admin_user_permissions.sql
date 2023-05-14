create table admin_user_permissions
(
    user_id       int       not null,
    permission_id int       not null,
    created_at    timestamp null,
    updated_at    timestamp null
)
    collate = utf8mb4_unicode_ci;

create index admin_user_permissions_user_id_permission_id_index
    on admin_user_permissions (user_id, permission_id);

