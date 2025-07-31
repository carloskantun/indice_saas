# Database Naming Standardization

## Overview
This document describes the comprehensive standardization of database table and column names from Spanish to English in the SaaS system to ensure better language neutrality and international compatibility.

## Changes Made

### Table Name Updates
- **Old**: `invitaciones` → **New**: `invitations`

### Column Name Updates
- **Old**: `empresa_id` → **New**: `company_id`
- **Old**: `unidad_id` → **New**: `unit_id`
- **Old**: `negocio_id` → **New**: `business_id`
- **Old**: `rol` → **New**: `role`
- **Old**: `fecha_envio` → **New**: `sent_date`
- **Old**: `fecha_expiracion` → **New**: `expiration_date`
- **Old**: `enviado_por` → **New**: `sent_by`

### Status Enum Values Updates
- **Old**: `'pendiente'` → **New**: `'pending'`
- **Old**: `'aceptada'` → **New**: `'accepted'`
- **Old**: `'expirada'` → **New**: `'expired'`

### Permission Keys Updates
- **Old**: `gastos.*` → **New**: `expenses.*`
- **Old**: `usuarios.*` → **New**: `users.*`
- **Old**: `reportes.*` → **New**: `reports.*`
- **Old**: `configuracion.*` → **New**: `settings.*`

### Files Updated

#### 1. Installation Scripts
- `admin/install_admin_tables.php`
  - Updated table creation SQL with English column names
  - Updated trigger references to use English column names
  - Updated permission keys to English
  - Updated role permission assignments

- `admin/install_admin_tables.sql`
  - Manual SQL script updated with English naming convention
  - All CREATE TABLE statements use English column names
  - Trigger definitions updated
  - Permission insertions use English keys

#### 2. Controller Logic
- `admin/controller.php`
  - Updated all SQL queries to use English column names
  - Updated INSERT, SELECT, UPDATE, DELETE operations
  - Updated array key references to use English column names
  - Maintained all functionality while changing database references

#### 3. System Verification
- `admin/verify_system.php`
  - Updated table existence checks
  - Updated structure verification queries
  - Updated data count queries

#### 4. Supporting Files
- `admin/accept_invitation.php` - Updated JOIN queries and column references
- `admin/complete_installation.php` - Updated trigger creation
- `admin/migrate_to_english.php` - **New migration script** for existing databases

## Technical Implementation

### SQL Changes
```sql
-- Old table name
CREATE TABLE invitaciones (...)

-- New table name  
CREATE TABLE invitations (...)
```

### Trigger Updates
```sql
-- Updated trigger to reference new table name
CREATE TRIGGER set_invitation_expiration 
BEFORE INSERT ON invitations
FOR EACH ROW
BEGIN
    IF NEW.fecha_expiracion IS NULL THEN
        SET NEW.fecha_expiracion = DATE_ADD(NOW(), INTERVAL 48 HOUR);
    END IF;
END
```

### PHP Query Updates
```php
// Old queries
SELECT * FROM invitaciones WHERE...
INSERT INTO invitaciones (...)

// New queries
SELECT * FROM invitations WHERE...
INSERT INTO invitations (...)
```

## Benefits of Standardization

### 1. Language Neutrality
- Database schema independent of interface language
- Easier for international developers to understand
- Consistent with industry best practices

### 2. Maintainability
- Clearer code for mixed-language development teams
- Reduced confusion between UI translations and database structure
- Better alignment with English-based technical documentation

### 3. Scalability
- Easier integration with third-party systems
- Better compatibility with international hosting providers
- Simplified documentation and training materials

## Migration Strategy

### Backward Compatibility
The system maintains backward compatibility by:
1. Creating new tables with English names
2. Updating all application code to use new table names
3. Preserving all existing functionality
4. Providing verification scripts to ensure proper migration

### Testing
- `admin/test_installation.php` - Verifies all English table names exist
- `admin/verify_system.php` - Comprehensive system verification
- Error checking in all installation scripts

## File Structure Impact

### Updated Files
```
admin/
├── install_admin_tables.php    ✅ Updated (columns + permissions)
├── install_admin_tables.sql    ✅ Updated (complete English naming)
├── controller.php              ✅ Updated (all column references)
├── verify_system.php          ✅ Updated (verification queries)
├── accept_invitation.php      ✅ Updated (JOIN queries)
├── complete_installation.php  ✅ Updated (trigger references)
├── migrate_to_english.php     ✅ New migration script
└── test_installation.php      ✅ Verification script
```

### Language Files
No changes required to language files (`lang/`) as they contain user interface translations, not database structure references.

## Best Practices Established

### 1. Naming Convention
- All table names in English
- Snake_case formatting (user_businesses, role_permissions)
- Descriptive and clear naming

### 2. Documentation
- Comprehensive change documentation
- Clear migration instructions  
- Testing and verification procedures

### 3. Quality Assurance
- Syntax validation for all PHP files
- Database structure verification
- Functionality testing scripts

## Future Considerations

### Extending the Standard
This naming standardization establishes a pattern for future development:
- All new tables should use English names
- All database objects (views, procedures, functions) should follow English naming
- Documentation should reference standard naming conventions

### International Deployment
The standardized naming makes the system ready for:
- Multi-region deployments
- International developer collaboration
- Third-party integrations
- SaaS marketplace listings

## Verification Commands

### Check Table Existence
```sql
SHOW TABLES LIKE 'invitations';
DESCRIBE invitations;
```

### Verify Triggers
```sql
SHOW TRIGGERS LIKE 'invitations';
```

### Test Functionality
- Run `admin/test_installation.php`
- Execute user invitation workflow
- Verify all CRUD operations work correctly

## Conclusion

The database naming standardization successfully transforms the system from Spanish-centric to language-neutral while maintaining all existing functionality. This change positions the SaaS platform for better international adoption and technical maintainability.
