# Actualización de Paleta de Colores - CRM Internacional

## Resumen de Cambios

Se ha implementado una paleta de colores unificada en todo el proyecto CRM Internacional, reemplazando los estilos inline por clases CSS organizadas y consistentes.

## Paleta de Colores Implementada

### Paleta Principal
- **Azul Principal (#005A9C)**: Botones de acción, encabezados, elementos clave
- **Azul Secundario (#1E88E5)**: Resaltar información importante, iconos destacados
- **Gris Oscuro (#333333)**: Texto principal, alto contraste
- **Gris Claro (#F5F5F5)**: Fondos de interfaz, separación sutil

### Paleta para Estatus y Alertas
- **Verde (#4CAF50)**: Éxito - acciones completadas, tareas terminadas
- **Rojo (#D32F2F)**: Error - errores, alertas críticas, acciones de precaución
- **Amarillo (#FFC107)**: Advertencia - advertencias, estados que requieren atención

## Archivos Modificados

### 1. Archivos CSS Nuevos
- `css/variables.css` - Variables CSS globales
- `css/role-specific.css` - Clases específicas por rol
- `css/README_COLOR_UPDATE.md` - Esta documentación

### 2. Archivos CSS Actualizados
- `css/dashboard.css` - Actualizado para usar variables CSS
- `css/asesor.css` - Actualizado con nueva paleta
- `css/coordinador.css` - Actualizado con nueva paleta
- `css/tickets.css` - Actualizado con nueva paleta
- `css/login.css` - Actualizado con nueva paleta

### 3. Archivos PHP Actualizados
- `views/asesor_dashboard.php` - Estilos inline reemplazados por clases
- `views/cliente_dashboard.php` - Estilos inline reemplazados por clases
- `views/coordinador_dashboard.php` - Estilos inline reemplazados por clases

## Clases CSS Creadas

### Clases por Rol
- `.logo-asesor`, `.logo-cliente`, `.logo-coordinador`
- `.avatar-asesor`, `.avatar-cliente`
- `.text-asesor`, `.subtitle-asesor`
- `.btn-asesor`, `.btn-refresh`
- `.header-asesor`, `.title-asesor`

### Clases para Estados
- `.status-abierto`, `.status-procesando`, `.status-terminado`
- `.estado-gestionado`, `.estado-nuevo`
- `.ticket-status-abierto`, `.ticket-status-procesando`, `.ticket-status-terminado`

### Clases para Iconos
- `.icon-total`, `.icon-nuevos`, `.icon-gestionados`, `.icon-llamadas`
- `.icon-tickets-abiertos`, `.icon-tickets-procesando`, `.icon-tickets-resueltos`

### Clases para Botones
- `.btn-ticket`, `.btn-view`, `.btn-refresh`
- `.btn-edit`, `.btn-delete`, `.btn-toggle`

### Clases para Mensajes
- `.message-no-data` - Para mensajes de "no hay datos"

## Beneficios de la Implementación

1. **Consistencia Visual**: Toda la interfaz usa la misma paleta de colores
2. **Mantenibilidad**: Cambios de color centralizados en variables CSS
3. **Rendimiento**: Menos estilos inline, mejor caché del navegador
4. **Escalabilidad**: Fácil agregar nuevos roles o temas
5. **Legibilidad**: Código más limpio y organizado
6. **Accesibilidad**: Mejor contraste y legibilidad

## Uso de Variables CSS

Todas las variables están definidas en `css/variables.css` y se pueden usar en cualquier archivo CSS:

```css
/* Ejemplo de uso */
.mi-elemento {
    background: var(--gradient-primary);
    color: var(--white);
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-soft);
}
```

## Próximos Pasos

1. **Verificar todas las vistas** para asegurar consistencia
2. **Probar en diferentes navegadores** para verificar compatibilidad
3. **Actualizar documentación** del proyecto
4. **Capacitar al equipo** en el uso de las nuevas clases CSS

## Notas Importantes

- Los estilos inline han sido reemplazados por clases CSS apropiadas
- Se mantiene la funcionalidad existente
- Los gradientes y efectos visuales se conservan
- El diseño responsive se mantiene intacto
- Se mejoró la accesibilidad con mejor contraste de colores

## Archivos que Requieren Revisión

- `views/admin_dashboard.php` - Verificar si necesita actualizaciones similares
- `views/settings.php` - Revisar estilos inline restantes
- `views/coordinador_gestion.php` - Verificar consistencia de colores
- Cualquier otro archivo PHP que contenga estilos inline

## Contacto

Para dudas o problemas con la implementación, revisar este documento o consultar con el equipo de desarrollo.
