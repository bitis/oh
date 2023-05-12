create table t66y_urls
(
    id         bigint unsigned auto_increment
        primary key,
    result     json      not null,
    created_at timestamp null,
    updated_at timestamp null
)
    collate = utf8mb4_unicode_ci;

