create table xhs_images
(
    id          bigint unsigned auto_increment
        primary key,
    xhs_note_id int          not null,
    fileId      varchar(255) not null,
    height      int          not null,
    width       int          not null,
    url         varchar(255) not null,
    created_at  varchar(255) not null,
    updated_at  varchar(255) not null
)
    collate = utf8mb4_unicode_ci;

