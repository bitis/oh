create table admin_roles
(
    id         int unsigned auto_increment
        primary key,
    name       varchar(50) not null,
    slug       varchar(50) not null,
    created_at timestamp   null,
    updated_at timestamp   null,
    constraint admin_roles_name_unique
        unique (name),
    constraint admin_roles_slug_unique
        unique (slug)
)
    collate = utf8mb4_unicode_ci;

