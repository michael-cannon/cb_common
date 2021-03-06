# useful for debugging

DROP TABLE IF EXISTS comprehensive;

CREATE TABLE comprehensive (
xtinyint         TINYINT DEFAULT 0,
xtinyint_u       TINYINT UNSIGNED DEFAULT 0,
xtinyint_z       TINYINT ZEROFILL DEFAULT 0,
xtinyint_u_z     TINYINT UNSIGNED ZEROFILL DEFAULT 0,
xsmallint        SMALLINT DEFAULT 0,
xsmallint_u      SMALLINT UNSIGNED DEFAULT 0,
xsmallint_z      SMALLINT ZEROFILL DEFAULT 0,
xsmallint_u_z    SMALLINT UNSIGNED ZEROFILL DEFAULT 0,
xmediumint       MEDIUMINT DEFAULT 0,
xmediumint_u     MEDIUMINT UNSIGNED DEFAULT 0,
xmediumint_z     MEDIUMINT ZEROFILL DEFAULT 0,
xmediumint_u_z   MEDIUMINT UNSIGNED ZEROFILL DEFAULT 0,
xint     int     NOT NULL DEFAULT 0,
xint_u   int     UNSIGNED DEFAULT 0,
xint_z   int     ZEROFILL DEFAULT 0,
xint_u_z int     UNSIGNED ZEROFILL DEFAULT 0,
xinteger         INTEGER DEFAULT 0,
xinteger_u       INTEGER UNSIGNED DEFAULT 0,
xinteger_z       INTEGER ZEROFILL DEFAULT 0,
xinteger_u_z     INTEGER UNSIGNED ZEROFILL DEFAULT 0,
xbigint          BIGINT DEFAULT 0,
xbigint_u        BIGINT UNSIGNED DEFAULT 0,
xbigint_z        BIGINT ZEROFILL DEFAULT 0,
xbigint_u_z      BIGINT UNSIGNED ZEROFILL DEFAULT 0,
xfloat1          FLOAT(24) DEFAULT 0,
xfloat1_z        FLOAT(24) ZEROFILL DEFAULT 0.00,
xfloat2          FLOAT(10,24) DEFAULT 0,
xfloat2_z        FLOAT(10,24) ZEROFILL DEFAULT 0.00,
xdouble2         DOUBLE(10,24) DEFAULT 0,
xdouble2_z       DOUBLE(10,24) ZEROFILL DEFAULT 0.00,
xreal2           REAL(10,24) DEFAULT 0,
xreal2_z         REAL(10,24) ZEROFILL DEFAULT 0.00,
xdecimal2        DECIMAL(10,24) DEFAULT 0,
xdecimal2_z      DECIMAL(10,24) ZEROFILL DEFAULT 0.00,
xnumeric2        NUMERIC(10,24) DEFAULT 0,
xnumeric2_z      NUMERIC(10,24) ZEROFILL DEFAULT 0.00,
xdate            DATE,
xdatetime        DATETIME,
xtimestamp2      TIMESTAMP(2) ,
xtimestamp4      TIMESTAMP(4) ,
xtimestamp6      TIMESTAMP(6) ,
xtimestamp8      TIMESTAMP(8) ,
xtimestamp10     TIMESTAMP(10),
xtimestamp12     TIMESTAMP(12),
xtimestamp14     TIMESTAMP(14),
xtime            TIME,
xyear            YEAR,
xchar1           CHAR(1),
xchar255         CHAR(255) BINARY,
xbit             BIT,
xbool            BOOL,
xchar            CHAR,
xvarchar1        VARCHAR(1),
xvarchar255      VARCHAR(255),
xtinytext        TINYTEXT,
xblob            BLOB,
xtext            TEXT,
xmediumblob      MEDIUMBLOB,
xmediumtext      MEDIUMTEXT,
xlongblob        LONGBLOB,
xlongtext        LONGTEXT,
xenum            ENUM('enum1','enum2','enum3'),
xset             SET('set0','set1','set2')
);

