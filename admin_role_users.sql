create table admin_role_users
(
    role_id    int       not null,
    user_id    int       not null,
    created_at timestamp null,
    updated_at timestamp null
)
    collate = utf8mb4_unicode_ci;

create index admin_role_users_role_id_user_id_index
    on admin_role_users (role_id, user_id);

