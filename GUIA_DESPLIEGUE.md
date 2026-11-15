# FoodMatch · Guía para publicarla en la web (y probarla en el celular)

Ruta: **tu PC → GitHub → Azure (crédito de estudiante) → link https → celular**.
Cada vez que subas cambios a GitHub, la imagen se reconstruye sola.

---
## 0. Antes de empezar (haz esto primero, es lo que más se demora)
1. Entra a https://azure.microsoft.com/free/students e inicia sesión con tu correo **@duocuc.cl**.
2. Verifica que eres estudiante (te manda un código al correo). **No pide tarjeta.**
3. Si DUOC no fuera aceptado, avísame y usamos otra opción.

## 1. Copiar el kit al proyecto
Copia dentro de `D:\laragon\www\foodmatch` (junto a `artisan`):
- `Dockerfile`
- `.dockerignore`
- `.env.production.example`
- carpeta `docker\`
- carpeta `.github\` (ojo: empieza con punto; activa "Elementos ocultos" en el Explorador si no la ves)

## 2. Subir el proyecto a GitHub
1. En https://github.com/new crea un repositorio llamado `foodmatch` (puede ser privado). No agregues README.
2. En el CMD:
   ```
   cd /d D:\laragon\www\foodmatch
   git init -b main
   git add .
   git status
   ```
   **Revisa que `.env` y `vendor` NO aparezcan en la lista** (son secretos/pesados). Si aparecen, avísame antes de seguir.
3. Luego:
   ```
   git commit -m "FoodMatch"
   git remote add origin https://github.com/TU_USUARIO/foodmatch.git
   git push -u origin main
   ```
   (cambia TU_USUARIO por tu usuario de GitHub; te pedirá iniciar sesión)

## 3. Esperar la imagen y hacerla pública
1. En tu repositorio, pestaña **Actions**: aparece "Construir imagen Docker". Espera el check verde (3–8 min).
2. Ve a tu perfil de GitHub → **Packages** → `foodmatch` → **Package settings** → *Change visibility* → **Public**.
   (Así Azure puede descargarla sin claves. No contiene tu .env.)

## 4. Crear la app en Azure
1. https://portal.azure.com → **Crear un recurso** → **Aplicación web** (Web App).
2. Pestaña Básico:
   - Suscripción: *Azure for Students*
   - Grupo de recursos: crear nuevo `foodmatch-rg`
   - Nombre: algo único, ej. `foodmatch-tunombre` (será `https://foodmatch-tunombre.azurewebsites.net`)
   - Publicar: **Contenedor** · Sistema operativo: **Linux**
   - Región: **Brazil South** (la más cercana disponible; si no deja, otra de América)
   - Plan: **Basic B1** (¡no el plan Free: se apaga a la hora!). El costo sale de tu crédito; revisa el precio que muestra.
3. Pestaña **Contenedor**:
   - Origen de imagen: *Otros registros de contenedor* · Acceso: **Público**
   - URL del servidor: `https://ghcr.io`
   - Imagen y etiqueta: `tu_usuario_github_en_minusculas/foodmatch:latest`
4. **Revisar y crear** → **Crear**.

## 5. Configurar variables
Portal → tu Web App → **Configuración** → **Variables de entorno** → agrega **una por una** las de `.env.production.example`:
- `APP_KEY` = la que está en tu `.env` local (línea APP_KEY=base64:...)
- `APP_URL` = `https://foodmatch-tunombre.azurewebsites.net`
- `APP_DEBUG` = `false`, `APP_ENV` = `production`
- `WEBSITES_ENABLE_APP_SERVICE_STORAGE` = `true` y `WEBSITES_PORT` = `80`
- `RUN_SEED` = `1` (solo la primera vez)
- Las de correo (Gmail) cuando tengas tu clave de aplicación
Guarda y **Reiniciar**. Espera 2–3 minutos y abre tu dirección.

## 6. Datos iniciales (una sola vez)
Portal → tu Web App → **Herramientas de desarrollo → SSH**. Escribe:
```
cd /var/www/html
php artisan foodmatch:importar-osm --radio=8000
php artisan db:seed --class=DemoMenusSeeder --force
```
Luego **regístrate en el sitio** con tu correo. Vuelve a SSH y hazte superadmin (debes ser el PRIMER admin):
```
php artisan tinker
>>> App\Models\User::where('email','TU_CORREO')->update(['es_admin'=>true,'email_verified_at'=>now()]);
>>> exit
```
Después cambia `RUN_SEED` a `0`.

## 7. Probar en el celular
1. Abre tu dirección https en Chrome (Android) o Safari (iPhone).
2. Permite la ubicación cuando la pida.
3. Menú del navegador → **Agregar a pantalla de inicio** → ábrela desde el ícono.
4. Checklist: deslizar el panel · punto azul · filtros · ficha y favoritos · login/registro · correo de verificación · panel Mi local (con un dueño de prueba).

## 8. Actualizar después
Cambias código → `git add . && git commit -m "cambio" && git push` → Actions reconstruye → en Azure pulsa **Reiniciar** (descarga la imagen nueva).

## 9. (Opcional) Archivo .apk de Android
Con la app ya publicada, https://www.pwabuilder.com genera un APK a partir de tu dirección.

## Notas
- La base SQLite y las fotos viven en `/home/data` (se conservan entre reinicios). Para producción seria se usaría MySQL.
- Nunca subas el `.env` ni claves a GitHub.
- Si algo falla: Portal → tu Web App → **Registro de transmisión** (Log stream) y cópiame el error.
