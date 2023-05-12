create table admin_menu
(
    id         int unsigned auto_increment
        primary key,
    parent_id  int default 0 not null,
    `order`    int default 0 not null,
    title      varchar(50)   not null,
    icon       varchar(50)   not null,
    uri        varchar(255)  null,
    permission varchar(255)  null,
    created_at timestamp     null,
    updated_at timestamp     null
)
    collate = utf8mb4_unicode_ci;

