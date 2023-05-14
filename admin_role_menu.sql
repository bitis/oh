create table admin_role_menu
(
    role_id    int       not null,
    menu_id    int       not null,
    created_at timestamp null,
    updated_at timestamp null
)
    collate = utf8mb4_unicode_ci;

create index admin_role_menu_role_id_menu_id_index
    on admin_role_menu (role_id, menu_id);

