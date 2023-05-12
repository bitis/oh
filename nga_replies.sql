create table nga_replies
(
    id         bigint unsigned auto_increment
        primary key,
    reply_id   varchar(255)         not null,
    content    text                 not null,
    author     varchar(255)         not null,
    authorid   varchar(255)         not null,
    subject    varchar(255)         not null,
    subject_id varchar(255)         not null,
    postdate   datetime             not null,
    notified   tinyint(1) default 0 not null,
    created_at timestamp            null,
    updated_at timestamp            null
)
    collate = utf8mb4_unicode_ci;

