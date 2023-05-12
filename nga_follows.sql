create table nga_follows
(
    id         bigint unsigned auto_increment
        primary key,
    name       varchar(255) not null,
    uid        varchar(255) not null,
    created_at timestamp    null,
    updated_at timestamp    null
)
    collate = utf8mb4_unicode_ci;

