DROP TABLE IF EXISTS Times;
CREATE TABLE Times(
    id         INT4 PRIMARY KEY AUTO_INCREMENT,

    start_time DATETIME,
    end_time DATETIME
);