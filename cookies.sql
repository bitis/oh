create table cookies
(
    id         bigint unsigned auto_increment
        primary key,
    scope      varchar(255) not null,
    content    text         not null,
    created_at timestamp    null,
    updated_at timestamp    null
)
    collate = utf8mb4_unicode_ci;

