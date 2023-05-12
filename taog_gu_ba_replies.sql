create table taog_gu_ba_replies
(
    id         bigint unsigned auto_increment
        primary key,
    reply_id   varchar(255)         not null,
    user_name  varchar(255)         not null,
    date       varchar(255)         not null,
    `from`     varchar(255)         not null,
    from_url   varchar(255)         not null,
    content    varchar(255)         not null,
    url        varchar(255)         not null,
    images     varchar(255)         not null,
    original   text                 not null,
    notified   tinyint(1) default 0 not null,
    created_at timestamp            null,
    updated_at timestamp            null
)
    collate = utf8mb4_unicode_ci;

