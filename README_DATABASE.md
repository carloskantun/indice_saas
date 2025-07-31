## ✅ `README_DATABASE.md` (estructura SQL base)

```markdown
# 📚 README_DATABASE.md

## ENTIDADES PRINCIPALES

### usuarios
- id
- nombre
- email
- password
- activo
- fecha_creacion

### empresas
- id
- nombre
- slug
- creada_por (usuario_id)

### unidades
- id
- nombre
- empresa_id

### negocios
- id
- nombre
- unidad_id

### usuarios_x_empresa
- id
- usuario_id
- empresa_id
- rol

### usuarios_x_unidad
- id
- usuario_id
- unidad_id
- rol

### usuarios_x_negocio
- id
- usuario_id
- negocio_id
- rol

---

## MÓDULOS Y PERMISOS

### permisos
- id
- clave (ej. gastos.ver)
- descripcion

### roles_permisos
- id
- rol (ej. superadmin)
- permiso_id

---

## FORMULARIOS Y ARCHIVOS

### gastos
- id
- concepto
- monto
- unidad_id
- negocio_id
- fecha
- estatus
- usuario_id
- adjuntos (json)

---

## 📝 CONVENCIONES

- Todas las claves foráneas en inglés.
- Los nombres de las columnas y tablas en inglés.
- Los comentarios del sistema estarán traducidos para uso en español (vía lang).

CREATE TABLE invitaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255),
  empresa_id INT,
  unidad_id INT DEFAULT NULL,
  negocio_id INT DEFAULT NULL,
  rol VARCHAR(50),
  token VARCHAR(64),
  status ENUM('pendiente', 'aceptada', 'expirada') DEFAULT 'pendiente',
  fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
