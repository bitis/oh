create table admin_permissions
(
    id          int unsigned auto_increment
        primary key,
    name        varchar(50)  not null,
    slug        varchar(50)  not null,
    http_method varchar(255) null,
    http_path   text         null,
    created_at  timestamp    null,
    updated_at  timestamp    null,
    constraint admin_permissions_name_unique
        unique (name),
    constraint admin_permissions_slug_unique
        unique (slug)
)
    collate = utf8mb4_unicode_ci;

