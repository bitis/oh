create table admin_operation_log
(
    id         int unsigned auto_increment
        primary key,
    user_id    int          not null,
    path       varchar(255) not null,
    method     varchar(10)  not null,
    ip         varchar(255) not null,
    input      text         not null,
    created_at timestamp    null,
    updated_at timestamp    null
)
    collate = utf8mb4_unicode_ci;

create index admin_operation_log_user_id_index
    on admin_operation_log (user_id);

