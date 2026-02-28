# 🚀 Quick Start - Sistema de Registro (3 Pasos)

## Empezar a Probar en 2 Minutos

### 1️⃣ Verifica que XAMPP esté corriendo
```bash
# En Windows, abre XAMPP Control Panel
# Asegúrate que Apache y MySQL estén en verde
```

### 2️⃣ Accede al registro
```
Abre en el navegador:
http://localhost/AppEgresados/views/auth/register-step-1.php
```

### 3️⃣ Completa el flujo rápido

**PASO 1 (Rol):**
- Selecciona cualquier rol (recomendado: Egresado)
- Click **Continuar**

**PASO 2 (Verificación):**
- **Egresado:** 
  - Matrícula: `2019010123`
  - CURP: `PEJI900112HDFRNN01`
- **Docente:**
  - ID: `PROF1234`
- **TI:**
  - ID: `12345`
- Click **Continuar**

**PASO 3 (Usuario):**
- Nombre: `Juan`
- Apellidos: `Pérez`
- Click **Generar Credenciales**
- Click **Ir al Login**

**LOGIN:**
- Email: (el mostrado en credenciales)
- Contraseña: (la mostrada en credenciales)
- Click **Entrar**

---

## 📍 Archivos Principales

```
├── app/controllers/
│   └── RegisterController.php          ← Lógica principal
├── app/models/
│   └── Usuario.php                      ← Métodos BD
└── views/auth/
    ├── register-step-1.php             ← Paso 1: Rol
    ├── register-step-2.php             ← Paso 2: Verificación
    ├── register-step-3.php             ← Paso 3: Usuario
    └── login.php                        ← Para probar login después
```

---

## 🐛 Si algo no funciona...

### Error: Página blanca

**Causa:** Incluye mal al controlador

**Solución:** En register-step-2.php y step-3.php, verifica:
```php
require_once __DIR__ . '/../../app/controllers/RegisterController.php';
```

### Error: 404 en CSS/JS

**Causa:** Rutas relativas incorrectas

**Solución:** Las vistas están en `views/auth/`, así que usan:
```html
<link href="../../public/assets/css/auth.css" rel="stylesheet">
```

### Error: Validación rechaza datos válidos

**Causa:** Formato de entrada incorrecto

**Solución:**
- Matrícula: `2019010123` (exactamente 10 dígitos)
- CURP: `PEJI900112HDFRNN01` (exactamente 18 caracteres)
- ID Docente: `PROF1234` (6-8 caracteres)
- ID TI: `12345` (5-6 dígitos)

### Error: No redirige a siguiente paso

**Causa:** Sesión no iniciada

**Solución:** Cada archivo debe tener `session_start();` en la línea 1

---

## 📊 Qué Pasa en Cada Paso

### PASO 1: Role Selection
```
✓ Selecciona rol
✓ Guarda en sessionStorage (client-side)
✓ POST al servidor
✓ Redirecciona a Step 2
```

### PASO 2: Verification
```
✓ Lee rol del $_SESSION
✓ Muestra campos según rol
✓ Usuario ingresa datos
✓ Backend valida (RegisterController::validateVerification)
✓ Si OK: guarda en $_SESSION y va a Step 3
✓ Si error: muestra alert y reintenta
```

### PASO 3: Create User
```
✓ Lee datos verificados de $_SESSION
✓ Usuario ingresa nombre y apellidos
✓ Backend genera usuario y contraseña (RegisterController::createUser)
✓ Contraseña se hashea con bcrypt
✓ Se guarda en $_SESSION (no en BD aún)
✓ Muestra credenciales al usuario
✓ Usuario puede copiar y ir a login
```

---

## 🔐 Cosas Importantes de Seguridad

1. **Contraseñas:** Se hashean con bcrypt, no se guardan en texto
2. **Entrada:** Se sanitiza con htmlspecialchars()
3. **CSRF:** Protección en login.php ya existe
4. **Validación:** Cliente + Servidor (nunca confiar en cliente)
5. **Sesión:** Servidor-side con $_SESSION (más seguro que cookies)

---

## 📱 Testear en Móvil

1. Abre DevTools (F12) en Chrome
2. Click en el icono de móvil (arriba a la izquierda)
3. Selecciona dispositivo (ej: iPhone 12)
4. Actualiza la página
5. El registro debe verse bien en 380px (teléfono pequeño)

---

## 📈 Estadísticas del Código

| Métrica | Valor |
|---------|-------|
| Líneas RegisterController | ~400 |
| Líneas register-step-2.php | ~200 |
| Líneas register-step-3.php | ~230 |
| Métodos de validación | 3 (egresado, docente, ti) |
| Campos validados | 7+ |
| Errores posibles | 10+ diferentes |
| Seguridad: bcrypt | ✅ |
| Seguridad: htmlspecialchars | ✅ |
| Seguridad: regex validation | ✅ |

---

## 🎯 Próximos Pasos (Después de Testear)

1. **Conectar BD real**
   - Guardar usuario en tabla `usuarios`
   - Validar IDs contra BD de egresados/docentes/TI

2. **Email verification**
   - Generar token
   - Enviar email
   - Verificar token

3. **Rate limiting**
   - Máximo 5 intentos por IP
   - Bloquear 15 minutos

4. **OTP opcional**
   - SMS o Email OTP
   - Código de 6 dígitos

---

## 📚 Documentación Completa

Para más detalles, lee:

1. **REGISTRO.md** - Flujo del registro
2. **INTEGRACION_BACKEND.md** - Cómo integrar BD
3. **TESTING.md** - 9 casos de prueba
4. **ESTADO_FINAL.md** - Estado actual del proyecto

---

## ⚡ Comandos Útiles

### Ver si PHP es válido
```bash
php -l app/controllers/RegisterController.php
```

### Revisar el navegador
```
F12 → Application → Cookies y Session Storage
F12 → Console → Ver mensajes de error
F12 → Network → Ver requests POST
```

### Probar sin ir a navegador
```bash
# En terminal (Windows):
php public/index.php  # Si existe router

# O abre:
http://localhost/AppEgresados/views/auth/register-step-1.php
```

---

## 💡 Tips Pro

1. **Copiar credenciales:** Click en 📋 junto a usuario/password
2. **Debug:** Abre F12 mientras completas registro
3. **Reset sesión:** Cierra navegador y reabre
4. **Test diferente rol:** Usa Docente (más corto) para testing rápido
5. **Ver sesión:** Agrega `<?php echo json_encode($_SESSION); ?>` para debug

---

## ❓ Respuestas Frecuentes

**P: ¿Dónde se guardan los usuarios?**
A: En `$_SESSION` (servidor) por ahora. Conexión BD es próximo paso.

**P: ¿Puedo cambiar la contraseña luego?**
A: No está implementado aún, próximas fases.

**P: ¿Qué pasa si cierro el navegador?**
A: La sesión se pierde (por diseño, para seguridad).

**P: ¿Funciona en iPhone?**
A: Sí, el diseño es responsive 100%.

**P: ¿Los datos se guardan?**
A: No permanentemente. Solo se guardan si conectas BD.

---

**¡Listo para testear! 🚀**

Abre: http://localhost/AppEgresados/views/auth/register-step-1.php
