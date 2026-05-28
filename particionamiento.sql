-- ============================================================
-- particionamiento.sql — Demostración Particionamiento por Años
-- Sistema de Gestión Restaurante "La Chula"
-- Universidad Javeriana | Bases de Datos 2025-1
-- ============================================================
--
-- ANTES DE EJECUTAR (en terminal):
--   mkdir -p /Users/josemadrigal/tablespaces/ts_1
--   mkdir -p /Users/josemadrigal/tablespaces/ts_2
--
-- EJECUTAR:
--   psql -U postgres -d "Restaurante" -f particionamiento.sql
-- ============================================================

-- ============================================================
-- PASO 1: Tablespaces (directorios físicos separados)
-- ============================================================

CREATE TABLESPACE restaurante_ts_1
    LOCATION '/Users/josemadrigal/tablespaces/ts_1';

CREATE TABLESPACE restaurante_ts_2
    LOCATION '/Users/josemadrigal/tablespaces/ts_2';

-- ============================================================
-- PASO 2: Agregar fecha_pedido a la tabla existente pedidos
--         (sin romper la BD compartida)
-- ============================================================

ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS fecha_pedido DATE NOT NULL DEFAULT CURRENT_DATE;

-- ============================================================
-- PASO 3: Tabla padre particionada por año (DEMO)
--         "pedido" (singular) es la tabla particionada.
--         "pedidos" (plural) es la tabla original de la app.
-- ============================================================

DROP TABLE IF EXISTS pedido CASCADE;

CREATE TABLE pedido (
    id_pedido    BIGSERIAL,
    cliente_id   INT,
    mesero_id    INT,
    fecha_pedido DATE NOT NULL DEFAULT CURRENT_DATE,
    PRIMARY KEY  (id_pedido, fecha_pedido)
) PARTITION BY RANGE (fecha_pedido);

-- Partición 2024 → tablespace 1
CREATE TABLE pedido_2024 PARTITION OF pedido
    FOR VALUES FROM ('2024-01-01') TO ('2025-01-01')
    TABLESPACE restaurante_ts_1;

-- Partición 2025 → tablespace 2
CREATE TABLE pedido_2025 PARTITION OF pedido
    FOR VALUES FROM ('2025-01-01') TO ('2026-01-01')
    TABLESPACE restaurante_ts_2;

-- Partición 2026 → tablespace 1
CREATE TABLE pedido_2026 PARTITION OF pedido
    FOR VALUES FROM ('2026-01-01') TO ('2027-01-01')
    TABLESPACE restaurante_ts_1;

-- Partición 2027 → tablespace 2
CREATE TABLE pedido_2027 PARTITION OF pedido
    FOR VALUES FROM ('2027-01-01') TO ('2028-01-01')
    TABLESPACE restaurante_ts_2;

-- Partición por defecto (otros años)
CREATE TABLE pedido_default PARTITION OF pedido DEFAULT
    TABLESPACE restaurante_ts_2;

-- ============================================================
-- PASO 4: Trigger — sincroniza pedidos → pedido (particionada)
--         Cada INSERT en pedidos se replica en la partición
--         correspondiente al año de fecha_pedido
-- ============================================================

CREATE OR REPLACE FUNCTION fn_replicar_pedido()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO pedido (id_pedido, cliente_id, mesero_id, fecha_pedido)
    VALUES (NEW.id_pedido, NEW.cliente_id, NEW.mesero_id, NEW.fecha_pedido)
    ON CONFLICT DO NOTHING;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_replicar_pedido ON pedidos;

CREATE TRIGGER trg_replicar_pedido
AFTER INSERT ON pedidos
FOR EACH ROW EXECUTE FUNCTION fn_replicar_pedido();

-- ============================================================
-- PASO 5: Poblar la tabla particionada con datos históricos
--         (pedidos ya existentes + registros de demostración)
-- ============================================================

-- Copiar pedidos existentes (con fecha_pedido = fecha actual)
INSERT INTO pedido (id_pedido, cliente_id, mesero_id, fecha_pedido)
SELECT id_pedido, cliente_id, mesero_id, fecha_pedido
FROM pedidos
ON CONFLICT DO NOTHING;

-- Insertar registros históricos de demostración
DO $$
DECLARE
    v_cliente INT;
    v_mesero  INT;
BEGIN
    SELECT u.id_usuario INTO v_cliente
    FROM usuarios u
    JOIN actuaciones a ON u.id_usuario = a.usuario_id
    JOIN roles r ON a.rol_id = r.id_rol
    WHERE r.nombre = 'Cliente'
    ORDER BY u.id_usuario LIMIT 1;

    SELECT u.id_usuario INTO v_mesero
    FROM usuarios u
    JOIN actuaciones a ON u.id_usuario = a.usuario_id
    JOIN roles r ON a.rol_id = r.id_rol
    WHERE r.nombre = 'Mesero'
    ORDER BY u.id_usuario LIMIT 1;

    IF v_cliente IS NOT NULL AND v_mesero IS NOT NULL THEN
        INSERT INTO pedido (cliente_id, mesero_id, fecha_pedido) VALUES
            (v_cliente, v_mesero, '2024-03-15'),
            (v_cliente, v_mesero, '2024-08-22'),
            (v_cliente, v_mesero, '2024-12-01'),
            (v_cliente, v_mesero, '2025-02-14'),
            (v_cliente, v_mesero, '2025-06-30'),
            (v_cliente, v_mesero, '2025-11-11'),
            (v_cliente, v_mesero, '2026-01-20'),
            (v_cliente, v_mesero, '2026-05-28');
    END IF;
END $$;

-- ============================================================
-- PASO 6: Auditoria de órdenes particionada por semestre
-- ============================================================

DROP TABLE IF EXISTS auditoria_ordenes CASCADE;
DROP TRIGGER IF EXISTS trg_auditar_ordenes ON ordenes;
DROP FUNCTION IF EXISTS fn_auditar_ordenes();

CREATE TABLE auditoria_ordenes (
    id_auditoria    BIGSERIAL,
    id_orden        INT,
    pedido_id       INT,
    estado_anterior INT,
    estado_nuevo    INT,
    accion          TEXT NOT NULL,
    fecha_evento    TIMESTAMP NOT NULL DEFAULT NOW(),
    usuario_bd      TEXT DEFAULT CURRENT_USER,
    PRIMARY KEY (id_auditoria, fecha_evento)
) PARTITION BY RANGE (fecha_evento);

CREATE TABLE auditoria_ordenes_2026_s1 PARTITION OF auditoria_ordenes
    FOR VALUES FROM ('2026-01-01') TO ('2026-07-01')
    TABLESPACE restaurante_ts_1;

CREATE TABLE auditoria_ordenes_2026_s2 PARTITION OF auditoria_ordenes
    FOR VALUES FROM ('2026-07-01') TO ('2027-01-01')
    TABLESPACE restaurante_ts_2;

CREATE TABLE auditoria_ordenes_default PARTITION OF auditoria_ordenes DEFAULT
    TABLESPACE restaurante_ts_2;

CREATE OR REPLACE FUNCTION fn_auditar_ordenes()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        INSERT INTO auditoria_ordenes (id_orden, pedido_id, estado_anterior, estado_nuevo, accion)
        VALUES (NEW.id_orden, NEW.pedido_id, NULL, NEW.estado, 'INSERT');
        RETURN NEW;
    END IF;
    IF TG_OP = 'UPDATE' THEN
        IF OLD.estado IS DISTINCT FROM NEW.estado THEN
            INSERT INTO auditoria_ordenes (id_orden, pedido_id, estado_anterior, estado_nuevo, accion)
            VALUES (NEW.id_orden, NEW.pedido_id, OLD.estado, NEW.estado, 'UPDATE_ESTADO');
        END IF;
        RETURN NEW;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_auditar_ordenes
AFTER INSERT OR UPDATE OF estado ON ordenes
FOR EACH ROW EXECUTE FUNCTION fn_auditar_ordenes();

-- ============================================================
-- VERIFICACIÓN — Ejecutar estas consultas para la sustentación
-- ============================================================

-- V1: Distribución de pedidos por partición (qué tablespace recibió qué)
SELECT
    tableoid::regclass                AS particion,
    COUNT(*)                          AS total_registros,
    MIN(fecha_pedido)                 AS primer_pedido,
    MAX(fecha_pedido)                 AS ultimo_pedido
FROM pedido
GROUP BY tableoid
ORDER BY particion;

-- V2: Tamaño físico de cada partición de pedidos
SELECT
    tablename                                                          AS particion,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS tamaño_en_disco
FROM pg_tables
WHERE tablename LIKE 'pedido_%'
ORDER BY tablename;

-- V3: Tamaño total de cada tablespace (refleja cambio en carpeta)
SELECT
    spcname                                      AS tablespace,
    pg_size_pretty(pg_tablespace_size(spcname))  AS tamaño_total
FROM pg_tablespace
WHERE spcname LIKE 'restaurante_%'
ORDER BY spcname;

-- V4: Partition pruning — PostgreSQL solo lee la partición 2024
EXPLAIN SELECT * FROM pedido WHERE fecha_pedido BETWEEN '2024-01-01' AND '2024-12-31';

-- V5: Árbol de particiones (tabla padre → hijas)
SELECT
    parent.relname AS tabla_padre,
    child.relname  AS particion
FROM pg_inherits
JOIN pg_class parent ON pg_inherits.inhparent = parent.oid
JOIN pg_class child  ON pg_inherits.inhrelid  = child.oid
WHERE parent.relname = 'pedido'
ORDER BY child.relname;

-- V6: Auditoria — ver eventos registrados por semestre
SELECT
    tableoid::regclass AS particion,
    accion,
    COUNT(*)           AS eventos
FROM auditoria_ordenes
GROUP BY tableoid, accion
ORDER BY particion;
