CREATE TABLESPACE restaurante_ts_1
LOCATION '/var/lib/postgresql/tablespaces/restaurante_ts_1';

CREATE TABLESPACE restaurante_ts_2
LOCATION '/var/lib/postgresql/tablespaces/restaurante_ts_2';

CREATE TABLE IF NOT EXISTS auditoria_ordenes (
    id_auditoria BIGSERIAL,
    id_orden INT,
    pedido_id INT,
    estado_anterior INT,
    estado_nuevo INT,
    accion TEXT NOT NULL,
    fecha_evento TIMESTAMP NOT NULL DEFAULT NOW(),
    usuario_bd TEXT DEFAULT CURRENT_USER,
    PRIMARY KEY (id_auditoria, fecha_evento)
) PARTITION BY RANGE (fecha_evento);

CREATE TABLE IF NOT EXISTS auditoria_ordenes_2026_01_06
PARTITION OF auditoria_ordenes
FOR VALUES FROM ('2026-01-01') TO ('2026-07-01')
TABLESPACE restaurante_ts_1;

CREATE TABLE IF NOT EXISTS auditoria_ordenes_2026_07_12
PARTITION OF auditoria_ordenes
FOR VALUES FROM ('2026-07-01') TO ('2027-01-01')
TABLESPACE restaurante_ts_2;

CREATE TABLE IF NOT EXISTS auditoria_ordenes_default
PARTITION OF auditoria_ordenes
DEFAULT
TABLESPACE restaurante_ts_2;

CREATE OR REPLACE FUNCTION fn_auditar_ordenes()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        INSERT INTO auditoria_ordenes (
            id_orden, pedido_id, estado_anterior, estado_nuevo, accion
        )
        VALUES (
            NEW.id_orden, NEW.pedido_id, NULL, NEW.estado, 'INSERT'
        );
        RETURN NEW;
    END IF;

    IF TG_OP = 'UPDATE' THEN
        IF OLD.estado IS DISTINCT FROM NEW.estado THEN
            INSERT INTO auditoria_ordenes (
                id_orden, pedido_id, estado_anterior, estado_nuevo, accion
            )
            VALUES (
                NEW.id_orden, NEW.pedido_id, OLD.estado, NEW.estado, 'UPDATE_ESTADO'
            );
        END IF;
        RETURN NEW;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_auditar_ordenes ON ordenes;

CREATE TRIGGER trg_auditar_ordenes
AFTER INSERT OR UPDATE OF estado
ON ordenes
FOR EACH ROW
EXECUTE FUNCTION fn_auditar_ordenes();
