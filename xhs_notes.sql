create table xhs_notes
(
    id         bigint unsigned auto_increment
        primary key,
    x_id       varchar(255) not null,
    title      varchar(255) null,
    `desc`     text         null,
    isLiked    tinyint(1)   not null,
    type       varchar(255) not null,
    time       varchar(255) not null,
    notified   tinyint(1)   not null,
    created_at varchar(255) not null,
    updated_at varchar(255) not null
)
    collate = utf8mb4_unicode_ci;

