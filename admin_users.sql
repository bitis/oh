create table admin_users
(
    id             int unsigned auto_increment
        primary key,
    username       varchar(190) not null,
    password       varchar(60)  not null,
    name           varchar(255) not null,
    avatar         varchar(255) null,
    remember_token varchar(100) null,
    created_at     timestamp    null,
    updated_at     timestamp    null,
    constraint admin_users_username_unique
        unique (username)
)
    collate = utf8mb4_unicode_ci;

