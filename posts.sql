create table posts
(
    id         bigint unsigned auto_increment
        primary key,
    title      varchar(255) not null,
    content    text         not null,
    created_at timestamp    null,
    updated_at timestamp    null
)
    collate = utf8mb4_unicode_ci;

