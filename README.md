# Sistema de Gestión Restaurante  
**Bases de Datos 2025-1**  
Pontificia Universidad Javeriana  
Angy Bautista

---

## 📌 Descripción del Proyecto

Este proyecto corresponde a una **propuesta de interfaz en HTML5** para el sistema de gestión de un restaurante, desarrollada según la especificación de requerimientos suministrada.

La interfaz contempla los diferentes roles del sistema:

- Administrador  
- Maitre  
- Mesero  
- Cocinero  
- Cliente  

El sistema fue implementado utilizando la plantilla base proporcionada por el docente, integrando PHP para manejar la navegación dinámica entre los distintos menús de usuario.

---

## 🏗️ Estructura del Proyecto

El proyecto utiliza una arquitectura basada en plantilla:

- `esqueleto.html` define la estructura general del sitio (header, main, footer).
- `index.php` genera dinámicamente el contenido según el rol seleccionado.
- Se usa `sprintf()` para insertar el contenido en el marcador `%s` definido en el esqueleto.

## 👥 Funcionalidades por Rol

### 🔹 Menú Principal
Permite seleccionar el rol del usuario:
- Administrador
- Maitre
- Mesero
- Cocinero
- Cliente

---

### 🔹 Administrador
- Gestión de Mesas (Agregar / Modificar / Eliminar)
- Gestión del Menú
- Gestión de Empleados
- Reporte de Reservaciones
- Reporte de Pedidos Más Solicitados
- Reporte de Ventas Totales

---

### 🔹 Maitre
- Registrar Reservación
- Asignar Mesa según Disponibilidad
- Verificar que no se crucen Reservaciones
- Validar Cupo Total del Restaurante
- Consultar Reservaciones Próximas

---

### 🔹 Mesero
- Registrar Pedido por Mesa
- Agregar múltiples Ítems al Pedido
- Actualizar Estado del Pedido
- Registrar Entrega
- Consultar Pedidos Listos para Entregar

---

### 🔹 Cocinero
- Visualizar Pedidos en Preparación
- Ver Pedidos Ordenados por Tiempo de Preparación
- Actualizar Estado del Pedido a "Listo"

---

### 🔹 Cliente
- Solicitar Reservación
- Consultar Historial de Reservaciones
- Consultar Historial de Pedidos

---

## 🛠️ Tecnologías Utilizadas

- HTML5
- PHP
- CSS
- Apache
- XAMPP (entorno local en Windows)

---

## 🚀 Instalación en Windows con XAMPP

### 1. Instalar XAMPP
Descargar desde:
https://www.apachefriends.org

### 2. Copiar el Proyecto
Copiar la carpeta `restaurante` dentro de:
C:\xampp\htdocs

Debe quedar: C:\xampp\htdocs\restaurante\index.php

### 3. Iniciar Apache
Abrir XAMPP Control Panel y presionar:

Start en Apache

### 4. Acceder desde el Navegador
http://localhost/Restaurante-Datos

