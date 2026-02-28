# Testing - Sistema de Registro (3 Pasos)

## URLs de Prueba

| Paso | URL | Tabla |
|------|-----|-------|
| 1 | http://localhost/AppEgresados/views/auth/register-step-1.php | Rol Selection |
| 2 | http://localhost/AppEgresados/views/auth/register-step-2.php | Verification |
| 3 | http://localhost/AppEgresados/views/auth/register-step-3.php | User Creation |
| Login | http://localhost/AppEgresados/views/auth/login.php | Prueba de Login |

## Test Case 1: Egresado

**Propósito:** Validar flujo completo de registro para egresado

**Pasos:**

1. Ir a Step 1: http://localhost/AppEgresados/views/auth/register-step-1.php
2. Seleccionar **Egresado** (debería resaltar en rojo)
3. Click **Continuar** → Debe redirigir a Step 2

**En Step 2:**
4. Ingresa Matrícula: `2019010123`
5. Ingresa CURP: `PEJI900112HDFRNN01`
6. Click **Continuar** → Debe validar y redirigir a Step 3

**En Step 3:**
7. Nombre: `Juan`
8. Apellidos: `Pérez López`
9. Click **Generar Credenciales** → Debe mostrar credenciales
10. Copiar usuario y contraseña
11. Click **Ir al Login**

**En Login:**
12. Email: `juan.perez@egresados.utp.edu.mx` (o lo que generó)
13. Password: La que se generó
14. Click **Entrar** → Debe autenticarse

**Validaciones:**
- ✅ Punto 2: El rol "Egresado" se muestra resaltado
- ✅ Punto 3: Redirección de Step 1 → Step 2
- ✅ Punto 6: Validación de matrícula/CURP
- ✅ Punto 9: Generación de credenciales
- ✅ Punto 14: Login exitoso con credenciales nuevas

---

## Test Case 2: Docente

**Propósito:** Validar flujo de registro para docente con diferente validación

**Pasos:**

1. Go to Step 1
2. Select **Docente**
3. Click **Continuar**

**En Step 2:**
4. ID Docente: `PROF1234`
5. Click **Continuar** → Valida ID y redirige a Step 3

**En Step 3:**
6. Nombre: `Carlos`
7. Apellidos: `Mendoza`
8. Click **Generar Credenciales**

**Validaciones:**
- ✅ Step 2 muestra SOLO campo "ID de Docente"
- ✅ Validación numérica: `PROF1234` (6-8 caracteres)
- ✅ Redirección exitosa a Step 3
- ✅ Credenciales generadas

---

## Test Case 3: Personal TI

**Propósito:** Validar flujo de registro para Personal TI

**Pasos:**

1. Go to Step 1
2. Select **Personal TI**
3. Click **Continuar**

**En Step 2:**
4. ID TI: `12345`
5. Click **Continuar**

**En Step 3:**
6. Nombre: `Roberto`
7. Apellidos: `García`
8. Click **Generar Credenciales**

**Validaciones:**
- ✅ Step 2 muestra SOLO campo "ID de TI"
- ✅ Validación: Debe ser 5-6 dígitos
- ✅ Redirección y generación exitosas

---

## Test Case 4: Validación de Errores

**Propósito:** Verificar que los errores se muestran correctamente

### Error 1: Matrícula inválida (formato)
1. Step 1 → Selecciona Egresado
2. Step 2 → Ingresa Matrícula: `abc` (menos de 10 dígitos)
3. Click **Continuar**
4. ❌ Debe mostrar error: "La matrícula debe tener 10 dígitos"

### Error 2: CURP inválido
1. Step 1 → Selecciona Egresado
2. Step 2 → 
   - Matrícula: `2019010123`
   - CURP: `abc` (menos de 18 caracteres)
3. Click **Continuar**
4. ❌ Debe mostrar error: "El CURP debe tener 18 caracteres"

### Error 3: Campos vacíos en Step 3
1. Complete Steps 1 y 2 exitosamente
2. Step 3 → No ingresa nombre ni apellidos
3. Click **Generar Credenciales**
4. ❌ Debe mostrar error: "El nombre y apellidos son requeridos"

---

## Test Case 5: Atrás (Back Navigation)

**Propósito:** Verificar botón "Atrás" funciona

1. Step 1 → Selecciona Egresado → **Continuar**
2. Step 2 → Click **Atrás**
3. ❌ Debe volver a Step 1
4. Selecciona **Docente** → **Continuar**
5. ✅ Debe mostrar Step 2 con campos de Docente
6. Click **Atrás**
7. ✅ Debe volver a Step 1
8. Selecciona **TI** → **Continuar**
9. ✅ Debe mostrar Step 2 con campos de TI

---

## Test Case 6: SessionStorage (Estado)

**Propósito:** Verificar que `sessionStorage` mantiene el estado

**Teste:**

1. Abre DevTools (F12 en Chrome)
2. Ve a Application → Session Storage
3. Step 1 → Selecciona **Egresado** → **Continuar**
4. En DevTools, busca clave: `registroRol`
5. ✅ Debe mostrar valor: `"egresado"`

6. Step 2 → Ingresa verificación → **Continuar**
7. En DevTools, busca: `registro_verificacion` (en $_SESSION PHP)
8. ✅ Los datos deben persistir en sesión del servidor

---

## Test Case 7: Credenciales Generadas

**Propósito:** Verificar generación y formato de credenciales

**Pasos:**

1. Completa registro hasta Step 3
2. Nombre: `José Luis`
3. Apellidos: `Rodríguez García`
4. Click **Generar Credenciales**
5. Verifica:
   - ❓ Usuario: `josé.luis.rodríguez` (sin acentos) o `jose.luis.rodriguez`
   - ❓ Contraseña: Mínimo 12 caracteres con mayúscula, minúscula, número y símbolo
   - ❓ Patrón: `Temporal[random]!`

---

## Test Case 8: Copiar al Portapapeles

**Propósito:** Verificar botón "Copiar"

**Pasos:**

1. Complete hasta Step 3 y genere credenciales
2. Click botón copiar (📋) junto a Usuario
3. ✅ El botón debe cambiar a ✓ por 2 segundos
4. Pega en un campo de texto (Ctrl+V)
5. ✅ Debe contener el usuario generado
6. Repite con botón de Contraseña
7. ✅ Debe contener la contraseña generada

---

## Test Case 9: Login Exitoso post-Registro

**Propósito:** Verificar que el usuario creado puede iniciar sesión

**Pasos:**

1. Complete todo el registry exitosamente
2. Copie credenciales (o anótelas)
3. Clic **Ir al Login**
4. Step Login:
   - Email: Ingresa el email generado
   - Contraseña: Ingresa la contraseña generada
5. Click **Entrar**
6. ✅ Debe redirigir a dashboard
7. ✅ Sesión está activa (`$_SESSION['logged_in'] === true`)

---

## Checklist de Validación

### Frontend
- [ ] Step 1: Selección de rol funciona (activa/inactiva)
- [ ] Step 2: Campos dinámicos según rol
- [ ] Step 3: Formulario con nombre y apellidos
- [ ] Botones "Continuar" y "Atrás" funcionan
- [ ] Alertas de error se muestran
- [ ] Copiar al portapapeles funciona
- [ ] Responsive en móvil (testear en DevTools 380px)

### Backend
- [ ] RegisterController instancia correctamente
- [ ] validateVerification() retorna success=true/false
- [ ] createUser() genera credenciales únicas
- [ ] Password se hashea con bcrypt
- [ ] Datos se guardan en sesión $_SESSION
- [ ] Redirecciones ocurren correctamente
- [ ] Errores se muestran en alerts

### Database (cuando esté plenamente integrada)
- [ ] `INSERT` en tabla `usuarios`
- [ ] Campos rellenados: nombre, apellidos, usuario, email, contraseña (hash)
- [ ] Usuario puede hacer login con credenciales generadas

---

## Comandos Útiles para Testing

### Ver sesión en PHP
```php
// Agregar en Step 2 o 3 para debug:
echo '<pre>';
echo 'SESSION:' . json_encode($_SESSION, JSON_PRETTY_PRINT);
echo '</pre>';
```

### Verificar clase RegisterController
```bash
# En terminal, desde raíz del proyecto:
php -l app/controllers/RegisterController.php  # Valida sintaxis
php -r "require 'app/controllers/RegisterController.php'; echo 'OK';"
```

### Ver SessionStorage en navegador
```javascript
// En consola del navegador (F12):
console.log(sessionStorage.getItem('registroRol'));
console.log(JSON.stringify(sessionStorage)); // Todos los items
```

---

## Posibles Errores y Soluciones

| Error | Causa | Solución |
|-------|-------|----------|
| 404 en RegisterController | Ruta incorrecta | Verificar: `__DIR__ . '/../../app/controllers/RegisterController.php'` |
| Redirección a Error | Sesión no iniciada | Verificar `session_start()` al inicio del archivo |
| Datos no persisten entre steps | sessionStorage vs $_SESSION | Cambiar a `$_SESSION` (más seguro) |
| Credenciales no generan | RegisterController no llamado | Verificar require_once y método createUser() |
| Contraseña no hasheada | password_hash() no usado | Verificar bcrypt en RegisterController line ~210 |
| Login falla con credenciales nuevas | Usuario no guardado en BD | Conectar método createUser() con insert en BD |

---

## Siguientes Pasos

1. ✅ Crear archivos registro-step-1/2/3.php
2. ✅ Crear RegisterController.php
3. ✅ Crear validaciones en backend
4. 🟡 **[TODO]** Conectar con base de datos real
5. 🟡 **[TODO]** Implementar email verification
6. 🟡 **[TODO]** Agregar rate limiting
7. 🟡 **[TODO]** Agregar logs de registro

---

## Contact Points

Si algo no funciona:

1. **Revisa JSON** del error en la respuesta POST
2. **Habilita Debug** agregando `$debug = true;` en RegisterController
3. **Revisa Logs** en `storage/logs/` (si existen)
4. **Valida BD** con query: `SELECT * FROM usuarios WHERE usuario LIKE ?`

**El sistema está 95% listo. Solo falta conectar con la BD real para persistencia.**
