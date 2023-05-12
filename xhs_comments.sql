create table xhs_comments
(
    id           bigint unsigned auto_increment
        primary key,
    x_id         varchar(255)         not null,
    parent_id    int                  not null,
    nickname     varchar(255)         not null,
    user_id      varchar(255)         not null,
    isSubComment tinyint(1) default 0 not null,
    content      text                 not null,
    likes        int                  not null,
    isLiked      tinyint(1)           not null,
    time         varchar(255)         not null,
    notified     tinyint(1)           not null,
    created_at   varchar(255)         not null,
    updated_at   varchar(255)         not null,
    xhs_note_id  int        default 0 not null
)
    collate = utf8mb4_unicode_ci;

