Mysql Triggers & Procedure
===

## Triggers

### Audit Triggers

```sql
CREATE TRIGGER book_insert_audit_trigger
AFTER INSERT ON book FOR EACH ROW
BEGIN
    INSERT INTO book_audit_log (
        book_id,
        old_row_data,
        new_row_data,
        dml_type,
        dml_timestamp,
        dml_created_by,
        trx_timestamp
    )
    VALUES(
        NEW.id,
        null,
        JSON_OBJECT(
            "title", NEW.title,
            "author", NEW.author,
            "price_in_cents", NEW.price_in_cents,
            "publisher", NEW.publisher
        ),
        'INSERT',
        CURRENT_TIMESTAMP,
        @logged_user,
        @transaction_timestamp
    );
END
```

### User Add Trigger

```sql
USE test;
DROP TRIGGER IF EXISTS user_register_trigger;

DELIMITER $$
CREATE TRIGGER user_register_trigger
AFTER INSERT 
ON user FOR EACH ROW
BEGIN
    INSERT INTO audit (
        ref,
        action,
        data,
        status
    )
    VALUES(
        CONCAT("user:", NEW.id),
        "type:add",
        CONCAT("email:", NEW.email, "|", NEW.token),
        NEW.status
    );
END$$
```

### User Update Trigger

```sql
USE test;
DROP TRIGGER IF EXISTS user_update_trigger;

DELIMITER $$
CREATE TRIGGER user_update_trigger AFTER UPDATE ON user FOR EACH ROW
BEGIN
    INSERT INTO audit (
        ref,
        action,
        data,
        status
    )
    VALUES(
        CONCAT("user:", NEW.id),
        "type:update",
        CONCAT("email:", NEW.email, "|", NEW.token),
        NEW.status
    );
END$$
DELIMITER ;
```

## Procedures

## Notify Procedure

```sql
USE test;
DROP PROCEDURE IF EXISTS notify;

DELIMITER $$
CREATE PROCEDURE notify (_ref TEXT, _action TEXT, _data TEXT , _who TEXT, _status VARCHAR(100))
BEGIN
    INSERT INTO notify (
        ref,
        action,
        data,
        who,
        status
    )
    VALUES(
        _ref,
        _action,
        _data,
        _who,
        _status
    );
END$$
```